<?php
ob_start();
session_start();
require '../db.php';

// Inicializar filtros
$filtro_idade_inicio = isset($_GET['idade_inicio']) ? intval($_GET['idade_inicio']) : '';
$filtro_idade_fim = isset($_GET['idade_fim']) ? intval($_GET['idade_fim']) : '';
$filtro_genero = isset($_GET['genero']) ? $_GET['genero'] : '';
$filtro_candidatura = isset($_GET['candidatura']) ? $_GET['candidatura'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';

// Construir query dinâmica
$sql = "
    SELECT u.*, 
           GROUP_CONCAT(p.nome SEPARATOR ', ') AS vagas_candidatadas,
           GROUP_CONCAT(c.status SEPARATOR ', ') AS status_candidaturas
    FROM usuarios u
    LEFT JOIN compras c ON c.cliente_id = u.id
    LEFT JOIN produtos p ON p.id = c.produto_id
    WHERE u.tipo='cliente'
";

if($filtro_idade_inicio != '' && $filtro_idade_fim != '') {
    $sql .= " AND u.idade BETWEEN ".$filtro_idade_inicio." AND ".$filtro_idade_fim;
} elseif($filtro_idade_inicio != '') {
    $sql .= " AND u.idade >= ".$filtro_idade_inicio;
} elseif($filtro_idade_fim != '') {
    $sql .= " AND u.idade <= ".$filtro_idade_fim;
}

if($filtro_genero != '') {
    $sql .= " AND u.genero = '".$conn->real_escape_string($filtro_genero)."'";
}

if($filtro_candidatura == 'sim') {
    $sql .= " AND c.id IS NOT NULL";
} elseif($filtro_candidatura == 'nao') {
    $sql .= " AND c.id IS NULL";
}

if($filtro_status != '') {
    $sql .= " AND c.status = '".$conn->real_escape_string($filtro_status)."'";
}

$sql .= " GROUP BY u.id ORDER BY u.criado_em DESC";

$atletas = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários</title>
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
    <h1 class="mb-4 text-center">Atletas</h1>

    <!-- FORMULÁRIO DE FILTROS -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Filtrar Atletas</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label>Idade Início</label>
                    <input type="number" name="idade_inicio" class="form-control" value="<?= htmlspecialchars($filtro_idade_inicio) ?>">
                </div>
                <div class="col-md-2">
                    <label>Idade Fim</label>
                    <input type="number" name="idade_fim" class="form-control" value="<?= htmlspecialchars($filtro_idade_fim) ?>">
                </div>
                <div class="col-md-2">
                    <label>Género</label>
                    <select name="genero" class="form-control">
                        <option value="">Todos</option>
                        <option value="Masculino" <?= $filtro_genero=='Masculino'?'selected':'' ?>>Masculino</option>
                        <option value="Feminino" <?= $filtro_genero=='Feminino'?'selected':'' ?>>Feminino</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Candidatura</label>
                    <select name="candidatura" class="form-control">
                        <option value="">Todos</option>
                        <option value="sim" <?= $filtro_candidatura=='sim'?'selected':'' ?>>Com Candidatura</option>
                        <option value="nao" <?= $filtro_candidatura=='nao'?'selected':'' ?>>Sem Candidatura</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status Candidatura</label>
                    <select name="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="Pendente" <?= $filtro_status=='Pendente'?'selected':'' ?>>Pendente</option>
                        <option value="Aceita" <?= $filtro_status=='Aceita'?'selected':'' ?>>Aceita</option>
                        <option value="Rejeitada" <?= $filtro_status=='Rejeitada'?'selected':'' ?>>Rejeitada</option>
                    </select>
                </div>
                <div class="col-12 text-end mt-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="usuarios.php" class="btn btn-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABELA DE ATLETAS -->
    <div class="card mb-5 shadow-sm">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0">Atletas Cadastrados</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">

            <?php
// Totais por gênero
$total_masculino = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo='cliente' AND genero='Masculino'")->fetch_assoc()['total'];
$total_feminino = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo='cliente' AND genero='Feminino'")->fetch_assoc()['total'];

// Totais de candidaturas
$total_candidaturas = $conn->query("SELECT COUNT(*) AS total FROM compras")->fetch_assoc()['total'];
$total_com_candidatura = $conn->query("SELECT COUNT(DISTINCT cliente_id) AS total FROM compras")->fetch_assoc()['total'];
$total_sem_candidatura = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo='cliente' AND id NOT IN (SELECT DISTINCT cliente_id FROM compras)")->fetch_assoc()['total'];

// Totais por status
$total_pendente = $conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='Pendente'")->fetch_assoc()['total'];
$total_aprovado = $conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='Aceita'")->fetch_assoc()['total'];
$total_rejeitado = $conn->query("SELECT COUNT(*) AS total FROM compras WHERE status='Rejeitada'")->fetch_assoc()['total'];
$total_geral = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo='cliente'")->fetch_assoc()['total'];
?>

<div class="mb-3">
 <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">

<!-- Card Gênero -->
<div class="card shadow-sm p-2 text-center" style="min-width: 220px;">
    <div class="card-body p-2">
        <h6 class="card-title text-muted mb-2">GÊNERO</h6>
        <div class="d-flex justify-content-between">
            <span class="badge bg-secondary rounded-pill px-2 py-1">TOTAL=<?= $total_geral ?></span>
            <span class="badge bg-primary rounded-pill px-2 py-1">MASC=<?= $total_masculino ?></span>
            <span class="badge bg-danger rounded-pill px-2 py-1">FEM=<?= $total_feminino ?></span>
        </div>
    </div>
</div>

<!-- Card Candidaturas -->
<div class="card shadow-sm p-2 text-center" style="min-width: 220px;">
    <div class="card-body p-2">
        <h6 class="card-title text-muted mb-2">CANDIDATURAS</h6>
        <div class="d-flex justify-content-between">
            <span class="badge bg-info rounded-pill px-2 py-1">TOTAL=<?= $total_candidaturas ?></span>
            <span class="badge bg-success rounded-pill px-2 py-1">COM=<?= $total_com_candidatura ?></span>
            <span class="badge bg-warning text-dark rounded-pill px-2 py-1">SEM=<?= $total_sem_candidatura ?></span>
        </div>
    </div>
</div>

<!-- Card Status -->
<div class="card shadow-sm p-2 text-center" style="min-width: 220px;">
    <div class="card-body p-2">
        <h6 class="card-title text-muted mb-2">STATUS</h6>
        <div class="d-flex justify-content-between">
            <span class="badge bg-secondary rounded-pill px-2 py-1">TODOS=<?= $total_candidaturas ?></span>
            <span class="badge bg-primary rounded-pill px-2 py-1">PEND=<?= $total_pendente ?></span>
            <span class="badge bg-success rounded-pill px-2 py-1">APROV=<?= $total_aprovado ?></span>
            <span class="badge bg-danger rounded-pill px-2 py-1">REJ=<?= $total_rejeitado ?></span>
        </div>
    </div>
</div>

</div>

<style>
.card-title {
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.badge {
    font-size: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
</style>

<div class="mb-3 text-end">
    <button id="btnExportPDF" class="btn btn-danger me-2">📄 Exportar PDF</button>
    <button id="btnExportExcel" class="btn btn-success">📊 Exportar Excel</button>
</div>

<!-- Scripts de Export -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
// Função para gerar texto dos filtros
function getFiltrosText() {
    const idadeInicio = '<?= htmlspecialchars($filtro_idade_inicio) ?>';
    const idadeFim = '<?= htmlspecialchars($filtro_idade_fim) ?>';
    const genero = '<?= htmlspecialchars($filtro_genero) ?>';
    const candidatura = '<?= htmlspecialchars($filtro_candidatura) ?>';
    const status = '<?= htmlspecialchars($filtro_status) ?>';

    let filtros = [];
    if(idadeInicio || idadeFim) filtros.push(`Idade: ${idadeInicio || '-'} até ${idadeFim || '-'}`);
    if(genero) filtros.push(`Gênero: ${genero}`);
    if(candidatura) filtros.push(`Candidatura: ${candidatura}`);
    if(status) filtros.push(`Status: ${status}`);
    
    return filtros.length ? filtros.join(' | ') : 'Todos os registros';
}

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
// Função para gerar texto dos filtros
function getFiltrosText() {
    const idadeInicio = '<?= htmlspecialchars($filtro_idade_inicio) ?>';
    const idadeFim = '<?= htmlspecialchars($filtro_idade_fim) ?>';
    const genero = '<?= htmlspecialchars($filtro_genero) ?>';
    const candidatura = '<?= htmlspecialchars($filtro_candidatura) ?>';
    const status = '<?= htmlspecialchars($filtro_status) ?>';

    let filtros = [];
    if(idadeInicio || idadeFim) filtros.push(`Idade: ${idadeInicio || '-'} até ${idadeFim || '-'}`);
    if(genero) filtros.push(`Gênero: ${genero}`);
    if(candidatura) filtros.push(`Candidatura: ${candidatura}`);
    if(status) filtros.push(`Status: ${status}`);
    
    return filtros.length ? filtros.join(' | ') : 'Todos os registros';
}

// Exportar PDF
document.getElementById('btnExportPDF').addEventListener('click', () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    
    doc.setFontSize(16);
    doc.text("Relatório de Atletas", 14, 16);
    
    doc.setFontSize(11);
    doc.text(getFiltrosText(), 14, 24);
    
    // Preparar dados da tabela
    const table = document.getElementById('tableAtletas');
    const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr => 
        Array.from(tr.querySelectorAll('td')).map(td => td.innerText)
    );
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText);

    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 30,
        headStyles: { fillColor: [40, 167, 69] },
        styles: { fontSize: 9 }
    });

    doc.save('relatorio_atletas.pdf');
});

