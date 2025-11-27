<?php
ob_start();
session_start();
require '../db.php';

// Buscar vagas
$vagas = $conn->query("SELECT * FROM produtos ORDER BY data_validade DESC");
if(!$vagas) die("Erro na consulta SQL: ".$conn->error);

// Contagem para gráficos
$ativas = $expiradas = $inativas = 0;
$hoje = date('Y-m-d H:i:s');

$vagasArray = [];
while($row = $vagas->fetch_assoc()) {
    $vagasArray[] = $row;

    if($row['data_validade'] >= $hoje) {
        $ativas++;
        $row['status'] = 'ativa';
    } else {
        $dias_passados = (strtotime($hoje) - strtotime($row['data_validade'])) / (60*60*24);
        if($dias_passados <= 5) {
            $expiradas++;
            $row['status'] = 'expirada';
        } else {
            $inativas++;
            $row['status'] = 'inativa';
        }
    }

    $vagasArrayStatus[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gerenciar Vagas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <h1 class="mb-4 text-center">Gerenciar Vagas</h1>

    <!-- Gráfico de Vagas -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white">Status das Vagas</div>
    <div class="card-body d-flex justify-content-center">
        <canvas id="graficoVagas" width="150" height="50"></canvas>
    </div>
</div>

    <!-- Tabela de Vagas -->
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h3 class="mb-0">Lista de Vagas</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            
                            <th>Modalidade</th>
                            
                            <th>Validade</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vagasArrayStatus as $row): ?>
                        <?php
                            // Definir cor da badge
                            if($row['status'] == 'ativa') $corStatus = 'success';
                            elseif($row['status'] == 'expirada') $corStatus = 'warning';
                            else $corStatus = 'secondary';
                        ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            
                            <td><?= htmlspecialchars($row['modalidade']) ?></td>
                            
                            <td><?= date('d/m/Y H:i', strtotime($row['data_validade'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $corStatus ?>"><?= ucfirst($row['status']) ?></span>
                            </td>
                            <td>
                                
                                <a href="excluir_vaga.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir esta vaga?')">Excluir</a>
                            </td>

                            <td>
                                
                                
                                <a href="../ver_vagas.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-lg">
                                    Ver Vaga
                                </a>
                            
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($vagasArrayStatus) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Nenhuma vaga encontrada</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const ctx = document.getElementById('graficoVagas').getContext('2d');
const graficoVagas = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Ativas', 'Expiradas', 'Inativas'],
        datasets: [{
            label: 'Quantidade de Vagas',
            data: [<?= $ativas ?>, <?= $expiradas ?>, <?= $inativas ?>],
            backgroundColor: ['#28a745','#ffc107','#6c757d']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        onClick: (e, elements) => {
            if(elements.length > 0){
                const index = elements[0].index;
                let url = '';
                if(index === 0) url = 'vagas_status.php?status=ativa';
                else if(index === 1) url = 'vagas_status.php?status=expirada';
                else if(index === 2) url = 'vagas_status.php?status=inativa';
                if(url) window.location.href = url;
            }
        }
    }
});
</script>

</body>
</html>
