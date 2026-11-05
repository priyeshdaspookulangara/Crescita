<?php
// views/layouts/footer.php
use PinnacleHunt\Services\AuthService;
$user = AuthService::getCurrentUser();
?>
<?php if ($user): ?>
        </main>

        <!-- Footer Bar -->
        <footer class="glass-panel border-t border-white/5 py-4 px-6 text-center text-xs text-gray-500">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 max-w-7xl mx-auto">
                <div>
                    &copy; <?= date('Y') ?> <span class="text-white font-semibold">Crescita</span>. 7-Level BFS 3-Matrix Forced Spillover System.
                </div>
                <div class="flex items-center space-x-4 text-gray-400">
                    <span class="inline-flex items-center text-emerald-400"><span class="w-2 h-2 rounded-full bg-emerald-400 inline-block mr-1.5 animate-ping"></span> Live Node Network</span>
                    <span>Direct: 20% Zero-Cut</span>
                    <span>Matrix: 40% (7 Lvl)</span>
                </div>
            </div>
        </footer>
    </div>
<?php else: ?>
    </div>
<?php endif; ?>

<script>
    // Initialize Lucide Icons
    lucide.createIcons();

    // Copy Join Link Helper
    function copyJoinLink(link) {
        navigator.clipboard.writeText(link).then(() => {
            const btnText = document.getElementById('copyBtnText');
            if (btnText) {
                const original = btnText.innerText;
                btnText.innerText = 'Copied!';
                setTimeout(() => {
                    btnText.innerText = original;
                }, 2000);
            }
        }).catch(err => {
            console.error('Failed to copy link: ', err);
        });
    }

    // Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const closeMobileMenu = document.getElementById('closeMobileMenu');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.remove('-translate-x-full');
        });
    }

    if (closeMobileMenu && sidebar) {
        closeMobileMenu.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
        });
    }
</script>
</body>
</html>
