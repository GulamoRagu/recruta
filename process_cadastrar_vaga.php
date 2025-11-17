<?php
ob_start();
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$nome = $_POST['nome'];
$descricao = $_POST['descricao'];
$preco = $_POST['preco'];
$data_validade = $_POST['data_validade'];
$modalidade = $_POST['modalidade'];
$posicao = $_POST['posicao'];
$vendedor_id = $_SESSION['user_id'];

$query = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, data_validade, modalidade, posicao, vendedor_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$query->bind_param("ssdsssi", $nome, $descricao, $preco, $data_validade, $modalidade, $posicao, $vendedor_id);

if ($query->execute()) {
    header("Location: listar_vaga.php");
} else {
    echo "Erro ao cadastrar vaga.";
}
?>