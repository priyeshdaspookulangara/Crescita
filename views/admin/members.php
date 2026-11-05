<?php
// views/admin/members.php
use PinnacleHunt\Config\Database;

$pageTitle = 'Member Directory & Management - Admin';
require_once __DIR__ . '/../layouts/header.php';

$pdo = Database::getConnection();

// Search filter
$search = trim($_GET['search'] ?? '');
$sql = "
    SELECT u.*, s.username as sponsor_name,
        COUNT(up.id) as active_pkg_count
    FROM users u
    LEFT JOIN users s ON u.sponsor_id = s.id
    LEFT JOIN user_packages up ON u.id = up.user_id AND up.status = 'active'
";

if (!empty($search)) {
    $sql .= " WHERE u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ?";
}
$sql .= " GROUP BY u.id ORDER BY u.id DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
if (!empty($search)) {
    $param = "%{$search}%";
    $stmt->execute([$param, $param, $param, $param]);
} else {
    $stmt->execute();
}
$members = $stmt->fetchAll();
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-black text-white flex items-center space-x-2">
            <i data-lucide="user-check" class="w-6 h-6 text-purple-400"></i>
            <span>Affiliate Member Directory</span>
        </h2>
        <p class="text-xs text-gray-400">Monitor balances, reserve pools, sponsor affiliations, and security roles.</p>
    </div>

    <!-- Search input -->
    <form method="GET" action="" class="flex items-center space-x-2">
        <input type="hidden" name="page" value="admin_members">
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <i data-lucide="search" class="w-4 h-4"></i>
            </span>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search user, email, phone..."
                class="pl-9 pr-4 py-2 rounded-xl bg-white/[0.04] border border-white/10 text-white text-xs outline-none focus:border-purple-500 w-64">
        </div>
        <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition">
            Filter
        </button>
        <?php if (!empty($search)): ?>
            <a href="?page=admin_members" class="px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs text-gray-300">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="glass-panel rounded-2xl p-6 border border-white/10">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-300">
            <thead class="text-xs text-gray-400 uppercase bg-white/[0.02] border-b border-white/5">
                <tr>
                    <th class="py-3 px-4">User</th>
                    <th class="py-3 px-4">Contact</th>
                    <th class="py-3 px-4">Sponsor</th>
                    <th class="py-3 px-4">Role</th>
                    <th class="py-3 px-4">Wallet Balance</th>
                    <th class="py-3 px-4">Reserve Pool</th>
                    <th class="py-3 px-4">Active Tiers</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Joined Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-xs">
                <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="9" class="py-8 text-center text-gray-500">No member records found matching criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white"><?= htmlspecialchars($m['full_name']) ?></div>
                                <div class="text-purple-400 font-mono">@<?= htmlspecialchars($m['username']) ?> (#<?= $m['id'] ?>)</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <div><?= htmlspecialchars($m['email']) ?></div>
                                <div class="text-gray-400"><?= htmlspecialchars($m['phone']) ?></div>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if ($m['sponsor_name']): ?>
                                    <span class="text-emerald-400 font-mono font-semibold">@<?= htmlspecialchars($m['sponsor_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-gray-500">None (System Root)</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold <?= $m['role'] === 'admin' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-gray-500/20 text-gray-300 border border-gray-500/30' ?>">
                                    <?= strtoupper($m['role']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-emerald-400">
                                ₹<?= number_format((float)$m['wallet_balance'], 2) ?>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-cyan-400">
                                ₹<?= number_format((float)$m['reserve_balance'], 2) ?>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <span class="px-2.5 py-0.5 rounded-full bg-cyan-500/10 text-cyan-300 border border-cyan-500/20">
                                    <?= $m['active_pkg_count'] ?> Active
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    <?= strtoupper($m['status']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-gray-400 font-sans">
                                <?= date('d M Y', strtotime($m['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
