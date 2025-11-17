<?php
ob_start();
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Atualizar dados se o formulário for enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_completo = $_POST['nome_completo'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];
    $idade = $_POST['idade'];
    $posicao = $_POST['posicao'];
    $clube_anterior = $_POST['clube_anterior'];
    $modalidade = $_POST['modalidade'];
    $pe = $_POST['pe'];
    $genero = $_POST['genero'];
    $situacao_atual = $_POST['situacao_atual'];
    $foto_perfil = $_POST['foto_perfil'];

    // Atualizar dados principais
    // Atualizar dados principais
$stmt = $conn->prepare(
    "UPDATE usuarios
       SET nome_completo = ?, telefone = ?, endereco = ?, idade = ?, posicao = ?,
           clube_anterior = ?, modalidade = ?, pe = ?, genero = ?, situacao_atual = ?
     WHERE id = ?"
);
$stmt->bind_param(
    "sssissssssi",
    $nome_completo, $telefone, $endereco, $idade, $posicao,
    $clube_anterior, $modalidade, $pe, $genero, $situacao_atual,
    $user_id
);
$stmt->execute(); // <-- ADICIONE ISSO

      

    // Se houver foto de perfil enviada
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
        $foto_nome = time() . "_" . basename($_FILES['foto_perfil']['name']);
        $target_path = "uploads/" . $foto_nome;

        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_path)) {
            $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
            $stmt->bind_param("si", $foto_nome, $user_id);
            $stmt->execute();
        }
    }

    header("Location: perfil_atleta.php");
    exit();
}

// Buscar os dados atuais
$query = $conn->prepare("SELECT nome_completo, telefone, endereco, idade, posicao, foto_perfil, clube_anterior, modalidade, pe, genero, situacao_atual FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card {
            max-width: 600px;
            width: 100%;
            margin: auto;
            margin-top: 50px;
            padding: 20px;
        }
    </style>
</head>


<body>
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Editar Perfil do Atleta</h2>
        <form method="POST" enctype="multipart/form-data">
            <?php if (!empty($user['foto_perfil'])): ?>
                <div class="text-center mb-3">
                    <img src="uploads/<?= htmlspecialchars($user['foto_perfil']) ?>"
     class="rounded-circle img-fluid"
     style="max-width: 120px;" alt="Foto de Perfil">
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <label for="nome_completo" class="form-label">Nome Completo</label>
                <input type="text" name="nome_completo" id="nome_completo" class="form-control" value="<?= htmlspecialchars($user['nome_completo']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="telefone" class="form-label">Telefone</label>
                <input type="text" name="telefone" id="telefone" class="form-control" value="<?= htmlspecialchars($user['telefone']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="endereco" class="form-label">Endereço</label>
                <input type="text" name="endereco" id="endereco" class="form-control" value="<?= htmlspecialchars($user['endereco']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="idade" class="form-label">Idade</label>
                <input type="number" name="idade" id="idade" class="form-control" value="<?= htmlspecialchars($user['idade']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="posicao" class="form-label">Posição</label>
                <input type="text" name="posicao" id="posicao" class="form-control" value="<?= htmlspecialchars($user['posicao']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="clube_anterior" class="form-label">Clubr Anterior</label>
                <input type="text" name="clube_anterior" id="clube_anterior" class="form-control" value="<?= htmlspecialchars($user['clube_anterior']) ?>" required>
        
            </div>
             
            <div class="mb-3">
    <label class="form-label">Modalidade</label><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="modalidade" id="modalidade_futebol" value="Futebol" <?= $user['modalidade'] == 'Futebol' ? 'checked' : '' ?>>
        <label class="form-check-label" for="modalidade_futebol">Futebol</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="modalidade" id="modalidade_futsal" value="Futsal" <?= $user['modalidade'] == 'Futsal' ? 'checked' : '' ?>>
        <label class="form-check-label" for="modalidade_futsal">Futsal</label>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Pé Preferido</label><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="pe" id="pe_destro" value="Destro" <?= $user['pe'] == 'Destro' ? 'checked' : '' ?>>
        <label class="form-check-label" for="pe_destro">Destro</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="pe" id="pe_canhoto" value="Canhoto" <?= $user['pe'] == 'Canhoto' ? 'checked' : '' ?>>
        <label class="form-check-label" for="pe_canhoto">Canhoto</label>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Gênero</label><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="genero" id="genero_masculino" value="Masculino" <?= $user['genero'] == 'Masculino' ? 'checked' : '' ?>>
        <label class="form-check-label" for="genero_masculino">Masculino</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="genero" id="genero_feminino" value="Feminino" <?= $user['genero'] == 'Feminino' ? 'checked' : '' ?>>
        <label class="form-check-label" for="genero_feminino">Feminino</label>
    </div>
</div>

            <div class="mb-3">
                <label for="situacao_atual" class="form-label">Situacao actual</label>
                <input type="text" name="situacao_atual" id="situacao_atual" class="form-control" value="<?= htmlspecialchars($user['situacao_atual']) ?>" required>
        
            </div>
            <div class="mb-3">
                <label for="foto_perfil" class="form-label">Nova Foto de Perfil</label>
                <input type="file" name="foto_perfil" id="foto_perfil" class="form-control" accept="image/*">
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success">Salvar Alterações</button>
                <a href="perfil_atleta.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>