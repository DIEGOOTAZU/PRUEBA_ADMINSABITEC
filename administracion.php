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
$pagos = [];
$mensajeError = "";
$search_term_buscado = ""; // Variable para mantener el término de búsqueda en el input
$cliente_dni_ruc = "N/A"; // Variable para almacenar el DNI/RUC del cliente
$datosPrestamoExistente = false; // Bandera para controlar la visibilidad del botón de préstamo
$cliente_id_para_prestamo = null; // Variable para almacenar el cliente_id para el botón de préstamo
$placas_encontradas = []; // Almacenará múltiples placas si se busca por DNI
$placa_actualmente_mostrada = ""; // Para saber qué placa se está mostrando

// --- Lógica para obtener todas las placas y DNI/RUC para el datalist (sugerencias) ---
$all_placas_dni = [];
try {
    $stmt_placas = $conn->query("SELECT placa FROM data_cobranzas");
    while ($row = $stmt_placas->fetch(PDO::FETCH_ASSOC)) {
        $all_placas_dni[] = $row['placa'];
    }

    $stmt_dni = $conn->query("SELECT documento FROM clientes WHERE documento IS NOT NULL AND documento != ''");
    while ($row = $stmt_dni->fetch(PDO::FETCH_ASSOC)) {
        if (!in_array($row['documento'], $all_placas_dni)) { // Evitar duplicados si DNI es igual a placa
            $all_placas_dni[] = $row['documento'];
        }
    }
    sort($all_placas_dni); // Opcional: ordenar alfabéticamente
} catch (PDOException $e) {
    // Manejar el error si la consulta falla
    error_log("Error al obtener placas y DNI/RUC para datalist: " . $e->getMessage());
}
// --- Fin Lógica datalist ---

// Si se envió un término de búsqueda desde el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_term'])) {
    $search_term = trim($_POST['search_term']);
    $search_term_buscado = $search_term;

    // 1. Intentar buscar como PLACA directamente
    try {
        $stmt_placa_directa = $conn->prepare("SELECT id, cliente, placa, mensualidad_real, fecha_pago, fecha_contrato, telefono, cliente_id, letra, inicial, monto_total FROM data_cobranzas WHERE placa = :placa");
        $stmt_placa_directa->execute([':placa' => $search_term]);
        $datosContrato = $stmt_placa_directa->fetch(PDO::FETCH_ASSOC);

        if ($datosContrato) {
            $placa_actualmente_mostrada = $datosContrato['placa']; // Se encontró por placa, se muestra esta
        } else {
            // 2. Si no se encuentra por placa, intentar buscar como DNI/RUC
            $stmt_cliente_by_dni = $conn->prepare("SELECT id FROM clientes WHERE documento = :documento");
            $stmt_cliente_by_dni->execute([':documento' => $search_term]);
            $cliente_data = $stmt_cliente_by_dni->fetch(PDO::FETCH_ASSOC);

            if ($cliente_data) {
                $cliente_id_buscado = $cliente_data['id'];

                // Encontrar todas las placas asociadas a este cliente_id
                $stmt_placas_cliente = $conn->prepare("SELECT placa FROM data_cobranzas WHERE cliente_id = :cliente_id");
                $stmt_placas_cliente->execute([':cliente_id' => $cliente_id_buscado]);
                $placas_encontradas = $stmt_placas_cliente->fetchAll(PDO::FETCH_COLUMN);

                if (count($placas_encontradas) === 1) {
                    // Si solo hay una placa, cargar sus datos automáticamente
                    $placa_unica = $placas_encontradas[0];
                    $stmt = $conn->prepare("SELECT id, cliente, placa, mensualidad_real, fecha_pago, fecha_contrato, telefono, cliente_id, letra, inicial, monto_total FROM data_cobranzas WHERE placa = :placa");
                    $stmt->execute([':placa' => $placa_unica]);
                    $datosContrato = $stmt->fetch(PDO::FETCH_ASSOC);
                    $placa_actualmente_mostrada = $placa_unica;
                } elseif (count($placas_encontradas) > 1) {
                    // Si hay múltiples placas, la UI las mostrará para selección
                    $mensajeError = "Se encontraron múltiples placas para el DNI/RUC. Por favor, selecciona una.";
                } else {
                    $mensajeError = "No se encontraron contratos asociados a este DNI/RUC.";
                }
            } else {
                $mensajeError = "No se encontró un contrato ni un cliente con la placa o DNI/RUC especificado.";
            }
        }
    } catch (PDOException $e) {
        $mensajeError = "Error al obtener los datos: " . $e->getMessage();
    }
}
// Si se seleccionó una placa desde el desplegable (viene por GET)
elseif (isset($_GET['selected_placa'])) {
    $selected_placa = trim($_GET['selected_placa']);
    $search_term_buscado = $selected_placa; // Para mantener la placa en el input si se recarga así
    $placa_actualmente_mostrada = $selected_placa;

    try {
        $stmt = $conn->prepare("SELECT id, cliente, placa, mensualidad_real, fecha_pago, fecha_contrato, telefono, cliente_id, letra, inicial, monto_total FROM data_cobranzas WHERE placa = :placa");
        $stmt->execute([':placa' => $selected_placa]);
        $datosContrato = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $mensajeError = "Error al obtener los datos de la placa seleccionada: " . $e->getMessage();
    }
}


