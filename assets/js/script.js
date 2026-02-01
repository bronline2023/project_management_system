/**
 * assets/js/script.js
 * This file contains the corrected and simplified JavaScript for global functionalities.
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');

    // --- Sidebar Toggle Logic for Mobile ---
    if (sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', function () {
            // This single line correctly toggles the sidebar on mobile.
            sidebar.classList.toggle('active');
        });
    }

    // --- Global Message Auto-Hide for Alerts ---
    window.setupAutoHideAlerts = function() {
        document.querySelectorAll('.alert:not([data-auto-hide-setup])').forEach(alertElement => {
            alertElement.dataset.autoHideSetup = 'true';
            setTimeout(function() {
                alertElement.classList.add('fade-out');
                alertElement.addEventListener('transitionend', function() {
                    alertElement.remove();
                }, { once: true });
            }, 5000); // 5 seconds
        });
    }

    // Initial call to handle alerts on page load
    setupAutoHideAlerts();
});