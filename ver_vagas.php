<?php
ob_start();
include 'db.php';
session_start();

// Verifica se o usuário está logado como cliente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

// Buscar o nome do usuário logado
$user_id = intval($_SESSION['user_id']);
$query = $conn->prepare("SELECT nome_completo FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'Atleta';

// Filtros
$filtro_idade = $_GET['idade'] ?? '';
$filtro_modalidade = $_GET['modalidade'] ?? '';

// Construir SQL com filtros
// $sql = "SELECT id, nome, descricao, preco, data_validade, modalidade, posicao FROM produtos WHERE 1=1";
$sql = "SELECT id, nome, descricao, preco, data_validade, modalidade, posicao 
        FROM produtos 
        WHERE data_validade >= DATE_SUB(NOW(), INTERVAL 5 DAY)";

if (!empty($filtro_idade)) {
    $sql .= " AND preco >= " . intval($filtro_idade);
}
if (!empty($filtro_modalidade)) {
    $sql .= " AND modalidade LIKE '%" . $conn->real_escape_string($filtro_modalidade) . "%'";
}

$query = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vagas Disponíveis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            padding-top: 56px;
        }
        .sidebar {
            background-color: #343a40;
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
        .card.expired {
            opacity: 0.6;
        }
        @media (min-width: 992px) {
            .sidebar-lg {
                position: fixed;
                top: 0;
                left: 0;
                width: 250px;
                height: 100vh;
                padding-top: 60px;
            }
            .main-content {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>

<!-- Navbar (para mobile) -->
<nav class="navbar navbar-dark bg-dark fixed-top d-lg-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <span class="navbar-brand"><?= htmlspecialchars($nome_usuario) ?></span>
    </div>
</nav>

<!-- Sidebar (Offcanvas para mobile, fixo para desktop) -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="offcanvasSidebar">
    <div class="offcanvas-header bg-dark text-white">
        <h5 class="offcanvas-title"><?= htmlspecialchars($nome_usuario) ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body sidebar">
        <a href="dashboard_atleta.php"><i class="fa-solid fa-home"></i> Início</a>
        <a href="perfil_atleta.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
        <a href="minhas_candidaturas.php"><i class="fa-solid fa-box"></i> Minhas Candidaturas</a>
        <a href="suporte_cliente.php"><i class="fa-solid fa-headset"></i> Suporte</a>
        <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
    </div>
</div>

<!-- Sidebar fixa para telas grandes -->
<div class="d-none d-lg-block sidebar-lg sidebar text-white">
    <h4 class="text-center"><?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_atleta.php"><i class="fa-solid fa-home"></i> Início</a>
    <a href="perfil_atleta.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
    <a href="minhas_candidaturas.php"><i class="fa-solid fa-box"></i> Minhas Candidaturas</a>
    <a href="suporte_cliente.php"><i class="fa-solid fa-headset"></i> Suporte</a>
    <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
</div>

<!-- Conteúdo principal -->
<div class="container main-content mt-4">
    <h2 class="text-center text-primary">Vagas Disponíveis</h2>

    <!-- Filtros -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label for="idade" class="form-label">Sua idade actual:</label>
            <input type="number" name="idade" id="idade" class="form-control" placeholder="Ex: 20" value="<?= htmlspecialchars($filtro_idade) ?>">
        </div>
        <div class="col-md-4">
            <label for="modalidade" class="form-label">Modalidade:</label>
            <input type="text" name="modalidade" id="modalidade" class="form-control" placeholder="Ex: Futebol" value="<?= htmlspecialchars($filtro_modalidade) ?>">
        </div>
        <div class="col-md-4 align-self-end">
            <button type="submit" class="btn btn-primary w-100">Procurar</button>
        </div>
    </form>

    <!-- Lista de Vagas -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php while ($row = $query->fetch_assoc()):
        $data_validade = new DateTime($row['data_validade']);
        $hoje = new DateTime();
        $vencido = $data_validade < $hoje;
    ?>
        <div class="col">
            <div class="card h-100 <?= $vencido ? 'border-danger expired' : '' ?>">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= htmlspecialchars($row['nome']) ?></h5>
                    <p class="card-text"><?= nl2br(htmlspecialchars($row['descricao'])) ?></p>
                    <p><strong>Idade Máxima:</strong> <?= htmlspecialchars($row['preco']) ?></p>
                    <p><strong>Modalidade:</strong> <?= htmlspecialchars($row['modalidade']) ?></p>
                    <p><strong>Posição:</strong> <?= htmlspecialchars($row['posicao']) ?></p>
                    <p>
                        <strong>Validade:</strong> <?= $data_validade->format('d/m/Y') ?>
                        <?= $vencido ? '<span class="text-danger fw-bold">(Expirou)</span>' : '' ?>
                    </p>
                </div>
                <div class="card-footer bg-white border-0 text-center">
                    <?php if (!$vencido): ?>
                        <a href="candidatar_se.php?id=<?= $row['id'] ?>" class="btn btn-success w-100">
                            <i class="fa-solid fa-user-check"></i> Candidatar-se
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100" disabled>Vaga Expirada</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>