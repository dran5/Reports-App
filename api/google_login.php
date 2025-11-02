<?php
// api/google_login.php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (empty($input['id_token'])) {
    echo json_encode(['success'=>false, 'message'=>'Missing id_token']);
    exit;
}
$id_token = $input['id_token'];

// Verify the token with Google tokeninfo endpoint using cURL
$verify_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $verify_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 || !$res) {
    echo json_encode(['success'=>false, 'message'=>'Invalid token (verification failed)']);
    exit;
}
$payload = json_decode($res, true);

// Check audience
if (empty($payload['aud']) || $payload['aud'] !== GOOGLE_CLIENT_ID) {
    echo json_encode(['success'=>false, 'message'=>'Invalid client ID']);
    exit;
}

// Extract user info
$google_id = $payload['sub'] ?? null;
$email = $payload['email'] ?? null;
$name = $payload['name'] ?? null;
$picture = $payload['picture'] ?? null;

if (!$google_id || !$email) {
    echo json_encode(['success'=>false, 'message'=>'Invalid token payload']);
    exit;
}

$pdo = getPDO();

// Insert or update user
$stmt = $pdo->prepare("SELECT id FROM users WHERE google_id = :g");
$stmt->execute(['g'=>$google_id]);
$user = $stmt->fetch();

if ($user) {
    $stmt = $pdo->prepare("UPDATE users SET email=:email, name=:name, picture=:pic WHERE google_id=:g");
    $stmt->execute(['email'=>$email, 'name'=>$name, 'pic'=>$picture, 'g'=>$google_id]);
    $user_id = $user['id'];
} else {
    $stmt = $pdo->prepare("INSERT INTO users (google_id, email, name, picture) VALUES (:g, :email, :name, :pic)");
    $stmt->execute(['g'=>$google_id, 'email'=>$email, 'name'=>$name, 'pic'=>$picture]);
    $user_id = $pdo->lastInsertId();
}

// create session
$_SESSION['user'] = [
    'id' => $user_id,
    'google_id' => $google_id,
    'email' => $email,
    'name' => $name,
    'picture' => $picture
];

echo json_encode(['success'=>true]);
