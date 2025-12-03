
<?php
ob_start();
require 'db.php';

// Buscar apenas vagas ativas (não expiradas)
$sql = "SELECT v.id, v.nome, v.descricao, v.genero_permitido, v.preco, v.modalidade, v.posicao, v.data_validade
        FROM produtos v
        WHERE v.data_validade >= CURDATE()
        ORDER BY v.data_validade DESC";

$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bem-vindo ao Sistema</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body, html {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
    }

    .banner {
      width: 100%;
      min-height: 50vh;
      background-image: url('foto1.jpg');
      background-size: cover;
      background-position: center;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
    }

    .banner::after {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5); /* escurece a imagem */
      z-index: 1;
    }

    .main-container {
      position: relative;
      z-index: 2;
      text-align: center;
      padding: 20px;
      animation: fadeIn 1s ease-in-out;
    }

    .main-container h1 {
      font-size: 3rem;
      font-weight: 600;
      color: #fff;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
    }

    .main-container h2 {
      font-size: 1.5rem;
      color: #fff;
      margin-bottom: 30px;
    }

    .btn-custom {
      font-size: 1rem;
      padding: 12px 25px;
      border-radius: 50px;
      margin: 5px;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #0056b3;
      transform: scale(1.05);
    }

    .btn-success:hover {
      background-color: #218838;
      transform: scale(1.05);
    }

    .about-section {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      padding: 50px 30px;
      border-radius: 15px;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
      animation: fadeIn 1s ease-in-out;
      max-width: 900px;
      margin: 50px auto;
      text-align: center;
    }

    .about-section h2 {
      font-size: 2rem;
      font-weight: 600;
      margin-bottom: 20px;
      color: #333;
    }

    .about-section p {
      font-size: 1.1rem;
      line-height: 1.7;
      color: #555;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @media (max-width: 768px) {
      .main-container h1 {
        font-size: 2.2rem;
      }

      .main-container h2 {
        font-size: 1.2rem;
      }

      .btn-custom {
        width: 100%;
        margin-bottom: 10px;
      }

      .about-section {
        padding: 30px 15px;
      }
    }
  </style>
</head>
<body>
 <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">
      <img src="logo2.png" alt="Sistema AAM" height="40">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

```
<div class="collapse navbar-collapse" id="navbarMenu">
  <ul class="navbar-nav ms-auto">
    <li class="nav-item">
      <a class="nav-link active" href="index.php">INÍCIO</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="index.php">VAGAS</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="index.php">PESQUISAR</a>
    </li>
  </ul>
</div>

  </div>
</nav>

<style>
  /* Evita que o conteúdo fique atrás da navbar fixa */
  body {
    padding-top: 70px; /* ajuste conforme a altura da navbar */
  }
</style>



  <!-- Banner -->
  <div class="banner">
    <div class="main-container">
      <h1>Sistema de Recrutamento de Atletas</h1>
      <h2>Acesse sua conta ou crie uma nova!</h2>
      <div class="d-flex flex-column flex-md-row justify-content-center">
        <a href="login.php" class="btn btn-primary btn-custom">Login</a>
        <a href="register.php" class="btn btn-success btn-custom">Registo</a>
      </div>
    </div>
  </div>

<!-- Seção de Vagas Disponíveis com fundo cinza elegante -->
<div class="container-fluid py-5" style="background-color: #f0f0f0;">
    <div class="container">
        <h2 class="text-center mb-5" style="color: #333; font-weight: 600;">Vagas Disponíveis</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php while($row = $result->fetch_assoc()): ?>
            <div class="col">
                <div class="card h-100 shadow-sm border-0 rounded-4">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-primary mb-3">
                            <i class="bi bi-briefcase-fill"></i> <?= htmlspecialchars($row['nome']) ?>
                        </h5>
                        <p class="card-text"><?= nl2br(htmlspecialchars($row['descricao'])) ?></p>
                        <p><i class="bi bi-person-fill"></i> <strong>Idade Máxima:</strong> <?= htmlspecialchars($row['preco']) ?></p>
                        <p><i class="bi bi-gender-ambiguous"></i> <strong>Gênero:</strong> <?= htmlspecialchars($row['genero_permitido']) ?></p>
                        <p><i class="bi bi-trophy-fill"></i> <strong>Modalidade:</strong> <?= htmlspecialchars($row['modalidade']) ?></p>
                        <p><i class="bi bi-geo-alt-fill"></i> <strong>Posição:</strong> <?= htmlspecialchars($row['posicao']) ?></p>
                        <p><i class="bi bi-calendar-check-fill"></i> <strong>Validade:</strong> <?= (new DateTime($row['data_validade']))->format('d/m/Y') ?></p>
                     <div class="mt-auto text-center">
    <a href="login.php?id=<?= $row['id'] ?>" class="btn btn-success btn-sm mt-2">
        <i class="bi bi-send-fill"></i> Faz login para concorrer
    </a>
</div>

                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<style>
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 25px rgba(0,0,0,0.15);
    }
    .card-title i {
        margin-right: 8px;
    }
    .card-text, p {
        color: #555;
    }
    .btn-success {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.2);
    }
</style>

</div>

<style>
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 25px rgba(0,0,0,0.15);
    }
    .card-title i {
        margin-right: 8px;
    }
    .card-text, p {
        color: #555;
    }
</style>


 <!-- Sobre a Empresa -->
  <div class="about-section">
    <h2>Sobre a AAM</h2>
    <p>
      Somos uma plataforma inovadora dedicada a conectar atletas e recrutadores,
      oferecendo uma solução eficaz para o processo de recrutamento esportivo.
      Nossa missão é proporcionar uma experiência única e intuitiva, ajudando atletas
      a encontrar oportunidades e recrutadores a descobrir os melhores talentos.
    </p>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>




