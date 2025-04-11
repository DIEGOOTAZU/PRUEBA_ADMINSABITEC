<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        $stmt = $conn->prepare("DELETE FROM gps WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: administrar_gps.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        die("Error al eliminar el GPS: " . $e->getMessage());
    }
} else {
    die("Acceso no autorizado.");
}
