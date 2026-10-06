<?php

require_once __DIR__ . '/../../models/Book.php';
//Get admin book request data
function getAdminBookRequestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    //Check if content type is multipart/form-data
    if (stripos($contentType, 'multipart/form-data') !== false) {
        return [
            'bookId' => (int) ($_POST['bookId'] ?? 0),
            'title' => trim($_POST['title'] ?? ''),
            'author' => trim($_POST['author'] ?? ''),
            'isbn' => trim($_POST['isbn'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'categoryId' => (int) ($_POST['categoryId'] ?? 0),
            'bonus' => (int) ($_POST['bonus'] ?? 1),
            'price' => (float) ($_POST['price'] ?? 1),
            'image' => trim($_POST['image'] ?? ''),
            'file' => $_FILES['imageFile'] ?? null
        ];
    }

    //Get data from request
    $data = json_decode(
        file_get_contents('php://input'),
        true
    ) ?? [];

    //Return data
    return [
        'bookId' => (int) ($data['bookId'] ?? 0),
        'title' => trim($data['title'] ?? ''),
        'author' => trim($data['author'] ?? ''),
        'isbn' => trim($data['isbn'] ?? ''),
        'description' => trim($data['description'] ?? ''),
        'categoryId' => (int) ($data['categoryId'] ?? 0),
        'bonus' => (int) ($data['bonus'] ?? 1),
        'price' => (float) ($data['price'] ?? 1),
        'image' => trim($data['image'] ?? ''),
        'file' => null
    ];
}

//Save uploaded book image
function saveUploadedBookImage(?array $file): ?string
{
    if (
        $file === null ||
        !isset($file['error']) ||
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }
//Check if image can be uploaded
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Unable to upload image');
    }
//Check if image size is valid
    if (($file['size'] ?? 0) <= 0 || $file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Image must be up to 2 MB');
    }
//Get temporary name
    $tmpName = $file['tmp_name'] ?? '';
//Check if temporary name is valid
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Invalid uploaded file');
    }
//Get MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName);
//Get extensions
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];
//Check if MIME type is valid
    if (!isset($extensions[$mime])) {
        throw new RuntimeException(
            'Only JPG, PNG, WEBP or GIF images are allowed'
        );
    }
//Get images directory
    $directory = realpath(__DIR__ . '/../../front/assets/images');
//Check if images directory is valid
    if ($directory === false || !is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('Images folder is not writable');
    }
    //Generate filename
    $filename = 'book_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4))
        . '.' . $extensions[$mime];
    //Get destination path
    $destination = $directory . DIRECTORY_SEPARATOR . $filename;
    //Check if image can be saved

    //Save image
    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Unable to save image');
    }

    //Return image path
    return '../front/assets/images/' . $filename;
}

//Resolve book image path
function resolveBookImagePath(string $image, ?array $file): string
{
    //Save uploaded book image
    $uploaded = saveUploadedBookImage($file);
    //Check if uploaded image is not null

    if ($uploaded !== null) {
        //Return uploaded image path
        return $uploaded;
    }

    //Return image path
    return Book::resolveImage($image);
}
