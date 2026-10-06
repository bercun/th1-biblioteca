<?php

session_start();
//Set content type
header('Content-Type: application/json; charset=utf-8');
//Require user model
require_once '../../models/User.php';

$data = json_decode(
    file_get_contents('php://input'),
    true
);

//Get first name, last name, email and password from data
$firstName = trim($data['firstName'] ?? '');
$lastName = trim($data['lastName'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

//Check if all fields are present
if (
    $firstName === '' ||
    $lastName === '' ||
    $email === '' ||
    $password === ''
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required'
    ]);
    exit;
}

//Check if email is valid
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email'
    ]);
    exit;
}

//Check if password is valid
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Password must contain at least 8 characters'
    ]);
    exit;
}

//Get user model
$user = new User();

//Check if user with such email already exists
if ($user->emailExists($email)) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'User with such email already exists'
    ]);
    exit;
}

//Set user properties
$user->first_name = $firstName;
$user->last_name = $lastName;
$user->email = $email;
$user->password = password_hash($password, PASSWORD_DEFAULT);

//Create user
$userId = $user->create();
//Set user id in session
$_SESSION['user_id'] = $userId;

//Return success response
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user->id,
        'firstName' => $user->first_name,
        'lastName' => $user->last_name,
        'email' => $user->email,
        'cardNumber' => null,
        'loginCount' => $user->login_count,
        'role' => $user->role,
        'books' => []
    ]
]);
