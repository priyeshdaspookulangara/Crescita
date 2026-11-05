<?php

namespace PinnacleHunt\Services;

use PinnacleHunt\Config\Database;
use PDO;

class SettingsService {
    private static array $cache = [];

    public static function get(string $key, ?string $default = null): ?string {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();

        if ($val !== false) {
            self::$cache[$key] = (string)$val;
            return self::$cache[$key];
        }

        return $default;
    }

    public static function set(string $key, string $value, ?string $description = null): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, description)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = COALESCE(VALUES(description), description)
        ");
        $stmt->execute([$key, $value, $description]);
        self::$cache[$key] = $value;
    }

    public static function getAll(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT setting_key, setting_value, description FROM settings");
        $all = [];
        while ($row = $stmt->fetch()) {
            $all[$row['setting_key']] = $row;
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
        return $all;
    }

    public static function clearCache(): void {
        self::$cache = [];
    }
}
