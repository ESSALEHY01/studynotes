/**
 * Summary functionality for StudyNotes - Optimized version using shared functions
 */
document.addEventListener('DOMContentLoaded', function() {
    // Setup generate summary modal using shared function
    setupGenerationModal({
        modalId: 'generateSummaryModal',
        formId: 'generateSummaryForm',
        progressId: 'generationProgress',
        apiEndpoint: 'api/generate_summary.php',
        successRedirect: 'view_summary.php?id={id}&success=Summary generated successfully',
        idField: 'summary_id',
        prepareFormData: function() {
            return {
                note_id: document.getElementById('note_id').value,
                max_length: document.getElementById('max_length').value
            };
        }
    });

    // Setup delete summary modal using shared function
    setupDeleteModal({
        buttonSelector: '.delete-summary-btn',
        modalId: 'deleteSummaryModal',
        idInputId: 'delete_summary_id',
        titleElementId: 'delete_summary_title',
        idAttribute: 'data-id',
        titleAttribute: 'data-title'
    });

    // Open generate summary modal
    const generateSummaryBtn = document.getElementById('generateSummaryBtn');
    const generateSummaryModal = document.getElementById('generateSummaryModal');
    if (generateSummaryBtn && generateSummaryModal) {
        generateSummaryBtn.addEventListener('click', function() {
            openModal(generateSummaryModal);
        });
    }

    // Form submission and delete modal setup handled by shared functions above

    // Handle regenerate button (specific to summary functionality)
    const regenerateBtn = document.getElementById('regenerateBtn');
    const regenerationModal = document.getElementById('regenerationModal');

    if (regenerateBtn && regenerationModal) {
        regenerateBtn.addEventListener('click', function() {
            const noteId = this.getAttribute('data-note-id');

            // Show regeneration modal
            openModal(regenerationModal);

            // Send API request
            fetch('api/generate_summary.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    note_id: noteId,
                    max_length: 500 // Default length
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show the new summary
                    window.location.reload();
                } else {
                    // Show error
                    closeModal(regenerationModal);
                    alert('Error: ' + (data.error || 'Failed to regenerate summary'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                closeModal(regenerationModal);
                alert('An error occurred while regenerating the summary. Please try again.');
            });
        });
    }

    // Setup form validation using shared function
    setupNumericValidation('max_length', 100, 2000);

    // Modal close handlers are set up in shared-modal.js
});
