<?php
/**
 * views/components/message_box.php
 *
 * This file provides a reusable HTML component for displaying alert messages.
 * It expects a PHP variable `$message` to be set in the calling script,
 * which should contain the full HTML of a Bootstrap alert.
 * e.g., $message = '<div class="alert alert-success">...</div>';
 */

// This component relies on the parent script for the $message variable.
if (isset($message) && !empty($message)):
?>
<div class="message-container mb-3">
    <?= $message ?>
</div>

<script>
    // This script can be part of a global JS file (like script.js or main.js)
    // but is included here to ensure it works even if the global file is missed.
    document.addEventListener('DOMContentLoaded', function() {
        const alertElement = document.querySelector('.message-container .alert');
        if (alertElement) {
            setTimeout(() => {
                // Use Bootstrap's built-in close method if available
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
                if (bsAlert) {
                    bsAlert.close();
                } else {
                    // Fallback for manual removal
                    alertElement.style.transition = 'opacity 0.5s ease-out';
                    alertElement.style.opacity = '0';
                    setTimeout(() => alertElement.remove(), 500);
                }
            }, 5000); // Alert will disappear after 5 seconds
        }
    });
</script>

<?php endif; ?>