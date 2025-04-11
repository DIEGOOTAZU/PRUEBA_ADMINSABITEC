<?php
// Incluir la conexión a la base de datos
require_once 'config/db.php';

// Verificar si el parámetro 'id_cliente' está presente
if (!isset($_GET['id_cliente'])) {
    echo json_encode(['error' => 'El parámetro "id_cliente" es necesario.']);
    exit;
}

$id_cliente = $_GET['id_cliente'];

try {
    // Consulta para obtener las placas asociadas al cliente
    $stmt = $conn->prepare("
        SELECT placa 
        FROM gps 
        WHERE id_cliente = :id_cliente
    ");
    $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $stmt->execute();

    // Obtener los resultados
    $placas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retornar los resultados en formato JSON
    echo json_encode($placas);
} catch (PDOException $e) {
    // En caso de error, devolver un mensaje
    echo json_encode(['error' => 'Error al obtener las placas: ' . $e->getMessage()]);
}
?>
