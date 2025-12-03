<?php
ob_start();
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$recrutador_id = (int) $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<script>alert('ID da vaga inválido.'); window.location='listar_vaga.php';</script>";
    exit();
}

// Buscar apenas vaga atribuída ao recrutador
$sql = "SELECT * FROM produtos WHERE id = ? AND recrutador_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $recrutador_id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produto) {
    echo "<script>alert('Você não tem permissão para editar esta vaga ou vaga não encontrada.'); window.location='listar_vaga.php';</script>";
    exit();
}

// Preenchimento de campos com fallback
$nome = htmlspecialchars($produto['nome']);
$descricao = htmlspecialchars($produto['descricao']);
$preco = htmlspecialchars($produto['preco']); // aqui representa "idade máxima"
$genero_atual = $produto['genero_permitido'] ?? 'Ambos';
$modalidade = htmlspecialchars($produto['modalidade'] ?? '');
$posicao = htmlspecialchars($produto['posicao'] ?? '');
$data_validade = $produto['data_validade'] ? date('Y-m-d', strtotime($produto['data_validade'])) : '';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vaga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow mx-auto" style="max-width: 800px;">
        <div class="card-body">
            <h2 class="text-center mb-4"><i class="fa-solid fa-pen-to-square"></i> Editar Vaga</h2>

            <form action="process_editar_vaga.php" method="POST">
                <input type="hidden" name="id" value="<?= $produto['id'] ?>">

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome da Vaga</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?= $nome ?>" required>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="4" required><?= $descricao ?></textarea>
                </div>

                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label for="preco" class="form-label">Idade Máxima</label>
                        <input type="number" min="0" class="form-control" id="preco" name="preco" value="<?= $preco ?>" required>
                    </div>

                    <div class="mb-3 col-md-4">
                        <label for="genero_permitido" class="form-label">Gênero Permitido</label>
                        <select id="genero_permitido" name="genero_permitido" class="form-select" required>
                            <option value="Ambos" <?= $genero_atual === 'Ambos' ? 'selected' : '' ?>>Ambos</option>
                            <option value="Masculino" <?= $genero_atual === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                            <option value="Feminino" <?= $genero_atual === 'Feminino' ? 'selected' : '' ?>>Feminino</option>
                        </select>
                    </div>

                    <div class="mb-3 col-md-4">
                        <label for="data_validade" class="form-label">Data de Validade</label>
                        <input type="date" class="form-control" id="data_validade" name="data_validade" value="<?= $data_validade ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label for="modalidade" class="form-label">Modalidade</label>
                        <input type="text" class="form-control" id="modalidade" name="modalidade" value="<?= $modalidade ?>">
                    </div>

                    <div class="mb-3 col-md-6">
                        <label for="posicao" class="form-label">Posição</label>
                        <input type="text" class="form-control" id="posicao" name="posicao" value="<?= $posicao ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Atualizar Vaga</button>
            </form>

            <a href="listar_vaga.php" class="btn btn-secondary mt-3 w-100">
                <i class="fa-solid fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
