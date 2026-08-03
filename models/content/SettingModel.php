<?php
class SettingModel {
    private static $cache = null;
    
    public static function loadAll() {
        if (self::$cache === null) {
            self::$cache = [];
            $rows = Database::hasConnection()
                ? Database::fetchAll("SELECT setting_key, setting_value FROM settings")
                : Database::getTable('settings');
            foreach ($rows as $row) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
        }
        return self::$cache;
    }
    
    public static function get($key, $default = '') {
        self::loadAll();
        return isset(self::$cache[$key]) && self::$cache[$key] !== '' 
            ? self::$cache[$key] 
            : $default;
    }
    
    /**
     * Lưu một setting đơn lẻ, cho phép chỉ định nhóm để đảm bảo UPSERT đúng `setting_group`.
     */
    public static function set($key, $value, $group = null) {
        Database::saveSetting($key, $value, $group);
        self::$cache = null;
    }
    
    /**
     * Lưu nhiều setting cùng lúc để controller admin không phải lặp logic truy cập DB.
     */
    public static function setMultiple($data, $group = null) {
        foreach ($data as $key => $value) {
            self::set($key, $value, $group);
        }
    }
    
    public static function getByGroup($group) {
        if (Database::hasConnection()) {
            return Database::fetchAll(
                "SELECT * FROM settings WHERE setting_group = ? ORDER BY setting_key ASC",
                [$group]
            );
        }

        $rows = array_filter(
            Database::getTable('settings'),
            static fn($row) => ($row['setting_group'] ?? '') === $group
        );
        usort($rows, static fn($a, $b) => strcmp($a['setting_key'], $b['setting_key']));
        return array_values($rows);
    }
}
