<?php

header('Content-Type: application/json; charset=utf-8');
require_once '../../models/Book.php';
//Get season from GET request
$season = strtolower(trim($_GET['season'] ?? ''));
//Get allowed seasons
$allowedSeasons = ['winter', 'spring', 'summer', 'autumn'];

//Check if season is valid
if ($season !== '' && !in_array($season, $allowedSeasons, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid season'
    ]);
    exit;
}

//Get book model
$bookModel = new Book();

//Return success response
echo json_encode(
    $bookModel->listBySeason($season === '' ? null : $season)
);
