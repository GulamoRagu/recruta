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
// Usando prepare/execute para segurança
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
    <title>Painel do Atleta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
 
<style>
body {
    display: flex;
    min-height: 100vh;
    font-family: 'Poppins', sans-serif; /* Fonte moderna */
    background: #e9ecef; /* Fundo mais suave */
}

/* Sidebar moderna */
.sidebar {
    min-width: 260px; /* Levemente mais larga */
    max-width: 260px;
    background: linear-gradient(135deg, #007bff, #0056b3); /* Novo gradiente azul profissional */
    color: white;
    min-height: 100vh;
    padding-top: 1.5rem;
    box-shadow: 6px 0 15px rgba(0,0,0,0.25); /* Sombra mais forte */
}
.sidebar h4 {
    margin-bottom: 2rem;
    text-align: center;
    font-weight: 700; /* Mais destaque */
    letter-spacing: 1px;
}
.sidebar a {
    color: white;
    display: flex;
    align-items: center;
    padding: 15px 25px; /* Mais padding */
    text-decoration: none;
    font-weight: 500;
    border-radius: 10px; /* Bordas mais arredondadas */
    margin: 8px 15px;
    transition: all 0.3s ease;
    border-left: 5px solid transparent; /* Linha de destaque */
}
.sidebar a i {
    margin-right: 15px;
    font-size: 1.1rem;
}
.sidebar a:hover, .sidebar a.active {
    background: rgba(255,255,255,0.1);
    transform: translateX(0); /* Remove o transform para um hover mais sutil */
    border-left: 5px solid #ffc107; /* Destaque amarelo */
}

/* Conteúdo principal */
.main-content {
    flex-grow: 1;
    padding: 30px;
}

/* Cards modernos com design "Neumorphism" (Sutil) */
.card-custom {
    border: none; /* Remove a borda padrão */
    border-radius: 20px;
    background: #e9ecef;
    box-shadow: 8px 8px 16px #c8c8c8, -8px -8px 16px #ffffff; /* Sombra sutil de Neumorphism */
    transition: all 0.4s ease;
    overflow: hidden; /* Garante que o conteúdo não vaze */
}
.card-custom:hover {
    box-shadow: 12px 12px 24px #b8b8b8, -12px -12px 24px #ffffff; /* Efeito de elevação no hover */
    transform: translateY(-3px);
}
.card-custom i {
    font-size: 3.5rem;
    padding-bottom: 10px;
}
.card-header-custom {
    background-color: #007bff; /* Cor para o cabeçalho do card */
    color: white;
    font-weight: 600;
    padding: 15px;
    border-radius: 20px 20px 0 0;
}

/* Botões modernos */
.btn-gradient {
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}
.btn-gradient:hover {
    background: linear-gradient(45deg, #0056b3, #007bff);
    transform: scale(1.05);
    color: white;
}

/* Alertas */
.alert-success {
    border-left: 8px solid #28a745;
    background-color: #d4edda;
    color: #155724;
    border-radius: 10px;
    font-weight: 500;
}

/* Responsividade - Mantida */
@media (max-width: 768px) {
.sidebar {
    position: fixed; /* Mudei para fixed para garantir que fique sempre visível */
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
    display: none; /* Inicialmente escondido */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6); /* Overlay mais escuro */
    z-index: 999;
}
.main-content {
    margin-left: 0 !important;
}
.toggle-btn {
    color: #007bff; /* Cor do botão mobile */
    font-size: 26px;
    margin-bottom: 20px;
    cursor: pointer;
}
} </style>

</head>
<body>

<div class="d-flex">
    <div id="sidebar" class="sidebar">
        <h4><i class="fa-solid fa-medal me-2"></i> <?= htmlspecialchars($nome_usuario) ?></h4>
        <a href="perfil_atleta.php" class="active"><i class="fa-solid fa-user"></i> Meu Perfil</a>
        <a href="ver_vagas.php"><i class="fa-solid fa-magnifying-glass"></i> Explorar Vagas</a>
        <a href="minhas_candidaturas.php"><i class="fa-solid fa-clipboard-list"></i> Minhas Candidaturas</a>
        <a href="logout.php" class="text-warning" style="margin-top: auto; padding-bottom: 20px;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a> </div>

    <div class="flex-grow-1 main-content">
        <span class="toggle-btn d-md-none" onclick="toggleSidebar()">
            <i class="fa fa-bars"></i> Menu
        </span>

        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
            <div class="alert alert-success">Perfil atualizado com sucesso!</div>
        <?php endif; ?>

        <h1 class="mb-5 border-bottom pb-2">👋 Bem-vindo(a) ao Painel, <?= htmlspecialchars($nome_usuario) ?></h1>

        <div class="row g-4"> <div class="col-md-4">
                <div class="card card-custom text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <i class="fa-solid fa-user text-primary mb-3"></i>
                            <h5 class="card-title">Meu Perfil</h5>
                            <p class="card-text text-muted">Mantenha suas informações e estatísticas atualizadas para os recrutadores.</p>
                        </div>
                        <a href="perfil_atleta.php" class="btn btn-gradient mt-3">Atualizar Perfil</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <i class="fa-solid fa-magnifying-glass text-success mb-3"></i>
                            <h5 class="card-title">Explorar Vagas</h5>
                            <p class="card-text text-muted">Descubra novas oportunidades de recrutamento e bolsas de estudo.</p>
                        </div>
                        <a href="ver_vagas.php" class="btn btn-gradient mt-3">Ver Oportunidades</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <i class="fa-solid fa-clipboard-list text-warning mb-3"></i>
                            <h5 class="card-title">Minhas Candidaturas</h5>
                            <p class="card-text text-muted">Acompanhe o status de todas as suas candidaturas ativas.</p>
                        </div>
                        <a href="minhas_candidaturas.php" class="btn btn-gradient mt-3">Acompanhar</a>
                    </div>
                </div>
            </div>
        </div>
        
        <h3 class="mt-5 mb-4 text-secondary">Estatísticas Rápidas 🚀</h3>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3" style="border-radius: 15px;">
                    <div class="card-body">
                        <i class="fa-solid fa-trophy float-end fs-2"></i>
                        <h4 class="card-title">5</h4>
                        <p class="card-text">Vagas Salvas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3" style="border-radius: 15px;">
                    <div class="card-body">
                        <i class="fa-solid fa-hourglass-half float-end fs-2"></i>
                        <h4 class="card-title">2</h4>
                        <p class="card-text">Candidaturas Pendentes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning mb-3" style="border-radius: 15px;">
                    <div class="card-body">
                        <i class="fa-solid fa-bell float-end fs-2"></i>
                        <h4 class="card-title">3</h4>
                        <p class="card-text">Novas Notificações</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="overlay" class="overlay" onclick="toggleSidebar()"></div>

<script>
    // Função JavaScript para o menu mobile
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('active');
        // Usar classes do Bootstrap para gerenciar a exibição do overlay
        overlay.classList.toggle('d-none');
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>