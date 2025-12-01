<?php
ob_start();
session_start();
require '../db.php';

// Buscar recrutadores com vagas alocadas
$recrutadores = $conn->query("
    SELECT u.*, GROUP_CONCAT(p.nome SEPARATOR ', ') AS vagas
    FROM usuarios u
    LEFT JOIN produtos p ON p.recrutador_id = u.id
    WHERE u.tipo='vendedor'
    GROUP BY u.id
    ORDER BY u.criado_em DESC
");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Recrutadores</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            background: #f4f6f9;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        .card-header h3 {
            margin: 0;
        }
        .btn-new {
            margin-bottom: 20px;
        }
        table.table-hover tbody tr:hover {
            background-color: #f1f3f6;
        }
        .action-btns a {
            margin-right: 5px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Dashboard Admin</a>
    <div class="d-flex">
      <a href="dashboard.php" class="btn btn-light me-2"><i class="fa-solid fa-house"></i> Dashboard</a>
      <a href="../login.php" class="btn btn-danger"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
    </div>
  </div>
</nav>

<div class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary">Recrutadores Cadastrados</h1>
        <a href="criar_recrutador.php" class="btn btn-success btn-new">
            <i class="fa-solid fa-plus"></i> Cadastrar Novo Recrutador
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Responsavel pela Vaga</th>
                            <th>Data Cadastro</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $recrutadores->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['nome_completo']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['telefone']) ?></td>
                            <td><?= htmlspecialchars($row['vagas'] ?? '-') ?></td>
                            <td><?= $row['criado_em'] ?></td>
                            <td class="action-btns">
                                <a href="./perfil_recrutador.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                </a>
                                <a href="./perfil_recrutador.php?id=<?= $row['id'] ?>&acao=apagar" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja apagar este recrutador?');">
                                    <i class="fa-solid fa-trash"></i> Apagar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($recrutadores->num_rows == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">Nenhum recrutador cadastrado.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
