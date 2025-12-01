<?php
ob_start();
session_start();
require '../db.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f0f2f5;
        }

        .card-dashboard {
            border-radius: 12px;
            transition: .3s;
            cursor: pointer;
        }

        .card-dashboard:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 22px rgba(0,0,0,0.15);
        }

        .icon-box {
            font-size: 45px;
            margin-bottom: 15px;
            color: #0d6efd;
        }

        .header-custom {
            background: #1e293b;
            padding: 15px 30px;
        }

        .header-custom h1 {
            color: white;
            font-size: 28px;
            margin: 0;
        }

        .header-custom .btn-logout {
            border: 1px solid #f87171;
            color: #f87171;
        }

        .header-custom .btn-logout:hover {
            background: #f87171;
            color: #000;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="header-custom d-flex justify-content-between align-items-center">
    <h1>Dashboard do Administrador</h1>

    <div class="text-white">
        <span class="me-3">Bem-vindo, <?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
        <a href="../index.php" class="btn btn-logout">Sair</a>
    </div>
</header>

<!-- CONTEÚDO -->
<div class="container py-5">

    <div class="row g-4">

        <!-- Card Atletas -->
        <div class="col-md-4 col-lg-3">
            <a href="usuarios.php" class="text-decoration-none text-dark">
                <div class="card card-dashboard p-4 text-center">
                    <div class="icon-box">👤</div>
                    <h5 class="fw-bold">Atletas</h5>
                </div>
            </a>
        </div>

        <!-- Card Candidatos -->
        <div class="col-md-4 col-lg-3">
            <a href="candidatos.php" class="text-decoration-none text-dark">
                <div class="card card-dashboard p-4 text-center">
                    <div class="icon-box">📋</div>
                    <h5 class="fw-bold">Candidatos</h5>
                </div>
            </a>
        </div>

        <!-- Card Vagas -->
        <div class="col-md-4 col-lg-3">
            <a href="listar_vaga.php" class="text-decoration-none text-dark">
                <div class="card card-dashboard p-4 text-center">
                    <div class="icon-box">📝</div>
                    <h5 class="fw-bold">Vagas</h5>
                </div>
            </a>
        </div>

        <!-- Card Criar Recrutador -->
        <div class="col-md-4 col-lg-3">
            <a href="criar_recrutador.php" class="text-decoration-none text-dark">
                <div class="card card-dashboard p-4 text-center">
                    <div class="icon-box">➕</div>
                    <h5 class="fw-bold">Criar Recrutador</h5>
                </div>
            </a>
        </div>

        <!-- Card Recrutadores -->
        <div class="col-md-4 col-lg-3">
            <a href="recrutadores.php" class="text-decoration-none text-dark">
                <div class="card card-dashboard p-4 text-center">
                    <div class="icon-box">👥</div>
                    <h5 class="fw-bold">Recrutadores</h5>
                </div>
            </a>
        </div>

    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
