<?php

require_once __DIR__ . '/../src/autoload.php';

use PinnacleHunt\Config\Database;
use PinnacleHunt\Services\AuthService;
use PinnacleHunt\Services\SettingsService;
use PinnacleHunt\Services\PackageService;
use PinnacleHunt\Services\MatrixEngine;
use PinnacleHunt\Services\CommissionEngine;
use PinnacleHunt\Services\AutoProgressionEngine;
use PinnacleHunt\Services\LedgerService;

function runAssertion(string $testName, bool $condition, string $details = '') {
    if ($condition) {
        echo "[\033[32mPASS\033[0m] {$testName}\n";
    } else {
        echo "[\033[31mFAIL\033[0m] {$testName} - {$details}\n";
        exit(1);
    }
}

echo "========================================================\n";
echo "   CRESCITA: AUTOMATED BACKEND TEST SUITE\n";
echo "========================================================\n\n";

$pdo = Database::getConnection();

// Reset test database to fresh state
echo "1. Resetting database to baseline schema...\n";
$schemaSql = file_get_contents(__DIR__ . '/../database/schema.sql');
$pdo->exec($schemaSql);
echo "   Database reset complete.\n\n";

// TEST 1: Registration and sponsor link tracking
echo "2. Running Test 1: User Registration & Sponsor Tracking...\n";
$userA = AuthService::register([
    'username' => 'alice',
    'email' => 'alice@test.com',
    'password' => 'secret123',
    'full_name' => 'Alice Wonderland',
    'phone' => '+919111111111',
    'sponsor' => 'admin'
]);
runAssertion("User A (Alice) registered with admin sponsor", $userA['sponsor_id'] === 1);

$userB = AuthService::register([
    'username' => 'bob',
    'email' => 'bob@test.com',
    'password' => 'secret123',
    'full_name' => 'Bob Builder',
    'phone' => '+919222222222',
    'sponsor' => 'alice'
]);
runAssertion("User B (Bob) registered with Alice as sponsor", $userB['sponsor_id'] === $userA['id']);

// TEST 2: BFS 3-Matrix Placement Logic
echo "\n3. Running Test 2: BFS 3-Matrix Placement (3 Children per Node, Depth 1-7)...\n";
// Alice activates Tier 1 (₹500). Should be root (depth 1, pos 1, parent NULL)
$resA = PackageService::purchasePackage($userA['id'], 1);
runAssertion("Alice placed as Root node at depth 1", $resA['matrix_node']['depth'] === 1 && $resA['matrix_node']['parent_id'] === null);

// Bob activates Tier 1. Should be Alice's child 1 (depth 2, pos 1)
$resB = PackageService::purchasePackage($userB['id'], 1);
runAssertion("Bob placed as child 1 of Alice at depth 2",
    $resB['matrix_node']['depth'] === 2 &&
    $resB['matrix_node']['parent_id'] === $resA['matrix_node']['id'] &&
    $resB['matrix_node']['position_index'] === 1
);

// Register and activate Charlie & Dave under Alice to fill Alice's 3 slots
$userC = AuthService::register([
    'username' => 'charlie',
    'email' => 'charlie@test.com',
    'password' => 'secret123',
    'full_name' => 'Charlie Chaplin',
    'phone' => '+919333333333',
    'sponsor' => 'alice'
]);
$resC = PackageService::purchasePackage($userC['id'], 1);
runAssertion("Charlie placed as child 2 of Alice",
    $resC['matrix_node']['depth'] === 2 &&
    $resC['matrix_node']['position_index'] === 2 &&
    $resC['matrix_node']['parent_id'] === $resA['matrix_node']['id']
);

$userD = AuthService::register([
    'username' => 'dave',
    'email' => 'dave@test.com',
    'password' => 'secret123',
    'full_name' => 'Dave Miller',
    'phone' => '+919444444444',
    'sponsor' => 'alice'
]);
$resD = PackageService::purchasePackage($userD['id'], 1);
runAssertion("Dave placed as child 3 of Alice (Alice slots now full = 3)",
    $resD['matrix_node']['depth'] === 2 &&
    $resD['matrix_node']['position_index'] === 3 &&
    $resD['matrix_node']['parent_id'] === $resA['matrix_node']['id']
);

// Check Alice's children_count in matrix_nodes
$aliceNodeStmt = $pdo->prepare("SELECT children_count FROM matrix_nodes WHERE id = ?");
$aliceNodeStmt->execute([$resA['matrix_node']['id']]);
$aliceChildrenCount = (int)$aliceNodeStmt->fetchColumn();
runAssertion("Alice node children_count is 3", $aliceChildrenCount === 3);

