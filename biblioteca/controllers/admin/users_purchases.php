<?php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/require_admin.php';
require_once '../../models/User.php';
require_once '../../models/UserBook.php';
//Get userId from GET request
$userId = (int) ($_GET['user_id'] ?? 0);

//Check if userId is valid
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user'
    ]);
    exit;
}

//Get user model
$userModel = new User();
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

//Get user book model
$userBookModel = new UserBook();

//Return success response
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email
    ],
    'purchases' => $userBookModel->listPurchasesForAdmin($userId)
]);
