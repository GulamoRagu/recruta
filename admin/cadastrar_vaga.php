<?php
ob_start();
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Buscar nome do admin logado
$admin_id = (int)$_SESSION['user_id'];
$nome_usuario = 'Admin';
if ($stmt = $conn->prepare('SELECT username FROM usuarios WHERE id = ? LIMIT 1')) {
    $stmt->bind_param('i', $admin_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $nome_usuario = $row['username'] ?? 'Admin';
        }
    }
    $stmt->close();
}

// Carregar recrutadores adicionados por este admin
$recrutadores = [];
if ($stmt = $conn->prepare("SELECT id, username FROM usuarios WHERE tipo = 'vendedor' AND criado_por = ? ORDER BY username")) {
    $stmt->bind_param('i', $admin_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $recrutadores[] = $row;
        }
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Vagas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        @media (min-width: 768px) {
            .sidebar {
                height: 100vh;
                position: fixed;
                width: 250px;
                background-color: #343a40;
                padding-top: 20px;
            }
            .content {
                margin-left: 260px;
                padding: 20px;
            }
        }

        @media (max-width: 767.98px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                background-color: #343a40;
            }
            .content {
                margin-left: 0;
                padding: 15px;
            }
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
    </style>
</head>
<body>

<!-- Menu mobile -->
<nav class="navbar navbar-dark bg-dark d-md-none">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><?= htmlspecialchars($nome_usuario) ?></span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar collapse d-md-block bg-dark" id="mobileMenu">
    <h4 class="text-center text-white d-none d-md-block"><?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_recrutador.php"><i class="fa-solid fa-chart-line"></i> Inicio</a>
   
    <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
</div>

<!-- Conteúdo -->
<div class="content">
    <div class="card shadow p-4">
        <h2 class="text-center"><i class="fa-solid fa-plus"></i> Cadastrar Vagas</h2>
<form action="process_cadastrar_vaga.php" method="POST"> 
    <div class="mb-3">
        <label for="nome" class="form-label">Nome da vaga</label>
        <input type="text" class="form-control" id="nome" name="nome" required>
    </div>
    <div class="mb-3">
        <label for="descricao" class="form-label">Descrição</label>
        <textarea class="form-control" id="descricao" name="descricao" required></textarea>
    </div>
    <div class="mb-3">
        <label for="genero_permitido" class="form-label">Gênero Permitido</label>
        <select name="genero_permitido" id="genero_permitido" class="form-select" required>
            <option value="">Selecione</option>
            <option value="Ambos">Ambos</option>
            <option value="Masculino">Masculino</option>
            <option value="Feminino">Feminino</option>
            
        </select>
    </div>
    <div class="mb-3">
        <label for="preco" class="form-label">Idade máxima</label>
        <input type="text" class="form-control" id="preco" name="preco" required>
    </div>
    <div class="mb-3">
        <label for="modalidade" class="form-label">Modalidade</label>
        <input type="text" class="form-control" id="modalidade" name="modalidade" required>
    </div>
    <div class="mb-3">
        <label for="posicao" class="form-label">Posição</label>
        <input type="text" class="form-control" id="posicao" name="posicao" required>
    </div>
    <div class="mb-3">
        <label for="data_validade" class="form-label">Data de Validade</label>
        <input type="date" class="form-control" id="data_validade" name="data_validade" required>
    </div>
    
    <div class="mb-3">
        <label for="recrutador" class="form-label">Recrutador Responsável</label>
        <select name="recrutador" id="recrutador" class="form-select" required>
            <?php foreach ($recrutadores as $rec): ?>
                <option value="<?= $rec['id'] ?>"><?= htmlspecialchars($rec['username']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
</form>


        <a href="dashboard_recrutador.php" class="btn btn-secondary mt-3 w-100">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>