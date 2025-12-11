<?php
ob_start();
session_start();
require '../db.php'; // Caminho para a conexão com o banco de dados

// ----------------------------------------------------------------
// 1. INICIALIZAÇÃO, FILTRO E CONSULTAS GERAIS
// ----------------------------------------------------------------

// Mapeamento de status para segurança e exibição
$valid_statuses = [
    'pendente' => ['texto' => 'Pendente', 'cor' => 'warning text-dark', 'icone' => 'fa-hourglass-half', 'color_chart' => '#ffc107'],
    'aprovado' => ['texto' => 'Aprovado', 'cor' => 'success', 'icone' => 'fa-check-circle', 'color_chart' => '#28a745'],
    'rejeitado' => ['texto' => 'Rejeitado', 'cor' => 'danger', 'icone' => 'fa-times-circle', 'color_chart' => '#dc3545']
];

// Capturar filtros da query string
$filtro_status = isset($_GET['status']) && array_key_exists($_GET['status'], $valid_statuses) ? $_GET['status'] : '';
$filtro_modalidade = $conn->real_escape_string($_GET['modalidade'] ?? '');
$filtro_vaga_id = (int)($_GET['vaga_id'] ?? 0);
$filtro_atleta_id = (int)($_GET['atleta_id'] ?? 0);


// ----------------------------------------------------------------
// 2. CONSTRUÇÃO DA QUERY PRINCIPAL (TABELA)
// ----------------------------------------------------------------
$where_condicoes = ["u.tipo='cliente'"];

if ($filtro_status) {
    $where_condicoes[] = "c.status = '{$filtro_status}'";
}
if ($filtro_modalidade) {
    $where_condicoes[] = "p.modalidade = '{$filtro_modalidade}'";
}
if ($filtro_vaga_id > 0) {
    $where_condicoes[] = "p.id = {$filtro_vaga_id}";
}
if ($filtro_atleta_id > 0) {
    $where_condicoes[] = "u.id = {$filtro_atleta_id}";
}

$where_clause = "WHERE " . implode(" AND ", $where_condicoes);


$sql_candidatos = "
    SELECT u.id AS atleta_id, u.nome_completo, u.email, u.foto_perfil, 
           c.id AS compra_id, c.status, c.data_compra,
           p.nome AS nome_vaga, p.modalidade
    FROM compras c
    INNER JOIN usuarios u ON u.id = c.cliente_id
    INNER JOIN produtos p ON c.produto_id = p.id
    {$where_clause}
    ORDER BY c.data_compra DESC
";

$candidatos = $conn->query($sql_candidatos);

if (!$candidatos) {
    error_log("Erro na consulta SQL: " . $conn->error);
    die("Erro ao carregar candidatos. Por favor, verifique os logs.");
}

