<?php 
ob_start();
include '../db.php';
session_start();

// Verifica se quem está logado é ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $tipo = 'recrutador'; 
    $criado_por = intval($_SESSION['user_id']);
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (username, email, senha, tipo, criado_por) 
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $username, $email, $hash, $tipo, $criado_por);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        $erro = "Erro ao registrar: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Criar Recrutador</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #eef1f6;
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }

        /* Sidebar fixa */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 220px;
            height: 100%;
            background-color: #343a40;
            color: white;
            padding-top: 20px;
        }

        .sidebar h3 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 5px;
            border-radius: 6px;
        }

        .sidebar a:hover {
            background-color: #495057;
        }

        /* Conteúdo */
        .main-content {
            margin-left: 240px;
            padding: 40px;
        }

        /* Card do formulário */
        .card-form {
            max-width: 650px;
            margin: auto;
            padding: 35px;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0px 8px 25px rgba(0,0,0,0.12);
        }

        .page-title {
            text-align: center;
            font-weight: 700;
            margin-bottom: 25px;
            color: #1f2937;
        }

        .btn-primary {
            padding: 10px;
            font-size: 17px;
        }

        .btn-back {
            width: 100%;
        }

        @media(max-width:768px){
            .sidebar{
                width:100%;
                height:auto;
                position:relative;
            }
            .main-content{
                margin-left:0;
            }
        }

    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Admin</h3>
        <a href="dashboard.php">Dashboard</a>
        <a href="usuarios.php">Atletas</a>
        <a href="candidatos.php">Candidatos</a>
        <a href="listar_vaga.php">Vagas</a>
        <a href="criar_recrutador.php">Criar Recrutador</a>
        <a href="recrutadores.php">Recrutadores</a>
        <a href="../index.php" class="text-danger">Sair</a>
    </div>

    <!-- Conteúdo -->
    <div class="main-content">
        <div class="card card-form">

            <h2 class="page-title">Criar Novo Recrutador</h2>

            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= $erro ?></div>
            <?php endif; ?>

            <form action="criar_recrutador.php" method="POST">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome Completo:</label>
                        <input type="text" name="username" class="form-control" required placeholder="Nome do recrutador">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email:</label>
                        <input type="email" name="email" class="form-control" required placeholder="email@dominio.com">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Senha:</label>
                        <input type="password" name="senha" class="form-control" required placeholder="Crie uma senha segura">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Criar Recrutador</button>
                <a href="dashboard.php" class="btn btn-secondary mt-3 btn-back">Voltar</a>
            </form>

        </div>
    </div>

</body>
</html>
