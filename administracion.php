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

// Variables iniciales
$datosContrato = null;
$datosVehiculo = null;
$pagos = []; // Pagos de contratos de vehículo
$mensajeError = "";
$dni_buscado = ""; // Variable para mantener el DNI/RUC en el input
$cliente_id_encontrado = null; // Almacenará el ID del cliente si se encuentra por DNI
$cliente_nombre_encontrado = null; // Almacenará el nombre del cliente
$cliente_telefono_encontrado = null; // Almacenará el teléfono del cliente
$placa_seleccionada_para_mostrar = null; // Inicializar para evitar el warning
$prestamos_asociados = []; // Almacena todos los préstamos encontrados para el cliente
$datosPrestamoSeleccionado = null; // Almacena los datos del préstamo específico seleccionado/mostrado
$prestamo_id_seleccionado = null; // ID del préstamo que se está mostrando actualmente
$pagosPrestamo = []; // Pagos de préstamos

// --- Lógica de procesamiento de la búsqueda ---

// Detectar si se envió el formulario principal por POST (búsqueda por DNI/RUC)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dni'])) {
    $dni_buscado = trim($_POST['dni']);

    try {
        // 1. Buscar el cliente por DNI/RUC (solo de la tabla clientes)
        $stmt_cliente = $conn->prepare("
            SELECT id, nombres, documento
            FROM clientes
            WHERE documento = :dni
            LIMIT 1
        ");
        $stmt_cliente->execute([':dni' => $dni_buscado]);
        $cliente_data = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

        if ($cliente_data) {
            $cliente_id_encontrado = $cliente_data['id'];
            $cliente_nombre_encontrado = $cliente_data['nombres'];

            // 2. Obtener el teléfono del cliente de la tabla 'telefonos' en una consulta separada
            $stmt_telefono = $conn->prepare("
                SELECT telefono FROM telefonos WHERE cliente_id = :cliente_id LIMIT 1
            ");
            $stmt_telefono->execute([':cliente_id' => $cliente_id_encontrado]);
            $telefono_data = $stmt_telefono->fetch(PDO::FETCH_ASSOC);
            if ($telefono_data) {
                $cliente_telefono_encontrado = $telefono_data['telefono'];
            }

            // 3. Obtener todas las placas asociadas a ese cliente por su NOMBRE
            $stmt_placas = $conn->prepare("SELECT placa, id FROM data_cobranzas WHERE cliente = :cliente_nombre");
            $stmt_placas->execute([':cliente_nombre' => $cliente_nombre_encontrado]);
            $placas_asociadas = $stmt_placas->fetchAll(PDO::FETCH_ASSOC);

            // 4. Obtener todos los préstamos asociados a ese cliente_id
            $stmt_prestamos = $conn->prepare("SELECT * FROM prestamos WHERE cliente_id = :cliente_id");
            $stmt_prestamos->execute([':cliente_id' => $cliente_id_encontrado]);
            $prestamos_asociados = $stmt_prestamos->fetchAll(PDO::FETCH_ASSOC);

            if (empty($placas_asociadas) && empty($prestamos_asociados)) {
                $mensajeError = "No se encontraron contratos ni préstamos para el DNI/RUC " . htmlspecialchars($dni_buscado) . ".";
            } elseif (count($placas_asociadas) === 1 && empty($prestamos_asociados)) {
                // Si solo hay una placa y no hay préstamos, cargar los datos del contrato automáticamente
                $placa_unica = $placas_asociadas[0]['placa'];
                $id_contrato_unico = $placas_asociadas[0]['id'];

                $stmt_contrato = $conn->prepare("SELECT * FROM data_cobranzas WHERE id = :id");
                $stmt_contrato->execute([':id' => $id_contrato_unico]);
                $datosContrato = $stmt_contrato->fetch(PDO::FETCH_ASSOC);
                $placa_seleccionada_para_mostrar = $placa_unica;

                if ($datosContrato) {
                    $stmtPagos = $conn->prepare("SELECT * FROM detalle_pagos WHERE data_cobranza_id = :id ORDER BY fecha_pago ASC");
                    $stmtPagos->execute([':id' => $datosContrato['id']]);
                    $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

                    $stmtVehiculo = $conn->prepare("SELECT * FROM vehiculos WHERE placa = :placa");
                    $stmtVehiculo->execute([':placa' => $placa_unica]);
                    $datosVehiculo = $stmtVehiculo->fetch(PDO::FETCH_ASSOC);
                }
            } elseif (count($prestamos_asociados) === 1 && empty($placas_asociadas)) {
                 // Si solo hay un préstamo y no hay placas, cargar los datos del préstamo automáticamente
                 $datosPrestamoSeleccionado = $prestamos_asociados[0];
                 $prestamo_id_seleccionado = $datosPrestamoSeleccionado['id'];
                 // Cargar pagos del préstamo automáticamente
                 $stmtPagosPrestamo = $conn->prepare("SELECT * FROM detalle_pagos_prestamo WHERE prestamo_id = :prestamo_id ORDER BY fecha_pago ASC");
                 $stmtPagosPrestamo->execute([':prestamo_id' => $datosPrestamoSeleccionado['id']]);
                 $pagosPrestamo = $stmtPagosPrestamo->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $mensajeError = "No se encontró un cliente con el DNI/RUC " . htmlspecialchars($dni_buscado) . ".";
        }
    } catch (PDOException $e) {
        $mensajeError = "Error al buscar: " . $e->getMessage();
    }
}
// Detectar si se seleccionó una placa del desplegable (viene por GET)
elseif (isset($_GET['selected_placa']) && isset($_GET['dni_original'])) {
    $placa_seleccionada_para_mostrar = trim($_GET['selected_placa']);
    $dni_buscado = trim($_GET['dni_original']);

    try {
        // Recargar el cliente_id_encontrado y el nombre
        $stmt_cliente = $conn->prepare("
            SELECT id, nombres, documento
            FROM clientes
            WHERE documento = :dni
            LIMIT 1
        ");
        $stmt_cliente->execute([':dni' => $dni_buscado]);
        $cliente_data = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
        if ($cliente_data) {
            $cliente_id_encontrado = $cliente_data['id'];
            $cliente_nombre_encontrado = $cliente_data['nombres'];

            // Recargar el teléfono del cliente en una consulta separada
            $stmt_telefono = $conn->prepare("
                SELECT telefono FROM telefonos WHERE cliente_id = :cliente_id LIMIT 1
            ");
            $stmt_telefono->execute([':cliente_id' => $cliente_id_encontrado]);
            $telefono_data = $stmt_telefono->fetch(PDO::FETCH_ASSOC);
            if ($telefono_data) {
                $cliente_telefono_encontrado = $telefono_data['telefono'];
            }

            // Recargar las placas asociadas (para el dropdown)
            $stmt_placas = $conn->prepare("SELECT placa, id FROM data_cobranzas WHERE cliente = :cliente_nombre");
            $stmt_placas->execute([':cliente_nombre' => $cliente_nombre_encontrado]);
            $placas_asociadas = $stmt_placas->fetchAll(PDO::FETCH_ASSOC);

            // Recargar los préstamos asociados (para el dropdown de préstamos)
            $stmt_prestamos = $conn->prepare("SELECT * FROM prestamos WHERE cliente_id = :cliente_id");
            $stmt_prestamos->execute([':cliente_id' => $cliente_id_encontrado]);
            $prestamos_asociados = $stmt_prestamos->fetchAll(PDO::FETCH_ASSOC);
        }

        // Obtener los datos del contrato para la placa seleccionada
        $stmt_contrato = $conn->prepare("SELECT * FROM data_cobranzas WHERE placa = :placa");
        $stmt_contrato->execute([':placa' => $placa_seleccionada_para_mostrar]);
        $datosContrato = $stmt_contrato->fetch(PDO::FETCH_ASSOC);

        if ($datosContrato) {
            $stmtPagos = $conn->prepare("SELECT * FROM detalle_pagos WHERE data_cobranza_id = :id ORDER BY fecha_pago ASC");
            $stmtPagos->execute([':id' => $datosContrato['id']]);
            $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

            $stmtVehiculo = $conn->prepare("SELECT * FROM vehiculos WHERE placa = :placa");
            $stmtVehiculo->execute([':placa' => $placa_seleccionada_para_mostrar]);
            $datosVehiculo = $stmtVehiculo->fetch(PDO::FETCH_ASSOC);
        } else {
            $mensajeError = "No se encontró un contrato para la placa seleccionada: " . htmlspecialchars($placa_seleccionada_para_mostrar);
        }
    } catch (PDOException $e) {
        $mensajeError = "Error al obtener datos de la placa seleccionada: " . $e->getMessage();
    }
}
// NUEVO: Detectar si se seleccionó un préstamo del desplegable (viene por GET)
elseif (isset($_GET['selected_prestamo_id']) && isset($_GET['dni_original'])) {
    $prestamo_id_seleccionado = trim($_GET['selected_prestamo_id']);
    $dni_buscado = trim($_GET['dni_original']);

    try {
        // Recargar el cliente_id_encontrado y el nombre
        $stmt_cliente = $conn->prepare("
            SELECT id, nombres, documento
            FROM clientes
            WHERE documento = :dni
            LIMIT 1
        ");
        $stmt_cliente->execute([':dni' => $dni_buscado]);
        $cliente_data = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

        if ($cliente_data) {
            $cliente_id_encontrado = $cliente_data['id'];
            $cliente_nombre_encontrado = $cliente_data['nombres'];

            // Recargar el teléfono del cliente en una consulta separada
            $stmt_telefono = $conn->prepare("
                SELECT telefono FROM telefonos WHERE cliente_id = :cliente_id LIMIT 1
            ");
            $stmt_telefono->execute([':cliente_id' => $cliente_id_encontrado]);
            $telefono_data = $stmt_telefono->fetch(PDO::FETCH_ASSOC);
            if ($telefono_data) {
                $cliente_telefono_encontrado = $telefono_data['telefono'];
            }

            // Recargar las placas asociadas (para el dropdown de vehículos)
            $stmt_placas = $conn->prepare("SELECT placa, id FROM data_cobranzas WHERE cliente = :cliente_nombre");
            $stmt_placas->execute([':cliente_nombre' => $cliente_nombre_encontrado]);
            $placas_asociadas = $stmt_placas->fetchAll(PDO::FETCH_ASSOC);

            // Recargar todos los préstamos para el dropdown
            $stmt_prestamos = $conn->prepare("SELECT * FROM prestamos WHERE cliente_id = :cliente_id");
            $stmt_prestamos->execute([':cliente_id' => $cliente_id_encontrado]);
            $prestamos_asociados = $stmt_prestamos->fetchAll(PDO::FETCH_ASSOC);

            // Obtener los datos del préstamo específico seleccionado
            $stmt_selected_prestamo = $conn->prepare("SELECT * FROM prestamos WHERE id = :id AND cliente_id = :cliente_id");
            $stmt_selected_prestamo->execute([':id' => $prestamo_id_seleccionado, ':cliente_id' => $cliente_id_encontrado]);
            $datosPrestamoSeleccionado = $stmt_selected_prestamo->fetch(PDO::FETCH_ASSOC);

            if ($datosPrestamoSeleccionado) {
                // Cargar pagos del préstamo seleccionado
                $stmtPagosPrestamo = $conn->prepare("SELECT * FROM detalle_pagos_prestamo WHERE prestamo_id = :prestamo_id ORDER BY fecha_pago ASC");
                $stmtPagosPrestamo->execute([':prestamo_id' => $datosPrestamoSeleccionado['id']]);
                $pagosPrestamo = $stmtPagosPrestamo->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $mensajeError = "No se encontró el préstamo seleccionado o no pertenece a este cliente.";
            }
        } else {
            $mensajeError = "No se encontró el cliente para el DNI/RUC original.";
        }
    } catch (PDOException $e) {
        $mensajeError = "Error al obtener datos del préstamo seleccionado: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            height: 100vh;
            background-color: #343a40;
            color: white;
            position: fixed;
            width: 250px;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar .submenu {
            display: none;
            padding-left: 20px;
        }
        .sidebar .has-submenu.active .submenu {
            display: block;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px;
        }
        .details-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .details-section {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .table-header {
            background-color: #007bff;
            color: white;
        }
        .summary .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .summary .total-row:last-child {
            border-bottom: none;
        }
        .summary .label {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3 class="text-center">Sabitec GPS</h3>
    <a href="index.php">Inicio</a>
    <div class="has-submenu">
        <a href="#" onclick="toggleSubmenu(event)">Contratos</a>
        <div class="submenu">
            <a href="administrar_contratos.php">Administrar Contratos</a>
        </div>
    </div>
    <div class="has-submenu">
        <a href="#" onclick="toggleSubmenu(event)">Servicios</a>
        <div class="submenu">
            <a href="consulta_pagos.php">Consulta de Pagos</a>
            <a href="generar_reportes.php">Generar Reportes</a>
        </div>
    </div>
    <a href="#">Cobranzas</a>
    <a href="administracion.php">Administración</a>
    <a href="clientes.php">Clientes</a>
    <a href="vehiculos.php">Vehículos</a>
    <a href="#">Tipos de Servicios</a>
    <a href="logout.php">Cerrar Sesión</a>
</div>

<div class="main-content">
    <h2>Administración</h2>

    <form method="POST" action="administracion.php" class="mb-4">
        <div class="form-group">
            <label for="dni">Ingrese DNI/RUC:</label>
            <input type="text" name="dni" id="dni" class="form-control" placeholder="Ej: 12345678" value="<?= htmlspecialchars($dni_buscado) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Buscar</button>

        <?php if ($cliente_id_encontrado): ?>
            <?php if (!empty($placas_asociadas)): ?>
                <button type="button" class="btn btn-info" id="btnAdministrarVehiculo">Administrar Vehículo</button>
            <?php endif; ?>
            <?php if (!empty($prestamos_asociados)): ?>
                <button type="button" class="btn btn-warning" id="btnAdministrarPrestamo">Administrar Préstamo</button>
            <?php endif; ?>
        <?php endif; ?>
    </form>

    <?php if ($mensajeError): ?>
        <div class="alert alert-danger"><?= $mensajeError ?></div>
    <?php endif; ?>

    <div id="detallesContratoVehiculo" style="display: none;">
        <?php if (!empty($placas_asociadas) && count($placas_asociadas) > 1): ?>
            <div class="form-group mt-3" id="placaSelectionContainer">
                <label for="select_placa">Este DNI/RUC tiene múltiples placas. Seleccione una:</label>
                <select id="select_placa" class="form-control">
                    <option value="">-- Seleccionar Placa --</option>
                    <?php foreach ($placas_asociadas as $placa_info): ?>
                        <option value="<?= htmlspecialchars($placa_info['placa']) ?>" <?= ($placa_info['placa'] === $placa_seleccionada_para_mostrar) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($placa_info['placa']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($datosContrato): ?>
            <div class="details-container">
                <div class="details-section">
                    <h4>Detalles del Contrato (Placa: <?= htmlspecialchars($placa_seleccionada_para_mostrar ?? 'N/A') ?>)</h4>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente_nombre_encontrado ?? 'N/A') ?></p>
                    <p><strong>DNI/RUC:</strong> <?= htmlspecialchars($dni_buscado) ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente_telefono_encontrado ?? 'N/A') ?></p>
                    <p><strong>Placa:</strong> <?= htmlspecialchars($datosContrato['placa'] ?? 'N/A') ?></p>
                    <p><strong>Fecha de Contrato:</strong> <?= htmlspecialchars($datosContrato['fecha_contrato'] ?? 'N/A') ?></p>
                    <p><strong>Fecha de Pago:</strong> <?= htmlspecialchars($datosContrato['fecha_pago'] ?? 'N/A') ?></p>
                    <p><strong>Letra:</strong> <?= htmlspecialchars($datosContrato['letra'] ?? 'N/A') ?> meses</p>
                    <p><strong>Inicial:</strong> $<?= htmlspecialchars($datosContrato['inicial'] ?? '0.00') ?></p>
                    <p><strong>Mensualidad Real:</strong> $<?= htmlspecialchars($datosContrato['mensualidad_real'] ?? '0.00') ?></p>
                    <p><strong>Monto Total:</strong> $<?= htmlspecialchars($datosContrato['monto_total'] ?? '0.00') ?></p>
                </div>
                <div class="details-section">
                    <h4>Información del Vehículo</h4>
                    <?php if ($datosVehiculo): ?>
                        <p><strong>Placa:</strong> <?= htmlspecialchars($datosVehiculo['placa']) ?></p>
                        <p><strong>Marca:</strong> <?= htmlspecialchars($datosVehiculo['marca']) ?></p>
                        <p><strong>Modelo:</strong> <?= htmlspecialchars($datosVehiculo['modelo']) ?></p>
                        <p><strong>Color:</strong> <?= htmlspecialchars($datosVehiculo['color']) ?></p>
                        <p><strong>Año:</strong> <?= htmlspecialchars($datosVehiculo['anio']) ?></p>
                    <?php else: ?>
                        <p>No se encontraron datos del vehículo para la placa asociada al contrato.</p>
                    <?php endif; ?>
                </div>
            </div>

            <h4 class="mt-4">Pagos Registrados</h4>
            <table class="table table-bordered">
                <thead class="table-header">
                    <tr>
                        <th>#</th>
                        <th>Forma de Pago</th>
                        <th>Fecha de Pago</th>
                        <th>Letra</th>
                        <th>Importe</th>
                        <th>Deuda Mora</th>
                        <th>Monto Mora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pagos)): ?>
                        <?php foreach ($pagos as $index => $pago): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($pago['efectivo_o_banco'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($pago['fecha_pago'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($pago['letra'] ?? 'N/A') ?></td>
                                <td>
                                    <input type="text" name="importe[]" class="form-control" value="<?= htmlspecialchars($pago['importe'] ?? '0.00') ?>" readonly>
                                </td>
                                <td><?= htmlspecialchars($pago['deuda_mora'] ?? '0.00') ?></td>
                                <td>
                                    <input type="text" name="monto_mora[]" class="form-control" value="<?= htmlspecialchars(isset($pago['deuda_mora']) ? ($pago['deuda_mora'] * 50) : '0.00') ?>" readonly>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No hay pagos registrados para este contrato.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="summary mt-4">
                <div class="total-row">
                    <span class="label">Total Cancelado:</span>
                    <span id="totalCancelado" class="value text-success">$0.00</span>
                </div>
                <div class="total-row">
                    <span class="label">Total Deuda:</span>
                    <span id="totalDeuda" class="value text-danger">$0.00</span>
                </div>
                <div class="total-row">
                    <span class="label">Deuda por Mora:</span>
                    <span id="totalDeudaMora" class="value text-warning">$0.00</span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div id="detallesPrestamo" class="mt-4" style="display: none;">
        <h4>Detalles del Préstamo</h4>
        <?php if (!empty($prestamos_asociados)): ?>
            <?php if (count($prestamos_asociados) > 1): ?>
                <div class="form-group mt-3" id="prestamoSelectionContainer">
                    <label for="select_prestamo">Este cliente tiene múltiples préstamos. Seleccione uno:</label>
                    <select id="select_prestamo" class="form-control">
                        <option value="">-- Seleccionar Préstamo --</option>
                        <?php foreach ($prestamos_asociados as $prestamo_info): ?>
                            <option value="<?= htmlspecialchars($prestamo_info['id']) ?>" <?= ($prestamo_info['id'] == ($prestamo_id_seleccionado ?? null)) ? 'selected' : '' ?>>
                                Préstamo ID: <?= htmlspecialchars($prestamo_info['id']) ?> (Garantía: <?= htmlspecialchars($prestamo_info['garantia']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php
            $displayLoan = null;
            if ($datosPrestamoSeleccionado) {
                $displayLoan = $datosPrestamoSeleccionado;
            } elseif (count($prestamos_asociados) === 1) {
                $displayLoan = $prestamos_asociados[0];
            }
            ?>

            <?php if ($displayLoan): ?>
                <div class="details-section mb-3">
                    <h5>Préstamo ID: <?= htmlspecialchars($displayLoan['id']) ?></h5>
                    <p><strong>Garantía:</strong> <?= htmlspecialchars($displayLoan['garantia'] ?? 'N/A') ?></p>
                    <p><strong>Fecha Préstamo:</strong> <?= htmlspecialchars($displayLoan['fecha_prestamo'] ?? 'N/A') ?></p>
                    <p><strong>Vence:</strong> <?= htmlspecialchars($displayLoan['vence'] ?? 'N/A') ?></p>
                    <p><strong>Tiempo:</strong> <?= htmlspecialchars($displayLoan['tiempo'] ?? 'N/A') ?> meses</p>
                    <p><strong>Interés:</strong> <?= htmlspecialchars($displayLoan['interes'] ?? '0.00') ?>%</p>
                    <p><strong>Capital:</strong> $<?= htmlspecialchars($displayLoan['capital'] ?? '0.00') ?></p>
                    <p><strong>Interés Capital:</strong> $<?= htmlspecialchars($displayLoan['interes_capital'] ?? '0.00') ?></p>
                    <p><strong>Interés Cobrado (Estimado):</strong> $<?= htmlspecialchars($displayLoan['interes_cobrado'] ?? '0.00') ?></p>
                </div>

                <h4 class="mt-4">Pagos Registrados del Préstamo</h4>
                <table class="table table-bordered">
                    <thead class="table-header">
                        <tr>
                            <th>#</th>
                            <th>Efectivo o Banco</th>
                <th>Día de Pago</th>
                <th>Letra</th>
                <th>Importe</th>
                <th>Deuda Mora</th>
                <th>Monto Mora</th>
                
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pagosPrestamo)): ?>
                            <?php foreach ($pagosPrestamo as $index => $pago): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($pago['efectivo_o_banco'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($pago['fecha_pago'] ?? '0.00') ?></td>
                                    <td><?= htmlspecialchars($pago['letra'] ?? '0.00') ?></td>
                                    <td>$<?= htmlspecialchars($pago['importe'] ?? '0.00') ?></td>
                                    <td><?= htmlspecialchars($pago['deuda_mora'] ?? '0.00') ?></td>
                                    <td><?= htmlspecialchars(($pago['deuda_mora'] ?? 0) * 50) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No hay pagos registrados para este préstamo.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="summary mt-4">
        <div class="total-row">
            <span class="label">Total Cancelado:</span>
            <span id="totalCancelado" class="value text-success">$0.00</span>
        </div>
        <div class="total-row">
            <span class="label">Total Deuda:</span>
            <span id="totalDeuda" class="value text-danger">$0.00</span>
        </div>
        <div class="total-row">
            <span class="label">Deuda por Mora:</span>
            <span id="totalDeudaMora" class="value text-warning">$0.00</span>
        </div>
    </div>

            <?php elseif (count($prestamos_asociados) > 1 && !isset($_GET['selected_prestamo_id'])): ?>
                <p>Por favor, seleccione un préstamo de la lista desplegable.</p>
            <?php else: ?>
                <p>No se encontraron préstamos registrados para este cliente.</p>
            <?php endif; ?>
        <?php else: ?>
            <p>No se encontraron préstamos registrados para este cliente.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleSubmenu(event) {
        event.preventDefault();
        const parent = event.target.closest('.has-submenu');
        parent.classList.toggle('active');
    }

    function calcularTotales() {
        const importes = document.querySelectorAll('#detallesContratoVehiculo input[name="importe[]"]');
        const montoMoras = document.querySelectorAll('#detallesContratoVehiculo input[name="monto_mora[]"]');
        let totalCancelado = 0;
        let totalDeudaMora = 0;

        importes.forEach(input => {
            const valor = parseFloat(input.value) || 0;
            totalCancelado += valor;
        });

        montoMoras.forEach(input => {
            const valor = parseFloat(input.value) || 0;
            totalDeudaMora += valor;
        });

        const montoTotal = parseFloat(<?= json_encode($datosContrato['monto_total'] ?? 0) ?>) || 0;
        const totalDeuda = montoTotal - totalCancelado;

        document.getElementById('totalCancelado').textContent = `$${totalCancelado.toFixed(2)}`;
        document.getElementById('totalDeuda').textContent = `$${totalDeuda.toFixed(2)}`;
        document.getElementById('totalDeudaMora').textContent = `$${totalDeudaMora.toFixed(2)}`;
    }

    // NUEVO: Función para calcular totales de préstamos
    function calcularTotalesPrestamo() {
        const capitalAmortizados = document.querySelectorAll('#detallesPrestamo tbody td:nth-child(4)'); // Columna Capital Amortizado
        const interesPagados = document.querySelectorAll('#detallesPrestamo tbody td:nth-child(5)'); // Columna Interés Pagado
        const moraPagadas = document.querySelectorAll('#detallesPrestamo tbody td:nth-child(6)'); // Columna Mora Pagada

        let totalCapitalAmortizado = 0;
        let totalInteresPagado = 0;
        let totalMoraPagada = 0;

        capitalAmortizados.forEach(td => {
            const value = parseFloat(td.textContent.replace('$', '')) || 0;
            totalCapitalAmortizado += value;
        });
        interesPagados.forEach(td => {
            const value = parseFloat(td.textContent.replace('$', '')) || 0;
            totalInteresPagado += value;
        });
        moraPagadas.forEach(td => {
            const value = parseFloat(td.textContent.replace('$', '')) || 0;
            totalMoraPagada += value;
        });

        // Obtener valores iniciales del préstamo desde PHP
        const loanCapital = parseFloat(<?= json_encode($displayLoan['capital'] ?? 0) ?>) || 0;
        const loanInteresCapital = parseFloat(<?= json_encode($displayLoan['interes_capital'] ?? 0) ?>) || 0; // Total de interés a pagar

        const capitalPendiente = loanCapital - totalCapitalAmortizado;
        const interesPendiente = loanInteresCapital - totalInteresPagado; // Total interés debido - Total interés pagado

        document.getElementById('totalCapitalAmortizado').textContent = `$${totalCapitalAmortizado.toFixed(2)}`;
        document.getElementById('totalInteresPagado').textContent = `$${totalInteresPagado.toFixed(2)}`;
        document.getElementById('totalMoraPagada').textContent = `$${totalMoraPagada.toFixed(2)}`;
        document.getElementById('capitalPendiente').textContent = `$${capitalPendiente.toFixed(2)}`;
        document.getElementById('interesPendiente').textContent = `$${interesPendiente.toFixed(2)}`;
    }


    document.addEventListener('DOMContentLoaded', function() {
        const btnAdministrarVehiculo = document.getElementById('btnAdministrarVehiculo');
        const detallesContratoVehiculo = document.getElementById('detallesContratoVehiculo');
        const selectPlaca = document.getElementById('select_placa');

        // Nuevos elementos para préstamos
        const btnAdministrarPrestamo = document.getElementById('btnAdministrarPrestamo');
        const detallesPrestamo = document.getElementById('detallesPrestamo');
        const selectPrestamo = document.getElementById('select_prestamo');


        // Inicializar todas las secciones ocultas
        if (detallesContratoVehiculo) {
            detallesContratoVehiculo.style.display = 'none';
        }
        if (detallesPrestamo) {
            detallesPrestamo.style.display = 'none';
        }

        // Mostrar la sección adecuada si se cargó un contrato de vehículo automáticamente (una sola placa)
        <?php if ($datosContrato): ?>
            if (detallesContratoVehiculo) {
                detallesContratoVehiculo.style.display = 'block';
            }
            calcularTotales(); // Calcular totales de vehículo si se muestra
        <?php endif; ?>

        // Mostrar la sección adecuada si se cargó un préstamo automáticamente (un solo préstamo o seleccionado)
        <?php if ($datosPrestamoSeleccionado): ?>
            if (detallesPrestamo) {
                detallesPrestamo.style.display = 'block';
                // Ocultar detalles de vehículos si el préstamo está visible
                if (detallesContratoVehiculo) {
                    detallesContratoVehiculo.style.display = 'none';
                }
                calcularTotalesPrestamo(); // Calcular totales de préstamo si se muestra
            }
        <?php endif; ?>


        // Event listener para el botón "Administrar Vehículo"
        if (btnAdministrarVehiculo) {
            btnAdministrarVehiculo.addEventListener('click', function() {
                if (detallesContratoVehiculo.style.display === 'none') {
                    detallesContratoVehiculo.style.display = 'block';
                    // Ocultar detalles de préstamos si están visibles
                    if (detallesPrestamo) {
                        detallesPrestamo.style.display = 'none';
                    }
                    calcularTotales(); // Recalcular totales al mostrar la sección de vehículo
                } else {
                    detallesContratoVehiculo.style.display = 'none';
                }
            });
        }

        // Event listener para el botón "Administrar Préstamo"
        if (btnAdministrarPrestamo) {
            btnAdministrarPrestamo.addEventListener('click', function() {
                if (detallesPrestamo.style.display === 'none') {
                    detallesPrestamo.style.display = 'block';
                    // Ocultar detalles de vehículos si están visibles
                    if (detallesContratoVehiculo) {
                        detallesContratoVehiculo.style.display = 'none';
                    }
                    calcularTotalesPrestamo(); // Recalcular totales al mostrar la sección de préstamo
                } else {
                    detallesPrestamo.style.display = 'none';
                }
            });
        }


        // Event listener para el desplegable de placas
        if (selectPlaca) {
            selectPlaca.addEventListener('change', function() {
                const selectedPlaca = this.value;
                const dniOriginal = "<?= htmlspecialchars($dni_buscado) ?>";

                if (selectedPlaca) {
                    window.location.href = `administracion.php?selected_placa=${encodeURIComponent(selectedPlaca)}&dni_original=${encodeURIComponent(dniOriginal)}`;
                } else {
                    // Si se selecciona la opción "Seleccionar Placa", recargar solo con el DNI
                    window.location.href = `administracion.php?dni_original=${encodeURIComponent(dniOriginal)}`;
                }
            });
        }

        // NUEVO: Event listener para el desplegable de préstamos
        if (selectPrestamo) {
            selectPrestamo.addEventListener('change', function() {
                const selectedPrestamoId = this.value;
                const dniOriginal = "<?= htmlspecialchars($dni_buscado) ?>";

                if (selectedPrestamoId) {
                    window.location.href = `administracion.php?selected_prestamo_id=${encodeURIComponent(selectedPrestamoId)}&dni_original=${encodeURIComponent(dniOriginal)}`;
                } else {
                    // Si se selecciona la opción " -- Seleccionar Préstamo --", recargar solo con el DNI
                    window.location.href = `administracion.php?dni_original=${encodeURIComponent(dniOriginal)}`;
                }
            });
        }
    });
</script>

</body>
</html>