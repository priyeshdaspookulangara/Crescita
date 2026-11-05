<?php
// views/admin/matrix_tree.php
use PinnacleHunt\Config\Database;
use PinnacleHunt\Services\PackageService;
use PinnacleHunt\Services\MatrixEngine;

$pageTitle = 'Global 3-Matrix Tree Explorer - Admin';
require_once __DIR__ . '/../layouts/header.php';

$allPackages = PackageService::getAll();
$selectedTierId = isset($_GET['tier']) ? (int)$_GET['tier'] : (int)$allPackages[0]['id'];

$selectedPkg = PackageService::getById($selectedTierId);
if (!$selectedPkg) {
    $selectedPkg = $allPackages[0];
    $selectedTierId = (int)$selectedPkg['id'];
}

$pdo = Database::getConnection();

// Total nodes in this package matrix
$nodeCountStmt = $pdo->prepare("SELECT COUNT(*) FROM matrix_nodes WHERE package_id = ?");
$nodeCountStmt->execute([$selectedTierId]);
$totalNodes = (int)$nodeCountStmt->fetchColumn();

// Max depth reached
$depthStmt = $pdo->prepare("SELECT COALESCE(MAX(depth), 0) FROM matrix_nodes WHERE package_id = ?");
$depthStmt->execute([$selectedTierId]);
$maxDepthReached = (int)$depthStmt->fetchColumn();

// Fetch company tree root
$companyTree = MatrixEngine::getCompanyTree($selectedTierId, 7);

// Helper for rendering expandable tree nodes
function renderAdminTreeNode(?array $node, int $level = 1) {
    if (!$node) return;
    $hasChildren = !empty($node['children']);
    ?>
    <div class="flex flex-col items-center">
        <!-- Node Box -->
        <div class="glass-card p-3 rounded-2xl border border-white/10 hover:border-cyan-500/50 transition shadow-lg w-44 text-center relative group">
            <span class="absolute -top-2.5 left-2 px-2 py-0.5 rounded-full text-[9px] font-mono font-bold bg-gray-900 border border-white/20 text-purple-300">
                Depth <?= $node['depth'] ?>
            </span>
            <span class="absolute -top-2.5 right-2 px-1.5 py-0.5 rounded-full text-[9px] font-mono font-bold bg-gray-900 border border-white/20 text-cyan-400">
                Pos #<?= $node['position_index'] ?>
            </span>

            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-500/20 to-cyan-500/20 border border-purple-500/40 mx-auto mt-1 mb-2 flex items-center justify-center text-white font-bold text-sm">
                <?= strtoupper(substr($node['username'], 0, 1)) ?>
            </div>

            <div class="font-bold text-white text-xs truncate">#<?= $node['id'] ?>: <?= htmlspecialchars($node['username']) ?></div>
            <div class="text-[10px] text-gray-400 truncate"><?= htmlspecialchars($node['full_name']) ?></div>

            <div class="mt-2 pt-2 border-t border-white/5 flex items-center justify-between text-[10px] text-gray-400 font-mono">
                <span>Slots:</span>
                <span class="font-bold text-emerald-400"><?= $node['children_count'] ?> / 3</span>
            </div>
        </div>

        <?php if ($hasChildren): ?>
            <div class="w-0.5 h-6 bg-cyan-500/30"></div>
            <div class="flex items-start justify-center gap-6 relative before:absolute before:top-0 before:left-12 before:right-12 before:h-0.5 before:bg-cyan-500/30">
                <?php foreach ($node['children'] as $child): ?>
                    <div class="flex flex-col items-center">
                        <div class="w-0.5 h-4 bg-cyan-500/30"></div>
                        <?php renderAdminTreeNode($child, $level + 1); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h2 class="text-2xl font-black text-white flex items-center space-x-2">
                <i data-lucide="network" class="w-6 h-6 text-cyan-400"></i>
                <span>Company-Wide 3-Matrix Explorer</span>
            </h2>
            <p class="text-xs text-gray-400">Inspect BFS forced spillover placement across depth levels 1 to 7.</p>
        </div>
        <div class="flex items-center space-x-3 text-xs font-mono">
            <span class="px-3 py-1.5 rounded-xl bg-purple-500/10 text-purple-300 border border-purple-500/20">
                Total Matrix Nodes: <strong><?= $totalNodes ?></strong>
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-cyan-500/10 text-cyan-300 border border-cyan-500/20">
                Max Depth: <strong>Level <?= $maxDepthReached ?> / 7</strong>
            </span>
        </div>
    </div>

    <!-- Scrollable Tier Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2">
        <?php foreach ($allPackages as $pkg): ?>
            <a href="?page=admin_matrix_tree&tier=<?= $pkg['id'] ?>"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex-shrink-0 flex items-center space-x-2 border <?= $pkg['id'] == $selectedTierId ? 'bg-gradient-to-r from-purple-500 to-cyan-500 text-white border-purple-400 shadow-md shadow-purple-500/20' : 'glass-panel text-gray-300 hover:text-white border-white/5 hover:border-white/20' ?>">
                <span><?= htmlspecialchars($pkg['name']) ?></span>
                <span class="text-[10px] font-mono opacity-80">(₹<?= number_format((float)$pkg['price']) ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tree Container -->
<div class="glass-panel rounded-2xl p-8 border border-white/10 overflow-x-auto min-h-[500px] flex items-center justify-center">
    <?php if (!$companyTree): ?>
        <div class="text-center py-12 max-w-md">
            <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mx-auto mb-4 text-gray-500">
                <i data-lucide="network" class="w-8 h-8"></i>
            </div>
            <h4 class="text-lg font-bold text-white mb-2">No Tree Placements for <?= htmlspecialchars($selectedPkg['name']) ?></h4>
            <p class="text-xs text-gray-400">No member has bought or upgraded into this package tier yet.</p>
        </div>
    <?php else: ?>
        <div class="py-4">
            <?php renderAdminTreeNode($companyTree, 1); ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
