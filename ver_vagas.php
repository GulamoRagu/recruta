<?php 
ob_start();
session_start();
require 'db.php';

// Verifica se o usuário está logado como cliente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Buscar nome e gênero do atleta logado
$query = $conn->prepare("SELECT nome_completo, genero FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result_user = $query->get_result();
$user = $result_user->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'Atleta';
$genero_atleta = strtolower($user['genero'] ?? '');

// Buscar IDs das vagas em que o atleta já se candidatou
$candidaturas_ids = [];
$res_cand = $conn->query("SELECT produto_id FROM compras WHERE cliente_id = $user_id");
while($row_c = $res_cand->fetch_assoc()) {
    $candidaturas_ids[] = $row_c['produto_id'];
}

// Configurar filtros
$filtro_idade = $_GET['idade'] ?? ''; 
$filtro_modalidade = $_GET['modalidade'] ?? '';
$filtro_genero = $_GET['genero'] ?? '';
$filtro_data = $_GET['data_validade'] ?? '';

// Verificar se o usuário quer ver todas as vagas
$ver_todas = isset($_GET['ver_todas']) && $_GET['ver_todas']=='1';

// Configurar paginação
$vagas_por_pagina = $ver_todas ? 1000 : 3; // 3 vagas por página ou todas se ver_todas=1
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $vagas_por_pagina;

// Construir SQL Base para vagas ativas
$sql_base = "SELECT id, nome, descricao, preco, genero_permitido, data_validade, modalidade, posicao 
             FROM produtos 
             WHERE data_validade >= CURDATE()"; 

$filtro_params = "";
if (!empty($filtro_idade)) $filtro_params .= " AND preco >= " . intval($filtro_idade);
if (!empty($filtro_modalidade)) $filtro_params .= " AND modalidade LIKE '%" . $conn->real_escape_string($filtro_modalidade) . "%'";
if (!empty($filtro_genero)) $filtro_params .= " AND LOWER(genero_permitido) = '" . strtolower($conn->real_escape_string($filtro_genero)) . "'";
if (!empty($filtro_data)) $filtro_params .= " AND data_validade <= '" . $conn->real_escape_string($filtro_data) . "'";

// Consulta total para paginação
$sql_total = "SELECT COUNT(id) AS total FROM produtos WHERE data_validade >= CURDATE() " . $filtro_params;
$result_total = $conn->query($sql_total);
$total_vagas = $result_total ? $result_total->fetch_assoc()['total'] : 0;
$total_paginas = ceil($total_vagas / $vagas_por_pagina);

// Consulta final
$sql_final = $sql_base . $filtro_params . " ORDER BY data_validade ASC LIMIT {$vagas_por_pagina} OFFSET {$offset}";
$result = $conn->query($sql_final);

// Montar parâmetros de filtro para URL
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
body { font-family: 'Poppins', sans-serif; background: #e9ecef; display: flex; min-height: 100vh; margin: 0; }
.sidebar { min-width: 260px; max-width: 260px; background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding-top: 1.5rem; box-shadow: 6px 0 15px rgba(0,0,0,0.25); position: fixed; height: 100%; }
.sidebar h4 { text-align: center; margin-bottom: 2rem; font-weight: 700; }
.sidebar a { color: white; display: flex; align-items: center; padding: 15px 25px; text-decoration: none; font-weight: 500; border-radius: 10px; margin: 8px 15px; border-left: 5px solid transparent; transition: all 0.3s ease; }
.sidebar a i { margin-right: 15px; }
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left: 5px solid #ffc107; }
.main-content { flex-grow: 1; margin-left: 260px; padding: 30px; }

.vaga-card { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform 0.3s ease, box-shadow 0.3s ease; }
.vaga-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.vaga-card.candidatado { background-color: #f8f9fa !important; border: 2px solid #6c757d; opacity: 0.85; }
.vaga-card .card-title { font-weight: 700; color: #007bff; }

.card-filtro { border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 30px; }

.badge-info { background-color: #17a2b8; }
.badge-success { background-color: #28a745; }
.badge-secondary { background-color: #6c757d; }
.btn-success, .btn-primary, .btn-secondary, .btn-warning { border-radius: 8px; font-weight: 600; transition: all 0.3s ease; }
.btn-success:hover, .btn-primary:hover { transform: scale(1.05); }

@media(max-width:992px){ .main-content { margin-left: 0; } .sidebar { position: relative; height: auto; box-shadow: none; } }
</style>
</head>
<body>

<div class="sidebar">
    <h4><i class="fa-solid fa-medal me-2"></i><?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_atleta.php"><i class="fa-solid fa-gauge-high"></i> Início</a>
    <a href="perfil_atleta.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
    <a href="ver_vagas.php" class="active"><i class="fa-solid fa-magnifying-glass"></i> Explorar Vagas</a>
    <a href="minhas_candidaturas.php"><i class="fa-solid fa-clipboard-list"></i> Minhas Candidaturas</a>
    <a href="logout.php" class="text-warning mt-auto mb-3"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
</div>

<div class="main-content">
    <h1 class="mb-4 text-primary"><i class="fa-solid fa-magnifying-glass me-2"></i>Explorar Vagas</h1>

    <div class="card card-filtro">
        <h5 class="mb-3 text-secondary"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
        <form method="GET" class="row g-3">
            <input type="hidden" name="ver_todas" value="<?= $ver_todas?1:0 ?>">
            <div class="col-md-3">
                <label for="modalidade" class="form-label">Modalidade</label>
                <input type="text" name="modalidade" id="modalidade" class="form-control" placeholder="Ex: Futebol" value="<?= htmlspecialchars($filtro_modalidade) ?>">
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
                <label for="idade" class="form-label">Idade Máxima</label>
                <input type="number" name="idade" id="idade" class="form-control" placeholder="Ex: 25" value="<?= htmlspecialchars($filtro_idade) ?>">
            </div>
            <div class="col-md-3">
                <label for="data_validade" class="form-label">Validade Até</label>
                <input type="date" name="data_validade" id="data_validade" class="form-control" value="<?= htmlspecialchars($filtro_data) ?>">
            </div>
            <div class="col-12 text-end">
                <a href="ver_vagas.php" class="btn btn-outline-secondary me-2">Limpar Filtros</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Buscar</button>
            </div>
        </form>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $ja_candidatou = in_array($row['id'], $candidaturas_ids);
                $genero_vaga = strtolower($row['genero_permitido']);
                $genero_diferente = ($genero_vaga !== 'ambos' && $genero_vaga !== $genero_atleta);
            ?>
                <div class="col">
                    <div class="card vaga-card h-100 <?= $ja_candidatou ? 'candidatado' : '' ?>">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($row['nome']) ?></h5>
                                <?php if($ja_candidatou): ?>
                                    <span class="badge bg-secondary"><i class="fa-solid fa-check"></i> Candidatado</span>
                                <?php else: ?>
                                    <span class="badge bg-info"><i class="fa-solid fa-clock"></i> Expira: <?= (new DateTime($row['data_validade']))->format('d/m/Y') ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="card-text text-muted small"><?= nl2br(htmlspecialchars(substr($row['descricao'], 0, 100))) ?><?= strlen($row['descricao'])>100?'...':'' ?></p>
                            <hr>
                            <div class="row g-2 small mb-3">
                                <div class="col-6"><strong><i class="fa-solid fa-person-half-dress me-1"></i> Gênero:</strong> <?= htmlspecialchars($row['genero_permitido']) ?></div>
                                <div class="col-6"><strong><i class="fa-solid fa-calendar-alt me-1"></i> Idade Máx:</strong> <?= htmlspecialchars($row['preco']) ?> anos</div>
                                <div class="col-6"><strong><i class="fa-solid fa-running me-1"></i> Modalidade:</strong> <?= htmlspecialchars($row['modalidade']) ?></div>
                                <div class="col-6"><strong><i class="fa-solid fa-crosshairs me-1"></i> Posição:</strong> <?= htmlspecialchars($row['posicao']) ?></div>
                            </div>
                            <div class="mt-auto text-center pt-2">
                                <?php if($genero_diferente): ?>
                                    <button class="btn btn-warning w-100" disabled><i class="fa-solid fa-ban"></i> Vaga destinada a outro gênero</button>
                                <?php elseif(!$ja_candidatou): ?>
                                    <a href="candidatar_se.php?id=<?= $row['id'] ?>" class="btn btn-success w-100"><i class="fa-solid fa-paper-plane"></i> Candidatar-se</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled><i class="fa-solid fa-check-circle"></i> Registrado</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Nenhuma vaga encontrada para seus filtros.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-center mt-4 gap-2">
        <?php if(!$ver_todas && $total_vagas > $vagas_por_pagina): 
            $prev_page_link = $pagina_atual>1?"ver_vagas.php?pagina=".($pagina_atual-1)."&".$url_filtros:"#";
            $next_page_link = $pagina_atual<$total_paginas?"ver_vagas.php?pagina=".($pagina_atual+1)."&".$url_filtros:"#";
            $prev_disabled = $pagina_atual<=1?"disabled":""; 
            $next_disabled = $pagina_atual>=$total_paginas?"disabled":""; 
        ?>
            <a href="<?= $prev_page_link ?>" class="btn btn-outline-primary <?= $prev_disabled ?>"><i class="fa-solid fa-chevron-left"></i> Anterior</a>
            <span class="btn btn-secondary disabled">Página <?= $pagina_atual ?> de <?= $total_paginas ?></span>
            <a href="<?= $next_page_link ?>" class="btn btn-primary <?= $next_disabled ?>">Próximo <i class="fa-solid fa-chevron-right"></i></a>
        <?php endif; ?>

        <?php if(!$ver_todas && $total_vagas > $vagas_por_pagina): ?>
            <a href="ver_vagas.php?ver_todas=1&<?= $url_filtros ?>" class="btn btn-success"><i class="fa-solid fa-eye"></i> Ver Todas</a>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

