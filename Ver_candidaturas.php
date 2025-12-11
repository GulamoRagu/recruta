<?php
ob_start();
include 'db.php';
session_start();

// 1. Verificação de Acesso
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_tipo = $_SESSION['tipo'];

// Buscar nome do recrutador para a sidebar
$nome_usuario = 'Recrutador';
if ($stmt_user = $conn->prepare('SELECT username FROM usuarios WHERE id = ? LIMIT 1')) {
    $stmt_user->bind_param('i', $user_id);
    if ($stmt_user->execute()) {
        $res_user = $stmt_user->get_result();
        if ($rowUser = $res_user->fetch_assoc()) {
            $nome_usuario = $rowUser['username'] ?? 'Recrutador';
        }
    }
    $stmt_user->close();
}

// 2. BUSCAR VAGAS DO RECRUTADOR PARA O FILTRO
$vagas_do_recrutador = [];
$query_vagas = $conn->prepare("SELECT id, nome, genero_permitido FROM produtos WHERE recrutador_id = ? ORDER BY nome ASC");
$query_vagas->bind_param("i", $user_id);
$query_vagas->execute();
$result_vagas = $query_vagas->get_result();
while ($vaga = $result_vagas->fetch_assoc()) {
    $vagas_do_recrutador[$vaga['id']] = $vaga['nome'] . " (Gênero: " . $vaga['genero_permitido'] . ")";
}
$query_vagas->close();


// 3. Definição e Sanitização dos Filtros
$valid_statuses = ['Pendente', 'Aprovado', 'Rejeitado'];
$valid_genders = ['Masculino', 'Feminino', 'Outro']; // Gêneros do atleta

$filtro_status = $_GET['status'] ?? '';
$filtro_genero = $_GET['genero'] ?? '';
$min_idade = filter_var($_GET['min_idade'] ?? '', FILTER_VALIDATE_INT);
$max_idade = filter_var($_GET['max_idade'] ?? '', FILTER_VALIDATE_INT);
$filtro_vaga_id = filter_var($_GET['vaga_id'] ?? '', FILTER_VALIDATE_INT); // NOVO FILTRO VAGA

// 4. Construção da Query Principal
$sql = "
    SELECT 
        c.id, c.data_compra, c.status, c.cliente_id,
        p.nome AS vaga_nome, 
        p.genero_permitido,
        u.nome_completo AS atleta_nome, 
        u.idade AS atleta_idade,
        u.genero AS atleta_genero
    FROM compras c 
    JOIN produtos p ON c.produto_id = p.id
    JOIN usuarios u ON c.cliente_id = u.id
    WHERE p.recrutador_id = ?
";

$tipos = 'i';
$parametros = [$user_id];

// --- 4.1 Adiciona Filtro de VAGA (produto_id) ---
if ($filtro_vaga_id !== false && $filtro_vaga_id > 0) {
    $sql .= " AND c.produto_id = ?";
    $tipos .= 'i';
    $parametros[] = $filtro_vaga_id;
}

// --- 4.2 Adiciona Filtro de STATUS ---
if (in_array($filtro_status, $valid_statuses)) {
    $sql .= " AND c.status = ?";
    $tipos .= 's';
    $parametros[] = $filtro_status;
}

// --- 4.3 Adiciona Filtro de GÊNERO DO ATLETA ---
if (in_array($filtro_genero, $valid_genders)) {
    $sql .= " AND u.genero = ?";
    $tipos .= 's';
    $parametros[] = $filtro_genero;
}

// --- 4.4 Adiciona Filtro de IDADE (Mínima e Máxima) ---
if ($min_idade !== false && $min_idade > 0) {
    $sql .= " AND u.idade >= ?";
    $tipos .= 'i';
    $parametros[] = $min_idade;
}

if ($max_idade !== false && $max_idade > 0) {
    $sql .= " AND u.idade <= ?";
    $tipos .= 'i';
    $parametros[] = $max_idade;
}


$sql .= " ORDER BY c.data_compra DESC";

