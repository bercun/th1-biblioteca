-- Migration: move books.season into books_category.
-- Keeps users / user_books / existing book rows.
-- Run once on an existing biblioteca database that still has books.season.

USE biblioteca;

CREATE TABLE IF NOT EXISTS books_category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season ENUM('winter', 'spring', 'summer', 'autumn') NOT NULL UNIQUE
);

INSERT IGNORE INTO books_category (id, season) VALUES
    (1, 'winter'),
    (2, 'spring'),
    (3, 'summer'),
    (4, 'autumn');

-- 1) Add category_id if missing
SET @has_category_id := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'books'
      AND COLUMN_NAME = 'category_id'
);

SET @sql := IF(
    @has_category_id = 0,
    'ALTER TABLE books ADD COLUMN category_id INT NULL AFTER description',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Fill category_id from old season column (only if season still exists)
SET @has_season := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'books'
      AND COLUMN_NAME = 'season'
);

SET @sql := IF(
    @has_season > 0,
    'UPDATE books b
     INNER JOIN books_category c ON c.season = b.season
     SET b.category_id = c.id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fallback for any NULL category_id
UPDATE books
SET category_id = 1
WHERE category_id IS NULL;

ALTER TABLE books
    MODIFY category_id INT NOT NULL;

-- 3) Drop old season column
SET @has_season := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'books'
      AND COLUMN_NAME = 'season'
);

SET @sql := IF(
    @has_season > 0,
    'ALTER TABLE books DROP COLUMN season',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) Add FK if missing
SET @has_fk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'books'
      AND CONSTRAINT_NAME = 'fk_books_category'
);

SET @sql := IF(
    @has_fk = 0,
    'ALTER TABLE books
        ADD CONSTRAINT fk_books_category
        FOREIGN KEY (category_id)
        REFERENCES books_category(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
