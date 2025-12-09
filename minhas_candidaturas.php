<?php
ob_start();
session_start();
require 'db.php';

// Verifica se o usuário está logado como cliente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$cliente_id = intval($_SESSION['user_id']);

// 1. Buscar o nome do usuário logado
$query_user = $conn->prepare("SELECT nome_completo FROM usuarios WHERE id = ?");
$query_user->bind_param("i", $cliente_id);
$query_user->execute();
$result_user = $query_user->get_result();
$user = $result_user->fetch_assoc();
$nome_usuario = $user['nome_completo'] ?? 'Atleta';
$query_user->close();

// 2. Buscar todas as candidaturas do atleta (com JOINs)
$query = $conn->prepare("
    SELECT 
        compras.id AS compra_id, 
        produtos.nome AS vaga, 
        produtos.descricao,  
        produtos.modalidade,
        produtos.genero_permitido, 
        recrutador.nome_completo AS recrutador_nome,
        compras.data_compra, 
        compras.status
    FROM compras 
    JOIN produtos 
        ON compras.produto_id = produtos.id 
    JOIN usuarios AS recrutador
        ON produtos.recrutador_id = recrutador.id
    WHERE compras.cliente_id = ?
    ORDER BY compras.data_compra DESC
");

$query->bind_param("i", $cliente_id);
$query->execute();
$result = $query->get_result();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Candidaturas | <?= htmlspecialchars($nome_usuario) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Estilos da Sidebar para Coerência */
        body {
            font-family: 'Poppins', sans-serif;
            background: #e9ecef; 
            display: flex;
            min-height: 100vh;
        }
        /* [Estilos da sidebar omitidos por brevidade] */
        .sidebar {
            min-width: 260px; max-width: 260px; background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding-top: 1.5rem; box-shadow: 6px 0 15px rgba(0,0,0,0.25);
        }
        .sidebar h4 { margin-bottom: 2rem; text-align: center; font-weight: 700; }
        .sidebar a { color: white; display: flex; align-items: center; padding: 15px 25px; text-decoration: none; font-weight: 500; border-radius: 10px; margin: 8px 15px; transition: all 0.3s ease; border-left: 5px solid transparent; }
        .sidebar a i { margin-right: 15px; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left: 5px solid #ffc107; }

        /* Conteúdo Principal e Responsividade */
        .content { flex-grow: 1; padding: 30px; margin-left: 0 !important; }
        @media (min-width: 992px) {
            .content { margin-left: 260px !important; }
            .sidebar { position: fixed; }
        }
        
        /* Tabela Moderna */
        .table-custom { border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .table-custom thead { background-color: #343a40; color: white; font-weight: 600; }
        .table-custom tbody tr:hover { background-color: #f0f2f5; }
        .status-badge-container { font-weight: 700; }
        .message-box {
            border-left: 4px solid #007bff;
            padding: 8px;
            background-color: #eaf6ff;
            border-radius: 5px;
            font-size: 0.9rem;
            cursor: pointer; /* Adicionado cursor pointer para indicar que é clicável */
            transition: background-color 0.2s;
        }
        .message-box:hover {
            background-color: #dbeeff; /* Cor mais clara ao passar o mouse */
        }
        .message-box small { display: block; margin-top: 5px; }
        .message-full-content {
            white-space: pre-wrap; /* Mantém quebras de linha no modal */
        }
    </style>
</head>
<body>

<div class="d-flex w-100">

    <div class="d-none d-lg-block sidebar text-white fixed-top">
        <h4><i class="fa-solid fa-medal me-2"></i> <?= htmlspecialchars($nome_usuario) ?></h4>
        <a href="dashboard_atleta.php"><i class="fa-solid fa-gauge-high"></i> Início</a>
        <a href="perfil_atleta.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
        <a href="ver_vagas.php"><i class="fa-solid fa-magnifying-glass"></i> Explorar Vagas</a>
        <a href="minhas_candidaturas.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Minhas Candidaturas</a>
        <a href="logout.php" class="text-warning" style="margin-top: auto; padding-bottom: 20px;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
    </div>

    <main class="content">
        <nav class="navbar navbar-dark bg-dark fixed-top d-lg-none mb-5">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <span class="navbar-brand">Candidaturas</span>
            </div>
        </nav>
        <div class="offcanvas offcanvas-start bg-dark d-lg-none" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
            <div class="offcanvas-header bg-dark text-white">
                <h5 class="offcanvas-title" id="offcanvasSidebarLabel"><i class="fa-solid fa-medal me-2"></i> <?= htmlspecialchars($nome_usuario) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body sidebar">
                <a href="dashboard_atleta.php"><i class="fa-solid fa-gauge-high"></i> Início</a>
                <a href="perfil_atleta.php"><i class="fa-solid fa-user"></i> Meu Perfil</a>
                <a href="ver_vagas.php"><i class="fa-solid fa-magnifying-glass"></i> Explorar Vagas</a>
                <a href="minhas_candidaturas.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Minhas Candidaturas</a>
                <a href="logout.php" class="text-warning"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
            </div>
        </div>
        
        <h1 class="mb-5 border-bottom pb-2 text-primary mt-4 mt-lg-0"><i class="fa-solid fa-clipboard-list me-2"></i> Minhas Candidaturas</h1>

        <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive table-custom">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">Vaga</th>
                        <th scope="col">Modalidade</th>
                        <th scope="col">Recrutador</th>
                        <th scope="col">Submissão</th>
                        <th scope="col" class="text-center">Status</th>
                        <th scope="col">Última Mensagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                            // 3. Busca e prepara dados da mensagem
                            $stmt_msg = $conn->prepare("SELECT mensagem, data_envio FROM mensagens_status WHERE compra_id = ? AND remetente='recrutador' ORDER BY data_envio DESC LIMIT 1");
                            $stmt_msg->bind_param("i", $row['compra_id']);
                            $stmt_msg->execute();
                            $res_msg = $stmt_msg->get_result();
                            $mensagem = $res_msg->fetch_assoc();
                            $stmt_msg->close();
                            
                            $status_class = match($row['status']) {
                                'aprovado' => 'success', 'rejeitado' => 'danger', default => 'warning',
                            };
                            $status_icon = match($row['status']) {
                                'aprovado' => 'fa-check-circle', 'rejeitado' => 'fa-times-circle', default => 'fa-clock',
                            };
                            $status_label = match($row['status']) {
                                'aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado', default => 'Pendente',
                            };
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['vaga']) ?></strong>
                                <p class="text-muted small mb-0"><?= htmlspecialchars(substr($row['descricao'], 0, 50)) . '...' ?></p>
                            </td>
                            <td><?= htmlspecialchars($row['modalidade']) ?></td>
                            <td><?= htmlspecialchars($row['recrutador_nome']) ?></td>
                            <td><?= date("d/m/Y", strtotime($row['data_compra'])) ?></td>
                            <td class="text-center status-badge-container">
                                <span class="badge bg-<?= $status_class ?>">
                                    <i class="fa-solid <?= $status_icon ?> me-1"></i> <?= $status_label ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($mensagem): 
                                    $data_formatada = date("d/m/Y H:i", strtotime($mensagem['data_envio']));
                                    // A mensagem COMPLETA é armazenada nos atributos data-*
                                ?>
                                    <div 
                                        class="message-box" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#messageModal" 
                                        data-message-recrutador="<?= htmlspecialchars($row['recrutador_nome']) ?>"
                                        data-message-vaga="<?= htmlspecialchars($row['vaga']) ?>"
                                        data-message-content="<?= htmlspecialchars($mensagem['mensagem']) ?>"
                                        data-message-date="<?= htmlspecialchars($data_formatada) ?>"
                                    >
                                        <i class="fa-solid fa-envelope me-1"></i>
                                        <?= nl2br(htmlspecialchars(substr($mensagem['mensagem'], 0, 40)) . (strlen($mensagem['mensagem']) > 40 ? '...' : '')) ?>
                                        <small class="text-muted"><i class="fa-solid fa-calendar-alt"></i> <?= date("d/m/Y", strtotime($mensagem['data_envio'])) ?></small>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Aguardando contato</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-info text-center mt-5" role="alert">
                <i class="fa-solid fa-info-circle me-2"></i> Você ainda não se candidatou a nenhuma vaga.
            </div>
        <?php endif; ?>
    </main>
</div>

<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="messageModalLabel"><i class="fa-solid fa-envelope-open-text me-2"></i> Mensagem do Recrutador</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Vaga:</strong> <span id="modal-vaga"></span></p>
        <p><strong>Recrutador:</strong> <span id="modal-recrutador"></span></p>
        <p><strong>Data de Envio:</strong> <span id="modal-date" class="text-muted"></span></p>
        <hr>
        <div class="p-3 border rounded bg-light">
            <h6 class="text-secondary">Conteúdo:</h6>
            <p id="modal-message-content" class="message-full-content"></p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var messageModal = document.getElementById('messageModal');
    if (messageModal) {
        messageModal.addEventListener('show.bs.modal', function (event) {
            // Elemento que disparou o modal (o 'message-box' clicado)
            var button = event.relatedTarget; 

            // Extrai as informações dos atributos data-*
            var recrutador = button.getAttribute('data-message-recrutador');
            var vaga = button.getAttribute('data-message-vaga');
            var content = button.getAttribute('data-message-content');
            var date = button.getAttribute('data-message-date');

            // Atualiza o conteúdo do modal
            var modalVaga = messageModal.querySelector('#modal-vaga');
            var modalRecrutador = messageModal.querySelector('#modal-recrutador');
            var modalContent = messageModal.querySelector('#modal-message-content');
            var modalDate = messageModal.querySelector('#modal-date');

            modalVaga.textContent = vaga;
            modalRecrutador.textContent = recrutador;
            modalContent.textContent = content; // Usamos textContent aqui para segurança e pre-wrap no CSS
            modalDate.textContent = date;
        });
    }
});
</script>

</body>
</html>