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

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $placa = $_POST['placa'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $color = $_POST['color'];
    $anio = $_POST['anio'];
    $numero_motor = $_POST['numero_motor'];
    $numero_vin = $_POST['numero_vin'];
    $combustible = $_POST['combustible'];



    try {
        $stmt = $conn->prepare("UPDATE vehiculos SET placa = :placa, marca = :marca, modelo = :modelo, color = :color, anio = :anio, numero_motor = :numero_motor, numero_vin = :numero_vin, combustible = :combustible WHERE id = :id");
        $stmt->execute([
            ':placa' => $placa,
            ':marca' => $marca,
            ':modelo' => $modelo,
            ':color' => $color,
            ':anio' => $anio,
            ':id' => $id,
            ':numero_motor' => $numero_motor,
            ':numero_vin' => $numero_vin,
            ':combustible' => $combustible,
            
        ]);
        header("Location: vehiculos.php");
        exit;
    } catch (PDOException $e) {
        die("Error al actualizar el vehículo: " . $e->getMessage());
    }
}
?>
