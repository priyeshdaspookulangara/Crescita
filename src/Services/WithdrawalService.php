<?php

namespace PinnacleHunt\Services;

use PinnacleHunt\Config\Database;
use PDO;
use Exception;

class WithdrawalService {
    public static function createRequest(int $userId, float $amount, string $payoutDetails): array {
        if ($amount <= 0) {
            throw new Exception("Withdrawal amount must be greater than zero.");
        }

        $pdo = Database::getConnection();

        // Check user wallet balance
        $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception("User not found.");
        }

        $currentBalance = (float)$user['wallet_balance'];
        if ($currentBalance < $amount) {
            throw new Exception("Insufficient wallet balance for withdrawal (Available: ₹" . number_format($currentBalance, 2) . ")");
        }

        // Deduct from wallet immediately and log to ledger
        LedgerService::record(
            $userId,
            'debit',
            $amount,
            "Withdrawal request submitted ({$payoutDetails})"
        );

        $insert = $pdo->prepare("
            INSERT INTO withdrawals (user_id, amount, payout_details, status)
            VALUES (?, ?, ?, 'pending')
        ");
        $insert->execute([$userId, $amount, $payoutDetails]);

        return [
            'id' => (int)$pdo->lastInsertId(),
            'amount' => $amount,
            'status' => 'pending'
        ];
    }

    public static function approveWithdrawal(int $withdrawalId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE withdrawals
            SET status = 'approved', processed_at = CURRENT_TIMESTAMP
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$withdrawalId]);
        return $stmt->rowCount() > 0;
    }

    public static function rejectWithdrawal(int $withdrawalId, string $reason = 'Rejected by Administrator'): bool {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ? AND status = 'pending' FOR UPDATE");
        $stmt->execute([$withdrawalId]);
        $wd = $stmt->fetch();

        if (!$wd) {
            return false;
        }

        // Refund funds back to member's wallet balance
        LedgerService::record(
            (int)$wd['user_id'],
            'credit',
            (float)$wd['amount'],
            "Refund for rejected withdrawal #{$withdrawalId}: {$reason}"
        );

        $update = $pdo->prepare("
            UPDATE withdrawals
            SET status = 'rejected', processed_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $update->execute([$withdrawalId]);

        return true;
    }
}
