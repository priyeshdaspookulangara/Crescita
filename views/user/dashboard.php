<?php
// views/user/dashboard.php
use PinnacleHunt\Config\Database;
use PinnacleHunt\Services\PackageService;
use PinnacleHunt\Services\AutoProgressionEngine;

$pageTitle = 'Member Dashboard - Pinnacle Hunt';
require_once __DIR__ . '/../layouts/header.php';

$pdo = Database::getConnection();

// User balance & info
$uStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$uStmt->execute([$user['id']]);
$user = $uStmt->fetch();

// Total earnings (direct sponsor + matrix level)
$earnStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE user_id = ?");
$earnStmt->execute([$user['id']]);
$totalEarned = (float)$earnStmt->fetchColumn();

// Total direct referrals
$refStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE sponsor_id = ?");
$refStmt->execute([$user['id']]);
$totalReferrals = (int)$refStmt->fetchColumn();

// Direct referral qualification target
$requiredDirects = (int)\PinnacleHunt\Services\SettingsService::get('direct_referrals_required_for_upgrade', '5');
$isDirectQualified = $totalReferrals >= $requiredDirects;

// Active packages for current user
$userPackages = PackageService::getUserPackages($user['id']);
$userPackageIds = array_column($userPackages, 'id');

// Highest active package
$highestPkg = AutoProgressionEngine::getHighestPackage($user['id']);
$nextPkg = $highestPkg
    ? AutoProgressionEngine::getNextPackage((int)$highestPkg['sort_order'])
    : AutoProgressionEngine::getNextPackage(0);
$progressionPercent = 0;
if ($nextPkg) {
    $progressionPercent = min(100, round(((float)$user['reserve_balance'] / (float)$nextPkg['price']) * 100, 1));
}

// All available 10 tiers
$allPackages = PackageService::getAll();

// Recent ledger transactions
$ledgerStmt = $pdo->prepare("SELECT * FROM ledger WHERE user_id = ? ORDER BY id DESC LIMIT 5");
$ledgerStmt->execute([$user['id']]);
$recentTransactions = $ledgerStmt->fetchAll();
?>

<!-- Next Matrix Qualification Notice Bar -->
<div class="mb-6 p-4 rounded-2xl glass-panel border <?= $isDirectQualified ? 'border-emerald-500/40 bg-emerald-500/[0.03]' : 'border-amber-500/40 bg-amber-500/[0.03]' ?> flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <div class="flex items-center space-x-3">
        <div class="p-2.5 rounded-xl <?= $isDirectQualified ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' ?>">
            <i data-lucide="<?= $isDirectQualified ? 'shield-check' : 'alert-triangle' ?>" class="w-5 h-5"></i>
        </div>
        <div>
            <div class="text-sm font-bold text-white flex items-center space-x-2">
                <span>Next Matrix Qualification Status:</span>
                <?php if ($isDirectQualified): ?>
                    <span class="text-emerald-400 font-extrabold flex items-center space-x-1">
                        <i data-lucide="check" class="w-3.5 h-3.5 inline"></i>
                        <span>Fully Qualified (<?= $totalReferrals ?>/<?= $requiredDirects ?> Directs)</span>
                    </span>
                <?php else: ?>
                    <span class="text-amber-400 font-extrabold flex items-center space-x-1">
                        <span>Under Qualification (<?= $totalReferrals ?>/<?= $requiredDirects ?> Directs)</span>
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">
                <?php if ($isDirectQualified): ?>
                    You have sponsored <?= $totalReferrals ?> direct referrals. No matrix level 2 qualification deductions apply!
                <?php else: ?>
                    If you haven't placed 5 direct referrals, an amount is deducted from Matrix Level 2 of current matrix into your reserve pool to qualify and proceed to the next matrix.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div>
        <a href="?page=referrals" class="px-3.5 py-1.5 rounded-xl <?= $isDirectQualified ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-300 border border-amber-500/20' ?> text-xs font-semibold hover:bg-white/10 transition inline-flex items-center space-x-1.5">
            <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
            <span>Invite Affiliates</span>
        </a>
    </div>
</div>

