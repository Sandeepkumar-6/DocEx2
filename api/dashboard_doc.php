<?php
// api/doctor.php
header('Content-Type: application/json');
include 'db.php';

$action = $_GET['action'] ?? '';

// --- 1. FETCH PATIENT LIST ---
if ($action === 'list') {
    try {
        // "ORDER BY r.review_requested DESC" puts patients requesting help at the top.
        $stmt = $pdo->query("
            SELECT r.id, r.ai_result, r.doc_status, r.review_requested, p.name, p.age, p.gender 
            FROM reports r 
            JOIN patients p ON r.patient_id = p.id 
            ORDER BY r.review_requested DESC, r.id DESC
        ");
        $patients = $stmt->fetchAll();
        
        // Add simple severity flag for frontend styling
        foreach ($patients as &$p) {
            $text_lower = strtolower($p['ai_result'] ?? '');
            $critical = ['high', 'critical', 'severe', 'abnormal', 'positive', 'danger'];
            $p['is_critical'] = false;
            foreach ($critical as $word) {
                if (strpos($text_lower, $word) !== false) {
                    $p['is_critical'] = true;
                    break;
                }
            }
        }
        echo json_encode($patients);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// --- 2. FETCH SINGLE REPORT ---
if ($action === 'get_report') {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, p.name, p.age, p.gender 
            FROM reports r 
            JOIN patients p ON r.patient_id = p.id 
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $report = $stmt->fetch();
        echo json_encode($report ?: ['error' => 'Report not found']);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// --- 3. SAVE DOCTOR OPINION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['report_id'];
    $opinion = $_POST['doctor_opinion'];

    if ($id && $opinion) {
        try {
            $stmt = $pdo->prepare("UPDATE reports SET doctor_opinion = ?, doc_status = 'reviewed' WHERE id = ?");
            $stmt->execute([$opinion, $id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Missing data']);
    }
    exit;
}
?>