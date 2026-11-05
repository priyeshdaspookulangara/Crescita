<?php
// views/user/genealogy.php
use PinnacleHunt\Config\Database;
use PinnacleHunt\Services\PackageService;
use PinnacleHunt\Services\MatrixEngine;

$pageTitle = 'Matrix Downline & Genealogy - Crescita';
require_once __DIR__ . '/../layouts/header.php';

$allPackages = PackageService::getAll();
$selectedTierId = isset($_GET['tier']) ? (int)$_GET['tier'] : (int)$allPackages[0]['id'];

// Selected package
$selectedPkg = PackageService::getById($selectedTierId);
if (!$selectedPkg) {
    $selectedPkg = $allPackages[0];
    $selectedTierId = (int)$selectedPkg['id'];
}

// Check if user has node in this package
$pdo = Database::getConnection();
$userNodeStmt = $pdo->prepare("SELECT * FROM matrix_nodes WHERE package_id = ? AND user_id = ?");
$userNodeStmt->execute([$selectedTierId, $user['id']]);
$userNode = $userNodeStmt->fetch();

// Fetch downline subtree starting from this user
$downlineTree = null;
if ($userNode) {
    $downlineTree = MatrixEngine::getDownlineTree($selectedTierId, $user['id'], 7);
}

// Recursive helper function for tree visualization
function renderTreeNode(?array $node, int $level = 1) {
    if (!$node) return;
    $hasChildren = !empty($node['children']);
    ?>
    <div class="flex flex-col items-center">
        <!-- Node Box -->
        <div class="glass-card p-3 rounded-2xl border border-white/10 hover:border-emerald-500/50 transition shadow-lg w-44 text-center relative group">
            <!-- Depth indicator badge -->
            <span class="absolute -top-2.5 left-2 px-2 py-0.5 rounded-full text-[9px] font-mono font-bold bg-gray-900 border border-white/20 text-emerald-400">
                Depth <?= $node['depth'] ?>
            </span>
            <!-- Position Index (1, 2, or 3) -->
            <span class="absolute -top-2.5 right-2 px-1.5 py-0.5 rounded-full text-[9px] font-mono font-bold bg-gray-900 border border-white/20 text-cyan-400">
                Pos #<?= $node['position_index'] ?>
            </span>

            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-emerald-500/20 to-cyan-500/20 border border-emerald-500/40 mx-auto mt-1 mb-2 flex items-center justify-center text-white font-bold text-sm">
                <?= strtoupper(substr($node['username'], 0, 1)) ?>
            </div>

            <div class="font-bold text-white text-xs truncate"><?= htmlspecialchars($node['username']) ?></div>
            <div class="text-[10px] text-gray-400 truncate"><?= htmlspecialchars($node['full_name']) ?></div>

            <div class="mt-2 pt-2 border-t border-white/5 flex items-center justify-between text-[10px] text-gray-400">
                <span>Children:</span>
                <span class="font-bold text-cyan-400"><?= $node['children_count'] ?> / 3</span>
            </div>
        </div>

        <!-- Render Children -->
        <?php if ($hasChildren): ?>
            <!-- Vertical connector line -->
            <div class="w-0.5 h-6 bg-emerald-500/30"></div>
            <!-- Horizontal connector bar -->
            <div class="flex items-start justify-center gap-6 relative before:absolute before:top-0 before:left-12 before:right-12 before:h-0.5 before:bg-emerald-500/30">
                <?php foreach ($node['children'] as $child): ?>
                    <div class="flex flex-col items-center">
                        <div class="w-0.5 h-4 bg-emerald-500/30"></div>
                        <?php renderTreeNode($child, $level + 1); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<!-- Tier Selector Tabs -->
<div class="mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-xl font-bold text-white flex items-center space-x-2">
            <i data-lucide="git-fork" class="w-5 h-5 text-emerald-400"></i>
            <span>3-Matrix Genealogy Tree (7 Levels)</span>
        </h3>
        <span class="text-xs text-gray-400 font-mono">Max Capacity: 3 &rarr; 9 &rarr; 27 &rarr; 81 &rarr; 243 &rarr; 729 &rarr; 2187</span>
    </div>

    <!-- Scrollable Tier Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2">
        <?php foreach ($allPackages as $pkg): ?>
            <a href="?page=genealogy&tier=<?= $pkg['id'] ?>"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex-shrink-0 flex items-center space-x-2 border <?= $pkg['id'] == $selectedTierId ? 'bg-gradient-to-r from-emerald-500 to-cyan-500 text-slate-950 border-emerald-400 shadow-md shadow-emerald-500/20' : 'glass-panel text-gray-300 hover:text-white border-white/5 hover:border-white/20' ?>">
                <span><?= htmlspecialchars($pkg['name']) ?></span>
                <span class="text-[10px] font-mono opacity-80">(₹<?= number_format((float)$pkg['price']) ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tree Information Bar -->
<div class="glass-panel rounded-2xl p-4 mb-6 border border-white/10 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center space-x-3">
        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            <i data-lucide="network" class="w-5 h-5"></i>
        </div>
        <div>
            <div class="text-sm font-bold text-white">Active Tier: <?= htmlspecialchars($selectedPkg['name']) ?> (₹<?= number_format((float)$selectedPkg['price']) ?>)</div>
            <div class="text-xs text-gray-400">Direct Sponsor Bonus: 20% | Matrix Payout across 7 Levels: 40%</div>
        </div>
    </div>

    <?php if ($userNode): ?>
        <div class="flex items-center space-x-4 text-xs font-mono">
            <span class="px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">Your Node ID: #<?= $userNode['id'] ?></span>
            <span class="px-3 py-1 rounded-lg bg-cyan-500/10 text-cyan-300 border border-cyan-500/20">Tree Depth: <?= $userNode['depth'] ?></span>
            <span class="px-3 py-1 rounded-lg bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">Direct Children: <?= $userNode['children_count'] ?> / 3</span>
        </div>
    <?php endif; ?>
</div>

<!-- Visual Tree Container -->
<div class="glass-panel rounded-2xl p-8 border border-white/10 overflow-x-auto min-h-[500px] flex items-center justify-center">
    <?php if (!$userNode): ?>
        <div class="text-center py-12 max-w-md">
            <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mx-auto mb-4 text-gray-500">
                <i data-lucide="git-commit" class="w-8 h-8"></i>
            </div>
            <h4 class="text-lg font-bold text-white mb-2">Not Enrolled in <?= htmlspecialchars($selectedPkg['name']) ?></h4>
            <p class="text-xs text-gray-400 mb-6">You have not been placed in this tier's company matrix yet. Activate this package or auto-progress via your reserve pool to enter this tree.</p>
            <form method="POST" action="?action=purchase_package">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="package_id" value="<?= $selectedPkg['id'] ?>">
                <button type="submit" class="py-2.5 px-6 rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-emerald-500/25 transition">
                    Activate <?= htmlspecialchars($selectedPkg['name']) ?> (₹<?= number_format((float)$selectedPkg['price']) ?>)
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="py-4">
            <?php renderTreeNode($downlineTree, 1); ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
