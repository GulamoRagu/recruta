
<?php
include "auth.php";
include "../db.php";

// Pegar status da query string, se houver
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';

// Montar SQL
$sql = "
    SELECT u.*, c.status, p.nome
    FROM usuarios u
    INNER JOIN compras c ON u.id = c.cliente_id
    INNER JOIN produtos p ON c.produto_id = p.id
    WHERE u.tipo='cliente'
";

if ($filtro_status && in_array($filtro_status, ['pendente','aprovado','rejeitado'])) {
    $sql .= " AND c.status='{$filtro_status}'";
}

$sql .= " ORDER BY c.data_compra DESC";

$candidatos = $conn->query($sql);
if (!$candidatos) die("Erro na consulta SQL: " . $conn->error);

// Contar status para gráficos
$resultAll = $conn->query("
    SELECT c.status
    FROM usuarios u
    INNER JOIN compras c ON u.id = c.cliente_id
    WHERE u.tipo='cliente'
");
$statusCounts = ['pendente'=>0,'aprovado'=>0,'rejeitado'=>0];
while($row = $resultAll->fetch_assoc()){
    if(isset($statusCounts[$row['status']])) $statusCounts[$row['status']]++;
}


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Candidatos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">Dashboard Admin</a>
    <div class="d-flex">
      <a href="dashboard.php" class="btn btn-light me-2">Voltar ao Dashboard</a>
      <a href="logout.php" class="btn btn-danger">Sair</a>
    </div>
  </div>
</nav>

<div class="container my-5">
    <h1 class="mb-4 text-center">Gerenciar Candidatos</h1>

    <!-- Gráficos de Status -->
    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-header bg-warning text-dark">Pendentes</div>
                <div class="card-body">
                    <canvas id="graficoPendentes"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-header bg-success text-white">Aprovados</div>
                <div class="card-body">
                    <canvas id="graficoAprovados"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-header bg-danger text-white">Rejeitados</div>
                <div class="card-body">
                    <canvas id="graficoRejeitados"></canvas>
                </div>
            </div>
        </div>
    </div>

    <?php if($filtro_status): ?>
<div class="alert alert-info">
    Mostrando candidatos com status: <strong><?= ucfirst($filtro_status) ?></strong>
    <a href="candidatos.php" class="btn btn-sm btn-secondary ms-2">Ver Todos</a>
</div>
<?php endif; ?>


    <!-- Tabela de Candidatos -->
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h3 class="mb-0">Lista de Candidatos</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Vaga</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($candidatos as $row): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td>
                                <?php if(!empty($row['foto_perfil'])): ?>
                                    <img src="../uploads/<?= $row['foto_perfil'] ?>" alt="Foto de <?= htmlspecialchars($row['nome_completo']) ?>" class="img-thumbnail" style="width:50px;height:50px;">
                                <?php else: ?>
                                    <span class="text-muted">Sem foto</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['nome_completo']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            <td>
                                <?php 
                                    if($row['status'] == 'pendente') echo '<span class="badge bg-warning text-dark">Pendente</span>';
                                    elseif($row['status'] == 'aprovado') echo '<span class="badge bg-success">Aprovado</span>';
                                    elseif($row['status'] == 'rejeitado') echo '<span class="badge bg-danger">Rejeitado</span>';
                                    else echo '<span class="badge bg-secondary">Desconhecido</span>';
                                ?>
                            </td>
                            <td>
                                <a href="../ver_candidato.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-lg">
                                    Ver Candidato
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script>

function graficoClique(status){
    window.location.href = '?status=' + status;
}

const ctxPendentes = document.getElementById('graficoPendentes').getContext('2d');
new Chart(ctxPendentes, {
    type: 'doughnut',
    data: {
        labels: ['Pendentes','Outros'],
        datasets:[{data:[<?= $statusCounts['pendente'] ?>, <?= $statusCounts['aprovado'] + $statusCounts['rejeitado'] ?>],
                    backgroundColor:['#ffc107','#e9ecef'] }]
    },
    options:{
        onClick: ()=> graficoClique('pendente'),
        responsive:true, plugins:{legend:{display:false}}
    }
});

const ctxAprovados = document.getElementById('graficoAprovados').getContext('2d');
new Chart(ctxAprovados, {
    type:'doughnut',
    data:{ labels:['Aprovados','Outros'], datasets:[{data:[<?= $statusCounts['aprovado'] ?>, <?= $statusCounts['pendente'] + $statusCounts['rejeitado'] ?>], backgroundColor:['#28a745','#e9ecef']}]},
    options:{onClick:()=>graficoClique('aprovado'), responsive:true, plugins:{legend:{display:false}}}
});

const ctxRejeitados = document.getElementById('graficoRejeitados').getContext('2d');
new Chart(ctxRejeitados, {
    type:'doughnut',
    data:{ labels:['Rejeitados','Outros'], datasets:[{data:[<?= $statusCounts['rejeitado'] ?>, <?= $statusCounts['pendente'] + $statusCounts['aprovado'] ?>], backgroundColor:['#dc3545','#e9ecef']}]},
    options:{onClick:()=>graficoClique('rejeitado'), responsive:true, plugins:{legend:{display:false}}}
});
</script>


</body>
</html>
