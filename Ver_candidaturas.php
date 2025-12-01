<?php
ob_start();
include 'db.php';
session_start();

$user_id = intval($_SESSION['user_id']);
$user_tipo = $_SESSION['tipo']; // Tipo de usuário (recrutador)

$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT compras.*, 
               produtos.nome AS produto_nome, 
               produtos.descricao,
               usuarios.nome_completo AS cliente_nome, 
               usuarios.idade AS cliente_idade, 
               compras.data_compra, 
               compras.status
        FROM compras 
        JOIN produtos ON compras.produto_id = produtos.id
        JOIN usuarios ON compras.cliente_id = usuarios.id
        WHERE produtos.recrutador_id = ?";

if ($filtro_status && in_array($filtro_status, ['Pendente','Aprovado','Rejeitado'])) {
    $sql .= " AND compras.status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $filtro_status);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Candidaturas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4"><i class="fa-solid fa-money-bill"></i> Candidaturas</h2>

        <?php if ($result->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php while ($row = $result->fetch_assoc()): 
                    // Define cor do badge pelo status
                    $badge_class = match($row['status']) {
                        'Aprovado' => 'success',
                        'Rejeitado' => 'danger',
                        default => 'warning', // Pendente ou outro
                    };
                ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-start border-4 border-primary">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($row['produto_nome']) ?></h5>
                            <p class="card-text"><strong>Atleta:</strong> <?= htmlspecialchars($row['cliente_nome']) ?></p>
                            <p class="card-text"><strong>Idade:</strong> <?= number_format($row['cliente_idade']) ?> anos</p>
                            <p class="card-text"><strong>Data de Submissão:</strong> <?= date("d/m/Y H:i", strtotime($row['data_compra'])) ?></p>

                            <p class="card-text mb-3">
                                <strong>Status:</strong>
                                <span class="badge bg-<?= $badge_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                            </p>

                            <?php if ($user_tipo === 'vendedor'): ?>
                                <form method="POST" action="atualizar_status.php" class="d-flex flex-column flex-sm-row gap-2">
                                    <input type="hidden" name="compra_id" value="<?= $row['id'] ?>">
                                    <select name="status" class="form-select form-select-sm" style="min-width: 140px;">
                                        <option value="Pendente" <?= $row['status'] == 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                                        <option value="Aprovado" <?= $row['status'] == 'Aprovado' ? 'selected' : '' ?>>Aprovado</option>
                                        <option value="Rejeitado" <?= $row['status'] == 'Rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-check"></i></button>
                                </form>
                                <a href="ver_candidato.php?id=<?= $row['cliente_id'] ?>" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fa-solid fa-user"></i> Ver Candidato
                                </a>
                            <?php endif; ?>
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
