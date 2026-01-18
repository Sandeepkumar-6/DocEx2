<?php
// api/ai.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

// 1. Get the Report
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT extracted_text FROM reports WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    die("Error: Report not found inside database.");
}

// 2. Clean the Text
$cleanText = mb_convert_encoding($row['extracted_text'], 'UTF-8', 'UTF-8');
if (empty($cleanText)) {
    $cleanText = "No text could be read from the image.";
}

// 3. Prepare AI Request
$prompt = "
You are a patient-friendly medical report explainer.
Act as an friendly Assitant to help patients understand their medical reports in simple, non-alarming language.
Your job is to convert messy medical reports, lab values and prescriptions into simple, calm, non-alarming language that an ordinary patient can understand.

Rules:
• DO NOT diagnose diseases.
• DO NOT suggest medicines or treatment changes.
• DO NOT give medical advice.
• DO NOT scare the patient.
• Only explain what the report says.

What you must do:

1. Explain each medical term in very simple words.
2. Explain lab values and what the normal range means.
3. Compare the patient’s value with the normal range and say clearly:
   - \"This is within normal range\"
   - \"This is slightly high\"
   - \"This is slightly low\"
4. Keep your tone calm, reassuring and neutral.
5. Do not use medical jargon.
6. At the end give a short overall summary in this format:

Overall Health Summary:
• Good: what is normal
• Needs Attention: what is outside normal range

Do not add new information. Only explain what is written in the report.


\n\n" . $cleanText;

$data = [
    "model" => "openai/gpt-oss-120b", // <--- UPDATED MODEL NAME
    "messages" => [
        ["role" => "user", "content" => $prompt]
    ]
];

// 4. Send to Groq
$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 5. Handle Errors
if ($httpCode !== 200) {
    $errorMsg = "AI Error ($httpCode): " . $response . " | Curl Error: " . $curlError;
    $stmt = $pdo->prepare("UPDATE reports SET ai_result = ? WHERE id = ?");
    $stmt->execute([$errorMsg, $id]);
    exit;
}

// 6. Save Success
$res = json_decode($response, true);
$out = $res['choices'][0]['message']['content'] ?? "AI processed but returned no text.";

$stmt = $pdo->prepare("UPDATE reports SET ai_result = ? WHERE id = ?");
$stmt->execute([$out, $id]);
?>