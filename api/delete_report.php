<?php
// api/delete_report.php
declare(strict_types=1);

require_once __DIR__ . '/../config.php'; // expects config.php to call session_start(), provide require_login() and getPDO()

// Ensure user is logged in (require_login() will send 401 JSON and exit if not)
require_login();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json', true, 405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// Read raw JSON body
$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    header('Content-Type: application/json', true, 400);
    echo json_encode(['success' => false, 'message' => 'Empty request body']);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    header('Content-Type: application/json', true, 400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

if (empty($payload['id'])) {
    header('Content-Type: application/json', true, 400);
    echo json_encode(['success' => false, 'message' => 'Missing id']);
    exit;
}

$id = intval($payload['id']);
if ($id <= 0) {
    header('Content-Type: application/json', true, 400);
    echo json_encode(['success' => false, 'message' => 'Invalid id value']);
    exit;
}

try {
    // Get PDO from your config
    $pdo = getPDO();

    // OPTIONAL: check authorization / ownership here if you need to restrict deletes
    // $user = $_SESSION['user']; // e.g. check $user['role'] or user ownership of the report

    // Perform delete (hard delete). To use soft-delete, update a deleted_at column instead.
    $stmt = $pdo->prepare('DELETE FROM reports WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        header('Content-Type: application/json', true, 200);
        echo json_encode(['success' => true, 'message' => 'Deleted', 'id' => $id]);
        exit;
    } else {
        // No rows deleted -> not found
        header('Content-Type: application/json', true, 404);
        echo json_encode(['success' => false, 'message' => 'Row not found or already deleted']);
        exit;
    }
} catch (Throwable $e) {
    // Log internal error for debugging (do not expose internal error details to clients in production)
    error_log('delete_report.php error: ' . $e->getMessage());
    header('Content-Type: application/json', true, 500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
