<?php

namespace PinnacleHunt\Services;

use PinnacleHunt\Config\Database;
use PDO;
use Exception;

class PackageService {
    public static function getAll(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM packages ORDER BY sort_order ASC")->fetchAll();
    }

    public static function getById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
        $stmt->execute([$id]);
        $pkg = $stmt->fetch();
        return $pkg ?: null;
    }

    public static function getUserPackages(int $userId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, up.status as enrollment_status, up.created_at as enrolled_at
            FROM user_packages up
            JOIN packages p ON up.package_id = p.id
            WHERE up.user_id = ?
            ORDER BY p.sort_order ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Purchase package manually or as initial package enrollment
     */
    public static function purchasePackage(int $userId, int $packageId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $pkg = self::getById($packageId);
            if (!$pkg) {
                throw new Exception("Package not found.");
            }

            // Check if already enrolled in this package
            $chk = $pdo->prepare("SELECT id FROM user_packages WHERE user_id = ? AND package_id = ?");
            $chk->execute([$userId, $packageId]);
            if ($chk->fetch()) {
                throw new Exception("You are already enrolled in this package tier.");
            }

            // Enroll user
            $enroll = $pdo->prepare("
                INSERT INTO user_packages (user_id, package_id, status)
                VALUES (?, ?, 'active')
            ");
            $enroll->execute([$userId, $packageId]);

            // Place in BFS 3-Matrix
            $placement = MatrixEngine::placeUserInMatrix($userId, $packageId);

            // Process Commissions (Direct sponsor 20% zero cutting, 40% matrix level payout)
            $commResult = CommissionEngine::processPackageCommissions($userId, $packageId);

            $pdo->commit();

            // Run auto progression check for affected users outside transaction
            if (!empty($commResult['affected_users_for_upgrade_check'])) {
                AutoProgressionEngine::checkAncestorsList($commResult['affected_users_for_upgrade_check']);
            }

            return [
                'package' => $pkg,
                'matrix_node' => $placement,
                'commissions' => $commResult
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
