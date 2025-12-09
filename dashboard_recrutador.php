<?php
ob_start();
session_start();
require 'db.php';

// 1. Verificação de Acesso (Mantido)
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// 2. Obter Nome do Recrutador (Mantido)
$query = $conn->prepare("SELECT nome_completo FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'Recrutador';
$query->close();

// 3. Estatísticas Otimizadas
// Nota: Usar subqueries no WHERE é menos eficiente que JOINs, mas aqui as subqueries são simples e para contagem.
$db_queries = [
    'total_vagas' => "SELECT COUNT(*) AS total FROM produtos WHERE recrutador_id = $user_id",
    'total_candidaturas' => "SELECT COUNT(*) AS total FROM compras WHERE produto_id IN (SELECT id FROM produtos WHERE recrutador_id = $user_id)",
    'aprovadas' => "SELECT COUNT(*) AS total FROM compras WHERE status='aprovado' AND produto_id IN (SELECT id FROM produtos WHERE recrutador_id = $user_id)",
    'rejeitadas' => "SELECT COUNT(*) AS total FROM compras WHERE status='rejeitado' AND produto_id IN (SELECT id FROM produtos WHERE recrutador_id = $user_id)",
    'pendentes' => "SELECT COUNT(*) AS total FROM compras WHERE status='pendente' AND produto_id IN (SELECT id FROM produtos WHERE recrutador_id = $user_id)",
];

$stats = [];
foreach ($db_queries as $key => $sql) {
    // Usamos 'aprovado'/'rejeitado'/'pendente' em minúsculo, o status da DB deve ser case-sensitive ou ter sido tratado.
    $stats[$key] = $conn->query($sql)->fetch_assoc()['total'] ?? 0;
}

// Variáveis para o gráfico (Chart.js)
$candidaturas_data = [
    $stats['aprovadas'],
    $stats['rejeitadas'],
    $stats['pendentes']
];

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Recrutador | <?= htmlspecialchars($nome_usuario) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --dark: #343a40;
        }

        /* Estrutura Geral */
        body { 
            background-color: #f8f9fa; 
            font-family: 'Poppins', sans-serif;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Moderna */
        .sidebar {
            background: linear-gradient(180deg, var(--dark), #212529); /* Degradê sutil */
            color: white;
            min-width: 250px;
            max-width: 250px;
            padding-top: 20px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            height: 100%;
        }
        .sidebar h4 {
            font-weight: 700;
            padding: 10px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
            text-align: center;
        }
        .sidebar a {
            color: white;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .sidebar a i {
            margin-right: 15px;
            width: 20px;
        }
        .sidebar a:hover, .sidebar a.active { 
            background-color: rgba(255, 255, 255, 0.1); 
            border-left-color: var(--warning); /* Destaque amarelo */
        }

        /* Conteúdo Principal */
        .content { 
            flex-grow: 1;
            margin-left: 250px; /* Offset para a sidebar fixa */
            padding: 30px;
        }
        .content h2 {
            font-weight: 700;
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
        }

        /* Cartões de Estatísticas (Info Cards) */
        .info-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
            padding: 20px;
        }
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        .info-card h5 {
            font-weight: 600;
            color: var(--secondary);
            font-size: 1rem;
        }
        .info-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-top: 10px;
        }

        .card-total { border-bottom: 4px solid var(--primary); }
        .card-aprovado { border-bottom: 4px solid var(--success); }
        .card-rejeitado { border-bottom: 4px solid var(--danger); }
        .card-pendente { border-bottom: 4px solid var(--warning); }

        /* Gráfico */
        .chart-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 20px;
            height: 100%;
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .sidebar { 
                position: relative; 
                width: 100%; 
                min-height: auto;
                box-shadow: none;
                padding-bottom: 10px;
                display: flex; /* Para centralizar os links */
                flex-wrap: wrap;
                justify-content: center;
            }
            .sidebar h4 { width: 100%; text-align: center; }
            .sidebar a { width: auto; margin: 5px; }
            .content { 
                margin-left: 0; 
                padding-top: 20px;
            }
        }
    </style>
</head>
<body>

