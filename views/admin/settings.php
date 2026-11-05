<?php
// views/admin/settings.php
use PinnacleHunt\Services\SettingsService;
use PinnacleHunt\Services\PackageService;

$pageTitle = 'Commission & Progression Settings - Admin';
require_once __DIR__ . '/../layouts/header.php';

$settings = SettingsService::getAll();
$packages = PackageService::getAll();
?>

<div class="mb-8">
    <h2 class="text-2xl font-black text-white">Commission & Auto-Progression Configuration</h2>
    <p class="text-xs text-gray-400 mt-1">Dynamically adjust company-wide matrix percentages, reserve pool deduction, and package tiers.</p>
</div>

<form method="POST" action="?action=save_settings" class="space-y-8">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <!-- Global Deduction & Direct Sponsor Engine -->
    <div class="glass-panel p-6 rounded-2xl border border-white/10">
        <h3 class="text-lg font-bold text-white mb-1 flex items-center space-x-2">
            <i data-lucide="sliders" class="w-5 h-5 text-emerald-400"></i>
            <span>Global Rates & Auto-Upgrade Reserve</span>
        </h3>
        <p class="text-xs text-gray-400 mb-6">Matrix income is split between Withdrawable Wallet and Reserve Pool. Direct Sponsor commissions have ZERO cutting.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                    Reserve Pool Rate (%)
                </label>
                <div class="relative">
                    <input type="number" step="0.01" min="0" max="100" name="reserve_deduction_rate"
                        value="<?= htmlspecialchars(SettingsService::get('reserve_deduction_rate', '25.00')) ?>" required
                        class="w-full px-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-cyan-500 text-cyan-300 font-mono text-sm outline-none">
                    <span class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-cyan-400 font-bold">%</span>
                </div>
                <p class="text-[11px] text-gray-400 mt-1">Default 25.00% standard matrix deduction.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                    Direct Sponsor Bonus (%)
                </label>
                <div class="relative">
                    <input type="number" step="0.01" min="0" max="100" name="direct_sponsor_percent"
                        value="<?= htmlspecialchars(SettingsService::get('direct_sponsor_percent', '20.00')) ?>" required
                        class="w-full px-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-emerald-500 text-emerald-300 font-mono text-sm outline-none">
                    <span class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-emerald-400 font-bold">%</span>
                </div>
                <p class="text-[11px] text-emerald-400 mt-1">Zero Cutting: 100% net credit to wallet.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                    Required Direct Referrals
                </label>
                <div class="relative">
                    <input type="number" min="0" max="50" name="direct_referrals_required_for_upgrade"
                        value="<?= htmlspecialchars(SettingsService::get('direct_referrals_required_for_upgrade', '5')) ?>" required
                        class="w-full px-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-purple-500 text-purple-300 font-mono text-sm outline-none">
                    <span class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-purple-400 font-bold">refs</span>
                </div>
                <p class="text-[11px] text-gray-400 mt-1">Target direct referrals (5) to qualify without deduction.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                    Level 2 Unqualified Deduction (%)
                </label>
                <div class="relative">
                    <input type="number" step="0.01" min="0" max="100" name="level_2_unqualified_deduction_percent"
                        value="<?= htmlspecialchars(SettingsService::get('level_2_unqualified_deduction_percent', '50.00')) ?>" required
                        class="w-full px-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-amber-500 text-amber-300 font-mono text-sm outline-none">
                    <span class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-amber-400 font-bold">%</span>
                </div>
                <p class="text-[11px] text-amber-400 mt-1">Deducted from Matrix L2 if direct referrals &lt; 5.</p>
            </div>
        </div>
    </div>

    <!-- 7-Level Matrix Dynamic Percentages -->
    <div class="glass-panel p-6 rounded-2xl border border-white/10">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-bold text-white flex items-center space-x-2">
                <i data-lucide="layers" class="w-5 h-5 text-cyan-400"></i>
                <span>7-Level Matrix Commission Payouts (Total 40.00%)</span>
            </h3>
            <span class="text-xs font-mono px-3 py-1 rounded-full bg-cyan-500/10 text-cyan-300 border border-cyan-500/20">
                Sum Target: 40.00%
            </span>
        </div>
        <p class="text-xs text-gray-400 mb-6">Percentage of package price distributed to upline matrix parents up to 7 levels.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <?php
            $defaultRates = [
                1 => '12.50',
                2 => '10.00',
                3 => '7.50',
                4 => '5.00',
                5 => '2.50',
                6 => '1.25',
                7 => '1.25'
            ];
            $nodeCapacities = [1 => 3, 2 => 9, 3 => 27, 4 => 81, 5 => 243, 6 => 729, 7 => 2187];
            ?>
            <?php for ($i = 1; $i <= 7; $i++): ?>
                <div class="p-4 rounded-xl bg-white/[0.02] border border-white/5">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-bold text-gray-300">Level <?= $i ?></span>
                        <span class="text-[10px] text-gray-400 font-mono"><?= $nodeCapacities[$i] ?> nodes</span>
                    </div>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" max="100" name="matrix_level_<?= $i ?>_percent"
                            value="<?= htmlspecialchars(SettingsService::get("matrix_level_{$i}_percent", $defaultRates[$i])) ?>" required
                            class="w-full px-3 py-2 rounded-lg bg-white/[0.04] border border-white/10 text-white font-mono text-xs outline-none focus:border-cyan-500">
                        <span class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-gray-400 text-xs">%</span>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Package Tier Configuration Table -->
    <div class="glass-panel p-6 rounded-2xl border border-white/10">
        <h3 class="text-lg font-bold text-white mb-2 flex items-center space-x-2">
            <i data-lucide="package" class="w-5 h-5 text-purple-400"></i>
            <span>Package Tier Pricing & Direct Commission Configuration</span>
        </h3>
        <p class="text-xs text-gray-400 mb-6">Manage all 10 investment package tiers including the new ₹2,000 and ₹4,000 tiers.</p>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-300">
                <thead class="text-xs text-gray-400 uppercase bg-white/[0.02] border-b border-white/5">
                    <tr>
                        <th class="py-3 px-4">Tier Order</th>
                        <th class="py-3 px-4">Package Name</th>
                        <th class="py-3 px-4">Price (₹)</th>
                        <th class="py-3 px-4">Direct Bonus (%)</th>
                        <th class="py-3 px-4">Calculated Direct (₹)</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 font-mono text-xs">
                    <?php foreach ($packages as $pkg): ?>
                        <tr>
                            <td class="py-3 px-4 text-gray-400">#<?= $pkg['sort_order'] ?></td>
                            <td class="py-3 px-4 font-sans font-bold text-white">
                                <input type="text" name="package_name[<?= $pkg['id'] ?>]" value="<?= htmlspecialchars($pkg['name']) ?>" required
                                    class="px-2.5 py-1.5 rounded-lg bg-white/[0.04] border border-white/10 text-white font-sans text-xs outline-none w-48">
                            </td>
                            <td class="py-3 px-4">
                                <input type="number" step="0.01" min="1" name="package_price[<?= $pkg['id'] ?>]" value="<?= (float)$pkg['price'] ?>" required
                                    class="px-2.5 py-1.5 rounded-lg bg-white/[0.04] border border-white/10 text-cyan-300 font-mono text-xs outline-none w-28">
                            </td>
                            <td class="py-3 px-4">
                                <input type="number" step="0.01" min="0" max="100" name="package_direct[<?= $pkg['id'] ?>]" value="<?= (float)$pkg['direct_commission_percent'] ?>" required
                                    class="px-2.5 py-1.5 rounded-lg bg-white/[0.04] border border-white/10 text-emerald-300 font-mono text-xs outline-none w-20"> %
                            </td>
                            <td class="py-3 px-4 text-emerald-400 font-bold">
                                ₹<?= number_format(((float)$pkg['price'] * (float)$pkg['direct_commission_percent']) / 100, 2) ?>
                            </td>
                            <td class="py-3 px-4 font-sans">
                                <select name="package_status[<?= $pkg['id'] ?>]" class="px-2 py-1 rounded-lg bg-gray-900 border border-white/10 text-xs">
                                    <option value="active" <?= $pkg['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $pkg['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <button type="submit" class="py-3 px-8 rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-400 hover:to-cyan-400 text-slate-950 font-bold text-sm shadow-xl shadow-emerald-500/20 transition flex items-center space-x-2">
            <i data-lucide="save" class="w-4 h-4"></i>
            <span>Save Configuration Changes</span>
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
