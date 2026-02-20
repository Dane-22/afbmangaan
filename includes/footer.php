            </div><!-- End content-wrapper -->
        </main><!-- End main-content -->
    </div><!-- End app-container -->

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <span>Loading...</span>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/theme_handler.js"></script>
    <script src="assets/js/attendance_ajax.js"></script>
    <script src="assets/js/dashboard_charts.js"></script>
    
    <script>
        // Initialize sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            function openSidebar() {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            
            function closeSidebar() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            if (menuToggle) menuToggle.addEventListener('click', openSidebar);
            if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
            
            // User dropdown
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            
            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdownMenu.classList.toggle('active');
                });
                
                document.addEventListener('click', function() {
                    userDropdownMenu.classList.remove('active');
                });
            }
            
            // Toast notification system
            window.showToast = function(message, type = 'info') {
                const container = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                toast.className = `toast toast-${type} animate__animated animate__fadeInRight`;
                
                const icon = type === 'success' ? 'ph-check-circle' : 
                            type === 'error' ? 'ph-x-circle' : 
                            type === 'warning' ? 'ph-warning' : 'ph-info';
                
                toast.innerHTML = `
                    <i class="ph ${icon}"></i>
                    <span>${message}</span>
                `;
                
                container.appendChild(toast);
                
                setTimeout(() => {
                    toast.classList.remove('animate__fadeInRight');
                    toast.classList.add('animate__fadeOutRight');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            };
            
            // Loading overlay
            window.showLoading = function(show = true) {
                const overlay = document.getElementById('loadingOverlay');
                if (show) {
                    overlay.classList.add('active');
                } else {
                    overlay.classList.remove('active');
                }
            };
            
            // Handle URL parameters for notifications
            const urlParams = new URLSearchParams(window.location.search);
            const success = urlParams.get('success');
            const error = urlParams.get('error');
            
            if (success) showToast(decodeURIComponent(success), 'success');
            if (error) showToast(decodeURIComponent(error), 'error');
        });
    </script>
    
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