// ----------------------------------------------------------------
// 3. CONTAGEM DE STATUS PARA CARDS E GRÁFICO PIZZA (Não filtrada)
// ----------------------------------------------------------------
$resultAllStatus = $conn->query("
    SELECT c.status, COUNT(c.id) as total
    FROM compras c
    INNER JOIN usuarios u ON u.id = c.cliente_id
    WHERE u.tipo='cliente'
    GROUP BY c.status
");

$statusCounts = ['pendente'=>0,'aprovado'=>0,'rejeitado'=>0];
$total_candidaturas = 0;

if ($resultAllStatus) {
    while($row = $resultAllStatus->fetch_assoc()){
        $statusKey = strtolower($row['status']);
        if(isset($statusCounts[$statusKey])) {
            $statusCounts[$statusKey] = (int)$row['total'];
        }
    }
    $total_candidaturas = array_sum($statusCounts);
}

// ----------------------------------------------------------------
// 4. CANDIDATURAS POR MODALIDADE (Para Gráfico de Barras e Filtro)
// ----------------------------------------------------------------
$sql_modalidade = "
    SELECT p.modalidade, COUNT(c.id) as total
    FROM compras c
    INNER JOIN usuarios u ON u.id = c.cliente_id
    INNER JOIN produtos p ON c.produto_id = p.id
    WHERE u.tipo='cliente'
    GROUP BY p.modalidade
    ORDER BY total DESC
";
$result_modalidade = $conn->query($sql_modalidade);
$modalidade_counts = [];
while ($row = $result_modalidade->fetch_assoc()) {
    $modalidade_counts[$row['modalidade']] = (int)$row['total'];
}


// ----------------------------------------------------------------
// 5. CANDIDATURAS POR VAGA (Para Gráfico de Barras e Filtro)
// ----------------------------------------------------------------
$sql_vaga = "
    SELECT p.id, p.nome, COUNT(c.id) as total
    FROM compras c
    INNER JOIN usuarios u ON u.id = c.cliente_id
    INNER JOIN produtos p ON c.produto_id = p.id
    WHERE u.tipo='cliente'
    GROUP BY p.id, p.nome
    ORDER BY total DESC
";
$result_vaga = $conn->query($sql_vaga);
$vaga_counts = [];
while ($row = $result_vaga->fetch_assoc()) {
    $vaga_counts[] = [
        'id' => $row['id'],
        'nome' => $row['nome'],
        'total' => (int)$row['total']
    ];
}


// ----------------------------------------------------------------
// 6. CANDIDATURAS POR ATLETA (Para Gráfico/Tabela de Resultados Finais)
// ----------------------------------------------------------------
$sql_atleta_stats = "
    SELECT u.id, u.nome_completo, 
        COUNT(c.id) AS total_candidaturas,
        SUM(CASE WHEN c.status = 'Aprovado' THEN 1 ELSE 0 END) AS total_aprovados
    FROM usuarios u
    JOIN compras c ON c.cliente_id = u.id
    WHERE u.tipo='cliente'
    GROUP BY u.id, u.nome_completo
    ORDER BY total_aprovados DESC, total_candidaturas DESC
";
$result_atleta_stats = $conn->query($sql_atleta_stats);
$atleta_stats = [];
while ($row = $result_atleta_stats->fetch_assoc()) {
    $atleta_stats[] = $row;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Candidatos - Relatórios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script> 

    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --background: #f4f6f9;
        }
        body {
            background: var(--background);
            font-family: 'Poppins', sans-serif;
        }
        .card-stats {
            border-radius: 12px;
            transition: transform 0.3s ease;
        }
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .icon-box {
            font-size: 2.5rem;
            padding: 10px;
            border-radius: 8px;
            opacity: 0.8;
        }
        .card-chart {
            border-radius: 12px;
            min-height: 350px;
        }
        .img-profile-sm {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
        .badge-status {
            padding: 0.5em 0.8em;
            font-weight: 600;
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
    <h1 class="mb-5 text-center fw-bold text-dark"><i class="fa-solid fa-chart-bar me-2"></i> Relatório Geral de Candidaturas</h1>
    
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="card card-stats text-white bg-primary shadow-lg">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-uppercase mb-0">Total Submetidas</h5>
                        <h2 class="display-4 fw-bold"><?= $total_candidaturas ?></h2>
                    </div>
                    <i class="fa-solid fa-users icon-box"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-stats text-white bg-warning shadow-lg">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-uppercase mb-0">Em Análise (Pendente)</h5>
                        <h2 class="display-4 fw-bold"><?= $statusCounts['pendente'] ?></h2>
                    </div>
                    <i class="fa-solid fa-hourglass-half icon-box"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-stats text-white bg-success shadow-lg">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-uppercase mb-0">Aprovadas (Sucesso)</h5>
                        <h2 class="display-4 fw-bold"><?= $statusCounts['aprovado'] ?></h2>
                    </div>
                    <i class="fa-solid fa-check-circle icon-box"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-stats text-white bg-danger shadow-lg">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-uppercase mb-0">Rejeitadas</h5>
                        <h2 class="display-4 fw-bold"><?= $statusCounts['rejeitado'] ?></h2>
                    </div>
                    <i class="fa-solid fa-times-circle icon-box"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-5 g-4">
        
        <div class="col-lg-4">
            <div class="card shadow-lg card-chart">
                <div class="card-header bg-primary text-white text-center fw-bold rounded-top-2">
                    <i class="fa-solid fa-chart-pie me-2"></i> Distribuição de Status
                </div>
                <div class="card-body p-4 d-flex justify-content-center align-items-center">
                    <canvas id="graficoStatus" style="max-height: 250px;"></canvas>
                </div>
                <div class="card-footer text-center small text-muted">
                    Clique no gráfico para filtrar a tabela abaixo.
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-lg card-chart">
                <div class="card-header bg-info text-white text-center fw-bold rounded-top-2">
                    <i class="fa-solid fa-chart-bar me-2"></i> Candidaturas por Modalidade
                </div>
                <div class="card-body p-4 d-flex justify-content-center align-items-center">
                    <canvas id="graficoModalidade" style="max-height: 250px;"></canvas>
                </div>
                <div class="card-footer text-center small text-muted">
                    Mostra a popularidade de cada modalidade.
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm p-4 h-100">
                <h5 class="mb-3"><i class="fa-solid fa-filter me-2 text-primary"></i> Filtros Detalhados</h5>
                <form method="GET" class="row g-3">
                    
                    <div class="col-12">
                        <label for="status" class="form-label fw-bold small text-muted">Filtrar por Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos os Status</option>
                            <?php foreach($valid_statuses as $key => $details): ?>
                                <option value="<?= $key ?>" <?= $filtro_status == $key ? 'selected' : '' ?>>
                                    <?= $details['texto'] ?> (<?= $statusCounts[$key] ?? 0 ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <label for="modalidade" class="form-label fw-bold small text-muted">Filtrar por Modalidade</label>
                        <select name="modalidade" id="modalidade" class="form-select">
                            <option value="">Todas as Modalidades</option>
                            <?php foreach($modalidade_counts as $modalidade => $total): ?>
                                <option value="<?= $modalidade ?>" <?= $filtro_modalidade == $modalidade ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($modalidade) ?> (<?= $total ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <label for="vaga_id" class="form-label fw-bold small text-muted">Filtrar por Vaga</label>
                        <select name="vaga_id" id="vaga_id" class="form-select">
                            <option value="0">Todas as Vagas</option>
                            <?php foreach($vaga_counts as $vaga): ?>
                                <option value="<?= $vaga['id'] ?>" <?= $filtro_vaga_id == $vaga['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vaga['nome']) ?> (<?= $vaga['total'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-12 d-flex gap-2 pt-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass me-1"></i> Aplicar Filtros</button>
                        <a href="candidatos.php" class="btn btn-secondary"><i class="fa-solid fa-eraser"></i></a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <div class="card shadow-lg mb-5">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="fa-solid fa-table me-2"></i> Candidaturas Filtradas (<?= $candidatos->num_rows ?>)</h3>
            <div class="d-flex align-items-center">
                <button id="btnExportExcel" class="btn btn-warning btn-sm fw-bold me-2" title="Exportar para Excel (XLSX)">
                    <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
                </button>
                <button id="btnExportPDF" class="btn btn-danger btn-sm fw-bold" title="Exportar para PDF">
                    <i class="fa-solid fa-file-pdf me-1"></i> Exportar PDF
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle small" id="tableCandidaturas"> 
                    <thead class="table-light">
                        <tr>
                            <th># Candidatura</th>
                            <th>Foto</th>
                            <th>Nome do Atleta</th>
                            <th>Email</th>
                            <th>Vaga Aplicada</th>
                            <th>Modalidade</th>
                            <th>Status</th>
                            <th>Data Aplicação</th>
                            <th class="text-center">Acções</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($candidatos->num_rows > 0): ?>
                        <?php while($row = $candidatos->fetch_assoc()): ?>
                        <?php $current_status = $valid_statuses[$row['status']]; ?>
                        <tr>
                            <td><?= $row['compra_id'] ?></td>
                            <td>
                                <?php if(!empty($row['foto_perfil'])): ?>
                                    <img src="../uploads/<?= $row['foto_perfil'] ?>" alt="Foto" class="img-profile-sm" title="<?= htmlspecialchars($row['nome_completo']) ?>">
                                <?php else: ?>
                                    <i class="fa-solid fa-user-circle fa-2x text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['nome_completo']?? '') ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['nome_vaga']) ?></td>
                            <td><?= htmlspecialchars($row['modalidade']) ?></td>
                            <td>
                                <span class="badge badge-status bg-<?= $current_status['cor'] ?>">
                                    <i class="fa-solid <?= $current_status['icone'] ?> me-1"></i> <?= $current_status['texto'] ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($row['data_compra'])) ?></td>
                            <td class="text-center text-nowrap">
                                <a href="../perfil_atleta.php?id=<?= $row['atleta_id'] ?>" target="_blank" class="btn btn-primary btn-sm me-1" title="Ver Perfil do Atleta">
                                    <i class="fa-solid fa-user-circle"></i> Perfil
                                </a>
                                <a href="mudar_status.php?candidatura_id=<?= $row['compra_id'] ?>" class="btn btn-info btn-sm" title="Mudar Status">
                                    <i class="fa-solid fa-sync"></i> Status
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted p-4">Nenhuma candidatura encontrada com os filtros aplicados.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <hr class="my-5">
    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h3 class="mb-0"><i class="fa-solid fa-trophy me-2"></i> Resultados Finais por Atleta (Histórico)</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle small" id="tableAtletasStats">
                    <thead class="table-light">
                        <tr>
                            <th># Atleta ID</th>
                            <th>Nome do Atleta</th>
                            <th>Total de Candidaturas</th>
                            <th>Total de Aprovações</th>
                            <th class="text-center">Taxa de Sucesso</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($atleta_stats as $atleta): ?>
                        <?php
                            $taxa_sucesso = ($atleta['total_candidaturas'] > 0) ? 
                                round(($atleta['total_aprovados'] / $atleta['total_candidaturas']) * 100, 1) : 0;
                            $cor_taxa = $taxa_sucesso >= 50 ? 'success' : ($taxa_sucesso >= 25 ? 'warning' : 'danger');
                        ?>
                        <tr>
                            <td><?= $atleta['id'] ?></td>
                            <td><?= htmlspecialchars($atleta['nome_completo']) ?></td>
                            <td><?= $atleta['total_candidaturas'] ?></td>
                            <td>
                                <span class="badge bg-success fw-bold p-2"><?= $atleta['total_aprovados'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $cor_taxa ?> fw-bold p-2"><?= $taxa_sucesso ?>%</span>
                            </td>
                            <td class="text-center">
                                <a href="?atleta_id=<?= $atleta['id'] ?>" class="btn btn-sm btn-info" title="Ver Histórico Completo">
                                    <i class="fa-solid fa-list-ul me-1"></i> Ver Histórico
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($atleta_stats)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted p-4">Nenhum atleta com candidaturas encontrado.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const statusCounts = {
        pendente: <?= $statusCounts['pendente'] ?>,
        aprovado: <?= $statusCounts['aprovado'] ?>,
        rejeitado: <?= $statusCounts['rejeitado'] ?>
    };
    
    const modalidadeData = <?= json_encode(array_keys($modalidade_counts)) ?>;
    const modalidadeCounts = <?= json_encode(array_values($modalidade_counts)) ?>;

    const vagaLabels = <?= json_encode(array_column($vaga_counts, 'nome')) ?>;
    const vagaCounts = <?= json_encode(array_column($vaga_counts, 'total')) ?>;

    const statusColors = ['#ffc107', '#28a745', '#dc3545'];

    // =================================================================
    // GRÁFICO 1: STATUS GERAL (PIZZA)
    // =================================================================
    const ctxStatus = document.getElementById('graficoStatus').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Pendente', 'Aprovado', 'Rejeitado'],
            datasets: [{
                label: 'Total de Candidaturas',
                data: [statusCounts.pendente, statusCounts.aprovado, statusCounts.rejeitado],
                backgroundColor: statusColors,
                hoverOffset: 10,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { position: 'bottom' },
            },
            onClick: (e, elements) => {
                if(elements.length > 0){
                    const index = elements[0].index;
                    const statusMap = ['pendente', 'aprovado', 'rejeitado'];
                    const selectedStatus = statusMap[index];
                    // Redireciona para filtrar a tabela
                    window.location.href = `candidatos.php?status=${selectedStatus}`;
                }
            }
        }
    });

    // =================================================================
    // GRÁFICO 2: POR MODALIDADE (BARRA)
    // =================================================================
    const ctxModalidade = document.getElementById('graficoModalidade').getContext('2d');
    new Chart(ctxModalidade, {
        type: 'bar',
        data: {
            labels: modalidadeData,
            datasets: [{
                label: 'Candidaturas',
                data: modalidadeCounts,
                backgroundColor: '#17a2b8', // Cor Info
                borderColor: '#17a2b8',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y', // Barras horizontais
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
            },
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });


    // =================================================================
    // EXPORTAÇÃO (PDF e EXCEL)
    // =================================================================
    
    // O NÚMERO DE COLUNAS DA TABELA VISÍVEL QUE SERÃO EXPORTADAS (ID até Data Aplicação)
    const NUM_COLS_CANDIDATURAS = 8; 
    
    // Função para obter o texto dos filtros ativos
    function getFiltrosTextCandidaturas() {
        const statusText = document.getElementById('status').value ? 
            `Status: ${document.getElementById('status').options[document.getElementById('status').selectedIndex].text}` : '';
        const modalidadeText = document.getElementById('modalidade').value ? 
            `Modalidade: ${document.getElementById('modalidade').options[document.getElementById('modalidade').selectedIndex].text}` : '';
        const vagaText = document.getElementById('vaga_id').value > 0 ? 
            `Vaga: ${document.getElementById('vaga_id').options[document.getElementById('vaga_id').selectedIndex].text}` : '';
        
        let filters = [statusText, modalidadeText, vagaText].filter(t => t).join(" | ");
        return filters || "Filtros: Nenhum aplicado";
    }

    // EXPORTAR PDF (Utilizando jsPDF e AutoTable)
    document.getElementById('btnExportPDF').addEventListener('click', () => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4'); // Paisagem

        doc.setFontSize(18);
        doc.text("Relatório de Candidaturas", 14, 16);
        
        doc.setFontSize(10);
        doc.text(getFiltrosTextCandidaturas(), 14, 24);
        
        const table = document.getElementById('tableCandidaturas');
        
        // 1. Captura cabeçalhos (Colunas 0 a 7, excluindo Ações)
        const headers = Array.from(table.querySelectorAll('thead th'))
            .slice(0, NUM_COLS_CANDIDATURAS)
            .map(th => th.innerText.trim()); 
            
        // 2. Captura dados do corpo da tabela (Colunas 0 a 7)
        const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr => 
            Array.from(tr.querySelectorAll('td'))
                .slice(0, NUM_COLS_CANDIDATURAS)
                .map((td, index) => {
                    // Trata a coluna 'Foto' para não dar erro no PDF, substituindo por 'Com Foto' ou 'Sem Foto'
                    if (index === 1) { 
                        return td.querySelector('img') ? 'Com Foto' : 'Sem Foto';
                    }
                    return td.innerText.trim();
                })
        ).filter(row => row.length > 1);

        doc.autoTable({
            head: [headers],
            body: rows,
            startY: 35,
            headStyles: { 
                fillColor: [40, 167, 69], // Cor Success
                textColor: 255,
                fontSize: 8 
            },
            styles: { 
                fontSize: 7,
                cellPadding: 1.5 
            },
            columnStyles: {
                0: { cellWidth: 18 }, // # Candidatura
                1: { cellWidth: 18 }, // Foto
                2: { cellWidth: 35 }, // Nome do Atleta
                3: { cellWidth: 40 }, // Email
                4: { cellWidth: 40 }, // Vaga
                5: { cellWidth: 30 }, // Modalidade
                6: { cellWidth: 20 }, // Status
                7: { cellWidth: 20 }  // Data
            }
        });

        doc.save('relatorio_candidaturas.pdf');
    });

    // EXPORTAR EXCEL (Utilizando SheetJS/XLSX)
    document.getElementById('btnExportExcel').addEventListener('click', () => {
        const table = document.getElementById('tableCandidaturas');
        const wb = XLSX.utils.book_new();

        // 1. Captura cabeçalhos
        const headers = Array.from(table.querySelectorAll('thead th'))
            .slice(0, NUM_COLS_CANDIDATURAS)
            .map(th => th.innerText.trim()); 
            
        // 2. Captura dados
        const data = Array.from(table.querySelectorAll('tbody tr')).map(tr => 
            Array.from(tr.querySelectorAll('td'))
                .slice(0, NUM_COLS_CANDIDATURAS)
                .map((td, index) => {
                    // Trata a coluna 'Foto'
                    if (index === 1) { 
                        return td.querySelector('img') ? 'Com Foto' : 'Sem Foto';
                    }
                    return td.innerText.trim();
                })
        ).filter(row => row.length > 1);
        
        // 3. Monta a matriz de dados para exportação
        data.unshift(headers); 
        data.unshift([getFiltrosTextCandidaturas()]); 
        
        const ws = XLSX.utils.aoa_to_sheet(data);

        // Mescla a célula do título (Filtro Aplicado)
        if (data.length > 0) {
            ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: headers.length - 1 } }]; 
        }

        XLSX.utils.book_append_sheet(wb, ws, "Candidaturas");
        XLSX.writeFile(wb, 'relatorio_candidaturas.xlsx');
    });
</script>

</body>
</html>