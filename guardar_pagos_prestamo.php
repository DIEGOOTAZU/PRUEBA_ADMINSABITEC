<?php 
// Incluir la conexión a la base de datos
require_once 'config/db.php';

// Validar que los datos necesarios estén presentes
// Usar un nombre de variable diferente para el cliente enviado en el POST para evitar conflictos si existe un cliente_id en la base de datos
$post_cliente_nombre = $_POST['cliente'] ?? null; 

if (!isset($_POST['prestamo_id']) || empty($_POST['prestamo_id'])) {
    die("Error: No se ha proporcionado un ID de préstamo."); // Mensaje más descriptivo
}

$cliente_id = $_POST['cliente_id']; // Este ya lo envías desde el hidden de consultar_fechas_prestamos
$prestamo_id = $_POST['prestamo_id'];


// Obtener los valores enviados por el formulario
$efectivo_banco = $_POST['efectivo_banco'] ?? [];
$fecha_pago = $_POST['fecha_pago'] ?? [];
$letra = $_POST['letra'] ?? [];
$importe = $_POST['importe'] ?? [];
$deuda_mora = $_POST['deuda_mora'] ?? [];
$monto_mora = $_POST['monto_mora'] ?? [];
$id_pago = $_POST['id_pago'] ?? []; // IDs de los pagos existentes (para los que ya estaban en la DB)

try {
    // Iniciar la transacción
    $conn->beginTransaction();

    $pagos_en_formulario = []; // Array para guardar los IDs de los pagos procesados en este formulario

    // Recorrer las filas del formulario
    // Es crucial que todos los arrays (efectivo_banco, fecha_pago, etc.) tengan la misma longitud.
    // Usaremos count($fecha_pago) como referencia.
    for ($i = 0; $i < count($fecha_pago); $i++) {
        // Obtener el ID de pago para la fila actual, si existe. Si es una fila nueva, será nulo o vacío.
        $current_id_pago = $id_pago[$i] ?? null; 

        // **🔹 Validar si la fila está realmente vacía (todos sus campos principales)**
        // Esto previene insertar o actualizar filas de la tabla que están vacías
        // También verifica que el 'efectivo_o_banco' no esté vacío si la intención es guardar una fila.
        if (
            empty(trim($fecha_pago[$i])) &&
            empty(trim($importe[$i])) &&
            empty(trim($letra[$i])) &&
            empty(trim($deuda_mora[$i])) &&
            empty(trim($monto_mora[$i])) &&
            empty(trim($efectivo_banco[$i])) // También considerar el campo de selección
        ) {
            // Si la fila está completamente vacía y no tiene un ID existente, simplemente la ignoramos.
            // Si tiene un ID existente pero está vacía, no haremos nada aquí, se encargará el bloque de eliminación.
            continue; 
        }

        // Si la fila tiene un ID (es un pago existente), la actualizamos
        if ($current_id_pago !== null && !empty($current_id_pago)) {
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
                ':efectivo_banco' => $efectivo_banco[$i] ?? null, // Usar null coalescing para evitar 'undefined index'
                ':fecha_pago' => $fecha_pago[$i] ?? null,
                ':letra' => $letra[$i] ?? null,
                ':importe' => $importe[$i] ?? null,
                ':deuda_mora' => $deuda_mora[$i] ?? 0,
                ':monto_mora' => $monto_mora[$i] ?? 0,
                ':id' => $current_id_pago
            ]);
            $pagos_en_formulario[] = $current_id_pago; // Agregar el ID a la lista de pagos procesados
        } else {
            // Si la fila NO tiene un ID (es un nuevo pago), la insertamos
            $stmt = $conn->prepare("INSERT INTO detalle_pagos_prestamo (prestamo_id, efectivo_o_banco, fecha_pago, letra, importe, deuda_mora, monto_mora) 
                VALUES (:prestamo_id, :efectivo_banco, :fecha_pago, :letra, :importe, :deuda_mora, :monto_mora)");
            $stmt->execute([
                ':prestamo_id' => $prestamo_id,
                ':efectivo_banco' => $efectivo_banco[$i] ?? null,
                ':fecha_pago' => $fecha_pago[$i] ?? null,
                ':letra' => $letra[$i] ?? null,
                ':importe' => $importe[$i] ?? null,
                ':deuda_mora' => $deuda_mora[$i] ?? 0,
                ':monto_mora' => $monto_mora[$i] ?? 0
            ]);
            $pagos_en_formulario[] = $conn->lastInsertId(); // Agregar el nuevo ID generado a la lista
        }
    }

    // **Eliminar pagos que ya NO están en el formulario (basado en los IDs originales)**
    // Primero, obtenemos todos los IDs de pagos existentes para este préstamo de la DB.
    $stmtExistingPayments = $conn->prepare("SELECT id FROM detalle_pagos_prestamo WHERE prestamo_id = :prestamo_id");
    $stmtExistingPayments->execute([':prestamo_id' => $prestamo_id]);
    $existing_ids_db = $stmtExistingPayments->fetchAll(PDO::FETCH_COLUMN);

    // Identificar los IDs que estaban en la DB pero NO fueron enviados en el formulario (es decir, fueron eliminados visualmente)
    $ids_to_delete = array_diff($existing_ids_db, $pagos_en_formulario);

    if (!empty($ids_to_delete)) {
        $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
        $stmtDelete = $conn->prepare("DELETE FROM detalle_pagos_prestamo WHERE id IN ($placeholders)");
        $stmtDelete->execute(array_values($ids_to_delete));
    }
    
    // Confirmar la transacción
    $conn->commit();

    // Redirigir después de guardar los pagos
    // Asegúrate de pasar el prestamo_id y el nombre del cliente (si es necesario)
    header('Location: consultar_fechas_prestamos.php?prestamo_id=' . $prestamo_id . '&cliente=' . urlencode($post_cliente_nombre));
    exit();

} catch (PDOException $e) {
    // Si ocurre un error, hacer rollback de la transacción
    $conn->rollBack();
    die("Error al guardar los pagos: " . $e->getMessage());
}
?>