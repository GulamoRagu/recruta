<?php
ob_start();
require 'db.php';
session_start();

// 1. Verificação de Acesso
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$vendedor_id = (int)($_SESSION['user_id'] ?? 0);
$nome_usuario = 'Recrutador';

// Buscar nome do recrutador
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

// 2. Construção Dinâmica da Query SQL com Prepared Statements (Segurança)
$status_filtro = $_GET['status'] ?? '';
$genero_filtro = $_GET['genero'] ?? '';

$sql_base = "
    SELECT 
        p.*, u.username AS recrutador_nome
    FROM produtos p
    JOIN usuarios u ON u.id = p.recrutador_id
    WHERE p.recrutador_id = ?
";

$tipos = 'i'; // Inicialmente apenas para $vendedor_id
$parametros = [$vendedor_id];
$condicoes = [];

// FILTRO POR STATUS
if ($status_filtro === 'activa') {
    $condicoes[] = "p.data_validade >= CURDATE()";
} elseif ($status_filtro === 'expirada') {
    $condicoes[] = "p.data_validade < CURDATE()";
}

// FILTRO POR GÉNERO
if (!empty($genero_filtro)) {
    $condicoes[] = "p.genero_permitido = ?";
    $tipos .= 's';
    $parametros[] = $genero_filtro;
}

// Concatena as condições
if (!empty($condicoes)) {
    $sql_base .= " AND " . implode(" AND ", $condicoes);
}

$sql_base .= " ORDER BY p.id DESC";

// Prepara e Executa a Query
$stmt_vagas = $conn->prepare($sql_base);

