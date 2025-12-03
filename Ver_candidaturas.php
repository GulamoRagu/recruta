<?php
ob_start();
include 'db.php';
session_start();

$user_id = intval($_SESSION['user_id']);
$user_tipo = $_SESSION['tipo']; // Tipo de usuário (recrutador)

$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT compras.*, 
               produtos.nome AS produto_nome, 
               produtos.descricao,produtos.genero_permitido,
               usuarios.nome_completo AS cliente_nome, 
               usuarios.idade AS cliente_idade, 
               compras.data_compra, 
               compras.status
        FROM compras 
        JOIN produtos ON compras.produto_id = produtos.id
        JOIN usuarios ON compras.cliente_id = usuarios.id
        WHERE produtos.recrutador_id = ?";

        $sql_msg = "SELECT mensagem, data_envio FROM mensagens_status WHERE compra_id = ? AND remetente = 'vendedor'";
$stmt_msg = $conn->prepare($sql_msg);
$stmt_msg->bind_param("i", $row['id']); 
$stmt_msg->execute();
$result_msg = $stmt_msg->get_result();
$mensagem_recrutador = $result_msg->fetch_assoc();
$stmt_msg->close();


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
    <div class="table-responsive mt-4">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Vaga</th>
                    <th>Genero Permitido</th>
                    <th>Nome do Atleta</th>
                    <th>Idade</th>
                    <th>Data de Submissão</th>
                    <th>Status</th>
                    <?php if ($user_tipo === 'vendedor'): ?>
                        <th>Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                while ($row = $result->fetch_assoc()):
                    $badge_class = match($row['status']) {
                        'Aprovado' => 'success',
                        'Rejeitado' => 'danger',
                        default => 'warning',
                    };
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['produto_nome']) ?></td>
                    <td><?= htmlspecialchars($row['genero_permitido']) ?></td>
                    <td><?= htmlspecialchars($row['cliente_nome']) ?></td>
                    <td><?= number_format($row['cliente_idade']) ?> anos</td>
                    <td><?= date("d/m/Y H:i", strtotime($row['data_compra'])) ?></td>

                    <td>
    <span class="badge bg-<?= $badge_class ?>"><?= $row['status'] ?></span>
    <?php if (!empty($mensagem_recrutador['mensagem'])): ?>
        <p class="mt-1 small text-muted"><strong>Mensagem:</strong> <?= nl2br(htmlspecialchars($mensagem_recrutador['mensagem'])) ?></p>
    <?php endif; ?>
</td>

                    <?php if ($user_tipo === 'vendedor'): ?>
                    <td class="d-flex gap-2">

                        <!-- Atualizar status -->
                       <form method="POST" action="atualizar_status.php" class="d-flex gap-2 flex-column">
    <input type="hidden" name="compra_id" value="<?= $row['id'] ?>">
    <select name="status" class="form-select form-select-sm">
        <option value="Pendente" <?= $row['status']=='Pendente'?'selected':'' ?>>Pendente</option>
        <option value="Aprovado" <?= $row['status']=='Aprovado'?'selected':'' ?>>Aprovado</option>
        <option value="Rejeitado" <?= $row['status']=='Rejeitado'?'selected':'' ?>>Rejeitado</option>
    </select>
    <textarea name="mensagem" class="form-control form-control-sm mt-2" placeholder="Mensagem para o atleta (opcional)"></textarea>
    <button type="submit" class="btn btn-sm btn-primary mt-2">
        <i class="fa-solid fa-check"></i> Atualizar
    </button>
</form>


                        <!-- Ver candidato -->
                        <a href="ver_candidato.php?id=<?= $row['cliente_id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-user"></i>
                        </a>

                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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
