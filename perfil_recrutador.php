<?php
ob_start();
session_start();
require 'db.php';

// Verifica se o usuário está autenticado e é um recrutador
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

// Recupera os dados do usuário logado
$user_id = intval($_SESSION['user_id']);
$query = $conn->prepare("SELECT username, email, nome_completo, telefone, endereco, idade, posicao FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

$nome_usuario = $user['nome_completo'] ?? 'Recrutador';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Recrutador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            padding: 15px;
            display: block;
            text-decoration: none;
            font-size: 18px;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            margin-left: 260px;
            padding: 20px;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                display: flex;
                flex-direction: row;
                justify-content: space-around;
                padding: 10px 0;
            }
            .sidebar h4 {
                display: none;
            }
            .sidebar a {
                font-size: 14px;
                padding: 10px;
            }
            .content {
                margin-left: 0;
                padding: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h4 class="text-center text-white"><?= htmlspecialchars($nome_usuario) ?></h4>
        <a href="dashboard_recrutador.php"><i class="fa-solid fa-chart-line"></i> Inicio</a>
        
        <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
    </div>

    <!-- Conteúdo Principal -->
    <div class="content">
        <div class="card shadow p-4">
            <h2 class="text-center"><i class="fa-solid fa-user"></i> Perfil de <?= htmlspecialchars($nome_usuario) ?></h2>
            <ul class="list-group">
                <li class="list-group-item"><strong>Usuário:</strong> <?= htmlspecialchars($user['username']) ?></li>
                <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></li>
                <li class="list-group-item"><strong>Nome Completo:</strong> <?= htmlspecialchars($user['nome_completo']) ?></li>
                <li class="list-group-item"><strong>Telefone:</strong> <?= htmlspecialchars($user['telefone']) ?></li>
                <li class="list-group-item"><strong>Endereço:</strong> <?= htmlspecialchars($user['endereco']) ?></li>
                <li class="list-group-item"><strong>Idade:</strong> <?= htmlspecialchars($user['idade']) ?></li>
                <li class="list-group-item"><strong>Cargo:</strong> <?= htmlspecialchars($user['posicao']) ?></li>
            </ul>
            <div class="d-grid gap-2 d-md-block mt-3 text-center">
                <a href="editar_recrutador.php" class="btn btn-primary">
                    <i class="fa-solid fa-pen"></i> Editar Perfil
                </a>
                <a href="dashboard_recrutador.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </div>

</body>
</html>