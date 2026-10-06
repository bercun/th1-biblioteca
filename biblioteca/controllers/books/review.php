<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../models/UserBook.php';
require_once '../../models/Review.php';

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
//Get rating from data
$rating = (int) ($data['rating'] ?? 0);

//Check if bookId and rating are valid
if ($bookId <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid book or rating'
    ]);
    exit;
}

//Get user book model
$userBookModel = new UserBook();
//Get review model
$review = new Review();

//Check if user owns book
if (!$userBookModel->userOwnsBook($userId, $bookId)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'You can review only purchased books'
    ]);
    exit;
}

//Set review properties
$review->user_id = $userId;
$review->book_id = $bookId;
$review->rating = $rating;
//Save review
$review->save();

$stats = $review->getBookStats($bookId);

//Return success response
echo json_encode([
    'success' => true,
    'bookId' => $bookId,
    'rating' => $rating,
    'averageRating' => $stats->average_rating,
    'reviewsCount' => $stats->reviews_count
]);
