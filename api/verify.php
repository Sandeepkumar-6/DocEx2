<?php
// api/verify.php
include 'db.php'; // This provides $pdo

$report_id = $_GET['id'] ?? 0;
if(!$report_id) { die(json_encode(["error" => "Invalid ID"])); }

// 1. Fetch AI Result (PDO style)
$stmt = $pdo->prepare("SELECT ai_result FROM reports WHERE id = ?");
$stmt->execute([$report_id]);
$row = $stmt->fetch();
$ai = trim($row['ai_result'] ?? '');

if(!$ai) { die(json_encode(["error" => "AI result missing"])); }

// 2. Safety Replacements
$unsafe = ['you have','you are suffering','diagnosed','cancer','guaranteed','fatal','die'];
$ai = str_ireplace($unsafe, 'may indicate', $ai);
$disclaimer = "\n\n⚠ Disclaimer: AI generated. Not a medical diagnosis.";
$final = $ai . $disclaimer;

// 3. Update Record (PDO style)
$stmt = $pdo->prepare("UPDATE reports SET ai_result = ?, verified = 1 WHERE id = ?");
$stmt->execute([$final, $report_id]);

echo json_encode(["status" => "verified", "final_result" => $final]);
?>