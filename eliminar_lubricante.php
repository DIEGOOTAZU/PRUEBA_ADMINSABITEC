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

// Verificar que se recibió un ID válido
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Preparar la consulta para eliminar el lubricante
        $stmt = $conn->prepare("DELETE FROM lubricantes WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            header("Location: administrar_lubricante.php?mensaje=eliminado");

            exit;
        } else {
            header("Location: administrar_lubricante.php?error=no_se_pudo_eliminar");

            exit;
        }
    } catch (PDOException $e) {
        die("Error al eliminar: " . $e->getMessage());
    }
} else {
    // Si el ID no es válido, redirigir con error
    header("Location: administrar_lubricante.php?error=id_invalido");
    exit;
}
?>
