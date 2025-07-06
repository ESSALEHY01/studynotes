
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

// Check if summary ID is provided
if (!isset($_GET['id'])) {
    header('Location: summaries.php?error=Summary ID is required');
    exit;
}

$summary_id = intval($_GET['id']);

// Get summary details
$stmt = $conn->prepare("
    SELECT s.*, n.title as note_title, n.note_id, n.content as note_content, m.module_name
    FROM ai_summaries s
    JOIN notes n ON s.note_id = n.note_id
    JOIN modules m ON n.module_id = m.module_id
    WHERE s.summary_id = ? AND n.user_id = ?
");
$stmt->execute([$summary_id, $user_id]);
$summary = $stmt->fetch();

// Check if summary exists
if (!$summary) {
    header('Location: summaries.php?error=Summary not found');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyNotes - View Summary</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="css/summary.css">
    <link rel="stylesheet" href="css/quiz.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Additional styles for summary view */
        .summary-container {
            background-color: var(--card-color);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
        }

        .summary-title {
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 28px;
        }

        .summary-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            color: var(--secondary-color);
            font-size: 14px;
        }

        .summary-module {
            background-color: var(--accent-color);
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 500;
        }

        .summary-date {
            color: var(--secondary-color);
        }

        .summary-content-container {
            margin-bottom: 30px;
        }

        .content-heading {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding-bottom: 10px;
        }

        .summary-content {
            line-height: 1.8;
            color: var(--text-color);
            font-size: 16px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid var(--accent-color);
            max-height: 300px;
            overflow-y: auto;
            transition: max-height 0.3s ease;
        }

        .summary-content.expanded {
            max-height: 1000px;
        }

        .summary-expand-collapse {
            text-align: center;
            margin-top: 10px;
        }

        .btn-accent {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }

        .btn-accent:hover {
            background-color: var(--primary-color);
            color: var(--light-text);
        }

        .summary-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .regenerate-btn {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }

        .regenerate-btn:hover {
            background-color: var(--primary-color);
            color: var(--light-text);
        }

        /* Breadcrumb Navigation */
        .breadcrumb {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .breadcrumb a {
            color: var(--secondary-color);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: var(--primary-color);
        }

        .breadcrumb .separator {
            margin: 0 8px;
            color: var(--secondary-color);
        }

        .breadcrumb .current {
            color: var(--primary-color);
            font-weight: 500;
        }

        /* Page Actions */
        .page-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StudyNotes</h1>
                <p class="tagline">View Summary</p>
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
                        <a href="notes.php"><i class="fas fa-sticky-note"></i> Notes</a>
                        <a href="quizzes.php"><i class="fas fa-question-circle"></i> Quizzes</a>
                        <a href="summaries.php" class="active"><i class="fas fa-file-alt"></i> Summaries</a>
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    </div>
                </div>

                <div class="content">
                    <div class="page-header">
                        <h2>View Summary</h2>
                        <div class="page-actions">
                            <a href="view_note.php?id=<?php echo $summary['note_id']; ?>&from_summary=<?php echo $summary_id; ?>" class="btn btn-slim btn-nav">
                                <i class="fas fa-sticky-note"></i> View Original Note
                            </a>
                            <a href="summaries.php" class="btn btn-slim btn-nav"><i class="fas fa-arrow-left"></i> Back to Summaries</a>
                        </div>
                    </div>

                    <div class="summary-container">
                        <h1 class="summary-title">Summary: <?php echo htmlspecialchars($summary['note_title']); ?></h1>

                        <div class="summary-meta">
                            <div class="summary-module">
                                <i class="fas fa-book"></i> <?php echo htmlspecialchars($summary['module_name']); ?>
                            </div>
                            <div class="summary-date">
                                <i class="fas fa-calendar-check"></i> Generated: <?php echo date('M d, Y', strtotime($summary['created_at'])); ?>
                            </div>
                        </div>



                        <div class="summary-content-container">
                            <h3 class="content-heading"><i class="fas fa-file-alt"></i> Summary Content</h3>
                            <div class="summary-content" id="summaryContent">
                                <?php echo nl2br(htmlspecialchars($summary['summary_content'])); ?>
                            </div>
                            <div class="summary-expand-collapse" id="expandCollapseBtn" style="display: none;">
                                <button class="btn btn-sm btn-accent" id="toggleSummaryBtn">
                                    <i class="fas fa-chevron-down"></i> <span id="toggleBtnText">Show More</span>
                                </button>
                            </div>
                        </div>

                        <div class="summary-actions">
                            <button id="regenerateBtn" class="btn regenerate-btn btn-slim btn-action" data-note-id="<?php echo $summary['note_id']; ?>" data-summary-id="<?php echo $summary_id; ?>">
                                <i class="fas fa-sync-alt"></i> Regenerate Summary
                            </button>
                            <button class="btn btn-danger btn-slim delete-summary-btn" data-id="<?php echo $summary_id; ?>" data-title="<?php echo htmlspecialchars($summary['note_title']); ?>">
                                <i class="fas fa-trash"></i> Delete Summary
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <!-- Delete Summary Modal -->
    <div id="deleteSummaryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Summary</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the summary for <strong id="delete_summary_title"></strong>?</p>
                <p class="warning">This action cannot be undone.</p>
                <form action="summaries.php" method="post">
                    <input type="hidden" name="action" value="delete_summary">
                    <input type="hidden" name="summary_id" id="delete_summary_id">
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary btn-slim cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-slim">Delete Summary</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Regeneration Progress Modal -->
    <div id="regenerationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Regenerating Summary</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="generation-progress">
                    <div class="spinner"></div>
                    <p>Regenerating summary with AI...</p>
                    <p class="small">This may take a few moments.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/common.js"></script>
    <script src="js/summary.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle summary content expansion
            const summaryContent = document.getElementById('summaryContent');
            const expandCollapseBtn = document.getElementById('expandCollapseBtn');
            const toggleSummaryBtn = document.getElementById('toggleSummaryBtn');
            const toggleBtnText = document.getElementById('toggleBtnText');

            // Check if the content is long enough to need expansion
            if (summaryContent && summaryContent.scrollHeight > 300) {
                // Show the expand/collapse button
                if (expandCollapseBtn) {
                    expandCollapseBtn.style.display = 'block';
                }

                // Add click event to toggle button
                if (toggleSummaryBtn) {
                    toggleSummaryBtn.addEventListener('click', function() {
                        if (summaryContent.classList.contains('expanded')) {
                            // Collapse
                            summaryContent.classList.remove('expanded');
                            toggleBtnText.textContent = 'Show More';
                            toggleSummaryBtn.querySelector('i').className = 'fas fa-chevron-down';

                            // Scroll back to the top of the content
                            summaryContent.scrollTop = 0;
                        } else {
                            // Expand
                            summaryContent.classList.add('expanded');
                            toggleBtnText.textContent = 'Show Less';
                            toggleSummaryBtn.querySelector('i').className = 'fas fa-chevron-up';
                        }
                    });
                }
            }

        });
    </script>
</body>
</html>

