<?php
require('./fpd/fpdf.php');

$pdf = new FPDF('L','mm','Letter');
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(40,10,'Hello World!');
$pdf->Output();
?>