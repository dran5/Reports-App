<?php
// api/update_profile.php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // fallback to form POST
    $input = $_POST;
}

$name = trim($input['name'] ?? '');

if ($name === '') {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Name cannot be empty.']);
    exit;
}

$pdo = getPDO();

try {
    $stmt = $pdo->prepare("UPDATE users SET name = :name WHERE id = :id");
    $stmt->execute([':name'=>$name, ':id'=>$_SESSION['user']['id']]);

    // update session
    $_SESSION['user']['name'] = $name;

    echo json_encode(['success'=>true, 'message'=>'Profile updated.', 'name'=>$name]);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Update failed: ' . $ex->getMessage()]);
}
