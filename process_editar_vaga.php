<?php
ob_start();
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$recrutador_id = (int) $_SESSION['user_id'];
$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo "<script>alert('ID inválido.'); window.location='vagas_recrutador.php';</script>";
    exit();
}

// Recebe e valida campos
$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$preco = intval($_POST['preco'] ?? 0); // idade máxima
$genero_permitido = $_POST['genero_permitido'] ?? 'Ambos';
$modalidade = trim($_POST['modalidade'] ?? '');
$posicao = trim($_POST['posicao'] ?? '');
$data_validade = $_POST['data_validade'] ?? '';

if ($nome === '' || $descricao === '' || $data_validade === '') {
    echo "<script>alert('Por favor preencha todos os campos obrigatórios.'); window.history.back();</script>";
    exit();
}

// Protege o formato da data (opcional: validar formato)
$date_ok = DateTime::createFromFormat('Y-m-d', $data_validade);
if (!$date_ok) {
    echo "<script>alert('Data de validade inválida.'); window.history.back();</script>";
    exit();
}

// Update seguro: só atualiza vaga pertencente ao recrutador logado
$sql = "UPDATE produtos 
        SET nome = ?, descricao = ?, preco = ?, genero_permitido = ?, modalidade = ?, posicao = ?, data_validade = ?
        WHERE id = ? AND recrutador_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "<script>alert('Erro no servidor (prepare).'); window.location='listar_vaga.php';</script>";
    exit();
}

$stmt->bind_param(
    "ssissssii",
    $nome,
    $descricao,
    $preco,
    $genero_permitido,
    $modalidade,
    $posicao,
    $data_validade,
    $id,
    $recrutador_id
);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
         header("Location: vagas_recrutador.php");
    } else {
        // 0 linhas afetadas: pode ser porque não era do recrutador ou sem mudanças
       echo "Erro ao actualizar vaga.";
    }
} else {
    echo "Erro ao actualizar vaga.";
}

$stmt->close();
$conn->close();
