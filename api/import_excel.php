<?php
// api/import_excel.php
require_once __DIR__ . '/../config.php';
require_login();

if (empty($_FILES['excel'])) {
    echo json_encode(['success'=>false, 'message'=>'No file uploaded']);
    exit;
}

$upload = $_FILES['excel'];
$allowed = ['application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
// Basic check (you could do more)
$tmp = $upload['tmp_name'];

require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $spreadsheet = IOFactory::load($tmp);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);

    $pdo = getPDO();
    $pdo->beginTransaction();
    $insert = $pdo->prepare("INSERT INTO reports (date, category, source, amount, visitors, notes) VALUES (:date, :category, :source, :amount, :visitors, :notes)");

    // Assume first row is header. Columns: Date | Category | Source | Amount | Visitors | Notes
    $first = true;
    foreach ($rows as $r) {
        if ($first) { $first=false; continue; }
        $date = !empty($r['A']) ? date('Y-m-d', strtotime($r['A'])) : null;
        if (!$date) continue; // skip invalid
        $category = $r['B'] ?? '';
        $source = $r['C'] ?? '';
        $amount = is_numeric($r['D']) ? $r['D'] : 0;
        $visitors = is_numeric($r['E']) ? intval($r['E']) : null;
        $notes = $r['F'] ?? null;

        $insert->execute([
            ':date'=>$date,
            ':category'=>$category,
            ':source'=>$source,
            ':amount'=>$amount,
            ':visitors'=>$visitors,
            ':notes'=>$notes
        ]);
    }
    $pdo->commit();
    echo json_encode(['success'=>true, 'message'=>'Import completed']);
} catch (Exception $ex) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>'Import failed: '.$ex->getMessage()]);
}
