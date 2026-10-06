-- Migration: add nullable UNIQUE isbn before description.
-- Multiple books may have isbn = NULL (NULL is never equal to NULL in SQL).
-- Run once on an existing biblioteca database.

USE biblioteca;

-- Add column if missing
SET @has_isbn := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'books'
      AND COLUMN_NAME = 'isbn'
);

SET @sql := IF(
    @has_isbn = 0,
    'ALTER TABLE books ADD COLUMN isbn VARCHAR(20) NULL UNIQUE AFTER author',
    'ALTER TABLE books MODIFY COLUMN isbn VARCHAR(20) NULL UNIQUE AFTER author'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fill ISBN for some books; others stay NULL
UPDATE books SET isbn = '978-1-250-81125-7' WHERE title = 'The Book Eaters';
UPDATE books SET isbn = '978-0-593-20202-9' WHERE title = 'Cackle';
UPDATE books SET isbn = '978-1-250-27439-0' WHERE title = 'The Last Queen';
UPDATE books SET isbn = '978-0-150-02637-6' WHERE title = 'The Body';
UPDATE books SET isbn = '978-0-06-287075-9' WHERE title = 'Days of Distraction';
UPDATE books SET isbn = '978-1-250-20593-3' WHERE title = 'Dominicana';
UPDATE books SET isbn = '978-0-14-303783-5' WHERE title = 'Let My People Go Surfing';
UPDATE books SET isbn = '978-0-452-27458-7' WHERE title = 'Shark Dialogues: A Novel';
UPDATE books SET isbn = '978-0-06-225645-4' WHERE title = 'The Great Fire';
UPDATE books SET isbn = '978-0-358-04819-9' WHERE title = 'Rickey: The Life and Legend';
