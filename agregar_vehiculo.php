<?php
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placa = trim($_POST['placa']);
    $marca = trim($_POST['marca']);
    $modelo = trim($_POST['modelo']);
    $color = trim($_POST['color']);
    $anio = trim($_POST['anio']);
    $numero_motor = trim($_POST['numero_motor']);
    $numero_vin = trim($_POST['numero_vin']);
    $combustible = trim($_POST['combustible']);

    try {
        // Verificar si la placa ya existe
        $stmt = $conn->prepare("SELECT COUNT(*) FROM vehiculos WHERE placa = :placa");
        $stmt->execute([':placa' => $placa]);
        $existe = $stmt->fetchColumn();

        if ($existe > 0) {
            echo json_encode(["status" => "error", "message" => "Error: La placa ya está registrada"]);
            exit;
        }

        // Insertar el nuevo vehículo si la placa no existe
        $stmt = $conn->prepare("
            INSERT INTO vehiculos (placa, marca, modelo, color, anio, numero_motor, numero_vin, combustible)
            VALUES (:placa, :marca, :modelo, :color, :anio, :numero_motor, :numero_vin, :combustible)
        ");

        $stmt->execute([
            ':placa' => $placa,
            ':marca' => $marca,
            ':modelo' => $modelo,
            ':color' => $color,
            ':anio' => $anio,
            ':numero_motor' => $numero_motor,
            ':numero_vin' => $numero_vin,
            ':combustible' => $combustible,
        ]);

        echo json_encode(["status" => "success", "message" => "Vehículo agregado con éxito"]);
        exit;
        
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error al agregar el vehículo: " . $e->getMessage()]);
        exit;
    }
}
?>
