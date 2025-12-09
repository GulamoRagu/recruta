<?php
ob_start();
session_start();
require 'db.php';

// 1. Verificação de Autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$success_message = '';
$error_message = '';

// 2. Processamento da Submissão do Formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitização e Coleta de Dados
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $idade = intval($_POST['idade'] ?? 0);
    $posicao = trim($_POST['posicao'] ?? '');
    $clube_anterior = trim($_POST['clube_anterior'] ?? '');
    $modalidade = trim($_POST['modalidade'] ?? ''); // O valor virá do novo SELECT
    $pe = trim($_POST['pe'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $situacao_atual = trim($_POST['situacao_atual'] ?? '');
    
    // Validação básica
    if (empty($nome_completo) || empty($posicao) || $idade <= 0 || empty($modalidade)) {
        $error_message = "Por favor, preencha nome completo, idade, posição e selecione a modalidade.";
    } else {
        // Atualização de dados principais (Prepared Statement)
        $stmt = $conn->prepare(
            "UPDATE usuarios
             SET nome_completo = ?, telefone = ?, endereco = ?, idade = ?, posicao = ?,
                 clube_anterior = ?, modalidade = ?, pe = ?, genero = ?, situacao_atual = ?
             WHERE id = ?"
        );
        
        if ($stmt) {
            // "sssissssssi" -> s (string) para todos, i (integer) para idade e user_id
            $stmt->bind_param(
                "sssissssssi",
                $nome_completo, $telefone, $endereco, $idade, $posicao,
                $clube_anterior, $modalidade, $pe, $genero, $situacao_atual,
                $user_id
            );
            
            if ($stmt->execute()) {
                $success_message = "Perfil atualizado com sucesso!";
            } else {
                $error_message = "Erro ao atualizar dados: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Erro interno na preparação da query.";
        }
    }

    // 3. Processamento da Foto de Perfil (se não houver erro no formulário)
    if (empty($error_message) && isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $foto_ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $foto_nome = $user_id . "_" . time() . "." . $foto_ext;
        $target_path = $upload_dir . $foto_nome;

        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_path)) {
            // Atualiza o nome da foto no banco de dados
            $stmt_foto = $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
            $stmt_foto->bind_param("si", $foto_nome, $user_id);
            $stmt_foto->execute();
            $stmt_foto->close();
            
            $success_message = "Perfil e foto atualizados com sucesso!";
        } else {
            $error_message .= (empty($error_message) ? '' : ' ') . "Erro ao fazer upload da imagem.";
        }
    }
    
    // Redireciona com feedback, se não houver erro
    if (empty($error_message)) {
        // Redireciona para o perfil com parâmetro de sucesso
        header("Location: perfil_atleta.php?edit=success");
        exit();
    }
}

