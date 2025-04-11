<?php

// Incluir la conexión a la base de datos
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    // Obtener datos básicos del cliente
    $tipoPersona = trim($_POST['tipo_persona']);
    $nombres = trim($_POST['nombres']);
    $tipoDocumento = trim($_POST['tipo_documento']);
    $documento = trim($_POST['documento']);
    $sexo = trim($_POST['sexo']);

    // Obtener los teléfonos como array
    $telefonos = isset($_POST['telefonos']) && is_array($_POST['telefonos']) ? $_POST['telefonos'] : [];

    try {
        // Verificar si el documento ya existe
        $stmt = $conn->prepare("SELECT COUNT(*) FROM clientes WHERE documento = :documento");
        $stmt->execute([':documento' => $documento]);
        $existe = $stmt->fetchColumn();

        if ($existe > 0) {
            echo json_encode(["status" => "error", "message" => "El documento ya está registrado."]);
            exit;
        }

        // Insertar el cliente en la tabla `clientes`
        $stmt = $conn->prepare("INSERT INTO clientes (tipo_persona, nombres, tipo_documento, documento, sexo) VALUES (:tipo_persona, :nombres, :tipo_documento, :documento, :sexo)");
        $stmt->execute([
            ':tipo_persona' => $tipoPersona,
            ':nombres' => $nombres,
            ':tipo_documento' => $tipoDocumento,
            ':documento' => $documento,
            ':sexo' => $sexo,
        ]);

        // Obtener el ID del cliente recién creado
        $clienteId = $conn->lastInsertId();

        // Insertar los teléfonos en la tabla `telefonos`
        foreach ($telefonos as $telefono) {
            $telefono = trim($telefono); // Eliminar espacios
            if (!empty($telefono)) {
                $stmt = $conn->prepare("INSERT INTO telefonos (cliente_id, telefono) VALUES (:cliente_id, :telefono)");
                $stmt->execute([
                    ':cliente_id' => $clienteId,
                    ':telefono' => $telefono,
                ]);
            }
        }

        echo json_encode(["status" => "success", "message" => "Cliente agregado correctamente"]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error al guardar los datos: " . $e->getMessage()]);
        exit;
    }
} else 
{
    header("Location: clientes.php");
    exit;
}

