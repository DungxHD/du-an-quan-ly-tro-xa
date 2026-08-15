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
            'old_billing_mode' => $row['old_billing_mode'] ?? null,
            'new_billing_mode' => $row['new_billing_mode'] ?? null,
            'applied' => (int)($row['applied'] ?? 0) === 1 ? 1 : 0,
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
    public static function scheduleServiceChange($serviceId, $newPrice, $newBillingMode, $effectiveMonth, $effectiveYear, $createdBy = null) {
        $service = ServiceModel::getById((int)$serviceId);
        if (!$service) { throw new RuntimeException('Dịch vụ cần đổi giá không tồn tại.'); }
        $currentPrice = (float)($service['price'] ?? 0);
        $currentMode = (string)($service['billing_mode'] ?? 'fixed');
        $hasPriceChange = $newPrice !== null && abs((float)$newPrice - $currentPrice) > 0.001;
        $hasModeChange = $newBillingMode !== null && $newBillingMode !== $currentMode;
        if (!$hasPriceChange && !$hasModeChange) { throw new RuntimeException('Giá và cách tính mới đang trùng hiện tại, không có thay đổi.'); }
        if ($hasPriceChange && (float)$newPrice <= 0) { throw new RuntimeException('Giá mới phải lớn hơn 0.'); }
        if ($hasModeChange) {
            $allowed = ServiceModel::getKindBillingModesMap()[$service['kind'] ?? 'other'] ?? ServiceModel::BILLING_MODES;
            if (!in_array($newBillingMode, $allowed, true)) { throw new RuntimeException('Cách tính này không được phép cho loại dịch vụ này.'); }
        }
        $period = MeterReadingModel::normalizePeriod($effectiveMonth, $effectiveYear);
        $targetOrder = ($period['year'] * 100) + $period['month'];
        $currentOrder = ((int)date('Y') * 100) + (int)date('n');
        if ($targetOrder <= $currentOrder) { throw new RuntimeException('Tháng hiệu lực phải lớn hơn tháng hiện tại.'); }
        if (self::existsForPeriod((int)$serviceId, $period['month'], $period['year'])) { throw new RuntimeException('Dịch vụ này đã có lịch thay đổi cho đúng tháng hiệu lực đã chọn.'); }
        $priceChangeId = (int)Database::insert('price_changes', [
            'service_id' => (int)$service['id'],
            'old_price' => $currentPrice,
            'new_price' => $hasPriceChange ? (float)$newPrice : $currentPrice,
            'old_billing_mode' => $currentMode,
            'new_billing_mode' => $hasModeChange ? $newBillingMode : null,
            'effective_month' => $period['month'],
            'effective_year' => $period['year'],
            'applied' => 0,
            'created_by' => $createdBy !== null ? (int)$createdBy : null,
        ]);
        $roomsUsing = ServiceModel::getRoomsUsingService((int)$service['id']);
        $affectedRoomIds = array_map(
            static fn($roomRow) => (int)($roomRow['room_id'] ?? 0),
            $roomsUsing
        );
        $affectedRoomIds = array_values(array_filter($affectedRoomIds, static fn($roomId) => $roomId > 0));
        NotificationModel::createForRoomUsers($affectedRoomIds, [
            'title' => 'Thay đổi giá/cách tính dịch vụ ' . trim((string)($service['name'] ?? 'Dịch vụ')),
            'content' => self::buildNotificationContent($service, $currentPrice, $hasPriceChange ? (float)$newPrice : $currentPrice, $period['month'], $period['year'], $hasModeChange ? $newBillingMode : null),
            'type' => 'service',
            'link' => '?page=tenant-services',
        ]);
        return $priceChangeId;
    }
    public static function applyDueChanges() {
        $currentOrder = ((int)date('Y') * 100) + (int)date('n');
        $rows = Database::hasConnection()
            ? Database::fetchAll('SELECT * FROM price_changes WHERE applied = 0 ORDER BY effective_year ASC, effective_month ASC, id ASC')
            : array_values(array_filter(Database::getTable('price_changes'), static fn($r) => (int)($r['applied'] ?? 0) === 0));
        $count = 0;
        foreach ($rows as $row) {
            $rowOrder = ((int)($row['effective_year'] ?? 0) * 100) + (int)($row['effective_month'] ?? 0);
            if ($rowOrder > $currentOrder) { continue; }
            $payload = ['price' => (float)($row['new_price'] ?? 0)];
            if (!empty($row['new_billing_mode'])) { $payload['billing_mode'] = $row['new_billing_mode']; }
            Database::update('services', $payload, 'id = :id', ['id' => (int)($row['service_id'] ?? 0)]);
            Database::update('price_changes', ['applied' => 1], 'id = :id', ['id' => (int)($row['id'] ?? 0)]);
            $count++;
        }
        return $count;
    }
    public static function cancelPendingChange($changeId) {
$row = Database::hasConnection()
? Database::fetchOne('SELECT * FROM price_changes WHERE id = ?', [(int)$changeId])
: (function() use ($changeId) { foreach (Database::getTable('price_changes') as $r) { if ((int)($r['id'] ?? 0) === (int)$changeId) { return $r; } } return null; })();
if (!$row) { throw new RuntimeException('Không tìm thấy lịch thay đổi cần hủy.'); }
if ((int)($row['applied'] ?? 0) === 1) { throw new RuntimeException('Lịch này đã áp dụng rồi, không thể hủy.'); }
Database::delete('price_changes', 'id = :id', ['id' => (int)$changeId]);
return true;
}
public static function getPendingHistoryByService($serviceId) {
$result = [];
foreach (self::getHistoryByServiceId((int)$serviceId) as $row) {
if ((int)($row['applied'] ?? 0) === 0) { $result[] = $row; }
}
return $result;
}public static function getPendingByServiceMap() {
        $currentOrder = ((int)date('Y') * 100) + (int)date('n');
        $map = [];
        foreach (self::getAll() as $row) {
            if ((int)($row['applied'] ?? 0) === 1) { continue; }
            $rowOrder = ((int)($row['effective_year'] ?? 0) * 100) + (int)($row['effective_month'] ?? 0);
            if ($rowOrder <= $currentOrder) { continue; }
            $map[(int)$row['service_id']] = $row;
        }
        return $map;
    }
    public static function getEffectiveConfigForPeriod(array $service, $month, $year) {
        $basePrice = (float)($service['price'] ?? 0);
        $baseMode = (string)($service['billing_mode'] ?? 'fixed');
        $history = self::getHistoryByServiceId((int)($service['id'] ?? 0));
        $targetOrder = ((int)$year * 100) + (int)$month;
        $currentOrder = ((int)date('Y') * 100) + (int)date('n');
        if (empty($history)) { return ['price' => $basePrice, 'billing_mode' => $baseMode]; }
        if ($targetOrder <= $currentOrder) {
            $price = $basePrice;
            for ($i = count($history) - 1; $i >= 0; $i--) {
                $row = $history[$i];
                if ((int)($row['applied'] ?? 0) !== 1) { continue; }
                $rowOrder = ((int)($row['effective_year'] ?? 0) * 100) + (int)($row['effective_month'] ?? 0);
                if ($rowOrder > $targetOrder) { $price = (float)($row['old_price'] ?? $price); }
            }
            return ['price' => $price, 'billing_mode' => $baseMode];
        }
        $price = $basePrice; $mode = $baseMode;
        foreach ($history as $row) {
            if ((int)($row['applied'] ?? 0) === 1) { continue; }
            $rowOrder = ((int)($row['effective_year'] ?? 0) * 100) + (int)($row['effective_month'] ?? 0);
            if ($rowOrder <= $targetOrder) {
                $price = (float)($row['new_price'] ?? $price);
                if (!empty($row['new_billing_mode'])) { $mode = (string)$row['new_billing_mode']; }
            }
        }
        return ['price' => $price, 'billing_mode' => $mode];
    }
    public static function getEffectivePriceForPeriod($serviceId, $month, $year, $fallbackPrice = 0.0) {
        $service = ServiceModel::getById((int)$serviceId) ?? ['price' => $fallbackPrice, 'billing_mode' => 'fixed'];
        return self::getEffectiveConfigForPeriod($service, $month, $year)['price'];
    }
    public static function buildNotificationContent(array $service, $oldPrice, $newPrice, $effectiveMonth, $effectiveYear, $newBillingMode = null) {
        $serviceName = trim((string)($service['name'] ?? 'Dịch vụ'));
        $modeLabels = ServiceModel::getBillingModeOptions();
        $oldModeLabel = $modeLabels[(string)($service['billing_mode'] ?? '')] ?? (string)($service['billing_mode'] ?? '');
        $newModeLabel = $newBillingMode !== null
            ? ($modeLabels[(string)$newBillingMode] ?? (string)$newBillingMode)
            : null;

        $parts = ['Dịch vụ ' . $serviceName];
        $priceChanged = $newPrice !== null && abs((float)$newPrice - (float)$oldPrice) > 0.001;
        if ($priceChanged) {
            $unit = trim((string)($service['unit'] ?? 'tháng'));
            $parts[] = 'giá từ ' . number_format((float)$oldPrice, 0, ',', '.') . 'đ thành ' . number_format((float)$newPrice, 0, ',', '.') . 'đ/' . ($unit !== '' ? $unit : 'tháng');
        }
        if ($newBillingMode !== null && $newModeLabel !== null) {
            $parts[] = 'cách tính từ "' . $oldModeLabel . '" thành "' . $newModeLabel . '"';
        }
        if ($priceChanged && $newBillingMode !== null) {
            $parts = ['Dịch vụ ' . $serviceName . ' sẽ thay đổi: giá ' . number_format((float)$oldPrice, 0, ',', '.') . 'đ → ' . number_format((float)$newPrice, 0, ',', '.') . 'đ, cách tính "' . $oldModeLabel . '" → "' . $newModeLabel . '"'];
        }

        $parts[] = 'áp dụng từ tháng '
            . str_pad((string)(int)$effectiveMonth, 2, '0', STR_PAD_LEFT)
            . '/'
            . (int)$effectiveYear
            . '.';

        return implode(', ', $parts);
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
