<?php
// views/user/login.php
$pageTitle = 'Login - Pinnacle Hunt';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="w-full max-w-md p-8 glass-panel rounded-2xl border border-white/10 shadow-2xl relative overflow-hidden">
    <!-- Glow Accent -->
    <div class="absolute -top-24 -right-24 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-white">Welcome Back</h2>
        <p class="text-sm text-gray-400 mt-1">Access your 3-Matrix investment dashboard</p>
    </div>

    <form method="POST" action="?action=login" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div>
            <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Username or Email</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </span>
                <input type="text" name="username_or_email" required
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-emerald-500/60 focus:ring-1 focus:ring-emerald-500 text-white placeholder-gray-500 text-sm outline-none transition"
                    placeholder="e.g. member1 or user@example.com">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Password</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </span>
                <input type="password" name="password" required
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 focus:border-emerald-500/60 focus:ring-1 focus:ring-emerald-500 text-white placeholder-gray-500 text-sm outline-none transition"
                    placeholder="••••••••">
            </div>
        </div>

        <button type="submit" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-400 hover:to-cyan-400 font-bold text-slate-950 text-sm shadow-lg shadow-emerald-500/25 transition flex items-center justify-center space-x-2">
            <span>Sign In to Dashboard</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </button>
    </form>

    <div class="mt-6 pt-6 border-t border-white/10 text-center text-sm text-gray-400">
        Don't have an account?
        <a href="?page=register" class="text-emerald-400 hover:text-emerald-300 font-semibold underline underline-offset-4 ml-1">Create Account</a>
    </div>

    <!-- Quick test credentials hint -->
    <div class="mt-4 p-3 rounded-xl bg-white/[0.02] border border-white/5 text-xs text-gray-400">
        <span class="text-gray-300 font-semibold">Admin demo:</span> username: <code class="text-cyan-300">admin</code> / password: <code class="text-cyan-300">Admin@123456</code>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
