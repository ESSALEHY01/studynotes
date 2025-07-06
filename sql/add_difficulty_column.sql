-- Add difficulty column to quizzes table for enhanced difficulty level support
-- This script safely adds the difficulty column if it doesn't already exist

-- Check if the difficulty column exists and add it if it doesn't
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'quizzes' 
         AND COLUMN_NAME = 'difficulty') = 0,
        'ALTER TABLE quizzes ADD COLUMN difficulty ENUM(''easy'', ''medium'', ''hard'') DEFAULT ''medium'' AFTER quiz_title',
        'SELECT "Difficulty column already exists" AS message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing quizzes to have a default difficulty of 'medium' if they don't have one
UPDATE quizzes SET difficulty = 'medium' WHERE difficulty IS NULL;

-- Display completion message
SELECT 'Difficulty column has been successfully added to the quizzes table!' AS completion_message;
