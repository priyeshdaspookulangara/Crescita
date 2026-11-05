<?php
// views/layouts/header.php
use PinnacleHunt\Services\AuthService;
$user = AuthService::getCurrentUser();
$csrfToken = AuthService::generateCsrfToken();
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Pinnacle Hunt - 7-Level BFS 3-Matrix Platform') ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        surface: '#0b0f19',
                        card: 'rgba(17, 24, 39, 0.75)',
                        cardHover: 'rgba(31, 41, 55, 0.85)',
                        borderGlass: 'rgba(255, 255, 255, 0.08)',
                        accentEmerald: '#10b981',
                        accentCyan: '#06b6d4',
                    },
                    backdropBlur: {
                        'xl': '20px',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #080c15;
            background-image:
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
            color: #f3f4f6;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        .glass-panel {
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .glass-card {
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .glass-card:hover {
            border-color: rgba(16, 185, 129, 0.3);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(16, 185, 129, 0.3);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(16, 185, 129, 0.6);
        }
    </style>
</head>
<body class="min-h-screen antialiased flex flex-col">

<?php if ($user): ?>
    <!-- Mobile Navigation Toggle Bar -->
    <div class="md:hidden glass-panel fixed top-0 left-0 right-0 z-40 px-4 py-3 flex items-center justify-between border-b border-white/10">
        <div class="flex items-center space-x-2">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-500 to-cyan-400 flex items-center justify-center font-bold text-white shadow-lg shadow-emerald-500/20">
                P
            </div>
            <span class="font-extrabold tracking-tight text-white text-lg">PINNACLE<span class="text-cyan-400">HUNT</span></span>
        </div>
        <button id="mobileMenuBtn" class="p-2 rounded-lg bg-white/5 border border-white/10 text-gray-300 hover:text-white focus:outline-none">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Fixed Desktop <aside> Sidebar -->
    <aside id="sidebar" class="w-64 fixed left-0 top-0 bottom-0 z-50 glass-panel border-r border-white/10 flex flex-col transition-transform duration-300 -translate-x-full md:translate-x-0">
        <!-- Logo Header -->
        <div class="px-6 py-6 border-b border-white/10 flex items-center justify-between">
            <a href="?page=dashboard" class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-cyan-400 flex items-center justify-center font-black text-white text-xl shadow-lg shadow-emerald-500/20">
                    P
                </div>
                <div>
                    <div class="font-extrabold tracking-wider text-white text-lg leading-tight">PINNACLE<span class="text-cyan-400">HUNT</span></div>
                    <span class="text-[10px] text-emerald-400 font-semibold uppercase tracking-widest">3-Matrix 7-Lvl</span>
                </div>
            </a>
            <button id="closeMobileMenu" class="md:hidden text-gray-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- User Status Badge -->
        <div class="px-5 py-4 border-b border-white/5">
            <div class="p-3 rounded-xl bg-white/[0.03] border border-white/5 flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center font-bold text-emerald-400 text-sm">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($user['username']) ?></p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium <?= $user['role'] === 'admin' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' ?>">
                        <?= strtoupper($user['role']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto">
            <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2">Member Portal</div>

            <a href="?page=dashboard" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentPage === 'dashboard' ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/10 text-emerald-300 border border-emerald-500/30 font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                <span>Dashboard</span>
            </a>

            <a href="?page=referrals" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentPage === 'referrals' ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/10 text-emerald-300 border border-emerald-500/30 font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>My Referrals</span>
            </a>

            <a href="?page=genealogy" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentPage === 'genealogy' ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/10 text-emerald-300 border border-emerald-500/30 font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="git-fork" class="w-4 h-4"></i>
                <span>Genealogy Tree</span>
            </a>

            <a href="?page=ledger" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentPage === 'ledger' ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/10 text-emerald-300 border border-emerald-500/30 font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="wallet" class="w-4 h-4"></i>
                <span>Ledger & Payouts</span>
            </a>

            <?php if ($user['role'] === 'admin'): ?>
                <div class="pt-4 pb-2">
                    <div class="text-[11px] font-semibold text-purple-400 uppercase tracking-wider px-3 mb-2 flex items-center justify-between">
                        <span>Admin Control</span>
                        <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                    </div>
                </div>

                <a href="?page=admin_dashboard" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentPage === 'admin_dashboard' ? 'bg-gradient-to-r from-purple-500/20 to-cyan-500/10 text-purple-300 border border-purple-500/30 font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                    <i data-lucide="shield-check" class="w-4 h-4 text-purple-400"></i>
                    <span>Admin Overview</span>
                </a>

                <a href="?page=admin_settings" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentPage === 'admin_settings' ? 'bg-gradient-to-r from-purple-500/20 to-cyan-500/10 text-purple-300 border border-purple-500/30 font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                    <i data-lucide="sliders" class="w-4 h-4 text-purple-400"></i>
                    <span>Commission Setup</span>
                </a>

                <a href="?page=admin_matrix_tree" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentPage === 'admin_matrix_tree' ? 'bg-gradient-to-r from-purple-500/20 to-cyan-500/10 text-purple-300 border border-purple-500/30 font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                    <i data-lucide="network" class="w-4 h-4 text-purple-400"></i>
                    <span>Company Matrix</span>
                </a>

                <a href="?page=admin_members" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentPage === 'admin_members' ? 'bg-gradient-to-r from-purple-500/20 to-cyan-500/10 text-purple-300 border border-purple-500/30 font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                    <i data-lucide="user-check" class="w-4 h-4 text-purple-400"></i>
                    <span>Member Directory</span>
                </a>

                <a href="?page=admin_withdrawals" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentPage === 'admin_withdrawals' ? 'bg-gradient-to-r from-purple-500/20 to-cyan-500/10 text-purple-300 border border-purple-500/30 font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                    <i data-lucide="badge-dollar-sign" class="w-4 h-4 text-purple-400"></i>
                    <span>Process Payouts</span>
                </a>
            <?php endif; ?>
        </nav>

        <!-- Quick Logout -->
        <div class="p-4 border-t border-white/10">
            <a href="?action=logout" class="flex items-center space-x-3 px-3 py-2 rounded-xl text-sm font-medium text-rose-400 hover:bg-rose-500/10 transition">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Quick Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Container Offset -->
    <div class="md:ml-64 flex-1 flex flex-col min-h-screen pt-14 md:pt-0">
        <!-- Sticky Glassmorphic Top Navbar -->
        <header class="sticky top-0 z-30 glass-panel border-b border-white/10 px-6 py-3 flex flex-wrap items-center justify-between gap-4">
            <!-- Left breadcrumb / title -->
            <div class="flex items-center space-x-2">
                <span class="text-xs uppercase tracking-wider text-emerald-400 font-bold">Platform</span>
                <span class="text-gray-600">/</span>
                <span class="text-sm font-medium text-white"><?= ucfirst(str_replace('_', ' ', $currentPage)) ?></span>
            </div>

            <!-- Right balance widgets & Referral Link -->
            <div class="flex items-center flex-wrap gap-3">
                <!-- Wallet Balance -->
                <div class="flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                    <i data-lucide="wallet-cards" class="w-4 h-4"></i>
                    <div class="text-xs">
                        <span class="text-emerald-400/70 block text-[10px] uppercase font-bold">Wallet</span>
                        <span class="font-bold">₹<?= number_format((float)$user['wallet_balance'], 2) ?></span>
                    </div>
                </div>

                <!-- Reserve Balance -->
                <div class="flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                    <div class="text-xs">
                        <span class="text-cyan-400/70 block text-[10px] uppercase font-bold">Reserve Pool</span>
                        <span class="font-bold">₹<?= number_format((float)$user['reserve_balance'], 2) ?></span>
                    </div>
                </div>

                <!-- Copy Joining Link Button -->
                <?php
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $domainName = $_SERVER['HTTP_HOST'];
                $joinLink = $protocol . $domainName . "/?page=register&ref=" . urlencode($user['username']);
                ?>
                <button onclick="copyJoinLink('<?= htmlspecialchars($joinLink) ?>')" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-400 hover:to-cyan-400 text-slate-950 font-bold text-xs shadow-lg shadow-emerald-500/20 transition">
                    <i data-lucide="link" class="w-3.5 h-3.5"></i>
                    <span id="copyBtnText">Copy Joining Link</span>
                </button>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto">
            <!-- Flash Messages -->
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="mb-6 p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 flex items-center space-x-3">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 flex items-center space-x-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

<?php else: ?>
    <!-- Guest Layout Wrapper -->
    <div class="min-h-screen flex flex-col justify-center items-center p-4">
        <!-- Logo Header for Auth -->
        <div class="mb-8 text-center">
            <div class="inline-flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-cyan-400 flex items-center justify-center font-black text-white text-2xl shadow-xl shadow-emerald-500/30">
                    P
                </div>
                <div class="text-left">
                    <div class="font-extrabold tracking-wider text-white text-2xl leading-none">PINNACLE<span class="text-cyan-400">HUNT</span></div>
                    <span class="text-xs text-emerald-400 font-semibold uppercase tracking-widest">7-Level BFS 3-Matrix</span>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 max-w-md w-full text-sm">
                <?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 max-w-md w-full text-sm">
                <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
<?php endif; ?>
