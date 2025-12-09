<?php
ob_start();
session_start();
require 'db.php'; // Garante que a conexão com o banco está incluída

// Verifica se o usuário está logado como cliente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// 1. Buscar o nome e gênero do atleta logado (Única vez)
$query = $conn->prepare("SELECT nome_completo, genero FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result_user = $query->get_result(); // Usar result_user para evitar conflito
$user = $result_user->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'Atleta';
$genero_atleta = strtolower($user['genero'] ?? '');

// 2. Buscar IDs das vagas em que o atleta JÁ se candidatou
$candidaturas_ids = [];
$res_cand = $conn->query("SELECT produto_id FROM compras WHERE cliente_id = $user_id");
while($row_c = $res_cand->fetch_assoc()) {
    $candidaturas_ids[] = $row_c['produto_id'];
}

// 3. Configurar e Sanitizar Filtros e Paginação
$vagas_por_pagina = 6;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) {
    $pagina_atual = 1;
}
$offset = ($pagina_atual - 1) * $vagas_por_pagina;

$filtro_idade = $_GET['idade'] ?? ''; 
$filtro_modalidade = $_GET['modalidade'] ?? '';
$filtro_genero = $_GET['genero'] ?? '';
$filtro_data = $_GET['data_validade'] ?? '';

// 4. Construir SQL Base para buscar vagas ativas (não expiradas)
$sql_base = "SELECT id, nome, descricao, preco, genero_permitido, data_validade, modalidade, posicao 
             FROM produtos 
             WHERE data_validade >= CURDATE()"; 

// Adicionar filtros ao SQL de forma segura
$filtro_params = "";
if (!empty($filtro_idade)) {
    // Usamos 'preco' como idade máxima (vagas que aceitam a idade do filtro ou maior)
    $filtro_params .= " AND preco >= " . intval($filtro_idade); 
}
if (!empty($filtro_modalidade)) {
    // Escapa a string para evitar SQL Injection em LIKE
    $filtro_params .= " AND modalidade LIKE '%" . $conn->real_escape_string($filtro_modalidade) . "%'";
}
if (!empty($filtro_genero)) {
    // Filtra pelo gênero permitido
    $filtro_params .= " AND LOWER(genero_permitido) = '" . strtolower($conn->real_escape_string($filtro_genero)) . "'";
}
if (!empty($filtro_data)) {
    // Filtra por data de validade (até a data especificada)
    $filtro_params .= " AND data_validade <= '" . $conn->real_escape_string($filtro_data) . "'";
}

// 5. Consulta Total para Paginação
$sql_total = "SELECT COUNT(id) AS total FROM produtos WHERE data_validade >= CURDATE() " . $filtro_params;
$result_total = $conn->query($sql_total);
$total_vagas = $result_total ? $result_total->fetch_assoc()['total'] : 0;
$total_paginas = ceil($total_vagas / $vagas_por_pagina);

// 6. Consulta para a Página Atual
$sql_final = $sql_base . $filtro_params . " ORDER BY data_validade ASC LIMIT {$vagas_por_pagina} OFFSET {$offset}";
$result = $conn->query($sql_final);

