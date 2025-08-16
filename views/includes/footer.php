<?php
/**
 * views/includes/footer.php
 *
 * This file contains the closing </body> and </html> tags.
 * It also includes all necessary JavaScript files (Bootstrap bundle, custom JS).
 * This file should be included at the very end of every HTML page.
 */

// Ensure ROOT_PATH is defined and config.php is included.
// This file is in views/includes, so ROOT_PATH is two directories up.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR);
}
require_once ROOT_PATH . 'config.php';

?>
    <!-- Bootstrap Bundle with Popper (v5.3.3) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Custom JavaScript for sidebar toggle and alerts -->
    <script src="<?= BASE_URL ?>public/js/main.js"></script>

    <script>
        // Function to set up auto-hiding alerts
        function setupAutoHideAlerts() {
            const alertElement = document.querySelector('.alert.fade.show');
            if (alertElement) {
                setTimeout(function() {
                    // Try to get Bootstrap's Alert instance
                    const bootstrapAlert = bootstrap.Alert.getInstance(alertElement);
                    if (bootstrapAlert) {
                        bootstrapAlert.close(); // Use Bootstrap's close method
                    } else {
                        // Fallback for alerts not initialized by Bootstrap JS
                        alertElement.classList.add('fade-out');
                        setTimeout(() => alertElement.remove(), 500);
                    }
                }, 5000); // 5 seconds
            }
        }

        // Call the function on DOMContentLoaded to apply to any alerts present on page load
        document.addEventListener('DOMContentLoaded', function() {
            setupAutoHideAlerts();

            // Sidebar toggle functionality
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });

            // Initialize tooltips (if any are present on the page)
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });
    </script>
</body>
</html>
