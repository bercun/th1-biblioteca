-- Migration: add user roles and seed admin account.
-- Admin login: admin@test.com / 12345678
-- Run once on an existing biblioteca database.

USE biblioteca;

SET @has_role := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'role'
);

SET @sql := IF(
    @has_role = 0,
    'ALTER TABLE users ADD COLUMN role ENUM(''user'', ''admin'') NOT NULL DEFAULT ''user'' AFTER bonus',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO users
    (first_name, last_name, email, password, login_count, bonus, role)
VALUES
(
    'Admin',
    'Admin',
    'admin@test.com',
    '$2y$10$Ybj/w99ORJkSZxOy0p6y/usvVJD/DdIvabM8DvO4k6lB4oN0Wnj5m',
    0,
    0,
    'admin'
)
ON DUPLICATE KEY UPDATE
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    password = VALUES(password),
    role = 'admin';
