/**
 * StudyNotes Common JavaScript Functions
 * Shared functionality used across both user and admin interfaces
 */

/**
 * Setup modal functionality
 * @param {Object} config - Configuration object with modal selectors
 * @param {string} config.addModalId - ID of the add modal
 * @param {string} config.editModalId - ID of the edit modal
 * @param {string} config.deleteModalId - ID of the delete modal
 * @param {string} config.addBtnId - ID of the add button
 * @param {string} config.editBtnClass - Class of edit buttons
 * @param {string} config.deleteBtnClass - Class of delete buttons
 * @param {Object} config.editDataMap - Mapping of data attributes to form fields for edit modal
 * @param {Object} config.deleteDataMap - Mapping of data attributes to form fields for delete modal
 */
function setupModals(config) {
    // Get all modals
    const addModal = document.getElementById(config.addModalId);
    const editModal = document.getElementById(config.editModalId);
    const deleteModal = document.getElementById(config.deleteModalId);

    // Get all modal triggers
    const addBtn = document.getElementById(config.addBtnId);
    const editBtns = document.querySelectorAll('.' + config.editBtnClass);
    const deleteBtns = document.querySelectorAll('.' + config.deleteBtnClass);

    // Get all close buttons
    const closeBtns = document.querySelectorAll('.close');
    const cancelBtns = document.querySelectorAll('.cancel-btn');

    // Store all modals in array for easier access
    const modals = [addModal, editModal, deleteModal].filter(modal => modal);

    // Add Button Click
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            if (addModal) {
                openModal(addModal);
            }
        });
    }

    // Edit Button Click
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (editModal) {
                // Map data attributes to form fields
                if (config.editDataMap) {
                    Object.keys(config.editDataMap).forEach(attr => {
                        const value = this.getAttribute('data-' + attr);
                        const fieldId = config.editDataMap[attr];
                        const field = document.getElementById(fieldId);
                        if (field) {
                            field.value = value || '';
                        }
                    });
                }
                openModal(editModal);
            }
        });
    });

    // Delete Button Click
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (deleteModal) {
                // Map data attributes to form fields
                if (config.deleteDataMap) {
                    Object.keys(config.deleteDataMap).forEach(attr => {
                        const value = this.getAttribute('data-' + attr);
                        const fieldId = config.deleteDataMap[attr];
                        const field = document.getElementById(fieldId);
                        if (field) {
                            if (field.tagName === 'INPUT') {
                                field.value = value || '';
                            } else {
                                field.textContent = value || '';
                            }
                        }
                    });
                }
                openModal(deleteModal);
            }
        });
    });

    // Close button click
    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal);
            }
        });
    });

    // Cancel button click
    cancelBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal);
            }
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        modals.forEach(modal => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            modals.forEach(modal => {
                if (modal && modal.style.display === 'block') {
                    closeModal(modal);
                }
            });
        }
    });
}

/**
 * Open modal
 * @param {HTMLElement} modal - The modal element to open
 */
function openModal(modal) {
    if (!modal) return;
    
    // Set the modal display to block
    modal.style.display = 'block';
    
    // Focus first input
    const firstInput = modal.querySelector('input, textarea');
    if (firstInput) {
        firstInput.focus();
    }
}

/**
 * Close modal
 * @param {HTMLElement} modal - The modal element to close
 */
function closeModal(modal) {
    if (!modal) return;
    
    // Hide the modal
    modal.style.display = 'none';
}

/**
 * Setup notification system
 */
function setupNotifications() {
    // Auto-hide success and error messages after 5 seconds
    const messages = document.querySelectorAll('.success-message, .error-message');

    messages.forEach(message => {
        setTimeout(() => {
            message.style.display = 'none';
        }, 5000);
    });
}

/**
 * Setup auto-save for forms
 * @param {string} excludeModalId - ID of modal to exclude from auto-save (e.g., delete confirmation)
 */
function setupFormAutoSave(excludeModalId) {
    // Auto-save form data to localStorage
    document.querySelectorAll('form').forEach(form => {
        // Skip forms that shouldn't be auto-saved (like delete forms)
        if (excludeModalId && form.closest('#' + excludeModalId)) return;

        const formId = form.id || `form_${Math.random().toString(36).substr(2, 9)}`;
        form.id = formId;

        // Load saved data
        const savedData = localStorage.getItem(`autosave_${formId}`);
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input && !['hidden', 'submit', 'button'].includes(input.type)) {
                        input.value = data[key];
                    }
                });
            } catch (e) {
                console.error('Error loading autosaved data', e);
            }
        }

        // Save data on input change
        form.querySelectorAll('input, textarea, select').forEach(input => {
            if (['hidden', 'submit', 'button'].includes(input.type)) return;

            input.addEventListener('input', function() {
                const formData = {};
                form.querySelectorAll('input, textarea, select').forEach(el => {
                    if (['hidden', 'submit', 'button'].includes(el.type)) return;
                    if (el.name) {
                        formData[el.name] = el.value;
                    }
                });

                localStorage.setItem(`autosave_${formId}`, JSON.stringify(formData));
            });
        });

        // Clear saved data on submit
        form.addEventListener('submit', function() {
            localStorage.removeItem(`autosave_${formId}`);
        });
    });
}

/**
 * Setup keyboard shortcuts
 * @param {Object} shortcuts - Map of key combinations to actions
 * @param {Object} tooltips - Map of element IDs to tooltip text
 */
function setupKeyboardShortcuts(shortcuts, tooltips) {
    document.addEventListener('keydown', function(e) {
        // Process each shortcut
        Object.keys(shortcuts).forEach(key => {
            const [modifier, keyName] = key.split('+');
            
            if (e[modifier + 'Key'] && e.key === keyName) {
                e.preventDefault();
                const action = shortcuts[key];
                
                if (typeof action === 'function') {
                    action();
                } else if (typeof action === 'string') {
                    if (action.startsWith('#')) {
                        // Click an element
                        const el = document.querySelector(action);
                        if (el) el.click();
                    } else {
                        // Navigate to URL
                        window.location.href = action;
                    }
                }
            }
        });
    });

    // Add tooltips
    if (tooltips) {
        Object.keys(tooltips).forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.setAttribute('title', tooltips[id]);
            }
        });
    }
}
