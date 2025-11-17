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
        .profile-pic {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            max-width: 100%;
            height: auto;
        }

        @media (max-width: 576px) {
            .profile-pic {
                width: 120px;
                height: 120px;
            }
            .card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow p-4">
            <!-- Botões para ligar e enviar mensagem -->

    <?php if (!empty($user['telefone'])): ?>
        <!-- Botão Ligar -->
        <a href="tel:<?= htmlspecialchars($user['telefone']) ?>" class="btn btn-success me-2">
            <i class="fa-solid fa-phone"></i> Entre em contacto
        </a>
       
    <?php endif; ?>
</div>

            <h2 class="mb-4 text-center text-primary">Perfil do Candidato</h2>
            <div class="text-center mb-4">
                <?php if (!empty($user['foto_perfil'])): ?>
                    <img src="uploads/<?= htmlspecialchars($user['foto_perfil']) ?>" alt="Foto de Perfil" class="profile-pic">
                <?php else: ?>
                    <img src="https://via.placeholder.com/150" class="profile-pic" alt="Sem foto">
                <?php endif; ?>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>Nome:</strong> <?= htmlspecialchars($user['nome_completo']) ?></li>
                <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></li>
                <li class="list-group-item"><strong>Telefone:</strong> <?= htmlspecialchars($user['telefone']) ?></li>
                <li class="list-group-item"><strong>Endereço:</strong> <?= htmlspecialchars($user['endereco']) ?></li>
                <li class="list-group-item"><strong>Idade:</strong> <?= htmlspecialchars($user['idade']) ?> anos</li>
                <li class="list-group-item"><strong>Posição:</strong> <?= htmlspecialchars($user['posicao']) ?></li>
                <li class="list-group-item"><strong>Clube Anterior:</strong> <?= htmlspecialchars($user['clube_anterior']) ?></li>
                <li class="list-group-item"><strong>Modalidade:</strong> <?= htmlspecialchars($user['modalidade']) ?></li>
                <li class="list-group-item"><strong>Pé Favorito:</strong> <?= htmlspecialchars($user['pe']) ?></li>
                <li class="list-group-item"><strong>Gênero:</strong> <?= htmlspecialchars($user['genero']) ?></li>
                <li class="list-group-item"><strong>Situação Atual:</strong> <?= htmlspecialchars($user['situacao_atual']) ?></li>
            </ul>
            <a href="javascript:history.back()" class="btn btn-secondary mt-4"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>