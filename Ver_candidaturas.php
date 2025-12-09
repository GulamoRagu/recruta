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
$user_tipo = $_SESSION['tipo']; // Deve ser 'vendedor' (Recrutador)

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

// 2. Definição e Sanitização do Filtro de Status
$filtro_status = $_GET['status'] ?? '';
$valid_statuses = ['Pendente', 'Aprovado', 'Rejeitado'];

// 3. Construção da Query Principal (Trazendo todas as candidaturas para as vagas deste recrutador)
// O nome da tabela "compras" foi mantido, mas o contexto é de candidaturas.
$sql = "
    SELECT 
        c.id, c.data_compra, c.status, c.cliente_id,
        p.nome AS vaga_nome, 
        p.genero_permitido,
        u.nome_completo AS atleta_nome, 
        u.idade AS atleta_idade
    FROM compras c 
    JOIN produtos p ON c.produto_id = p.id
    JOIN usuarios u ON c.cliente_id = u.id
    WHERE p.recrutador_id = ?
";

$tipos = 'i';
$parametros = [$user_id];

// Adiciona o filtro de status de forma segura (Prepared Statement)
if (in_array($filtro_status, $valid_statuses)) {
    $sql .= " AND c.status = ?";
    $tipos .= 's';
    $parametros[] = $filtro_status;
}

$sql .= " ORDER BY c.data_compra DESC";

// 4. Execução da Query
$stmt = $conn->prepare($sql);
$stmt->bind_param($tipos, ...$parametros);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Candidaturas | <?= htmlspecialchars($nome_usuario) ?></title>
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome -->
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

        /* Sidebar Moderna */
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
        
        /* Ações na Tabela - Estilo para Mobile */
        .actions-cell {
            min-width: 200px;
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .sidebar { display: none !important; }
            .content { margin-left: 0; padding-top: 10px; }
            .table-responsive > .table {
                font-size: 0.9rem;
            }
            .actions-cell {
                min-width: 280px; /* Garante que os campos de ação caibam */
            }
        }
    </style>
</head>
<body>

<!-- Sidebar (Fixa no Desktop) -->
<nav class="sidebar d-none d-lg-block">
    <h4 class="text-white"><i class="fa-solid fa-building me-2"></i> <?= htmlspecialchars($nome_usuario) ?></h4>
    <a href="dashboard_recrutador.php"><i class="fa-solid fa-gauge-high"></i> Inicio</a>
    <a href="perfil_recrutador.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
    <a href="vagas_recrutador.php"><i class="fa-solid fa-briefcase"></i> Gerir Vagas</a>
    <a href="ver_candidaturas.php" class="active"><i class="fa-solid fa-list-check"></i> Candidaturas</a>
    <a href="logout.php" class="text-warning mt-auto"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
</nav>

<!-- Conteúdo Principal -->
<div class="content">
    <h2 class="mb-4"><i class="fa-solid fa-users me-2 text-primary"></i> Candidaturas Recebidas</h2>

    <!-- Filtro de Status -->
    <form method="GET" class="card shadow-sm p-3 mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label fw-bold small text-muted">Filtrar por Status</label>
                <select name="status" class="form-select">
                    <option value="">Todas as Candidaturas</option>
                    <?php foreach ($valid_statuses as $status): ?>
                        <option value="<?= $status ?>" <?= ($filtro_status === $status ? 'selected' : '') ?>>
                            <?= $status ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8 col-lg-9">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter me-1"></i> Aplicar Filtro
                </button>
                <a href="ver_candidaturas.php" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-times me-1"></i> Limpar Filtro
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
                        <th class="p-3">Vaga</th>
                        <th class="p-3">Atleta (Candidato)</th>
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
                            <p class="small text-muted mb-0">Gênero: <?= htmlspecialchars($row['genero_permitido']) ?></p>
                        </td>
                        <td><?= htmlspecialchars($row['atleta_nome']) ?></td>
                        <td><?= htmlspecialchars($row['atleta_idade']) ?> anos</td>
                        <td><?= date("d/m/Y H:i", strtotime($row['data_compra'])) ?></td>

                        <td>
                            <span class="badge bg-<?= $badge_class ?> p-2"><?= $row['status'] ?></span>
                        </td>

                        <?php if ($user_tipo === 'vendedor'): ?>
                        <td class="actions-cell">
                            
                            <!-- Botão Ver Perfil -->
                            <a href="ver_candidato.php?id=<?= $row['cliente_id'] ?>" class="btn btn-sm btn-outline-primary mb-2 me-2" title="Ver Perfil Completo">
                                <i class="fa-solid fa-id-card me-1"></i> Perfil
                            </a>

                            <!-- Atualizar status/form: Centralizado e agrupado -->
                            <form method="POST" action="atualizar_status.php" class="d-inline-flex flex-wrap gap-2">
                                <input type="hidden" name="compra_id" value="<?= $row['id'] ?>">
                                
                                <select name="status" class="form-select form-select-sm w-auto">
                                    <option value="Pendente" <?= $row['status']=='Pendente'?'selected':'' ?>>Pendente</option>
                                    <option value="Aprovado" <?= $row['status']=='Aprovado'?'selected':'' ?>>Aprovado</option>
                                    <option value="Rejeitado" <?= $row['status']=='Rejeitado'?'selected':'' ?>>Rejeitado</option>
                                </select>
                                
                                <!-- Mensagem é opcional e deve ser enviada via modal/link para evitar poluir a tabela -->
                                <!-- Adicionado um placeholder para a textarea, caso o usuário queira mantê-la aqui -->
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
            <h4 class="alert-heading"><i class="fa-solid fa-info-circle me-2"></i> Sem Candidaturas</h4>
            <p>Nenhuma candidatura foi recebida ainda ou nenhuma corresponde ao filtro aplicado.</p>
            <a href="vagas_recrutador.php" class="btn btn-primary mt-2">
                <i class="fa-solid fa-briefcase me-1"></i> Gerir Vagas
            </a>
        </div>
    <?php endif; ?>

    <a href="dashboard_recrutador.php" class="btn btn-secondary mt-5">
        <i class="fa-solid fa-arrow-left"></i> Inicio
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>