// Test Forced Spillover: User E (Eve) activates Tier 1.
// BFS should select Bob (first node with children_count < 3 at depth 2) as parent. Depth should be 3, pos 1.
$userE = AuthService::register([
    'username' => 'eve',
    'email' => 'eve@test.com',
    'password' => 'secret123',
    'full_name' => 'Eve Adams',
    'phone' => '+919555555555',
    'sponsor' => 'alice'
]);
$resE = PackageService::purchasePackage($userE['id'], 1);
runAssertion("Forced Spillover: Eve placed under Bob at depth 3, pos 1",
    $resE['matrix_node']['depth'] === 3 &&
    $resE['matrix_node']['parent_id'] === $resB['matrix_node']['id'] &&
    $resE['matrix_node']['position_index'] === 1
);

// TEST 3: Direct Sponsor Commission (20.00% - Zero Cutting)
echo "\n4. Running Test 3: Direct Sponsor Commission Calculation & Zero Cutting...\n";
// Alice sponsored Bob, Charlie, Dave, Eve. Each purchased ₹500 package.
// Direct commission per ₹500 package is 20% = ₹100.
// Zero cutting: 100% credited to wallet_balance, reserved_amount = 0.
$directCommStmt = $pdo->prepare("SELECT * FROM commissions WHERE user_id = ? AND type = 'direct_sponsor'");
$directCommStmt->execute([$userA['id']]);
$aliceDirectComms = $directCommStmt->fetchAll();
runAssertion("Alice received 4 direct sponsor commissions", count($aliceDirectComms) === 4);

$allZeroCut = true;
foreach ($aliceDirectComms as $c) {
    if ((float)$c['amount'] !== 100.00 || (float)$c['reserved_amount'] !== 0.00 || (float)$c['net_amount'] !== 100.00) {
        $allZeroCut = false;
        break;
    }
}
runAssertion("Direct sponsor bonus has 0% reserve cutting (100% net credit)", $allZeroCut);

