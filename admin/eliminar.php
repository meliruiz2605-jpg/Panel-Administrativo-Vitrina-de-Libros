<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM libros WHERE id = ?");
$stmt->execute([$id]);
$libro = $stmt->fetch();

if ($libro) {
    // Eliminar imagen del servidor
    $img = '../images/libros/' . $libro['portada'];
    if (file_exists($img)) unlink($img);
    // Eliminar de la base de datos
    $stmt = $conn->prepare("DELETE FROM libros WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: panel.php?msg=eliminado');
exit;
?>
