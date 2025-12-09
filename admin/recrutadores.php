<?php
ob_start();
session_start();
// Assumindo que o arquivo db.php contém a conexão mysqli ($conn)
require '../db.php'; 

// --- Configuração e Captura de Dados ---

// 1. CAPTURA DO FILTRO DE ORDENAÇÃO
// Pega o valor 'ordem' da URL. Default é 'data' (por data de criação, DESC)
$ordem = $_GET['ordem'] ?? 'data'; 

// Define a cláusula ORDER BY com base no filtro
$order_by_sql = "ORDER BY u.criado_em DESC"; // Default: por data (mais recente)

if ($ordem === 'nome_asc') {
    // Ordenar por nome em ordem alfabética (A-Z)
    $order_by_sql = "ORDER BY u.nome_completo ASC";
} elseif ($ordem === 'nome_desc') {
     // Ordenar por nome em ordem alfabética reversa (Z-A)
    $order_by_sql = "ORDER BY u.nome_completo DESC";
}


// Total de recrutadores cadastrados
$totalRecrutadores = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo='vendedor'")
                            ->fetch_assoc()['total'];


// Buscar recrutadores com vagas alocadas
// DEPOIS - Adiciona a contagem de vagas
$recrutadores = $conn->query("
    SELECT 
        u.*, 
        COUNT(p.id) AS total_vagas,  /* <<< Adiciona a contagem de vagas */
        GROUP_CONCAT(p.nome SEPARATOR '||') AS vagas_nomes /* <<< Novo separador para facilitar o split no JS */
    FROM usuarios u
    LEFT JOIN produtos p ON p.recrutador_id = u.id
    WHERE u.tipo='vendedor'
    GROUP BY u.id
    {$order_by_sql}
");

// --- Início do HTML com novo design ---
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Recrutadores | Admin Recruta</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #007bff; /* Azul primário */
            --secondary-color: #6c757d; /* Cinza secundário */
            --light-bg: #f4f6f9; /* Fundo claro */
        }
        body {
            background: var(--light-bg);
            font-family: 'Inter', sans-serif;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        .card-custom {
            border-radius: 0.75rem; /* Cantos mais arredondados */
            border: none;
        }
        .btn-new {
            background-color: #28a745; /* Verde */
            border-color: #28a745;
            transition: all 0.3s ease;
        }
        .btn-new:hover {
             background-color: #218838;
             border-color: #1e7e34;
        }
        .table thead th {
            font-weight: 600;
            color: var(--secondary-color);
            border-bottom: 2px solid #dee2e6;
        }
        .table-responsive {
            border-radius: 0.75rem;
            overflow-x: auto;
        }
        .badge-vagas {
            background-color: #e9ecef;
            color: var(--primary-color);
            font-weight: 500;
            padding: 0.4em 0.6em;
            border-radius: 0.5rem;
            cursor: pointer; /* Adiciona cursor de clique para indicar modal */
        }
        /* Estilo para o box de resumo (Total de Recrutadores) */
        .summary-box {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .summary-box h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        .summary-box strong {
            font-size: 2rem;
            font-weight: 700;
        }
        .action-btns a {
            transition: transform 0.2s;
        }
        .action-btns a:hover {
            transform: translateY(-1px);
        }
    </style>

    
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fa-solid fa-user-tie me-2"></i> Dashboard Admin
        </a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-outline-light me-2"><i class="fa-solid fa-house me-1"></i> Dashboard</a>
            <a href="../login.php?logout=true" class="btn btn-danger"><i class="fa-solid fa-right-from-bracket me-1"></i> Sair</a>
        </div>
    </div>
</nav>

<div class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary mb-0">Gerir Recrutadores</h1>
        <a href="criar_recrutador.php" class="btn btn-new shadow-sm">
            <i class="fa-solid fa-user-plus me-1"></i> Cadastrar Novo
        </a>
    </div>
    
    <div class="summary-box">
        <div>
            <i class="fa-solid fa-users fa-2x opacity-75"></i>
        </div>
        <div class="text-end">
            <h3>Total de Recrutadores Ativos</h3>
            <strong><?= $totalRecrutadores ?></strong>
        </div>
    </div>

    <div class="card card-custom shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="ordem" class="form-label mb-0 text-muted">Ordernar Lista:</label>
                </div>
                <div class="col-md-3 col-lg-2">
                    <select class="form-select form-select-sm" id="ordem" name="ordem">
                        <option value="data" <?= $ordem === 'data' ? 'selected' : '' ?>>Mais Recentes</option>
                        <option value="nome_asc" <?= $ordem === 'nome_asc' ? 'selected' : '' ?>>Nome (A - Z)</option>
                        <option value="nome_desc" <?= $ordem === 'nome_desc' ? 'selected' : '' ?>>Nome (Z - A)</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-sort me-1"></i> Aplicar
                    </button>
                </div>
                <div class="col-auto">
                    <?php if ($ordem !== 'data'): ?>
                        <a href="?" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-rotate-left me-1"></i> Resetar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card card-custom shadow-sm">
        <div class="card-header bg-white p-4 d-flex justify-content-between align-items-center">
             <h5 class="mb-0 text-primary">Lista Completa de Recrutadores</h5>
             
              <div class="d-flex justify-content-end gap-3">
        <button id="btnExportPDF" class="btn btn-danger btn-lg shadow-sm">
            <i class="fa-solid fa-file-pdf me-1"></i> Exportar Dados (PDF)
        </button>
        <button id="btnExportExcel" class="btn btn-success btn-lg shadow-sm">
            <i class="fa-solid fa-file-excel me-1"></i> Exportar Dados (Excel)
        </button>
    </div>
             </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">ID</th>
                            <th scope="col">Nome</th>
                            <th scope="col">Email</th>
                            <th scope="col">Telefone</th>
                            <th scope="col">Vagas Designadas</th>
                            <th scope="col">Data Cadastro</th>
                            <th scope="col" class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $recrutadores->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $row['id'] ?></td>
                            <td class="text-nowrap fw-bold"><?= htmlspecialchars($row['nome_completo']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['telefone']) ?></td>
                            
                            <td class="text-center">
                                <?php if ($row['total_vagas'] > 0): ?>
                                    <a href="#" class="badge badge-vagas" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#vagasModal"
                                       data-recrutador-nome="<?= htmlspecialchars($row['nome_completo']) ?>"
                                       data-vagas="<?= htmlspecialchars($row['vagas_nomes']) ?>"
                                       >
                                        <strong><?= $row['total_vagas'] ?></strong> Vaga(s)
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Nenhuma (0)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap"><?= date('d/m/Y', strtotime($row['criado_em'])) ?></td>
                            <td class="action-btns text-center text-nowrap">
                                <a href="./editar_recrutador.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm" title="Editar">
                                    <i class="fa-solid fa-pencil"></i>
                                </a>
                                <a href="./perfil_recrutador.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm text-white" title="Ver Perfil">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="./perfil_recrutador.php?id=<?= $row['id'] ?>&acao=apagar" class="btn btn-danger btn-sm" title="Apagar" 
                                   onclick="return confirm('ATENÇÃO: Você realmente deseja apagar o recrutador <?= htmlspecialchars($row['nome_completo']) ?>?');">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($recrutadores->num_rows == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-circle-info me-2"></i> Não foram encontrados recrutadores cadastrados com o filtro atual.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="vagasModal" tabindex="-1" aria-labelledby="vagasModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="vagasModalLabel">Vagas Designadas para: <span id="recrutadorNome"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group" id="vagasLista">
                    </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

    
// Exportar PDF
document.getElementById('btnExportPDF').addEventListener('click', () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4'); 
    
    doc.setFontSize(18);
    doc.text("Relatório de Atletas (Admin)", 14, 16);
    
    doc.setFontSize(10);
    doc.text(getFiltrosText(), 14, 24);
    
    // Preparar dados da tabela: 8 colunas de dados + Ação
    const table = document.getElementById('tableAtletas');
    const numCols = 8; // ID, Nome, Idade, Email, Telefone, Posição, Candidaturas, Ação (8 total)
    
    const headers = Array.from(table.querySelectorAll('thead th')).slice(0, numCols).map(th => th.innerText); 
    
    const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr => 
        // Pegar apenas as 8 primeiras TDs (o campo de ação é o 8º)
        Array.from(tr.querySelectorAll('td')).slice(0, numCols).map(td => td.innerText)
    ).filter(row => row.length > 1); // Remover a linha de "Nenhum atleta encontrado"

    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 30,
        headStyles: { fillColor: [40, 167, 69] },
        styles: { fontSize: 8 },
        columnStyles: {
            // A coluna de Candidaturas é a 7ª coluna (índice 6)
            '6': { cellWidth: 30 }, 
        }
    });

    doc.save('relatorio_atletas.pdf');
});

document.addEventListener('DOMContentLoaded', function () {
    const vagasModal = document.getElementById('vagasModal');
    
    vagasModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; 
        const recrutadorNome = button.getAttribute('data-recrutador-nome');
        const vagasString = button.getAttribute('data-vagas'); // Vagas separadas por '||'

        const modalTitle = vagasModal.querySelector('#recrutadorNome');
        const vagasListElement = vagasModal.querySelector('#vagasLista');
        
        vagasListElement.innerHTML = ''; // Limpa a lista anterior
        
        modalTitle.textContent = recrutadorNome;

        if (vagasString) {
            const vagasArray = vagasString.split('||'); 
            
            vagasArray.forEach(vaga => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.textContent = vaga.trim();
                vagasListElement.appendChild(li);
            });
        } else {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-info';
            li.textContent = 'Nenhuma vaga designada.';
            vagasListElement.appendChild(li);
        }
    });
});
</script>

</body>
</html>