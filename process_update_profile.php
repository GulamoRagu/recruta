<?php
ob_start();
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $nome_completo = $_POST['nome_completo'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];
    $idade = $_POST['idade'];
    $posicao = $_POST['posicao'];

    $query = $conn->prepare("UPDATE usuarios SET nome_completo = ?, telefone = ?, endereco = ?, idade = ?, posicao = ? WHERE id = ?");
    $query->bind_param("sssssi", $nome_completo, $telefone, $endereco, $idade, $posicao, $user_id);
    
    if ($query->execute()) {
        // Redireciona para o dashboard, dependendo do tipo de usuário
        header("Location: perfil_" . $_SESSION['tipo'] . ".php?sucesso=1");
        exit();
    } else {
        echo "Erro ao actualizar perfil.";
    }
}
?>