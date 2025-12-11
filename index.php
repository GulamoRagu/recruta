<?php
ob_start();
require 'db.php'; // Conexão com o banco de dados

// --- Configurações de Paginação ---
$vagas_por_pagina = 3;
// Captura o número da página atual. Se não houver, começa na página 1.
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
// Garante que a página não seja menor que 1
if ($pagina_atual < 1) {
    $pagina_atual = 1;
}

// Calcula o ponto de partida (OFFSET) para a consulta
$offset = ($pagina_atual - 1) * $vagas_por_pagina;

// ----------------------------------------------------------------
// 1. Consulta para a página atual
// ----------------------------------------------------------------
$sql = "SELECT v.id, v.nome, v.descricao, v.genero_permitido, v.preco, v.modalidade, v.posicao, v.data_validade
        FROM produtos v
        WHERE v.data_validade >= CURDATE()
        ORDER BY v.data_validade DESC
        LIMIT {$vagas_por_pagina} OFFSET {$offset}";

$result = $conn->query($sql);

// ----------------------------------------------------------------
// 2. Consulta para verificar se há mais vagas (Contagem Total)
// ----------------------------------------------------------------
$sql_total = "SELECT COUNT(id) AS total FROM produtos WHERE data_validade >= CURDATE()";
$result_total = $conn->query($sql_total);
$total_vagas = $result_total ? $result_total->fetch_assoc()['total'] : 0;

// Calcula o número total de páginas
$total_paginas = ceil($total_vagas / $vagas_por_pagina);

