        </main>
    </div>

    <script>
        // Lógica simples para o menu mobile
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('mobile-menu-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if(btn && sidebar) {
                btn.addEventListener('click', () => {
                    sidebar.classList.toggle('-translate-x-full');
                    if(overlay) overlay.classList.toggle('hidden');
                });
            }

            if(overlay) {
                overlay.addEventListener('click', () => {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>