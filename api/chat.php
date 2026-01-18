<?php
// api/chat.php
include 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$message = $_POST['message'] ?? $input['message'] ?? '';
$id = (int)($_POST['report_id'] ?? $input['report_id'] ?? 0);

if (!$message || !$id) {
    echo json_encode(["reply" => "I didn't catch that. Please try again."]);
    exit;
}

// 1. Fetch Context
$stmt = $pdo->prepare("SELECT ai_result FROM reports WHERE id = ?");
$stmt->execute([$id]);
$ctx = $stmt->fetch()['ai_result'] ?? '';

// --- IMPROVED SYSTEM PROMPT ---
$systemPrompt = "You are a helpful medical assistant. "
              . "Context: $ctx. "
              . "Guidelines: Be friendly but extremely concise. "
              ."Carry casual talk if appropriate. "
              . "Answer the question directly based on the context. "
              . "Avoid long introductions or unnecessary explanations. "
              . "If the information isn't in the context, say so politely.";

// 2. Call AI with NEW MODEL
$data = [
    "model" => "openai/gpt-oss-120b", 
    "messages" => [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $message]
    ],
    "temperature" => 0.5, // Lower temperature helps keep it focused/on-point
    "max_tokens" => 150    // Limits the length of the response
];

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => 1, 
    CURLOPT_POST => 1, 
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => ["Content-Type: application/json", "Authorization: Bearer $apiKey"]
]);

$res = json_decode(curl_exec($ch), true);
$reply = $res['choices'][0]['message']['content'] ?? "I'm having trouble connecting.";

// 3. Log Chat
$stmt = $pdo->prepare("INSERT INTO chats (report_id, role, message) VALUES (?, ?, ?)");
$stmt->execute([$id, 'user', $message]);
$stmt->execute([$id, 'bot', $reply]);

echo json_encode(["reply" => $reply]);
?>