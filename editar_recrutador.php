<?php
ob_start();
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome_completo'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];
    $idade = $_POST['idade'];
    $posicao = $_POST['posicao'];

    $stmt = $conn->prepare("UPDATE usuarios SET nome_completo=?, telefone=?, email=?, endereco=?, idade=?, posicao=? WHERE id=?");
    $stmt->bind_param("ssssisi", $nome, $telefone, $email, $endereco, $idade, $posicao, $user_id);
    $stmt->execute();

    header("Location: perfil_recrutador.php?edit=success");
    exit();
}

$query = $conn->prepare("SELECT username, email, nome_completo, telefone, endereco, idade, posicao FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2>Editar Perfil do Recrutador</h2>
        <form method="POST">
            <div class="mb-3">
                <label>Nome Completo</label>
                <input type="text" name="nome_completo" class="form-control" value="<?= htmlspecialchars($user['nome_completo']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Telefone</label>
                <input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($user['telefone']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Endereço</label>
                <input type="text" name="endereco" class="form-control" value="<?= htmlspecialchars($user['endereco']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Idade</label>
                <input type="number" name="idade" class="form-control" value="<?= htmlspecialchars($user['idade']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Cargo</label>
                <input type="text" name="posicao" class="form-control" value="<?= htmlspecialchars($user['posicao']) ?>" required>
            </div>
            <button type="submit" class="btn btn-success">Salvar Alterações</button>
            <a href="perfil_recrutador.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>