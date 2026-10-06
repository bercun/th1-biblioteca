<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../models/User.php';
require_once '../../models/Book.php';
require_once '../../models/UserBook.php';
//Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You must log in first'
    ]);
    exit;
}

//Get userId from session
$userId = (int) $_SESSION['user_id'];

//Get data from request
$data = json_decode(
    file_get_contents('php://input'),
    true
);

//Get bookId from data
$bookId = (int) ($data['bookId'] ?? 0);

//Check if bookId is valid
if ($bookId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid book'
    ]);
    exit;
}

//Get user model
$userModel = new User();
//Get book model
$bookModel = new Book();
//Get user book model
$userBookModel = new UserBook();

//Find user by ID
$user = $userModel->findById($userId);

//Check if user exists
if (!$user) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
    exit;
}

//Find book for purchase
$book = $bookModel->findForPurchase($bookId);

//Check if book exists
if (!$book) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Book not found'
    ]);
    exit;
}

//Check if user owns book
if ($userBookModel->userOwnsBook($userId, $bookId)) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'You already own this book'
    ]);
    exit;
}

//Purchase book
$purchasedAt = $userBookModel->purchase($userId, $bookId);
//Add bonus
$userModel->addBonus($book->bonus, $userId);

//Return success response
echo json_encode([
    'success' => true,
    'message' => 'Book added successfully',
    'book' => [
        'id' => $book->id,
        'title' => $book->title,
        'author' => $book->author,
        'bonus' => $book->bonus,
        'price' => $book->price,
        'purchased_at' => $purchasedAt
    ],
    'booksCount' => $userBookModel->countByUser($userId),
    'bonus' => $userModel->getBonus($userId)
]);
