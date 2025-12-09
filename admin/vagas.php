<?php
ob_start();
session_start();
// Caminho para a conexão com o banco de dados. 
// Certifique-se de que este caminho está correto para acessar seu arquivo de conexão.
require '../db.php'; 

// ----------------------------------------------------------------
// 1. OBTENÇÃO DE DADOS E APLICAÇÃO DE FILTROS
// ----------------------------------------------------------------

$hoje = date('Y-m-d H:i:s');
// Captura o status do filtro da URL (se existir)
$filtro_status = $_GET['status'] ?? ''; 

$where_condicao = ""; // Para filtros que usam colunas diretas (data_validade)
$having_condicao = ""; // Para filtros que usam colunas agregadas (total_candidaturas, total_aprovados)

// Lógica de Filtro Aprimorada
if ($filtro_status === 'ativa') {
    // Filtro de data (WHERE)
    // ATENÇÃO: Adicionamos 'p.' na coluna para resolver o erro SQL anterior, garantindo que
    // o WHERE se aplique na tabela principal (produtos).
    $where_condicao = "WHERE p.data_validade >= '{$hoje}'"; 
} elseif ($filtro_status === 'expirada') {
    // Filtro de data (WHERE)
    $where_condicao = "WHERE p.data_validade < '{$hoje}'";
} elseif ($filtro_status === 'candidaturas') {
    // Filtro de contagem (HAVING)
    $having_condicao = "HAVING total_candidaturas > 0"; 
} elseif ($filtro_status === 'aprovados') {
    // Filtro de contagem (HAVING)
    $having_condicao = "HAVING total_aprovados > 0"; 
}


// Aplica o filtro na consulta principal
// A consulta é estruturada para aplicar o WHERE/HAVING na subconsulta (VagasEstatistica)
$sql_principal = "
    SELECT *
    FROM (
        SELECT 
            p.*, 
            COUNT(c.id) AS total_candidaturas,
            SUM(CASE WHEN c.status = 'Aprovado' THEN 1 ELSE 0 END) AS total_aprovados 
        FROM produtos p
        LEFT JOIN compras c ON c.produto_id = p.id
        -- O WHERE condicional deve ser aplicado DENTRO da subconsulta, 
        -- se o filtro for de data.
        {$where_condicao}
        GROUP BY p.id
    ) AS VagasEstatistica
    -- O HAVING condicional deve ser aplicado FORA da subconsulta, 
    -- se o filtro for de contagem.
    {$having_condicao}
    ORDER BY data_validade DESC;
";

$result_vagas = $conn->query($sql_principal);

if(!$result_vagas) {
    error_log("Erro na consulta SQL de Vagas: " . $conn->error);
    // Exibe o erro SQL para depuração
    die("Erro ao carregar vagas. Por favor, verifique os logs. Erro SQL: " . $conn->error . " | Query: " . $sql_principal); 
}

// ----------------------------------------------------------------
// 2. CÁLCULO DE ESTATÍSTICAS GERAIS (Para os Cards)
// ----------------------------------------------------------------

$vagasArrayStatus = [];
$total_vagas = 0;
$total_vagas_ativas = 0;
$total_vagas_expiradas = 0;
$total_candidaturas_geral = 0;
$total_aprovados_geral = 0;

// Consulta para Estatísticas Gerais (não filtrada)
// Usamos LEFT JOIN para garantir que as vagas sem candidaturas sejam contadas.
$sql_stats = "
    SELECT 
        COUNT(p.id) AS total_vagas,
        SUM(CASE WHEN p.data_validade >= '{$hoje}' THEN 1 ELSE 0 END) AS total_vagas_ativas,
        SUM(CASE WHEN p.data_validade < '{$hoje}' THEN 1 ELSE 0 END) AS total_vagas_expiradas,
        COUNT(c.id) AS total_candidaturas_geral,
        SUM(CASE WHEN c.status = 'Aprovado' THEN 1 ELSE 0 END) AS total_aprovados_geral
    FROM produtos p
    LEFT JOIN compras c ON c.produto_id = p.id
";

$result_stats = $conn->query($sql_stats);
// Verifica se a consulta retornou dados
if ($result_stats && $result_stats->num_rows > 0) {
    $stats = $result_stats->fetch_assoc();
} else {
    // Define como zero se não houver resultados
    $stats = [];
}

$total_vagas = (int)($stats['total_vagas'] ?? 0);
$total_vagas_ativas = (int)($stats['total_vagas_ativas'] ?? 0);
$total_vagas_expiradas = (int)($stats['total_vagas_expiradas'] ?? 0);
$total_candidaturas_geral = (int)($stats['total_candidaturas_geral'] ?? 0);
$total_aprovados_geral = (int)($stats['total_aprovados_geral'] ?? 0);

