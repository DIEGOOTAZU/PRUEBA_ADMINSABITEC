<?php
// Incluir la conexión a la base de datos
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clienteId = $_POST['id'];
    $tipoPersona = $_POST['tipo_persona'];
    $nombres = $_POST['nombres'];
    $tipoDocumento = $_POST['tipo_documento'];
    $documento = $_POST['documento'];
    $sexo = $_POST['sexo'];

    // Validar los datos de los teléfonos
    $telefonosExistentes = isset($_POST['telefonos_existentes']) && is_array($_POST['telefonos_existentes']) ? $_POST['telefonos_existentes'] : [];
    $telefonosNuevos = isset($_POST['telefonos_nuevos']) && is_array($_POST['telefonos_nuevos']) ? $_POST['telefonos_nuevos'] : [];

    try {
        // Actualizar los datos básicos del cliente
        $stmt = $conn->prepare("UPDATE clientes SET tipo_persona = :tipo_persona, nombres = :nombres, tipo_documento = :tipo_documento, documento = :documento, sexo = :sexo WHERE id = :id");
        $stmt->execute([
            ':tipo_persona' => $tipoPersona,
            ':nombres' => $nombres,
            ':tipo_documento' => $tipoDocumento,
            ':documento' => $documento,
            ':sexo' => $sexo,
            ':id' => $clienteId,
        ]);

        // Procesar los teléfonos existentes
        $stmt = $conn->prepare("SELECT id FROM telefonos WHERE cliente_id = :cliente_id");
        $stmt->execute([':cliente_id' => $clienteId]);
        $telefonosDB = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($telefonosDB as $telefonoId) {
            if (isset($telefonosExistentes[$telefonoId])) {
                // Si el teléfono existe en el formulario, actualizarlo
                $stmt = $conn->prepare("UPDATE telefonos SET telefono = :telefono WHERE id = :id");
                $stmt->execute([
                    ':telefono' => $telefonosExistentes[$telefonoId],
                    ':id' => $telefonoId,
                ]);
            } else {
                // Si el teléfono no existe en el formulario, eliminarlo
                $stmt = $conn->prepare("DELETE FROM telefonos WHERE id = :id");
                $stmt->execute([':id' => $telefonoId]);
            }
        }

        // Procesar los nuevos teléfonos
        foreach ($telefonosNuevos as $telefonoNuevo) {
            if (!empty($telefonoNuevo)) {
                // Insertar los nuevos teléfonos
                $stmt = $conn->prepare("INSERT INTO telefonos (cliente_id, telefono) VALUES (:cliente_id, :telefono)");
                $stmt->execute([
                    ':cliente_id' => $clienteId,
                    ':telefono' => $telefonoNuevo,
                ]);
            }
        }

        // Redirigir con un mensaje de éxito
        header("Location: clientes.php?mensaje=Cliente actualizado correctamente");
        exit;
    } catch (PDOException $e) {
        die("Error al actualizar los datos: " . $e->getMessage());
    }
} else {
    // Si no es un POST, redirigir a la lista de clientes
    header("Location: clientes.php");
    exit;
}
