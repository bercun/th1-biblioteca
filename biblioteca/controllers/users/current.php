<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
//Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(null);
    exit;
}
//Get userId from session
require_once '../../models/User.php';
require_once '../../models/UserBook.php';
//Get user model
$userId = (int) $_SESSION['user_id'];
//Get user model
$userModel = new User();
//Find user by ID
$user = $userModel->findById($userId);
//Check if user exists

if (!$user) {
    //Return not found response
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
    exit;
}
//Get user book model for owned books
$userBookModel = new UserBook();
//List owned books
$books = $userBookModel->listOwnedByUser($userId);
//Return success response
//Return user data
echo json_encode([
    'id' => $user->id,
    'first_name' => $user->first_name,
    'last_name' => $user->last_name,
    'email' => $user->email,
    'login_count' => $user->login_count,
    'bonus' => $user->bonus,
    'role' => $user->role,
    'books_count' => count($books),
    'books' => $books
]);
