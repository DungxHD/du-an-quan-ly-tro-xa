<?php
/**
 * Quản lý danh sách từ cấm và bộ lọc offline cho bình luận.
 * Dữ liệu được lưu ở dạng đã chuẩn hóa để so khớp ổn định kể cả khi người dùng gõ có dấu.
 */
class BannedWordModel {
    /**
     * Trả danh sách type hợp lệ để controller/view dùng chung một nguồn.
     */
    public static function getTypeOptions() {
        return [
            'word' => 'Từ đơn',
            'phrase' => 'Cụm từ',
            'abbreviation' => 'Viết tắt',
        ];
    }

    /**
     * Chuẩn hóa bộ lọc cho trang admin để GET/POST dùng cùng một shape.
     */
    public static function normalizeFilters(array $filters = []) {
        $type = trim((string)($filters['type'] ?? ''));

        return [
            'keyword' => self::normalizeWord((string)($filters['keyword'] ?? '')),
            'type' => array_key_exists($type, self::getTypeOptions()) ? $type : '',
            'is_active' => in_array((string)($filters['is_active'] ?? ''), ['0', '1'], true)
                ? (string)$filters['is_active']
                : '',
        ];
    }

    /**
     * Lấy danh sách từ cấm cho admin kèm filter từ khóa/type/trạng thái hoạt động.
     */
    public static function getAll(array $filters = []) {
        $filters = self::normalizeFilters($filters);

        if (Database::hasConnection()) {
            $sql = 'SELECT * FROM banned_words WHERE 1 = 1';
            $params = [];

            if ($filters['keyword'] !== '') {
                $sql .= ' AND word LIKE ?';
                $params[] = '%' . $filters['keyword'] . '%';
            }

            if ($filters['type'] !== '') {
                $sql .= ' AND type = ?';
                $params[] = $filters['type'];
            }

            if ($filters['is_active'] !== '') {
                $sql .= ' AND is_active = ?';
                $params[] = (int)$filters['is_active'];
            }

            $sql .= ' ORDER BY is_active DESC, CHAR_LENGTH(word) DESC, id DESC';
            return array_map([self::class, 'normalizeRow'], Database::fetchAll($sql, $params));
        }

        $rows = array_filter(Database::getTable('banned_words'), static function ($row) use ($filters) {
            $word = self::normalizeWord((string)($row['word'] ?? ''));
            $type = (string)($row['type'] ?? 'word');
            $isActive = (int)($row['is_active'] ?? 1);

            if ($filters['keyword'] !== '' && mb_strpos($word, $filters['keyword'], 0, 'UTF-8') === false) {
                return false;
            }

            if ($filters['type'] !== '' && $type !== $filters['type']) {
                return false;
            }

            if ($filters['is_active'] !== '' && $isActive !== (int)$filters['is_active']) {
                return false;
            }

            return true;
        });

        $rows = array_map([self::class, 'normalizeRow'], $rows);
        usort($rows, static function ($left, $right) {
            $activeCompare = (int)($right['is_active'] ?? 0) <=> (int)($left['is_active'] ?? 0);
            if ($activeCompare !== 0) {
                return $activeCompare;
            }

            $lengthCompare = mb_strlen((string)($right['word'] ?? ''), 'UTF-8') <=> mb_strlen((string)($left['word'] ?? ''), 'UTF-8');
            if ($lengthCompare !== 0) {
                return $lengthCompare;
            }

            return (int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0);
        });

        return array_values($rows);
    }

    /**
     * Lấy danh sách từ đang bật để pipeline moderation dùng trực tiếp.
     */
    public static function getActiveWords() {
        if (Database::hasConnection()) {
            return array_map(
                [self::class, 'normalizeRow'],
                Database::fetchAll('SELECT * FROM banned_words WHERE is_active = 1 ORDER BY CHAR_LENGTH(word) DESC, id ASC')
            );
        }

        $rows = array_filter(Database::getTable('banned_words'), static fn($row) => (int)($row['is_active'] ?? 1) === 1);
        $rows = array_map([self::class, 'normalizeRow'], $rows);
        usort($rows, static function ($left, $right) {
            return mb_strlen((string)($right['word'] ?? ''), 'UTF-8') <=> mb_strlen((string)($left['word'] ?? ''), 'UTF-8');
        });

        return array_values($rows);
    }

