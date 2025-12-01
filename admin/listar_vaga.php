<?php
ob_start();
include '../db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: login.php");
    exit();
}


// Admin logado
$admin_id = (int)($_SESSION['user_id'] ?? 0);

// Buscar nome do admin para exibir na sidebar/navbar
$nome_usuario = 'Admin';
if ($stmt = $conn->prepare('SELECT username FROM usuarios WHERE id = ? LIMIT 1')) {
    $stmt->bind_param('i', $admin_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($rowAdmin = $res->fetch_assoc()) {
            $nome_usuario = $rowAdmin['username'] ?? 'Admin';
        }
    }
    $stmt->close();
}

// Seleciona produtos vinculados aos recrutadores criados por este admin
$sql = "
    SELECT 
        p.*, 
        u.username AS recrutador_nome
    FROM produtos p
    JOIN usuarios u 
        ON u.id = p.recrutador_id
    WHERE u.criado_por = $admin_id
    ORDER BY p.id DESC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Minhas Vagas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        body {
            background-color: #f8f9fa;
        }
        /* Sidebar fixa apenas para telas md+ */
        @media (min-width: 768px) {
            .sidebar {
                width: 250px;
                height: 100vh;
                position: fixed;
                background-color: #343a40;
                padding-top: 20px;
            }
            .content {
                margin-left: 260px;
                padding: 20px;
            }
        }
        /* Para telas menores, a sidebar some e o conteúdo ocupa toda largura */
        @media (max-width: 767.98px) {
            .content {
                padding: 20px 10px;
            }
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
        .card {
            max-width: 900px;
            margin: auto;
        }
        .card-title {
            font-weight: bold;
        }
        .card-footer {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>

<!-- Navbar com botão para abrir sidebar no mobile -->
<nav class="navbar navbar-dark bg-dark d-md-none">
    <div class="container-fluid">
        <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
        <span class="navbar-brand mb-0 h1">Minhas Vagas</span>
    </div>
</nav>

<!-- Sidebar fixa para md+ e offcanvas para mobile -->
<div class="offcanvas offcanvas-start bg-dark text-white d-md-none" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasSidebarLabel"><?= htmlspecialchars($nome_usuario) ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body p-0">
        <a href="dashboard_recrutador.php" class="sidebar"><i class="fa-solid fa-chart-line"></i> Inicio</a>

        <a href="logout.php" class="sidebar text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
    </div>
</div>

<div class="sidebar d-none d-md-block">
    <h4 class="text-center text-white"><?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_recrutador.php"><i class="fa-solid fa-chart-line"></i> Inicio</a>
  
    <a href="../login.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
</div>

<!-- Conteúdo -->
<div class="content">
    <div class="card shadow p-4">
        <h2 class="text-center"><i class="fa-solid fa-box"></i> Minhas Vagas</h2>
        <a href="cadastrar_vaga.php" class="btn btn-success mb-3">
            <i class="fa-solid fa-plus"></i> Cadastrar Nova Vaga
        </a>

        <?php if ($result->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php while ($row = $result->fetch_assoc()):
                    $data_validade = new DateTime($row['data_validade']);
                    $hoje = new DateTime();
                    $vencido = $data_validade < $hoje;
                ?>
                    <div class="col">
                        <div class="card h-100 shadow <?= $vencido ? 'border-danger' : '' ?>" style="<?= $vencido ? 'background-color: #ffe6e6;' : '' ?>">
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($row['nome']) ?></h6>
                                <p class="card-text">Descrição: <?= htmlspecialchars($row['descricao']) ?></p>
                                <p><strong>Idade Máxima:</strong> <?= htmlspecialchars($row['preco']) ?></p>
                                <p><strong>Modalidade:</strong> <?= htmlspecialchars($row['modalidade']) ?></p>
                                <p><strong>Posição:</strong> <?= htmlspecialchars($row['posicao']) ?></p>
                                <p><strong>Validade:</strong> <?= $data_validade->format('d/m/Y') ?></p>
                                <p><strong>Responsável:</strong> <?= htmlspecialchars($row['recrutador_nome']) ?></p>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <a href="./editar_vaga.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="./apagar_vaga.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja apagar?');">Apagar</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fa-solid fa-info-circle"></i> Nenhuma vaga cadastrada ainda.
            </div>
        <?php endif; ?>

        <a href="../login.php" class="btn btn-secondary mt-3">
            <i class="fa-solid fa-arrow-left"></i> Voltarr
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Script para confirmar exclusão já está no onclick do link apagar, então pode remover este bloco se desejar
</script>

</body>
</html>