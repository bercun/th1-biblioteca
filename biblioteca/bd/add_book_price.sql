-- Migration for existing biblioteca DB (keeps users/user_books).
-- Run once if books table was created without price.

USE biblioteca;

ALTER TABLE books
    ADD COLUMN price DECIMAL(8, 2) NOT NULL DEFAULT 1.00 AFTER bonus;

UPDATE books SET bonus = 1, price = 1.00 WHERE title = 'The Book Eaters';
UPDATE books SET bonus = 1, price = 1.50 WHERE title = 'Cackle';
UPDATE books SET bonus = 2, price = 2.00 WHERE title = 'Dante: Poet of the Secular World';
UPDATE books SET bonus = 1, price = 1.00 WHERE title = 'The Last Queen';

UPDATE books SET bonus = 2, price = 2.00 WHERE title = 'The Body';
UPDATE books SET bonus = 1, price = 1.50 WHERE title = 'Carry: A Memoir of Survival on Stolen Land';
UPDATE books SET bonus = 1, price = 1.00 WHERE title = 'Days of Distraction';
UPDATE books SET bonus = 1, price = 1.50 WHERE title = 'Dominicana';

UPDATE books SET bonus = 1, price = 1.00 WHERE title = 'Crude: A Memoir';
UPDATE books SET bonus = 2, price = 2.50 WHERE title = 'Let My People Go Surfing';
UPDATE books SET bonus = 1, price = 1.00 WHERE title = 'The Octopus Museum: Poems';
UPDATE books SET bonus = 1, price = 2.00 WHERE title = 'Shark Dialogues: A Novel';

UPDATE books SET bonus = 1, price = 1.00 WHERE title = 'Casual Conversation';
UPDATE books SET bonus = 2, price = 2.00 WHERE title = 'The Great Fire';
UPDATE books SET bonus = 1, price = 1.50 WHERE title = 'Rickey: The Life and Legend';
UPDATE books SET bonus = 1, price = 1.00 WHERE title = 'Slug: And Other Stories';
