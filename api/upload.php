<?php
// api/upload.php
include 'db.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // 1. Input Validation (UPDATED to match your UI)
    // We map 'Report Title' from the UI to 'name' in the DB
    $name = htmlspecialchars($_POST['name'] ?? 'Untitled Report');
    
    // Default Age/Gender if not provided by the UI
    $age = (int)($_POST['age'] ?? 99); 
    $gender = $_POST['gender'] ?? 'Unknown';
    
    // Only check for the image and name
    if (empty($_FILES['image'])) {
        throw new Exception("Please select an image file.");
    }

    // 2. Secure File Upload Logic
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $fileInfo = $_FILES['image'];
    $extension = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception("Invalid file extension. Only JPG, PNG, WEBP allowed.");
    }

    $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;
    $uploadDirectory = "../uploads/";
    
    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0755, true);
    }

    $targetPath = $uploadDirectory . $newFileName;

    if (!move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
        throw new Exception("Could not save the file to the server.");
    }

    // 3. Database Transaction
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO patients (name, age, gender) VALUES (?, ?, ?)");
    $stmt->execute([$name, $age, $gender]);
    $patientId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO reports (patient_id, image, status, created_at) VALUES (?, ?, 'Processing', NOW())");
    $stmt->execute([$patientId, $newFileName]);
    $reportId = $pdo->lastInsertId();

    $pdo->commit();

    // 4. Response
    echo json_encode([
        "success" => true,
        "message" => "Upload successful",
        "redirect" => "result.html?id=" . $reportId
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400); // Send error code
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>