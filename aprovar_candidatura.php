<?php
ob_start();
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['compras_id'], $_POST['acao'])) {
    $compras_id = intval($_POST['candidatura_id']);
    $acao = $_POST['acao'];

    if (!in_array($acao, ['Aprovado', 'Reprovado'])) {
        $_SESSION['msg'] = "Ação inválida.";
        header("Location: ver_candidaturas.php");
        exit();
    }

    $sql = "UPDATE compras SET acao = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $acao, $compras_id);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Candidatura $acao com sucesso!";
    } else {
        $_SESSION['msg'] = "Erro ao processar a candidatura.";
    }

    $stmt->close();
    $conn->close();

    header("Location: ver_candidaturas.php");
    exit();
}
?>