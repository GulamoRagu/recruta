<?php
ob_start();
include '../db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ID da vaga a editar
$id = intval($_GET['id']);

// Buscar dados da vaga
$sql = "SELECT * FROM produtos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Buscar recrutadores (tipo = recrutador)
$sql_rec = "SELECT id, username FROM usuarios WHERE tipo = 'vendedor'";
$recrutadores = $conn->query($sql_rec);

  header("Location: listar_vaga.php");
    exit();


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
<body>

<main class="col px-4 py-4">
    <div class="card shadow mx-auto" style="max-width: 600px;">
        <div class="card-body">
            <h2 class="text-center"><i class="fa-solid fa-pen-to-square"></i> Editar Vaga</h2>

            <form action="process_editar_vaga.php" method="POST">
                <input type="hidden" name="id" value="<?= $produto['id'] ?>">

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome" 
                           value="<?= htmlspecialchars($produto['nome']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" required><?= htmlspecialchars($produto['descricao']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="preco" class="form-label">Idade Máxima</label>
                    <input type="text" class="form-control" 
                           id="preco" name="preco" 
                           value="<?= htmlspecialchars($produto['preco']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="data_validade" class="form-label">Data de Validade</label>
                    <input type="date" class="form-control" 
                           id="data_validade" name="data_validade" 
                           value="<?= $produto['data_validade'] ?>" required>
                </div>

                <!-- Recrutador responsável -->
                <div class="mb-3">
                    <label for="recrutador" class="form-label">Recrutador Responsável</label>
                    <select name="recrutador" id="recrutador" class="form-select" required>
                        <?php while($rec = $recrutadores->fetch_assoc()): ?>
                            <option value="<?= $rec['id'] ?>"
                                <?= ($produto['recrutador_id'] == $rec['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rec['username']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100">Atualizar</button>
            </form>

            <a href="./listar_vaga.php" class="btn btn-secondary mt-3 w-100">
                <i class="fa-solid fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
