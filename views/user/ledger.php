<?php
// views/user/ledger.php
use PinnacleHunt\Config\Database;
use PinnacleHunt\Services\AuthService;

$pageTitle = 'Financial Ledger & Payouts - Pinnacle Hunt';
require_once __DIR__ . '/../layouts/header.php';

$pdo = Database::getConnection();

// User balance
$uStmt = $pdo->prepare("SELECT wallet_balance, reserve_balance FROM users WHERE id = ?");
$uStmt->execute([$user['id']]);
$userBal = $uStmt->fetch();

// Ledger history
$ledgerStmt = $pdo->prepare("SELECT * FROM ledger WHERE user_id = ? ORDER BY id DESC LIMIT 50");
$ledgerStmt->execute([$user['id']]);
$transactions = $ledgerStmt->fetchAll();

// Withdrawal history
$wdStmt = $pdo->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY id DESC LIMIT 20");
$wdStmt->execute([$user['id']]);
$withdrawals = $wdStmt->fetchAll();
?>

<!-- Balances & Withdrawal Form Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Balances Summary -->
    <div class="glass-panel rounded-2xl p-6 border border-white/10 flex flex-col justify-between">
        <div>
            <h3 class="text-base font-bold text-white mb-4 flex items-center space-x-2">
                <i data-lucide="wallet" class="w-5 h-5 text-emerald-400"></i>
                <span>Available Balances</span>
            </h3>

            <div class="space-y-4">
                <div class="p-4 rounded-xl bg-white/[0.03] border border-white/5">
                    <span class="text-xs text-gray-400 block mb-1">Withdrawable Wallet Balance</span>
                    <div class="text-2xl font-black text-emerald-400">₹<?= number_format((float)$userBal['wallet_balance'], 2) ?></div>
                    <span class="text-[11px] text-gray-500 mt-1 block">Includes 100% net direct referrals + 75% matrix payout</span>
                </div>

                <div class="p-4 rounded-xl bg-white/[0.03] border border-white/5">
                    <span class="text-xs text-gray-400 block mb-1">Auto-Progression Reserve Pool</span>
                    <div class="text-2xl font-black text-cyan-400">₹<?= number_format((float)$userBal['reserve_balance'], 2) ?></div>
                    <span class="text-[11px] text-gray-500 mt-1 block">25% matrix withholding for automatic tier promotions</span>
                </div>
            </div>
        </div>

        <div class="text-[11px] text-gray-400 mt-4 pt-4 border-t border-white/5">
            <i data-lucide="shield-check" class="w-3.5 h-3.5 inline text-emerald-400 mr-1"></i>
            Automated double-entry audit balance ledger.
        </div>
    </div>

    <!-- Payout Request Form -->
    <div class="lg:col-span-2 glass-panel rounded-2xl p-6 border border-white/10">
        <h3 class="text-base font-bold text-white mb-1 flex items-center space-x-2">
            <i data-lucide="arrow-down-to-dot" class="w-5 h-5 text-cyan-400"></i>
            <span>Request Payout / Withdrawal</span>
        </h3>
        <p class="text-xs text-gray-400 mb-5">Withdraw funds directly to your UPI ID or Bank Account. Payout requests are audited and processed swiftly.</p>

        <form method="POST" action="?action=request_withdrawal" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Withdrawal Amount (₹)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-emerald-400 font-bold">₹</span>
                        <input type="number" step="0.01" min="100" max="<?= (float)$userBal['wallet_balance'] ?>" name="amount" required
                            class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-emerald-500 text-white placeholder-gray-500 text-sm outline-none"
                            placeholder="Min ₹100.00">
                    </div>
                    <span class="text-[10px] text-gray-400 mt-1 block">Max: ₹<?= number_format((float)$userBal['wallet_balance'], 2) ?></span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Payout Method</label>
                    <select name="payout_type" id="payoutTypeSelect" class="w-full px-4 py-2.5 rounded-xl bg-gray-900 border border-white/10 text-white text-sm outline-none">
                        <option value="UPI">UPI ID (e.g. user@okhdfcbank)</option>
                        <option value="BANK">Bank Account (IMPS / NEFT)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Payment Details / Account Info</label>
                <textarea name="payout_details" rows="2" required
                    class="w-full px-4 py-2 rounded-xl bg-white/[0.04] border border-white/10 focus:border-cyan-500 text-white placeholder-gray-500 text-sm outline-none"
                    placeholder="Enter your UPI ID (e.g. john@upi) or Bank Name, A/C Number, and IFSC Code"></textarea>
            </div>

            <button type="submit" <?= (float)$userBal['wallet_balance'] < 100 ? 'disabled' : '' ?>
                class="py-3 px-6 rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-400 hover:to-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed text-slate-950 font-bold text-sm shadow-lg shadow-emerald-500/20 transition flex items-center space-x-2">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>Submit Withdrawal Request</span>
            </button>
        </form>
    </div>
