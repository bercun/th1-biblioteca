<?php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/require_admin.php';
require_once '../config/book_image.php';
require_once '../../models/Book.php';
require_once '../../models/BookCategory.php';
//Get data from request
$data = getAdminBookRequestData();

$bookId = $data['bookId'];
//Get title from data
$title = $data['title'];
//Get author from data
$author = $data['author'];
$isbn = $data['isbn'];
//Get description from data
$description = $data['description'];
$categoryId = $data['categoryId'];
//Get bonus from data
$bonus = $data['bonus'];
$price = $data['price'];
//Check if bookId is valid

//Check if title, author, description and category are required
if ($bookId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid book'
    ]);
    exit;
}

if (
    $title === '' ||
    $author === '' ||
    $description === '' ||
    $categoryId <= 0
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Title, author, description and category are required'
    ]);
    exit;
}
//Check if bonus and price are not negative
if ($bonus < 0 || $price < 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Price and bonus cannot be negative'
    ]);
    exit;
}

//Try to resolve book image path
try {
    $image = resolveBookImagePath($data['image'], $data['file']);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

//Get book model
$bookModel = new Book();
//Get category model
$categoryModel = new BookCategory();
//Find book by ID
$book = $bookModel->findById($bookId);
//Check if book exists

if (!$book) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Book not found'
    ]);
    exit;
}

//Check if category exists
if (!$categoryModel->exists($categoryId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid category'
    ]);
    exit;
}

//Set book properties
$book->title = $title;
$book->author = $author;
$book->isbn = $isbn === '' ? null : $isbn;
$book->description = $description;
$book->category_id = $categoryId;
$book->image = $image;
$book->bonus = $bonus;
$book->price = $price;

//Try to update book
try {
    $book->update();
} catch (PDOException $e) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to update book (ISBN may already exist)'
    ]);
    exit;
}

//Return success response
echo json_encode([
    'success' => true,
    'image' => $book->image,
    'message' => 'Book updated'
]);
