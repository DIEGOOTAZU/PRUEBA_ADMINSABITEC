<?php
// Incluir la conexión a la base de datos
require_once 'config/db.php';

// Verificar si el ID fue enviado por POST
if (isset($_POST['id'])) {
    $id = $_POST['id'];  // Obtener el ID de la fila a eliminar

    // Intentar eliminar el pago de la base de datos
    try {
        // Asegurarse de que el ID recibido sea numérico
        if (!is_numeric($id)) {
            die('Error: ID no válido');
        }

        // Preparar la sentencia SQL para eliminar el pago
        $stmt = $conn->prepare("DELETE FROM detalle_pagos_prestamo WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Verificar si la eliminación fue exitosa
        if ($stmt->rowCount() > 0) {
            echo 'success'; // Responder con "success" si se eliminó
        } else {
            echo 'error: no se pudo eliminar el pago'; // Si no se eliminó
        }
    } catch (PDOException $e) {
        // Capturar cualquier error en la base de datos
        echo 'error: ' . $e->getMessage(); // Mostrar el mensaje de error
    }
} else {
    echo 'error: ID no especificado'; // Si no se envió el ID
}
?>
