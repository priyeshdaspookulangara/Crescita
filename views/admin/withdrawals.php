<?php
// views/admin/withdrawals.php
use PinnacleHunt\Config\Database;

$pageTitle = 'Withdrawal Processing & Payout Audit - Admin';
require_once __DIR__ . '/../layouts/header.php';

$pdo = Database::getConnection();

// Fetch all withdrawal requests
$stmt = $pdo->query("
    SELECT w.*, u.username, u.full_name, u.email, u.phone, u.wallet_balance
    FROM withdrawals w
    JOIN users u ON w.user_id = u.id
    ORDER BY CASE WHEN w.status = 'pending' THEN 0 ELSE 1 END, w.id DESC
");
$withdrawals = $stmt->fetchAll();
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-black text-white flex items-center space-x-2">
            <i data-lucide="badge-dollar-sign" class="w-6 h-6 text-purple-400"></i>
            <span>Withdrawal Requests & Payout Processing</span>
        </h2>
        <p class="text-xs text-gray-400">Review pending member withdrawals. Rejecting will automatically refund 100% of the funds back to the member's wallet balance.</p>
    </div>

    <div>
        <a href="?action=export_csv&type=withdrawals" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition flex items-center space-x-2 shadow-lg shadow-purple-500/20">
            <i data-lucide="download" class="w-4 h-4"></i>
            <span>Download Withdrawals CSV</span>
        </a>
    </div>
</div>

<div class="glass-panel rounded-2xl p-6 border border-white/10">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-300">
            <thead class="text-xs text-gray-400 uppercase bg-white/[0.02] border-b border-white/5">
                <tr>
                    <th class="py-3 px-4">ID</th>
                    <th class="py-3 px-4">Member</th>
                    <th class="py-3 px-4">Amount</th>
                    <th class="py-3 px-4">Payout Destination Details</th>
                    <th class="py-3 px-4">Current Status</th>
                    <th class="py-3 px-4">Requested At</th>
                    <th class="py-3 px-4">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-xs font-mono">
                <?php if (empty($withdrawals)): ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500 font-sans">No withdrawal requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($withdrawals as $w): ?>
                        <tr>
                            <td class="py-3.5 px-4 text-gray-500">#<?= $w['id'] ?></td>
                            <td class="py-3.5 px-4 font-sans">
                                <div class="font-bold text-white"><?= htmlspecialchars($w['full_name']) ?></div>
                                <div class="text-emerald-400 font-mono text-xs">@<?= htmlspecialchars($w['username']) ?></div>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-emerald-400 text-sm">
                                ₹<?= number_format((float)$w['amount'], 2) ?>
                            </td>
                            <td class="py-3.5 px-4 font-sans text-gray-300 max-w-xs break-words">
                                <?= htmlspecialchars($w['payout_details']) ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php
                                $statusClass = match($w['status']) {
                                    'pending' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                    'approved' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                    'rejected' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                                    default => 'bg-gray-500/20 text-gray-300'
                                };
                                ?>
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold border <?= $statusClass ?>">
                                    <?= strtoupper($w['status']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-gray-400 font-sans">
                                <?= date('d M Y, h:i A', strtotime($w['created_at'])) ?>
                            </td>
                            <td class="py-3.5 px-4 font-sans">
                                <?php if ($w['status'] === 'pending'): ?>
                                    <div class="flex items-center space-x-2">
                                        <!-- Approve form -->
                                        <form method="POST" action="?action=process_withdrawal" onsubmit="return confirm('Approve withdrawal #<?= $w['id'] ?> for ₹<?= number_format((float)$w['amount'], 2) ?>?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                            <input type="hidden" name="decision" value="approve">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs flex items-center space-x-1 transition">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>Approve</span>
                                            </button>
                                        </form>

                                        <!-- Reject form -->
                                        <form method="POST" action="?action=process_withdrawal" onsubmit="return confirm('Reject and refund ₹<?= number_format((float)$w['amount'], 2) ?> back to @<?= htmlspecialchars($w['username']) ?>?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                            <input type="hidden" name="decision" value="reject">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 font-semibold text-xs flex items-center space-x-1 transition">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                <span>Reject & Refund</span>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-500 font-sans">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
