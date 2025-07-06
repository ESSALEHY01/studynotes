/**
 * Shared Modal and Form Functionality for StudyNotes
 * Consolidates common modal handling patterns used across quiz.js and summary.js
 */

/**
 * Setup modal functionality for generation forms
 * @param {Object} config - Configuration object
 * @param {string} config.modalId - ID of the modal
 * @param {string} config.formId - ID of the form
 * @param {string} config.progressId - ID of the progress indicator
 * @param {string} config.apiEndpoint - API endpoint for form submission
 * @param {string} config.successRedirect - URL pattern for success redirect
 * @param {Function} config.prepareFormData - Function to prepare form data
 */
function setupGenerationModal(config) {
    const modal = document.getElementById(config.modalId);
    const form = document.getElementById(config.formId);
    const progress = document.getElementById(config.progressId);
    
    if (!modal || !form) return;
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show progress indicator
        if (progress) {
            form.style.display = 'none';
            progress.style.display = 'block';
        }
        
        // Get form data
        const formData = config.prepareFormData ? config.prepareFormData() : new FormData(form);
        
        // Send API request
        fetch(config.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to success page
                const redirectUrl = config.successRedirect.replace('{id}', data[config.idField || 'id']);
                window.location.href = redirectUrl;
            } else {
                // Show error
                alert('Error: ' + (data.error || 'Operation failed'));
                resetForm();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            resetForm();
        });
        
        function resetForm() {
            if (progress) {
                form.style.display = 'block';
                progress.style.display = 'none';
            }
        }
    });
}

/**
 * Setup delete modal functionality
 * @param {Object} config - Configuration object
 * @param {string} config.buttonSelector - CSS selector for delete buttons
 * @param {string} config.modalId - ID of the delete modal
 * @param {string} config.idInputId - ID of the hidden input for item ID
 * @param {string} config.titleElementId - ID of the element showing item title
 * @param {string} config.idAttribute - Data attribute name for item ID
 * @param {string} config.titleAttribute - Data attribute name for item title
 */
function setupDeleteModal(config) {
    const deleteButtons = document.querySelectorAll(config.buttonSelector);
    const modal = document.getElementById(config.modalId);
    
    if (deleteButtons.length === 0 || !modal) return;
    
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute(config.idAttribute);
            const itemTitle = this.getAttribute(config.titleAttribute);
            
            document.getElementById(config.idInputId).value = itemId;
            document.getElementById(config.titleElementId).textContent = itemTitle;
            
            openModal(modal);
        });
    });
}

/**
 * Setup form validation for numeric inputs
 * @param {string} inputId - ID of the input element
 * @param {number} min - Minimum value
 * @param {number} max - Maximum value
 */
function setupNumericValidation(inputId, min, max) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.addEventListener('input', function() {
        const value = parseInt(this.value);
        if (value < min) this.value = min;
        if (value > max) this.value = max;
    });
}

/**
 * Setup expandable content functionality
 * @param {string} contentSelector - CSS selector for expandable content
 * @param {string} buttonSelector - CSS selector for toggle buttons
 */
function setupExpandableContent(contentSelector, buttonSelector) {
    const contents = document.querySelectorAll(contentSelector);
    
    contents.forEach(content => {
        const contentId = content.id.replace(contentSelector.replace('#', '').replace('.', ''), '');
        const button = document.querySelector(buttonSelector + '_' + contentId);
        
        // Check if content needs expansion
        if (content && content.scrollHeight > 200 && button) {
            button.style.display = 'block';
        }
    });
    
    // Add click event to toggle buttons
    const toggleButtons = document.querySelectorAll(buttonSelector.replace('_', ''));
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const contentId = this.getAttribute('data-content-id');
            const content = document.getElementById(contentSelector.replace('.', '') + '_' + contentId);
            const buttonText = this.querySelector('span');
            const buttonIcon = this.querySelector('i');
            
            if (content.classList.contains('expanded')) {
                // Collapse
                content.classList.remove('expanded');
                buttonText.textContent = 'Show More';
                buttonIcon.className = 'fas fa-chevron-down';
                content.scrollTop = 0;
            } else {
                // Expand
                content.classList.add('expanded');
                buttonText.textContent = 'Show Less';
                buttonIcon.className = 'fas fa-chevron-up';
            }
        });
    });
}

/**
 * Setup option item click handling for better UX
 * @param {string} selector - CSS selector for option items
 */
function setupOptionItemClicks(selector) {
    const optionItems = document.querySelectorAll(selector);
    optionItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Don't trigger if clicking directly on the radio button
            if (e.target.type !== 'radio') {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
            }
        });
    });
}

/**
 * Setup universal modal close functionality
 */
function setupModalCloseHandlers() {
    // Close buttons
    const closeBtns = document.querySelectorAll('.close, .cancel-btn');
    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal);
                resetModalForms(modal);
            }
        });
    });
    
    // Click outside to close
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
            resetModalForms(e.target);
        }
    });
}

/**
 * Reset forms inside a modal when closing
 * @param {HTMLElement} modal - The modal element
 */
function resetModalForms(modal) {
    const forms = modal.querySelectorAll('form');
    const progressElements = modal.querySelectorAll('.generation-progress');
    
    forms.forEach(form => {
        form.style.display = 'block';
    });
    
    progressElements.forEach(progress => {
        progress.style.display = 'none';
    });
}

/**
 * Auto-fill form fields based on selections
 * @param {string} selectId - ID of the select element
 * @param {string} targetInputId - ID of the target input
 * @param {Function} valueExtractor - Function to extract value from selected option
 */
function setupAutoFill(selectId, targetInputId, valueExtractor) {
    const select = document.getElementById(selectId);
    const targetInput = document.getElementById(targetInputId);
    
    if (!select || !targetInput) return;
    
    select.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const value = valueExtractor(selectedOption);
        targetInput.value = value;
    });
}

// Initialize common modal functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setupModalCloseHandlers();
});
