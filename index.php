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
      min-height: 100vh;
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

  <!-- Banner -->
  <div class="banner">
    <div class="main-container">
      <h1>Bem-vindo ao Sistema</h1>
      <h2>Acesse sua conta ou crie uma nova!</h2>
      <div class="d-flex flex-column flex-md-row justify-content-center">
        <a href="login.php" class="btn btn-primary btn-custom">Login</a>
        <a href="register.php" class="btn btn-success btn-custom">Registrar</a>
      </div>
    </div>
  </div>

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