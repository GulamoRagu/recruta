<?php
ob_start();
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $senha = $_POST['senha'];

    $sql = "SELECT id, username, senha, tipo FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (!password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['tipo'] = $user['tipo'];

            if ($user['tipo'] == 'cliente') {
                header("Location: dashboard_atleta.php");
            } else {
                header("Location: dashboard_recrutador.php");
            }
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
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Arial', sans-serif;
        }
        .login-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: auto;
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }
        .form-label {
            font-weight: bold;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
        }
        .alert {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .text-center {
            text-align: center;
            margin-top: 20px;
        }
        .btn-link {
            background: none;
            border: none;
            color: #007bff;
            text-decoration: underline;
            cursor: pointer;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

    <div class="container px-3">
        <div class="login-container">
            <h2>Login</h2>
            <form action="login.php" method="post">
                <div class="mb-3">
                    <label class="form-label">Usuário</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Senha</label>
                    <input type="password" name="senha" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <div class="text-center">
                <a href="register.php" class="btn btn-link">Ainda não se registrou? Registre-se</a><br>
                <a href="recover_password.php" class="btn btn-link">Esqueceu a senha?</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
