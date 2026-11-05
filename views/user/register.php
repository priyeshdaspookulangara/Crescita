<?php
// views/user/register.php
$pageTitle = 'Register - Pinnacle Hunt';
$sponsorPrefill = $_GET['ref'] ?? '';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="w-full max-w-lg p-8 glass-panel rounded-2xl border border-white/10 shadow-2xl relative overflow-hidden">
    <!-- Glow Accent -->
    <div class="absolute -top-24 -right-24 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-white">Join Pinnacle Hunt</h2>
        <p class="text-sm text-gray-400 mt-1">Experience 7-Level Company-Wide Forced Spillover</p>
    </div>

    <form method="POST" action="?action=register" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div>
            <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1.5">Sponsor Username / ID</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-emerald-400">
                    <i data-lucide="award" class="w-4 h-4"></i>
                </span>
                <input type="text" name="sponsor" value="<?= htmlspecialchars($sponsorPrefill) ?>"
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/[0.04] border border-emerald-500/30 focus:border-emerald-500/60 focus:ring-1 focus:ring-emerald-500 text-emerald-300 placeholder-gray-500 text-sm outline-none transition"
                    placeholder="Enter sponsor code (Optional: defaults to system)">
            </div>
            <?php if (!empty($sponsorPrefill)): ?>
                <p class="text-[11px] text-emerald-400 mt-1">Invited by sponsor: <strong><?= htmlspecialchars($sponsorPrefill) ?></strong></p>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1.5">Full Name</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="full_name" required
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-emerald-500/60 focus:ring-1 focus:ring-emerald-500 text-white placeholder-gray-500 text-sm outline-none transition"
                        placeholder="John Doe">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1.5">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="at-sign" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="username" required
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-emerald-500/60 focus:ring-1 focus:ring-emerald-500 text-white placeholder-gray-500 text-sm outline-none transition"
                        placeholder="johndoe77">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1.5">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                    </span>
                    <input type="email" name="email" required
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-emerald-500/60 focus:ring-1 focus:ring-emerald-500 text-white placeholder-gray-500 text-sm outline-none transition"
                        placeholder="user@example.com">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1.5">Phone Number</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="phone" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="phone" required
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-emerald-500/60 focus:ring-1 focus:ring-emerald-500 text-white placeholder-gray-500 text-sm outline-none transition"
                        placeholder="+919876543210">
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1.5">Password</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </span>
                <input type="password" name="password" required minlength="6"
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-emerald-500/60 focus:ring-1 focus:ring-emerald-500 text-white placeholder-gray-500 text-sm outline-none transition"
                    placeholder="Min 6 characters">
            </div>
        </div>

        <button type="submit" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-400 hover:to-cyan-400 font-bold text-slate-950 text-sm shadow-lg shadow-emerald-500/25 transition flex items-center justify-center space-x-2 mt-2">
            <span>Register & Start Matrix</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </button>
    </form>

    <div class="mt-6 pt-6 border-t border-white/10 text-center text-sm text-gray-400">
        Already registered?
        <a href="?page=login" class="text-emerald-400 hover:text-emerald-300 font-semibold underline underline-offset-4 ml-1">Log in here</a>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