// 4. Buscar os dados atuais do usuário (para preencher o formulário)
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
    <title>Editar Perfil do Atleta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #28a745; /* Verde Sucesso/Foco */
            --dark: #343a40;
            --background: #f4f6f9;
        }
        body {
            background-color: var(--background);
            font-family: 'Poppins', sans-serif;
        }
        .edit-card {
            max-width: 750px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .edit-card h2 {
            font-weight: 700;
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        .input-group-text {
            background-color: #eee;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
        }
        .rounded-circle {
            border: 4px solid var(--primary);
            object-fit: cover;
        }
    </style>
</head>


<body>
    <div class="container">
        <div class="edit-card">
            <h2><i class="fa-solid fa-user-edit me-2"></i> Editar Perfil do Atleta</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                
                <?php if (!empty($user['foto_perfil'])): ?>
                    <div class="text-center mb-4">
                        <img src="uploads/<?= htmlspecialchars($user['foto_perfil']) ?>"
                            class="rounded-circle"
                            style="width: 120px; height: 120px;" alt="Foto de Perfil Atual">
                        <p class="small text-muted mt-2">Foto Atual</p>
                    </div>
                <?php endif; ?>

                <h4 class="mb-3 text-primary"><i class="fa-solid fa-address-card me-2"></i> Informações Básicas</h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="nome_completo" class="form-label">Nome Completo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-id-card-clip"></i></span>
                            <input type="text" name="nome_completo" id="nome_completo" class="form-control" value="<?= htmlspecialchars($user['nome_completo']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="telefone" class="form-label">Telefone</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                            <input type="text" name="telefone" id="telefone" class="form-control" value="<?= htmlspecialchars($user['telefone']) ?>" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="endereco" class="form-label">Endereço</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-location-dot"></i></span>
                            <input type="text" name="endereco" id="endereco" class="form-control" value="<?= htmlspecialchars($user['endereco']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="idade" class="form-label">Idade</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-birthday-cake"></i></span>
                            <input type="number" name="idade" id="idade" class="form-control" value="<?= htmlspecialchars($user['idade']) ?>" required min="10">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gênero</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="genero" id="genero_masculino" value="Masculino" <?= $user['genero'] == 'Masculino' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="genero_masculino">Masculino</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="genero" id="genero_feminino" value="Feminino" <?= $user['genero'] == 'Feminino' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="genero_feminino">Feminino</label>
                            </div>
                        </div>
                    </div>
                </div>

                <h4 class="mb-3 text-primary mt-4"><i class="fa-solid fa-futbol me-2"></i> Dados Esportivos</h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="modalidade" class="form-label">Modalidade Esportiva</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-person-running"></i></span>
                            <select name="modalidade" id="modalidade" class="form-select" required>
                                <option value="" disabled>Escolha a Modalidade</option>
                                <option value="Futebol" <?= ($user['modalidade'] == 'Futebol') ? 'selected' : '' ?>>Futebol</option>
                                <option value="Voleibol" <?= ($user['modalidade'] == 'Voleibol') ? 'selected' : '' ?>>Voleibol</option>
                                <option value="Basquetebol" <?= ($user['modalidade'] == 'Basquetebol') ? 'selected' : '' ?>>Basquetebol</option>
                                <option value="Sem modalidade" <?= ($user['modalidade'] == 'Sem modalidade') ? 'selected' : '' ?>>Sem modalidade</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="posicao" class="form-label">Posição em Campo/Quadra</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-crosshairs"></i></span>
                            <input type="text" name="posicao" id="posicao" class="form-control" value="<?= htmlspecialchars($user['posicao']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pé Preferido</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pe" id="pe_destro" value="Destro" <?= $user['pe'] == 'Destro' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pe_destro">Destro</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pe" id="pe_canhoto" value="Canhoto" <?= $user['pe'] == 'Canhoto' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pe_canhoto">Canhoto</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="clube_anterior" class="form-label">Clube Anterior</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
                            <input type="text" name="clube_anterior" id="clube_anterior" class="form-control" value="<?= htmlspecialchars($user['clube_anterior']) ?>" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="situacao_atual" class="form-label">Situação Atual (Contrato, Livre, etc.)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-list-check"></i></span>
                            <input type="text" name="situacao_atual" id="situacao_atual" class="form-control" value="<?= htmlspecialchars($user['situacao_atual']) ?>" required>
                        </div>
                    </div>
                </div>

                <h4 class="mb-3 text-primary mt-4"><i class="fa-solid fa-camera me-2"></i> Foto de Perfil</h4>
                <div class="mb-3">
                    <label for="foto_perfil" class="form-label">Nova Foto de Perfil (Máx. 2MB)</label>
                    <input type="file" name="foto_perfil" id="foto_perfil" class="form-control" accept="image/*">
                </div>

                <div class="d-flex justify-content-center gap-3 pt-3 border-top mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Salvar Alterações
                    </button>
                    <a href="perfil_atleta.php" class="btn btn-secondary btn-lg">
                        <i class="fa-solid fa-arrow-left me-1"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>