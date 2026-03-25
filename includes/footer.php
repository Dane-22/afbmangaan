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
            console.log('DOMContentLoaded fired at', new Date().toISOString());
            
            // Check if elements exist BEFORE getting them
            console.log('menuToggle exists:', !!document.getElementById('menuToggle'));
            console.log('sidebar exists:', !!document.getElementById('sidebar'));
            console.log('sidebarClose exists:', !!document.getElementById('sidebarClose'));
            console.log('sidebarOverlay exists:', !!document.getElementById('sidebarOverlay'));
            
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            console.log('Elements found:', {
                menuToggle: menuToggle?.tagName || 'null',
                sidebar: sidebar?.tagName || 'null',
                sidebarClose: sidebarClose?.tagName || 'null',
                sidebarOverlay: sidebarOverlay?.tagName || 'null'
            });
            
            function openSidebar() {
                console.log('Opening sidebar');
                if (sidebar) {
                    sidebar.classList.add('active');
                    console.log('Sidebar classes after open:', sidebar.className);
                    console.log('Sidebar computed display:', getComputedStyle(sidebar).display);
                    console.log('Sidebar computed visibility:', getComputedStyle(sidebar).visibility);
                    console.log('Sidebar computed zIndex:', getComputedStyle(sidebar).zIndex);
                    console.log('Sidebar computed position:', getComputedStyle(sidebar).position);
                    console.log('Sidebar computed left:', getComputedStyle(sidebar).left);
                }
                if (sidebarOverlay) sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            
            function closeSidebar() {
                console.log('Closing sidebar');
                if (sidebar) sidebar.classList.remove('active');
                if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            if (menuToggle) {
                console.log('Adding click listener to menuToggle');
                menuToggle.addEventListener('click', function(e) {
                    console.log('menuToggle clicked!');
                    e.preventDefault();
                    e.stopPropagation();
                    openSidebar();
                });
            } else {
                console.error('menuToggle not found!');
            }
            
            if (sidebarClose) {
                sidebarClose.addEventListener('click', closeSidebar);
            }
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }
            
            // User dropdown
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            
            console.log('User dropdown elements:', {
                userDropdownBtn: userDropdownBtn?.tagName || 'null',
                userDropdownMenu: userDropdownMenu?.tagName || 'null'
            });
            
            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function(e) {
                    console.log('User dropdown clicked');
                    e.stopPropagation();
                    userDropdownMenu.classList.toggle('active');
                });
                
                document.addEventListener('click', function() {
                    userDropdownMenu.classList.remove('active');
                });
            } else {
                console.error('User dropdown elements not found!');
            }
            
            console.log('Footer initialization complete');
            
            // DEBUG: Check if loading overlay is blocking clicks
            const loadingOverlay = document.getElementById('loadingOverlay');
            console.log('Loading overlay:', loadingOverlay ? {
                hasActiveClass: loadingOverlay.classList.contains('active'),
                opacity: getComputedStyle(loadingOverlay).opacity,
                visibility: getComputedStyle(loadingOverlay).visibility,
                display: getComputedStyle(loadingOverlay).display,
                zIndex: getComputedStyle(loadingOverlay).zIndex
            } : 'null');
            
            // Force remove active class from loading overlay if present
            if (loadingOverlay && loadingOverlay.classList.contains('active')) {
                console.warn('Loading overlay was active - removing it!');
                loadingOverlay.classList.remove('active');
            }
            
            // Fix toast container blocking clicks when empty
            const toastContainer = document.getElementById('toastContainer');
            if (toastContainer) {
                toastContainer.style.pointerEvents = 'none';
                // Enable pointer events when toasts are added
                const observer = new MutationObserver(function(mutations) {
                    const hasToasts = toastContainer.querySelector('.toast') !== null;
                    toastContainer.style.pointerEvents = hasToasts ? 'auto' : 'none';
                });
                observer.observe(toastContainer, { childList: true, subtree: true });
            }
        });
    </script>
    
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
