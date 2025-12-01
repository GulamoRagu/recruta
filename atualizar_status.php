<?php
ob_start();
include 'db.php';
session_start();

// Verifica se o usuário está logado e é vendedor
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

// Verifica se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['compra_id'], $_POST['status'])) {
    $compra_id = intval($_POST['compra_id']);
    $novo_status = $_POST['status'];

    // Valida o status permitido
    $status_permitidos = ['Pendente', 'Aprovado', 'Rejeitado'];
    if (!in_array($novo_status, $status_permitidos)) {
        die("Status inválido.");
    }

    // Verifica se o vendedor tem permissão para atualizar esta compra
    $verifica_sql = "SELECT produtos.recrutador_id 
                     FROM compras 
                     JOIN produtos ON compras.produto_id = produtos.id 
                     WHERE compras.id = ?";
    $stmt = $conn->prepare($verifica_sql);
    $stmt->bind_param("i", $compra_id);
    $stmt->execute();
    $stmt->bind_result($vendedor_id);
    $stmt->fetch();
    $stmt->close();

    if ($vendedor_id != $_SESSION['user_id']) {
        die("Você não tem permissão para atualizar este status.");
    }

    // Atualiza o status
    $update_sql = "UPDATE compras SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $novo_status, $compra_id);
    
    if ($stmt->execute()) {
        header("Location: ver_candidaturas.php"); // redireciona após sucesso
        exit();
    } else {
        echo "Erro ao atualizar status.";
    }

    $stmt->close();
} else {
    echo "Requisição inválida.";
}

$conn->close();
?>