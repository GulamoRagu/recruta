<?php 
ob_start();
include '../db.php';
session_start();

// Verifica se quem está logado é ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    $tipo = 'recrutador'; // Tipo fixo
    $criado_por = intval($_SESSION['user_id']); // 👈 ID do admin
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    // Inserir com criado_por
    $sql = "INSERT INTO usuarios (username, email, senha, tipo, criado_por) 
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $username, $email, $hash, $tipo, $criado_por);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<p class='alert alert-danger'>Erro ao registrar: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

$conn->close();
?>


<form action="criar_recrutador.php" method="POST">
    <label>Nome Completo:</label>
    <input type="text" name="username" required><br>

    <label>Email:</label>
    <input type="email" name="email" required><br>

    <label>Senha:</label>
    <input type="password" name="senha" required><br>

    <button type="submit">Criar Recrutador</button>
</form>
