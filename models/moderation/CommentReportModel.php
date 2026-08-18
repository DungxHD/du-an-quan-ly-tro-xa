<?php
/**
 * Quản lý báo cáo cộng đồng cho comment:
 * - tenant gửi báo cáo,
 * - admin lọc danh sách,
 * - admin giải quyết hoặc bác bỏ hàng loạt theo comment.
 */
class CommentReportModel {
    /**
     * Tạo bộ filter chuẩn cho màn admin reports.
     */
    public static function normalizeFilters(array $filters = []) {
        $status = trim((string)($filters['status'] ?? ''));

        return [
            'status' => in_array($status, ['pending', 'resolved', 'dismissed'], true) ? $status : '',
            'keyword' => trim((string)($filters['keyword'] ?? '')),
        ];
    }

    /**
     * Tenant gửi báo cáo cho một comment đang công khai.
     */
    public static function create($commentId, $userId, $reason) {
        $commentId = (int)$commentId;
        $userId = (int)$userId;
        $reason = trim((string)$reason);

        if ($commentId <= 0) {
            throw new RuntimeException('Đánh giá cần báo cáo không hợp lệ.');
        }

        if ($userId <= 0) {
            throw new RuntimeException('Bạn cần đăng nhập để báo cáo đánh giá.');
        }

        if ($reason === '') {
            throw new RuntimeException('Vui lòng nhập lý do báo cáo.');
        }

        $comment = CommentModel::getById($commentId);
        if (!$comment) {
            throw new RuntimeException('Đánh giá không tồn tại hoặc đã bị xóa.');
        }

        if ((int)($comment['user_id'] ?? 0) === $userId) {
            throw new RuntimeException('Bạn không thể tự báo cáo đánh giá của chính mình.');
        }

        if ((int)($comment['status'] ?? 0) !== 1) {
            throw new RuntimeException('Đánh giá này hiện không còn ở trạng thái công khai để báo cáo.');
        }

        if (self::hasExistingReport($commentId, $userId)) {
            throw new RuntimeException('Bạn đã báo cáo đánh giá này trước đó.');
        }

        Database::insert('comment_reports', [
            'comment_id' => $commentId,
            'user_id' => $userId,
            'reason' => mb_substr($reason, 0, 255, 'UTF-8'),
            'status' => 'pending',
        ]);

        return true;
    }

    /**
     * Lấy danh sách báo cáo để admin duyệt.
     */
    public static function getAdminReports(array $filters = []) {
        $filters = self::normalizeFilters($filters);

        if (Database::hasConnection()) {
            $sql = "
                SELECT
                    cr.*,
                    c.room_id,
                    c.content AS comment_content,
                    c.rating AS comment_rating,
                    c.status AS comment_status,
                    r.name AS room_name,
                    reporter.full_name AS reporter_name,
                    owner.full_name AS comment_author_name
                FROM comment_reports cr
                INNER JOIN comments c ON c.id = cr.comment_id
                INNER JOIN rooms r ON r.id = c.room_id
                INNER JOIN users reporter ON reporter.id = cr.user_id
                INNER JOIN users owner ON owner.id = c.user_id
                WHERE 1 = 1
            ";
            $params = [];

            if ($filters['status'] !== '') {
                $sql .= ' AND cr.status = ?';
                $params[] = $filters['status'];
            }

            if ($filters['keyword'] !== '') {
                $sql .= ' AND (reporter.full_name LIKE ? OR owner.full_name LIKE ? OR r.name LIKE ? OR cr.reason LIKE ? OR c.content LIKE ?)';
                $keyword = '%' . $filters['keyword'] . '%';
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
            }

            $sql .= " ORDER BY CASE cr.status WHEN 'pending' THEN 0 WHEN 'resolved' THEN 1 ELSE 2 END ASC, cr.created_at DESC";
            return array_map([self::class, 'normalizeRow'], Database::fetchAll($sql, $params));
        }

        $reports = self::buildFallbackRows();
        $reports = array_filter($reports, static function ($row) use ($filters) {
            if ($filters['status'] !== '' && (string)($row['status'] ?? '') !== $filters['status']) {
                return false;
            }

            if ($filters['keyword'] !== '') {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    (string)($row['reporter_name'] ?? ''),
                    (string)($row['comment_author_name'] ?? ''),
                    (string)($row['room_name'] ?? ''),
                    (string)($row['reason'] ?? ''),
                    (string)($row['comment_content'] ?? ''),
                ])), 'UTF-8');

