<?php
// api/generate_pdf.php
require_once __DIR__ . '/../config.php';
require_login();

require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';  // make sure this file exists

$pdo = getPDO();
$stmt = $pdo->query("SELECT date, category, source, amount, visitors FROM reports ORDER BY date DESC LIMIT 200");
$rows = $stmt->fetchAll();

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,'Reports Export',0,1,'C');
        $this->Ln(2);
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

// table header
$pdf->SetFillColor(240,240,240);
$pdf->Cell(30,8,'Date',1,0,'C', true);
$pdf->Cell(35,8,'Category',1,0,'C', true);
$pdf->Cell(55,8,'Source',1,0,'C', true);
$pdf->Cell(30,8,'Amount',1,0,'C', true);
$pdf->Cell(30,8,'Visitors',1,1,'C', true);

foreach ($rows as $r) {
    $pdf->Cell(30,7, $r['date'],1);
    $pdf->Cell(35,7, substr($r['category'],0,20),1);
    $pdf->Cell(55,7, substr($r['source'],0,30),1);
    $pdf->Cell(30,7, number_format($r['amount'],2),1,0,'R');
    $pdf->Cell(30,7, $r['visitors'] ?? '',1,1,'R');
}

$pdf->Output('I', 'reports_'.date('Ymd_His').'.pdf');
exit;
