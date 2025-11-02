<?php
// api/get_settings.php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');

$pdo = getPDO();
$user_id = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare("SELECT currency, rows_per_page FROM user_preferences WHERE user_id = :uid LIMIT 1");
    $stmt->execute([':uid'=>$user_id]);
    $row = $stmt->fetch();

    if ($row) {
        echo json_encode(['success'=>true, 'data'=>$row]);
    } else {
        // default values
        echo json_encode(['success'=>true, 'data'=>['currency'=>'PHP','rows_per_page'=>25]]);
    }
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Failed to retrieve settings.']);
}
