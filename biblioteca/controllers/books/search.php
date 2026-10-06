<?php

header('Content-Type: application/json; charset=utf-8');
require_once '../../models/Book.php';
//Get query from GET request
$query = trim($_GET['q'] ?? '');

//Check if query is valid
if ($query === '') {
    echo json_encode([]);
    exit;
}

//Get book model
$bookModel = new Book();

//Return success response
echo json_encode(
    $bookModel->search($query)
);
