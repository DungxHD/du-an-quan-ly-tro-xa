<?php

/**
 * Gallery phòng: 1 ảnh chính (is_primary=1) + tối đa 3 ảnh phụ.
 * Bảng: room_images (room_id, image_url, is_primary, sort_order).
 */
class RoomImageModel
{
    private const MAX_SECONDARY = 3;

    private static function normalizeRow(array $row)
    {
        return [
            'id'         => (int)($row['id'] ?? 0),
            'room_id'    => (int)($row['room_id'] ?? 0),
            'image_url'  => trim((string)($row['image_url'] ?? '')),
            'is_primary' => (int)($row['is_primary'] ?? 0) === 1 ? 1 : 0,
            'sort_order' => (int)($row['sort_order'] ?? 0),
        ];
    }

    public static function getByRoom($roomId)
    {
        $roomId = (int)$roomId;
        if ($roomId <= 0) {
            return [];
        }

        if (Database::hasConnection()) {
            try {
                $rows = Database::fetchAll(
                    'SELECT * FROM room_images WHERE room_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC',
                    [$roomId]
                );
            } catch (Throwable $e) {
                $rows = [];
            }
        } else {
            $rows = array_values(array_filter(
                Database::getTable('room_images'),
                static fn($row) => (int)($row['room_id'] ?? 0) === $roomId
            ));
            usort($rows, static function ($a, $b) {
                $p = (int)($b['is_primary'] ?? 0) <=> (int)($a['is_primary'] ?? 0);
                if ($p !== 0) return $p;
                $s = (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0);
                if ($s !== 0) return $s;
                return (int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0);
            });
        }

        return array_values(array_filter(
            array_map([self::class, 'normalizeRow'], $rows),
            static fn($row) => $row['image_url'] !== ''
        ));
    }

    public static function getPrimaryUrl($roomId)
    {
        foreach (self::getByRoom($roomId) as $row) {
            if ($row['is_primary'] === 1) {
                return $row['image_url'];
            }
        }
        return '';
    }

    public static function getSecondaryUrls($roomId)
    {
        $urls = [];
        foreach (self::getByRoom($roomId) as $row) {
            if ($row['is_primary'] === 0) {
                $urls[] = $row['image_url'];
            }
        }
        return array_slice($urls, 0, self::MAX_SECONDARY);
    }

    public static function getGalleryUrls($roomId)
    {
        $urls = [];
        foreach (self::getByRoom($roomId) as $row) {
            $urls[] = $row['image_url'];
        }
        return array_slice($urls, 0, self::MAX_SECONDARY + 1);
    }

    public static function getSecondaryUrlMap(array $roomIds)
    {
        $map = [];
        $roomIds = array_values(array_unique(array_filter(array_map('intval', $roomIds), static fn($v) => $v > 0)));
        foreach ($roomIds as $rid) {
            $map[$rid] = [];
        }
        if (empty($roomIds)) {
            return $map;
        }

        if (Database::hasConnection()) {
            $ph = implode(', ', array_fill(0, count($roomIds), '?'));
            try {
                $rows = Database::fetchAll(
                    "SELECT * FROM room_images WHERE room_id IN ($ph) AND is_primary = 0 ORDER BY room_id ASC, sort_order ASC, id ASC",
                    $roomIds
                );
            } catch (Throwable $e) {
                $rows = [];
            }
        } else {
            $rows = array_values(array_filter(
                Database::getTable('room_images'),
                static fn($row) => in_array((int)($row['room_id'] ?? 0), $roomIds, true) && (int)($row['is_primary'] ?? 0) === 0
            ));
        }

        foreach ($rows as $row) {
            $rid = (int)($row['room_id'] ?? 0);
            $url = trim((string)($row['image_url'] ?? ''));
            if ($url === '' || count($map[$rid] ?? []) >= self::MAX_SECONDARY) {
                continue;
            }
            $map[$rid][] = $url;
        }
        return $map;
    }

    /** Xóa sạch rồi ghi lại: 1 chính + tối đa 3 phụ. */
    public static function sync($roomId, $primaryUrl, array $secondaryUrls = [])
    {
        $roomId = (int)$roomId;
        if ($roomId <= 0) {
            return;
        }
        $primaryUrl = trim((string)$primaryUrl);
        $secondaryUrls = array_slice(array_values(array_filter(
            array_map(static fn($u) => trim((string)$u), $secondaryUrls),
            static fn($u) => $u !== '' && $u !== $primaryUrl
        )), 0, self::MAX_SECONDARY);

        Database::delete('room_images', 'room_id = :room_id', ['room_id' => $roomId]);
        if ($primaryUrl !== '') {
            Database::insert('room_images', [
                'room_id' => $roomId,
                'image_url' => $primaryUrl,
                'is_primary' => 1,
                'sort_order' => 0,
            ]);
        }
        foreach ($secondaryUrls as $i => $url) {
            Database::insert('room_images', [
                'room_id' => $roomId,
                'image_url' => $url,
                'is_primary' => 0,
                'sort_order' => $i + 1,
            ]);
        }
    }

    /** Alias cho sync() — AdminController gọi tên này. */
    public static function syncForRoom($roomId, $primaryUrl, array $secondaryUrls = [])
    {
        return self::sync($roomId, $primaryUrl, $secondaryUrls);
    }

    public static function deleteByRoom($roomId)
    {
        Database::delete('room_images', 'room_id = :room_id', ['room_id' => (int)$roomId]);
    }
}
