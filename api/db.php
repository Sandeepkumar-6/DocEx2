<?php
// api/db.php
header('Content-Type: application/json'); // Default to JSON for APIs
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from users, log them instead

// REAL SECURITY: Load these from environment variables in production
$host = 'localhost';
$db   = 'codebuddies_db';
$user = 'root';
$pass = ''; 
$apiKey = $groq_secret_key; 

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Critical for security
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
?>