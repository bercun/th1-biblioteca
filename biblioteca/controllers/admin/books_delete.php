<?php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/require_admin.php';
require_once '../../models/Book.php';

$data = json_decode(
    file_get_contents('php://input'),
    true
);
//Get bookId
$bookId = (int) ($data['bookId'] ?? 0);

//Invalid book
if ($bookId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid book'
    ]);
    exit;
}

//Get book model
$bookModel = new Book();

//Book does not exist
if (!$bookModel->findById($bookId)) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Book not found'
    ]);
    exit;
}

//Delete book
$bookModel->delete($bookId);
//JSON data
echo json_encode([
    'success' => true,
    'message' => 'Book deleted'
]);
