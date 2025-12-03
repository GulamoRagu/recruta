<?php
ob_start();
require 'db.php';

// Buscar todas as vagas registradas pelo admin
$sql = "SELECT v.id, v.nome, v.descricao, v.genero_permitido, v.preco, v.modalidade, v.posicao, v.data_validade, u.username AS recrutador
        FROM produtos v
        JOIN usuarios u ON v.vendedor_id = u.id
        ORDER BY v.data_validade DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>

<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vagas Disponíveis</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 70px; font-family: Arial, sans-serif; }
    .card.expired { opacity: 0.6; }
</style>
</head>
<body>

<?php include 'index.php'; ?><!-- Menu principal -->

<div class="container mt-4">
    <h2 class="text-center mb-4">Vagas Disponíveis</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php while($row = $result->fetch_assoc()):
            $data_validade = new DateTime($row['data_validade']);
            $hoje = new DateTime();
            $vencido = $data_validade < $hoje;
        ?>
        <div class="col">
            <div class="card h-100 <?= $vencido ? 'expired border-danger' : '' ?>">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($row['nome']) ?></h5>
                    <p class="card-text"><?= nl2br(htmlspecialchars($row['descricao'])) ?></p>
                    <p><strong>Idade Máxima:</strong> <?= htmlspecialchars($row['preco']) ?></p>
                    <p><strong>Gênero:</strong> <?= htmlspecialchars($row['genero_permitido']) ?></p>
                    <p><strong>Modalidade:</strong> <?= htmlspecialchars($row['modalidade']) ?></p>
                    <p><strong>Posição:</strong> <?= htmlspecialchars($row['posicao']) ?></p>
                    <p><strong>Recrutador:</strong> <?= htmlspecialchars($row['recrutador']) ?></p>
                    <p><strong>Validade:</strong> <?= $data_validade->format('d/m/Y') ?> <?= $vencido ? '<span class="text-danger fw-bold">(Expirou)</span>' : '' ?></p>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