// Exportar Excel
document.getElementById('btnExportExcel').addEventListener('click', () => {
    const table = document.getElementById('tableAtletas');
    const wb = XLSX.utils.book_new();

    // Capturar filtros
    const filtrosText = getFiltrosText();

    // Capturar cabeçalho
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText);

    // Capturar linhas
    const data = Array.from(table.querySelectorAll('tbody tr')).map(tr => 
        Array.from(tr.querySelectorAll('td')).map(td => td.innerText)
    );

    // Inserir filtros como primeira linha
    data.unshift([filtrosText]);
    // Inserir cabeçalho na linha 2
    data.splice(1, 0, headers);

    const ws = XLSX.utils.aoa_to_sheet(data);
    XLSX.utils.book_append_sheet(wb, ws, "Atletas");
    XLSX.writeFile(wb, 'relatorio_atletas.xlsx');
});
</script>




               <table id="tableAtletas" class="table table-hover mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Idade</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Endereço</th>
                            <th>Data Cadastro</th>
                            <th>Vagas Candidatadas</th>
                            <th>Status Candidaturas</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
<?php while($row = $atletas->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['nome_completo']) ?></td>
    <td><?= htmlspecialchars($row['idade']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= htmlspecialchars($row['telefone']) ?></td>
    <td><?= htmlspecialchars($row['endereco']) ?></td>
    <td><?= $row['criado_em'] ?></td>
    <td><?= $row['vagas_candidatadas'] ? 'Sim' : 'Não' ?></td>
    <td><?= $row['status_candidaturas'] ? htmlspecialchars($row['status_candidaturas']) : 'Nenhuma' ?></td>
    <td>
        <a href="../perfil_atleta.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Ver Perfil</a>
        <a href="../perfil_atleta.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Apagar</a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>

                </table>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
