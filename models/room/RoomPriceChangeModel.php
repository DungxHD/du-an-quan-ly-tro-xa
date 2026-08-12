<?php
/**
 * RoomPriceChangeModel - Quản lý lịch thay đổi giá phòng
 * Tương tự PriceChangeModel nhưng cho rooms
 */
class RoomPriceChangeModel
{
    /**
     * Lên lịch thay đổi giá phòng
     * Nếu đã có pending cho cùng phòng → xóa bản ghi cũ (ghi đè)
     */
    public static function scheduleChange($roomId, $oldPrice, $newPrice, $month, $year, $createdBy)
    {
        $roomId = (int)$roomId;
        $oldPrice = (float)$oldPrice;
        $newPrice = (float)$newPrice;
        $month = (int)$month;
        $year = (int)$year;
        $createdBy = (int)$createdBy;

        // Xóa tất cả pending changes cũ của phòng này (ghi đè)
        $deleted = self::cancelPendingByRoom($roomId);

        // Tạo bản ghi mới
        Database::insert('room_price_changes', [
            'room_id' => $roomId,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'effective_month' => $month,
            'effective_year' => $year,
            'applied' => 0,
            'created_by' => $createdBy,
        ]);

        return $deleted; // Trả về số bản ghi đã xóa
    }

    /**
     * Hủy tất cả pending changes của một phòng
     */
    public static function cancelPendingByRoom($roomId)
    {
        $roomId = (int)$roomId;
        return Database::delete('room_price_changes', 'room_id = :room_id AND applied = 0', ['room_id' => $roomId]);
    }

    /**
     * Lấy danh sách pending changes của một phòng
     */
    public static function getPendingByRoom($roomId)
    {
        $roomId = (int)$roomId;
        return Database::fetchAll(
            'SELECT * FROM room_price_changes WHERE room_id = :room_id AND applied = 0 ORDER BY effective_year, effective_month',
            ['room_id' => $roomId]
        );
    }

    /**
     * Apply tất cả pending changes đã đến hạn
     * Gọi khi admin login hoặc vào trang admin
     */
    public static function applyDueChanges()
    {
        $currentOrder = ((int)date('Y') * 100) + (int)date('n');
        $applied = 0;

        $pending = Database::fetchAll(
            'SELECT * FROM room_price_changes WHERE applied = 0'
        );

        foreach ($pending as $change) {
            $order = ((int)$change['effective_year'] * 100) + (int)$change['effective_month'];
            if ($order <= $currentOrder) {
                // Cập nhật giá phòng
                Database::update('rooms', 
                    ['price' => (float)$change['new_price']], 
                    'id = :id', 
                    ['id' => (int)$change['room_id']]
                );

                // Đánh dấu đã apply
                Database::update('room_price_changes', 
                    ['applied' => 1], 
                    'id = :id', 
                    ['id' => (int)$change['id']]
                );

                $applied++;
            }
        }

        return $applied;
    }

    /**
     * Apply ngay tất cả pending changes của một phòng (khi phòng chuyển trạng thái)
     */
    public static function applyPendingImmediately($roomId)
    {
        $roomId = (int)$roomId;
        $pending = self::getPendingByRoom($roomId);
        
        if (empty($pending)) {
            return 0;
        }

        // Lấy bản ghi mới nhất (cuối cùng trong danh sách đã sort)
        $latest = end($pending);

        // Cập nhật giá phòng ngay lập tức
        Database::update('rooms', 
            ['price' => (float)$latest['new_price']], 
            'id = :id', 
            ['id' => $roomId]
        );

        // Đánh dấu tất cả pending là đã apply
        Database::update('room_price_changes', 
            ['applied' => 1], 
            'room_id = :room_id AND applied = 0', 
            ['room_id' => $roomId]
        );

        return count($pending);
    }
}