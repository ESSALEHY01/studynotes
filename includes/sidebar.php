<?php
/**
 * Common sidebar template for StudyNotes application
 *
 * @param string $active_page - The currently active page (dashboard, modules, notes, profile)
 */

// Default value
$active_page = $active_page ?? '';
?>
<div class="sidebar">
    <div class="sidebar-menu">
        <a href="dashboard.php" <?php echo ($active_page === 'dashboard') ? 'class="active"' : ''; ?>>
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="modules.php" <?php echo ($active_page === 'modules') ? 'class="active"' : ''; ?>>
            <i class="fas fa-book"></i> Modules
        </a>
        <a href="notes.php" <?php echo ($active_page === 'notes') ? 'class="active"' : ''; ?>>
            <i class="fas fa-sticky-note"></i> Notes
        </a>
        <a href="quizzes.php" <?php echo ($active_page === 'quizzes') ? 'class="active"' : ''; ?>>
            <i class="fas fa-question-circle"></i> Quizzes
        </a>
        <a href="summaries.php" <?php echo ($active_page === 'summaries') ? 'class="active"' : ''; ?>>
            <i class="fas fa-file-alt"></i> Summaries
        </a>
        <a href="profile.php" <?php echo ($active_page === 'profile') ? 'class="active"' : ''; ?>>
            <i class="fas fa-user"></i> Profile
        </a>
    </div>
</div>
