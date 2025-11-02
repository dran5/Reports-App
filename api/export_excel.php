<?php
// api/export_excel.php
require_once __DIR__ . '/../config.php';
require_login();
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$pdo = getPDO();
$stmt = $pdo->query("SELECT date, category, source, amount, visitors, notes FROM reports ORDER BY date DESC");
$rows = $stmt->fetchAll();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1','Date');
$sheet->setCellValue('B1','Category');
$sheet->setCellValue('C1','Source');
$sheet->setCellValue('D1','Amount');
$sheet->setCellValue('E1','Visitors');
$sheet->setCellValue('F1','Notes');

$r = 2;
foreach ($rows as $row) {
    $sheet->setCellValue('A'.$r, $row['date']);
    $sheet->setCellValue('B'.$r, $row['category']);
    $sheet->setCellValue('C'.$r, $row['source']);
    $sheet->setCellValue('D'.$r, $row['amount']);
    $sheet->setCellValue('E'.$r, $row['visitors']);
    $sheet->setCellValue('F'.$r, $row['notes']);
    $r++;
}

$filename = 'reports_export_'.date('Ymd_His').'.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
