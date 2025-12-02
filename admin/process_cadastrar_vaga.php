<?php
ob_start();
include '../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Campos do formulário
$nome          = $_POST['nome'] ?? '';
$descricao     = $_POST['descricao'] ?? '';
$preco         = $_POST['preco'] ?? '';
$genero_permitido = $_POST['genero_permitido']?? '';
$data_validade = $_POST['data_validade'] ?? '';
$modalidade    = $_POST['modalidade'] ?? '';
$posicao       = $_POST['posicao'] ?? '';
$recrutador_id = isset($_POST['recrutador']) ? (int)$_POST['recrutador'] : 0;
$admin_id      = (int)$_SESSION['user_id'];

// Validação simples
if ($nome === '' || $descricao === '' || $preco === '' ||  $genero_permitido === '' || $data_validade === '' || $modalidade === '' || $posicao === '' || $recrutador_id <= 0) {
    die("Todos os campos são obrigatórios e deve selecionar um recrutador.");
}

// Verificar se o recrutador pertence a este admin
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'vendedor' AND criado_por = ? LIMIT 1");
$stmt->bind_param("ii", $recrutador_id, $admin_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    die("Recrutador inválido para este administrador.");
}
$stmt->close();

// Inserir vaga na tabela produtos
$query = $conn->prepare("
    INSERT INTO produtos (nome, descricao, preco, genero_permitido, data_validade, modalidade, posicao, recrutador_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$query->bind_param("ssdssssi", $nome, $descricao,  $preco, $genero_permitido, $data_validade, $modalidade, $posicao, $recrutador_id);

if ($query->execute()) {
    header("Location: listar_vaga.php");
    exit();
} else {
    echo "Erro ao cadastrar vaga: " . $conn->error;
}
?>
