<?php
ob_start();
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$cliente_id = $_SESSION['user_id'];
$user_id = intval($_SESSION['user_id']);
$query = $conn->prepare("SELECT nome_completo FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'Atleta';

$query = $conn->prepare("SELECT compras.id, produtos.nome AS produto, produtos.descricao, usuarios.nome_completo AS vendedor_nome, compras.data_compra, compras.status 
                         FROM compras 
                         JOIN produtos ON compras.produto_id = produtos.id 
                         JOIN usuarios ON produtos.vendedor_id = usuarios.id 
                         WHERE compras.cliente_id = ?
                         ORDER BY compras.data_compra DESC");

$query->bind_param("i", $cliente_id);
$query->execute();
$result = $query->get_result();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Candidaturas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #343a40;
            padding-top: 20px;
            overflow-y: auto;
        }

        .sidebar a {
            color: white;
            padding: 15px;
            display: block;
            text-decoration: none;
            font-size: 18px;
        }

        .sidebar a:hover {
            background-color: #495057;
        }

        .content {
            margin-left: 250px;
            padding: 30px 20px 20px 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .content {
                margin-left: 0;
                padding: 20px;
            }

            .sidebar a {
                font-size: 16px;
                padding: 12px;
            }

            h2.text-center {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <nav class="sidebar">
        <h4 class="text-white text-center"><?= htmlspecialchars($nome_usuario) ?></h4>
        <a href="dashboard_atleta.php"><i class="fa-solid fa-home"></i> Início</a>
        <a href="perfil_atleta.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
        <a href="ver_vagas.php"><i class="fa-solid fa-box"></i> Ver Vagas</a>
        <a href="suporte_atleta.php"><i class="fa-solid fa-headset"></i> Suporte</a>
        <a href="logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> Sair</a>
    </nav>

    <!-- Conteúdo -->
    <main class="content">
        <h2 class="text-center text-primary">Minhas Candidaturas</h2>
        <div class="table-responsive">
            <table class="table table-bordered mt-3">
                <thead class="table-dark">
                    <tr>
                        <th>Vaga</th>
                        <th>Descrição</th>
                        <th>Recrutador</th>
                        <th>Data da submissão</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['produto']) ?></td>
                            <td><?= htmlspecialchars($row['descricao']) ?></td>
                            <td><?= htmlspecialchars($row['vendedor_nome']) ?></td>
                            <td><?= date("d/m/Y H:i", strtotime($row['data_compra'])) ?></td>
                            <td>
                                <?php if ($row['status'] == 'pendente'): ?>
                                    <span class="badge bg-warning text-dark">Pendente</span>
                                <?php elseif ($row['status'] == 'aprovado'): ?>
                                    <span class="badge bg-success">Aprovado</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejeitado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
