<?php
/**
 * Common footer template for StudyNotes application
 * 
 * @param array $additional_scripts - Additional JavaScript files to include
 */

// Default value
$additional_scripts = $additional_scripts ?? [];
?>
        <footer>
            <p>&copy; <?php echo date('Y'); ?> StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <script src="js/common.js"></script>
    <script src="js/shared-modal.js"></script>
    <script src="js/user.js"></script>

    <?php foreach ($additional_scripts as $script): ?>
    <script src="<?php echo htmlspecialchars($script); ?>"></script>
    <?php endforeach; ?>
</body>
</html>