// 5. Execução da Query Principal
$stmt = $conn->prepare($sql);
if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Candidaturas | <?= htmlspecialchars($nome_usuario) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />

    <style>
        :root {
            --primary: #007bff; /* Azul */
            --dark: #1e293b; /* Cinza Escuro */
            --warning: #ffc107; /* Amarelo */
            --background: #f4f6f9;
        }

        body {
            background-color: var(--background);
            font-family: 'Poppins', sans-serif;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Moderna (mantida) */
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

        /* Tabela e Filtros */
        .table-custom {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .table-custom thead {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }
        .table-custom tbody tr:hover {
            background-color: #e9ecef;
        }
        
        /* Ações na Tabela */
        .actions-cell {
            min-width: 250px;
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .sidebar { display: none !important; }
            .content { margin-left: 0; padding-top: 10px; }
            .table-responsive > .table {
                font-size: 0.8rem;
            }
            .actions-cell {
                min-width: 280px;
            }
        }
    </style>
</head>
<body>

<nav class="sidebar d-none d-lg-block">
    <h4 class="text-white"><i class="fa-solid fa-building me-2"></i> <?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_recrutador.php"><i class="fa-solid fa-gauge-high"></i> Inicio</a>
    <a href="perfil_recrutador.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
    <a href="vagas_recrutador.php"><i class="fa-solid fa-briefcase"></i> Gerir Vagas</a>
    <a href="ver_candidaturas.php" class="active"><i class="fa-solid fa-list-check"></i> Candidaturas</a>
    <a href="logout.php" class="text-warning mt-auto"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
</nav>

<div class="content">
    <h2 class="mb-4"><i class="fa-solid fa-users-line me-2 text-primary"></i> Gerir Candidaturas Recebidas</h2>

    <form method="GET" class="card shadow-sm p-4 mb-4">
        <h5 class="mb-3 text-primary"><i class="fa-solid fa-funnel-dollar me-2"></i> Opções de Filtro</h5>
        <div class="row g-3">

            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold small text-muted">Vaga (Produto) Específica</label>
                <select name="vaga_id" class="form-select">
                    <option value="">Todas as Minhas Vagas</option>
                    <?php foreach ($vagas_do_recrutador as $id => $nome): ?>
                        <option value="<?= $id ?>" <?= ($filtro_vaga_id == $id ? 'selected' : '') ?>>
                            <?= htmlspecialchars($nome) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold small text-muted">Status da Candidatura</label>
                <select name="status" class="form-select">
                    <option value="">Todos os Status</option>
                    <?php foreach ($valid_statuses as $status): ?>
                        <option value="<?= $status ?>" <?= ($filtro_status === $status ? 'selected' : '') ?>>
                            <?= $status ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="form-label fw-bold small text-muted">Gênero do Atleta</label>
                <select name="genero" class="form-select">
                    <option value="">Todos</option>
                    <option value="M" <?= ($filtro_genero === 'M' ? 'selected' : '') ?>>Masculino </option>
                    <option value="F" <?= ($filtro_genero === 'F' ? 'selected' : '') ?>>Feminino</option>
                    <option value="Outro" <?= ($filtro_genero === 'Outro' ? 'selected' : '') ?>>Outro</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="form-label fw-bold small text-muted">Idade Mínima</label>
                <input type="number" name="min_idade" class="form-control" placeholder="Ex: 18" value="<?= htmlspecialchars($min_idade) ?>" min="1" max="100">
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="form-label fw-bold small text-muted">Idade Máxima</label>
                <input type="number" name="max_idade" class="form-control" placeholder="Ex: 30" value="<?= htmlspecialchars($max_idade) ?>" min="1" max="100">
            </div>

            <div class="col-12 mt-3">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fa-solid fa-filter me-1"></i> Aplicar Filtros
                </button>
                <a href="ver_candidaturas.php" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-times me-1"></i> Limpar Filtros
                </a>
            </div>
        </div>
    </form>
    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive mt-4 card table-custom">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">Vaga (Gênero)</th>
                        <th class="p-3">Atleta (Candidato)</th>
                        <th class="p-3">Gênero do Atleta</th>
                        <th class="p-3">Idade</th>
                        <th class="p-3">Submissão</th>
                        <th class="p-3">Status</th>
                        <?php if ($user_tipo === 'vendedor'): ?>
                            <th class="p-3 actions-cell">Acções & Status</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while ($row = $result->fetch_assoc()):
                        $badge_class = match($row['status']) {
                            'Aprovado' => 'success',
                            'Rejeitado' => 'danger',
                            default => 'warning',
                        };
                    ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td>
                            <strong class="text-primary"><?= htmlspecialchars($row['vaga_nome']) ?></strong>
                            
                        </td>
                        <td><?= htmlspecialchars($row['atleta_nome']?? '') ?></td>
                        <td>
                            <span class="badge bg-secondary p-2">
                                <?= htmlspecialchars($row['atleta_genero']?? '') ?>
                            </span>
                        </td>
                     <td><?= htmlspecialchars($row['atleta_idade'] ?? '') ?> anos</td>
<td>
    <?= $row['data_compra'] ? date("d/m/Y H:i", strtotime($row['data_compra'])) : '' ?>
</td>


                        <td>
                            <span class="badge bg-<?= $badge_class ?> p-2"><?= $row['status'] ?></span>
                        </td>

                        <?php if ($user_tipo === 'vendedor'): ?>
                        <td class="actions-cell">
                            
                            <a href="ver_candidato.php?id=<?= $row['cliente_id'] ?>" class="btn btn-sm btn-outline-primary mb-2 me-2" title="Ver Perfil Completo">
                                <i class="fa-solid fa-id-card me-1"></i> Perfil
                            </a>

                            <form method="POST" action="atualizar_status.php" class="d-inline-flex flex-wrap gap-2">
                                <input type="hidden" name="compra_id" value="<?= $row['id'] ?>">
                                
                                <select name="status" class="form-select form-select-sm w-auto">
                                    <option value="Pendente" <?= $row['status']=='Pendente'?'selected':'' ?>>Pendente</option>
                                    <option value="Aprovado" <?= $row['status']=='Aprovado'?'selected':'' ?>>Aprovado</option>
                                    <option value="Rejeitado" <?= $row['status']=='Rejeitado'?'selected':'' ?>>Rejeitado</option>
                                </select>
                                
                                <textarea name="mensagem" class="form-control form-control-sm" style="width: 100%;" placeholder="Mensagem para o atleta (opcional)"></textarea>

                                <button type="submit" class="btn btn-sm btn-success w-100 mt-1">
                                    <i class="fa-solid fa-sync me-1"></i> Salvar Status
                                </button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center shadow-sm p-4">
            <h4 class="alert-heading"><i class="fa-solid fa-info-circle me-2"></i> Resultado Vazio</h4>
            <p>Nenhuma candidatura corresponde aos **filtros** aplicados. Tente limpar ou ajustar a vaga, status, faixa etária ou gênero.</p>
            <a href="ver_candidaturas.php" class="btn btn-outline-primary mt-2">
                <i class="fa-solid fa-filter-circle-xmark me-1"></i> Limpar Todos os Filtros
            </a>
        </div>
    <?php endif; ?>

    <a href="dashboard_recrutador.php" class="btn btn-secondary mt-5">
        <i class="fa-solid fa-arrow-left"></i> Início
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>