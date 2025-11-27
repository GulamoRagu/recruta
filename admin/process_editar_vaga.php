<?php
ob_start();
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$id = $_POST['id'];
$nome = $_POST['nome'];
$descricao = $_POST['descricao'];
$preco = $_POST['preco'];
$data_validade = $_POST['data_validade'];

$query = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, data_validade = ? WHERE id = ?");
$query->bind_param("ssdsi", $nome, $descricao, $preco, $data_validade, $id);

if ($query->execute()) {
    header("Location: listar_vaga.php");
} else {
    echo "Erro ao actualizar vaga.";
}
?>