    /**
     * Lấy chi tiết một từ cấm để sửa.
     */
    public static function getById($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        $row = Database::hasConnection()
            ? Database::fetchOne('SELECT * FROM banned_words WHERE id = ? LIMIT 1', [$id])
            : Database::find('banned_words', $id);

        return $row ? self::normalizeRow($row) : null;
    }

    /**
     * Lưu mới hoặc cập nhật từ cấm. `word` luôn được ép về dạng chuẩn hóa trước khi lưu.
     */
    public static function save(array $data, $id = null) {
        $typeOptions = self::getTypeOptions();
        $normalizedWord = self::normalizeWord((string)($data['word'] ?? ''));
        $type = trim((string)($data['type'] ?? 'word'));
        $replacement = trim((string)($data['replacement'] ?? '***')) ?: '***';
        $isActive = !empty($data['is_active']) ? 1 : 0;
        $id = (int)$id;

        if ($normalizedWord === '') {
            throw new RuntimeException('Từ cấm không được để trống sau khi chuẩn hóa.');
        }

        if (!array_key_exists($type, $typeOptions)) {
            throw new RuntimeException('Loại từ cấm không hợp lệ.');
        }

        $duplicate = self::findDuplicate($normalizedWord, $id > 0 ? $id : null);
        if ($duplicate) {
            throw new RuntimeException('Từ cấm này đã tồn tại trong danh sách.');
        }

        $payload = [
            'word' => $normalizedWord,
            'type' => $type,
            'replacement' => mb_substr($replacement, 0, 20, 'UTF-8'),
            'is_active' => $isActive,
        ];

        if ($id > 0) {
            if (!self::getById($id)) {
                throw new RuntimeException('Từ cấm cần cập nhật không tồn tại.');
            }

            Database::update('banned_words', $payload, 'id = :id', ['id' => $id]);
            return $id;
        }

        return (int)Database::insert('banned_words', $payload);
    }

    /**
     * Xóa một từ cấm khỏi danh sách.
     */
    public static function delete($id) {
        $word = self::getById($id);
        if (!$word) {
            throw new RuntimeException('Từ cấm không tồn tại hoặc đã bị xóa.');
        }

        Database::delete('banned_words', 'id = :id', ['id' => (int)$id]);
        return true;
    }

    /**
     * Trả thống kê nhanh cho view admin.
     */
    public static function getStats(array $rows) {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'word' => 0,
            'phrase' => 0,
            'abbreviation' => 0,
        ];

        foreach ($rows as $row) {
            $stats['total']++;
            $stats[(int)($row['is_active'] ?? 0) === 1 ? 'active' : 'inactive']++;
            $type = (string)($row['type'] ?? 'word');
            if (isset($stats[$type])) {
                $stats[$type]++;
            }
        }

