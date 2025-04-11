<?php
require_once 'config/db.php';

$id_cliente = $_POST['id_cliente'] ?? null;
$placa = $_POST['placa'] ?? null;

$efectivo_banco = $_POST['efectivo_banco'] ?? [];
$fecha_pago = $_POST['fecha_pago'] ?? [];
$letra = $_POST['letra'] ?? [];
$importe = $_POST['importe'] ?? [];
$deuda_mora = $_POST['deuda_mora'] ?? [];
$monto_mora = $_POST['monto_mora'] ?? [];

if (!$id_cliente || !$placa) {
    die('Datos incompletos.');
}

try {
    // Buscar el ID del GPS
    $stmt = $conn->prepare("SELECT id FROM gps WHERE id_cliente = :id_cliente AND placa = :placa");
    $stmt->execute([':id_cliente' => $id_cliente, ':placa' => $placa]);
    $gps = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gps) {
        die('No se encontró el registro de GPS.');
    }

    $gps_id = $gps['id'];

    // Eliminar todos los pagos anteriores
    $deleteStmt = $conn->prepare("DELETE FROM detalle_pagos_gps WHERE gps_id = :gps_id");
    $deleteStmt->execute([':gps_id' => $gps_id]);

    // Insertar nuevos registros
    $insertStmt = $conn->prepare("INSERT INTO detalle_pagos_gps 
        (gps_id, efectivo_o_banco, fecha_pago, letra, importe, deuda_mora, monto_mora) 
        VALUES (:gps_id, :efectivo_banco, :fecha_pago, :letra, :importe, :deuda_mora, :monto_mora)");

    for ($i = 0; $i < count($fecha_pago); $i++) {
        if (empty($fecha_pago[$i]) || empty($importe[$i])) {
            continue; // Saltar si no tiene fecha o importe
        }

        $insertStmt->execute([
            ':gps_id' => $gps_id,
            ':efectivo_banco' => $efectivo_banco[$i] ?? '',
            ':fecha_pago' => $fecha_pago[$i],
            ':letra' => $letra[$i] ?? '',
            ':importe' => $importe[$i] ?? 0,
            ':deuda_mora' => $deuda_mora[$i] ?? 0,
            ':monto_mora' => $monto_mora[$i] ?? 0,
        ]);
    }

    // Redirigir de nuevo a la vista con un mensaje
    header("Location: consultar_fechas_gps.php?id_cliente=$id_cliente&placa=$placa&guardado=1");
    exit;

} catch (PDOException $e) {
    die("Error al guardar los pagos: " . $e->getMessage());
}
