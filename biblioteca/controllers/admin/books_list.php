<?php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/require_admin.php';
require_once '../../models/Book.php';

//Get book model
$bookModel = new Book();

//JSON data
echo json_encode([
    'success' => true,
    'books' => $bookModel->listForAdmin()
]);