<!-- Metrics Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <!-- Wallet Card -->
    <div class="glass-card rounded-2xl p-6 relative overflow-hidden group">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition"></div>
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Withdrawable Wallet</span>
            <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <i data-lucide="wallet" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-white mb-1">
            ₹<?= number_format((float)$user['wallet_balance'], 2) ?>
        </div>
        <div class="text-xs text-gray-400 flex items-center space-x-1">
            <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-400 inline"></i>
            <span>Instant payout ready</span>
        </div>
    </div>

    <!-- Reserve Pool Card -->
    <div class="glass-card rounded-2xl p-6 relative overflow-hidden group">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-cyan-500/10 rounded-full blur-xl group-hover:bg-cyan-500/20 transition"></div>
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-cyan-400 uppercase tracking-wider">Auto-Upgrade Pool</span>
            <div class="p-2 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                <i data-lucide="layers" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-white mb-1">
            ₹<?= number_format((float)$user['reserve_balance'], 2) ?>
        </div>
        <div class="text-xs text-gray-400 flex items-center space-x-1">
            <i data-lucide="zap" class="w-3.5 h-3.5 text-cyan-400 inline"></i>
            <span>Funds automatic tier jump</span>
        </div>
    </div>

    <!-- Total Gross Earnings -->
    <div class="glass-card rounded-2xl p-6 relative overflow-hidden group">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-indigo-500/10 rounded-full blur-xl group-hover:bg-indigo-500/20 transition"></div>
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Gross Commission</span>
            <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                <i data-lucide="trending-up" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-white mb-1">
            ₹<?= number_format($totalEarned, 2) ?>
        </div>
        <div class="text-xs text-gray-400 flex items-center space-x-1">
            <i data-lucide="award" class="w-3.5 h-3.5 text-indigo-400 inline"></i>
            <span>Direct (20%) + Matrix (40%)</span>
        </div>
    </div>

    <!-- Direct Network -->
    <div class="glass-card rounded-2xl p-6 relative overflow-hidden group">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-amber-500/10 rounded-full blur-xl group-hover:bg-amber-500/20 transition"></div>
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Direct Referrals</span>
            <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-white mb-1">
            <?= $totalReferrals ?>
        </div>
        <div class="text-xs text-gray-400 flex items-center space-x-1">
            <i data-lucide="link-2" class="w-3.5 h-3.5 text-amber-400 inline"></i>
            <span>Active network affiliates</span>
        </div>
    </div>
</div>

<!-- Auto Progression Status Banner -->
<div class="glass-panel rounded-2xl p-6 mb-8 border border-white/10 relative overflow-hidden">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    Highest Tier: <?= $highestPkg ? htmlspecialchars($highestPkg['name']) : 'None (Unenrolled)' ?>
                </span>
                <?php if ($nextPkg): ?>
                    <span class="text-gray-400 text-xs">Targeting <strong class="text-cyan-300"><?= htmlspecialchars($nextPkg['name']) ?> (₹<?= number_format((float)$nextPkg['price']) ?>)</strong></span>
                <?php else: ?>
                    <span class="text-cyan-400 text-xs font-semibold">Tier 10 Pinnacle Achieved!</span>
                <?php endif; ?>
            </div>
            <p class="text-sm text-gray-300">
                25% of matrix commissions auto-accumulate in your reserve pool. When reaching the next tier price, you are placed into the next company matrix automatically!
            </p>
        </div>

        <?php if ($nextPkg): ?>
            <div class="w-full md:w-64 flex-shrink-0">
                <div class="flex justify-between text-xs mb-1 font-semibold">
                    <span class="text-gray-400">Pool Progress</span>
                    <span class="text-emerald-400"><?= $progressionPercent ?>%</span>
                </div>
                <div class="w-full h-3 rounded-full bg-white/5 border border-white/10 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-cyan-400 rounded-full transition-all duration-500" style="width: <?= $progressionPercent ?>%"></div>
                </div>
                <div class="text-[11px] text-gray-400 mt-1.5 text-right">
                    ₹<?= number_format((float)$user['reserve_balance'], 2) ?> / ₹<?= number_format((float)$nextPkg['price'], 2) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Package Tier Selection & Overview -->
