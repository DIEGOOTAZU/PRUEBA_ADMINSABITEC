<?php
// Incluir la conexión a la base de datos
require_once 'config/db.php';

// Verificar si se envió el ID
if (!isset($_POST['id'])) {
    echo "error";
    exit;
}

$id = intval($_POST['id']);

try {
    // Preparar la consulta para eliminar el registro
    $stmt = $conn->prepare("DELETE FROM detalle_pagos_gps WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
} catch (PDOException $e) {
    echo "error";
}
?>