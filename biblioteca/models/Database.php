<?php

//Database connection
class Database
{
    private static ?PDO $pdo = null;

    //Get database connection
    public static function getConnection(): PDO
    {
        //If database connection already exists, return it
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        //Database host
        $host = 'localhost';
        //Database name
        $dbname = 'biblioteca';
        //Database username
        $username = 'root';
        //Database password
        $password = '';

        try {
            //Create database connection
            self::$pdo = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $username,
                $password
            );

            //Set error mode
            self::$pdo->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            //Set default fetch mode
            self::$pdo->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {
            //Set HTTP response code
            http_response_code(500);
            //Set content type
            header('Content-Type: application/json; charset=utf-8');
            //Send error response
            echo json_encode([
                'success' => false,
                'message' => 'Database connection error'
            ]);
            exit;
        }

        //Return database connection
        return self::$pdo;
    }
}
