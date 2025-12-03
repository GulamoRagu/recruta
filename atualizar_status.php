<?php
ob_start();
include 'db.php';
session_start();

// Verifica se o usuário está logado e é vendedor
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['compra_id'], $_POST['status'])) {
    $compra_id = intval($_POST['compra_id']);
    $novo_status = $_POST['status'];
    $mensagem = trim($_POST['mensagem'] ?? '');

    // ... validação do status e permissão do vendedor (igual ao seu código)

    // Atualiza o status
    $update_sql = "UPDATE compras SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $novo_status, $compra_id);

    if ($stmt->execute()) {
        // Se houver mensagem, insere na tabela mensagens_status
        if (!empty($mensagem)) {
            $insert_msg = "INSERT INTO mensagens_status (compra_id, remetente, mensagem) VALUES (?, 'recrutador', ?)";
            $stmt_msg = $conn->prepare($insert_msg);
            $stmt_msg->bind_param("is", $compra_id, $mensagem);
            $stmt_msg->execute();
            $stmt_msg->close();
        }

        header("Location: ver_candidaturas.php"); // redireciona
        exit();
    } else {
        echo "Erro ao atualizar status.";
    }

    $stmt->close();
}
