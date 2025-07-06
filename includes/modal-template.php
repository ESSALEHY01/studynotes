<?php
/**
 * Reusable modal template for StudyNotes application
 * 
 * @param string $modal_id - The ID of the modal
 * @param string $modal_title - The title of the modal
 * @param string $modal_content - The HTML content of the modal body
 * @param string $modal_footer - The HTML content of the modal footer (optional)
 * @param bool $is_danger - Whether this is a dangerous action modal (optional)
 */

// Default values
$modal_id = $modal_id ?? 'modal';
$modal_title = $modal_title ?? 'Modal Title';
$modal_content = $modal_content ?? '';
$modal_footer = $modal_footer ?? '<button type="button" class="btn btn-secondary cancel-btn">Cancel</button>';
$is_danger = $is_danger ?? false;
?>
<div id="<?php echo htmlspecialchars($modal_id); ?>" class="modal<?php echo $is_danger ? ' danger-modal' : ''; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php echo htmlspecialchars($modal_title); ?></h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <?php echo $modal_content; ?>
        </div>
        <?php if (!empty($modal_footer)): ?>
        <div class="modal-footer">
            <?php echo $modal_footer; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
