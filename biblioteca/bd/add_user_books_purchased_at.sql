-- Migration: store purchase date/time in user_books.
-- Run once on an existing biblioteca database.

USE biblioteca;

ALTER TABLE user_books
    ADD COLUMN purchased_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    AFTER book_id;