// Define a próxima página e se o botão "Próximo" deve aparecer
$proxima_pagina = $pagina_atual + 1;
$tem_proxima_pagina = $pagina_atual < $total_paginas;

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistema de Recrutamento Atletas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
    
    <style>
        /* Estilos Globais */
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 70px; /* Ajuste para a navbar fixa */
        }
        
        /* Banner - Hero Section */
        .banner {
            width: 100%;
            min-height: 55vh;
            background-image: url('foto1.jpg'); 
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .banner::after {
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

        .main-container {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 30px;
            animation: fadeIn 1.2s ease-in-out;
        }

        .main-container h1 {
            font-size: 3.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.8);
        }

        .main-container h2 {
            font-size: 1.6rem;
            color: #ffc107;
            margin-bottom: 40px;
            font-weight: 400;
        }

        /* Botões */
        .btn-custom {
            font-size: 1.1rem;
            padding: 12px 30px;
            border-radius: 50px;
            margin: 8px;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .btn-primary:hover { background-color: #0056b3; border-color: #0056b3; transform: scale(1.05); }

        .btn-success { background-color: #28a745; border-color: #28a745; }
        .btn-success:hover { background-color: #1e7e34; border-color: #1e7e34; transform: scale(1.05); }

        /* Estilo da Seção de Vagas */
        .vagas-section {
            background-color: #ffffff;
            padding: 60px 0;
        }
        .vagas-section h2 {
            color: #007bff;
            font-weight: 700;
            margin-bottom: 45px;
        }

        /* Card de Vaga */
        .card-vaga {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .card-vaga:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        .card-vaga .card-title {
            color: #343a40;
            font-weight: 600;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .card-vaga p {
            margin-bottom: 8px;
            font-size: 0.95rem;
            color: #555;
        }
        .card-vaga p strong {
            color: #333;
            font-weight: 500;
        }
        .card-vaga i {
            width: 20px;
            color: #007bff;
        }
        
        /* Seção Sobre */
        .about-section {
            background: linear-gradient(135deg, #e9ecef, #ced4da);
            padding: 60px 30px;
            text-align: center;
        }
        .about-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #343a40;
            margin-bottom: 25px;
        }
        .about-section p {
            max-width: 800px;
            margin: 0 auto;
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
        }

        /* Rodapé */
        .footer {
            background-color: #343a40;
            color: #f8f9fa;
            padding: 30px 0;
            font-size: 0.9rem;
        }
        .footer a {
            color: #ffc107;
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer a:hover {
            color: #fff;
        }
        
        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .main-container h1 { font-size: 2.5rem; }
            .main-container h2 { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow">
    <div class="container-fluid">
       <a class="navbar-brand" href="#">
    <img src="logo2.png" alt="Sistema AAM" height="35" class="me-2">
    <span style="color: red; font-weight: bold;">AAM</span>
</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="#inicio"><i class="fa-solid fa-home me-1"></i> INÍCIO</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#vagas"><i class="fa-solid fa-briefcase me-1"></i> VAGAS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#sobre"><i class="fa-solid fa-info-circle me-1"></i> SOBRE</a>
                </li>
                <li class="nav-item d-none d-lg-block ms-3">
                    <a href="login.php" class="btn btn-outline-primary btn-sm me-2">Login</a>
                </li>
                <li class="nav-item d-none d-lg-block">
                    <a href="register.php" class="btn btn-warning btn-sm">Registo</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<section class="banner" id="inicio">
    <div class="main-container">
        <h1><i class="fa-solid fa-running me-2"></i> Sua Jornada Desportiva Começa Aqui</h1>
        <h2>Conectando talentos à próxima oportunidade de carreira.</h2>
        <div class="d-flex flex-column flex-md-row justify-content-center">
            <a href="login.php" class="btn btn-primary btn-custom shadow-lg">
                <i class="fa-solid fa-sign-in-alt me-2"></i> Fazer Login
            </a>
            <a href="register.php" class="btn btn-success btn-custom shadow-lg">
                <i class="fa-solid fa-user-plus me-2"></i> Criar Conta (Atleta)
            </a>
        </div>
    </div>
</section>

<section class="vagas-section" id="vagas">
    <div class="container">
        <h2 class="text-center">Oportunidades em Destaque</h2>
        
        <?php if ($total_vagas > 0): ?>
            <p class="text-center text-muted mb-4 small">Mostrando **<?= $result->num_rows ?>** de **<?= $total_vagas ?>** vagas activas (Página <?= $pagina_atual ?> de <?= $total_paginas ?>)</p>
        <?php endif; ?>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <div class="col">
                    <div class="card card-vaga shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-center">
                                <?= htmlspecialchars($row['nome']) ?>
                            </h5>
                            
                            <p class="card-text text-muted small mb-3"><?= nl2br(htmlspecialchars(substr($row['descricao'], 0, 100))) . (strlen($row['descricao']) > 100 ? '...' : '') ?></p>
                            
                            <p><i class="fa-solid fa-trophy"></i> <strong>Modalidade:</strong> <?= htmlspecialchars($row['modalidade']) ?></p>
                            <p><i class="fa-solid fa-users"></i> <strong>Posição:</strong> <?= htmlspecialchars($row['posicao']) ?></p>
                            <p><i class="fa-solid fa-venus-mars"></i> <strong>Gênero:</strong> <?= htmlspecialchars($row['genero_permitido']) ?></p>
                            <p><i class="fa-solid fa-user-tag"></i> <strong>Idade Máx:</strong> <?= htmlspecialchars($row['preco']) ?></p> 
                            <p class="mb-4 text-danger"><i class="fa-solid fa-hourglass-end"></i> <strong>Validade:</strong> <?= (new DateTime($row['data_validade']))->format('d/m/Y') ?></p>

                            <div class="mt-auto text-center">
                                <a href="login.php?id=<?= $row['id'] ?>" class="btn btn-primary w-100 mt-2">
                                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Concorrer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> No momento, **não há vagas ativas** disponíveis.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex justify-content-center mt-5">
            <?php if ($pagina_atual > 1): ?>
                <a href="?pagina=<?= $pagina_atual - 1 ?>#vagas" class="btn btn-outline-secondary me-3">
                    <i class="fa-solid fa-chevron-left me-1"></i> Anterior
                </a>
            <?php endif; ?>

            <?php if ($tem_proxima_pagina): ?>
                <a href="?pagina=<?= $proxima_pagina ?>#vagas" class="btn btn-primary">
                    Próximas Vagas <i class="fa-solid fa-chevron-right ms-1"></i>
                </a>
            <?php endif; ?>

            <?php if ($pagina_atual > 1 || $tem_proxima_pagina): ?>
                <a href="?pagina=1#vagas" class="btn btn-outline-info ms-3">
                    <i class="fa-solid fa-list-ul me-1"></i> Ver Todas (Início)
                </a>
            <?php endif; ?>

        </div>
    </div>
</section>

<section class="about-section" id="sobre">
    <div class="container">
        <h2><i class="fa-solid fa-globe me-2"></i> Sobre a AAM</h2>
        <p>
            Somos uma plataforma inovadora dedicada a conectar **atletas** e **recrutadores**,
            oferecendo uma solução eficaz para o processo de recrutamento desportivo.
            Nossa missão é proporcionar uma experiência única e intuitiva, ajudando atletas
            a encontrar oportunidades e recrutadores a descobrir os melhores talentos de forma rápida e transparente.
        </p>
    </div>
</section>

<footer class="footer">
    <div class="container text-center">
        <p class="mb-1">&copy; <?= date("Y") ?> AAM - Sistema de Recrutamento Despostivo. Todos os direitos reservados.</p>
        <p class="mb-0">
            <a href="#">Política de Privacidade</a> | 
            <a href="#">Termos de Uso</a>
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>