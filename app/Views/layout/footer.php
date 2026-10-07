            <footer class="px-4 py-4 text-center text-xs text-gray-400">
                <a href="<?= BASE_URL ?>/auth/privacidade" class="hover:text-corpBlue-700 underline">Política de Privacidade (LGPD)</a>
            </footer>
        </main>
    </div>

    <div id="toast-container" class="fixed top-4 right-4 left-4 sm:left-auto sm:w-96 z-[100] space-y-2 pointer-events-none" aria-live="polite"></div>

    <script src="<?= BASE_URL ?>/assets/js/ui.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/budgets.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('mobile-menu-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            const toggleMenu = (open) => {
                if (!sidebar) return;
                sidebar.classList.toggle('-translate-x-full', !open);
                if (overlay) overlay.classList.toggle('hidden', !open);
            };

            if (btn) btn.addEventListener('click', () => toggleMenu(sidebar.classList.contains('-translate-x-full')));
            if (overlay) overlay.addEventListener('click', () => toggleMenu(false));
        });
    </script>
</body>
</html>
