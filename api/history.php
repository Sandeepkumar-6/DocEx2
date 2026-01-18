<?php
// api/history.php
header('Content-Type: application/json');
include 'db.php';

// 1. Get the Report ID securely
$id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;

if ($id > 0) {
    try {
        // 2. Fetch the AI Result from the database
        $stmt = $pdo->prepare("SELECT ai_result FROM reports WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        // 3. Send the result back to the frontend
        if ($row) {
            echo json_encode($row);
        } else {
            echo json_encode(["ai_result" => "Report not found."]);
        }
    } catch (Exception $e) {
        // Handle database errors
        echo json_encode(["ai_result" => "Error fetching history."]);
    }
} else {
    echo json_encode(["ai_result" => "Waiting for upload..."]);
}
?>