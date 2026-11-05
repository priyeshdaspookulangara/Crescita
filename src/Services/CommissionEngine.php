<?php

namespace PinnacleHunt\Services;

use PinnacleHunt\Config\Database;
use PDO;
use Exception;

class CommissionEngine {
    /**
     * Level percentages default configuration:
     * L1: 12.50%
     * L2: 10.00%
     * L3: 7.50%
     * L4: 5.00%
     * L5: 2.50%
     * L6: 1.25%
     * L7: 1.25%
     * Total = 40.00%
     */
    public static function getMatrixLevelPercentages(): array {
        return [
            1 => (float)SettingsService::get('matrix_level_1_percent', '12.50'),
            2 => (float)SettingsService::get('matrix_level_2_percent', '10.00'),
            3 => (float)SettingsService::get('matrix_level_3_percent', '7.50'),
            4 => (float)SettingsService::get('matrix_level_4_percent', '5.00'),
            5 => (float)SettingsService::get('matrix_level_5_percent', '2.50'),
            6 => (float)SettingsService::get('matrix_level_6_percent', '1.25'),
            7 => (float)SettingsService::get('matrix_level_7_percent', '1.25'),
        ];
    }

    /**
     * Process all commissions for a package purchase or auto-upgrade
     */
    public static function processPackageCommissions(int $buyerUserId, int $packageId): array {
        $pdo = Database::getConnection();

        // Fetch package details
        $pkgStmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
        $pkgStmt->execute([$packageId]);
        $package = $pkgStmt->fetch();
        if (!$package) {
            throw new Exception("Package not found: {$packageId}");
        }

        $packagePrice = (float)$package['price'];
        $directPercent = (float)$package['direct_commission_percent'];

        // Fetch buyer and direct sponsor
        $userStmt = $pdo->prepare("SELECT id, sponsor_id, username FROM users WHERE id = ?");
        $userStmt->execute([$buyerUserId]);
        $buyer = $userStmt->fetch();

        $results = [
            'direct_sponsor_commission' => null,
            'matrix_commissions' => [],
            'affected_users_for_upgrade_check' => []
        ];

        // 1. Direct Sponsor Commission (20.00% - Zero Cutting)
        if ($buyer && $buyer['sponsor_id']) {
            $sponsorId = (int)$buyer['sponsor_id'];
            $directAmount = round(($packagePrice * $directPercent) / 100.0, 2);

            // Zero cutting rule: 100% credited to withdrawable wallet_balance, reserved_amount = 0
            $insertComm = $pdo->prepare("
                INSERT INTO commissions (user_id, source_user_id, package_id, type, level, amount, reserved_amount, net_amount)
                VALUES (?, ?, ?, 'direct_sponsor', NULL, ?, 0.00, ?)
            ");
            $insertComm->execute([$sponsorId, $buyerUserId, $packageId, $directAmount, $directAmount]);

            // Ledger credit to wallet
            LedgerService::record(
                $sponsorId,
                'credit',
                $directAmount,
                "Direct Sponsor Bonus ({$directPercent}%) from {$buyer['username']} on {$package['name']} [Zero-Cut]"
            );

            $results['direct_sponsor_commission'] = [
                'user_id' => $sponsorId,
                'amount' => $directAmount,
                'net_amount' => $directAmount,
                'reserved_amount' => 0.00
            ];
        }

        // 2. 7-Level Matrix Commissions (40.00% total)
        $levelPercentages = self::getMatrixLevelPercentages();
        $reserveDeductionRate = (float)SettingsService::get('reserve_deduction_rate', '25.00');

        $ancestors = MatrixEngine::getUplineAncestors($packageId, $buyerUserId, 7);

        foreach ($ancestors as $ancestor) {
            $level = (int)$ancestor['matrix_level'];
            if (!isset($levelPercentages[$level])) {
                continue;
            }

            $ancestorUserId = (int)$ancestor['user_id'];
            $levelPercent = $levelPercentages[$level];
            $grossAmount = round(($packagePrice * $levelPercent) / 100.0, 2);

            if ($grossAmount <= 0) {
                continue;
            }

            // Reserve pool deduction: e.g. 25% of matrix income goes to reserve_balance
            $reservedAmount = round(($grossAmount * $reserveDeductionRate) / 100.0, 2);
            $netAmount = round($grossAmount - $reservedAmount, 2);

            // Record commission
            $commStmt = $pdo->prepare("
                INSERT INTO commissions (user_id, source_user_id, package_id, type, level, amount, reserved_amount, net_amount)
                VALUES (?, ?, ?, 'matrix_level', ?, ?, ?, ?)
            ");
            $commStmt->execute([$ancestorUserId, $buyerUserId, $packageId, $level, $grossAmount, $reservedAmount, $netAmount]);

            // Record ledger transactions
            // Net amount to wallet balance
            if ($netAmount > 0) {
                LedgerService::record(
                    $ancestorUserId,
                    'credit',
                    $netAmount,
                    "Matrix Level {$level} Income ({$levelPercent}%) from {$buyer['username']} on {$package['name']}"
                );
            }

            // Reserved amount to reserve balance
            if ($reservedAmount > 0) {
                LedgerService::record(
                    $ancestorUserId,
                    'reserve_hold',
                    $reservedAmount,
                    "Matrix Level {$level} Auto-Upgrade Reserve ({$reserveDeductionRate}%) from {$buyer['username']} on {$package['name']}"
                );
            }

            $results['matrix_commissions'][] = [
                'user_id' => $ancestorUserId,
                'level' => $level,
                'gross_amount' => $grossAmount,
                'net_amount' => $netAmount,
                'reserved_amount' => $reservedAmount
            ];

            $results['affected_users_for_upgrade_check'][] = $ancestorUserId;
        }

        return $results;
    }
}