                if (mb_strpos($haystack, mb_strtolower($filters['keyword'], 'UTF-8'), 0, 'UTF-8') === false) {
                    return false;
                }
            }

            return true;
        });

        usort($reports, [self::class, 'compareRows']);
        return array_values($reports);
    }

    /**
     * Lấy một báo cáo theo ID.
     */
    public static function getById($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        foreach (self::getAdminReports() as $row) {
            if ((int)($row['id'] ?? 0) === $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Admin xử lý báo cáo. `resolve` sẽ ẩn comment và chuyển toàn bộ report pending cùng comment sang resolved.
     */
    public static function resolve($reportId, $action) {
        $reportId = (int)$reportId;
        $action = trim((string)$action);
        $targetStatus = $action === 'resolve' ? 'resolved' : ($action === 'dismiss' ? 'dismissed' : '');

        if ($reportId <= 0 || $targetStatus === '') {
            throw new RuntimeException('Thao tác xử lý báo cáo không hợp lệ.');
        }

        $report = self::getById($reportId);
        if (!$report) {
            throw new RuntimeException('Báo cáo không tồn tại hoặc đã bị xóa.');
        }

        $commentId = (int)($report['comment_id'] ?? 0);
        if ($commentId <= 0) {
            throw new RuntimeException('Báo cáo này không còn liên kết tới đánh giá hợp lệ.');
        }

        if ($targetStatus === 'resolved') {
            Database::update('comments', ['status' => 0], 'id = :id', ['id' => $commentId]);
        }

        self::updatePendingReportsByComment($commentId, $targetStatus);

        return [
            'action' => $targetStatus,
            'comment_id' => $commentId,
        ];
    }

    /**
     * Trả thống kê nhanh cho màn quản trị báo cáo.
     */
    public static function getStats(array $rows) {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'resolved' => 0,
            'dismissed' => 0,
        ];

        foreach ($rows as $row) {
            $stats['total']++;
            $status = (string)($row['status'] ?? 'pending');
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return $stats;
    }

    /**
     * Kiểm tra user đã report comment này hay chưa.
     */
    private static function hasExistingReport($commentId, $userId) {
        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                'SELECT id FROM comment_reports WHERE comment_id = ? AND user_id = ? LIMIT 1',
                [(int)$commentId, (int)$userId]
            );
            return $row !== null;
        }

        foreach (Database::getTable('comment_reports') as $row) {
            if ((int)($row['comment_id'] ?? 0) === (int)$commentId && (int)($row['user_id'] ?? 0) === (int)$userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cập nhật toàn bộ report pending cùng comment sang trạng thái mới để admin không phải xử lý lặp.
     */
    private static function updatePendingReportsByComment($commentId, $status) {
        $commentId = (int)$commentId;

        if (Database::hasConnection()) {
            Database::query(
                'UPDATE comment_reports SET status = ? WHERE comment_id = ? AND status = ?',
                [$status, $commentId, 'pending']
            );
            return;
        }

        $rows = Database::getTable('comment_reports');
        foreach ($rows as $index => $row) {
            if ((int)($row['comment_id'] ?? 0) === $commentId && (string)($row['status'] ?? 'pending') === 'pending') {
                $rows[$index]['status'] = $status;
            }
        }
        Database::setTable('comment_reports', $rows);
    }

    /**
     * Chuẩn hóa một dòng report để view không phải tự suy diễn.
     */
    private static function normalizeRow(array $row) {
        $row['id'] = (int)($row['id'] ?? 0);
        $row['comment_id'] = (int)($row['comment_id'] ?? 0);
        $row['user_id'] = (int)($row['user_id'] ?? 0);
        $row['room_id'] = (int)($row['room_id'] ?? 0);
        $row['comment_status'] = (int)($row['comment_status'] ?? 1);
        $row['comment_rating'] = max(1, min(5, (int)($row['comment_rating'] ?? 5)));
        $row['status'] = in_array((string)($row['status'] ?? ''), ['pending', 'resolved', 'dismissed'], true)
            ? (string)$row['status']
            : 'pending';
        $row['reason'] = trim((string)($row['reason'] ?? ''));
        $row['comment_content'] = trim((string)($row['comment_content'] ?? ''));
        $row['room_name'] = trim((string)($row['room_name'] ?? ''));
        $row['reporter_name'] = trim((string)($row['reporter_name'] ?? '')) ?: 'Người dùng';
        $row['comment_author_name'] = trim((string)($row['comment_author_name'] ?? '')) ?: 'Người đánh giá';
        $row['created_at_label'] = !empty($row['created_at']) && strtotime((string)$row['created_at']) !== false
            ? date('d/m/Y H:i', strtotime((string)$row['created_at']))
            : '';
        $row['status_label'] = [
            'pending' => 'Chờ xử lý',
            'resolved' => 'Đã giải quyết',
            'dismissed' => 'Đã bác bỏ',
        ][$row['status']];

        return $row;
    }

    /**
     * Dựng report rows khi chạy bằng fallback data.
     */
    private static function buildFallbackRows() {
        $comments = [];
        foreach (Database::getTable('comments') as $comment) {
            $comments[(int)($comment['id'] ?? 0)] = $comment;
        }

        $rooms = [];
        foreach (Database::getTable('rooms') as $room) {
            $rooms[(int)($room['id'] ?? 0)] = $room;
        }

        $users = [];
        foreach (Database::getTable('users') as $user) {
            $users[(int)($user['id'] ?? 0)] = $user;
        }

        return array_map(static function ($report) use ($comments, $rooms, $users) {
            $comment = $comments[(int)($report['comment_id'] ?? 0)] ?? [];
            $room = $rooms[(int)($comment['room_id'] ?? 0)] ?? [];
            $reporter = $users[(int)($report['user_id'] ?? 0)] ?? [];
            $owner = $users[(int)($comment['user_id'] ?? 0)] ?? [];

            $report['room_id'] = (int)($comment['room_id'] ?? 0);
            $report['comment_content'] = $comment['content'] ?? '';
            $report['comment_rating'] = $comment['rating'] ?? 5;
            $report['comment_status'] = $comment['status'] ?? 1;
            $report['room_name'] = $room['name'] ?? '';
            $report['reporter_name'] = $reporter['full_name'] ?? 'Người dùng';
            $report['comment_author_name'] = $owner['full_name'] ?? 'Người đánh giá';

            return self::normalizeRow($report);
        }, Database::getTable('comment_reports'));
    }

    /**
     * So sánh theo thứ tự ưu tiên: pending trước, sau đó mới đến thời gian tạo mới nhất.
     */
    private static function compareRows(array $left, array $right) {
        $order = ['pending' => 0, 'resolved' => 1, 'dismissed' => 2];
        $statusCompare = ($order[$left['status']] ?? 99) <=> ($order[$right['status']] ?? 99);
        if ($statusCompare !== 0) {
            return $statusCompare;
        }

        return strcmp((string)($right['created_at'] ?? ''), (string)($left['created_at'] ?? ''));
    }
}
