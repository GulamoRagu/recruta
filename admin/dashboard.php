<?php
ob_start();
session_start();
// Caminho para a conexão com o banco de dados. 
// Certifique-se de que este caminho está correto.
require '../db.php'; 

// =================================================================
// 1. OBTENÇÃO DE DADOS E CÁLCULO DE KPIS
// =================================================================

// --- KPIs PRINCIPAIS ---
$total_atletas = (int)$conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo='cliente'")->fetch_assoc()['total'];
$total_recrutadores = (int)$conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo='vendedor'")->fetch_assoc()['total'];
$total_vagas = (int)$conn->query("SELECT COUNT(*) AS total FROM produtos")->fetch_assoc()['total'];
$total_candidaturas = (int)$conn->query("SELECT COUNT(*) AS total FROM compras")->fetch_assoc()['total'];

// --- STATUS CANDIDATURAS ---
$total_aprovadas = (int)$conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='Aprovado'")->fetch_assoc()['total'];
$total_pendentes = (int)$conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='Pendente'")->fetch_assoc()['total'];
$total_rejeitadas = (int)$conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='Rejeitado'")->fetch_assoc()['total'];
// Nota: Os status foram ajustados para começar com maiúscula, se este for o padrão do seu banco de dados.
// Caso contrário, use 'aprovado', 'pendente', 'rejeitado'.

// --- NOVOS KPIS DE VAGAS E GÊNERO ---
$hoje = date('Y-m-d');
$total_vagas_expiradas = (int)$conn->query("SELECT COUNT(*) AS total FROM produtos WHERE data_validade < '{$hoje}'")->fetch_assoc()['total'];
$total_vagas_ativas = (int)$conn->query("SELECT COUNT(*) AS total FROM produtos WHERE data_validade >= '{$hoje}'")->fetch_assoc()['total'];

$total_masculinos = (int)$conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo='cliente' AND genero='Masculino'")->fetch_assoc()['total'];
$total_femininos = (int)$conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo='cliente' AND genero='Feminino'")->fetch_assoc()['total'];

