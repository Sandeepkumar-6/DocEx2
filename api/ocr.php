<?php
// api/ocr.php
include 'db.php';

// Set headers to return JSON (better for debugging via Network tab)
header('Content-Type: application/json');

// Increase execution time limit (5 minutes) as OCR + AI can be slow
set_time_limit(300);

try {
    if (!isset($_GET['id'])) {
        throw new Exception("No report ID provided");
    }

    $id = (int)$_GET['id'];
    
    // 1. Fetch the image filename
    $stmt = $pdo->prepare("SELECT image FROM reports WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        throw new Exception("Report not found in database");
    }

    // 2. Locate the file
    $imagePath = "../uploads/" . $row['image'];
    $realPath = realpath($imagePath);

    if (!$realPath || !file_exists($realPath)) {
        throw new Exception("Image file not found on disk at: " . $imagePath);
    }

    // 3. Execute OCR using Tesseract
    // Update status to let the user know OCR is running
    $stmt = $pdo->prepare("UPDATE reports SET status = 'Reading Text...' WHERE id = ?");
    $stmt->execute([$id]);

    $safePath = escapeshellarg($realPath);
    $tesseractPath = "C:\\Program Files\\Tesseract-OCR\\tesseract.exe";
    
    // Command structure: "ExePath" "ImagePath" stdout
    $cmd = "\"$tesseractPath\" $safePath stdout 2>&1";
    $text = shell_exec($cmd);

    // 4. Handle OCR Results
    if (empty(trim($text))) {
        // Fallback if OCR fails
        $text = "OCR could not read the text. The image might be blurry.";
    }

    // Update the record with the extracted text
    $stmt = $pdo->prepare("UPDATE reports SET extracted_text = ?, status = 'OCR Complete' WHERE id = ?");
    $stmt->execute([$text, $id]);

    // 5. Trigger AI Analysis
    // Instead of redirecting, we include the AI script to run immediately
    // Make sure you have an api/ai.php file!
    include 'ai.php';

    // If ai.php doesn't output its own JSON, we output success here
    if (!headers_sent()) {
        echo json_encode(["success" => true, "message" => "OCR and AI processing complete"]);
    }

} catch (Exception $e) {
    // Log error to database so the user sees "Failed" on the dashboard
    if (isset($pdo) && isset($id)) {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'Failed', ai_result = ? WHERE id = ?");
        $stmt->execute(["System Error: " . $e->getMessage(), $id]);
    }

    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "error" => $e->getMessage()
    ]);
}
?>