<?php
ob_start();
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$query = $conn->prepare("SELECT nome_completo FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'Recrutador';

// Estatísticas
$total_vagas = $conn->query("SELECT COUNT(*) AS total FROM produtos WHERE vendedor_id = $user_id")->fetch_assoc()['total'];
$total_candidaturas = $conn->query("SELECT COUNT(*) AS total FROM compras WHERE produto_id IN (SELECT id FROM produtos WHERE vendedor_id = $user_id)")->fetch_assoc()['total'];
$candidaturas_aprovadas = $conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='Aprovado' AND produto_id IN (SELECT id FROM produtos WHERE vendedor_id = $user_id)")->fetch_assoc()['total'];
$candidaturas_rejeitadas = $conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='Rejeitado' AND produto_id IN (SELECT id FROM produtos WHERE vendedor_id = $user_id)")->fetch_assoc()['total'];
$candidaturas_pendentes = $conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='Pendente' AND produto_id IN (SELECT id FROM produtos WHERE vendedor_id = $user_id)")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Recrutador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
        }
        .sidebar a {
            color: white;
            padding: 15px;
            display: block;
            text-decoration: none;
            font-size: 18px;
        }
        .sidebar a:hover { background-color: #495057; }
        @media (min-width: 768px) {
            .sidebar { width: 250px; position: fixed; top: 0; left: 0; padding-top: 20px; }
            .content { margin-left: 250px; padding: 20px; }
        }
        @media (max-width: 767.98px) {
            .sidebar { position: relative; width: 100%; padding: 10px; }
            .content { margin-left: 0; padding: 10px; }
        }
        .chart-container {
            width: 100%;
            max-width: 500px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 sidebar">
            <h4 class="text-center text-white"><?= htmlspecialchars($nome_usuario) ?></h4>
            <a href="perfil_recrutador.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
          
            <a href="vagas_recrutador.php"><i class="fa-solid fa-box"></i> Vagas</a>
            <a href="ver_candidaturas.php"><i class="fa-solid fa-chart-line"></i> Ver Candidaturas</a>
            <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
        </nav>

        <!-- Conteúdo -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 content">
            <h2 class="mb-4 mt-3">Bem-vindo ao seu Dashboard, <?= htmlspecialchars($nome_usuario) ?>!</h2>

            <!-- Cartões de resumo -->
            <div class="row text-center g-4">
    <div class="col-md-3">
        <div class="card p-3 shadow-sm">
            <h5>Total de Vagas</h5>
            <h3><?= $total_vagas ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm">
            <h5>Total de Candidaturas</h5>
            <h3><?= $total_candidaturas ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm">
            <h5>Aprovadas</h5>
            <h3><?= $candidaturas_aprovadas ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm">
            <h5>Pendentes</h5>
            <h3><?= $candidaturas_pendentes ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm">
            <h5>Rejeitadas</h5>
            <h3><?= $candidaturas_rejeitadas ?></h3>
        </div>
    </div>
</div>


            <!-- Gráfico -->
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>

        </main>
    </div>
</div>


</body>
</html>
