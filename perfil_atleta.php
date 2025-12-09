<?php
ob_start();
session_start();
require 'db.php'; // Garante que a conexão com o banco está incluída

// Redireciona se não estiver logado ou não for 'cliente'
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Busca ÚNICA de todos os dados necessários do usuário
$query = $conn->prepare(
    "SELECT username, email, nome_completo, telefone, endereco, idade, posicao,
            clube_anterior, modalidade, pe, genero, situacao_atual, foto_perfil
     FROM usuarios
     WHERE id = ?"
);
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Se o usuário não for encontrado (após a verificação de login), redireciona.
if (!$user) {
    header("Location: dashboard_atleta.php");
    exit();
}

// O nome do usuário para a sidebar é extraído da busca única
$nome_usuario = $user['nome_completo'] ?? 'Atleta';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Atleta | <?= htmlspecialchars($nome_usuario) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f2f5; /* Fundo mais suave */
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar moderna (Coerência com o Painel) */
        .sidebar {
            min-width: 260px;
            max-width: 260px;
            background: linear-gradient(135deg, #007bff, #0056b3); /* Gradiente azul esportivo */
            color: white;
            padding-top: 1.5rem;
            box-shadow: 6px 0 15px rgba(0,0,0,0.25);
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
            padding: 15px 25px;
            text-decoration: none;
            font-weight: 500;
            border-radius: 10px;
            margin: 8px 15px;
            transition: all 0.3s ease;
            border-left: 5px solid transparent;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.1);
            border-left: 5px solid #ffc107; /* Destaque amarelo */
        }

        /* Conteúdo Principal */
        .content {
            flex-grow: 1;
            padding: 30px;
            display: flex;
            justify-content: center;
        }

        /* Card do Perfil */
        .profile-card {
            width: 100%;
            max-width: 900px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background-color: white;
        }

        /* Foto de Perfil */
        .profile-img-container {
            margin-top: -75px; /* Sobe a foto no topo do card */
            position: relative;
        }
        .profile-img {
            border: 6px solid white;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            object-fit: cover;
        }

        /* Destaque das informações */
        .info-header {
            background-color: #007bff;
            color: white;
            padding: 15px 25px;
            border-radius: 15px 15px 0 0;
            font-weight: 600;
            margin-bottom: 0;
            font-size: 1.1rem;
        }

        .info-list .list-group-item {
            border: none;
            border-bottom: 1px dashed #eee;
            padding: 12px 25px;
        }
        .info-list .list-group-item strong {
            color: #0056b3;
            min-width: 150px;
            display: inline-block;
        }

        /* Botão principal (Editar) */
        .btn-edit {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #333;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-edit:hover {
            background-color: #e0a800;
            border-color: #e0a800;
            color: #333;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="d-flex w-100">
    <div class="sidebar">
        <h4><i class="fa-solid fa-medal me-2"></i> <?= htmlspecialchars($nome_usuario) ?></h4>
        <a href="dashboard_atleta.php"><i class="fa-solid fa-gauge-high"></i> Início</a>
        <a href="perfil_atleta.php" class="active"><i class="fa-solid fa-user"></i> Meu Perfil</a>
        <a href="ver_vagas.php"><i class="fa-solid fa-magnifying-glass"></i> Explorar Vagas</a>
        <a href="minhas_candidaturas.php"><i class="fa-solid fa-clipboard-list"></i> Minhas Candidaturas</a>
        <a href="logout.php" class="text-warning" style="margin-top: auto; padding-bottom: 20px;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
    </div>

    <div class="content">
        <div class="profile-card">
            <div class="bg-primary p-5 text-center" style="border-radius: 20px 20px 0 0;">
                <h1 class="text-white mb-0">Perfil do Atleta</h1>
            </div>

            <div class="text-center profile-img-container">
                <img 
                    src="uploads/<?= htmlspecialchars($user['foto_perfil'] ?? 'default_avatar.png') ?>" 
                    class="rounded-circle profile-img" 
                    width="150" 
                    height="150" 
                    alt="Foto de Perfil"
                >
            </div>

            <div class="card-body pt-5">
                <h2 class="text-center mb-4 text-primary"><?= htmlspecialchars($user['nome_completo']) ?></h2>
                
                <h5 class="info-header mb-0"><i class="fa-solid fa-id-card me-2"></i> Informações de Contacto</h5>
                <ul class="list-group info-list mb-4">
                    <li class="list-group-item"><strong>Usuário:</strong> <?= htmlspecialchars($user['username']) ?></li>
                    <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></li>
                    <li class="list-group-item"><strong>Telefone:</strong> <?= htmlspecialchars($user['telefone']) ?></li>
                    <li class="list-group-item"><strong>Endereço:</strong> <?= htmlspecialchars($user['endereco']) ?></li>
                    <li class="list-group-item"><strong>Género:</strong> <?= htmlspecialchars($user['genero']) ?></li>
                </ul>

                <h5 class="info-header mb-0 bg-success"><i class="fa-solid fa-baseball-bat-ball me-2"></i> Estatísticas Desportivas</h5>
                <ul class="list-group info-list">
                    <li class="list-group-item"><strong>Modalidade:</strong> <?= htmlspecialchars($user['modalidade']) ?></li>
                    <li class="list-group-item"><strong>Posição:</strong> <?= htmlspecialchars($user['posicao']) ?></li>
                    <li class="list-group-item"><strong>Pé Preferido:</strong> <?= htmlspecialchars($user['pe']) ?></li>
                    <li class="list-group-item"><strong>Idade:</strong> <?= htmlspecialchars($user['idade']) ?> anos</li>
                    <li class="list-group-item"><strong>Clube Anterior:</strong> <?= htmlspecialchars($user['clube_anterior']) ?></li>
                    <li class="list-group-item"><strong>Situação Actual:</strong> <?= htmlspecialchars($user['situacao_atual']) ?></li>
                </ul>
            </div>

            <div class="p-4 d-grid gap-2">
                <a href="update_profile.php" class="btn btn-edit btn-lg">
                    <i class="fa-solid fa-pen me-2"></i> Editar Perfil
                </a>
                <a href="dashboard_atleta.php" class="btn btn-secondary btn-lg">
                    <i class="fa-solid fa-arrow-left me-2"></i> Voltar ao Painel
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>