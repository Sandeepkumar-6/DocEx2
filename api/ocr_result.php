<?php
// api/get_text.php
header('Content-Type: application/json');
include 'db.php';

try {
    // 1. Validate ID
    if (!isset($_GET['id'])) {
        throw new Exception("Missing report ID");
    }

    $id = (int)$_GET['id'];

    // 2. Use Prepared Statements (Prevents SQL Injection)
    // We use PDO to match your upload.php and ocr.php logic
    $stmt = $pdo->prepare("SELECT extracted_text, status FROM reports WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception("Report not found");
    }

    // 3. Return the data
    echo json_encode([
        "success" => true,
        "extracted_text" => $row['extracted_text'],
        "status" => $row['status']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>