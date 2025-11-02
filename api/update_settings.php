<?php
// api/update_settings.php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$currency = strtoupper(trim($input['currency'] ?? 'PHP'));
$rows = intval($input['rows'] ?? 25);

// Basic validation
$allowed_currencies = ['PHP','USD','EUR'];
if (!in_array($currency, $allowed_currencies)) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Invalid currency.']);
    exit;
}
if ($rows <= 0 || $rows > 1000) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Invalid rows value.']);
    exit;
}

$pdo = getPDO();
$user_id = $_SESSION['user']['id'];

try {
    // Upsert into user_preferences (works for MySQL >= 5.7+)
    $stmt = $pdo->prepare("
      INSERT INTO user_preferences (user_id, currency, rows_per_page)
      VALUES (:user_id, :currency, :rows)
      ON DUPLICATE KEY UPDATE currency = VALUES(currency), rows_per_page = VALUES(rows_per_page), updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([':user_id'=>$user_id, ':currency'=>$currency, ':rows'=>$rows]);

    echo json_encode(['success'=>true, 'message'=>'Settings saved.', 'currency'=>$currency, 'rows'=>$rows]);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Save failed: ' . $ex->getMessage()]);
}