// Calcular Vagas que tiveram pelo menos 1 aprovado (para o card extra)
$sql_vagas_com_aprovados = "SELECT COUNT(DISTINCT p.id) as count FROM produtos p JOIN compras c ON c.produto_id = p.id WHERE c.status = 'Aprovado'";
$result_vagas_aprov = $conn->query($sql_vagas_com_aprovados);
$count_vagas_aprovadas = ($result_vagas_aprov && $result_vagas_aprov->num_rows > 0) ? (int)$result_vagas_aprov->fetch_assoc()['count'] : 0;


// Processar os resultados filtrados da tabela
while($row = $result_vagas->fetch_assoc()) {
    $row['total_candidaturas'] = (int)($row['total_candidaturas'] ?? 0);
    $row['total_aprovados'] = (int)($row['total_aprovados'] ?? 0);

    // Determinação do Status para exibição
    if(isset($row['data_validade']) && $row['data_validade'] >= $hoje) {
        $row['status_vaga'] = 'ativa';
    } else {
        $row['status_vaga'] = 'expirada';
    }

    $vagasArrayStatus[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relatórios de Vagas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />

<style>
    :root {
        --primary-color: #0d6efd;
        --success-color: #28a745;
        --info-color: #17a2b8;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
        --secondary-color: #6c757d;
        --background: #f4f6f9;
    }
    body {
        background: var(--background);
        font-family: 'Poppins', sans-serif;
    }
    .card-stats {
        border-radius: 8px; /* Ligeiramente menor */
        transition: transform 0.3s ease;
    }
    .card-stats:hover {
        transform: translateY(-3px); /* Efeito menor */
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }
    .icon-box {
        font-size: 1.8rem; /* Ícone menor */
        padding: 8px; /* Preenchimento menor */
        border-radius: 6px;
        opacity: 0.8;
    }
    /* Estilo para garantir que o número e o título caibam bem nos cards compactos */
    .card-stats h6 {
        font-size: 0.75rem; /* Título bem pequeno */
        font-weight: 600;
        margin-bottom: 0.2rem !important;
    }
    .card-stats .small-number {
        font-size: 1.8rem; /* Número reduzido */
        font-weight: 700;
    }

    .filter-btn.active {
        font-weight: bold;
        text-decoration: underline;
        /* Adiciona um estilo visual mais forte para o botão ativo */
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.5); 
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 10px 15px;
    }
    .badge-status {
        padding: 0.3em 0.6em; /* Reduz o padding do badge na tabela */
        font-weight: 600;
        min-width: 70px; /* Reduz a largura mínima */
        display: inline-block;
        text-align: center;
        font-size: 0.75rem;
    }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php"><i class="fa-solid fa-gauge-high me-2"></i> Dashboard Admin</a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-outline-light me-2"><i class="fa-solid fa-arrow-left me-1"></i> Voltar</a>
            <a href="logout.php" class="btn btn-danger"><i class="fa-solid fa-right-from-bracket me-1"></i> Sair</a>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h1 class="mb-5 text-center fw-bold text-dark"><i class="fa-solid fa-chart-line me-2"></i> Relatórios de Vagas</h1>

    <div class="row g-3 mb-5 justify-content-center"> 
        
        <div class="col-lg-2 col-md-4 col-6">
            <div class="card card-stats text-white bg-primary shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center p-3">
                    <div>
                        <h6 class="card-title text-uppercase mb-0">Total Vagas</h6>
                        <div class="small-number fw-bold"><?= $total_vagas ?></div>
                    </div>
                    <i class="fa-solid fa-list-check icon-box"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-6">
            <div class="card card-stats text-white bg-success shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center p-3">
                    <div>
                        <h6 class="card-title text-uppercase mb-0">Vagas Ativas</h6>
                        <div class="small-number fw-bold"><?= $total_vagas_ativas ?></div>
                    </div>
                    <i class="fa-solid fa-circle-check icon-box"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-6">
            <div class="card card-stats text-white bg-danger shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center p-3">
                    <div>
                        <h6 class="card-title text-uppercase mb-0">Vagas Expiradas</h6>
                        <div class="small-number fw-bold"><?= $total_vagas_expiradas ?></div>
                    </div>
                    <i class="fa-solid fa-clock-rotate-left icon-box"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6">
            <div class="card card-stats text-white bg-info shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center p-3">
                    <div>
                        <h6 class="card-title text-uppercase mb-0">Candidaturas</h6>
                        <div class="small-number fw-bold"><?= $total_candidaturas_geral ?></div>
                    </div>
                    <i class="fa-solid fa-users icon-box"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6">
            <div class="card card-stats text-white bg-warning shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center p-3">
                    <div>
                        <h6 class="card-title text-uppercase mb-0">Aprovados</h6>
                        <div class="small-number fw-bold"><?= $total_aprovados_geral ?></div>
                    </div>
                    <i class="fa-solid fa-graduation-cap icon-box"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-6">
            <div class="card card-stats text-white bg-secondary shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center p-3">
                    <div>
                        <h6 class="card-title text-uppercase mb-0">Vagas c/ Aprov.</h6>
                        <div class="small-number fw-bold"><?= $count_vagas_aprovadas ?></div>
                    </div>
                    <i class="fa-solid fa-trophy icon-box"></i>
                </div>
            </div>
        </div>
        
    </div>

    <div class="card shadow-lg mb-4">
        <div class="card-body">
            <h5 class="card-title"><i class="fa-solid fa-filter me-2"></i> Filtrar Tabela de Vagas</h5>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="?status=" class="btn btn-outline-dark filter-btn <?= empty($filtro_status) ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-ul me-1"></i> Todas as Vagas (<?= $total_vagas ?>)
                </a>
                <a href="?status=ativa" class="btn btn-outline-success filter-btn <?= $filtro_status == 'ativa' ? 'active' : '' ?>">
                    <i class="fa-solid fa-check me-1"></i> Apenas Ativas (<?= $total_vagas_ativas ?>)
                </a>
                <a href="?status=expirada" class="btn btn-outline-danger filter-btn <?= $filtro_status == 'expirada' ? 'active' : '' ?>">
                    <i class="fa-solid fa-ban me-1"></i> Apenas Expiradas (<?= $total_vagas_expiradas ?>)
                </a>
                <a href="?status=candidaturas" class="btn btn-outline-info filter-btn <?= $filtro_status == 'candidaturas' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users-viewfinder me-1"></i> Com Candidaturas
                </a>
                <a href="?status=aprovados" class="btn btn-outline-warning filter-btn <?= $filtro_status == 'aprovados' ? 'active' : '' ?>">
                    <i class="fa-solid fa-thumbs-up me-1"></i> Com Atletas Aprovados
                </a>
            </div>
        </div>
    </div>


    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="fa-solid fa-table me-2"></i> Vagas em Tabela</h3>
            <div class="d-flex align-items-center">
                <button id="btnExportExcel" class="btn btn-success btn-sm fw-bold me-2" title="Exportar para Excel (XLSX)">
                    <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
                </button>
                <button id="btnExportPDF" class="btn btn-danger btn-sm fw-bold me-2" title="Exportar para PDF">
                    <i class="fa-solid fa-file-pdf me-1"></i> Exportar PDF
                </button>
                <a href="cadastrar_vaga.php" class="btn btn-warning btn-sm fw-bold text-dark">
                    <i class="fa-solid fa-plus me-1"></i> Criar Nova Vaga
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle small" id="tableVagas"> 
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nome da Vaga</th>
                            <th>Modalidade</th>
                            <th>Validade</th>
                            <th>Status</th>
                            <th><i class="fa-solid fa-users me-1"></i> Submetidas</th>
                            <th><i class="fa-solid fa-user-check me-1"></i> Aprovados</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vagasArrayStatus as $row): ?>
                        <?php
                            $status_info = [];
                            if($row['status_vaga'] == 'ativa') {
                                $status_info = ['cor' => 'success', 'texto' => 'Ativa', 'icone' => 'fa-play'];
                            } else {
                                $status_info = ['cor' => 'danger', 'texto' => 'Expirada', 'icone' => 'fa-stop'];
                            }
                            $submetidas_color = $row['total_candidaturas'] > 0 ? 'info' : 'light text-muted';
                            $aprovados_color = $row['total_aprovados'] > 0 ? 'success' : 'light text-muted';
                        ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['nome'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['modalidade'] ?? '') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['data_validade'] ?? '')) ?></td>
                            <td>
                                <span class="badge badge-status bg-<?= $status_info['cor'] ?>">
                                    <i class="fa-solid <?= $status_info['icone'] ?> me-1"></i> <?= $status_info['texto'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $submetidas_color ?> px-3 py-2 fw-bold">
                                    <?= $row['total_candidaturas'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $aprovados_color ?> px-3 py-2 fw-bold">
                                    <?= $row['total_aprovados'] ?>
                                </span>
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="editar_vaga.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm me-1" title="Editar Vaga">
                                    <i class="fa-solid fa-edit"></i>
                                </a>
                                <a href="../ver_vagas.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-info btn-sm me-1" title="Visualizar (Público)">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="excluir_vaga.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" title="Excluir Vaga" onclick="return confirm('Tem certeza que deseja excluir esta vaga e todas as candidaturas associadas?')">
                                    <i class="fa-solid fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($vagasArrayStatus) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted p-4">Nenhuma vaga encontrada com o filtro atual.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Função utilitária para obter o texto do filtro ativo (usado no título do relatório)
    function getFiltrosText() {
        const activeFilter = document.querySelector('.filter-btn.active');
        const defaultText = "Todas as Vagas";
        if (activeFilter) {
            // Remove o contador de vagas no final do texto do botão (e.g., remove "(12)")
            return `Filtro Aplicado: ${activeFilter.innerText.replace(/\s\(\d+\)$/, '').trim()}`; 
        }
        return `Filtro Aplicado: ${defaultText}`;
    }

    // O NÚMERO DE COLUNAS DA TABELA VISÍVEL QUE SERÃO EXPORTADAS (ID até Aprovados)
    const NUM_COLS_VAGAS = 7; 
    
    
    // =================================================================
    // 🎨 EXPORTAR PDF (Utilizando jsPDF e AutoTable)
    // =================================================================

    document.getElementById('btnExportPDF').addEventListener('click', () => {
        const { jsPDF } = window.jspdf;
        // 'l' para layout paisagem (landscape)
        const doc = new jsPDF('l', 'mm', 'a4'); 
        
        doc.setFontSize(18);
        doc.text("Relatório de Vagas", 14, 16);
        
        doc.setFontSize(10);
        doc.text(getFiltrosText(), 14, 24);
        
        const table = document.getElementById('tableVagas');
        
        // 1. Captura cabeçalhos (excluindo 'Ações')
        const headers = Array.from(table.querySelectorAll('thead th'))
            .slice(0, NUM_COLS_VAGAS)
            // Limpa o texto dos cabeçalhos, removendo ícones e contadores
            .map(th => th.textContent.replace(/\s\(\d+\)|[\s\r\n]+|fa-users|fa-user-check/g, ' ').trim());
            
        // 2. Captura dados do corpo da tabela (excluindo 'Ações')
        const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr => 
            Array.from(tr.querySelectorAll('td'))
                .slice(0, NUM_COLS_VAGAS)
                // Captura apenas o texto puro da célula (o innerText funciona bem com badges)
                .map(td => td.innerText.trim())
        ).filter(row => row.length > 1);

        doc.autoTable({
            head: [headers],
            body: rows,
            startY: 35, // Posição inicial Y após o cabeçalho/título
            headStyles: { 
                fillColor: [13, 110, 253], 
                textColor: 255,
                fontSize: 9 
            },
            styles: { 
                fontSize: 8,
                cellPadding: 2 
            }
        });

        doc.save('relatorio_vagas.pdf');
    });

    // =================================================================
    // 📊 EXPORTAR EXCEL (Utilizando SheetJS/XLSX)
    // =================================================================
    
    document.getElementById('btnExportExcel').addEventListener('click', () => {
        const table = document.getElementById('tableVagas');
        const wb = XLSX.utils.book_new();

        // 1. Captura cabeçalhos
        const headers = Array.from(table.querySelectorAll('thead th'))
            .slice(0, NUM_COLS_VAGAS)
            .map(th => th.textContent.replace(/\s\(\d+\)|[\s\r\n]+|fa-users|fa-user-check/g, ' ').trim()); 
            
        // 2. Captura dados
        const data = Array.from(table.querySelectorAll('tbody tr')).map(tr => 
            Array.from(tr.querySelectorAll('td'))
                .slice(0, NUM_COLS_VAGAS)
                .map(td => td.innerText.trim())
        ).filter(row => row.length > 1);
        
        // 3. Monta a matriz de dados para exportação
        data.unshift(headers); 
        data.unshift([getFiltrosText()]); // Adiciona o filtro como a primeira linha
        
        const ws = XLSX.utils.aoa_to_sheet(data);

        // Mescla a célula do título (Filtro Aplicado)
        if (data.length > 0) {
            ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: headers.length - 1 } }]; 
        }

        XLSX.utils.book_append_sheet(wb, ws, "Vagas");
        XLSX.writeFile(wb, 'relatorio_vagas.xlsx');
    });
</script>

</body>
</html>