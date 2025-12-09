<?php
ob_start();
session_start();
require 'db.php';

// Verificação de Autenticação e Tipo
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$success_message = '';
$error_message = '';

// Processamento da Submissão do Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome_completo'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    // Converte idade para inteiro seguro
    $idade = intval($_POST['idade'] ?? 0); 
    $posicao = $_POST['posicao'] ?? '';

    // Validação básica (garante que idade é um número)
    if (empty($nome) || empty($email) || $idade <= 0) {
        $error_message = "Por favor, preencha todos os campos obrigatórios corretamente.";
    } else {
        // Atualização Segura (Prepared Statement)
        $stmt = $conn->prepare("UPDATE usuarios SET nome_completo=?, telefone=?, email=?, endereco=?, idade=?, posicao=? WHERE id=?");
        // Note o tipo de dados "i" (integer) para 'idade'
        if ($stmt) {
            $stmt->bind_param("ssssisi", $nome, $telefone, $email, $endereco, $idade, $posicao, $user_id);
            if ($stmt->execute()) {
                // Redireciona com feedback de sucesso
                header("Location: perfil_recrutador.php?edit=success");
                exit();
            } else {
                $error_message = "Erro ao salvar: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Erro interno na preparação da query.";
        }
    }
}

// Recuperação dos dados atuais do usuário (para preencher o formulário)
$query = $conn->prepare("SELECT username, email, nome_completo, telefone, endereco, idade, posicao FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Se o usuário não for encontrado, redireciona
if (!$user) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil | Recrutador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #007bff;
            --background: #f4f6f9;
        }
        body {
            background-color: var(--background);
            font-family: 'Poppins', sans-serif;
        }
        .edit-card {
            max-width: 600px;
            margin: 50px auto;
            background-color: #ffffff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .edit-card h2 {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 30px;
            text-align: center;
        }
        .form-label {
            font-weight: 600;
            color: #343a40;
            margin-bottom: 5px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #ced4da;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .input-group-text {
            background-color: #e9ecef;
            border-radius: 8px 0 0 8px;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-secondary {
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="edit-card">
        <h2><i class="fa-solid fa-pen-to-square me-2"></i> Editar Perfil do Recrutador</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            
            <div class="row g-3">
                
                <div class="col-md-12">
                    <label class="form-label">Nome Completo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                        <input type="text" name="nome_completo" class="form-control" value="<?= htmlspecialchars($user['nome_completo']) ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Telefone</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                        <input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($user['telefone']) ?>" required>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Endereço</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-location-dot"></i></span>
                        <input type="text" name="endereco" class="form-control" value="<?= htmlspecialchars($user['endereco']) ?>" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Idade</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-calendar-alt"></i></span>
                        <input type="number" name="idade" class="form-control" value="<?= htmlspecialchars($user['idade']) ?>" required min="18">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Cargo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-briefcase"></i></span>
                        <input type="text" name="posicao" class="form-control" value="<?= htmlspecialchars($user['posicao']) ?>" required>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-2 border-top text-center">
                <button type="submit" class="btn btn-success me-3">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Salvar Alterações
                </button>
                <a href="perfil_recrutador.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Cancelar
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>