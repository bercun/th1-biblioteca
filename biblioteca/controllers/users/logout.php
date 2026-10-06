<?php

//Check if user is logged in
session_start();

header('Content-Type: application/json; charset=utf-8');


// Clear current session
$_SESSION = [];


// Delete cookie
if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Delete current session
session_destroy();

//Return success response
echo json_encode([
    'success' => true,
    'message' => 'Logged out'
]);