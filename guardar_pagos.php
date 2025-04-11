<?php
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = $_POST['cliente'] ?? '';
    $placa = $_POST['placa'] ?? '';
    $efectivo_banco = $_POST['efectivo_banco'] ?? [];
    $fecha_pago = $_POST['fecha_pago'] ?? [];
    $letra = $_POST['letra'] ?? [];
    $importe = $_POST['importe'] ?? [];
    $deuda_mora = $_POST['deuda_mora'] ?? [];
    $monto_mora = $_POST['monto_mora'] ?? [];

    try {
        // Buscar o insertar registro en `data_cobranzas`
        $stmt = $conn->prepare("
            SELECT id FROM data_cobranzas WHERE cliente = :cliente AND placa = :placa
        ");
        $stmt->execute([':cliente' => $cliente, ':placa' => $placa]);
        $cobranza = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cobranza) {
            // Insertar si no existe
            $insertCobranza = $conn->prepare("
                INSERT INTO data_cobranzas (cliente, placa, mensualidad_real, fecha_contrato, telefono)
                VALUES (:cliente, :placa, 0, CURDATE(), '')
            ");
            $insertCobranza->execute([':cliente' => $cliente, ':placa' => $placa]);
            $cobranzaId = $conn->lastInsertId();
        } else {
            $cobranzaId = $cobranza['id'];
        }

        // Eliminar detalles previos
        $deleteStmt = $conn->prepare("DELETE FROM detalle_pagos WHERE data_cobranza_id = :id");
        $deleteStmt->execute([':id' => $cobranzaId]);

        // Insertar nuevos detalles
        $insertDetail = $conn->prepare("
            INSERT INTO detalle_pagos (data_cobranza_id, efectivo_o_banco, fecha_pago, letra, importe, deuda_mora, monto_mora)
            VALUES (:data_cobranza_id, :efectivo_o_banco, :fecha_pago, :letra, :importe, :deuda_mora, :monto_mora)
        ");

        for ($i = 0; $i < count($efectivo_banco); $i++) {
            if (!empty($efectivo_banco[$i]) || !empty($fecha_pago[$i]) || !empty($letra[$i]) || !empty($importe[$i])) {
                // Calcular monto_mora si no se envió desde el formulario
                $calculated_monto_mora = !empty($deuda_mora[$i]) ? $deuda_mora[$i] * 50 : 0;

                $insertDetail->execute([
                    ':data_cobranza_id' => $cobranzaId,
                    ':efectivo_o_banco' => $efectivo_banco[$i],
                    ':fecha_pago' => $fecha_pago[$i],
                    ':letra' => $letra[$i],
                    ':importe' => $importe[$i],
                    ':deuda_mora' => $deuda_mora[$i] ?? 0,
                    ':monto_mora' => $calculated_monto_mora
                ]);
            }
        }

        // Redirigir de vuelta a la página con éxito
        header("Location: consultar_fechas.php?cliente=$cliente&placa=$placa&success=true");
        exit;
    } catch (PDOException $e) {
        die("Error al guardar los datos: " . $e->getMessage());
    }
}
?>
