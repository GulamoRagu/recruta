<?php
ob_start();
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}
// Buscar o nome do usuário logado
$user_id = intval($_SESSION['user_id']);
$query = $conn->prepare("SELECT nome_completo FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'Atleta';


$user_id = intval($_SESSION['user_id']); // Proteção contra SQL Injection
$query = $conn->prepare(
    "SELECT username, email, nome_completo, telefone, endereco, idade, posicao,
            clube_anterior, modalidade, pe, genero, situacao_atual, foto_perfil
     FROM usuarios
     WHERE id = ?"
  );
//$query = $conn->prepare("SELECT username, email, nome_completo, telefone, endereco, idade, posicao, foto_perfil FROM usuarios WHERE id = ?");

//$query = $conn->prepare("SELECT username, email, nome_completo, telefone, endereco, idade, posicao FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perfil do Atleta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    body {
      background-color: #f8f9fa;
    }

    .sidebar {
      background-color: #343a40;
      padding-top: 20px;
      color: white;
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

    .main-container {
      display: flex;
      flex-direction: row;
      min-height: 100vh;
    }

    @media (max-width: 768px) {
      .main-container {
        flex-direction: column;
      }

      .sidebar {
        width: 100%;
        height: auto;
        position: relative;
      }

      .content {
        margin-left: 0 !important;
      }
    }

    .content {
      flex: 1;
      padding: 20px;
    }

    .card {
      max-width: 700px;
      margin: auto;
    }

    img.rounded-circle {
      object-fit: cover;
    }
  </style>
</head>
<body>

<div class="main-container">
  <!-- Sidebar -->
  <div class="sidebar">
    <h4 class="text-center"><?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_atleta.php"><i class="fa-solid fa-home"></i> Início</a>
    <a href="ver_vagas.php"><i class="fa-solid fa-user"></i> Ver vagas</a>
    <a href="minhas_candidaturas.php"><i class="fa-solid fa-box"></i> Minhas Candidaturas</a>
    <a href="suporte_atleta.php"><i class="fa-solid fa-headset"></i> Suporte</a>
    <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
  </div>

  <!-- Conteúdo -->
  <div class="content">
    <div class="card shadow p-4">
      <h2 class="text-center"><i class="fa-solid fa-user"></i> Perfil do <?= htmlspecialchars($nome_usuario) ?></h2>

      <!-- Foto -->
      <div class="text-center mb-3">
        <img 
          src="uploads/<?= htmlspecialchars($user['foto_perfil'] ?? 'default_avatar.png') ?>" 
          class="rounded-circle border" 
          width="150" 
          height="150" 
          alt="Foto de Perfil"
        >
      </div>

      <!-- Dados -->
      <ul class="list-group">
        <li class="list-group-item"><strong>Usuário:</strong> <?= htmlspecialchars($user['username']) ?></li>
        <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></li>
        <li class="list-group-item"><strong>Nome Completo:</strong> <?= htmlspecialchars($user['nome_completo']) ?></li>
        <li class="list-group-item"><strong>Telefone:</strong> <?= htmlspecialchars($user['telefone']) ?></li>
        <li class="list-group-item"><strong>Endereço:</strong> <?= htmlspecialchars($user['endereco']) ?></li>
        <li class="list-group-item"><strong>Idade:</strong> <?= htmlspecialchars($user['idade']) ?> anos</li>
        <li class="list-group-item"><strong>Posição:</strong> <?= htmlspecialchars($user['posicao']) ?></li>
        <li class="list-group-item"><strong>Clube Anterior:</strong> <?= htmlspecialchars($user['clube_anterior']) ?></li>
        <li class="list-group-item"><strong>Modalidade:</strong> <?= htmlspecialchars($user['modalidade']) ?></li>
        <li class="list-group-item"><strong>Pé Preferido:</strong> <?= htmlspecialchars($user['pe']) ?></li>
        <li class="list-group-item"><strong>Género:</strong> <?= htmlspecialchars($user['genero']) ?></li>
        <li class="list-group-item"><strong>Situação Atual:</strong> <?= htmlspecialchars($user['situacao_atual']) ?></li>
      </ul>

      <a href="update_profile.php" class="btn btn-primary mt-3">
        <i class="fa-solid fa-pen"></i> Editar Perfil
      </a>
      <a href="dashboard_atleta.php" class="btn btn-secondary mt-3">
        <i class="fa-solid fa-arrow-left"></i> Voltar
      </a>
    </div>
  </div>
</div>

</body>
</html>