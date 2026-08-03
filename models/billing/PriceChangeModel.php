<?php
/**
 * PriceChangeModel quản lý lịch sử đổi giá dịch vụ theo tháng hiệu lực.
 * Mục tiêu là giữ được audit trail và tính đúng hóa đơn ở mọi kỳ.
 */
class PriceChangeModel {
    /**
     * Chuẩn hóa một bản ghi lịch sử giá để DB thật và fallback trả cùng shape.
     */
    private static function normalizeRow(array $row) {
        return [
            'id' => (int)($row['id'] ?? 0),
            'service_id' => (int)($row['service_id'] ?? 0),
            'old_price' => (float)($row['old_price'] ?? 0),
            'new_price' => (float)($row['new_price'] ?? 0),
            'effective_month' => (int)($row['effective_month'] ?? 0),
            'effective_year' => (int)($row['effective_year'] ?? 0),
            'created_by' => isset($row['created_by']) && $row['created_by'] !== null ? (int)$row['created_by'] : null,
            'created_at' => $row['created_at'] ?? null,
            'service_name' => trim((string)($row['service_name'] ?? '')),
            'service_icon' => trim((string)($row['service_icon'] ?? 'settings')),
        ];
    }

    /**
     * Trả danh sách lịch sử đổi giá, có thể lọc theo dịch vụ.
     */
    public static function getAll(array $filters = []) {
        $serviceId = (int)($filters['service_id'] ?? 0);

        if (Database::hasConnection()) {
            $sql = "
                SELECT
                    pc.*,
                    s.name AS service_name,
                    s.icon AS service_icon
                FROM price_changes pc
                INNER JOIN services s ON s.id = pc.service_id
                WHERE 1 = 1
            ";
            $params = [];

            if ($serviceId > 0) {
                $sql .= ' AND pc.service_id = ?';
                $params[] = $serviceId;
            }

            $sql .= ' ORDER BY pc.effective_year DESC, pc.effective_month DESC, pc.id DESC';
            return array_map([self::class, 'normalizeRow'], Database::fetchAll($sql, $params));
        }

        $services = [];
        foreach (ServiceModel::getAll() as $service) {
            $services[(int)($service['id'] ?? 0)] = $service;
        }

        $rows = array_map(static function ($row) use ($services) {
            $service = $services[(int)($row['service_id'] ?? 0)] ?? [];
            $row['service_name'] = $service['name'] ?? '';
            $row['service_icon'] = $service['icon'] ?? 'settings';
            return self::normalizeRow($row);
        }, Database::getTable('price_changes'));

        if ($serviceId > 0) {
            $rows = array_values(array_filter($rows, static fn($row) => (int)($row['service_id'] ?? 0) === $serviceId));
        }

        usort($rows, static function ($left, $right) {
            $leftOrder = ((int)($left['effective_year'] ?? 0) * 100) + (int)($left['effective_month'] ?? 0);
            $rightOrder = ((int)($right['effective_year'] ?? 0) * 100) + (int)($right['effective_month'] ?? 0);
            if ($leftOrder !== $rightOrder) {
                return $rightOrder <=> $leftOrder;
            }

            return (int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0);
        });

        return array_values($rows);
    }

    /**
     * Lấy toàn bộ lịch sử của một dịch vụ theo thứ tự thời gian tăng dần.
     */
    public static function getHistoryByServiceId($serviceId) {
        $resolvedServiceId = (int)$serviceId;
        if ($resolvedServiceId <= 0) {
            return [];
        }

        $rows = self::getAll(['service_id' => $resolvedServiceId]);
        usort($rows, static function ($left, $right) {
            $leftOrder = ((int)($left['effective_year'] ?? 0) * 100) + (int)($left['effective_month'] ?? 0);
            $rightOrder = ((int)($right['effective_year'] ?? 0) * 100) + (int)($right['effective_month'] ?? 0);
            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
        });

        return $rows;
    }

