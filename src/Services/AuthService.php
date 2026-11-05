<?php

namespace PinnacleHunt\Services;

use PinnacleHunt\Config\Database;
use PDO;
use Exception;

class AuthService {
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    public static function generateCsrfToken(): string {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool {
        self::startSession();
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function register(array $data): array {
        $pdo = Database::getConnection();

        $username = trim($data['username'] ?? '');
        $email = trim(strtolower($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $fullName = trim($data['full_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $sponsorCode = trim($data['sponsor'] ?? '');

        if (empty($username) || empty($email) || empty($password) || empty($fullName) || empty($phone)) {
            throw new Exception("All fields are required.");
        }

        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters.");
        }

        // Check unique username and email
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            throw new Exception("Username or email already in use.");
        }

        // Resolve sponsor
        $sponsorId = null;
        if (!empty($sponsorCode)) {
            $spStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR id = ?");
            $spStmt->execute([$sponsorCode, $sponsorCode]);
            $sp = $spStmt->fetch();
            if ($sp) {
                $sponsorId = (int)$sp['id'];
            }
        }

        // If no sponsor provided, default to admin if exists
        if (!$sponsorId) {
            $adminCheck = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetch();
            if ($adminCheck) {
                $sponsorId = (int)$adminCheck['id'];
            }
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $insert = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, phone, role, sponsor_id, wallet_balance, reserve_balance, status)
            VALUES (?, ?, ?, ?, ?, 'user', ?, 0.00, 0.00, 'active')
        ");
        $insert->execute([$username, $email, $passwordHash, $fullName, $phone, $sponsorId]);
        $newUserId = (int)$pdo->lastInsertId();

        return [
            'id' => $newUserId,
            'username' => $username,
            'email' => $email,
            'sponsor_id' => $sponsorId
        ];
    }

    public static function login(string $usernameOrEmail, string $password): ?array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            self::startSession();
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            return $user;
        }

        return null;
    }

    public static function getCurrentUser(): ?array {
        self::startSession();
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function logout(): void {
        self::startSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
