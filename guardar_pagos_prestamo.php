<?php 
// Incluir la conexión a la base de datos
require_once 'config/db.php';

// Validar que los datos necesarios estén presentes
if (!isset($_POST['cliente_id']) || empty($_POST['cliente_id'])) {
    die("Error: Cliente no especificado.");
}
if (!isset($_POST['prestamo_id']) || empty($_POST['prestamo_id'])) {
    die("Error: Prestamo no especificado.");
}

$cliente_id = $_POST['cliente_id'];
$prestamo_id = $_POST['prestamo_id'];

// Obtener los valores enviados por el formulario
$efectivo_banco = $_POST['efectivo_banco'] ?? [];
$fecha_pago = $_POST['fecha_pago'] ?? [];
$letra = $_POST['letra'] ?? [];
$importe = $_POST['importe'] ?? [];
$deuda_mora = $_POST['deuda_mora'] ?? [];
$monto_mora = $_POST['monto_mora'] ?? [];
$id_pago = $_POST['id_pago'] ?? []; // IDs de los pagos existentes

try {
    // Iniciar la transacción
    $conn->beginTransaction();

    $pagos_en_formulario = [];

    for ($i = 0; $i < count($fecha_pago); $i++) {
        $id = $id_pago[$i] ?? '';

        // **🔹 Validar si la fila está vacía**
        if (
            empty(trim($fecha_pago[$i])) &&
            empty(trim($importe[$i])) &&
            empty(trim($letra[$i])) &&
            empty(trim($deuda_mora[$i])) &&
            empty(trim($monto_mora[$i]))
        ) {
            continue; // Si la fila está vacía, saltarla
        }

        if (!empty($id)) {
            // **Actualizar pagos existentes**
            $stmt = $conn->prepare("UPDATE detalle_pagos_prestamo SET 
                efectivo_o_banco = :efectivo_banco,
                fecha_pago = :fecha_pago,
                letra = :letra,
                importe = :importe,
                deuda_mora = :deuda_mora,
                monto_mora = :monto_mora
                WHERE id = :id
            ");
            $stmt->execute([
                ':efectivo_banco' => $efectivo_banco[$i],
                ':fecha_pago' => $fecha_pago[$i],
                ':letra' => $letra[$i],
                ':importe' => $importe[$i],
                ':deuda_mora' => $deuda_mora[$i],
                ':monto_mora' => $monto_mora[$i],
                ':id' => $id
            ]);
            $pagos_en_formulario[] = $id;
        } else {
            // **Insertar nuevos pagos**
            $stmt = $conn->prepare("INSERT INTO detalle_pagos_prestamo (prestamo_id, efectivo_o_banco, fecha_pago, letra, importe, deuda_mora, monto_mora) 
                VALUES (:prestamo_id, :efectivo_banco, :fecha_pago, :letra, :importe, :deuda_mora, :monto_mora)");
            $stmt->execute([
                ':prestamo_id' => $prestamo_id,
                ':efectivo_banco' => $efectivo_banco[$i],
                ':fecha_pago' => $fecha_pago[$i],
                ':letra' => $letra[$i],
                ':importe' => $importe[$i],
                ':deuda_mora' => $deuda_mora[$i],
                ':monto_mora' => $monto_mora[$i]
            ]);
            $pagos_en_formulario[] = $conn->lastInsertId();
        }
    }

    // **Eliminar pagos que ya no están en el formulario**
    if (!empty($pagos_en_formulario)) {
        $ids_str = implode(",", array_map('intval', $pagos_en_formulario));
        $stmt = $conn->prepare("DELETE FROM detalle_pagos_prestamo WHERE prestamo_id = :prestamo_id AND id NOT IN ($ids_str)");
        $stmt->execute([':prestamo_id' => $prestamo_id]);
    } else {
        // Si no hay pagos en el formulario, eliminar todos los pagos del préstamo
        $stmt = $conn->prepare("DELETE FROM detalle_pagos_prestamo WHERE prestamo_id = :prestamo_id");
        $stmt->execute([':prestamo_id' => $prestamo_id]);
    }

    // Confirmar la transacción
    $conn->commit();

    // Redirigir después de guardar los pagos
    header('Location: consultar_fechas_prestamos.php?cliente_id=' . $cliente_id . '&cliente=' . urlencode($_POST['cliente']));
    exit();
} catch (PDOException $e) {
    // Si ocurre un error, hacer rollback de la transacción
    $conn->rollBack();
    die("Error al guardar los pagos: " . $e->getMessage());
}
