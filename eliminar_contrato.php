<?php
// Iniciar sesión
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Incluir la conexión a la base de datos
require_once 'config/db.php';

// Verificar si se envió el ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        // Eliminar el contrato
        $stmt = $conn->prepare("DELETE FROM data_cobranzas WHERE id = ?");
        $stmt->execute([$id]);

        // Redirigir de nuevo a la página de administración
        header("Location: administrar_contratos.php?mensaje=eliminado");
        exit;
    } catch (PDOException $e) {
        die("Error al eliminar el contrato: " . $e->getMessage());
    }
} else {
    die("ID no especificado para eliminar el contrato.");
}
?>
