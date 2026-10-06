<?php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/require_admin.php';
require_once '../config/book_image.php';
require_once '../../models/Book.php';
require_once '../../models/BookCategory.php';

//Get data from request
$data = getAdminBookRequestData();

//Get title from data
$title = $data['title'];
//Get author from data
$author = $data['author'];
//Get ISBN from data
$isbn = $data['isbn'];
//Get description from data
$description = $data['description'];
$categoryId = $data['categoryId'];
//Get bonus from data
$bonus = $data['bonus'];
//Get price from data
$price = $data['price'];

//Check if title, author, description and category are required
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

//Get category model
$categoryModel = new BookCategory();
//Check if category exists

if (!$categoryModel->exists($categoryId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid category'
    ]);
    exit;
}

//Get book model
$book = new Book();
//Set book properties
$book->title = $title;
$book->author = $author;
$book->isbn = $isbn === '' ? null : $isbn;
$book->description = $description;
$book->category_id = $categoryId;
$book->image = $image;
$book->bonus = $bonus;
$book->price = $price;

//Try to create book
try {
    $bookId = $book->create();
} catch (PDOException $e) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to create book (ISBN may already exist)'
    ]);
    exit;
}

//Return success response
echo json_encode([
    'success' => true,
    'bookId' => $bookId,
    'image' => $book->image,
    'message' => 'Book created'
]);
