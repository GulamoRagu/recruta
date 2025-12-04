<?php
ob_start();
session_start();
require '../db.php';

// ---------- CONTADORES PRINCIPAIS ----------
$total_atletas = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo='cliente'")->fetch_assoc()['total'];
$total_vagas = $conn->query("SELECT COUNT(*) AS total FROM produtos")->fetch_assoc()['total'];
$total_candidaturas = $conn->query("SELECT COUNT(*) AS total FROM compras")->fetch_assoc()['total'];
$total_recrutadores = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo='vendedor'")->fetch_assoc()['total'];

// ---------- CONTADORES EXTRA ----------
$total_aprovadas = $conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='aprovado'")->fetch_assoc()['total'];
$total_rejeitadas = $conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='rejeitado'")->fetch_assoc()['total'];
$total_pendentes = $conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='pendente'")->fetch_assoc()['total'];

// ---------- ESTATÍSTICAS POR GÉNERO ----------
$genero = $conn->query("SELECT genero, COUNT(*) AS total FROM usuarios WHERE tipo='cliente' GROUP BY genero");
$generos = [];
$genero_valores = [];
while ($row = $genero->fetch_assoc()) {
    $generos[] = $row['genero'];
    $genero_valores[] = $row['total'];
}

// ---------- ESTATÍSTICAS POR IDADE ----------
$idades = $conn->query("SELECT idade, COUNT(*) AS total FROM usuarios WHERE tipo='cliente' GROUP BY idade");
$idades_label = [];
$idades_valores = [];
while ($row = $idades->fetch_assoc()) {
    $idades_label[] = $row['idade'];
    $idades_valores[] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatórios</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Chart JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { background: #f0f2f5; }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background: #1e293b;
            color: white;
            padding-top: 20px;
        }
        .sidebar h4 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 16px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background: #334155;
            color: white;
        }

        .content {
            margin-left: 260px;
            padding: 20px;
        }

        .card-dashboard {
            border-radius: 12px;
            transition: .3s;
            cursor: pointer;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 22px rgba(0,0,0,0.15);
        }
        .icon-box {
            font-size: 45px;
            margin-bottom: 15px;
            color: #0d6efd;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h4>Admin</h4>

    <a href="dashboard.php"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a>
    <a href="usuarios.php"><i class="fa-solid fa-user me-2"></i> Atletas</a>
    <a href="vagas.php"><i class="fa-solid fa-file-lines me-2"></i> Vagas</a>
    <a href="candidatos.php"><i class="fa-solid fa-list-check me-2"></i> Candidaturas</a>
    <a href="recrutadores.php"><i class="fa-solid fa-users me-2"></i> Recrutadores</a>
    <a href="relatorios.php" class="fw-bold"><i class="fa-solid fa-chart-pie me-2"></i> Relatórios</a>
    <a href="../index.php" class="text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i> Sair</a>
</div>

<!-- MAIN CONTENT -->
<div class="content">

    <h2 class="mb-4">Relatórios Gerais</h2>

    <!-- CARDS RESUMO -->
    <div class="row g-4">

        <div class="col-md-3">
            <div class="card card-dashboard p-4 text-center">
                <div class="icon-box"><i class="fa-solid fa-user"></i></div>
                <h5 class="fw-bold">Atletas</h5>
                <h2><?= $total_atletas ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-dashboard p-4 text-center">
                <div class="icon-box"><i class="fa-solid fa-users"></i></div>
                <h5 class="fw-bold">Recrutadores</h5>
                <h2><?= $total_recrutadores ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-dashboard p-4 text-center">
                <div class="icon-box"><i class="fa-solid fa-file-lines"></i></div>
                <h5 class="fw-bold">Vagas</h5>
                <h2><?= $total_vagas ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-dashboard p-4 text-center">
                <div class="icon-box"><i class="fa-solid fa-list-check"></i></div>
                <h5 class="fw-bold">Candidaturas</h5>
                <h2><?= $total_candidaturas ?></h2>
            </div>
        </div>

    </div>

    <!-- CARDS STATUS -->
    <div class="row g-4 mt-2">

        <div class="col-md-4">
            <div class="card card-dashboard p-4 text-center border-success">
                <h5 class="fw-bold text-success">Aprovadas</h5>
                <h2><?= $total_aprovadas ?></h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-dashboard p-4 text-center border-warning">
                <h5 class="fw-bold text-warning">Pendentes</h5>
                <h2><?= $total_pendentes ?></h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-dashboard p-4 text-center border-danger">
                <h5 class="fw-bold text-danger">Rejeitadas</h5>
                <h2><?= $total_rejeitadas ?></h2>
            </div>
        </div>

    </div>

    <!-- GRÁFICOS -->
    <div class="row mt-5">

        <div class="col-md-4">
            <div class="card p-4">
                <h5 class="text-center">Estatísticas por Género</h5>
                <canvas id="generoChart"></canvas>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4">
                <h5 class="text-center">Estatísticas por Idade</h5>
                <canvas id="idadeChart"></canvas>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4">
                <h5 class="text-center">Status das Candidaturas</h5>
                <canvas id="statusChart"></canvas>
            </div>
        </div>

    </div>

</div>

<script>
    // ---- GRÁFICO DE GÉNERO ----
    new Chart(document.getElementById('generoChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($generos) ?>,
            datasets: [{
                data: <?= json_encode($genero_valores) ?>,
                backgroundColor: ['#0d6efd', '#dc3545', '#198754']
            }]
        }
    });

    // ---- GRÁFICO DE IDADE ----
    new Chart(document.getElementById('idadeChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($idades_label) ?>,
            datasets: [{
                label: 'Atletas por Idade',
                data: <?= json_encode($idades_valores) ?>,
                backgroundColor: '#0d6efd'
            }]
        }
    });

    // ---- GRÁFICO DE STATUS ----
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Aprovadas', 'Pendentes', 'Rejeitadas'],
            datasets: [{
                data: [<?= $total_aprovadas ?>, <?= $total_pendentes ?>, <?= $total_rejeitadas ?>],
                backgroundColor: ['#198754', '#ffc107', '#dc3545']
            }]
        }
    });
</script>

</body>
</html>
