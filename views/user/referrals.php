<?php
// views/user/referrals.php
use PinnacleHunt\Config\Database;
use PinnacleHunt\Services\AuthService;

$pageTitle = 'My Referrals - Crescita';
require_once __DIR__ . '/../layouts/header.php';

$pdo = Database::getConnection();

// Direct referral stats
$refStmt = $pdo->prepare("
    SELECT u.*,
        COUNT(up.id) as active_pkg_count,
        COALESCE(SUM(c.amount), 0) as total_sponsor_bonus
    FROM users u
    LEFT JOIN user_packages up ON u.id = up.user_id AND up.status = 'active'
    LEFT JOIN commissions c ON c.source_user_id = u.id AND c.user_id = ? AND c.type = 'direct_sponsor'
    WHERE u.sponsor_id = ?
    GROUP BY u.id
    ORDER BY u.id DESC
");
$refStmt->execute([$user['id'], $user['id']]);
$referrals = $refStmt->fetchAll();

$totalDirectCount = count($referrals);
$activePackagesCount = array_sum(array_column($referrals, 'active_pkg_count'));
$totalDirectBonus = array_sum(array_column($referrals, 'total_sponsor_bonus'));

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$referralUrl = $protocol . $_SERVER['HTTP_HOST'] . "/?page=register&ref=" . urlencode($user['username']);
$shareText = "Join me on Crescita! Earn 20% direct sponsor bonus with ZERO cutting and 40% matrix spillover across 7 levels: " . $referralUrl;
$waShareUrl = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
$tgShareUrl = "https://t.me/share/url?url=" . urlencode($referralUrl) . "&text=" . urlencode("Join Crescita - 7-Level BFS 3-Matrix Platform!");
?>

<!-- Referral Metrics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <div class="glass-card rounded-2xl p-6 relative overflow-hidden group">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Total Direct Referrals</span>
            <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-white mb-1">
            <?= $totalDirectCount ?>
        </div>
        <p class="text-xs text-gray-400">Personally sponsored members</p>
    </div>

    <div class="glass-card rounded-2xl p-6 relative overflow-hidden group">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-cyan-400 uppercase tracking-wider">Active Package Tiers</span>
            <div class="p-2 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                <i data-lucide="box" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-white mb-1">
            <?= $activePackagesCount ?>
        </div>
        <p class="text-xs text-gray-400">Total packages active across team</p>
    </div>

    <div class="glass-card rounded-2xl p-6 relative overflow-hidden group">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Direct Bonus Earned</span>
            <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                <i data-lucide="badge-dollar-sign" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-white mb-1">
            ₹<?= number_format((float)$totalDirectBonus, 2) ?>
        </div>
        <p class="text-xs text-emerald-400 font-semibold">100% Net Withdrawable (0% Reserve Cut)</p>
    </div>
</div>

<!-- Instant Referral Link & Social Sharing Widget -->
<div class="glass-panel rounded-2xl p-6 mb-8 border border-white/10">
    <div class="max-w-3xl">
        <h3 class="text-lg font-bold text-white mb-1">Instant Referral Link</h3>
        <p class="text-xs text-gray-400 mb-4">Share your personalized invite link to earn an immediate 20.00% direct sponsor bonus on every package activation and auto-progression.</p>

        <div class="flex flex-col sm:flex-row items-center gap-3 mb-4">
            <input type="text" readonly value="<?= htmlspecialchars($referralUrl) ?>" id="refLinkInput"
                class="w-full px-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 text-emerald-300 font-mono text-sm outline-none">
            <button onclick="copyToClipboard()" id="copyReferralBtn" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm shadow-lg shadow-emerald-500/20 transition flex items-center justify-center space-x-2 flex-shrink-0">
                <i data-lucide="copy" class="w-4 h-4"></i>
                <span id="copyReferralText">Copy Link</span>
            </button>
        </div>

        <!-- Social Share Buttons -->
        <div class="flex flex-wrap items-center gap-3 pt-2">
            <span class="text-xs text-gray-400 font-semibold">Share via:</span>

            <a href="<?= htmlspecialchars($waShareUrl) ?>" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition">
                <i data-lucide="message-circle" class="w-4 h-4 text-emerald-400"></i>
                <span>WhatsApp</span>
            </a>

            <a href="<?= htmlspecialchars($tgShareUrl) ?>" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-xl bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-300 border border-cyan-500/30 text-xs font-semibold transition">
                <i data-lucide="send" class="w-4 h-4 text-cyan-400"></i>
                <span>Telegram</span>
            </a>
        </div>
    </div>
</div>

<!-- Direct Referral Directory Table -->
<div class="glass-panel rounded-2xl p-6 border border-white/10">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-white flex items-center space-x-2">
            <i data-lucide="list" class="w-5 h-5 text-emerald-400"></i>
            <span>Direct Referral Directory</span>
        </h3>
        <span class="text-xs text-gray-400"><?= count($referrals) ?> members listed</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-300">
            <thead class="text-xs text-gray-400 uppercase bg-white/[0.02] border-b border-white/5">
                <tr>
                    <th class="py-3 px-4">Member</th>
                    <th class="py-3 px-4">Contact Info</th>
                    <th class="py-3 px-4">Active Packages</th>
                    <th class="py-3 px-4">Direct Bonus Generated</th>
                    <th class="py-3 px-4">Joined Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-xs">
                <?php if (empty($referrals)): ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">You haven't referred any members yet. Share your referral link above!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($referrals as $ref): ?>
                        <tr>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white"><?= htmlspecialchars($ref['full_name']) ?></div>
                                <div class="text-emerald-400 font-mono">@<?= htmlspecialchars($ref['username']) ?></div>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <div><?= htmlspecialchars($ref['email']) ?></div>
                                <div class="text-gray-400"><?= htmlspecialchars($ref['phone']) ?></div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-cyan-500/10 text-cyan-300 border border-cyan-500/20 font-mono">
                                    <?= $ref['active_pkg_count'] ?> Active Tiers
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-emerald-400 text-sm">
                                ₹<?= number_format((float)$ref['total_sponsor_bonus'], 2) ?>
                            </td>
                            <td class="py-3.5 px-4 text-gray-400 font-sans">
                                <?= date('d M Y', strtotime($ref['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function copyToClipboard() {
        const copyInput = document.getElementById('refLinkInput');
        copyInput.select();
        copyInput.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyInput.value);

        const btnText = document.getElementById('copyReferralText');
        const orig = btnText.innerText;
        btnText.innerText = 'Copied!';
        setTimeout(() => {
            btnText.innerText = orig;
        }, 2000);
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
