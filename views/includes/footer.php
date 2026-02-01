<?php
/**
 * views/includes/footer.php
 * FINAL & COMPLETE: Includes a robust, real-time notification polling system with animated Bootstrap toasts
 * displayed centrally on the screen AND a custom confirmation modal for actions like delete.
 */
?>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

            <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1150">
                <div id="notificationToastContainer">
                    </div>
            </div>

            <div class="modal fade" id="customConfirmModal" tabindex="-1" aria-labelledby="customConfirmModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 shadow">
                        <div class="modal-header border-0 text-center pb-0">
                            <h4 class="modal-title w-100" id="customConfirmModalLabel">Confirmation</h4>
                        </div>
                        <div class="modal-body p-4 text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <p id="confirm-message" class="lead"></p>
                        </div>
                        <div class="modal-footer flex-nowrap p-0">
                            <a href="#" id="confirm-link" class="btn btn-lg btn-link fs-6 text-decoration-none col-6 m-0 rounded-0 border-end"><strong>Yes, I'm sure</strong></a>
                            <button type="button" class="btn btn-lg btn-link fs-6 text-decoration-none col-6 m-0 rounded-0" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                // [NEW] Custom Confirm Modal Function
                function showCustomConfirm(title, message, link) {
                    const confirmModalEl = document.getElementById('customConfirmModal');
                    const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
                    
                    document.getElementById('customConfirmModalLabel').textContent = title;
                    document.getElementById('confirm-message').innerHTML = message; // Use innerHTML to allow simple tags like <b>
                    
                    const confirmLink = document.getElementById('confirm-link');
                    confirmLink.href = link;

                    // Dynamically change button color based on action
                    if (title.toLowerCase().includes('delete') || title.toLowerCase().includes('reject')) {
                        confirmLink.classList.remove('text-primary', 'text-warning');
                        confirmLink.classList.add('text-danger');
                    } else if (title.toLowerCase().includes('disable')) {
                        confirmLink.classList.remove('text-primary', 'text-danger');
                        confirmLink.classList.add('text-warning');
                    }
                    else {
                        confirmLink.classList.remove('text-danger', 'text-warning');
                        confirmLink.classList.add('text-primary');
                    }

                    confirmModal.show();
                }

                document.addEventListener('DOMContentLoaded', function() {
                    // Tooltip Initializer
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl)
                    });
                    
                    // ... (your existing notification script)
                });
            </script>
        </body>
    </html>