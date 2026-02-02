<?php
/**
 * views/includes/footer.php
 * FINAL VERSION: Handles UI Logic & Notifications
 */
?>
            </div> 
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1150">
            <div id="notificationToastContainer"></div>
        </div>

        <div class="modal fade" id="customConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 shadow">
                    <div class="modal-header border-0 text-center pb-0">
                        <h4 class="modal-title w-100">Confirmation</h4>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <p id="confirm-message" class="lead"></p>
                    </div>
                    <div class="modal-footer flex-nowrap p-0">
                        <a href="#" id="confirm-link" class="btn btn-lg btn-link fs-6 text-decoration-none col-6 m-0 rounded-0 border-end"><strong>Yes</strong></a>
                        <button type="button" class="btn btn-lg btn-link fs-6 text-decoration-none col-6 m-0 rounded-0" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // --- 1. CLOCK LOGIC ---
            function updateClock() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', { 
                    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true 
                });
                
                // Desktop Clock
                const deskClock = document.getElementById('headerClock');
                if(deskClock) deskClock.innerHTML = '<i class="far fa-clock"></i> ' + timeString;

                // Mobile Clock
                const mobClock = document.getElementById('mobileClockText');
                if(mobClock) mobClock.innerText = timeString;
            }
            setInterval(updateClock, 1000);
            updateClock();

            // --- 2. MOBILE SIDEBAR TOGGLE ---
            document.addEventListener('DOMContentLoaded', function () {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.overlay');
                const sidebarBtn = document.getElementById('sidebarCollapse'); // Navbar Toggle Btn
                const dismissBtn = document.getElementById('dismiss'); // Sidebar Close Btn

                // Open Sidebar
                if (sidebarBtn) {
                    sidebarBtn.addEventListener('click', function () {
                        sidebar.classList.add('active');
                        if(overlay) overlay.classList.add('active');
                    });
                }

                // Close Sidebar
                function closeSidebar() {
                    sidebar.classList.remove('active');
                    if(overlay) overlay.classList.remove('active');
                }

                if (dismissBtn) dismissBtn.addEventListener('click', closeSidebar);
                if (overlay) overlay.addEventListener('click', closeSidebar);
                
                // Tooltips Init
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                });
            });

            // --- 3. GLOBAL NOTIFICATION POLLING ---
            let originalTitle = document.title; 

            function checkNotifications() {
                // Call API
                fetch('app/chat_api.php?action=get_total_unread')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        updateNotificationUI(data.count);
                    }
                })
                .catch(err => { 
                    // Silent fail for polling errors
                });
            }

            function updateNotificationUI(count) {
                const badge = document.getElementById('sidebar-badge');
                
                if (count > 0) {
                    // Show Badge
                    if (badge) {
                        badge.style.display = 'inline-block';
                        badge.innerText = count;
                    }
                    // Update Title
                    document.title = `(${count}) New Message! | ` + originalTitle;
                } else {
                    // Hide Badge
                    if (badge) badge.style.display = 'none';
                    document.title = originalTitle;
                }
            }

            // Check every 3 seconds
            setInterval(checkNotifications, 3000);
            checkNotifications(); // Immediate check

            // --- 4. CUSTOM CONFIRM MODAL ---
            function showCustomConfirm(title, message, link) {
                const confirmModalEl = document.getElementById('customConfirmModal');
                const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
                
                document.querySelector('#customConfirmModal .modal-title').innerText = title;
                document.getElementById('confirm-message').innerHTML = message;
                const confirmLink = document.getElementById('confirm-link');
                confirmLink.href = link;

                // Color Logic
                if (title.toLowerCase().includes('delete') || title.toLowerCase().includes('reject')) {
                    confirmLink.className = 'btn btn-lg btn-link fs-6 text-decoration-none col-6 m-0 rounded-0 border-end text-danger';
                } else {
                    confirmLink.className = 'btn btn-lg btn-link fs-6 text-decoration-none col-6 m-0 rounded-0 border-end text-primary';
                }
                confirmModal.show();
            }
        </script>
    </body>
</html>