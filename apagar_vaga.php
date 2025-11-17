<?php
ob_start();
include 'db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'vendedor') {
    header("Location: login.php");
    exit();
}
$id = $_GET['id'];
$conn->query("DELETE FROM produtos WHERE id = $id");
header("Location: listar_vaga.php");
exit();
?>