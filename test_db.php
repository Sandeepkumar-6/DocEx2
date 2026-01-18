<?php
include 'api/db.php';

try {
    $stmt = $pdo->query("SELECT 1");
    echo "Database connection successful.";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>
