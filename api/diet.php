<?php
// api/diet.php
ob_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(0); 

// Debug logging helper
function logDebug($msg) {
    file_put_contents("debug_log.txt", date('H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

$response = [];

try {
    if (!file_exists('db.php')) throw new Exception("db.php missing.");
    include 'db.php'; 

    $id = $_GET['id'] ?? 0;
    $type = $_GET['type'] ?? 'Vegetarian';

    // 1. Fetch Report Data
    $context = "General health checkup.";
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT ai_result, extracted_text FROM reports WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            // Combine and limit text length to avoid token limits
            $rawText = $row['ai_result'] . " " . $row['extracted_text'];
            $context = substr(strip_tags($rawText), 0, 2000);
        }
    }

    // 2. Improved Prompt - Explicitly asks for raw JSON
    $prompt = "
    You are an expert Indian Nutritionist.
    Patient Context: $context
    Diet Preference: $type
    
    Task: Create a simple 1-day Indian meal plan.
    
    IMPORTANT: RETURN ONLY RAW JSON. NO MARKDOWN. NO EXPLANATION TEXT.
    
    Expected JSON Structure:
    {
      \"condition\": \"(Identify condition from context)\",
      \"diet_html\": \"<ul><li><b>Breakfast:</b> [Item]</li><li><b>Lunch:</b> [Item]</li><li><b>Dinner:</b> [Item]</li></ul>\",
      \"tips\": [\"Tip 1\", \"Tip 2\", \"Tip 3\"],
      \"summary_hindi\": \"(One encouraging sentence in Hindi)\"
    }
    ";

    // 3. Call Groq API (Switched to Llama 3 70B for stability)
    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    $payload = [
        "model" => "openai/gpt-oss-120b", 
        "messages" => [
            ["role" => "system", "content" => "You are a JSON generator. Output only valid JSON."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.5 // Lower temp = more consistent formatting
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $result = curl_exec($ch);
    
    if(curl_errno($ch)) {
        throw new Exception("Network Error: " . curl_error($ch));
    }
    curl_close($ch);

    // 4. Parse & Clean Response
    $aiData = json_decode($result, true);
    
    // Check if Groq returned an error message inside the JSON
    if (isset($aiData['error'])) {
        logDebug("Groq API Error: " . json_encode($aiData['error']));
        throw new Exception("AI Service Error: " . $aiData['error']['message']);
    }

    $rawContent = $aiData['choices'][0]['message']['content'] ?? "";
    
    if (empty($rawContent)) {
        logDebug("Empty content received. Full dump: " . $result);
        throw new Exception("AI returned empty content.");
    }

    // Strip Markdown code blocks if present (```json ... ```)
    $cleanJson = preg_replace('/```json|```/', '', $rawContent);
    $cleanJson = trim($cleanJson);

    $parsedData = json_decode($cleanJson, true);

    // 5. Fallback Mechanism (Prevent crash if AI fails)
    if (!$parsedData) {
        logDebug("JSON Parse Failed. Raw: " . $rawContent);
        // Return a safe default instead of an error
        $response = [
            "condition" => "General Health (Fallback)",
            "diet_html" => "<ul><li><b>Breakfast:</b> Poha or Upma with Tea</li><li><b>Lunch:</b> 2 Roti, Dal, and Green Sabzi</li><li><b>Dinner:</b> Khichdi or Vegetable Soup</li></ul>",
            "tips" => ["Drink 8 glasses of water daily", "Walk for 20 mins after meals", "Avoid processed sugar"],
            "summary_hindi" => "Kripya ye sadharan diet follow karein jab tak AI connect ho."
        ];
    } else {
        $response = $parsedData;
    }

} catch (Exception $e) {
    logDebug("Critical Error: " . $e->getMessage());
    $response = [
        "error" => $e->getMessage(),
        "diet_html" => "<p style='color:red'>Unable to generate plan. Please try again.</p>",
        "condition" => "System Error",
        "tips" => [],
        "summary_hindi" => "Connection failed."
    ];
}

ob_clean();
echo json_encode($response);
?>