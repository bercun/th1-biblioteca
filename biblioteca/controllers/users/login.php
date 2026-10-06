<?php

//Check if user is logged in
session_start();
//Set content type
header('Content-Type: application/json');
require_once '../../models/User.php';

//Get data from request
$data = json_decode(
    file_get_contents('php://input'),
    true
);

//Get email and password from data
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

//Get user model
$userModel = new User();
$user = $userModel->findByEmail($email);

//Check if user exists and password is correct
if (
    !$user ||
    !password_verify($password, (string) $user->password)
) {
    //Return unauthorized response
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid credentials'
    ]);
    exit;
}

//Set user id in session
$_SESSION['user_id'] = $user->id;
$user->incrementLoginCount();

//Return success response
echo json_encode([
    'success' => true
]);