// TEST 4: 7-Level Matrix Commissions & Reserve Deduction
echo "\n5. Running Test 4: 7-Level Matrix Commissions & 25% Reserve Hold...\n";
// When Bob activated Tier 1 (₹500), Alice was Level 1 matrix parent.
// Level 1 payout = 12.50% of ₹500 = ₹62.50.
// Reserve deduction = 25% of ₹62.50 = ₹15.63.
// Net wallet credit = ₹62.50 - ₹15.63 = ₹46.87.
$matrixCommStmt = $pdo->prepare("
    SELECT * FROM commissions
    WHERE user_id = ? AND source_user_id = ? AND type = 'matrix_level' AND level = 1
");
$matrixCommStmt->execute([$userA['id'], $userB['id']]);
$mComm = $matrixCommStmt->fetch();
runAssertion("Matrix Level 1 commission recorded for Alice from Bob", (bool)$mComm);
runAssertion("Matrix Level 1 gross amount is ₹62.50", (float)$mComm['amount'] === 62.50);
runAssertion("Matrix Level 1 reserved amount is ₹15.63 (25%)", (float)$mComm['reserved_amount'] === 15.63);
runAssertion("Matrix Level 1 net wallet amount is ₹46.87 (75%)", (float)$mComm['net_amount'] === 46.87);

// TEST 4B: Matrix Level 2 Qualification Deduction (< 5 Direct Referrals)
echo "\n5B. Running Test 4B: Matrix Level 2 Qualification Deduction (< 5 Direct Referrals)...\n";
// Alice currently has 4 direct referrals (Bob, Charlie, Dave, Eve). This is < 5 direct referrals.
// When Eve activated Tier 1 (placed under Bob at depth 3), Bob is Level 1, Alice is Level 2.
// Level 2 gross payout for Tier 1 (₹500) = 10% = ₹50.00.
// Since Alice has 4 direct referrals (< 5), the unqualified deduction applies (default 50% = ₹25.00 reserved).
// Net wallet credit = ₹25.00.
$l2CommStmt = $pdo->prepare("
    SELECT * FROM commissions
    WHERE user_id = ? AND source_user_id = ? AND type = 'matrix_level' AND level = 2
");
$l2CommStmt->execute([$userA['id'], $userE['id']]);
$l2Comm = $l2CommStmt->fetch();
runAssertion("Matrix Level 2 commission recorded for Alice from Eve", (bool)$l2Comm);
runAssertion("Matrix Level 2 gross amount is ₹50.00", (float)$l2Comm['amount'] === 50.00);
runAssertion("Matrix Level 2 reserved amount is ₹25.00 (50% qualification deduction)", (float)$l2Comm['reserved_amount'] === 25.00);
runAssertion("Matrix Level 2 net wallet amount is ₹25.00 (50%)", (float)$l2Comm['net_amount'] === 25.00);

// Now sponsor 5th user under Alice to satisfy 5 direct referrals
$userF = AuthService::register([
    'username' => 'frank',
    'email' => 'frank@test.com',
    'password' => 'secret123',
    'full_name' => 'Frank Ocean',
    'phone' => '+919666666666',
    'sponsor' => 'alice'
]);
// Now Alice has 5 direct referrals!
// Place Frank under Bob at depth 3 (child 2 of Bob, Level 2 for Alice)
$resF = PackageService::purchasePackage($userF['id'], 1);

// When Frank activates, Alice is Level 2 ancestor with >= 5 direct referrals.
// Standard reserve rate (25%) should now apply instead of 50%.
// Gross: ₹50.00. Reserved: 25% of ₹50 = ₹12.50. Net: ₹37.50.
$l2CommQualifiedStmt = $pdo->prepare("
    SELECT * FROM commissions
    WHERE user_id = ? AND source_user_id = ? AND type = 'matrix_level' AND level = 2
");
$l2CommQualifiedStmt->execute([$userA['id'], $userF['id']]);
$l2CommQualified = $l2CommQualifiedStmt->fetch();
runAssertion("Matrix Level 2 with >= 5 direct referrals has standard 25% reserve hold (₹12.50)",
    (float)$l2CommQualified['reserved_amount'] === 12.50 && (float)$l2CommQualified['net_amount'] === 37.50
);

// TEST 5: Auto-Progression Engine (Tier 1 -> Tier 2 -> Tier 3)
echo "\n6. Running Test 5: Automated Package Progression Engine...\n";
// Let's grant Alice reserve pool balance to meet Tier 2 Basic price (₹1,000)
// Then trigger checkAndUpgradeUser
LedgerService::record(
    $userA['id'],
    'reserve_hold',
    1000.00,
    "Simulated matrix accumulation to reach Tier 2 (₹1,000)"
);

$upgrades = AutoProgressionEngine::checkAndUpgradeUser($userA['id']);
runAssertion("Alice triggered auto-progression to Tier 2", count($upgrades) >= 1 && $upgrades[0]['package']['id'] == 2);

// Verify Alice is in user_packages for Tier 2 as 'active'
$pkg2Stmt = $pdo->prepare("SELECT * FROM user_packages WHERE user_id = ? AND package_id = 2");
$pkg2Stmt->execute([$userA['id']]);
$userPkg2 = $pkg2Stmt->fetch();
runAssertion("Alice has active user_packages entry for Tier 2", $userPkg2 && $userPkg2['status'] === 'active');

// Verify Tier 1 status marked as 'upgraded'
$pkg1Stmt = $pdo->prepare("SELECT * FROM user_packages WHERE user_id = ? AND package_id = 1");
$pkg1Stmt->execute([$userA['id']]);
$userPkg1 = $pkg1Stmt->fetch();
runAssertion("Alice Tier 1 marked as 'upgraded'", $userPkg1 && $userPkg1['status'] === 'upgraded');

// Verify Alice placed in Tier 2 matrix tree as root
$node2Stmt = $pdo->prepare("SELECT * FROM matrix_nodes WHERE user_id = ? AND package_id = 2");
$node2Stmt->execute([$userA['id']]);
$userNode2 = $node2Stmt->fetch();
runAssertion("Alice placed in Tier 2 matrix tree", (bool)$userNode2 && $userNode2['depth'] === 1);

// Test Progression to Tier 3 Bronze (₹2,000 - New Package)
LedgerService::record(
    $userA['id'],
    'reserve_hold',
    2000.00,
    "Simulated reserve accumulation for Tier 3 Bronze (₹2,000)"
);
$upgradesTier3 = AutoProgressionEngine::checkAndUpgradeUser($userA['id']);
runAssertion("Alice triggered auto-progression to Tier 3 Bronze (₹2,000)", count($upgradesTier3) >= 1 && $upgradesTier3[0]['package']['id'] == 3);

$pkg3Stmt = $pdo->prepare("SELECT * FROM user_packages WHERE user_id = ? AND package_id = 3");
$pkg3Stmt->execute([$userA['id']]);
$userPkg3 = $pkg3Stmt->fetch();
runAssertion("Alice has active status for Tier 3 Bronze", $userPkg3 && $userPkg3['status'] === 'active');

echo "\n========================================================\n";
echo "   ALL BACKEND & SYSTEM TESTS COMPLETED SUCCESSFULLY!\n";
echo "========================================================\n";
