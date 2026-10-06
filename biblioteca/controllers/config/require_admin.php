<?php

// Guard for admin API endpoints in controllers/admin/*.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../models/User.php';
//Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    //Return unauthorized response
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You must log in first'
    ]);
    exit;
}

//Get userId from session
$userId = (int) $_SESSION['user_id'];
//Get user model
$userModel = new User();
//Find admin user by ID
$adminUser = $userModel->findById($userId);
//Check if admin user exists and is admin

if (!$adminUser || $adminUser->role !== 'admin') {
    //Return forbidden response
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Admin access required'
    ]);
    exit;
}
