<?php

ob_start();
session_start();
require '../db.php';

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
        <span>Bem-vindo, <?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
        <a href="../index.php">Sair</a>
    </nav>
</header>

<div class="cards">
    <div class="card">
        <a href="usuarios.php">Atletas</a>
    </div>
    <div class="card">
        <a href="candidatos.php">Candidatos</a>
    </div>

    <div class="card">
        <a href="listar_vaga.php">Vagas</a>
    </div>
     <div class="card">
        <a href="criar_recrutador.php">Criar Recrutador</a>
    </div>
     <div class="card">
        <a href="recrutadores.php">Recrutador</a>
    </div>
    


    
    <!-- Você pode adicionar outros cards futuramente -->
</div>

</body>
</html>