</div>

<!-- Financial Ledger Audit Table -->
<div class="glass-panel rounded-2xl p-6 mb-8 border border-white/10">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-white flex items-center space-x-2">
            <i data-lucide="scroll-text" class="w-5 h-5 text-emerald-400"></i>
            <span>Financial Transaction Ledger</span>
        </h3>
        <span class="text-xs text-gray-400">Audited Balance Trail</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-300">
            <thead class="text-xs text-gray-400 uppercase bg-white/[0.02] border-b border-white/5">
                <tr>
                    <th class="py-3 px-4">TX ID</th>
                    <th class="py-3 px-4">Type</th>
                    <th class="py-3 px-4">Amount</th>
                    <th class="py-3 px-4">Description</th>
                    <th class="py-3 px-4">Wallet After</th>
                    <th class="py-3 px-4">Reserve After</th>
                    <th class="py-3 px-4">Timestamp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 font-mono text-xs">
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500 font-sans">No transactions recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td class="py-3 px-4 text-gray-500">#<?= $tx['id'] ?></td>
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

<!-- Withdrawal Requests History -->
<div class="glass-panel rounded-2xl p-6 border border-white/10">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-white flex items-center space-x-2">
            <i data-lucide="clock" class="w-5 h-5 text-cyan-400"></i>
            <span>Withdrawal Payout Requests</span>
        </h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-300">
            <thead class="text-xs text-gray-400 uppercase bg-white/[0.02] border-b border-white/5">
                <tr>
                    <th class="py-3 px-4">Request ID</th>
                    <th class="py-3 px-4">Amount</th>
                    <th class="py-3 px-4">Details</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Submitted At</th>
                    <th class="py-3 px-4">Processed At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-xs font-mono">
                <?php if (empty($withdrawals)): ?>
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500 font-sans">No withdrawal requests submitted yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($withdrawals as $wd): ?>
                        <tr>
                            <td class="py-3 px-4 text-gray-500">#<?= $wd['id'] ?></td>
                            <td class="py-3 px-4 font-bold text-white">₹<?= number_format((float)$wd['amount'], 2) ?></td>
                            <td class="py-3 px-4 font-sans text-gray-300 max-w-xs truncate"><?= htmlspecialchars($wd['payout_details']) ?></td>
                            <td class="py-3 px-4">
                                <?php
                                $statusClass = match($wd['status']) {
                                    'pending' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                    'approved' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                    'rejected' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                                    default => 'bg-gray-500/20 text-gray-300'
                                };
                                ?>
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold border <?= $statusClass ?>">
                                    <?= strtoupper($wd['status']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-gray-400 font-sans"><?= date('d M Y, h:i A', strtotime($wd['created_at'])) ?></td>
                            <td class="py-3 px-4 text-gray-400 font-sans"><?= $wd['processed_at'] ? date('d M Y, h:i A', strtotime($wd['processed_at'])) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
