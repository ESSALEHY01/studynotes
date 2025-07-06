
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

// Get all modules for this user (for dropdown)
$stmt = $conn->prepare("SELECT * FROM modules WHERE user_id = ? ORDER BY module_name ASC");
$stmt->execute([$user_id]);
$modules = $stmt->fetchAll();

// Check if filtering by module
$module_filter = isset($_GET['module']) ? intval($_GET['module']) : null;

// Get notes for this user
if ($module_filter) {
    $stmt = $conn->prepare("
        SELECT n.*, m.module_name
        FROM notes n
        JOIN modules m ON n.module_id = m.module_id
        WHERE n.user_id = ? AND n.module_id = ?
        ORDER BY n.updated_at DESC
    ");
    $stmt->execute([$user_id, $module_filter]);
} else {
    $stmt = $conn->prepare("
        SELECT n.*, m.module_name
        FROM notes n
        JOIN modules m ON n.module_id = m.module_id
        WHERE n.user_id = ?
        ORDER BY n.updated_at DESC
    ");
    $stmt->execute([$user_id]);
}
$notes = $stmt->fetchAll();

// Handle note actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new note
    if (isset($_POST['action']) && $_POST['action'] === 'add_note') {
        $title = trim($_POST['title']);
        $content = $_POST['content'];
        $module_id = $_POST['module_id'];

        // Validate input
        if (!empty($title) && !empty($content) && !empty($module_id)) {
            try {
                $stmt = $conn->prepare("INSERT INTO notes (title, content, module_id, user_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $module_id, $user_id]);

                // Redirect to refresh page
                header('Location: notes.php?success=Note added successfully');
                exit;
            } catch (PDOException $e) {
                $error = "Error adding note: " . $e->getMessage();
            }
        } else {
            $error = "Title, content and module are required";
        }
    }

    // Delete note
    if (isset($_POST['action']) && $_POST['action'] === 'delete_note') {
        $note_id = $_POST['note_id'];

        try {
            $stmt = $conn->prepare("DELETE FROM notes WHERE note_id = ? AND user_id = ?");
            $stmt->execute([$note_id, $user_id]);

            // Redirect to refresh page
            header('Location: notes.php?success=Note deleted successfully');
            exit;
        } catch (PDOException $e) {
            $error = "Error deleting note: " . $e->getMessage();
        }
    }

    // Edit note
    if (isset($_POST['action']) && $_POST['action'] === 'edit_note') {
        $note_id = $_POST['note_id'];
        $title = trim($_POST['title']);
        $content = $_POST['content'];
        $module_id = $_POST['module_id'];

        // Validate input
        if (!empty($title) && !empty($content) && !empty($module_id)) {
            try {
                $stmt = $conn->prepare("UPDATE notes SET title = ?, content = ?, module_id = ?, updated_at = NOW() WHERE note_id = ? AND user_id = ?");
                $stmt->execute([$title, $content, $module_id, $note_id, $user_id]);

                // Redirect to refresh page
                header('Location: notes.php?success=Note updated successfully');
                exit;
            } catch (PDOException $e) {
                $error = "Error updating note: " . $e->getMessage();
            }
        } else {
            $error = "Title, content and module are required";
        }
    }
}

// Check if we're in create mode
$create_mode = isset($_GET['action']) && $_GET['action'] === 'new';

// Check if we're in edit mode
$edit_mode = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$edit_note = null;

if ($edit_mode) {
    $note_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM notes WHERE note_id = ? AND user_id = ?");
    $stmt->execute([$note_id, $user_id]);
    $edit_note = $stmt->fetch();

    if (!$edit_note) {
        header('Location: notes.php?error=Note not found');
        exit;
    }
}

// Get success or error messages
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : (isset($error) ? $error : '');

// Get module name for filter display
$filter_module_name = '';
if ($module_filter) {
    $stmt = $conn->prepare("SELECT module_name FROM modules WHERE module_id = ? AND user_id = ?");
    $stmt->execute([$module_filter, $user_id]);
    $module = $stmt->fetch();
    if ($module) {
        $filter_module_name = $module['module_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyNotes - Notes</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="css/form-elements.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Include TinyMCE for rich text editing (self-hosted version) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.7/tinymce.min.js" integrity="sha512-n3wLeNCy3mLETpMQmAYgCDJn4aKY+3tgOhtXr1yKZ4/IVxv5JDnO3H4JtAQIwJJjwNjV7r9K3/JgGJH4UUg3uw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        /* Additional styles for notes page */
        .note-editor {
            margin-bottom: 20px;
        }

        .tox-tinymce {
            border-radius: 8px !important;
            border: 1px solid #ddd !important;
        }

        .note-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .note-preview {
            flex-grow: 1;
        }

        .filter-badge {
            background-color: var(--primary-color);
            color: var(--light-text);
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            margin-left: 10px;
            font-size: 14px;
        }

        .filter-badge i {
            margin-left: 5px;
            cursor: pointer;
        }

        .filter-badge i:hover {
            color: var(--accent-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StudyNotes</h1>
                <p class="tagline">Your Study Notes</p>
            </div>
            <div class="user-profile">
                <span class="user-name"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <div class="dashboard-container">
                <?php
                $active_page = 'notes';
                include('includes/sidebar.php');
                ?>

                <div class="content">
                    <?php if ($create_mode || $edit_mode): ?>
                        <!-- Note Editor -->
                        <div class="page-header">
                            <h2><?php echo $edit_mode ? 'Edit Note' : 'Create New Note'; ?></h2>
                            <a href="notes.php" class="btn btn-secondary btn-slim btn-nav"><i class="fas fa-arrow-left"></i> Back to Notes</a>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="error-message">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-body">
                                <form action="notes.php" method="post">
                                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit_note' : 'add_note'; ?>">
                                    <?php if ($edit_mode): ?>
                                        <input type="hidden" name="note_id" value="<?php echo $edit_note['note_id']; ?>">
                                    <?php endif; ?>

                                    <div class="form-group">
                                        <label for="title"><i class="fas fa-heading"></i> Note Title</label>
                                        <input type="text" id="title" name="title" value="<?php echo $edit_mode ? htmlspecialchars($edit_note['title']) : ''; ?>" placeholder="Enter a title for your note" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="module_id"><i class="fas fa-book"></i> Module</label>
                                        <div class="select-with-icons">
                                            <div class="custom-select-container">
                                                <select id="module_id" name="module_id" class="custom-select" required>
                                                    <option value="">Select a module</option>
                                                    <?php foreach ($modules as $module): ?>
                                                        <option value="<?php echo $module['module_id']; ?>" <?php echo ($edit_mode && $edit_note['module_id'] == $module['module_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($module['module_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <i class="fas fa-folder"></i>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="content"><i class="fas fa-edit"></i> Note Content</label>
                                        <textarea id="content" name="content" class="note-editor"><?php echo $edit_mode ? $edit_note['content'] : ''; ?></textarea>
                                    </div>

                                    <div class="form-buttons">
                                        <a href="notes.php" class="btn btn-secondary btn-slim btn-nav"><i class="fas fa-times"></i> Cancel</a>
                                        <button type="submit" class="btn btn-primary btn-slim">
                                            <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?>"></i>
                                            <?php echo $edit_mode ? 'Update Note' : 'Save Note'; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                            // Initialize TinyMCE
                            tinymce.init({
                                selector: '#content',
                                height: 400,
                                menubar: false,
                                branding: false,
                                promotion: false,
                                plugins: [
                                    'advlist autolink lists link image charmap anchor',
                                    'searchreplace visualblocks code fullscreen',
                                    'insertdatetime media table paste code help wordcount'
                                ],
                                toolbar: 'undo redo | formatselect | ' +
                                    'bold italic backcolor | alignleft aligncenter ' +
                                    'alignright alignjustify | bullist numlist outdent indent | ' +
                                    'removeformat | help',
                                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.6; }',
                                setup: function(editor) {
                                    editor.on('init', function() {
                                        // Remove any API key warnings that might appear
                                        const warnings = document.querySelectorAll('.tox-notification');
                                        warnings.forEach(warning => {
                                            warning.style.display = 'none';
                                        });
                                    });
                                }
                            });

                            // Form animations and focus
                            document.addEventListener('DOMContentLoaded', function() {
                                // Add animation to form elements
                                const formGroups = document.querySelectorAll('.form-group');
                                formGroups.forEach((group, index) => {
                                    group.style.opacity = '0';
                                    group.style.transform = 'translateY(20px)';

                                    setTimeout(() => {
                                        group.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                                        group.style.opacity = '1';
                                        group.style.transform = 'translateY(0)';
                                    }, 100 + (index * 100));
                                });

                                // Focus title input after animations
                                setTimeout(() => {
                                    document.getElementById('title').focus();
                                }, 600);

                                // Form validation with visual feedback
                                const moduleSelect = document.getElementById('module_id');

                                moduleSelect.addEventListener('change', function() {
                                    if (this.value === '') {
                                        this.style.borderColor = '#dc3545';
                                    } else {
                                        this.style.borderColor = '#28a745';
                                    }
                                });

                                document.querySelector('form').addEventListener('submit', function(e) {
                                    if (moduleSelect.value === '') {
                                        e.preventDefault();
                                        moduleSelect.style.borderColor = '#dc3545';
                                        moduleSelect.style.animation = 'shake 0.5s';
                                        setTimeout(() => {
                                            moduleSelect.style.animation = '';
                                        }, 500);
                                    }
                                });
                            });
                        </script>

                        <style>
                            @keyframes shake {
                                0%, 100% { transform: translateX(0); }
                                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                                20%, 40%, 60%, 80% { transform: translateX(5px); }
                            }
                        </style>
                    <?php else: ?>
                        <!-- Notes List -->
                        <div class="page-header">
                            <h2>
                                Your Notes
                                <?php if ($module_filter && !empty($filter_module_name)): ?>
                                    <span class="filter-badge">
                                        <?php echo htmlspecialchars($filter_module_name); ?>
                                        <a href="notes.php"><i class="fas fa-times"></i></a>
                                    </span>
                                <?php endif; ?>
                            </h2>
                            <div class="page-actions" style="display: flex; gap: 10px; align-items: center;">
                                <?php if (count($notes) > 0): ?>
                                    <a href="generate_quiz.php?source=all_notes" class="btn btn-primary btn-slim btn-action">
                                        <i class="fas fa-question-circle"></i> Generate Quiz from All Notes
                                    </a>
                                <?php endif; ?>
                                <a href="notes.php?action=new" class="btn btn-primary btn-slim btn-action">
                                    <i class="fas fa-plus"></i> Add New Note
                                </a>
                            </div>
                        </div>

                        <?php if (!empty($success)): ?>
                            <div class="success-message">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="error-message">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>



                        <!-- Notes Grid -->
                        <div class="card">
                            <div class="card-body">
                                <?php if (count($notes) > 0): ?>
                                    <div class="grid-container" id="notesGrid">
                                        <?php foreach ($notes as $note): ?>
                                            <div class="content-card">
                                                <div class="content-card-header">
                                                    <h4><?php echo htmlspecialchars($note['title']); ?></h4>
                                                    <span class="module-badge"><?php echo htmlspecialchars($note['module_name']); ?></span>
                                                </div>
                                                <div class="content-card-body">
                                                    <?php echo substr(strip_tags($note['content']), 0, 150) . '...'; ?>
                                                </div>
                                                <div class="content-card-footer">
                                                    <span class="content-card-date"><i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($note['updated_at'])); ?></span>
                                                    <div class="content-card-actions">
                                                        <a href="view_note.php?id=<?php echo $note['note_id']; ?>" class="btn-sm"><i class="fas fa-eye"></i> View</a>
                                                        <a href="notes.php?action=edit&id=<?php echo $note['note_id']; ?>" class="btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                                        <button class="btn-sm btn-danger delete-note-btn" data-id="<?php echo $note['note_id']; ?>" data-title="<?php echo htmlspecialchars($note['title']); ?>">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">
                                        <?php if ($module_filter): ?>
                                            <p>No notes found in this module. <a href="notes.php?action=new">Create your first note</a> in this module.</p>
                                        <?php else: ?>
                                            <p>You haven't created any notes yet. <a href="notes.php?action=new">Create your first note</a>.</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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
                        <button type="button" class="btn btn-secondary btn-slim cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-slim">Delete Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/common.js"></script>
    <script src="js/user.js"></script>
    <script>
        // Wait for both DOM and all scripts to be fully loaded
        window.addEventListener('load', function() {
            console.log('Notes.php: Page fully loaded, setting up delete modal...');

            // Setup delete note modal functionality
            setupNoteDeleteModal();
        });

        function setupNoteDeleteModal() {
            const deleteNoteBtns = document.querySelectorAll('.delete-note-btn');
            const deleteNoteModal = document.getElementById('deleteNoteModal');

            console.log('Found delete buttons:', deleteNoteBtns.length);
            console.log('Found delete modal:', deleteNoteModal ? 'Yes' : 'No');

            if (!deleteNoteModal) {
                console.error('Delete modal not found!');
                return;
            }

            // Setup delete button click handlers
            deleteNoteBtns.forEach((btn, index) => {
                console.log(`Setting up delete button ${index + 1}:`, btn);
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Delete button clicked!');

                    const noteId = this.getAttribute('data-id');
                    const noteTitle = this.getAttribute('data-title');

                    console.log('Note ID:', noteId);
                    console.log('Note Title:', noteTitle);

                    // Set modal content
                    document.getElementById('delete_note_id').value = noteId;
                    document.getElementById('delete_note_title').textContent = noteTitle;

                    // Show modal
                    console.log('Opening modal...');
                    showDeleteModal(deleteNoteModal);
                });
            });

            // Setup modal close handlers
            setupModalCloseHandlers(deleteNoteModal);
        }

        function showDeleteModal(modal) {
            if (typeof openModal === 'function') {
                openModal(modal);
            } else {
                // Fallback: show modal directly
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
        }

        function closeDeleteModal(modal) {
            if (typeof closeModal === 'function') {
                closeModal(modal);
            } else {
                // Fallback: hide modal directly
                modal.style.display = 'none';
                document.body.style.overflow = 'auto'; // Restore scrolling
            }
        }

        function setupModalCloseHandlers(modal) {
            // Close button
            const closeBtn = modal.querySelector('.close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    closeDeleteModal(modal);
                });
            }

            // Cancel button
            const cancelBtn = modal.querySelector('.cancel-btn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    closeDeleteModal(modal);
                });
            }

            // Click outside modal
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeDeleteModal(modal);
                }
            });

            // Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.style.display === 'block') {
                    closeDeleteModal(modal);
                }
            });
        }
    </script>
</body>
</html>




