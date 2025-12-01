<?php
ob_start();
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}

$candidato_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT nome_completo, telefone, endereco, idade, posicao, foto_perfil, email ,clube_anterior, modalidade, pe, genero, situacao_atual FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $candidato_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Candidato não encontrado.";
    exit();
}

$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Perfil do Candidato</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Importante para responsividade -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> <!-- ícones -->
    <style>
    body {
        background: #eef2f7;
        font-family: "Inter", sans-serif;
    }

    .profile-pic {
        width: 160px;
        height: 160px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #ffffff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .card-custom {
        border-radius: 18px;
        padding: 30px;
        background: #ffffff;
        box-shadow: 0px 5px 18px rgba(0,0,0,0.10);
    }

    .info-label {
        font-weight: 600;
        color: #334155;
    }

    .info-value {
        font-size: 15px;
        color: #475569;
    }

    .info-box {
        padding: 15px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        transition: 0.2s ease;
    }

    .info-box:hover {
        background: #f1f5f9;
        transform: translateY(-2px);
    }

    .btn-contact {
        border-radius: 12px;
        font-weight: 600;
        box-shadow: 0 3px 10px rgba(0,0,0,0.12);
    }

    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>

</head>
<body class="bg-light">
    <div class="container mt-5">
<div class="card-custom">

    <!-- Nome -->
    <h2 class="mb-4 text-center text-primary fw-bold">
        <i class="fa-solid fa-user"></i> Perfil do Candidato
    </h2>

    <!-- Foto -->
    <div class="text-center mb-4">
        <?php if (!empty($user['foto_perfil'])): ?>
            <img src="uploads/<?= htmlspecialchars($user['foto_perfil']) ?>" class="profile-pic">
        <?php else: ?>
            <img src="https://via.placeholder.com/160" class="profile-pic" alt="Sem foto">
        <?php endif; ?>
    </div>

    <!-- Botões -->
    <div class="text-center mb-4">
        <?php if (!empty($user['telefone'])): ?>
            <a href="https://wa.me/<?= preg_replace('/\D/', '', $user['telefone']) ?>" 
               target="_blank" 
               class="btn btn-success btn-contact me-2">
                <i class="fa-brands fa-whatsapp"></i> WhatsApp
            </a>

            <a href="tel:<?= htmlspecialchars($user['telefone']) ?>" 
               class="btn btn-primary btn-contact">
                <i class="fa-solid fa-phone"></i> Ligar
            </a>
        <?php endif; ?>
    </div>

    <!-- Informações em Grade -->
    <div class="info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

        <div class="info-box">
            <span class="info-label">Nome:</span><br>
            <span class="info-value"><?= htmlspecialchars($user['nome_completo']) ?></span>
        </div>

        <div class="info-box">
            <span class="info-label">Email:</span><br>
            <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
        </div>

        <div class="info-box">
            <span class="info-label">Telefone:</span><br>
            <span class="info-value"><?= htmlspecialchars($user['telefone']) ?></span>
        </div>

        <div class="info-box">
            <span class="info-label">Endereço:</span><br>
            <span class="info-value"><?= htmlspecialchars($user['endereco']) ?></span>
        </div>

        <div class="info-box">
            <span class="info-label">Idade:</span><br>
            <span class="info-value"><?= htmlspecialchars($user['idade']) ?> anos</span>
        </div>

        <div class="info-box">
            <span class="info-label">Posição:</span><br>
            <span class="info-value"><?= htmlspecialchars($user['posicao']) ?></span>
        </div>

        <div class="info-box">
            <span class="info-label">Clube Anterior:</span><br>
            <span class="info-value"><?= htmlspecialchars($user['clube_anterior']) ?></span>
        </div>

        <div class="info-box">
            <span class="info-label">Modalidade:</span><br>
            <span class="info-value"><?= htmlspecialchars($user['modalidade']) ?></span>
        </div>

        <div class="info-box">
            <span class="info-label">Pé Preferido:</span><br>
            <span class="info-value"><?= htmlspecialchars($user['pe']) ?></span>
        </div>

        <div class="info-box">
            <span class="info-label">Gênero:</span><br>
            <span class="info-value"><?= htmlspecialchars($user['genero']) ?></span>
        </div>

        <div class="info-box">
            <span class="info-label">Situação Atual:</span><br>
            <span class="info-value"><?= htmlspecialchars($user['situacao_atual']) ?></span>
        </div>

    </div>

    <!-- Voltar -->
    <div class="text-center mt-4">
        <a href="javascript:history.back()" class="btn btn-secondary btn-contact">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
    </div>

</div>

    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>