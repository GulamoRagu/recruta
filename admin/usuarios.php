<?php
include "auth.php"; // verifica login do admin
include "../db.php";

// Buscar atletas
$atletas = $conn->query("SELECT * FROM usuarios WHERE tipo='cliente' ORDER BY criado_em DESC");

// Buscar recrutadores
$recrutadores = $conn->query("SELECT * FROM usuarios WHERE tipo='vendedor' ORDER BY criado_em DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Dashboard Admin</a>
    <div class="d-flex">
      <a href="dashboard.php" class="btn btn-light me-2">Voltar ao Dashboard</a>
      <a href="logout.php" class="btn btn-danger">Sair</a>
    </div>
  </div>
</nav>

<div class="container my-5">
    <h1 class="mb-4 text-center">Gerenciar Usuários</h1>

    <!-- Atletas -->
    <div class="card mb-5 shadow-sm">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0">Atletas Cadastrados</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Data Cadastro</th>
                            <th>Accao</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $atletas->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['nome_completo']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['telefone']) ?></td>
                            <td><?= $row['criado_em'] ?></td>
                            <td>
                                
                                
                                <a href="../perfil_atleta.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-lg">
                                    Ver Perfil
                                </a>
                            
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recrutadores -->
    <div class="card mb-5 shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h3 class="mb-0">Recrutadores Cadastrados</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Data Cadastro</th>
                            <th>Accao</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $recrutadores->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['nome_completo']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['telefone']) ?></td>
                            <td><?= $row['criado_em'] ?></td>
                            <td>
                                
                                
                                <a href="../perfil_recrutador.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-lg">
                                    Ver Perfil
                                </a>
                            
                            </td>
                        </tr>
                        <?php endwhile; ?>
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
