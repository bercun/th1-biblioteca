<?php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/require_admin.php';
require_once '../../models/User.php';

//Get user model
$userModel = new User();

//JSON data
echo json_encode([
    'success' => true,
    'users' => $userModel->listForAdmin()
]);