// Adiciona os parâmetros e executa
if (!empty($parametros)) {
    $stmt_vagas->bind_param($tipos, ...$parametros);
}
$stmt_vagas->execute();
$result = $stmt_vagas->get_result();

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Minhas Vagas | <?= htmlspecialchars($nome_usuario) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />

    <style>
        :root {
            --primary: #007bff;
            --dark: #1e293b;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --background: #f4f6f9;
        }

        body {
            background-color: var(--background);
            font-family: 'Poppins', sans-serif;
        }

        /* Sidebar Moderna (Coerente com outros painéis) */
        .sidebar {
            background: linear-gradient(180deg, var(--dark), #212529);
            color: white;
            min-width: 250px;
            max-width: 250px;
            padding-top: 20px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            height: 100%;
        }
        .sidebar a {
            color: white;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .sidebar a i { margin-right: 15px; width: 20px; }
        .sidebar a:hover, .sidebar a.active { 
            background-color: rgba(255, 255, 255, 0.1); 
            border-left-color: var(--warning);
        }
        .sidebar h4 { text-align: center; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }

        /* Conteúdo Principal */
        .content { 
            flex-grow: 1;
            margin-left: 250px; 
            padding: 30px;
        }
        .content h2 {
            font-weight: 700;
            color: var(--dark);
        }

        /* Card de Vaga */
        .vaga-card {
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid var(--primary); /* Destaque lateral padrão */
            overflow: hidden;
            height: 100%;
        }
        .vaga-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .vaga-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        .vaga-card .card-body p {
            margin-bottom: 5px;
            font-size: 0.95rem;
        }
        .vaga-card .card-footer {
            background: #fff;
            border-top: none;
        }

        /* Estilos de Status */
        .status-active { 
            border-left-color: var(--success) !important;
            background-color: #e6ffed !important;
        }
        .status-expired { 
            border-left-color: var(--danger) !important;
            background-color: #ffe6e6 !important;
        }
        .status-tag {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }

        /* Responsividade (Mobile) */
        @media (max-width: 992px) {
            .sidebar { display: none !important; }
            .content { margin-left: 0; padding-top: 10px; }
            .navbar-mobile { display: block !important; }
        }
    </style>
</head>
<body>

<nav class="sidebar d-none d-lg-block">
    <h4 class="text-white"><i class="fa-solid fa-building me-2"></i> <?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_recrutador.php"><i class="fa-solid fa-gauge-high"></i> Inicio</a>
    <a href="perfil_recrutador.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
    <a href="vagas_recrutador.php" class="active"><i class="fa-solid fa-briefcase"></i> Gerir Vagas</a>
    <a href="ver_candidaturas.php"><i class="fa-solid fa-list-check"></i> Candidaturas</a>
    <a href="logout.php" class="text-warning mt-auto"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
</nav>

<nav class="navbar navbar-dark bg-dark sticky-top d-lg-none">
    <div class="container-fluid">
        <button class="btn btn-dark" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
        <span class="navbar-brand ms-3">Minhas Vagas</span>
    </div>
</nav>

<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasSidebarLabel"><?= htmlspecialchars($nome_usuario) ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body p-0">
        <a href="dashboard_recrutador.php" class="d-block text-white mb-2"><i class="fa-solid fa-gauge-high"></i> Inicio</a>
        <a href="perfil_recrutador.php" class="d-block text-white mb-2"><i class="fa-solid fa-user"></i> Meu Perfil</a>
        <a href="vagas_recrutador.php" class="d-block text-white mb-2 active"><i class="fa-solid fa-briefcase"></i> Gerir Vagas</a>
        <a href="ver_candidaturas.php" class="d-block text-white mb-2"><i class="fa-solid fa-list-check"></i> Candidaturas</a>
        <a href="logout.php" class="d-block text-warning mt-4"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
    </div>
</div>

<div class="content">
    <h2 class="mb-4"><i class="fa-solid fa-briefcase me-2 text-primary"></i> Gestão de Vagas</h2>

    <form method="GET" class="card shadow-sm p-3 mb-4">
        <div class="row g-3 align-items-end">
            
            <div class="col-md-4 col-lg-3">
                <label class="form-label fw-bold small text-muted">Status da Vaga</label>
                <select name="status" class="form-select">
                    <option value="">Todas</option>
                    <option value="activa" <?= ($status_filtro == 'activa' ? 'selected' : '') ?>>Ativa</option>
                    <option value="expirada" <?= ($status_filtro == 'expirada' ? 'selected' : '') ?>>Expirada</option>
                </select>
            </div>

            <div class="col-md-4 col-lg-3">
                <label class="form-label fw-bold small text-muted">Gênero Alvo</label>
                <select name="genero" class="form-select">
                    <option value="">Todos</option>
                    <option value="Masculino" <?= ($genero_filtro == 'Masculino' ? 'selected' : '') ?>>Masculino</option>
                    <option value="Feminino" <?= ($genero_filtro == 'Feminino' ? 'selected' : '') ?>>Feminino</option>
                </select>
            </div>

            <div class="col-md-4 col-lg-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-filter me-1"></i> Aplicar Filtros
                </button>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="criar_vaga.php" class="btn btn-success w-100 w-lg-auto">
                    <i class="fa-solid fa-plus-circle me-1"></i> Nova Vaga
                </a>
            </div>

        </div>
    </form>

    <?php if ($result->num_rows > 0): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php while ($row = $result->fetch_assoc()):
                $data_validade = new DateTime($row['data_validade']);
                $hoje = new DateTime();
                $vencido = $data_validade < $hoje;
                $status_classe = $vencido ? 'status-expired' : 'status-active';
                $status_texto = $vencido ? 'EXPIRADA' : 'ATIVA';
                $status_badge = $vencido ? 'danger' : 'success';
            ?>
                <div class="col">
                    <div class="card vaga-card shadow-sm <?= $status_classe ?>">
                        <div class="vaga-header d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($row['nome']) ?></h5>
                            <span class="badge bg-<?= $status_badge ?> status-tag"><?= $status_texto ?></span>
                        </div>

                        <div class="card-body">
                            <p class="text-muted small mb-3"><?= htmlspecialchars(substr($row['descricao'], 0, 100)) . (strlen($row['descricao']) > 100 ? '...' : '') ?></p>
                            
                            <ul class="list-unstyled small">
                                <li><i class="fa-solid fa-calendar-alt me-2 text-primary"></i> **Validade:** **<?= $data_validade->format('d/m/Y') ?>**</li>
                                <li><i class="fa-solid fa-male me-2 text-secondary"></i> **Gênero:** <?= htmlspecialchars($row['genero_permitido']) ?></li>
                                <li><i class="fa-solid fa-ruler-vertical me-2 text-secondary"></i> **Modalidade:** <?= htmlspecialchars($row['modalidade']) ?></li>
                                <li><i class="fa-solid fa-crosshairs me-2 text-secondary"></i> **Posição:** <?= htmlspecialchars($row['posicao']) ?></li>
                                <li><i class="fa-solid fa-user-circle me-2 text-secondary"></i> **Responsável:** <?= htmlspecialchars($row['recrutador_nome']) ?></li>
                            </ul>
                        </div>

                        <div class="card-footer d-flex justify-content-end">
                            <a href="editar_vaga.php?id=<?= $row['id'] ?>" 
                               class="btn btn-warning btn-modern btn-sm me-2">
                                <i class="fa-solid fa-pencil-alt me-1"></i> Editar
                            </a>
                            <a href="ver_candidaturas_vaga.php?vaga_id=<?= $row['id'] ?>" 
                               class="btn btn-info btn-modern btn-sm text-white">
                                <i class="fa-solid fa-users me-1"></i> Candidaturas
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center shadow-sm p-4">
            <h4 class="alert-heading"><i class="fa-solid fa-info-circle me-2"></i> Sem Vagas Encontradas</h4>
            <p>Nenhuma vaga corresponde aos filtros aplicados, ou você ainda não cadastrou nenhuma vaga.</p>
            <hr>
            <a href="criar_vaga.php" class="btn btn-success mt-2">
                <i class="fa-solid fa-plus-circle me-1"></i> Publicar sua primeira vaga!
            </a>
        </div>
    <?php endif; ?>

    <a href="dashboard_recrutador.php" class="btn btn-secondary mt-5">
        <i class="fa-solid fa-arrow-left"></i> Voltar ao Painel
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>