    /**
     * Lưu lịch sử đổi giá và cập nhật giá hiện tại của dịch vụ.
     * Đồng thời tự phát sinh thông báo cho cư dân theo yêu cầu nghiệp vụ.
     */
    public static function saveChange($serviceId, $newPrice, $effectiveMonth, $effectiveYear, $createdBy = null) {
        $service = ServiceModel::getById((int)$serviceId);
        if (!$service) {
            throw new RuntimeException('Dịch vụ cần đổi giá không tồn tại.');
        }

        $resolvedNewPrice = (float)$newPrice;
        if ($resolvedNewPrice <= 0) {
            throw new RuntimeException('Giá mới phải lớn hơn 0.');
        }

        $period = MeterReadingModel::normalizePeriod($effectiveMonth, $effectiveYear);
        $targetOrder = ($period['year'] * 100) + $period['month'];
        $currentOrder = ((int)date('Y') * 100) + (int)date('n');
        if ($targetOrder <= $currentOrder) {
            throw new RuntimeException('Tháng hiệu lực phải lớn hơn tháng hiện tại.');
        }
        if (self::existsForPeriod((int)$serviceId, $period['month'], $period['year'])) {
            throw new RuntimeException('Dịch vụ này đã có lịch đổi giá cho đúng tháng hiệu lực đã chọn.');
        }

        $oldPrice = (float)($service['price'] ?? 0);
        if ($oldPrice === $resolvedNewPrice) {
            throw new RuntimeException('Giá mới đang trùng với giá hiện tại của dịch vụ.');
        }

        $connection = Database::hasConnection() ? Database::getInstance() : null;
        $useTransaction = $connection instanceof PDO;

        if ($useTransaction) {
            $connection->beginTransaction();
        }

        try {
            $priceChangeId = (int)Database::insert('price_changes', [
                'service_id' => (int)($service['id'] ?? 0),
                'old_price' => $oldPrice,
                'new_price' => $resolvedNewPrice,
                'effective_month' => $period['month'],
                'effective_year' => $period['year'],
                'created_by' => $createdBy !== null ? (int)$createdBy : null,
            ]);

            Database::update(
                'services',
                ['price' => $resolvedNewPrice],
                'id = :id',
                ['id' => (int)($service['id'] ?? 0)]
            );

            NotificationModel::create([
                'user_id' => null,
                'title' => 'Thay đổi giá dịch vụ',
                'content' => self::buildNotificationContent($service, $oldPrice, $resolvedNewPrice, $period['month'], $period['year']),
                'type' => 'price_change',
            ]);

            if ($useTransaction) {
                $connection->commit();
            }

            return $priceChangeId;
        } catch (Throwable $exception) {
            if ($useTransaction && $connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Suy ra giá đúng của dịch vụ tại một kỳ bất kỳ.
     * Rule: ưu tiên change mới nhất đã có hiệu lực; nếu kỳ đang hỏi nằm trước lần đổi đầu tiên thì dùng `old_price` của lần đổi đầu.
     */
    public static function getEffectivePriceForPeriod($serviceId, $month, $year, $fallbackPrice = 0.0) {
        $history = self::getHistoryByServiceId((int)$serviceId);
        if (empty($history)) {
            return (float)$fallbackPrice;
        }

        $targetOrder = ((int)$year * 100) + (int)$month;
        $latestPastOrCurrent = null;
        $earliestFuture = null;

        foreach ($history as $row) {
            $rowOrder = ((int)($row['effective_year'] ?? 0) * 100) + (int)($row['effective_month'] ?? 0);

            if ($rowOrder <= $targetOrder) {
                $latestPastOrCurrent = $row;
                continue;
            }

            if ($earliestFuture === null) {
                $earliestFuture = $row;
            }
        }

        if ($latestPastOrCurrent !== null) {
            return (float)($latestPastOrCurrent['new_price'] ?? $fallbackPrice);
        }

        if ($earliestFuture !== null) {
            return (float)($earliestFuture['old_price'] ?? $fallbackPrice);
        }

        return (float)$fallbackPrice;
    }

    /**
     * Tạo câu thông báo broadcast khi admin đổi giá thành công.
     */
    public static function buildNotificationContent(array $service, $oldPrice, $newPrice, $effectiveMonth, $effectiveYear) {
        return trim((string)($service['name'] ?? 'Dịch vụ'))
            . ': '
            . number_format((float)$oldPrice, 0, ',', '.')
            . 'đ → '
            . number_format((float)$newPrice, 0, ',', '.')
            . 'đ/'
            . trim((string)($service['unit'] ?? 'tháng'))
            . ', áp dụng từ tháng '
            . str_pad((string)(int)$effectiveMonth, 2, '0', STR_PAD_LEFT)
            . '/'
            . (int)$effectiveYear
            . '.';
    }

    /**
     * Kiểm tra một dịch vụ đã có lịch đổi giá ở đúng kỳ hay chưa.
     */
    private static function existsForPeriod($serviceId, $month, $year) {
        $resolvedServiceId = (int)$serviceId;
        $resolvedMonth = (int)$month;
        $resolvedYear = (int)$year;

        if (Database::hasConnection()) {
            return (bool)Database::fetchOne(
                'SELECT id FROM price_changes WHERE service_id = ? AND effective_month = ? AND effective_year = ? LIMIT 1',
                [$resolvedServiceId, $resolvedMonth, $resolvedYear]
            );
        }

        foreach (Database::getTable('price_changes') as $row) {
            if (
                (int)($row['service_id'] ?? 0) === $resolvedServiceId
                && (int)($row['effective_month'] ?? 0) === $resolvedMonth
                && (int)($row['effective_year'] ?? 0) === $resolvedYear
            ) {
                return true;
            }
        }

        return false;
    }
}