<div class="mb-10">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-xl font-bold text-white">10 Investment Package Tiers</h3>
            <p class="text-xs text-gray-400">Join any tier directly or auto-progress sequentially via your reserve pool.</p>
        </div>
        <span class="text-xs font-mono text-cyan-400 bg-cyan-500/10 px-3 py-1 rounded-full border border-cyan-500/20">
            Company-Wide Forced 3-Matrix
        </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <?php foreach ($allPackages as $pkg): ?>
            <?php
                $isEnrolled = in_array((int)$pkg['id'], $userPackageIds);
            ?>
            <div class="glass-card rounded-2xl p-5 border <?= $isEnrolled ? 'border-emerald-500/50 bg-emerald-500/[0.04]' : 'border-white/10' ?> flex flex-col justify-between transition hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider <?= $isEnrolled ? 'text-emerald-400' : 'text-gray-400' ?>">
                            Tier <?= $pkg['sort_order'] ?>
                        </span>
                        <?php if ($isEnrolled): ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Active</span>
                        <?php endif; ?>
                    </div>
                    <h4 class="font-bold text-white text-base truncate"><?= htmlspecialchars($pkg['name']) ?></h4>
                    <div class="text-2xl font-black text-cyan-400 mt-1 mb-3">
                        ₹<?= number_format((float)$pkg['price']) ?>
                    </div>

                    <ul class="space-y-1.5 text-xs text-gray-300 mb-4">
                        <li class="flex items-center space-x-1.5">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>Direct Bonus: <strong>₹<?= number_format((float)$pkg['price'] * 0.20) ?></strong></span>
                        </li>
                        <li class="flex items-center space-x-1.5">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-cyan-400"></i>
                            <span>Matrix Total: <strong>₹<?= number_format((float)$pkg['price'] * 0.40) ?></strong></span>
                        </li>
                        <li class="flex items-center space-x-1.5">
                            <i data-lucide="git-commit" class="w-3.5 h-3.5 text-indigo-400"></i>
                            <span>Capacity: <strong>3,279 slots</strong></span>
                        </li>
                    </ul>
                </div>

                <div>
                    <?php if ($isEnrolled): ?>
                        <a href="?page=genealogy&tier=<?= $pkg['id'] ?>" class="w-full py-2 px-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-emerald-400 text-xs font-semibold flex items-center justify-center space-x-1.5 transition">
                            <i data-lucide="network" class="w-3.5 h-3.5"></i>
                            <span>View Matrix Tree</span>
                        </a>
                    <?php else: ?>
                        <form method="POST" action="?action=purchase_package">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">
                            <button type="submit" onclick="return confirm('Confirm activation for <?= htmlspecialchars($pkg['name']) ?> at ₹<?= number_format((float)$pkg['price']) ?>?')" class="w-full py-2 px-3 rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-400 hover:to-cyan-400 text-slate-950 text-xs font-bold shadow-md shadow-emerald-500/20 transition flex items-center justify-center space-x-1.5">
                                <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                <span>Activate Package</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent Ledger Activity -->
<div class="glass-panel rounded-2xl p-6 border border-white/10">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-white flex items-center space-x-2">
            <i data-lucide="history" class="w-5 h-5 text-emerald-400"></i>
            <span>Recent Financial Activity</span>
        </h3>
        <a href="?page=ledger" class="text-xs text-emerald-400 hover:underline">View Full Ledger &rarr;</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-300">
            <thead class="text-xs text-gray-400 uppercase bg-white/[0.02] border-b border-white/5">
                <tr>
                    <th class="py-3 px-4">Type</th>
                    <th class="py-3 px-4">Amount</th>
                    <th class="py-3 px-4">Description</th>
                    <th class="py-3 px-4">Wallet After</th>
                    <th class="py-3 px-4">Reserve After</th>
                    <th class="py-3 px-4">Date & Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 font-mono text-xs">
                <?php if (empty($recentTransactions)): ?>
                    <tr>
                        <td colspan="6" class="py-6 text-center text-gray-500 font-sans">No recent financial transactions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentTransactions as $tx): ?>
                        <tr>
                            <td class="py-3 px-4">
                                <?php
                                $badgeClass = match($tx['type']) {
                                    'credit' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                    'debit' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                                    'reserve_hold' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
                                    'upgrade_payment' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
                                    default => 'bg-gray-500/20 text-gray-300 border-gray-500/30'
                                };
                                ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border <?= $badgeClass ?>">
                                    <?= strtoupper($tx['type']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 font-bold <?= $tx['type'] === 'credit' ? 'text-emerald-400' : ($tx['type'] === 'debit' ? 'text-rose-400' : 'text-cyan-400') ?>">
                                ₹<?= number_format((float)$tx['amount'], 2) ?>
                            </td>
                            <td class="py-3 px-4 font-sans text-gray-300"><?= htmlspecialchars($tx['description']) ?></td>
                            <td class="py-3 px-4 text-emerald-400">₹<?= number_format((float)$tx['balance_after'], 2) ?></td>
                            <td class="py-3 px-4 text-cyan-400">₹<?= number_format((float)$tx['reserve_after'], 2) ?></td>
                            <td class="py-3 px-4 text-gray-400 font-sans"><?= date('d M Y, h:i A', strtotime($tx['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
