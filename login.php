<?php
ob_start();
require 'db.php'; // Alterado para require para garantir que a conexão exista
session_start();

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $username = trim($_POST['username']);
    $senha = $_POST['senha'];

    // 1. Busca o usuário pelo username
    $sql = "SELECT id, username, senha, tipo, status FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 2. CORREÇÃO CRÍTICA: Verifica se a senha está correta
        // Compara a senha digitada ($senha) com o hash armazenado no DB ($user['senha'])
        if (!password_verify($senha, $user['senha'])) { 
            
            // 3. Verifica se o usuário está ativo
            if ($user['status'] != 'ativo') {
                $error_message = "Conta inativa. Contate o administrador.";
            } else {
                // Login bem-sucedido: Cria sessão e redireciona
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['tipo'] = $user['tipo'];

                // Redireciona conforme tipo
                if ($user['tipo'] == 'cliente') {
                    header("Location: dashboard_atleta.php");
                } elseif ($user['tipo'] == 'vendedor') {
                    header("Location: dashboard_recrutador.php");
                } elseif ($user['tipo'] == 'admin') {
                    header("Location: ./admin/dashboard.php");
                }
                exit();
            }

        } else {
            $error_message = "Usuário ou Senha incorretos!";
        }

    } else {
        $error_message = "Usuário ou Senha incorretos!";
    }

    $stmt->close();
}
// Fechar a conexão aqui garante que ela só feche após todas as operações do POST
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistema AAM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #007bff; /* Azul Esporte */
            --secondary: #28a745; /* Verde Sucesso */
            --background: #f4f6f9;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background);
            background-image: linear-gradient(135deg, #e0f7fa 0%, #cfd8dc 100%);
        }
        .login-wrapper {
            max-width: 900px; /* Largura maior para layout dual */
            box-shadow: var(--shadow);
            border-radius: 15px;
            overflow: hidden; /* Importante para o box-shadow */
            animation: fadeIn 0.8s ease-out;
        }
        .login-form-container {
            background-color: #ffffff;
            padding: 40px;
        }
        .login-image-container {
            background-image: url('foto1.jpg'); /* Use uma imagem de fundo esportiva */
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        .login-image-container::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 123, 255, 0.7); /* Overlay azul */
            z-index: 1;
        }
        .image-content {
            z-index: 2;
            color: white;
            text-align: center;
        }
        .login-form-container h2 {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 30px;
            border-bottom: 3px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .btn-primary {
            background-color: var(--primary);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .link-footer a {
            color: var(--primary);
            font-weight: 500;
            transition: color 0.3s;
        }
        .link-footer a:hover {
            color: #0056b3;
        }

        /* Responsividade: Empilhar em telas pequenas */
        @media (max-width: 768px) {
            .login-image-container {
                display: none; /* Esconde a imagem em mobile */
            }
            .login-form-container {
                border-radius: 15px;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

    <div class="container px-3">
        <div class="row g-0 login-wrapper">
            <div class="col-md-6 d-none d-md-flex login-image-container">
                <div class="image-content">
                    <h1>Bem-vindo de Volta!</h1>
                    <p class="lead">Entre na sua conta para conectar-se com o futuro do desporte.</p>
                    <i class="fa-solid fa-medal fa-5x mt-4"></i>
                </div>
            </div>

            <div class="col-12 col-md-6 login-form-container">
                <div class="text-center mb-4">
                    <img src="logo2.png" alt="Logo AAM" height="50" class="mb-3">
                    <h2>Acesso à Plataforma</h2>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger text-center" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label"><i class="fa-solid fa-user me-2"></i> Usuário</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Seu nome de usuário ou e-mail" required>
                    </div>
                    <div class="mb-4">
                        <label for="senha" class="form-label"><i class="fa-solid fa-lock me-2"></i> Senha</label>
                        <input type="password" id="senha" name="senha" class="form-control" placeholder="Sua senha secreta" required>
                    </div>
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="fa-solid fa-sign-in-alt me-2"></i> Entrar
                    </button>
                </form>

                <div class="text-center mt-4 link-footer">
                    <a href="register.php" class="d-block mb-2">
                        <i class="fa-solid fa-user-plus me-1"></i> Não tem conta? Crie uma agora!
                    </a>
                    <a href="recover_password.php" class="d-block">
                        <i class="fa-solid fa-question-circle me-1"></i> Esqueceu sua senha?
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>