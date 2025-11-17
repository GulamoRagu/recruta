<?php
ob_start();
include 'db.php';
session_start();

// Verifica se o usuário está logado como cliente (atleta)
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$cliente_id = $_SESSION['user_id'];

// Verifica se o ID da vaga foi passado
if (isset($_GET['id'])) {
    $produto_id = intval($_GET['id']); // Converte para número inteiro

    // Verifica se o atleta já se candidatou a esta vaga
    $verifica_candidatura = $conn->prepare("SELECT id FROM compras WHERE cliente_id = ? AND produto_id = ?");
    $verifica_candidatura->bind_param("ii", $cliente_id, $produto_id);
    $verifica_candidatura->execute();
    $verifica_candidatura->store_result();

    if ($verifica_candidatura->num_rows > 0) {
        // Se já existe candidatura, exibe alerta e redireciona
        echo "<script>
                alert('Você já se candidatou a esta vaga!');
                window.location='minhas_candidaturas.php';
              </script>";
        exit();
    }

    // Insere a nova candidatura na tabela 'candidaturas'
    $stmt = $conn->prepare("INSERT INTO compras (cliente_id, produto_id, data_compra, status) VALUES (?, ?, NOW(), 'Pendente')");
    $stmt->bind_param("ii", $cliente_id, $produto_id);

    if ($stmt->execute()) {
        echo "<script>
                alert('Candidatura realizada com sucesso!');
                window.location='minhas_candidaturas.php';
              </script>";
        exit();
    } else {
        echo "<script>
                alert('Erro ao processar a candidatura. Tente novamente!');
                window.location='ver_vagas.php';
              </script>";
    }
}
?>