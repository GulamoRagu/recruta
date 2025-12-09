// Conteúdo de exportar_pdf.php
<?php
// Certifique-se de que a biblioteca de PDF está incluída aqui
// Exemplo: require('fpdf/fpdf.php');
require '../db.php'; 

// 1. CAPTURAR E CONFIGURAR O FILTRO DE ORDENAÇÃO
$ordem = $_GET['ordem'] ?? 'data'; 
$order_by_sql = "ORDER BY u.criado_em DESC"; 

if ($ordem === 'nome_asc') {
    $order_by_sql = "ORDER BY u.nome_completo ASC";
} elseif ($ordem === 'nome_desc') {
    $order_by_sql = "ORDER BY u.nome_completo DESC";
}

// 2. BUSCAR OS DADOS (A MESMA CONSULTA USADA NA TELA)
// Usamos GROUP_CONCAT(p.nome SEPARATOR ', ') para formatar as vagas para o PDF/Excel
$recrutadores_export = $conn->query("
    SELECT 
        u.id, 
        u.nome_completo, 
        u.email, 
        u.telefone,
        GROUP_CONCAT(p.nome SEPARATOR ', ') AS vagas_listadas, /* Lista de vagas */
        u.criado_em
    FROM usuarios u
    LEFT JOIN produtos p ON p.recrutador_id = u.id
    WHERE u.tipo='vendedor'
    GROUP BY u.id
    {$order_by_sql} /* APLICA O FILTRO DE ORDENAÇÃO AQUI */
");

// 3. INICIALIZAR A CLASSE PDF (Exemplo FPDF)

/*
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// Cabeçalho do PDF
$pdf->Cell(20, 10, 'ID', 1);
$pdf->Cell(50, 10, 'Nome', 1);
$pdf->Cell(60, 10, 'Email', 1);
$pdf->Cell(40, 10, 'Vagas', 1);
$pdf->Ln();

// 4. PREENCHER O PDF COM OS DADOS FILTRADOS
$pdf->SetFont('Arial', '', 10);
while($row = $recrutadores_export->fetch_assoc()) {
    $pdf->Cell(20, 10, $row['id'], 1);
    $pdf->Cell(50, 10, iconv('UTF-8', 'ISO-8859-1', $row['nome_completo']), 1);
    $pdf->Cell(60, 10, $row['email'], 1);
    $pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', $row['vagas_listadas'] ?? 'Nenhuma'), 1);
    $pdf->Ln();
}

// 5. SAÍDA
$pdf->Output('D', 'recrutadores_filtrados_' . date('Ymd_His') . '.pdf'); 
*/

exit;
?>