// Montar os parâmetros de filtro para a URL de navegação
$url_filtros = http_build_query([
    'idade' => $filtro_idade, 
    'modalidade' => $filtro_modalidade, 
    'genero' => $filtro_genero, 
    'data_validade' => $filtro_data
]);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Explorar Vagas | <?= htmlspecialchars($nome_usuario) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Estilos da Sidebar para Coerência */
        body {
            font-family: 'Poppins', sans-serif;
            background: #e9ecef; 
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            min-width: 260px;
            max-width: 260px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding-top: 1.5rem;
            box-shadow: 6px 0 15px rgba(0,0,0,0.25);
        }
        .sidebar h4 {
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 700;
        }
        .sidebar a {
            color: white;
            display: flex;
            align-items: center;
            padding: 15px 25px;
            text-decoration: none;
            font-weight: 500;
            border-radius: 10px;
            margin: 8px 15px;
            transition: all 0.3s ease;
            border-left: 5px solid transparent;
        }
        .sidebar a i {
            margin-right: 15px;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.1);
            border-left: 5px solid #ffc107;
        }

        /* Conteúdo Principal */
        .main-content {
            flex-grow: 1;
            padding: 30px;
            margin-left: 0 !important; /* Reset para telas pequenas */
        }
        @media (min-width: 992px) {
            .main-content {
                margin-left: 260px !important; /* Espaço para a sidebar */
            }
        }
        
        /* Estilo Moderno dos Cards de Vagas */
        .vaga-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        .vaga-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .vaga-card.candidatado {
            background-color: #f8f9fa !important;
            border: 2px solid #6c757d;
            opacity: 0.8;
        }
        .vaga-card .card-title {
            font-weight: 700;
            color: #007bff;
        }
        .vaga-card .badge-info {
            background-color: #17a2b8;
        }
        .vaga-card .badge-success {
            background-color: #28a745;
        }

        /* Navbar e Offcanvas (Mobile) */
        .navbar-brand {
            font-weight: 600;
        }
        .offcanvas-body.sidebar a {
            margin: 5px 0;
            border-radius: 5px;
        }
        .offcanvas-body.sidebar a:hover {
            background: #495057;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark fixed-top d-lg-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <span class="navbar-brand">Vagas</span>
    </div>
</nav>

<div class="offcanvas offcanvas-start bg-dark d-lg-none" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header bg-dark text-white">
        <h5 class="offcanvas-title" id="offcanvasSidebarLabel"><i class="fa-solid fa-medal me-2"></i> <?= htmlspecialchars($nome_usuario) ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body sidebar">
        <a href="dashboard_atleta.php"><i class="fa-solid fa-gauge-high"></i> Início</a>
        <a href="perfil_atleta.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
        <a href="ver_vagas.php" class="active"><i class="fa-solid fa-magnifying-glass"></i> Explorar Vagas</a>
        <a href="minhas_candidaturas.php"><i class="fa-solid fa-clipboard-list"></i> Minhas Candidaturas</a>
        <a href="logout.php" class="text-warning" style="margin-top: auto; padding-bottom: 20px;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
    </div>
</div>

<div class="d-none d-lg-block sidebar text-white fixed-top">
    <h4><i class="fa-solid fa-medal me-2"></i> <?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_atleta.php"><i class="fa-solid fa-gauge-high"></i> Início</a>
    <a href="perfil_atleta.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
    <a href="ver_vagas.php" class="active"><i class="fa-solid fa-magnifying-glass"></i> Explorar Vagas</a>
    <a href="minhas_candidaturas.php"><i class="fa-solid fa-clipboard-list"></i> Minhas Candidaturas</a>
    <a href="logout.php" class="text-warning" style="margin-top: auto; padding-bottom: 20px;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
</div>

<div class="main-content">
    <h1 class="mb-5 border-bottom pb-2 text-primary"><i class="fa-solid fa-magnifying-glass me-2"></i> Explorar Vagas</h1>

    <div class="card p-4 mb-5 shadow-sm">
        <h4 class="mb-3 text-secondary"><i class="fa-solid fa-filter me-2"></i> Filtros de Busca</h4>
        <form method="GET" class="row g-3">
            <input type="hidden" name="pagina" value="1"> <div class="col-md-3">
                <label for="modalidade" class="form-label">Modalidade</label>
                <input type="text" name="modalidade" id="modalidade" class="form-control" placeholder="Futebol, Basquete, etc." value="<?= htmlspecialchars($filtro_modalidade) ?>">
            </div>
            <div class="col-md-3">
                <label for="genero" class="form-label">Género</label>
                <select name="genero" id="genero" class="form-select">
                    <option value="">Todos</option>
                    <option value="Masculino" <?= strtolower($filtro_genero)=='masculino'?'selected':'' ?>>Masculino</option>
                    <option value="Feminino" <?= strtolower($filtro_genero)=='feminino'?'selected':'' ?>>Feminino</option>
                    <option value="Ambos" <?= strtolower($filtro_genero)=='ambos'?'selected':'' ?>>Ambos</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="idade" class="form-label">Idade Máxima (Até)</label>
                <input type="number" name="idade" id="idade" class="form-control" placeholder="Ex: 25" value="<?= htmlspecialchars($filtro_idade) ?>">
            </div>
            <div class="col-md-3">
                <label for="data_validade" class="form-label">Validade Até</label>
                <input type="date" name="data_validade" id="data_validade" class="form-control" value="<?= htmlspecialchars($filtro_data) ?>">
            </div>
            <div class="col-12 text-end">
                <a href="ver_vagas.php" class="btn btn-outline-secondary me-2">Limpar Filtros</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Buscar Vagas</button>
            </div>
        </form>
    </div>
    <?php if ($total_vagas > 0): ?>
        <h3 class="mb-4 text-secondary">
            Resultados: Mostrando **<?= $result->num_rows ?>** de **<?= $total_vagas ?>** vagas ativas 
            <?php if ($total_paginas > 1): ?>
                (Página **<?= $pagina_atual ?>** de **<?= $total_paginas ?>**)
            <?php endif; ?>
        </h3>
    <?php endif; ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $ja_candidatou = in_array($row['id'], $candidaturas_ids);
            ?>
                <div class="col">
                    <div class="card vaga-card h-100 <?= $ja_candidatou ? 'candidatado' : '' ?>">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($row['nome']) ?></h5>
                                <?php if ($ja_candidatou): ?>
                                    <span class="badge bg-secondary"><i class="fa-solid fa-check"></i> Candidatado</span>
                                <?php else: ?>
                                    <span class="badge bg-info"><i class="fa-solid fa-clock"></i> Expira em: <?= (new DateTime($row['data_validade']))->format('d/m/Y') ?></span>
                                <?php endif; ?>
                            </div>

                            <p class="card-text text-muted small"><?= nl2br(htmlspecialchars(substr($row['descricao'], 0, 100)) . (strlen($row['descricao']) > 100 ? '...' : '')) ?></p>
                            
                            <hr>
                            
                            <div class="row small g-2 mb-3">
                                <div class="col-6"><strong><i class="fa-solid fa-person-half-dress me-1"></i> Gênero:</strong> <?= htmlspecialchars($row['genero_permitido']) ?></div>
                                <div class="col-6"><strong><i class="fa-solid fa-calendar-alt me-1"></i> Idade Máx:</strong> <?= htmlspecialchars($row['preco']) ?> anos</div>
                                <div class="col-6"><strong><i class="fa-solid fa-running me-1"></i> Modalidade:</strong> <?= htmlspecialchars($row['modalidade']) ?></div>
                                <div class="col-6"><strong><i class="fa-solid fa-crosshairs me-1"></i> Posição:</strong> <?= htmlspecialchars($row['posicao']) ?></div>
                            </div>
                            
                            <div class="mt-auto text-center pt-3">
                                <?php if(!$ja_candidatou): ?>
                                    <a href="candidatar_se.php?id=<?= $row['id'] ?>" class="btn btn-success w-100">
                                        <i class="fa-solid fa-paper-plane"></i> Candidatar-se Agora
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fa-solid fa-check-circle"></i> Candidatura Registrada
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Não encontramos vagas ativas que correspondam aos seus filtros.
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($total_paginas > 1): ?>
        <div class="d-flex justify-content-center mt-5">
            <?php
            // Se estiver na primeira página, o botão "Anterior" fica desabilitado
            $prev_page_link = $pagina_atual > 1 ? "ver_vagas.php?pagina=" . ($pagina_atual - 1) . "&" . $url_filtros : "#";
            $prev_disabled = $pagina_atual <= 1 ? "disabled" : "";

            // Se estiver na última página, o botão "Próximo" fica desabilitado
            $next_page_link = $pagina_atual < $total_paginas ? "ver_vagas.php?pagina=" . ($pagina_atual + 1) . "&" . $url_filtros : "#";
            $next_disabled = $pagina_atual >= $total_paginas ? "disabled" : "";
            ?>

            <a href="<?= $prev_page_link ?>" class="btn btn-outline-primary me-3 <?= $prev_disabled ?>">
                <i class="fa-solid fa-chevron-left"></i> Anterior
            </a>

            <button class="btn btn-secondary" disabled>
                Página <?= $pagina_atual ?>
            </button>

            <a href="<?= $next_page_link ?>" class="btn btn-primary ms-3 <?= $next_disabled ?>">
                Próximo <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
    <?php endif; ?>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>