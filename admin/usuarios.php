<?php
ob_start();
session_start();
require '../db.php';

// ===============================
// 1. CAPTURA DE FILTROS
// ===============================
$filtro_genero       = $_GET['genero'] ?? '';
$filtro_submissao    = $_GET['submissao'] ?? '';
$filtro_faixa_etaria = $_GET['faixa_etaria'] ?? '';
$filtro_modalidade   = $_GET['modalidade'] ?? '';

// ===============================
// 2. DETEÇÃO DE COLUNAS (idade / preco / modalidade)
// ===============================
function coluna_existe($conn, $tabela, $coluna) {
    $t = $conn->real_escape_string($tabela);
    $c = $conn->real_escape_string($coluna);
    $q = $conn->query("SELECT COUNT(*) AS cnt 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = '$t' 
        AND COLUMN_NAME = '$c'");
    if (!$q) return false;
    return (int)$q->fetch_assoc()['cnt'] > 0;
}

$idade_field = null;
if (coluna_existe($conn, 'usuarios', 'idade')) {
    $idade_field = 'idade';
} elseif (coluna_existe($conn, 'usuarios', 'preco')) {
    $idade_field = 'preco';
}

// tabela usuarios TEM a coluna modalidade ✔
$usuarios_tem_modalidade = true;

// ===============================
// 3. CONTAGENS GERAIS
// ===============================
$total_atletas = $conn->query("SELECT COUNT(*) AS total 
    FROM usuarios WHERE tipo='cliente'")
    ->fetch_assoc()['total'];

$total_masculinos = $conn->query("
    SELECT COUNT(*) AS total 
    FROM usuarios 
    WHERE tipo='cliente' 
    AND LOWER(COALESCE(genero,'')) LIKE 'm%'")
    ->fetch_assoc()['total'];

$total_femininos = $conn->query("
    SELECT COUNT(*) AS total 
    FROM usuarios 
    WHERE tipo='cliente' 
    AND LOWER(COALESCE(genero,'')) LIKE 'f%'")
    ->fetch_assoc()['total'];

// modalidades existentes no sistema
$modalidades_opcoes = [];
$modal_res = $conn->query("
    SELECT DISTINCT LOWER(COALESCE(modalidade,'')) AS modalidade
    FROM usuarios
    WHERE modalidade IS NOT NULL AND modalidade <> ''
    ORDER BY modalidade ASC
");
if ($modal_res) {
    while($row = $modal_res->fetch_assoc()) {
        $modalidades_opcoes[] = ucfirst($row['modalidade']);
    }
}
$total_modalidades = count($modalidades_opcoes);

// ===============================
// 4. FAIXAS ETÁRIAS
// ===============================
$faixa_10_15  = 'N/D';
$faixa_16_20  = 'N/D';
$faixa_21_25  = 'N/D';
$faixa_26_more= 'N/D';

if ($idade_field) {
    $idf = $conn->real_escape_string($idade_field);
    $q = $conn->query("
        SELECT
            SUM(CASE WHEN $idf BETWEEN 10 AND 15 THEN 1 ELSE 0 END) AS f1,
            SUM(CASE WHEN $idf BETWEEN 16 AND 20 THEN 1 ELSE 0 END) AS f2,
            SUM(CASE WHEN $idf BETWEEN 21 AND 25 THEN 1 ELSE 0 END) AS f3,
            SUM(CASE WHEN $idf >= 26 THEN 1 ELSE 0 END) AS f4
        FROM usuarios
        WHERE tipo='cliente' AND $idf IS NOT NULL AND $idf <> ''
    ");
    if ($q) {
        $r = $q->fetch_assoc();
        $faixa_10_15  = (int)$r['f1'];
        $faixa_16_20  = (int)$r['f2'];
        $faixa_21_25  = (int)$r['f3'];
        $faixa_26_more= (int)$r['f4'];
    }
}

// ===============================
// 5. ATLETAS POR MODALIDADE  ✔ CORRIGIDO
// ===============================
$atletas_por_modalidade = [];

$q = $conn->query("
    SELECT 
        LOWER(COALESCE(modalidade,'não definida')) AS modalidade,
        COUNT(*) AS total
    FROM usuarios
    WHERE tipo='cliente'
    GROUP BY LOWER(COALESCE(modalidade,'não definida'))
    ORDER BY total DESC
");

if ($q) {
    while($row=$q->fetch_assoc()) {
        $atletas_por_modalidade[] = [
            "modalidade" => ucfirst($row['modalidade']),
            "total"      => $row['total']
        ];
    }
}

// ===============================
// 6. ATLETAS QUE FIZERAM SUBMISSÕES
// ===============================
$atletas_com_submissao = [];
$q = $conn->query("
    SELECT DISTINCT u.id, u.nome_completo, u.idade, 
           u.email, u.telefone, u.posicao
    FROM usuarios u
    INNER JOIN compras c ON c.cliente_id = u.id
    WHERE u.tipo='cliente'
    ORDER BY u.nome_completo ASC
");
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $atletas_com_submissao[] = $r;
    }
}

// ===============================
// 7. QUERY PRINCIPAL — TABELA
// ===============================
$where = ["u.tipo='cliente'"];

// genero
if (!empty($filtro_genero)) {
    $g = strtolower($conn->real_escape_string($filtro_genero));
    if ($g == 'm') {
        $where[] = "LOWER(u.genero) LIKE 'm%'";
    } elseif ($g == 'f') {
        $where[] = "LOWER(u.genero) LIKE 'f%'";
    }
}

// submissao
if ($filtro_submissao === 'sim') {
    $where[] = "(SELECT COUNT(*) FROM compras c2 WHERE c2.cliente_id=u.id) > 0";
} elseif ($filtro_submissao === 'nao') {
    $where[] = "(SELECT COUNT(*) FROM compras c2 WHERE c2.cliente_id=u.id) = 0";
}

// faixa etária
if ($idade_field && !empty($filtro_faixa_etaria)) {
    $idf = $conn->real_escape_string($idade_field);
    switch ($filtro_faixa_etaria) {
        case '10-15': $where[] = "u.$idf BETWEEN 10 AND 15"; break;
        case '16-20': $where[] = "u.$idf BETWEEN 16 AND 20"; break;
        case '21-25': $where[] = "u.$idf BETWEEN 21 AND 25"; break;
        case '26+':   $where[] = "u.$idf >= 26"; break;
    }
}

// modalidade (a tabela usuarios TEM essa coluna)
if (!empty($filtro_modalidade)) {
    $mod = strtolower($conn->real_escape_string($filtro_modalidade));
    $where[] = "LOWER(u.modalidade) = '$mod'";
}

$where_sql = "WHERE " . implode(" AND ", $where);

// ===============================
// QUERY FINAL — CORRIGIDA ✔

$sql = "
    SELECT 
        u.*, 
        GROUP_CONCAT(DISTINCT p.nome ORDER BY p.nome SEPARATOR ', ') AS vagas_candidatadas
    FROM usuarios u
    LEFT JOIN compras c ON c.cliente_id = u.id
    LEFT JOIN produtos p ON p.id = c.produto_id
    $where_sql
    GROUP BY u.id
    ORDER BY u.criado_em DESC
";

$atletas = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Atletas - Relatórios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet" />
    <style>
        :root {
            /* Cores Elegantes */
            --primary-dark: #123456; /* Azul Marinho Escuro */
            --primary-light: #5A7EA8; /* Azul Suave */
            --accent: #E8B949; /* Dourado/Âmbar (Para Destaque e KPIs) */
            --bg-light: #F8F9FA;
            --text-dark: #343A40;
            --shadow-subtle: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        body { 
            background: var(--bg-light); 
            font-family: 'Poppins', sans-serif; 
            color: var(--text-dark);
        }

        /* Navbar Aprimorada */
        .navbar-dark {
            background-color: var(--primary-dark) !important;
        }
        .navbar-brand {
            font-weight: 700;
            color: var(--accent) !important;
        }

        /* Título Principal */
        .page-title {
            color: var(--primary-dark);
            font-weight: 800;
        }

        /* Cartões KPI (Melhorados e Reduzidos) */
        .card-kpi { 
            border-radius: 8px; /* Ligeiramente menor */
            box-shadow: var(--shadow-subtle); 
            padding: 10px; /* Redução de padding */
            border: none;
            transition: transform 0.2s;
            height: 100%; /* Garante que todos tenham a mesma altura */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .card-kpi:hover {
            transform: translateY(-2px); /* Efeito de hover mais sutil */
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.08);
        }
        .kpi-icon {
            font-size: 1.5rem; /* Icone menor */
            color: var(--accent);
            margin-bottom: 5px;
        }
        .kpi-title { 
            font-size: 0.75rem; /* Título menor */
            color: #6c757d; 
            font-weight: 600;
            line-height: 1.2;
        }
        .kpi-value { 
            font-size: 1.5rem; /* Valor menor */
            font-weight: 800;
            color: var(--primary-dark);
            line-height: 1.2;
        }

        /* Cartões de Conteúdo e Filtros */
        .card {
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        .card-header-custom {
            background-color: var(--primary-light);
            color: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            padding: 10px 15px; /* Redução de padding */
            font-weight: 600;
        }
        
        /* Lista de Modalidades/Submissões */
        .modalidade-list { 
            max-height: 200px; /* Reduz a altura máxima */
            overflow-y:auto; 
            font-size: 0.85rem; /* Reduz a fonte */
        }
        .list-group-item { 
            border-color: rgba(0, 0, 0, 0.08); 
            padding: 8px 15px; /* Reduz o padding da lista */
        }

        /* Filtros */
        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
        }
        .form-select, .form-control {
            font-size: 0.9rem; /* Reduz a fonte dos inputs */
        }

        /* Tabela */
        .table-responsive {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
        }
        .table thead th {
            background-color: #E9ECEF; 
            color: var(--text-dark);
            font-weight: 600;
            border-bottom: 2px solid var(--primary-light);
            vertical-align: middle;
            font-size: 0.8rem; /* Título da tabela menor */
            padding: 8px; /* Reduz padding do cabeçalho */
        }
        .table-hover > tbody > tr:hover > td, 
        .table-hover > tbody > tr:hover > th { 
            background: #e6f0f7;
        }
        .table-striped > tbody > tr:nth-of-type(odd) > * {
            --bs-table-bg-type: #f6f7f8; /* Listras mais claras */
        }
        /* Classe customizada para tabela principal */
        .table.small td, .table.small th {
            padding: 0.4rem; /* Diminui o padding das células */
            font-size: 0.75rem; /* Fonte da célula menor */
        }
        .badge.bg-info {
            background-color: var(--accent) !important;
            color: var(--primary-dark);
            font-weight: 700;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fa-solid fa-gauge-high me-2"></i> ADMIN PANEL</a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2"><i class="fa-solid fa-arrow-left me-1"></i> Dashboard</a>
            <a href="../login.php" class="btn btn-danger btn-sm"><i class="fa-solid fa-right-from-bracket me-1"></i> Sair</a>
        </div>
    </div>
</nav>

<div class="container-fluid my-3">
    <h1 class="mb-3 text-center page-title fs-3"><i class="fa-solid fa-users-viewfinder me-2 text-danger"></i> Relatórios - Atletas</h1>

    <div class="row g-2 mb-3">
        
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card card-kpi text-center">
                <i class="fa-solid fa-users kpi-icon"></i>
                <div class="kpi-title">Total Atletas</div>
                <div class="kpi-value"><?= (int)$total_atletas ?></div>
            </div>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
            <div class="card card-kpi text-center">
                <i class="fa-solid fa-person kpi-icon"></i>
                <div class="kpi-title">Masculinos</div>
                <div class="kpi-value"><?= (int)$total_masculinos ?></div>
            </div>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
            <div class="card card-kpi text-center">
                <i class="fa-solid fa-person-dress kpi-icon"></i>
                <div class="kpi-title">Femininos</div>
                <div class="kpi-value"><?= (int)$total_femininos ?></div>
            </div>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
            <div class="card card-kpi text-center">
                <i class="fa-solid fa-trophy kpi-icon"></i>
                <div class="kpi-title">Modalidades</div>
                <div class="kpi-value"><?= (int)$total_modalidades ?></div>
            </div>
        </div>
    
        <div class="col-6 col-md-3 col-lg-1">
            <div class="card card-kpi text-center">
                <i class="fa-solid fa-child kpi-icon" style="color:#5A7EA8;"></i>
                <div class="kpi-title">10–15</div>
                <div class="kpi-value"><?= is_numeric($faixa_10_15) ? $faixa_10_15 : 'N/D' ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-1">
            <div class="card card-kpi text-center">
                <i class="fa-solid fa-user-graduate kpi-icon" style="color:#5A7EA8;"></i>
                <div class="kpi-title">16–20</div>
                <div class="kpi-value"><?= is_numeric($faixa_16_20) ? $faixa_16_20 : 'N/D' ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-1">
            <div class="card card-kpi text-center">
                <i class="fa-solid fa-briefcase kpi-icon" style="color:#5A7EA8;"></i>
                <div class="kpi-title">21–25</div>
                <div class="kpi-value"><?= is_numeric($faixa_21_25) ? $faixa_21_25 : 'N/D' ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-1">
            <div class="card card-kpi text-center">
                <i class="fa-solid fa-user-tie kpi-icon" style="color:#5A7EA8;"></i>
                <div class="kpi-title">26+</div>
                <div class="kpi-value"><?= is_numeric($faixa_26_more) ? $faixa_26_more : 'N/D' ?></div>
            </div>
        </div>
    </div>

    <div class="row mb-3 g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header card-header-custom p-2">
                    <i class="fa-solid fa-ranking-star me-2"></i> Atletas por Modalidade
                </div>
                <div class="card-body p-0">
                    <div class="modalidade-list">
                        <table class="table table-sm mb-0 small">
                            <thead class="table-light">
                                <tr><th>Modalidade</th><th class="text-end">Atletas</th></tr>
                            </thead>
                            <tbody>
                                <?php if (count($atletas_por_modalidade) === 0): ?>
                                    <tr><td colspan="2" class="text-muted text-center p-3">Nenhuma modalidade encontrada.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($atletas_por_modalidade as $m): ?>
                                        <tr>
                                            <td><i class="fa-solid fa-baseball-bat-ball me-1 text-muted"></i><?= htmlspecialchars($m['modalidade']) ?></td>
                                            <td class="text-end fw-bold text-primary-dark"><?= (int)$m['total'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header card-header-custom p-2">
                    <i class="fa-solid fa-paper-plane me-2"></i> Atletas c/ Submissões (<span class="badge bg-light text-primary-dark fw-bold"><?= count($atletas_com_submissao) ?></span>)
                </div>
                <div class="card-body p-0">
                    <div style="max-height:200px; overflow-y:auto;">
                        <ul class="list-group list-group-flush">
                            <?php if (count($atletas_com_submissao) === 0): ?>
                                <li class="list-group-item text-muted text-center p-3">Nenhum atleta com submissões encontrado.</li>
                            <?php else: ?>
                                <?php foreach ($atletas_com_submissao as $a): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center small">
                                        <div>
                                            <strong><i class="fa-solid fa-user-check me-1 text-success"></i><?= htmlspecialchars($a['nome_completo'] ?? '')?></strong><br>
                                            <small class="text-muted" style="font-size: 0.7rem;"><i class="fa-solid fa-envelope me-1"></i><?= htmlspecialchars($a['email']) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <a href="perfil_atleta_admin.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-dark py-0 px-1" title="Ver Detalhes">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow-sm mb-3">
        <div class="card-body p-3">
            <h5 class="card-title text-primary-dark fw-bold mb-2 fs-6"><i class="fa-solid fa-sliders me-1"></i> Opções de Filtragem</h5>
            <form method="GET" class="row g-2 align-items-end">
                
                <div class="col-md-3 col-lg-2">
                    <label for="genero" class="form-label mb-1">Gênero</label>
                    <select class="form-select form-select-sm" id="genero" name="genero">
                        <option value="">Todos</option>
                        <option value="M" <?= $filtro_genero === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= $filtro_genero === 'F' ? 'selected' : '' ?>>Feminino</option>
                    </select>
                </div>

                <div class="col-md-3 col-lg-2">
                    <label for="submissao" class="form-label mb-1">Candidatura</label>
                    <select class="form-select form-select-sm" id="submissao" name="submissao">
                        <option value="">Todos</option>
                        <option value="sim" <?= $filtro_submissao === 'sim' ? 'selected' : '' ?>>Sim</option>
                        <option value="nao" <?= $filtro_submissao === 'nao' ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>

                <div class="col-md-3 col-lg-3">
                    <label for="faixa_etaria" class="form-label mb-1">Faixa Etária</label>
                    <select class="form-select form-select-sm" id="faixa_etaria" name="faixa_etaria" <?= $idade_field ? '' : 'disabled' ?>>
                        <option value="">Todas</option>
                        <option value="10-15" <?= $filtro_faixa_etaria === '10-15' ? 'selected' : '' ?>>10 a 15 anos</option>
                        <option value="16-20" <?= $filtro_faixa_etaria === '16-20' ? 'selected' : '' ?>>16 a 20 anos</option>
                        <option value="21-25" <?= $filtro_faixa_etaria === '21-25' ? 'selected' : '' ?>>21 a 25 anos</option>
                        <option value="26+" <?= $filtro_faixa_etaria === '26+' ? 'selected' : '' ?>>26 anos ou mais</option>
                    </select>
                    <?php if (!$idade_field): ?>
                        <small class="text-danger" style="font-size: 0.7rem;">Campo 'idade' ou 'preco' ausente.</small>
                    <?php endif; ?>
                </div>

                <div class="col-md-3 col-lg-3">
                    <label for="modalidade" class="form-label mb-1">Modalidade</label>
                    <select class="form-select form-select-sm" id="modalidade" name="modalidade">
                        <option value="">Todas</option>
                        <?php foreach ($modalidades_opcoes as $modalidade): ?>
                            <option value="<?= $modalidade ?>" <?= $filtro_modalidade === $modalidade ? 'selected' : '' ?>><?= $modalidade ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-12 col-lg-2 d-flex justify-content-end pt-2">
                    <button type="submit" class="btn btn-dark btn-sm me-2" style="background-color: var(--primary-dark);"><i class="fa-solid fa-magnifying-glass"></i> Aplicar</button>
                    <a href="?genero=&submissao=&faixa_etaria=&modalidade=" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-xmark"></i> Limpar</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card shadow">
        <div class="card-header card-header-custom p-2">
            <h3 class="mb-0 fs-5"><i class="fa-solid fa-list-ul me-2"></i> Tabela de Atletas (<?= $atletas ? $atletas->num_rows : '0' ?>)</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tableAtletas" class="table table-hover table-striped mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th><i class="fa-solid fa-user"></i> Nome Completo</th>
                            <th><i class="fa-solid fa-cake-candles"></i> Idade</th>
                            <th><i class="fa-solid fa-at"></i> Email</th>
                            <th><i class="fa-solid fa-phone"></i> Telefone</th>
                            <th><i class="fa-solid fa-crosshairs"></i> Posição</th>
                            <th><i class="fa-solid fa-shield-halved"></i> Modalidade</th>
                            <th><i class="fa-solid fa-check"></i> Candidaturas</th>
                            <th><i class="fa-solid fa-screwdriver-wrench"></i> Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($atletas && $atletas->num_rows > 0): ?>
                            <?php while($row = $atletas->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['nome_completo']) ?></td>
                                <td><?= htmlspecialchars($row['idade'] ?? ($row['preco'] ?? 'N/D')) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['telefone'] ?? '') ?></td>

                                <td><?= htmlspecialchars($row['posicao'] ?? 'N/D') ?></td>
                                <td><?= htmlspecialchars($usuarios_tem_modalidade ? ($row['modalidade'] ?? 'N/D') : 'Via Produto') ?></td>
                                <td>
                                    <span class="badge <?= $row['vagas_candidatadas'] ? 'bg-info' : 'bg-light text-muted' ?>">
                                        <?= $row['vagas_candidatadas'] ? 'Sim' : 'Não' ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <a href="../perfil_atleta.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-1 me-1" title="Ver Detalhes">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="deletar_atleta.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger py-0 px-1" title="Apagar Atleta" onclick="return confirm('Tem certeza que deseja apagar este atleta e todos os seus dados?');">
                                        <i class="fa-solid fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center p-3 text-muted">Nenhum atleta encontrado com os filtros aplicados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <hr class="my-3">

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button id="btnExportPDF" class="btn btn-danger btn-sm shadow-sm">
            <i class="fa-solid fa-file-pdf me-1"></i> Exportar (PDF)
        </button>
        <button id="btnExportExcel" class="btn btn-success btn-sm shadow-sm">
            <i class="fa-solid fa-file-excel me-1"></i> Exportar (Excel)
        </button>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
// Função para capturar texto dos filtros (Atualizada)
function getFiltrosText() {
    const genero = document.getElementById('genero').options[document.getElementById('genero').selectedIndex].text;
    const submissao = document.getElementById('submissao').options[document.getElementById('submissao').selectedIndex].text;
    const faixaEtariaSelect = document.getElementById('faixa_etaria');
    const faixaEtaria = faixaEtariaSelect.options[faixaEtariaSelect.selectedIndex].text;
    const modalidadeSelect = document.getElementById('modalidade');
    const modalidade = modalidadeSelect.options[modalidadeSelect.selectedIndex].text;
    
    let filtros = [];
    if (genero !== 'Todos') filtros.push(`Gênero: ${genero}`);
    if (submissao !== 'Todos') filtros.push(`Candidatura: ${submissao}`);
    if (faixaEtaria !== 'Todas') filtros.push(`Faixa Etária: ${faixaEtaria}`);
    if (modalidade !== 'Todas') filtros.push(`Modalidade: ${modalidade}`);

    
    return filtros.length > 0 ? `Filtros Aplicados: ${filtros.join(', ')}` : 'Todos os registros (Sem Filtro)';
}

// Exportar PDF
document.getElementById('btnExportPDF').addEventListener('click', () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4'); 
    
    doc.setFontSize(16);
    doc.text("Relatório de Atletas (Admin)", 14, 16);
    
    doc.setFontSize(9);
    doc.text(getFiltrosText(), 14, 24);
    
    const table = document.getElementById('tableAtletas');
    const numCols = 9; // 9 Colunas
    // Remove os ícones e a palavra Ação/ID/etc. do cabeçalho
    const headers = Array.from(table.querySelectorAll('thead th')).slice(0, numCols).map(th => th.innerText.replace(/\s(\w+)$/, '')); 
    const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr => 
        Array.from(tr.querySelectorAll('td')).slice(0, numCols).map(td => td.innerText)
    ).filter(row => row.length > 1);

    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 30,
        headStyles: { 
            fillColor: [18, 52, 86], // Usando a cor primary-dark
            textColor: 255,
            fontSize: 9, 
            fontStyle: 'bold'
        },
        styles: { fontSize: 8, cellPadding: 1.5 } // Células mais compactas
    });

    doc.save('relatorio_atletas.pdf');
});

// Exportar Excel
document.getElementById('btnExportExcel').addEventListener('click', () => {
    const table = document.getElementById('tableAtletas');
    const wb = XLSX.utils.book_new();

    const numCols = 9; // 9 Colunas
    const headers = Array.from(table.querySelectorAll('thead th')).slice(0, numCols).map(th => th.innerText.replace(/\s(\w+)$/, ''));
    const data = Array.from(table.querySelectorAll('tbody tr')).map(tr => 
        Array.from(tr.querySelectorAll('td')).slice(0, numCols).map(td => td.innerText)
    ).filter(row => row.length > 1);
    
    data.unshift(headers); 
    data.unshift([getFiltrosText()]); 

    const ws = XLSX.utils.aoa_to_sheet(data);
    if (data.length > 0) {
        ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: headers.length - 1 } }]; 
    }

    XLSX.utils.book_append_sheet(wb, ws, "Atletas");
    XLSX.writeFile(wb, 'relatorio_atletas.xlsx');
});
</script>

</body>
</html>