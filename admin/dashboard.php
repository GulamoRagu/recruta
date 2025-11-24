<?php
include "auth.php"; // garante que o admin está logado
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        header { display: flex; justify-content: space-between; align-items: center; }
        nav a { margin-left: 10px; text-decoration: none; color: blue; }
        .cards { display: flex; gap: 20px; margin-top: 30px; }
        .card { padding: 20px; border: 1px solid #ccc; border-radius: 8px; width: 200px; text-align: center; background: #f5f5f5; }
        .card a { text-decoration: none; color: #000; font-weight: bold; }
    </style>
</head>
<body>

<header>
    <h1>Dashboard do Admin</h1>
    <nav>
        <span>Bem-vindo, <?= htmlspecialchars($_SESSION['admin_usuario']) ?></span>
        <a href="logout.php">Sair</a>
    </nav>
</header>

<div class="cards">
    <div class="card">
        <a href="usuarios.php">Ver Usuários Cadastrados</a>
    </div>
    <div class="card">
        <a href="candidatos.php">Gerenciar Candidatos</a>
    </div>

    <div class="card">
        <a href="vagas.php">Gerenciar Vagas</a>
    </div>

    
    <!-- Você pode adicionar outros cards futuramente -->
</div>

</body>
</html>
