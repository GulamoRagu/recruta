<?php
ob_start();
session_start();
require 'db.php';

// 1. Verificação de Acesso (Deve ser recrutador)
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$recrutador_id = (int) $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);

// Validação do ID
if ($id <= 0) {
    echo "<script>alert('ID da vaga inválido.'); window.location='listar_vaga.php';</script>";
    exit();
}

// 2. Busca e Verificação de Permissão (Seguro via Prepared Statement)
// Buscar apenas vaga atribuída ao recrutador logado
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

// 3. Preenchimento de Variáveis (com clareza de nomes, especialmente para 'preco' = 'idade máxima')
$nome = htmlspecialchars($produto['nome'] ?? '');
$descricao = htmlspecialchars($produto['descricao'] ?? '');
$idade_maxima = htmlspecialchars($produto['preco'] ?? ''); // PREÇO = IDADE MÁXIMA
$genero_atual = $produto['genero_permitido'] ?? 'Ambos';
$modalidade = htmlspecialchars($produto['modalidade'] ?? '');
$posicao = htmlspecialchars($produto['posicao'] ?? '');
$data_validade = $produto['data_validade'] ? date('Y-m-d', strtotime($produto['data_validade'])) : '';

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vaga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #007bff; /* Azul */
            --background: #f4f6f9;
        }
        body {
            background-color: var(--background);
            font-family: 'Poppins', sans-serif;
        }
        .edit-card {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .edit-card h2 {
            font-weight: 700;
            color: var(--primary);
        }
        .form-label {
            font-weight: 600;
            color: #343a40;
            margin-bottom: 5px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="edit-card mx-auto" style="max-width: 800px;">
        <h2 class="text-center mb-4"><i class="fa-solid fa-briefcase me-2"></i> Editar Oportunidade</h2>
        <p class="text-center text-muted mb-4">Veja todos detalhes desta vaga de recrutamento desportivo.</p>

        <form action="process_editar_vaga.php" method="POST">
            <input type="hidden" name="id" value="<?= $produto['id'] ?>">

            <div class="row g-3">
                <div class="mb-3 col-12">
                    <label for="nome" class="form-label">Nome da Vaga (Título)</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?= $nome ?>" required>
                </div>

                <div class="mb-4 col-12">
                    <label for="descricao" class="form-label">Descrição Completa e Requisitos</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="5" required><?= $descricao ?></textarea>
                </div>
            </div>
            
            <hr class="my-4">

            <div class="row g-3">
                <div class="mb-3 col-md-4">
                    <label for="preco" class="form-label"><i class="fa-solid fa-child me-1"></i> Idade Máxima</label>
                    <input type="number" min="10" max="99" class="form-control" id="preco" name="preco" value="<?= $idade_maxima ?>" required>
                </div>

                <div class="mb-3 col-md-4">
                    <label for="genero_permitido" class="form-label"><i class="fa-solid fa-venus-mars me-1"></i> Gênero Permitido</label>
                    <select id="genero_permitido" name="genero_permitido" class="form-select" required>
                        <option value="Ambos" <?= $genero_atual === 'Ambos' ? 'selected' : '' ?>>Ambos</option>
                        <option value="Masculino" <?= $genero_atual === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                        <option value="Feminino" <?= $genero_atual === 'Feminino' ? 'selected' : '' ?>>Feminino</option>
                    </select>
                </div>

                <div class="mb-3 col-md-4">
                    <label for="data_validade" class="form-label"><i class="fa-solid fa-calendar-alt me-1"></i> Data Limite</label>
                    <input type="date" class="form-control" id="data_validade" name="data_validade" value="<?= $data_validade ?>" required>
                </div>
            </div>

            <div class="row g-3">
                <div class="mb-3 col-md-6">
                    <label for="modalidade" class="form-label"><i class="fa-solid fa-futbol me-1"></i> Modalidade</label>
                    <input type="text" class="form-control" id="modalidade" name="modalidade" value="<?= $modalidade ?>" placeholder="Ex: Futebol, Basquete, Natação">
                </div>

                <div class="mb-3 col-md-6">
                    <label for="posicao" class="form-label"><i class="fa-solid fa-crosshairs me-1"></i> Posição Necessária</label>
                    <input type="text" class="form-control" id="posicao" name="posicao" value="<?= $posicao ?>" placeholder="Ex: Zagueiro, Armador, Goleiro">
                </div>
            </div>
            
            <hr class="my-4">

            <div class="d-flex justify-content-between gap-3">
                <button type="submit" class="btn btn-primary btn-lg flex-fill">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Atualizar Vaga
                </button>
                <a href="vagas_recrutador.php" class="btn btn-secondary btn-lg flex-fill">
                    <i class="fa-solid fa-arrow-left me-1"></i> Cancelar e Voltar
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>