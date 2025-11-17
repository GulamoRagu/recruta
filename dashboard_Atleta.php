<?php
ob_start();
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$query = $conn->prepare("SELECT nome_completo FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'Atleta';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Atleta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            background-color: #343a40;
            padding: 20px 0;
            min-height: 100vh;
        }

        .sidebar a {
            color: white;
            padding: 12px 20px;
            display: block;
            text-decoration: none;
            font-size: 16px;
        }

        .sidebar a:hover {
            background-color: #495057;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: absolute;
                width: 250px;
                height: 100%;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .overlay {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }

            .content {
                margin-left: 0 !important;
            }
        }

        .toggle-btn {
            font-size: 24px;
            margin-bottom: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <h4 class="text-center text-white"><?= htmlspecialchars($nome_usuario) ?></h4>
        <a href="perfil_atleta.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
        <a href="ver_vagas.php"><i class="fa-solid fa-box"></i> Ver Vagas</a>
        <a href="minhas_candidaturas.php"><i class="fa-solid fa-shopping-cart"></i> Minhas Candidaturas</a>
        <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
    </div>

    <!-- Conteúdo Principal -->
    <div class="flex-grow-1 content p-4">
        <span class="toggle-btn d-md-none" onclick="toggleSidebar()">
            <i class="fa fa-bars"></i> Menu
        </span>

        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
            <div class="alert alert-success">Perfil atualizado com sucesso!</div>
        <?php endif; ?>

        <h2 class="mb-4">Bem-vindo ao seu Dashboard, Atleta</h2>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <i class="fa-solid fa-user fa-3x text-primary"></i>
                        <h5 class="card-title mt-2">Meu Perfil</h5>
                        <p class="card-text">Atualize seus dados.</p>
                        <a href="perfil_atleta.php" class="btn btn-primary">Acessar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <i class="fa-solid fa-box fa-3x text-success"></i>
                        <h5 class="card-title mt-2">Ver Vagas</h5>
                        <p class="card-text">Candidate-se às vagas de recrutadores.</p>
                        <a href="ver_vagas.php" class="btn btn-success">Acessar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <i class="fa-solid fa-shopping-cart fa-3x text-info"></i>
                        <h5 class="card-title mt-2">Minhas Candidaturas</h5>
                        <p class="card-text">Veja seu histórico de candidaturas.</p>
                        <a href="minhas_candidaturas.php" class="btn btn-info">Acessar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overlay para mobile -->
<div id="overlay" class="overlay d-none" onclick="toggleSidebar()"></div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('d-none');
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>