<?php
ob_start();
session_start();
require 'db.php';

// Verifica se o usuário está autenticado e é um recrutador
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

// Recupera os dados do usuário logado de forma segura
$user_id = intval($_SESSION['user_id']);

// CORREÇÃO E MELHORIA: A coluna 'idade' e 'posicao' geralmente não são relevantes para recrutadores/empresas.
// Assumi que 'posicao' se refere ao cargo/função do recrutador dentro da empresa.
$query = $conn->prepare("SELECT username, email, nome_completo, telefone, endereco, posicao FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$query->close();

$nome_usuario = $user['nome_completo'] ?? 'Recrutador';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Recrutador | <?= htmlspecialchars($nome_usuario) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    
    <style>
        :root {
            --primary: #007bff; /* Azul */
            --dark: #343a40; /* Cinza Escuro */
            --warning: #ffc107; /* Amarelo */
        }
        
        /* Estrutura Geral */
        body { 
            background-color: #f4f6f9; 
            font-family: 'Poppins', sans-serif;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Moderna (Mesmo estilo do Dashboard) */
        .sidebar {
            background: linear-gradient(180deg, var(--dark), #212529);
            color: white;
            min-width: 250px;
            max-width: 250px;
            padding-top: 20px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            height: 100%;
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
            border-left-color: var(--warning);
        }
        .sidebar h4 {
            font-weight: 700;
            padding: 10px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
            text-align: center;
        }

        /* Conteúdo Principal */
        .content { 
            flex-grow: 1;
            margin-left: 250px; 
            padding: 30px;
        }
        
        /* Card de Perfil */
        .profile-card {
            max-width: 700px;
            margin: 0 auto;
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            background-color: var(--primary);
            color: white;
            padding: 25px;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            text-align: center;
        }
        .profile-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .profile-card h2 {
            font-weight: 700;
            margin-bottom: 0;
        }
        .list-group-item {
            padding: 15px 20px;
            border: none;
            border-bottom: 1px solid #eee;
        }
        .list-group-item:last-child {
            border-bottom: none;
        }
        .list-group-item strong {
            font-weight: 600;
            color: var(--dark);
            min-width: 150px; /* Alinhamento visual */
            display: inline-block;
        }

        /* Responsividade (Mobile) */
        @media (max-width: 992px) {
            .sidebar { 
                position: relative; 
                width: 100%; 
                min-height: auto;
                box-shadow: none;
                padding-bottom: 10px;
                display: flex; 
                flex-wrap: wrap;
                justify-content: center;
            }
            .sidebar h4 { display: none; }
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
        <a href="dashboard_recrutador.php"><i class="fa-solid fa-gauge-high"></i> Inicio</a>
        <a href="perfil_recrutador.php" class="active"><i class="fa-solid fa-user"></i> Meu Perfil</a>
        <a href="vagas_recrutador.php"><i class="fa-solid fa-briefcase"></i> Gerir Vagas</a>
        <a href="ver_candidaturas.php"><i class="fa-solid fa-list-check"></i> Candidaturas</a>
        <a href="logout.php" class="text-warning mt-auto"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
    </nav>

    <main class="content">
        <div class="card profile-card">
            
            <div class="profile-header">
                <i class="fa-solid fa-building profile-icon"></i>
                <h2>Dados do Responsavel</h2>
                <p class="lead mb-0"><?= htmlspecialchars($user['posicao'] ?? 'Recrutador(a) Profissional') ?></p>
            </div>
            
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fa-solid fa-id-card me-3 text-secondary"></i>
                        <strong>Nome Completo:</strong> 
                        <?= htmlspecialchars($user['nome_completo']) ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fa-solid fa-envelope me-3 text-secondary"></i>
                        <strong>Email:</strong> 
                        <?= htmlspecialchars($user['email']) ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fa-solid fa-user-tag me-3 text-secondary"></i>
                        <strong>Usuário:</strong> 
                        <?= htmlspecialchars($user['username']) ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fa-solid fa-phone me-3 text-secondary"></i>
                        <strong>Telefone:</strong> 
                        <?= htmlspecialchars($user['telefone']) ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fa-solid fa-location-dot me-3 text-secondary"></i>
                        <strong>Endereço:</strong> 
                        <?= htmlspecialchars($user['endereco']) ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fa-solid fa-briefcase me-3 text-secondary"></i>
                        <strong>Cargo/Função:</strong> 
                        <?= htmlspecialchars($user['posicao']) ?>
                    </li>
                </ul>
            </div>
            
            <div class="card-footer bg-light p-4 text-center">
                <a href="editar_recrutador.php" class="btn btn-primary btn-lg me-2 shadow-sm">
                    <i class="fa-solid fa-pen-to-square me-2"></i> Editar Informações
                </a>
                <a href="dashboard_recrutador.php" class="btn btn-secondary btn-lg shadow-sm">
                    <i class="fa-solid fa-arrow-left me-2"></i> Inicio
                </a>
            </div>
        </div>
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>