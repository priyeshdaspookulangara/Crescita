<?php

namespace PinnacleHunt\Services;

use PinnacleHunt\Config\Database;
use PDO;
use Exception;

class AutoProgressionEngine {
    /**
     * Determine highest active package tier for a user.
     * Packages are sorted by sort_order / id (1 to 10).
     */
    public static function getHighestPackage(int $userId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, up.status as user_pkg_status
            FROM user_packages up
            JOIN packages p ON up.package_id = p.id
            WHERE up.user_id = ?
            ORDER BY p.sort_order DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $pkg = $stmt->fetch();
        return $pkg ?: null;
    }

    /**
     * Get the next package in progression tier hierarchy
     */
    public static function getNextPackage(int $currentPackageSortOrder): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM packages
            WHERE sort_order > ? AND status = 'active'
            ORDER BY sort_order ASC
            LIMIT 1
        ");
        $stmt->execute([$currentPackageSortOrder]);
        $next = $stmt->fetch();
        return $next ?: null;
    }

    /**
     * Check and execute auto-upgrades for a single user.
     * Recursively checks if subsequent tiers can also be unlocked if reserve balance allows.
     */
    public static function checkAndUpgradeUser(int $userId): array {
        $pdo = Database::getConnection();
        $upgradesExecuted = [];

        // Loop to allow multi-tier progression if reserve balance is sufficient
        while (true) {
            // Get user's current reserve balance and highest package
            $uStmt = $pdo->prepare("SELECT id, username, reserve_balance FROM users WHERE id = ? FOR UPDATE");
            $uStmt->execute([$userId]);
            $user = $uStmt->fetch();
            if (!$user) {
                break;
            }

            $reserveBalance = (float)$user['reserve_balance'];
            $highestPkg = self::getHighestPackage($userId);

            $currentSortOrder = $highestPkg ? (int)$highestPkg['sort_order'] : 0;
            $nextPkg = self::getNextPackage($currentSortOrder);

            if (!$nextPkg) {
                // Reached highest tier (Tier 10 - Pinnacle)
                break;
            }

            $upgradeCost = (float)$nextPkg['price'];

            if ($reserveBalance >= $upgradeCost) {
                // Perform upgrade
                // 1. Deduct upgrade price from reserve_balance using LedgerService
                LedgerService::record(
                    $userId,
                    'upgrade_payment',
                    $upgradeCost,
                    "Auto-Progression Upgrade to {$nextPkg['name']} (₹" . number_format($upgradeCost, 2) . ") funded via Reserve Pool"
                );

                // 2. Mark previous tier status as 'upgraded'
                if ($highestPkg) {
                    $updatePrev = $pdo->prepare("UPDATE user_packages SET status = 'upgraded' WHERE user_id = ? AND package_id = ?");
                    $updatePrev->execute([$userId, $highestPkg['id']]);
                }

                // 3. Enroll user into next tier (active)
                $enrollStmt = $pdo->prepare("
                    INSERT INTO user_packages (user_id, package_id, status)
                    VALUES (?, ?, 'active')
                    ON DUPLICATE KEY UPDATE status = 'active'
                ");
                $enrollStmt->execute([$userId, $nextPkg['id']]);

                // 4. Automatically place user into the next tier's matrix using BFS algorithm
                $matrixPlacement = MatrixEngine::placeUserInMatrix($userId, (int)$nextPkg['id']);

                // 5. Trigger fresh direct sponsor commissions and matrix level payouts on that higher tier
                $commissionsResult = CommissionEngine::processPackageCommissions($userId, (int)$nextPkg['id']);

                $upgradesExecuted[] = [
                    'package' => $nextPkg,
                    'matrix_node' => $matrixPlacement,
                    'commissions' => $commissionsResult
                ];

                // Check upline ancestors affected by the new commissions on the new tier as well
                if (!empty($commissionsResult['affected_users_for_upgrade_check'])) {
                    self::checkAncestorsList($commissionsResult['affected_users_for_upgrade_check']);
                }
            } else {
                // Reserve balance insufficient for next tier
                break;
            }
        }

        return $upgradesExecuted;
    }

    /**
     * Check and trigger auto-progression for a list of ancestor user IDs
     */
    public static function checkAncestorsList(array $userIds): void {
        $uniqueIds = array_unique(array_filter($userIds));
        foreach ($uniqueIds as $uid) {
            self::checkAndUpgradeUser((int)$uid);
        }
    }
}
