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
$status = $_GET['status'] ?? "";
$genero_permitido = $_GET['genero_permitido'] ?? "";
$ordem = ($_GET['ordem'] ?? "desc") == "asc" ? "ASC" : "DESC";

$sql = "
    SELECT 
        p.*, 
        u.username AS recrutador_nome
    FROM produtos p
    JOIN usuarios u 
        ON u.id = p.recrutador_id
    WHERE u.criado_por = $admin_id
";

// FILTRAR POR STATUS
if ($status === "ativa") {
    $sql .= " AND p.data_validade >= CURDATE() ";
} elseif ($status === "expirada") {
    $sql .= " AND p.data_validade < CURDATE() ";
}

// FILTRAR POR GENERO
if (!empty($genero_permitido)) {
    // Proteção contra SQL injection
    $genero_permitido_esc = $conn->real_escape_string($genero_permitido);
    $sql .= " AND p.genero_permitido = '$genero_permitido_esc' ";
}

// ORDEM (RECENTES / ANTIGOS)
$sql .= " ORDER BY p.id $ordem ";

$result = $conn->query($sql);


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
    background-color: #e9eef5;
    font-family: "Segoe UI", sans-serif;
}

/* Sidebar */
@media (min-width: 768px) {
    .sidebar {
        width: 180px;          /* mais fino que 250px */
        height: 100vh;
        position: fixed;
        background-color: #1f2937;
        padding-top: 20px;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }
    .content {
        margin-left: 190px;     /* ajusta para sidebar mais fina */
        padding: 25px;
    }
}

@media (max-width: 767.98px) {
    .sidebar {
        width: 100%;
        position: relative;
        height: auto;
    }
    .content {
        margin-left: 0;
        padding: 20px 10px;
    }
}

.sidebar a {
    color: #ced4da;
    padding: 12px 15px;       /* menos padding para ficar mais fino */
    display: block;
    text-decoration: none;
    font-size: 15px;          /* um pouco menor */
    font-weight: 500;
    transition: 0.3s;
}

.sidebar a:hover {
    background-color: #374151;
    color: #fff;
    padding-left: 20px;       /* leve efeito de hover */
}

/* Card container */
.card {
    width: 100% !important;
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.card h2 {
    font-weight: 700;
    color: #1f2937;
}

/* Table */
.table {
    background-color: #fff;
    border-radius: 8px !important;
    overflow: hidden;
    width: 100%;
}

thead.table-dark {
    background-color: #1f2937 !important;
}

thead th {
    font-size: 14px; /* ligeiramente menor para combinar com sidebar */
    letter-spacing: .5px;
}

tbody tr {
    transition: .2s ease;
}

tbody tr:hover {
    background-color: #f1f5f9 !important;
    transform: scale(1.002);
}

.btn-sm i {
    font-size: 13px;
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
    <a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> Inicio</a>
  
    <a href="../login.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
</div>

<!-- Conteúdo -->
<div class="content">
 <div class="card shadow p-4">
    <h2 class="text-center mb-4">
        <i class="fa-solid fa-box"></i> Minhas Vagas
    </h2>

    <div class="d-flex justify-content-between mb-3">
        <a href="cadastrar_vaga.php" class="btn btn-success">
            <i class="fa-solid fa-plus"></i> Nova Vaga
        </a>
        
    </div>
    <form method="GET" class="row g-3 mb-4">

    <!-- Filtro por status -->
    <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="">Todas</option>
            <option value="ativa" <?= isset($_GET['status']) && $_GET['status']=="ativa" ? "selected" : "" ?>>Ativa</option>
            <option value="expirada" <?= isset($_GET['status']) && $_GET['status']=="expirada" ? "selected" : "" ?>>Expirada</option>
        </select>
    </div>

    <!-- Filtro por genero -->
  <div class="col-md-3">
    <label class="form-label">Gênero</label>
    <select name="genero_permitido" class="form-select">
        <option value="" <?= (!isset($_GET['genero_permitido']) || $_GET['genero_permitido']=="") ? "Ambos" : "" ?>>Ambos</option>
        
        <option value="Masculino" <?= (isset($_GET['genero_permitido']) && $_GET['genero_permitido']=="Masculino") ? "selected" : "" ?>>Masculino</option>
        <option value="Feminino" <?= (isset($_GET['genero_permitido']) && $_GET['genero_permitido']=="Feminino") ? "selected" : "" ?>>Feminino</option>
        
    </select>
</div>



    <!-- Ordenação -->
    <div class="col-md-3">
        <label class="form-label">Ordenar por</label>
        <select name="ordem" class="form-select">
            <option value="desc" <?= (!isset($_GET['ordem']) || $_GET['ordem']=="desc") ? "selected" : "" ?>>Mais Recentes</option>
            <option value="asc" <?= (isset($_GET['ordem']) && $_GET['ordem']=="asc") ? "selected" : "" ?>>Mais Antigos</option>
        </select>
    </div>

    <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary w-100">
            <i class="fa-solid fa-filter"></i> Filtrar
        </button>
    </div>

</form>


    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
    <tr>
        <th>ID</th>
        <th>Nome da Vaga</th>
        <th>Descrição</th>
        <th>Idade Máxima</th>
        <th>Genero Permitido</th>
        <th>Modalidade</th>
        <th>Posição</th>
        <th>Validade</th>
        <th>Status</th>
        <th>Recrutador</th>
        <th class="text-center">Ações</th>
    </tr>
</thead>


                <tbody>
                <?php while ($row = $result->fetch_assoc()):
                    $data_validade = new DateTime($row['data_validade']);
                    $hoje = new DateTime();
                    $vencido = $data_validade < $hoje;
                ?>
                  <tr class="<?= $vencido ? 'table-danger' : '' ?>">
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['nome']) ?></td>
    <td><?= htmlspecialchars($row['descricao']) ?></td>
    <td><?= htmlspecialchars($row['preco']) ?></td>
    <td><?= htmlspecialchars($row['genero_permitido']) ?></td>
    <td><?= htmlspecialchars($row['modalidade']) ?></td>
    <td><?= htmlspecialchars($row['posicao']) ?></td>
    <td><?= $data_validade->format('d/m/Y') ?></td>

    <td>
        <?php if ($vencido): ?>
            <span class="badge bg-danger px-3 py-2">Expirada</span>
        <?php else: ?>
            <span class="badge bg-success px-3 py-2">Ativa</span>
        <?php endif; ?>
    </td>

    <td><?= htmlspecialchars($row['recrutador_nome']) ?></td>

    <td class="text-center">
        <a href="editar_vaga.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm me-1">
            <i class="fa-solid fa-pen"></i>
        </a>

        <a href="apagar_vaga.php?id=<?= $row['id'] ?>"
           onclick="return confirm('Tem certeza que deseja apagar?');"
           class="btn btn-danger btn-sm">
            <i class="fa-solid fa-trash"></i>
        </a>
    </td>
</tr>

                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="fa-solid fa-info-circle"></i> Nenhuma vaga cadastrada ainda.
        </div>
    <?php endif; ?>

    <a href="../login.php" class="btn btn-secondary mt-3">
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