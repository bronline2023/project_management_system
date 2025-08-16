<?php
/**
 * views/components/message_box.php
 *
 * This file provides a reusable HTML component for displaying alert messages.
 * It expects a PHP variable `$message` to be set in the calling script,
 * which should contain the full HTML of a Bootstrap alert (e.g., `<div class="alert alert-success">...</div>`).
 *
 * This component helps centralize message display and styling.
 */

// This file is a component and typically does not need its own direct access checks
// or database connections, as it relies on the parent script for data and context.

// The `$message` variable should be passed from the parent scope where this file is included.
// Example usage in a parent file:
// $message = '<div class="alert alert-success" role="alert">Action completed successfully!</div>';
// include VIEWS_PATH . 'components/message_box.php';

if (isset($message) && !empty($message)):
?>
<div class="message-container mb-3">
    <!-- The $message variable is expected to contain the full HTML of a Bootstrap alert -->
    <?= $message ?>
</div>
<?php endif; ?>

<style>
    /* Add a basic style for the message container if needed, though Bootstrap alerts handle most styling */
    .message-container {
        /* Optional: Add some padding or margin if default Bootstrap spacing isn't enough */
        /* padding: 10px; */
    }

    /* Styles for the fade-out effect, to be consistent with how alerts are hidden via JS */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
</style>
