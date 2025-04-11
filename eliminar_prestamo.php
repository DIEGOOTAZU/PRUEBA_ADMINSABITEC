<?php
session_start();
require_once 'config/db.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Verificar si la solicitud es POST y contiene el ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        $stmt = $conn->prepare("DELETE FROM prestamos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Redirigir con un mensaje de éxito
        header("Location: administrar_prestamo.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        die("Error al eliminar el préstamo: " . $e->getMessage());
    }
} else {
    die("Acceso no autorizado.");
}
