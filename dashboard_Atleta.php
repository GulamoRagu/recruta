<?php
ob_start();
session_start();
require 'db.php';

// Redireciona se não estiver logado ou não for 'cliente'
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// --- 1. BUSCA DE INFORMAÇÕES DO USUÁRIO ---
$query = $conn->prepare("SELECT nome_completo FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'Atleta';

// --- 2. BUSCA DAS ESTATÍSTICAS ---
$query_vagas = $conn->prepare("SELECT COUNT(*) as total_vagas FROM produtos WHERE data_validade >= CURDATE()");
$query_vagas->execute();
$result_vagas = $query_vagas->get_result();
$total_vagas_disponiveis = $result_vagas->fetch_assoc()['total_vagas'];

$query_pendentes = $conn->prepare("SELECT COUNT(*) as total FROM compras WHERE cliente_id = ? AND status = 'pendente'");
$query_pendentes->bind_param("i", $user_id);
$query_pendentes->execute();
$total_pendentes = $query_pendentes->get_result()->fetch_assoc()['total'];

$query_aprovadas = $conn->prepare("SELECT COUNT(*) as total FROM compras WHERE cliente_id = ? AND status = 'aprovado'");
$query_aprovadas->bind_param("i", $user_id);
$query_aprovadas->execute();
$total_aprovadas = $query_aprovadas->get_result()->fetch_assoc()['total'];

$query_rejeitadas = $conn->prepare("SELECT COUNT(*) as total FROM compras WHERE cliente_id = ? AND status = 'rejeitado'");
$query_rejeitadas->bind_param("i", $user_id);
$query_rejeitadas->execute();
$total_rejeitadas = $query_rejeitadas->get_result()->fetch_assoc()['total'];

$query_total_candidaturas = $conn->prepare("SELECT COUNT(*) as total FROM compras WHERE cliente_id = ?");
$query_total_candidaturas->bind_param("i", $user_id);
$query_total_candidaturas->execute();
$total_candidaturas_atleta = $query_total_candidaturas->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel do Atleta</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f0f2f5;
}

/* Sidebar */
.sidebar {
    min-width: 220px;
    max-width: 220px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    min-height: 100vh;
    padding-top: 1.5rem;
    box-shadow: 6px 0 15px rgba(0,0,0,0.2);
}
.sidebar h4 {
    margin-bottom: 2rem;
    text-align: center;
    font-weight: 700;
}
.sidebar a {
    color: white;
    display: flex;
    align-items: center;
    padding: 12px 20px;
    text-decoration: none;
    font-weight: 500;
    border-radius: 10px;
    margin: 6px 10px;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}
.sidebar a i {
    margin-right: 10px;
    font-size: 1.1rem;
}
.sidebar a:hover, .sidebar a.active {
    background: rgba(255,255,255,0.1);
    border-left: 4px solid #ffc107;
}

/* Main content */
.main-content {
    flex-grow: 1;
    padding: 30px;
}

/* Cards */
.card-custom {
    border: none;
    border-radius: 15px;
    background: #ffffff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
.card-custom:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
}
.card-custom i {
    font-size: 3rem;
}
.card-body p {
    margin-bottom: 0;
}

/* Estatísticas */
.stat-card {
    border-radius: 15px;
    color: white;
    padding: 20px;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}
.stat-card i {
    font-size: 2.5rem;
    margin-bottom: 10px;
}
.stat-card h4 {
    margin: 0;
}

/* Responsividade */
@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        transform: translateX(-100%);
        z-index: 1000;
        transition: transform 0.3s ease;
    }
    .sidebar.active {
        transform: translateX(0);
    }
    .overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }
    .main-content {
        margin-left: 0 !important;
    }
    .toggle-btn {
        font-size: 26px;
        margin-bottom: 20px;
        cursor: pointer;
    }
}
</style>
</head>
<body>
<div class="d-flex">
    <div id="sidebar" class="sidebar">
        <h4><i class="fa-solid fa-medal me-2"></i> <?= htmlspecialchars($nome_usuario) ?></h4>
        <a href="perfil_atleta.php" class="active"><i class="fa-solid fa-user"></i> Meu Perfil</a>
        <a href="ver_vagas.php"><i class="fa-solid fa-magnifying-glass"></i> Explorar Vagas</a>
        <a href="minhas_candidaturas.php"><i class="fa-solid fa-clipboard-list"></i> Minhas Candidaturas</a>
        <a href="logout.php" class="text-warning mt-auto"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
    </div>

    <div class="flex-grow-1 main-content">
        <span class="toggle-btn d-md-none" onclick="toggleSidebar()">
            <i class="fa fa-bars"></i> Menu
        </span>

        <h1 class="mb-4">Bem-vindo(a), <?= htmlspecialchars($nome_usuario) ?></h1>

        <div class="row g-4 mb-4">
            <!-- Cards principais -->
            <div class="col-md-4">
                <div class="card card-custom text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <i class="fa-solid fa-user text-primary mb-3"></i>
                            <h5 class="card-title">Meu Perfil</h5>
                            <p class="card-text text-muted">Actualize suas informações para os recrutadores.</p>
                        </div>
                        <a href="perfil_atleta.php" class="btn btn-primary mt-3">Actualizar Perfil</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <i class="fa-solid fa-magnifying-glass text-success mb-3"></i>
                            <h5 class="card-title">Explorar Vagas</h5>
                            <p class="card-text text-muted">Descubra novas oportunidades de recrutamento.</p>
                        </div>
                        <a href="ver_vagas.php" class="btn btn-success mt-3">Ver Vagas</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <i class="fa-solid fa-clipboard-list text-warning mb-3"></i>
                            <h5 class="card-title">Minhas Candidaturas</h5>
                            <p class="card-text text-muted">Acompanhe o status de suas candidaturas.</p>
                        </div>
                        <a href="minhas_candidaturas.php" class="btn btn-warning mt-3">Entrar</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas rápidas -->
        <h3 class="mb-3">Estatísticas Rápidas</h3>
        <div class="row g-3">
            <div class="col-md-2 col-sm-4 col-6">
                <div class="stat-card bg-info">
                    <i class="fa-solid fa-briefcase"></i>
                    <h4><?= $total_vagas_disponiveis ?></h4>
                    <p>Vagas</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="stat-card bg-primary">
                    <i class="fa-solid fa-list-check"></i>
                    <h4><?= $total_candidaturas_atleta ?></h4>
                    <p>Candidaturas</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="stat-card bg-warning">
                    <i class="fa-solid fa-hourglass-half"></i>
                    <h4><?= $total_pendentes ?></h4>
                    <p>Pendentes</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="stat-card bg-success">
                    <i class="fa-solid fa-check-circle"></i>
                    <h4><?= $total_aprovadas ?></h4>
                    <p>Aprovadas</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="stat-card bg-danger">
                    <i class="fa-solid fa-times-circle"></i>
                    <h4><?= $total_rejeitadas ?></h4>
                    <p>Rejeitadas</p>
                </div>
            </div>
        </div>

    </div>
</div>

<div id="overlay" class="overlay" onclick="toggleSidebar()"></div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('d-none');
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
