<?php

require_once __DIR__ . '/../src/autoload.php';

use PinnacleHunt\Config\Database;
use PinnacleHunt\Services\AuthService;
use PinnacleHunt\Services\SettingsService;
use PinnacleHunt\Services\PackageService;
use PinnacleHunt\Services\WithdrawalService;

AuthService::startSession();

$action = $_GET['action'] ?? null;
$page = $_GET['page'] ?? null;
$currentUser = AuthService::getCurrentUser();

// --- Action Handlers ---
if ($action !== null) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!AuthService::validateCsrfToken($csrfToken)) {
            $_SESSION['flash_error'] = "Invalid or expired session security token. Please try again.";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }
    }

    try {
        switch ($action) {
            case 'register':
                $newUser = AuthService::register($_POST);
                AuthService::login($_POST['username'], $_POST['password']);
                $_SESSION['flash_success'] = "Registration successful! Welcome to Pinnacle Hunt, {$newUser['username']}.";
                header("Location: /?page=dashboard");
                exit;

            case 'login':
                $user = AuthService::login($_POST['username_or_email'] ?? '', $_POST['password'] ?? '');
                if ($user) {
                    $_SESSION['flash_success'] = "Welcome back, {$user['username']}!";
                    if ($user['role'] === 'admin') {
                        header("Location: /?page=admin_dashboard");
                    } else {
                        header("Location: /?page=dashboard");
                    }
                    exit;
                } else {
                    $_SESSION['flash_error'] = "Invalid username/email or password.";
                    header("Location: /?page=login");
                    exit;
                }

            case 'logout':
                AuthService::logout();
                header("Location: /?page=login");
                exit;

            case 'purchase_package':
                if (!$currentUser) {
                    header("Location: /?page=login");
                    exit;
                }
                $packageId = (int)($_POST['package_id'] ?? 0);
                PackageService::purchasePackage($currentUser['id'], $packageId);
                $_SESSION['flash_success'] = "Package activated successfully! You are now placed in the 3-Matrix tree.";
                header("Location: /?page=dashboard");
                exit;

            case 'request_withdrawal':
                if (!$currentUser) {
                    header("Location: /?page=login");
                    exit;
                }
                $amount = (float)($_POST['amount'] ?? 0);
                $payoutDetails = trim(($_POST['payout_type'] ?? '') . ': ' . ($_POST['payout_details'] ?? ''));
                WithdrawalService::createRequest($currentUser['id'], $amount, $payoutDetails);
                $_SESSION['flash_success'] = "Withdrawal request for ₹" . number_format($amount, 2) . " submitted successfully.";
                header("Location: /?page=ledger");
                exit;

            case 'save_settings':
                if (!$currentUser || $currentUser['role'] !== 'admin') {
                    die("Unauthorized access.");
                }

                // Update settings
                SettingsService::set('reserve_deduction_rate', $_POST['reserve_deduction_rate'] ?? '25.00');
                SettingsService::set('direct_sponsor_percent', $_POST['direct_sponsor_percent'] ?? '20.00');
                for ($lvl = 1; $lvl <= 7; $lvl++) {
                    SettingsService::set("matrix_level_{$lvl}_percent", $_POST["matrix_level_{$lvl}_percent"] ?? '0.00');
                }

                // Update packages
                $pdo = Database::getConnection();
                if (!empty($_POST['package_price']) && is_array($_POST['package_price'])) {
                    $updatePkg = $pdo->prepare("
                        UPDATE packages
                        SET name = ?, price = ?, direct_commission_percent = ?, status = ?
                        WHERE id = ?
                    ");
                    foreach ($_POST['package_price'] as $pkgId => $price) {
                        $name = $_POST['package_name'][$pkgId] ?? '';
                        $direct = (float)($_POST['package_direct'][$pkgId] ?? 20.0);
                        $status = $_POST['package_status'][$pkgId] ?? 'active';
                        $updatePkg->execute([$name, (float)$price, $direct, $status, (int)$pkgId]);
                    }
                }

                $_SESSION['flash_success'] = "System commission configuration and package tiers updated successfully.";
                header("Location: /?page=admin_settings");
                exit;

            case 'process_withdrawal':
                if (!$currentUser || $currentUser['role'] !== 'admin') {
                    die("Unauthorized access.");
                }
                $withdrawalId = (int)($_POST['withdrawal_id'] ?? 0);
                $decision = $_POST['decision'] ?? '';

                if ($decision === 'approve') {
                    WithdrawalService::approveWithdrawal($withdrawalId);
                    $_SESSION['flash_success'] = "Withdrawal #{$withdrawalId} approved.";
                } elseif ($decision === 'reject') {
                    WithdrawalService::rejectWithdrawal($withdrawalId);
                    $_SESSION['flash_success'] = "Withdrawal #{$withdrawalId} rejected and funds refunded to member wallet.";
                }
                header("Location: /?page=admin_withdrawals");
                exit;

            case 'export_csv':
                if (!$currentUser || $currentUser['role'] !== 'admin') {
                    die("Unauthorized access.");
                }
                $type = $_GET['type'] ?? 'ledger';
                $pdo = Database::getConnection();

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="pinnacle_hunt_' . $type . '_' . date('Ymd_His') . '.csv"');
                $out = fopen('php://output', 'w');

                if ($type === 'ledger') {
                    fputcsv($out, ['ID', 'User ID', 'Username', 'Type', 'Amount', 'Wallet After', 'Reserve After', 'Description', 'Timestamp']);
                    $stmt = $pdo->query("SELECT l.*, u.username FROM ledger l JOIN users u ON l.user_id = u.id ORDER BY l.id DESC");
                    while ($r = $stmt->fetch()) {
                        fputcsv($out, [$r['id'], $r['user_id'], $r['username'], $r['type'], $r['amount'], $r['balance_after'], $r['reserve_after'], $r['description'], $r['created_at']]);
                    }
                } elseif ($type === 'commissions') {
                    fputcsv($out, ['ID', 'Recipient ID', 'Recipient Username', 'Source User', 'Package ID', 'Type', 'Level', 'Gross Amount', 'Reserved Amount', 'Net Amount', 'Timestamp']);
                    $stmt = $pdo->query("SELECT c.*, u.username as recipient, s.username as source FROM commissions c JOIN users u ON c.user_id = u.id JOIN users s ON c.source_user_id = s.id ORDER BY c.id DESC");
                    while ($r = $stmt->fetch()) {
                        fputcsv($out, [$r['id'], $r['user_id'], $r['recipient'], $r['source'], $r['package_id'], $r['type'], $r['level'], $r['amount'], $r['reserved_amount'], $r['net_amount'], $r['created_at']]);
                    }
                } elseif ($type === 'withdrawals') {
                    fputcsv($out, ['ID', 'User ID', 'Username', 'Amount', 'Payout Details', 'Status', 'Processed At', 'Submitted At']);
                    $stmt = $pdo->query("SELECT w.*, u.username FROM withdrawals w JOIN users u ON w.user_id = u.id ORDER BY w.id DESC");
                    while ($r = $stmt->fetch()) {
                        fputcsv($out, [$r['id'], $r['user_id'], $r['username'], $r['amount'], $r['payout_details'], $r['status'], $r['processed_at'], $r['created_at']]);
                    }
                }
                fclose($out);
                exit;

            default:
                throw new Exception("Unknown action: {$action}");
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = $e->getMessage();
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }
}

// --- Page View Router ---
if (!$page) {
    $page = $currentUser ? ($currentUser['role'] === 'admin' ? 'admin_dashboard' : 'dashboard') : 'login';
}

// RBAC Protection
$guestPages = ['login', 'register'];
if (!in_array($page, $guestPages) && !$currentUser) {
    header("Location: /?page=login");
    exit;
}

$adminPages = ['admin_dashboard', 'admin_settings', 'admin_matrix_tree', 'admin_members', 'admin_withdrawals'];
if (in_array($page, $adminPages) && (!$currentUser || $currentUser['role'] !== 'admin')) {
    $_SESSION['flash_error'] = "Access denied: Administrator privileges required.";
    header("Location: /?page=dashboard");
    exit;
}

// Render Page
switch ($page) {
    case 'login':
        require_once __DIR__ . '/../views/user/login.php';
        break;
    case 'register':
        require_once __DIR__ . '/../views/user/register.php';
        break;
    case 'dashboard':
        require_once __DIR__ . '/../views/user/dashboard.php';
        break;
    case 'referrals':
        require_once __DIR__ . '/../views/user/referrals.php';
        break;
    case 'genealogy':
        require_once __DIR__ . '/../views/user/genealogy.php';
        break;
    case 'ledger':
        require_once __DIR__ . '/../views/user/ledger.php';
        break;
    case 'admin_dashboard':
        require_once __DIR__ . '/../views/admin/dashboard.php';
        break;
    case 'admin_settings':
        require_once __DIR__ . '/../views/admin/settings.php';
        break;
    case 'admin_matrix_tree':
        require_once __DIR__ . '/../views/admin/matrix_tree.php';
        break;
    case 'admin_members':
        require_once __DIR__ . '/../views/admin/members.php';
        break;
    case 'admin_withdrawals':
        require_once __DIR__ . '/../views/admin/withdrawals.php';
        break;
    default:
        http_response_code(404);
        echo "<h1 style='color:white;text-align:center;margin-top:100px;'>404 - Page Not Found</h1>";
        break;
}
