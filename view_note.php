<?php
session_start();
require_once('db_connection.php');
require_once('includes/functions.php');

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}



// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Check if note ID is provided
if (!isset($_GET['id'])) {
    header('Location: notes.php?error=Note ID is required');
    exit;
}

$note_id = intval($_GET['id']);

// Get note details
$stmt = $conn->prepare("
    SELECT n.*, m.module_name
    FROM notes n
    JOIN modules m ON n.module_id = m.module_id
    WHERE n.note_id = ? AND n.user_id = ?
");
$stmt->execute([$note_id, $user_id]);
$note = $stmt->fetch();

// Check if note exists
if (!$note) {
    header('Location: notes.php?error=Note not found');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyNotes - <?php echo htmlspecialchars($note['title']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StudyNotes</h1>
                <p class="tagline">View Note</p>
            </div>
            <div class="user-profile">
                <span class="user-name"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <div class="dashboard-container">
                <div class="sidebar">
                    <div class="sidebar-menu">
                        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a href="modules.php"><i class="fas fa-book"></i> Modules</a>
                        <a href="notes.php" class="active"><i class="fas fa-sticky-note"></i> Notes</a>
                        <a href="quizzes.php"><i class="fas fa-question-circle"></i> Quizzes</a>
                        <a href="summaries.php"><i class="fas fa-file-alt"></i> Summaries</a>
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    </div>
                </div>

                <div class="content">
                    <div class="page-header">
                        <h2>View Note</h2>
                        <div class="page-actions">
                            <?php if (isset($_GET['from_summary'])): ?>
                                <a href="view_summary.php?id=<?php echo intval($_GET['from_summary']); ?>" class="btn-nav">
                                    <i class="fas fa-arrow-left"></i> Back to Summary
                                </a>
                            <?php elseif (isset($_GET['from_quiz'])): ?>
                                <a href="take_quiz.php?id=<?php echo intval($_GET['from_quiz']); ?>" class="btn-nav">
                                    <i class="fas fa-arrow-left"></i> Back to Quiz
                                </a>
                            <?php else: ?>
                                <a href="notes.php" class="btn-nav">
                                    <i class="fas fa-arrow-left"></i> Back to Notes
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="content-view-card">
                        <div class="content-view-header">
                            <h1 class="content-view-title"><?php echo htmlspecialchars($note['title']); ?></h1>
                            <div class="content-view-meta">
                                <span class="module-badge">
                                    <i class="fas fa-book"></i> <?php echo htmlspecialchars($note['module_name']); ?>
                                </span>
                                <span><i class="fas fa-calendar-plus"></i> Created: <?php echo date('M d, Y', strtotime($note['created_at'])); ?></span>
                                <span><i class="fas fa-calendar-check"></i> Updated: <?php echo date('M d, Y', strtotime($note['updated_at'])); ?></span>
                            </div>
                        </div>

                        <div class="content-view-body">
                            <?php echo $note['content']; ?>
                        </div>

                        <div class="content-view-actions">
                            <a href="notes.php?action=edit&id=<?php echo $note['note_id']; ?>" class="btn-sm"><i class="fas fa-edit"></i> Edit Note</a>
                            <button class="btn-sm btn-danger delete-note-btn" data-id="<?php echo $note['note_id']; ?>" data-title="<?php echo htmlspecialchars($note['title']); ?>">
                                <i class="fas fa-trash"></i> Delete Note
                            </button>
                            <button id="generateQuizBtn" class="btn-sm" data-note-id="<?php echo $note['note_id']; ?>" data-note-title="<?php echo htmlspecialchars($note['title']); ?>">
                                <i class="fas fa-question-circle"></i> Generate Quiz
                            </button>
                            <button id="generateSummaryBtn" class="btn-sm" data-note-id="<?php echo $note['note_id']; ?>" data-note-title="<?php echo htmlspecialchars($note['title']); ?>">
                                <i class="fas fa-file-alt"></i> Generate Summary
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <!-- Delete Note Modal -->
    <div id="deleteNoteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Note</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete note <strong id="delete_note_title"></strong>?</p>
                <p class="warning">This action cannot be undone.</p>
                <form action="notes.php" method="post">
                    <input type="hidden" name="action" value="delete_note">
                    <input type="hidden" name="note_id" id="delete_note_id">
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/user.js"></script>
    <script src="js/common.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup delete note modal
            const deleteNoteBtn = document.querySelector('.delete-note-btn');
            const deleteNoteModal = document.getElementById('deleteNoteModal');

            if (deleteNoteBtn && deleteNoteModal) {
                deleteNoteBtn.addEventListener('click', function() {
                    const noteId = this.getAttribute('data-id');
                    const noteTitle = this.getAttribute('data-title');

                    document.getElementById('delete_note_id').value = noteId;
                    document.getElementById('delete_note_title').textContent = noteTitle;

                    openModal(deleteNoteModal);
                });
            }

            // Setup generate quiz button
            const generateQuizBtn = document.getElementById('generateQuizBtn');
            if (generateQuizBtn) {
                generateQuizBtn.addEventListener('click', function() {
                    const noteId = this.getAttribute('data-note-id');
                    const noteTitle = this.getAttribute('data-note-title');

                    // Create and show a modal for quiz generation
                    const modal = document.createElement('div');
                    modal.className = 'modal';
                    modal.id = 'quickQuizModal';
                    modal.innerHTML = `
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3>Generate Quiz</h3>
                                <span class="close">&times;</span>
                            </div>
                            <div class="modal-body">
                                <form id="quickQuizForm">
                                    <div class="form-group">
                                        <label for="quiz_title"><i class="fas fa-heading"></i> Quiz Title</label>
                                        <input type="text" id="quiz_title" name="quiz_title" value="Quiz on ${noteTitle}" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="num_questions"><i class="fas fa-list-ol"></i> Number of Questions</label>
                                        <input type="number" id="num_questions" name="num_questions" min="1" max="20" value="5" required>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-sliders-h"></i> Difficulty Level</label>
                                        <div class="difficulty-radio-group">
                                            <div class="difficulty-radio-item easy">
                                                <input type="radio" id="quick_easy" name="difficulty" value="easy">
                                                <label for="quick_easy" class="difficulty-radio-label">
                                                    <i class="fas fa-seedling"></i>
                                                    <span>Easy</span>
                                                </label>
                                            </div>
                                            <div class="difficulty-radio-item medium">
                                                <input type="radio" id="quick_medium" name="difficulty" value="medium" checked>
                                                <label for="quick_medium" class="difficulty-radio-label">
                                                    <i class="fas fa-balance-scale"></i>
                                                    <span>Medium</span>
                                                </label>
                                            </div>
                                            <div class="difficulty-radio-item hard">
                                                <input type="radio" id="quick_hard" name="difficulty" value="hard">
                                                <label for="quick_hard" class="difficulty-radio-label">
                                                    <i class="fas fa-fire"></i>
                                                    <span>Hard</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" id="note_id" name="note_id" value="${noteId}">
                                    <div class="form-buttons">
                                        <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-magic"></i> Generate Quiz
                                        </button>
                                    </div>
                                </form>
                                <div id="generationProgress" class="generation-progress" style="display: none;">
                                    <div class="spinner"></div>
                                    <p>Generating quiz questions with AI...</p>
                                    <p class="small">This may take a few moments.</p>
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.appendChild(modal);
                    openModal(modal);

                    // Setup form submission
                    const form = document.getElementById('quickQuizForm');
                    const generationProgress = document.getElementById('generationProgress');

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        // Show progress indicator
                        form.style.display = 'none';
                        generationProgress.style.display = 'block';

                        // Get form data
                        const formData = {
                            note_id: document.getElementById('note_id').value,
                            quiz_title: document.getElementById('quiz_title').value,
                            num_questions: document.getElementById('num_questions').value,
                            difficulty: document.querySelector('input[name="difficulty"]:checked').value
                        };

                        // Send API request
                        fetch('api/generate_quiz.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(formData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Redirect to take quiz page
                                window.location.href = `take_quiz.php?id=${data.quiz_id}&success=Quiz generated successfully`;
                            } else {
                                // Show error
                                alert('Error: ' + (data.error || 'Failed to generate quiz'));

                                // Hide progress indicator
                                form.style.display = 'block';
                                generationProgress.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while generating the quiz. Please try again.');

                            // Hide progress indicator
                            form.style.display = 'block';
                            generationProgress.style.display = 'none';
                        });
                    });

                    // Setup close button
                    const closeBtn = modal.querySelector('.close');
                    const cancelBtn = modal.querySelector('.cancel-btn');

                    closeBtn.addEventListener('click', function() {
                        closeModal(modal);
                        setTimeout(() => {
                            document.body.removeChild(modal);
                        }, 300);
                    });

                    cancelBtn.addEventListener('click', function() {
                        closeModal(modal);
                        setTimeout(() => {
                            document.body.removeChild(modal);
                        }, 300);
                    });

                    // Close modal when clicking outside
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            closeModal(modal);
                            setTimeout(() => {
                                document.body.removeChild(modal);
                            }, 300);
                        }
                    });
                });
            }

            // Setup generate summary button
            const generateSummaryBtn = document.getElementById('generateSummaryBtn');
            if (generateSummaryBtn) {
                generateSummaryBtn.addEventListener('click', function() {
                    const noteId = this.getAttribute('data-note-id');

                    // Create and show a modal for summary generation
                    const modal = document.createElement('div');
                    modal.className = 'modal';
                    modal.id = 'quickSummaryModal';
                    modal.innerHTML = `
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3>Generate Summary</h3>
                                <span class="close">&times;</span>
                            </div>
                            <div class="modal-body">
                                <form id="quickSummaryForm">
                                    <div class="form-group">
                                        <label for="max_length"><i class="fas fa-text-width"></i> Maximum Length (characters)</label>
                                        <input type="number" id="max_length" name="max_length" min="100" max="2000" value="500" required>
                                    </div>
                                    <input type="hidden" id="note_id" name="note_id" value="${noteId}">
                                    <div class="form-buttons">
                                        <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-magic"></i> Generate Summary
                                        </button>
                                    </div>
                                </form>
                                <div id="summaryProgress" class="generation-progress" style="display: none;">
                                    <div class="spinner"></div>
                                    <p>Generating summary with AI...</p>
                                    <p class="small">This may take a few moments.</p>
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.appendChild(modal);
                    openModal(modal);

                    // Setup form submission
                    const form = document.getElementById('quickSummaryForm');
                    const summaryProgress = document.getElementById('summaryProgress');

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        // Show progress indicator
                        form.style.display = 'none';
                        summaryProgress.style.display = 'block';

                        // Get form data
                        const formData = {
                            note_id: document.getElementById('note_id').value,
                            max_length: document.getElementById('max_length').value
                        };

                        // Send API request
                        fetch('api/generate_summary.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(formData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Redirect to view summary page
                                window.location.href = `view_summary.php?id=${data.summary_id}&success=Summary generated successfully`;
                            } else {
                                // Show error
                                alert('Error: ' + (data.error || 'Failed to generate summary'));

                                // Hide progress indicator
                                form.style.display = 'block';
                                summaryProgress.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while generating the summary. Please try again.');

                            // Hide progress indicator
                            form.style.display = 'block';
                            summaryProgress.style.display = 'none';
                        });
                    });

                    // Setup close button
                    const closeBtn = modal.querySelector('.close');
                    const cancelBtn = modal.querySelector('.cancel-btn');

                    closeBtn.addEventListener('click', function() {
                        closeModal(modal);
                        setTimeout(() => {
                            document.body.removeChild(modal);
                        }, 300);
                    });

                    cancelBtn.addEventListener('click', function() {
                        closeModal(modal);
                        setTimeout(() => {
                            document.body.removeChild(modal);
                        }, 300);
                    });

                    // Close modal when clicking outside
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            closeModal(modal);
                            setTimeout(() => {
                                document.body.removeChild(modal);
                            }, 300);
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