// Si se encontró un contrato (ya sea por POST o GET con placa seleccionada), cargar datos adicionales
if ($datosContrato) {
    // Si la placa_actualmente_mostrada está vacía (ej. al cargar el contrato por DNI y hay solo una placa)
    if (empty($placa_actualmente_mostrada)) {
        $placa_actualmente_mostrada = $datosContrato['placa'];
    }

    // Obtener pagos asociados
    $stmtPagos = $conn->prepare("SELECT * FROM detalle_pagos WHERE data_cobranza_id = :id ORDER BY fecha_pago ASC");
    $stmtPagos->execute([':id' => $datosContrato['id']]);
    $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

    // Obtener datos del vehículo
    $stmtVehiculo = $conn->prepare("SELECT * FROM vehiculos WHERE placa = :placa");
    $stmtVehiculo->execute([':placa' => $datosContrato['placa']]);
    $datosVehiculo = $stmtVehiculo->fetch(PDO::FETCH_ASSOC);

    // Obtener DNI/RUC del cliente desde la tabla 'clientes' si el cliente_id está disponible
    if (isset($datosContrato['cliente_id']) && $datosContrato['cliente_id'] > 0) {
        $cliente_id_para_prestamo = $datosContrato['cliente_id'];

        $stmtClienteDni = $conn->prepare("SELECT documento FROM clientes WHERE id = :cliente_id");
        $stmtClienteDni->execute([':cliente_id' => $datosContrato['cliente_id']]);
        $clienteDniData = $stmtClienteDni->fetch(PDO::FETCH_ASSOC);
        if ($clienteDniData) {
            $cliente_dni_ruc = $clienteDniData['documento'];
        }

        // Verificar si existen préstamos para este cliente en la tabla 'prestamos'
        $stmtPrestamoCount = $conn->prepare("SELECT COUNT(*) AS count FROM prestamos WHERE cliente_id = :cliente_id");
        $stmtPrestamoCount->execute([':cliente_id' => $cliente_id_para_prestamo]);
        $prestamoCountResult = $stmtPrestamoCount->fetch(PDO::FETCH_ASSOC);

        if ($prestamoCountResult['count'] > 0) {
            $datosPrestamoExistente = true;
        }
    }
}

// --- DEBUGGING TEMPORAL (Borra esto en producción) ---
// echo "<pre>";
// echo "DEBUG INFO:\n";
// echo "search_term_buscado: " . htmlspecialchars($search_term_buscado) . "\n";
// echo "datosContrato: " . print_r($datosContrato, true);
// echo "placas_encontradas: " . print_r($placas_encontradas, true);
// echo "placa_actualmente_mostrada: " . htmlspecialchars($placa_actualmente_mostrada) . "\n";
// echo "cliente_id_para_prestamo: " . htmlspecialchars($cliente_id_para_prestamo ?? 'N/A') . "\n";
// echo "datosPrestamoExistente: " . ($datosPrestamoExistente ? 'true' : 'false') . "\n";
// echo "mensajeError: " . htmlspecialchars($mensajeError) . "\n";
// echo "</pre>";
// --- FIN DEBUGGING TEMPORAL ---

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
            <label for="search_term">Ingrese Placa o DNI/RUC:</label>
            <input type="text" name="search_term" id="search_term" class="form-control" placeholder="Ej: ABC-123 o 12345678" list="suggestions" value="<?= htmlspecialchars($search_term_buscado) ?>" required>
            <datalist id="suggestions">
                <?php foreach ($all_placas_dni as $item): ?>
                    <option value="<?= htmlspecialchars($item) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <button type="submit" class="btn btn-primary">Buscar</button>

        <?php if ($datosContrato): // Mostrar el botón "Administrar Vehículo" solo si se encontró un contrato ?>
            <button type="button" class="btn btn-info" id="btnAdministrarVehiculo">Administrar Vehículo</button>
        <?php endif; ?>

        <?php if ($datosPrestamoExistente): // Mostrar el botón "Administrar Préstamo" solo si hay préstamos para el cliente ?>
            <button type="button" class="btn btn-warning" id="btnAdministrarPrestamo">Administrar Préstamo</button>
        <?php endif; ?>
    </form>

    <?php if ($mensajeError): ?>
        <div class="alert alert-danger"><?= $mensajeError ?></div>
    <?php endif; ?>

    <?php if (!empty($placas_encontradas) && count($placas_encontradas) > 1): // Mostrar el desplegable de placas si hay varias ?>
        <div class="form-group mt-3" id="placaSelectionContainer">
            <label for="select_placa">Seleccione una Placa:</label>
            <select id="select_placa" class="form-control">
                <option value="">-- Seleccionar Placa --</option>
                <?php foreach ($placas_encontradas as $placa_opt): ?>
                    <option value="<?= htmlspecialchars($placa_opt) ?>" <?= ($placa_opt === $placa_actualmente_mostrada) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($placa_opt) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>


    <div id="detallesContratoVehiculo" style="display: none;">
        <?php if ($datosContrato): // Asegurarse de que los datos estén disponibles para mostrar ?>
            <div class="details-container">
                <div class="details-section">
                    <h4>Detalles del Contrato (Placa: <?= htmlspecialchars($placa_actualmente_mostrada) ?>)</h4>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($datosContrato['cliente'] ?? 'N/A') ?></p>
                    <p><strong>DNI/RUC:</strong> <?= htmlspecialchars($cliente_dni_ruc) ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($datosContrato['telefono'] ?? 'N/A') ?></p>
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
                                <td><?= htmlspecialchars($pago['deuda_mora'] ?? '0') ?></td>
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
</div>

