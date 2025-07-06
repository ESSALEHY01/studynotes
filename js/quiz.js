/**
 * Quiz functionality for StudyNotes - Optimized version using shared functions
 */
document.addEventListener('DOMContentLoaded', function() {
    // Setup expandable question content using shared function
    setupExpandableContent('.question-text', '.toggle-question-btn');

    // Setup option item clicks using shared function
    setupOptionItemClicks('.option-item');
    // Setup generate quiz modal using shared function
    setupGenerationModal({
        modalId: 'generateQuizModal',
        formId: 'generateQuizForm',
        progressId: 'generationProgress',
        apiEndpoint: 'api/generate_quiz.php',
        successRedirect: 'take_quiz.php?id={id}&success=Quiz generated successfully',
        idField: 'quiz_id',
        prepareFormData: function() {
            return {
                note_id: document.getElementById('note_id').value,
                quiz_title: document.getElementById('quiz_title').value,
                num_questions: document.getElementById('num_questions').value,
                difficulty: document.querySelector('input[name="difficulty"]:checked').value,
                module_id: document.getElementById('module_id').value
            };
        }
    });

    // Setup delete quiz modal using shared function
    setupDeleteModal({
        buttonSelector: '.delete-quiz-btn',
        modalId: 'deleteQuizModal',
        idInputId: 'delete_quiz_id',
        titleElementId: 'delete_quiz_title',
        idAttribute: 'data-id',
        titleAttribute: 'data-title'
    });

    // Setup auto-fill for quiz title and module ID
    setupAutoFill('note_id', 'module_id', function(option) {
        return option.getAttribute('data-module') || 0;
    });

    setupAutoFill('note_id', 'quiz_title', function(option) {
        const noteTitle = option.textContent.split('(')[0].trim();
        return `Quiz on ${noteTitle}`;
    });

    // Open generate quiz modal
    const generateQuizBtn = document.getElementById('generateQuizBtn');
    const generateQuizModal = document.getElementById('generateQuizModal');
    if (generateQuizBtn && generateQuizModal) {
        generateQuizBtn.addEventListener('click', function() {
            openModal(generateQuizModal);
        });
    }

    // Form submission and delete modal setup handled by shared functions above

    // Setup form validation using shared function
    setupNumericValidation('num_questions', 1, 20);

    // Modal close handlers are set up in shared-modal.js
});
