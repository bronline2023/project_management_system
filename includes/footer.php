<?php
/**
 * views/includes/footer.php
 *
 * This file contains the closing HTML tags, includes Bootstrap's JavaScript bundle,
 * and defines GLOBAL JavaScript functions, such as the custom confirmation modal
 * and message notification polling.
 * It's included at the end of every page that uses the main layout.
 *
 * This file MUST be the ONLY place where `showCustomConfirm` and `setupAutoHideAlerts`
 * and `startMessageNotificationPolling` are defined.
 * All other files should CALL these functions, not redefine them.
 */
?>
            </main> <!-- Closes the main content area if opened in header.php, adjust as per your header -->

            <!-- Bootstrap 5 JS (popper.js included) -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

            <!-- Custom Confirmation Modal HTML -->
            <div class="modal fade" id="customConfirmModal" tabindex="-1" aria-labelledby="customConfirmModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 shadow">
                        <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                            <h5 class="modal-title" id="customConfirmModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <p id="confirm-message" class="lead text-center"></p>
                        </div>
                        <div class="modal-footer border-0 rounded-bottom-4 justify-content-center">
                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                            <!-- The target of this button will be set by JavaScript to submit the hidden form -->
                            <button type="button" id="confirm-action-btn" class="btn btn-danger rounded-pill">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden Form for POST Deletion/Actions (Dynamically populated by JavaScript) -->
            <form id="deletionForm" action="<?= BASE_URL ?>index.php" method="POST" style="display:none;">
                <input type="hidden" name="page" id="deletionFormPage">
                <input type="hidden" name="action" id="deletionFormAction">
                <input type="hidden" name="id" id="deletionFormId">
                <!-- Any other dynamic fields can be added here if needed -->
            </form>

            <script>
                // Store the previous unread count to detect new messages
                let previousUnreadMessageCount = parseInt(document.getElementById('message-count-badge')?.textContent || '0');

                /**
                 * showCustomConfirm - Displays a custom Bootstrap confirmation modal.
                 * This function is designed to work with a hidden form to perform POST requests
                 * for actions like deletion, which is more secure and reliable than GET for modifications.
                 *
                 * @param {string} title - The title of the confirmation modal (e.g., "Delete Task").
                 * @param {string} message - The message to display inside the modal (e.g., "Are you sure...?").
                 * @param {string} targetPage - The 'page' parameter for the POST request (e.g., 'dashboard', 'users', 'clients').
                 * @param {string} targetAction - The 'action' parameter for the POST request (e.g., 'delete_task', 'delete', 'toggle_status').
                 * @param {string|number} targetId - The 'id' parameter for the item to be affected.
                 */
                function showCustomConfirm(title, message, targetPage, targetAction, targetId) {
                    // *** DEBUG: Log when function is called and its parameters ***
                    console.log(`showCustomConfirm called: Title="${title}", Message="${message}", Page="${targetPage}", Action="${targetAction}", ID="${targetId}"`);

                    const confirmModalElement = document.getElementById('customConfirmModal');
                    // Check if the modal element exists before proceeding
                    if (!confirmModalElement) {
                        console.error("ERROR JS: 'customConfirmModal' element not found! Ensure the modal HTML is correctly defined in footer.php.");
                        return; // Exit function if modal element is missing
                    }
                    const confirmModal = new bootstrap.Modal(confirmModalElement);
                    const confirmButton = document.getElementById('confirm-action-btn');

                    // Get references to the hidden form and its inputs
                    const deletionForm = document.getElementById('deletionForm');
                    const deletionFormPageInput = document.getElementById('deletionFormPage');
                    const deletionFormActionInput = document.getElementById('deletionFormAction');
                    const deletionFormIdInput = document.getElementById('deletionFormId');

                    // Check if necessary form elements exist
                    if (!deletionForm || !deletionFormPageInput || !deletionFormActionInput || !deletionFormIdInput) {
                        console.error("ERROR JS: One or more hidden form elements (deletionForm, deletionFormPage, deletionFormAction, deletionFormId) not found.");
                        return; // Exit if form elements are missing
                    }

                    // Set modal content
                    const modalTitleElement = document.getElementById('customConfirmModalLabel');
                    const confirmMessageElement = document.getElementById('confirm-message');
                    if (modalTitleElement) modalTitleElement.textContent = title;
                    if (confirmMessageElement) confirmMessageElement.textContent = message;

                    // Clear previous click listener to prevent multiple submissions
                    confirmButton.onclick = null;

                    // Set up the click listener for the 'Confirm' button
                    confirmButton.onclick = function() {
                        // Populate the hidden form fields
                        deletionFormPageInput.value = targetPage;
                        deletionFormActionInput.value = targetAction;
                        deletionFormIdInput.value = targetId;

                        // *** DEBUG: Log before form submission ***
                        console.log(`Submitting form for page=${deletionFormPageInput.value}, action=${deletionFormActionInput.value}, id=${deletionFormIdInput.value}`);

                        // Hide the modal immediately (or with slight delay)
                        confirmModal.hide();

                        // Submit the hidden form after a very short delay to allow modal to close visually
                        // This helps prevent issues where form submission might interrupt modal closing animation
                        setTimeout(() => {
                            deletionForm.submit();
                        }, 50); // 50ms delay
                    };

                    // Ensure the button styling is correct (e.g., for danger action)
                    confirmButton.classList.remove('btn-primary', 'btn-info', 'btn-success', 'btn-warning'); // Remove other styles
                    confirmButton.classList.add('btn-danger'); // Add the danger style for confirmation

                    // Show the modal
                    confirmModal.show();
                    console.log("DEBUG JS: Custom confirm modal shown.");
                }

                /**
                 * Global function for auto-hiding alerts/messages.
                 * This function should be called after a new alert message is displayed on the page.
                 */
                function setupAutoHideAlerts() {
                    console.log("DEBUG JS: setupAutoHideAlerts called.");
                    // Select all alert elements on the page that don't have the 'data-auto-hide-setup' attribute
                    document.querySelectorAll('.alert:not([data-auto-hide-setup])').forEach(alertElement => {
                        // Mark the alert as setup to prevent duplicate timers
                        alertElement.dataset.autoHideSetup = 'true';

                        setTimeout(function() {
                            alertElement.classList.add('fade-out');
                            // Remove the alert element from the DOM after the fade-out transition completes
                            alertElement.addEventListener('transitionend', function() {
                                alertElement.remove();
                                console.log("DEBUG JS: Alert removed after fade-out.");
                            }, { once: true }); // Ensure the event listener runs only once
                        }, 5000); // 5 seconds
                    });
                     // If no new alerts found, log that
                    if (document.querySelectorAll('.alert:not([data-auto-hide-setup])').length === 0) {
                        console.log("DEBUG JS: No new alert elements found to auto-hide.");
                    }
                }

                /**
                 * Starts polling for unread messages and updates the sidebar notification badge.
                 * Applies a blinking effect if new unread messages are detected.
                 */
                function startMessageNotificationPolling() {
                    const badge = document.getElementById('message-count-badge');
                    if (!badge) {
                        console.warn("WARN JS: Message count badge element not found. Notification polling will not run.");
                        return; // Exit if badge element doesn't exist
                    }

                    // Poll every 10 seconds (adjust as needed)
                    setInterval(async () => {
                        try {
                            const response = await fetch('<?= BASE_URL ?>models/fetch_unread_messages.php');
                            const data = await response.json();

                            if (data.success) {
                                const newUnreadCount = parseInt(data.unread_count);
                                console.log(`DEBUG JS: Polling - New unread message count: ${newUnreadCount}, Previous: ${previousUnreadMessageCount}`);

                                // Update the badge text
                                badge.textContent = newUnreadCount;

                                if (newUnreadCount > 0) {
                                    // Show the badge
                                    badge.style.display = 'inline-block';

                                    // Apply blinking effect if count increased or became > 0 from 0
                                    if (newUnreadCount > previousUnreadMessageCount || (previousUnreadMessageCount === 0 && newUnreadCount > 0)) {
                                        console.log("DEBUG JS: Applying blink effect due to new unread messages.");
                                        badge.classList.add('blink-animation');
                                        // Remove blink class after a short period (e.g., 2 seconds) to make it blink for a bit
                                        setTimeout(() => {
                                            badge.classList.remove('blink-animation');
                                        }, 2000); // Blink for 2 seconds
                                    }
                                } else {
                                    // Hide the badge if no unread messages
                                    badge.style.display = 'none';
                                    badge.classList.remove('blink-animation'); // Ensure no lingering animation
                                }

                                // Update previous count for next comparison
                                previousUnreadMessageCount = newUnreadCount;

                            } else {
                                console.error("ERROR JS: Failed to fetch unread message count:", data.message);
                                // Optionally hide badge or show error
                                badge.style.display = 'none';
                                badge.classList.remove('blink-animation');
                            }
                        } catch (error) {
                            console.error("ERROR JS: Error during message polling:", error);
                            // Hide badge on network errors
                            badge.style.display = 'none';
                            badge.classList.remove('blink-animation');
                        }
                    }, 10000); // Poll every 10 seconds (10000 milliseconds)
                }


                // Call setupAutoHideAlerts and startMessageNotificationPolling on page load
                document.addEventListener('DOMContentLoaded', function() {
                    // Check if any message box element exists on the page on initial load
                    const messageBoxExists = document.querySelector('.alert');
                    if (messageBoxExists) {
                        setupAutoHideAlerts();
                    }

                    // Start message notification polling only if a user is logged in
                    // This is assumed by the presence of the sidebar, but you can add more explicit checks if needed
                    startMessageNotificationPolling();
                });
            </script>
            <style>
                /* Custom CSS for fade-out alert */
                .alert.fade-out {
                    opacity: 0;
                    transition: opacity 0.5s ease-out;
                }

                /* CSS for Blinking Notification Badge */
                .blink-animation {
                    animation: blink-effect 1s step-end infinite; /* Blink indefinitely */
                    background-color: #dc3545 !important; /* Ensure it's red */
                    color: white !important; /* Ensure text is white */
                }

                @keyframes blink-effect {
                    50% {
                        opacity: 0;
                    }
                }

                /* Ensure the badge itself is styled correctly even without blinking */
                .badge.bg-danger {
                    min-width: 25px; /* Give it a minimum width for roundness */
                    height: 25px; /* Make it circular */
                    display: flex; /* Use flexbox for centering content */
                    align-items: center;
                    justify-content: center;
                    font-size: 0.8rem; /* Adjust font size */
                    transition: all 0.3s ease-in-out; /* Smooth transitions for appearance */
                }
            </style>
        </body>
    </html>
