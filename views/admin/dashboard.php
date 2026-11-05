<?php
// views/admin/dashboard.php
use PinnacleHunt\Config\Database;

$pageTitle = 'Admin Executive Dashboard - Pinnacle Hunt';
require_once __DIR__ . '/../layouts/header.php';

$pdo = Database::getConnection();

// System wide metrics
$totalMembers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalWalletLiabilities = (float)$pdo->query("SELECT COALESCE(SUM(wallet_balance), 0) FROM users")->fetchColumn();
$totalReserveHeld = (float)$pdo->query("SELECT COALESCE(SUM(reserve_balance), 0) FROM users")->fetchColumn();
$grossCommissionsPaid = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM commissions")->fetchColumn();
$pendingWithdrawals = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
$pendingWithdrawalAmount = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-black text-white">System Executive Overview</h2>
        <p class="text-xs text-gray-400 mt-1">Real-time liabilities, reserve pool telemetry, and network health</p>
    </div>

    <!-- Quick CSV Export Tools -->
    <div class="flex flex-wrap items-center gap-2">
        <a href="?action=export_csv&type=ledger" class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-semibold text-gray-300 flex items-center space-x-1.5 transition">
            <i data-lucide="download" class="w-3.5 h-3.5 text-emerald-400"></i>
            <span>Export Ledger CSV</span>
        </a>
        <a href="?action=export_csv&type=commissions" class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-semibold text-gray-300 flex items-center space-x-1.5 transition">
            <i data-lucide="download" class="w-3.5 h-3.5 text-cyan-400"></i>
            <span>Export Commissions CSV</span>
        </a>
        <a href="?action=export_csv&type=withdrawals" class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-semibold text-gray-300 flex items-center space-x-1.5 transition">
            <i data-lucide="download" class="w-3.5 h-3.5 text-purple-400"></i>
            <span>Export Withdrawals CSV</span>
        </a>
    </div>
</div>

<!-- Key Metric Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
    <div class="glass-card rounded-2xl p-5 border border-white/10">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Members</span>
            <i data-lucide="users" class="w-4 h-4 text-emerald-400"></i>
        </div>
        <div class="text-2xl font-black text-white"><?= number_format($totalMembers) ?></div>
        <span class="text-[10px] text-emerald-400 font-mono mt-1 block">Active Network Size</span>
    </div>

    <div class="glass-card rounded-2xl p-5 border border-white/10">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Wallet Liabilities</span>
            <i data-lucide="wallet" class="w-4 h-4 text-emerald-400"></i>
        </div>
        <div class="text-2xl font-black text-emerald-400">₹<?= number_format($totalWalletLiabilities, 2) ?></div>
        <span class="text-[10px] text-gray-400 font-mono mt-1 block">Withdrawable Balances</span>
    </div>

    <div class="glass-card rounded-2xl p-5 border border-white/10">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Reserve Pool</span>
            <i data-lucide="layers" class="w-4 h-4 text-cyan-400"></i>
        </div>
        <div class="text-2xl font-black text-cyan-400">₹<?= number_format($totalReserveHeld, 2) ?></div>
        <span class="text-[10px] text-cyan-400 font-mono mt-1 block">Held for Auto-Progression</span>
    </div>

    <div class="glass-card rounded-2xl p-5 border border-white/10">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Gross Payouts</span>
            <i data-lucide="award" class="w-4 h-4 text-purple-400"></i>
        </div>
        <div class="text-2xl font-black text-purple-300">₹<?= number_format($grossCommissionsPaid, 2) ?></div>
        <span class="text-[10px] text-gray-400 font-mono mt-1 block">20% Direct + 40% Matrix</span>
    </div>

    <div class="glass-card rounded-2xl p-5 border border-white/10">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Pending Payouts</span>
            <i data-lucide="clock" class="w-4 h-4 text-amber-400"></i>
        </div>
        <div class="text-2xl font-black text-amber-300"><?= $pendingWithdrawals ?></div>
        <span class="text-[10px] text-amber-400 font-mono mt-1 block">₹<?= number_format($pendingWithdrawalAmount, 2) ?> Pending</span>
    </div>
</div>

<!-- Quick Action Shortcuts -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <a href="?page=admin_settings" class="glass-panel p-6 rounded-2xl border border-white/10 hover:border-emerald-500/40 transition group">
        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 mb-4 group-hover:scale-105 transition">
            <i data-lucide="sliders" class="w-6 h-6"></i>
        </div>
        <h3 class="text-base font-bold text-white mb-1">Commission & Upgrade Settings</h3>
        <p class="text-xs text-gray-400">Configure Reserve Pool Withholding (25%), Direct Bonus (20%), and 7-Level Matrix percentages.</p>
    </a>

    <a href="?page=admin_matrix_tree" class="glass-panel p-6 rounded-2xl border border-white/10 hover:border-cyan-500/40 transition group">
        <div class="w-12 h-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 mb-4 group-hover:scale-105 transition">
            <i data-lucide="network" class="w-6 h-6"></i>
        </div>
        <h3 class="text-base font-bold text-white mb-1">Global 3-Matrix Tree Explorer</h3>
        <p class="text-xs text-gray-400">View company-wide BFS 3-Matrix trees for all 10 tiers, from ₹500 Starter to ₹1,00,000 Pinnacle.</p>
    </a>

    <a href="?page=admin_withdrawals" class="glass-panel p-6 rounded-2xl border border-white/10 hover:border-purple-500/40 transition group">
        <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 mb-4 group-hover:scale-105 transition">
            <i data-lucide="badge-dollar-sign" class="w-6 h-6"></i>
        </div>
        <h3 class="text-base font-bold text-white mb-1">Process Pending Withdrawals</h3>
        <p class="text-xs text-gray-400">Approve bank/UPI payouts or reject with instant 100% automated refund to member wallet.</p>
    </a>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
