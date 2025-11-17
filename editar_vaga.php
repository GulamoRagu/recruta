<?php
ob_start();
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM produtos WHERE id = $id");
$produto = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
        }
        .sidebar a {
            color: white;
            padding: 15px;
            display: block;
            text-decoration: none;
            font-size: 18px;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        @media (max-width: 767.98px) {
            .sidebar {
                position: static;
                height: auto;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row flex-nowrap">
        <!-- Sidebar -->
        <nav class="col-12 col-md-3 col-lg-2 sidebar d-md-block d-none">
            <h4 class="text-center text-white mt-3">Recrutador</h4>
            <a href="dashboard_recrutador.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
            <a href="recrutador.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
            <a href="listar_vaga.php"><i class="fa-solid fa-box"></i> Listar Vagas</a>
            <a href="ver_candidaturas.php"><i class="fa-solid fa-money-bill"></i> Ver Candidaturas</a>
            <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
        </nav>

        <!-- Sidebar colapsável para mobile -->
        <div class="d-md-none bg-dark text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <span><strong>Menu</strong></span>
                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
                    <i class="fa fa-bars"></i>
                </button>
            </div>
            <div class="collapse mt-2" id="mobileMenu">
                <a href="dashboard_recrutador.php" class="d-block text-white">Dashboard</a>
                <a href="recrutador.php" class="d-block text-white">Meu Perfil</a>
                <a href="listar_vaga.php" class="d-block text-white">Listar Vagas</a>
                <a href="ver_candidaturas.php" class="d-block text-white">Ver Candidaturas</a>
                <a href="logout.php" class="d-block text-danger">Sair</a>
            </div>
        </div>

        <!-- Conteúdo -->
        <main class="col px-4 py-4">
            <div class="card shadow mx-auto" style="max-width: 600px;">
                <div class="card-body">
                    <h2 class="text-center"><i class="fa-solid fa-pen-to-square"></i> Editar Vaga</h2>
                    <form action="process_editar_vaga.php" method="POST">
                        <input type="hidden" name="id" value="<?= $produto['id'] ?>">

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($produto['nome']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" required><?= htmlspecialchars($produto['descricao']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="preco" class="form-label">Idade Máxima</label>
                            <input type="text" class="form-control" id="preco" name="preco" value="<?= htmlspecialchars($produto['preco']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="data_validade" class="form-label">Data de Validade</label>
                            <input type="date" class="form-control" id="data_validade" name="data_validade" value="<?= $produto['data_validade'] ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Atualizar</button>
                    </form>

                    <a href="listar_vaga.php" class="btn btn-secondary mt-3 w-100"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>