        return $stats;
    }

    /**
     * Quét nội dung theo danh sách từ cấm đang bật và thay phần khớp bằng chuỗi replacement.
     */
    public static function sanitizeContent($content) {
        $sanitized = trim((string)$content);
        $flaggedWords = [];

        if ($sanitized === '') {
            return [
                'content' => null,
                'flagged_words' => [],
            ];
        }

        foreach (self::getActiveWords() as $word) {
            $normalizedWord = trim((string)($word['word'] ?? ''));
            if ($normalizedWord === '') {
                continue;
            }

            $pattern = self::buildContentPattern($normalizedWord, (string)($word['type'] ?? 'word'));
            $replacement = trim((string)($word['replacement'] ?? '***')) ?: '***';
            $matched = false;

            $sanitized = (string)preg_replace_callback($pattern, static function () use (&$matched, $replacement) {
                $matched = true;
                return $replacement;
            }, $sanitized);

            if ($matched) {
                $flaggedWords[] = $normalizedWord;
            }
        }

        return [
            'content' => trim($sanitized),
            'flagged_words' => array_values(array_unique($flaggedWords)),
        ];
    }

    /**
     * Chuẩn hóa từ/cụm từ: bỏ dấu, chữ thường, bỏ ký tự rác và ép khoảng trắng gọn lại.
     */
    public static function normalizeWord($word) {
        $word = trim((string)$word);
        if ($word === '') {
            return '';
        }

        $word = mb_strtolower($word, 'UTF-8');
        $word = self::stripVietnameseAccents($word);
        $word = preg_replace('/[^a-z0-9\s]+/u', ' ', $word);
        $word = preg_replace('/\s+/u', ' ', (string)$word);

        return trim((string)$word);
    }

    /**
     * Chuẩn hóa row lấy từ DB/fallback và gắn thêm label cho view.
     */
    private static function normalizeRow(array $row) {
        $typeOptions = self::getTypeOptions();
        $row['id'] = (int)($row['id'] ?? 0);
        $row['word'] = self::normalizeWord((string)($row['word'] ?? ''));
        $row['type'] = array_key_exists((string)($row['type'] ?? ''), $typeOptions) ? (string)$row['type'] : 'word';
        $row['replacement'] = trim((string)($row['replacement'] ?? '***')) ?: '***';
        $row['is_active'] = (int)($row['is_active'] ?? 1);
        $row['type_label'] = $typeOptions[$row['type']] ?? 'Từ đơn';
        $row['created_at_label'] = !empty($row['created_at']) && strtotime((string)$row['created_at']) !== false
            ? date('d/m/Y H:i', strtotime((string)$row['created_at']))
            : '';

        return $row;
    }

    /**
     * Tìm bản ghi trùng theo `word` đã chuẩn hóa để chặn duplicate trước khi lưu.
     */
    private static function findDuplicate($normalizedWord, $excludeId = null) {
        foreach (self::getAll() as $row) {
            if ((string)($row['word'] ?? '') !== $normalizedWord) {
                continue;
            }

            if ($excludeId !== null && (int)($row['id'] ?? 0) === (int)$excludeId) {
                continue;
            }

            return $row;
        }

        return null;
    }

    /**
     * Dựng regex khớp trực tiếp trên nội dung gốc nhưng vẫn chịu được chữ có dấu.
     */
    private static function buildContentPattern($normalizedWord, $type) {
        $characters = preg_split('//u', $normalizedWord, -1, PREG_SPLIT_NO_EMPTY);
        $segments = [];

        foreach ($characters as $character) {
            if ($character === ' ') {
                $segments[] = '(?:[\s\p{P}]+)';
                continue;
            }

            $segments[] = self::buildAccentAwareToken($character);
        }

        $body = implode('', $segments);
        if (in_array($type, ['word', 'abbreviation'], true)) {
            return '/(?<![\p{L}\p{N}_])' . $body . '(?![\p{L}\p{N}_])/iu';
        }

        return '/' . $body . '/iu';
    }

    /**
     * Tạo token regex cho từng ký tự ASCII để khớp cả biến thể tiếng Việt có dấu.
     */
    private static function buildAccentAwareToken($character) {
        $map = [
            'a' => '[aàáạảãăằắặẳẵâầấậẩẫ]',
            'e' => '[eèéẹẻẽêềếệểễ]',
            'i' => '[iìíịỉĩ]',
            'o' => '[oòóọỏõôồốộổỗơờớợởỡ]',
            'u' => '[uùúụủũưừứựửữ]',
            'y' => '[yỳýỵỷỹ]',
            'd' => '[dđ]',
        ];

        return $map[$character] ?? preg_quote($character, '/');
    }

    /**
     * Bỏ dấu tiếng Việt bằng chuỗi map tĩnh để không phụ thuộc extension máy chủ.
     */
    private static function stripVietnameseAccents($text) {
        $map = [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        ];

        return strtr($text, $map);
    }
}