<div class="d-flex w-100">
    <nav class="sidebar d-none d-lg-block">
        <h4 class="text-white"><i class="fa-solid fa-building me-2"></i> <?= htmlspecialchars($nome_usuario) ?></h4>
        <a href="dashboard_recrutador.php" class="active"><i class="fa-solid fa-gauge-high"></i> Inicio</a>
        <a href="perfil_recrutador.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
        <a href="vagas_recrutador.php"><i class="fa-solid fa-briefcase"></i> Gerir Vagas</a>
        <a href="ver_candidaturas.php"><i class="fa-solid fa-list-check"></i> Ver Candidaturas</a>
        <a href="logout.php" class="text-warning mt-auto"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
    </nav>
    
    <nav class="navbar navbar-dark bg-dark fixed-top d-lg-none">
        <div class="container-fluid">
            <span class="navbar-brand"><i class="fa-solid fa-building me-2"></i> Inicio</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title text-white" id="offcanvasSidebarLabel"><?= htmlspecialchars($nome_usuario) ?></h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <a href="dashboard_recrutador.php" class="d-block text-white mb-2 active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                    <a href="perfil_recrutador.php" class="d-block text-white mb-2"><i class="fa-solid fa-user"></i> Meu Perfil</a>
                    <a href="vagas_recrutador.php" class="d-block text-white mb-2"><i class="fa-solid fa-briefcase"></i> Gerir Vagas</a>
                    <a href="ver_candidaturas.php" class="d-block text-white mb-2"><i class="fa-solid fa-list-check"></i> Ver Candidaturas</a>
                    <a href="logout.php" class="d-block text-warning mt-4"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
                </div>
            </div>
        </div>
    </nav>


    <main class="content">
        <h2 class="mb-5 mt-lg-0 mt-5"><i class="fa-solid fa-chart-simple me-2"></i> Visão Geral</h2>

        <div class="row g-4 mb-5">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card info-card card-total">
                    <i class="fa-solid fa-briefcase fa-2x mb-2 text-primary"></i>
                    <h5>Total de Vagas</h5>
                    <h3><?= $stats['total_vagas'] ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card info-card card-total">
                    <i class="fa-solid fa-list-alt fa-2x mb-2 text-primary"></i>
                    <h5>Total de Candidaturas</h5>
                    <h3><?= $stats['total_candidaturas'] ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card info-card card-aprovado">
                    <i class="fa-solid fa-check-circle fa-2x mb-2 text-success"></i>
                    <h5>Aprovadas</h5>
                    <h3><?= $stats['aprovadas'] ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card info-card card-pendente">
                    <i class="fa-solid fa-clock fa-2x mb-2 text-warning"></i>
                    <h5>Pendentes</h5>
                    <h3><?= $stats['pendentes'] ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card info-card card-rejeitado">
                    <i class="fa-solid fa-times-circle fa-2x mb-2 text-danger"></i>
                    <h5>Rejeitadas</h5>
                    <h3><?= $stats['rejeitadas'] ?></h3>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h4 class="mb-4 text-center text-primary">Status das Candidaturas</h4>
                    <canvas id="candidaturasChart"></canvas>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="chart-container d-flex flex-column">
                    <h4 class="mb-4 text-center text-dark">Acções Rápidas</h4>
                    <a href="vagas_recrutador.php?action=create" class="btn btn-primary btn-lg mb-3 shadow">
                        <i class="fa-solid fa-plus-circle me-2"></i> Publicar Nova Vaga
                    </a>
                    <a href="ver_candidaturas.php?status=pendente" class="btn btn-warning btn-lg mb-3 shadow">
                        <i class="fa-solid fa-hourglass-half me-2"></i> Revisar Pendentes (<?= $stats['pendentes'] ?>)
                    </a>
                    <a href="perfil_recrutador.php" class="btn btn-secondary btn-lg mb-3 shadow">
                        <i class="fa-solid fa-user-edit me-2"></i> Editar Perfil
                    </a>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('candidaturasChart').getContext('2d');
    
    // Dados do PHP (Convertidos para JS)
    const chartData = {
        aprovadas: <?= $stats['aprovadas'] ?>,
        rejeitadas: <?= $stats['rejeitadas'] ?>,
        pendentes: <?= $stats['pendentes'] ?>
    };

    new Chart(ctx, {
        type: 'doughnut', // Gráfico de Pizza (Doughnut)
        data: {
            labels: ['Aprovadas', 'Rejeitadas', 'Pendentes'],
            datasets: [{
                label: 'Contagem de Candidaturas',
                data: [chartData.aprovadas, chartData.rejeitadas, chartData.pendentes],
                backgroundColor: [
                    '#28a745', // Success
                    '#dc3545', // Danger
                    '#ffc107'  // Warning
                ],
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            aspectRatio: 1, // Para manter o formato em container flexível
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false,
                    text: 'Status das Candidaturas'
                }
            }
        }
    });
});
</script>

</body>
</html>