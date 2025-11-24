<?php
ob_start();
include '.db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_tipo = $_SESSION['tipo']; // Tipo de usuário (vendedor ou cliente)

$sql = "SELECT compras.*, 
               produtos.nome AS produto_nome, 
               produtos.vendedor_id,
               usuarios.nome_completo AS cliente_nome, 
               usuarios.idade AS cliente_idade, 
               compras.data_compra, 
               compras.status
        FROM compras 
        JOIN produtos ON compras.produto_id = produtos.id
        JOIN usuarios ON compras.cliente_id = usuarios.id
        WHERE (produtos.vendedor_id = ? OR compras.cliente_id = ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Minhas Candidaturas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        body {
            background-color: #f8f9fa;
            font-size: 14px;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 15px; /* padding horizontal para telas pequenas */
        }
        .card {
            border-radius: 12px;
            padding: 10px;
            font-size: 14px;
            /* Força largura total do card em telas pequenas */
            width: 100%;
        }
        .card-body {
            padding: 10px;
        }
        .card-title {
            color: #0d6efd;
            font-size: 1rem; /* Ajusta para rem para escala responsiva */
            margin-bottom: 10px;
            word-break: break-word; /* Quebra texto longo */
        }
        .card-text {
            margin-bottom: 6px;
            word-break: break-word;
        }
        .form-select,
        .btn {
            font-size: 0.85rem;
            padding: 6px 10px;
        }
        .btn i {
            font-size: 0.85rem;
        }

        /* Para garantir os cards ficam bem no mobile */
        @media (max-width: 576px) {
            .container {
                margin: 20px 10px;
                max-width: 100%;
            }
            .card {
                padding: 15px;
            }
            .form-select,
            .btn {
                font-size: 0.9rem;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow p-4">
            <h2 class="text-center mb-4"><i class="fa-solid fa-money-bill"></i> Candidaturas</h2>

            <?php if ($result->num_rows > 0): ?>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm border-start border-4 border-primary">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($row['produto_nome']) ?></h5>
                                <p class="card-text"><strong>Atleta:</strong> <?= htmlspecialchars($row['cliente_nome']) ?></p>
                                <p class="card-text"><strong>Idade:</strong> <?= number_format($row['cliente_idade']) ?> anos</p>
                                <p class="card-text"><strong>Data de Submissão:</strong> <?= htmlspecialchars($row['data_compra']) ?></p>

                                <p class="card-text mb-3">
                                    <strong>Status:</strong><br />
                                    <?php if ($user_tipo === 'vendedor'): ?>
                                        <form method="POST" action="atualizar_status.php" class="d-flex flex-column flex-sm-row gap-2 align-items-start align-items-sm-center">
                                            <input type="hidden" name="compra_id" value="<?= $row['id'] ?>">
                                            <select name="status" class="form-select form-select-sm" style="min-width: 140px;">
                                                <option value="Pendente" <?= $row['status'] == 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                                                <option value="Aprovado" <?= $row['status'] == 'Aprovado' ? 'selected' : '' ?>>Aprovado</option>
                                                <option value="Rejeitado" <?= $row['status'] == 'Rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary" aria-label="Atualizar status"><i class="fa-solid fa-check"></i></button>
                                        </form>
                                        <a href="ver_candidato.php?id=<?= $row['cliente_id'] ?>" class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="fa-solid fa-user"></i> Ver Candidato
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-<?php echo ($row['status'] == 'Aprovado') ? 'success' : (($row['status'] == 'Rejeitado') ? 'danger' : 'warning'); ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fa-solid fa-info-circle"></i> Nenhuma candidatura encontrada.
                </div>
            <?php endif; ?>

            <a href="dashboard_recrutador.php" class="btn btn-secondary mt-4">
                <i class="fa-solid fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>