<script>
    function toggleSubmenu(event) {
        event.preventDefault();
        const parent = event.target.closest('.has-submenu');
        parent.classList.toggle('active');
    }

    function calcularTotales() {
        const importes = document.querySelectorAll('input[name="importe[]"]');
        const montoMoras = document.querySelectorAll('input[name="monto_mora[]"]');
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

        // Asegúrate de que $datosContrato['monto_total'] esté disponible antes de usarlo
        const montoTotal = parseFloat(<?= json_encode($datosContrato['monto_total'] ?? 0) ?>) || 0;
        const totalDeuda = montoTotal - totalCancelado;

        document.getElementById('totalCancelado').textContent = `$${totalCancelado.toFixed(2)}`;
        document.getElementById('totalDeuda').textContent = `$${totalDeuda.toFixed(2)}`;
        document.getElementById('totalDeudaMora').textContent = `$${totalDeudaMora.toFixed(2)}`;
    }

    // *** LÓGICA PARA MOSTRAR/OCULTAR DETALLES CON BOTONES Y REDIRIGIR ***
    document.addEventListener('DOMContentLoaded', function() {
        const btnAdministrarVehiculo = document.getElementById('btnAdministrarVehiculo');
        const detallesContratoVehiculo = document.getElementById('detallesContratoVehiculo');
        const btnAdministrarPrestamo = document.getElementById('btnAdministrarPrestamo');
        const selectPlaca = document.getElementById('select_placa'); // Nuevo: el select de placas

        // Ocultar detalles del contrato/vehículo por defecto al cargar la página
        if (detallesContratoVehiculo) {
            detallesContratoVehiculo.style.display = 'none';
        }

        // Si hay un contrato cargado al inicio (después de un POST o GET), mostrarlo
        <?php if ($datosContrato): ?>
            detallesContratoVehiculo.style.display = 'block'; // O 'flex' si usas flexbox
            calcularTotales(); // Recalcular totales al mostrar
        <?php endif; ?>

        // Event listener para el botón "Administrar Vehículo" (para alternar visibilidad)
        if (btnAdministrarVehiculo) {
            btnAdministrarVehiculo.addEventListener('click', function() {
                if (detallesContratoVehiculo.style.display === 'none') {
                    detallesContratoVehiculo.style.display = 'block';
                    calcularTotales();
                } else {
                    detallesContratoVehiculo.style.display = 'none';
                }
            });
        }

        // Event listener para el botón "Administrar Préstamo"
        if (btnAdministrarPrestamo) {
            btnAdministrarPrestamo.addEventListener('click', function() {
                const clienteId = <?= json_encode($cliente_id_para_prestamo) ?>;
                if (clienteId) {
                    window.location.href = `administrar_prestamo.php?cliente_id=${encodeURIComponent(clienteId)}`;
                } else {
                    alert('No se pudo obtener el ID del cliente para administrar préstamos. Asegúrese de que el contrato tenga un cliente_id válido asociado.');
                }
            });
        }

        // Nuevo: Event listener para el desplegable de selección de placa
        if (selectPlaca) {
            selectPlaca.addEventListener('change', function() {
                const selectedPlaca = this.value;
                if (selectedPlaca) {
                    // Recargar la página con la placa seleccionada en la URL
                    window.location.href = `administracion.php?selected_placa=${encodeURIComponent(selectedPlaca)}`;
                }
            });
        }
    });
</script>

</body>
</html>