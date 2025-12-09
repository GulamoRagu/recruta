<?php
ob_start();
include 'db.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $senha    = $_POST['senha'];
    
    // NOVOS CAMPOS AQUI
    $genero   = trim($_POST['genero']); 
    $endereco = trim($_POST['endereco']);
    // FIM NOVOS CAMPOS

    $tipo     = 'cliente'; // Força todos como atleta/cliente
    $hash     = password_hash($senha, PASSWORD_DEFAULT);

    // 1. Validação Simples de Campos
    if (empty($username) || empty($email) || empty($senha) || empty($genero) || empty($endereco)) {
        $error_message = "Todos os campos (incluindo Gênero e Endereço) são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Formato de e-mail inválido.";
    } elseif (strlen($senha) < 6) {
        $error_message = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($genero != 'M' && $genero != 'F') { // Validação simples do gênero
        $error_message = "Gênero inválido selecionado.";
    } else {
        // 2. Verificação de Duplicidade (Username ou Email)
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ? LIMIT 1");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $error_message = "Nome de usuário ou e-mail já registrado. Tente outro.";
        } else {
            // 3. Inserção Segura (Incluindo gênero e endereço)
            // Certifique-se de que as colunas 'genero' e 'endereco' existam na sua tabela 'usuarios'
            $sql = "INSERT INTO usuarios (username, email, senha, tipo, genero, endereco) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                 // Erro na preparação
                $error_message = "Erro interno ao preparar o registro: " . $conn->error;
            } else {
                // ATENÇÃO: A string de tipos é 'ssssss' (6 strings)
                $stmt->bind_param("ssssss", $username, $email, $hash, $tipo, $genero, $endereco);

                if ($stmt->execute()) {
                    // Sucesso: Redireciona para a página principal ou login
                    header("Location: login.php?registered=true");
                    exit();
                } else {
                    $error_message = "Erro ao registrar no banco de dados: " . $stmt->error;
                }
                $stmt->close();
            }
        }
        $stmt_check->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Atleta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --background: #f4f6f9;
        }

        body {
            background-color: var(--background);
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .register-card {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 100%;
        }
        .register-card h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            color: var(--primary);
        }
        .input-group-text {
            background-color: #eee;
            border: 1px solid #ced4da;
            border-right: none;
        }
        /* Ajuste para o select */
        .input-group .form-select {
             border-radius: 0 8px 8px 0 !important;
             padding: 10px 15px;
             font-size: 1rem;
        }
        .form-control {
            border-radius: 0 8px 8px 0 !important;
            padding: 10px 15px;
            font-size: 1rem;
        }
        .btn-primary {
            background-color: var(--primary);
            border: none;
            padding: 12px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--secondary);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>

    <div class="register-card">
        <h2><i class="fa-solid fa-running me-2"></i> Cadastro de Atleta</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="post">
            
            <div class="mb-3">
                <label class="form-label visually-hidden">Nome de Usuário</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Nome de Usuário" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label visually-hidden">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="Seu Melhor E-mail" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label visually-hidden">Gênero</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-venus-mars"></i></span>
                    <select name="genero" class="form-select" required>
                        <option value="" disabled selected>Selecione seu Gênero</option>
                        <option value="M" <?= (($_POST['genero'] ?? '') == 'M') ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= (($_POST['genero'] ?? '') == 'F') ? 'selected' : '' ?>>Feminino</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label visually-hidden">Endereço</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-location-dot"></i></span>
                    <input type="text" name="endereco" class="form-control" placeholder="Seu Endereço Completo" required value="<?= htmlspecialchars($_POST['endereco'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label visually-hidden">Senha</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="senha" class="form-control" placeholder="Senha (mín. 6 caracteres)" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Registrar Conta
            </button>
        </form>

        <a href="login.php" class="back-link">
            Já tem uma conta? <span class="fw-bold">Fazer Login</span>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>