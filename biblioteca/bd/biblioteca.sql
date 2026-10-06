CREATE DATABASE IF NOT EXISTS biblioteca
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE biblioteca;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    login_count INT NOT NULL DEFAULT 0,
    bonus INT NOT NULL DEFAULT 0,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user'
);

CREATE TABLE books_category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season ENUM('winter', 'spring', 'summer', 'autumn') NOT NULL UNIQUE
);

CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    -- NULL allowed; UNIQUE permits many rows with isbn = NULL
    -- because in SQL NULL is never equal to NULL
    isbn VARCHAR(20) NULL UNIQUE,
    description TEXT,
    category_id INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    bonus INT NOT NULL DEFAULT 1,
    price DECIMAL(8, 2) NOT NULL DEFAULT 1.00,

    FOREIGN KEY (category_id)
        REFERENCES books_category(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

CREATE TABLE user_books (
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    purchased_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, book_id),

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE CASCADE
);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating TINYINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_user_book_review (user_id, book_id),

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE CASCADE,

    CHECK (rating BETWEEN 1 AND 5)
);

INSERT INTO books
    (title, author, isbn, description, category_id, image, bonus, price)
VALUES

-- WINTER (category_id = 1)

(
    'The Book Eaters',
    'Sunyi Dean',
    '978-1-250-81125-7',
    'An unusual sci-fi story about a book eater woman who tries desperately to save her dangerous mind-eater son from tradition and certain death. Complete with dysfunctional family values, light Sapphic romance, and a strong, complex protagonist. Not for the faint of heart.',
    1,
    '../front/assets/images/book1.png',
    1,
    1.00
),

(
    'Cackle',
    'Rachel Harrison',
    '978-0-593-20202-9',
    'Are your Halloween movies of choice The Witches of Eastwick and Practical Magic? Look no further than here - where a woman recovering from a breakup moves to a quaint town in upstate New York and befriends a beautiful witch.',
    1,
    '../front/assets/images/book2.png',
    1,
    1.50
),

(
    'Dante: Poet of the Secular World',
    'Erich Auerbach',
    NULL,
    'Auerbach''s engaging book places the ''Comedy'' within the tradition of epic, tragedy, and philosophy in general, arguing for Dante''s uniqueness as one who raised the individual and his drama of soul into something of divine significance—an inspired introduction to Dante''s main themes.',
    1,
    '../front/assets/images/book3.png',
    2,
    2.00
),

(
    'The Last Queen',
    'Clive Irving',
    '978-1-250-27439-0',
    'A timely and revelatory new biography of Queen Elizabeth (and her family) exploring how the Windsors have evolved and thrived as the modern world has changed around them.',
    1,
    '../front/assets/images/book4.png',
    1,
    1.00
),


-- SPRING (category_id = 2)

(
    'The Body',
    'Stephen King',
    '978-0-150-02637-6',
    'Powerful novel that takes you back to a nostalgic time, exploring both the beauty and danger and loss of innocence that is youth.',
    2,
    '../front/assets/images/book5.png',
    2,
    2.00
),

(
    'Carry: A Memoir of Survival on Stolen Land',
    'Toni Jenson',
    NULL,
    'This memoir about the author''s relationship with gun violence feels both expansive and intimate, resulting in a lyrical indictment of the way things are.',
    2,
    '../front/assets/images/book6.png',
    1,
    1.50
),

(
    'Days of Distraction',
    'Alexandra Chang',
    '978-0-06-287075-9',
    'A sardonic view of Silicon Valley culture, a meditation on race, and a journal of displacement and belonging, all in one form-defying package of spare prose.',
    2,
    '../front/assets/images/book7.png',
    1,
    1.00
),

(
    'Dominicana',
    'Angie Cruz',
    '978-1-250-20593-3',
    'A fascinating story of a teenage girl who marries a man twice her age with the promise to bring her to America. Her marriage is an opportunity for her family to eventually immigrate. For fans of Isabel Allende and Julia Alvarez.',
    2,
    '../front/assets/images/book8.png',
    1,
    1.50
),


-- SUMMER (category_id = 3)

(
    'Crude: A Memoir',
    'Pablo Fajardo & Sophie Tardy-Joubert',
    NULL,
    'Drawing and color by Damien Roudeau | This book illustrates the struggles of a group of indigenous Ecuadoreans as they try to sue the ChevronTexaco company for damage their oil fields did to the Amazon and her people.',
    3,
    '../front/assets/images/book9.png',
    1,
    1.00
),

(
    'Let My People Go Surfing',
    'Yvon Chouinard',
    '978-0-14-303783-5',
    'Chouinard—climber, businessman, environmentalist—shares tales of courage and persistence from his experience of founding and leading Patagonia, Inc. Full title: Let My People Go Surfing: The Education of a Reluctant Businessman, Including 10 More Years of Business Unusual.',
    3,
    '../front/assets/images/book10.png',
    2,
    2.50
),

(
    'The Octopus Museum: Poems',
    'Brenda Shaughnessy',
    NULL,
    'This collection of bold and scathingly beautiful feminist poems imagines what comes after our current age of environmental destruction, racism, sexism, and divisive politics.',
    3,
    '../front/assets/images/book11.png',
    1,
    1.00
),

(
    'Shark Dialogues: A Novel',
    'Kiana Davenport',
    '978-0-452-27458-7',
    'An epic saga of seven generations of one family encompasses the tumultuous history of Hawaii as a Hawaiian woman gathers her four granddaughters together in an erotic tale of villains and dreamers, queens and revolutionaries, lepers and healers.',
    3,
    '../front/assets/images/book12.png',
    1,
    2.00
),


-- AUTUMN (category_id = 4)

(
    'Casual Conversation',
    'Renia White',
    NULL,
    'White''s impressive debut collection takes readers through and beyond the concepts of conversation and the casual - both what we say to each other and what we don''t, examining the possibilities around how we construct and communicate identity.',
    4,
    '../front/assets/images/book13.png',
    1,
    1.00
),

(
    'The Great Fire',
    'Lou Ureneck',
    '978-0-06-225645-4',
    'The harrowing story of an ordinary American and a principled Naval officer who, horrified by the burning of Smyrna, led an extraordinary rescue effort that saved a quarter of a million refugees from the Armenian Genocide.',
    4,
    '../front/assets/images/book14.png',
    2,
    2.00
),

(
    'Rickey: The Life and Legend',
    'Howard Bryant',
    '978-0-358-04819-9',
    'With the fall rolling around, one can''t help but think of baseball''s postseason coming up! And what better way to prepare for it than reading the biography of one of the game''s all-time greatest performers, the Man of Steal, Rickey Henderson?',
    4,
    '../front/assets/images/book15.png',
    1,
    1.50
),

(
    'Slug: And Other Stories',
    'Megan Milks',
    NULL,
    'Exes Tegan and Sara find themselves chained together by hairballs of codependency. A father and child experience the shared trauma of giving birth to gods from their wounds.',
    4,
    '../front/assets/images/book16.png',
    1,
    1.00
);

-- Default admin: admin@test.com / 12345678
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
);
