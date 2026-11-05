<?php

namespace PinnacleHunt\Services;

use PinnacleHunt\Config\Database;
use PDO;
use Exception;

class LedgerService {
    public static function record(
        int $userId,
        string $type,
        float $amount,
        string $description
    ): array {
        $pdo = Database::getConnection();

        // Lock user row to get latest balances
        $stmt = $pdo->prepare("SELECT wallet_balance, reserve_balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception("User not found for ledger transaction: {$userId}");
        }

        $wallet = (float)$user['wallet_balance'];
        $reserve = (float)$user['reserve_balance'];

        switch ($type) {
            case 'credit':
                $wallet += $amount;
                break;
            case 'debit':
                if ($wallet < $amount) {
                    throw new Exception("Insufficient wallet balance ({$wallet} < {$amount})");
                }
                $wallet -= $amount;
                break;
            case 'reserve_hold':
                $reserve += $amount;
                break;
            case 'reserve_release':
                if ($reserve < $amount) {
                    throw new Exception("Insufficient reserve balance ({$reserve} < {$amount})");
                }
                $reserve -= $amount;
                $wallet += $amount;
                break;
            case 'upgrade_payment':
                if ($reserve < $amount) {
                    throw new Exception("Insufficient reserve balance to fund upgrade ({$reserve} < {$amount})");
                }
                $reserve -= $amount;
                break;
            default:
                throw new Exception("Unknown ledger transaction type: {$type}");
        }

        // Update user balances
        $updateStmt = $pdo->prepare("UPDATE users SET wallet_balance = ?, reserve_balance = ? WHERE id = ?");
        $updateStmt->execute([$wallet, $reserve, $userId]);

        // Insert ledger record
        $ledgerStmt = $pdo->prepare("
            INSERT INTO ledger (user_id, type, amount, balance_after, reserve_after, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $ledgerStmt->execute([$userId, $type, $amount, $wallet, $reserve, $description]);

        return [
            'id' => (int)$pdo->lastInsertId(),
            'wallet_balance' => $wallet,
            'reserve_balance' => $reserve,
        ];
    }
}
