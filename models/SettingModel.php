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
    
    public static function set($key, $value) {
        Database::saveSetting($key, $value);
        self::$cache = null;
    }
    
    public static function setMultiple($data) {
        foreach ($data as $key => $value) {
            self::set($key, $value);
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
