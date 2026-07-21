<?php
class AmenityModel {
    public static function getAllActive() {
        $rows = Database::hasConnection()
            ? Database::fetchAll("SELECT * FROM amenities WHERE is_active = 1 ORDER BY sort_order ASC")
            : Database::getTable('amenities');

        $rows = array_filter($rows, static fn($row) => (int)($row['is_active'] ?? 0) === 1);
        usort($rows, static fn($a, $b) => (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0));
        return array_values($rows);
    }
    
    public static function getAll() {
        $rows = Database::hasConnection()
            ? Database::fetchAll("SELECT * FROM amenities ORDER BY sort_order ASC")
            : Database::getTable('amenities');

        usort($rows, static fn($a, $b) => (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0));
        return array_values($rows);
    }
    
    public static function save($data, $id = null) {
        if ($id) {
            Database::update('amenities', $data, 'id = :id', ['id' => $id]);
        } else {
            Database::insert('amenities', $data);
        }
    }
    
    public static function delete($id) {
        Database::delete('amenities', 'id = :id', ['id' => $id]);
    }
}