// --- MODALIDADE TOP ---
$modalidade_top = $conn->query("
    SELECT p.modalidade, COUNT(c.id) AS total
    FROM compras c
    INNER JOIN produtos p ON c.produto_id = p.id
    GROUP BY p.modalidade
    ORDER BY total DESC
    LIMIT 1
")->fetch_assoc();

$modalidade_mais_candidaturas = htmlspecialchars($modalidade_top['modalidade'] ?? 'Nenhuma');
$modalidade_total_candidaturas = (int)($modalidade_top['total'] ?? 0);

// --- CANDIDATURAS POR MODALIDADE (Não utilizada nos cards, mas útil para um futuro gráfico) ---
$total_por_modalidade_result = $conn->query("
    SELECT modalidade, COUNT(compras.id) AS total
    FROM produtos 
    LEFT JOIN compras ON produtos.id = compras.produto_id
    GROUP BY modalidade
");

// --- IDADE MÉDIA ---
$idade_media_result = $conn->query("
    SELECT AVG(idade) AS media 
    FROM usuarios 
    WHERE tipo='cliente' AND idade IS NOT NULL
")->fetch_assoc()['media'];
$idade_media = number_format((float)($idade_media_result ?? 0), 1); // Garante que a formatação funcione mesmo se for nulo

// =================================================================
// 2. PREPARAÇÃO DE DADOS PARA GRÁFICOS (Chart.js)
// =================================================================

// --- GRÁFICO 1: GÊNERO ---
$genero_result = $conn->query("SELECT COALESCE(genero,'Não Definido') AS genero, COUNT(*) AS total FROM usuarios WHERE tipo='cliente' GROUP BY genero");
$generos = [];
$genero_valores = [];
while ($row = $genero_result->fetch_assoc()) {
    $generos[] = htmlspecialchars($row['genero']);
    $genero_valores[] = (int)$row['total'];
}

// --- GRÁFICO 2: IDADES ---
$idades_result = $conn->query("SELECT idade, COUNT(*) AS total FROM usuarios WHERE tipo='cliente' AND idade IS NOT NULL GROUP BY idade ORDER BY idade ASC");
$idades_label = [];
$idades_valores = [];
while ($row = $idades_result->fetch_assoc()) {
    $idades_label[] = (int)$row['idade'];
    $idades_valores[] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />

    <style>
        :root {
            --primary-bg: #1e293b;
            --primary-text: #cbd5e1;
            --active-bg: #334155;
            --background: #f4f6f9;
        }
        body { background: var(--background); font-family: 'Poppins', sans-serif; }
        .sidebar {
            width: 250px; height: 100vh; position: fixed; background: var(--primary-bg);
            color: white; padding-top: 25px; z-index: 1000;
        }
        .sidebar a {
            display: block; padding: 15px 20px; color: var(--primary-text);
            text-decoration: none; transition: 0.3s;
        }
        .sidebar a:hover, .sidebar a.active { background: var(--active-bg); color: #fff; font-weight: 600; }
        .content { margin-left: 260px; padding: 20px; }
        .card-dashboard { 
            border-radius: 12px; 
            padding: 20px; 
            text-align: center; 
            height: 100%; /* Garante que todos os cards tenham a mesma altura */
        }
        .card-dashboard h5 { 
            font-size: 0.9rem; 
            color: #6c757d; 
            font-weight: 600; 
            text-transform: uppercase;
        }
        .card-dashboard h2 { 
            font-size: 2.2rem; 
            font-weight: 700;
            color: #343a40;
        }
    </style>

</head>
<body>

<div class="sidebar shadow-lg">
    <h4 class="text-center mb-4"><i class="fa-solid fa-gauge-high me-2"></i> ADMIN DASHBOARD</h4>
    <a class="active" href="dashboard.php"><i class="fa-solid fa-table-columns me-2"></i> Dashboard</a>
    <a href="usuarios.php"><i class="fa-solid fa-user-tag me-2"></i> Atletas</a>
    <a href="vagas.php"><i class="fa-solid fa-clipboard-list me-2"></i> Vagas</a>
    <a href="candidatos.php"><i class="fa-solid fa-users-gear me-2"></i> Candidaturas</a>
    <a href="recrutadores.php"><i class="fa-solid fa-briefcase me-2"></i> Recrutadores</a>
    <a href="relatorios.php"><i class="fa-solid fa-chart-pie me-2"></i> Relatórios</a>
    <a href="../logout.php" class="text-warning mt-3"><i class="fa-solid fa-right-from-bracket me-2"></i> Sair</a>
</div>

<div class="content">

<h2 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-chart-line me-2"></i> Visão Geral do Sistema</h2>

<div class="row g-4 mb-5">

    <div class="col-lg-3 col-md-6">
        <div class="card card-dashboard shadow border-0 bg-light">
            <h5 class="text-primary">Total Atletas</h5>
            <h2><?= $total_atletas ?></h2>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-dashboard shadow border-0 bg-light">
            <h5 class="text-info">Atletas Masculinos</h5>
            <h2><?= $total_masculinos ?></h2>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-dashboard shadow border-0 bg-light">
            <h5 class="text-info">Atletas Femininos</h5>
            <h2><?= $total_femininos ?></h2>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-dashboard shadow border-0 bg-light">
            <h5 class="text-secondary">Idade Média</h5>
            <h2><?= $idade_media ?> anos</h2>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card card-dashboard shadow border-0 bg-light">
            <h5 class="text-dark">Total Vagas</h5>
            <h2><?= $total_vagas ?></h2>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-dashboard shadow border-0 bg-light">
            <h5 class="text-success">Vagas Ativas</h5>
            <h2><?= $total_vagas_ativas ?></h2>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-dashboard shadow border-0 bg-light">
            <h5 class="text-danger">Vagas Expiradas</h5>
            <h2><?= $total_vagas_expiradas ?></h2>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-dashboard shadow border-0 bg-light">
            <h5 class="text-dark">Total Recrutadores</h5>
            <h2><?= $total_recrutadores ?></h2>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card card-dashboard shadow border-0 bg-light">
            <h5 class="text-dark">Total Candidaturas</h5>
            <h2><?= $total_candidaturas ?></h2>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-dashboard shadow border-0 bg-light">
            <h5 class="text-success">Candidaturas Aprovadas</h5>
            <h2><?= $total_aprovadas ?></h2>
        </div>
    </div>
    <div class="col-lg-6 col-md-12">
        <div class="card card-dashboard shadow border-0 bg-light">
            <h5 class="text-warning">Modalidade Mais Buscada</h5>
            <h2><?= $modalidade_mais_candidaturas ?> <span class="text-muted small">(<?= $modalidade_total_candidaturas ?> candidaturas)</span></h2>
        </div>
    </div>

</div>

<hr>

<h3 class="fw-bold mt-4 mb-4 text-dark"><i class="fa-solid fa-chart-simple me-2"></i> Distribuição de Dados</h3>

<div class="row g-4 mt-2">

    <div class="col-md-4">
        <div class="card shadow p-3 h-100">
            <h5 class="text-center mb-3">Distribuição por Gênero</h5>
            <canvas id="generoChart"></canvas>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow p-3 h-100">
            <h5 class="text-center mb-3">Distribuição por Idade</h5>
            <canvas id="idadeChart"></canvas>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow p-3 h-100">
            <h5 class="text-center mb-3">Status das Candidaturas</h5>
            <canvas id="statusChart"></canvas>
        </div>
    </div>

</div>

</div>

<script>
// =================================================================
// CONFIGURAÇÃO DOS GRÁFICOS (CHART.JS)
// =================================================================

// --- GRÁFICO GÊNERO (PIE/DOUGHNUT) ---
new Chart(document.getElementById('generoChart'), {
    type: 'doughnut', // Usando doughnut em vez de pie
    data: {
        labels: <?= json_encode($generos) ?>,
        datasets: [{
            label: 'Total de Atletas',
            data: <?= json_encode($genero_valores) ?>,
            backgroundColor: [
                '#0d6efd', // Azul (Primary)
                '#dc3545', // Vermelho (Danger)
                '#6c757d', // Cinza (Secondary)
                '#20c997' // Verde Água
            ],
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            },
            title: {
                display: false,
            }
        }
    }
});

// --- GRÁFICO IDADE (BARRA) ---
new Chart(document.getElementById('idadeChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($idades_label) ?>,
        datasets: [{
            label: 'Atletas',
            data: <?= json_encode($idades_valores) ?>,
            backgroundColor: '#28a745', // Verde (Success)
            borderColor: '#218838',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false,
            },
            title: {
                display: false,
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Número de Atletas'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Idade'
                }
            }
        }
    }
});

// --- GRÁFICO STATUS CANDIDATURAS (DOUGHNUT) ---
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Aprovadas','Pendentes','Rejeitadas'],
        datasets: [{
            label: 'Total de Candidaturas',
            data: [<?= $total_aprovadas ?>, <?= $total_pendentes ?>, <?= $total_rejeitadas ?>],
            backgroundColor: [
                '#198754', // Verde Escuro (Aprovadas)
                '#ffc107', // Amarelo (Pendentes)
                '#dc3545' // Vermelho (Rejeitadas)
            ],
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            },
            title: {
                display: false,
            }
        }
    }
});
</script>

</body>
</html>
