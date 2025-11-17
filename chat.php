<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$destinatario_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar mensagens entre os usuários
$result = $conn->query("SELECT * FROM mensagens 
                        WHERE (remetente_id = $user_id AND destinatario_id = $destinatario_id)
                        OR (remetente_id = $destinatario_id AND destinatario_id = $user_id)
                        ORDER BY data_envio ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mensagem'])) {
    $mensagem = $conn->real_escape_string($_POST['mensagem']);
    $conn->query("INSERT INTO mensagens (remetente_id, destinatario_id, mensagem) 
                  VALUES ($user_id, $destinatario_id, '$mensagem')");
    header("Location: chat.php?id=$destinatario_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Chat</h2>
        <div class="border p-3" style="height: 300px; overflow-y: scroll;">
            <?php while ($row = $result->fetch_assoc()): ?>
                <p><strong><?= $row['remetente_id'] == $user_id ? "Você" : "Vendedor" ?>:</strong> <?= htmlspecialchars($row['mensagem']) ?></p>
            <?php endwhile; ?>
        </div>
        
        <form method="POST" class="mt-3">
            <textarea name="mensagem" class="form-control" rows="3" required></textarea>
            <button type="submit" class="btn btn-primary mt-2">Enviar</button>
        </form>

        <a href="dashboard_cliente.php" class="btn btn-secondary mt-3">Voltar</a>
    </div>
</body>
</html>
