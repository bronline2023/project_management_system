/**
 * assets/js/script.js
 *
 * This file contains custom JavaScript for global functionalities across the
 * Project Management System.
 *
 * Key functionalities include:
 * - Sidebar toggling for responsiveness on different screen sizes.
 * - Global auto-hiding for Bootstrap alerts.
 * - Reusable function for dynamic subcategory loading and fare auto-population (AJAX).
 * - Basic client-side form validation helper.
 * - HTML escape utility.
 * - Date/Time formatter utility.
 */

// BASE_URL_JS is expected to be defined globally in <script> tag within includes/header.php
// Example: const BASE_URL_JS = 'http://localhost/project_management_system/';

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const content = document.getElementById('content');

    // --- Sidebar Toggle Logic ---
    if (sidebarCollapse && sidebar && content) {
        // Initial state setup for sidebar based on screen width
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('active');
            content.classList.add('active');
            document.body.classList.remove('sidebar-open');
        } else {
            sidebar.classList.add('active');
            content.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }

        // Event listener for the sidebar toggle button
        sidebarCollapse.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            content.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        });

        // Close sidebar on small screens when a menu item is clicked
        document.querySelectorAll('#sidebar ul.components li a').forEach(item => {
            item.addEventListener('click', function() {
                const isDropdownToggle = this.hasAttribute('data-bs-toggle') && this.getAttribute('data-bs-toggle') === 'collapse';
                if (window.innerWidth < 768 && !isDropdownToggle) {
                    sidebar.classList.add('active');
                    content.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            });
        });
    }

    // --- Global Message Auto-Hide for Alerts ---
    // This function sets a timer to fade out and remove any .alert element.
    // It is called automatically on DOMContentLoaded.
    // It's also attached to window for explicit calls if needed after dynamic content load.
    window.setupAutoHideAlerts = function() {
        document.querySelectorAll('.alert').forEach(alertElement => {
            // Check if it's already set up to avoid multiple timers
            if (!alertElement.dataset.autoHideSetup) {
                setTimeout(function() {
                    alertElement.classList.add('fade-out');
                    alertElement.addEventListener('transitionend', function() {
                        alertElement.remove();
                    }, { once: true }); // Remove after fade-out completes
                }, 5000); // 5 seconds
                alertElement.dataset.autoHideSetup = true; // Mark as setup
            }
        });
    }

    // Call it once on DOMContentLoaded to handle initial page load alerts
    setupAutoHideAlerts();


    // --- Reusable Subcategory Loader and Fare Auto-population (AJAX) ---
    // This function handles the dynamic loading of subcategories and
    // updates the fare input field.
    // categoryIdElementId: ID of the <select> element for categories.
    // subcategoryIdElementId: ID of the <select> element for subcategories.
    // feeInputId: ID of the <input type="number"> for the fee/fare.
    // initialSelectedSubcategoryId (optional): Used when editing, to pre-select a subcategory.
    window.setupSubcategoryLoader = function(categoryIdElementId, subcategoryIdElementId, feeInputId, initialSelectedSubcategoryId = null) {
        const categorySelect = document.getElementById(categoryIdElementId);
        const subcategorySelect = document.getElementById(subcategoryIdElementId);
        const feeInput = document.getElementById(feeInputId);

        // Function to perform the actual AJAX fetch
        const fetchSubcategories = function(categoryId, selectedSubcategoryId = null) {
            subcategorySelect.innerHTML = '<option value="">Loading Subcategories...</option>';
            subcategorySelect.disabled = true;
            feeInput.value = '0.00'; // Reset fee when loading

            if (categoryId) {
                // Use the globally defined BASE_URL_JS variable
                const fetchUrl = `${BASE_URL_JS}models/fetch_subcategories.php?category_id=${categoryId}`;

                fetch(fetchUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                        if (data.length > 0) {
                            data.forEach(sub => {
                                const option = document.createElement('option');
                                option.value = sub.id;
                                option.textContent = sub.name;
                                option.setAttribute('data-fare', sub.fare);
                                if (selectedSubcategoryId && parseInt(sub.id) === parseInt(selectedSubcategoryId)) {
                                    option.selected = true;
                                }
                                subcategorySelect.appendChild(option);
                            });
                            subcategorySelect.disabled = false;
                            // Trigger change to auto-populate fee if a subcategory is selected or just loaded
                            subcategorySelect.dispatchEvent(new Event('change'));
                        } else {
                            subcategorySelect.innerHTML = '<option value="">No Subcategories Found</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching subcategories:', error);
                        subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                        // Optionally display a user-friendly message
                    });
            } else {
                subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            }
        };

        // Event listener for category change
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                fetchSubcategories(this.value);
            });
        }

        // Event listener for subcategory change to update fee
        if (subcategorySelect) {
            subcategorySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.getAttribute('data-fare')) {
                    feeInput.value = parseFloat(selectedOption.getAttribute('data-fare')).toFixed(2);
                } else {
                    feeInput.value = '0.00';
                }
            });
        }

        // Initial call if an initial category is already selected (e.g., on page load for editing)
        if (categorySelect && categorySelect.value) {
            fetchSubcategories(categorySelect.value, initialSelectedSubcategoryId);
        }
    };

    // --- Basic Form Validation Helper ---
    // This function can be used to add client-side validation to forms.
    // It checks for 'required' fields and adds Bootstrap's validation classes.
    window.setupFormValidation = function(formId) {
        const form = document.getElementById(formId);

        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    // Optionally, trigger an alert for general validation failure
                    // setupAutoHideAlerts('<div class="alert alert-danger" role="alert">Please fill in all required fields.</div>');
                }
                form.classList.add('was-validated'); // Add Bootstrap's validation styles
            }, false);

            // Reset validation state on modal close if the form is in a modal
            const modal = form.closest('.modal');
            if (modal) {
                modal.addEventListener('hidden.bs.modal', function() {
                    form.classList.remove('was-validated');
                    form.reset(); // Also reset form fields
                    // Clear any custom validation messages or styles here if applicable
                    form.querySelectorAll('.form-control').forEach(input => {
                         input.classList.remove('is-invalid', 'is-valid');
                    });
                });
            }
        }
    };

    // --- Utility: HTML Escape (for displaying dynamic content safely) ---
    window.htmlspecialchars = function(str) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    };

    // --- Utility: Date/Time Formatter (for messages, etc.) ---
    window.formatDateTime = function(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleString(); // You can customize this format
    };
});
