<?php
ob_start();
include 'db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}


// Admin logado
$vendedor_id = (int)($_SESSION['user_id'] ?? 0);

// Buscar nome do admin para exibir na sidebar/navbar
$nome_usuario = 'Recrutador';
if ($stmt = $conn->prepare('SELECT username FROM usuarios WHERE id = ? LIMIT 1')) {
    $stmt->bind_param('i', $vendedor_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($rowAdmin = $res->fetch_assoc()) {
            $nome_usuario = $rowAdmin['username'] ?? 'Recrutador';
        }
    }
    $stmt->close();
}

// Seleciona produtos vinculados aos recrutadores criados por este admin
$status = $_GET['status'] ?? '';
$genero = $_GET['genero'] ?? '';

$sql = "
    SELECT p.*, u.username AS recrutador_nome
    FROM produtos p
    JOIN usuarios u ON u.id = p.recrutador_id
    WHERE p.recrutador_id = $vendedor_id
";

// FILTRO POR STATUS
if ($status === 'activa') {
    $sql .= " AND p.data_validade >= CURDATE() ";
}

if ($status === 'expirada') {
    $sql .= " AND p.data_validade < CURDATE() ";
}

// FILTRO POR GÉNERO
if (!empty($genero)) {
    $sql .= " AND p.genero_permitido = '$genero' ";
}

$sql .= " ORDER BY p.id DESC";


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
        background: #eef1f5;
        font-family: 'Inter', sans-serif;
    }

    /* Sidebar */
    .sidebar {
        width: 260px;
        height: 100vh;
        position: fixed;
        background: #1e293b;
        padding-top: 20px;
        box-shadow: 2px 0px 10px rgba(0,0,0,0.15);
    }

    .sidebar h4 {
        font-weight: 600;
        margin-bottom: 20px;
    }

    .sidebar a {
        color: #cbd5e1;
        padding: 14px 18px;
        display: block;
        text-decoration: none;
        font-size: 15px;
        border-radius: 6px;
        margin: 5px 10px;
        transition: 0.3s ease;
    }

    .sidebar a:hover {
        background: #334155;
        color: white;
    }

    .sidebar .text-danger:hover {
        background: #ef4444;
        color: white !important;
    }

    /* Conteúdo */
    .content {
        margin-left: 270px;
        padding: 25px;
    }

    /* Cards */
    .card-modern {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    }

    .vaga-card {
        border-radius: 12px;
        transition: 0.2s ease;
        border: none;
    }

    .vaga-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 18px rgba(0,0,0,0.10);
    }

    .vaga-card .card-footer {
        background: transparent;
        border-top: none;
    }

    .btn-modern {
        border-radius: 8px;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .content {
            margin-left: 0;
        }
    }
</style>

</head>
<body>

<!-- Navbar com botão para abrir sidebar no mobile -->
<nav class="navbar navbar-dark bg-dark d-md-none">
  <div class="card-modern mb-4 text-center">
    <h2 class="fw-bold text-dark">
        <i class="fa-solid fa-briefcase"></i> Minhas Vagas
    </h2>
    <p class="text-muted">Gerencie todas as vagas publicadas</p>
</div>

</nav>

<!-- Sidebar fixa para md+ e offcanvas para mobile -->
<div class="offcanvas offcanvas-start bg-dark text-white d-md-none" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasSidebarLabel"><?= htmlspecialchars($nome_usuario) ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body p-0">
        <a href="dashboard_recrutador.php"><i class="fa-solid fa-house"></i> Início</a>
<a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
    </div>
</div>

<div class="sidebar d-none d-md-block">
    <h4 class="text-center text-white"><?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_recrutador.php"><i class="fa-solid fa-chart-line"></i> Inicio</a>
  
    <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
</div>

<!-- Conteúdo -->
<div class="content">
    <div class="card shadow p-4">
        <h2 class="text-center"><i class="fa-solid fa-box"></i> Vagas Disponiveis</h2>
        
<form method="GET" class="row g-3 mb-4">

    <!-- FILTRO STATUS -->
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="">-- Todos --</option>
            <option value="activa" <?= ($_GET['status'] ?? '') == 'activa' ? 'selected' : '' ?>>Activa</option>
            <option value="expirada" <?= ($_GET['status'] ?? '') == 'expirada' ? 'selected' : '' ?>>Expirada</option>
        </select>
    </div>

    <!-- FILTRO GÉNERO -->
    <div class="col-md-4">
        <label class="form-label">Género</label>
        <select name="genero" class="form-select">
            <option value="">-- Todos --</option>
            <option value="Masculino" <?= ($_GET['genero'] ?? '') == 'Masculino' ? 'selected' : '' ?>>Masculino</option>
            <option value="Feminino" <?= ($_GET['genero'] ?? '') == 'Feminino' ? 'selected' : '' ?>>Feminino</option>
           
        </select>
    </div>

    <!-- BOTÃO -->
    <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-primary w-100">Filtrar</button>
    </div>

</form>

        <?php if ($result->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php while ($row = $result->fetch_assoc()):
                    $data_validade = new DateTime($row['data_validade']);
                    $hoje = new DateTime();
                    $vencido = $data_validade < $hoje;
                ?>
                   <div class="col">
    <div class="card vaga-card shadow-sm p-3 <?= $vencido ? 'border-danger' : '' ?>" 
         style="<?= $vencido ? 'background-color:#ffe6e6;' : 'background:white;' ?>">

        <h5 class="fw-bold text-primary"><?= htmlspecialchars($row['nome']) ?></h5>

        <p class="text-muted small mb-1"><?= htmlspecialchars($row['descricao']) ?></p>

        <div class="mt-2">
            <p><strong>Idade Máxima:</strong> <?= htmlspecialchars($row['preco']) ?></p>
            <p><strong>Genero:</strong> <?= htmlspecialchars($row['genero_permitido']) ?></p>
            <p><strong>Modalidade:</strong> <?= htmlspecialchars($row['modalidade']) ?></p>
            <p><strong>Posição:</strong> <?= htmlspecialchars($row['posicao']) ?></p>
            <p><strong>Validade:</strong> <?= $data_validade->format('d/m/Y') ?></p>
            <p><strong>Responsável:</strong> <?= htmlspecialchars($row['recrutador_nome']) ?></p>
        </div>

        <div class="card-footer mt-3">
    <a href="editar_vaga.php?id=<?= $row['id'] ?>" 
       class="btn btn-warning btn-modern btn-sm w-100">
        <i class="fa-solid fa-eye"></i> Editar
    </a>
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

        <a href="dashboard_recrutador.php" class="btn btn-secondary mt-3">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Script para confirmar exclusão já está no onclick do link apagar, então pode remover este bloco se desejar
</script>

</body>
</html>