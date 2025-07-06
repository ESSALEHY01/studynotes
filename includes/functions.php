<?php
/**
 * Utility functions for StudyNotes application
 */

/**
 * Check if current user is an admin
 *
 * @return bool True if current user is an admin, false otherwise
 */
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}
