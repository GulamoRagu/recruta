<?php
ob_start();
include 'db.php';
session_start();

// Verifica se o usuário está logado como cliente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}


// Buscar IDs das vagas em que o atleta já se candidatou
$user_id = intval($_SESSION['user_id']);
$candidaturas_ids = [];
$res_cand = $conn->query("SELECT produto_id FROM compras WHERE cliente_id = $user_id");
while($row_c = $res_cand->fetch_assoc()) {
    $candidaturas_ids[] = $row_c['produto_id'];
}


// Buscar o nome do usuário logado e género
$user_id = intval($_SESSION['user_id']);
$query = $conn->prepare("SELECT nome_completo, genero FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'cliente';
$genero_atleta = strtolower($user['genero'] ?? '');

// Filtros
$filtro_idade = $_GET['idade'] ?? '';
$filtro_modalidade = $_GET['modalidade'] ?? '';
$filtro_genero = $_GET['genero'] ?? ''; // novo filtro
$filtro_data = $_GET['data_validade'] ?? ''; // novo filtro de data

// Construir SQL com filtros
$sql = "SELECT id, nome, descricao, preco, genero_permitido, data_validade, modalidade, posicao 
        FROM produtos 
        WHERE data_validade >= CURDATE()"; // apenas vagas não expiradas

if (!empty($filtro_idade)) {
    $sql .= " AND preco >= " . intval($filtro_idade);
}
if (!empty($filtro_modalidade)) {
    $sql .= " AND modalidade LIKE '%" . $conn->real_escape_string($filtro_modalidade) . "%'";
}
if (!empty($filtro_genero)) {
    $sql .= " AND LOWER(genero_permitido) = '" . strtolower($conn->real_escape_string($filtro_genero)) . "'";
}
if (!empty($filtro_data)) {
    $sql .= " AND data_validade <= '" . $conn->real_escape_string($filtro_data) . "'";
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


<!-- Sidebar fixa para telas grandes -->
<div class="d-none d-lg-block sidebar-lg sidebar text-white">
    <h4 class="text-center"><?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_atleta.php"><i class="fa-solid fa-home"></i> Início</a>
    
    <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
</div>

<!-- Conteúdo principal -->
<div class="container main-content mt-4">
    <h2 class="text-center text-primary">Vagas Disponíveis</h2>

    <!-- Filtros -->
<?php


// Verifica se o usuário está logado como cliente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Buscar IDs das vagas em que o atleta já se candidatou
$candidaturas_ids = [];
$res_cand = $conn->query("SELECT produto_id FROM compras WHERE cliente_id = $user_id");
while($row_c = $res_cand->fetch_assoc()) {
    $candidaturas_ids[] = $row_c['produto_id'];
}

// Buscar nome do usuário e gênero
$query = $conn->prepare("SELECT nome_completo, genero FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'Atleta';
$genero_atleta = strtolower($user['genero'] ?? '');

// Filtros
$filtro_idade = $_GET['idade'] ?? '';
$filtro_modalidade = $_GET['modalidade'] ?? '';
$filtro_genero = $_GET['genero'] ?? '';
$filtro_data = $_GET['data_validade'] ?? '';

// Construir SQL
$sql = "SELECT * FROM produtos WHERE data_validade >= CURDATE()";

if (!empty($filtro_idade)) {
    $sql .= " AND preco >= " . intval($filtro_idade);
}
if (!empty($filtro_modalidade)) {
    $sql .= " AND modalidade LIKE '%" . $conn->real_escape_string($filtro_modalidade) . "%'";
}
if (!empty($filtro_genero)) {
    $sql .= " AND LOWER(genero_permitido) = '" . strtolower($conn->real_escape_string($filtro_genero)) . "'";
}
if (!empty($filtro_data)) {
    $sql .= " AND data_validade <= '" . $conn->real_escape_string($filtro_data) . "'";
}

$result = $conn->query($sql);
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
body { padding-top: 56px; }
.sidebar { background-color: #343a40; }
.sidebar a { color: white; padding: 15px; display: block; text-decoration: none; font-size: 18px; }
.sidebar a:hover { background-color: #495057; }
.card.expired { opacity: 0.6; }
@media (min-width: 992px) {
    .sidebar-lg { position: fixed; top: 0; left: 0; width: 250px; height: 100vh; padding-top: 60px; }
    .main-content { margin-left: 250px; }
}
</style>
</head>
<body>

<!-- Navbar e Sidebar -->

<nav class="navbar navbar-dark bg-dark fixed-top d-lg-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <span class="navbar-brand"><?= htmlspecialchars($nome_usuario) ?></span>
    </div>
</nav>

<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="offcanvasSidebar">
    <div class="offcanvas-header bg-dark text-white">
        <h5 class="offcanvas-title"><?= htmlspecialchars($nome_usuario) ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body sidebar">
        <a href="dashboard_atleta.php"><i class="fa-solid fa-home"></i> Início</a>
        <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
    </div>
</div>

<div class="d-none d-lg-block sidebar-lg sidebar text-white">
    <h4 class="text-center"><?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_atleta.php"><i class="fa-solid fa-home"></i> Início</a>
    <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
</div>

<!-- Conteúdo principal -->


<!-- Filtros -->
<form method="GET" class="row g-3 mb-4">
    <div class="col-md-3">
        <label for="genero" class="form-label">Género</label>
        <select name="genero" id="genero" class="form-select">
            <option value="">Todos</option>
            <option value="Masculino" <?= $filtro_genero=='Masculino'?'selected':'' ?>>Masculino</option>
            <option value="Feminino" <?= $filtro_genero=='Feminino'?'selected':'' ?>>Feminino</option>
        </select>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
    </div>
</form>

<div class="container-fluid py-5" style="background-color: #f0f0f0;">
    <div class="container">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $ja_candidatou = in_array($row['id'], $candidaturas_ids);
                ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm border-0 rounded-4 <?= $ja_candidatou ? 'bg-secondary text-white' : '' ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-primary mb-3"><?= htmlspecialchars($row['nome']) ?></h5>
                                <p class="card-text"><?= nl2br(htmlspecialchars($row['descricao'])) ?></p>
                                <p><strong>Idade Máxima:</strong> <?= htmlspecialchars($row['preco']) ?></p>
                                <p><strong>Gênero:</strong> <?= htmlspecialchars($row['genero_permitido']) ?></p>
                                <p><strong>Modalidade:</strong> <?= htmlspecialchars($row['modalidade']) ?></p>
                                <p><strong>Posição:</strong> <?= htmlspecialchars($row['posicao']) ?></p>
                                <p><strong>Validade:</strong> <?= (new DateTime($row['data_validade']))->format('d/m/Y') ?></p>
                                <div class="mt-auto text-center">
                                    <?php if(!$ja_candidatou): ?>
                                        <a href="candidatar_se.php?id=<?= $row['id'] ?>" class="btn btn-success btn-sm mt-2">Candidatar-se</a>
                                    <?php else: ?>
                                        <button class="btn btn-light btn-sm mt-2" disabled>Já Candidatado</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-muted">Nenhuma vaga disponível.</p>
            <?php endif; ?>
        </div>
    </div>
</div>


</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>






