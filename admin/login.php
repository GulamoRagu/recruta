<?php
ob_start();
include "../db.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];

    $sql = "SELECT id, usuario, senha FROM admin WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        if (! password_verify($senha, $admin['senha'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_usuario'] = $admin['usuario'];

            header("Location: dashboard.php");
            exit();
        } else {
            echo "<p class='alert alert-danger'>Senha incorreta!</p>";
        }
    } else {
        echo "<p class='alert alert-danger'>Usuário não encontrado!</p>";
    }

    $stmt->close();
}
$conn->close();
?>


<!DOCTYPE html>
<html>
<head>
    <title>Login Admin</title>
</head>
<body>
    <h2>Login do Administrador</h2>

   

    <form method="POST">
        <label>Usuário:</label><br>
        <input type="text" name="usuario" required><br><br>

        <label>Senha:</label><br>
        <input type="password" name="senha" required><br><br>

        <button type="submit">Entrar</button>
    </form>
</body>
</html>
