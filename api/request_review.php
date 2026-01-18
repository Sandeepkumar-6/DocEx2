<?php
// api/request_review.php
header('Content-Type: application/json');
include 'db.php';

// 1. Enable Error Reporting (To see hidden bugs)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Capture Input
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;

// 3. Check ID
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing ID in URL. Example: diet.html?id=1']);
    exit;
}

// 4. Update Database
try {
    $stmt = $pdo->prepare("UPDATE reports SET review_requested = 1 WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // 5. Return Specific Database Error
    echo json_encode(['success' => false, 'error' => 'DB Error: ' . $e->getMessage()]);
}
?>