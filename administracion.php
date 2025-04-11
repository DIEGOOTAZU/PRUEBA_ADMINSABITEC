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

// Si se envió una placa desde el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placa = trim($_POST['placa']);

    try {
        // Buscar los datos del contrato por placa
        $stmt = $conn->prepare("SELECT * FROM data_cobranzas WHERE placa = :placa");
        $stmt->execute([':placa' => $placa]);
        $datosContrato = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($datosContrato) {
            // Redirigir a sí mismo con el parámetro GET
            header("Location: administracion.php?placa=" . urlencode($placa));
            exit;
        } else {
            $mensajeError = "No se encontró un contrato con la placa especificada.";
        }
    } catch (PDOException $e) {
        $mensajeError = "Error al obtener los datos: " . $e->getMessage();
    }
}



if (isset($_GET['placa'])) {
    $placa = trim($_GET['placa']);

    try {
        // Buscar los datos del contrato por placa
        $stmt = $conn->prepare("SELECT * FROM data_cobranzas WHERE placa = :placa");
        $stmt->execute([':placa' => $placa]);
        $datosContrato = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($datosContrato) {
            // Obtener los pagos asociados al contrato
            $stmtPagos = $conn->prepare("SELECT * FROM detalle_pagos WHERE data_cobranza_id = :id");
            $stmtPagos->execute([':id' => $datosContrato['id']]);
            $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

            // Obtener los datos del vehículo asociado
            $stmtVehiculo = $conn->prepare("SELECT * FROM vehiculos WHERE placa = :placa");
            $stmtVehiculo->execute([':placa' => $placa]);
            $datosVehiculo = $stmtVehiculo->fetch(PDO::FETCH_ASSOC);
        } else {
            $mensajeError = "No se encontró un contrato con la placa especificada.";
        }
    } catch (PDOException $e) {
        $mensajeError = "Error al obtener los datos: " . $e->getMessage();
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
    </style>
</head>
<body>

<!-- Sidebar -->
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

<!-- Main Content -->
<div class="main-content">
    <h2>Administración</h2>

    <!-- Formulario de búsqueda -->
    <form method="POST" action="administracion.php" class="mb-4">
    <div class="form-group">
        <label for="placa">Ingrese la Placa:</label>
        <input type="text" name="placa" id="placa" class="form-control" placeholder="Ej: ABC-123" value="<?= htmlspecialchars($_GET['placa'] ?? '') ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Buscar</button>
    <?php if ($datosContrato): ?>
        <button type="button" class="btn btn-secondary" onclick="redirigirEditar()">Editar</button>
    <?php endif; ?>
</form>



    <?php if ($mensajeError): ?>
        <div class="alert alert-danger"><?= $mensajeError ?></div>
    <?php endif; ?>

    <?php if ($datosContrato): ?>
        <!-- Contenedor de detalles -->
        <div class="details-container">
            <!-- Detalles del contrato -->
            <div class="details-section">
                <h4>Detalles del Contrato</h4>
                <p><strong>Cliente:</strong> <?= htmlspecialchars($datosContrato['cliente']) ?></p>
                <p><strong>Fecha de Contrato:</strong> <?= htmlspecialchars($datosContrato['fecha_contrato']) ?></p>
                <p><strong>Fecha de Pago:</strong> <?= htmlspecialchars($datosContrato['fecha_pago']) ?></p>
                <p><strong>Letra:</strong> <?= htmlspecialchars($datosContrato['letra']) ?> meses</p>
                <p><strong>Inicial:</strong> $<?= htmlspecialchars($datosContrato['inicial']) ?></p>
                <p><strong>Mensualidad Real:</strong> $<?= htmlspecialchars($datosContrato['mensualidad_real']) ?></p>
                <p><strong>Monto Total:</strong> $<?= htmlspecialchars($datosContrato['monto_total']) ?></p>
            </div>
            <!-- Información del vehículo -->
<div class="details-section">
    <h4>Información del Vehículo</h4>
    <?php if ($datosVehiculo): ?>
        <p><strong>Placa:</strong> <?= htmlspecialchars($datosVehiculo['placa']) ?></p>
        <p><strong>Marca:</strong> <?= htmlspecialchars($datosVehiculo['marca']) ?></p>
        <p><strong>Modelo:</strong> <?= htmlspecialchars($datosVehiculo['modelo']) ?></p>
        <p><strong>Color:</strong> <?= htmlspecialchars($datosVehiculo['color']) ?></p>
        <p><strong>Año:</strong> <?= htmlspecialchars($datosVehiculo['anio']) ?></p>
    <?php else: ?>
        <p>No se encontraron datos del vehículo para la placa especificada.</p>
    <?php endif; ?>
</div>


    
        </div>

        <!-- Mostrar tabla de pagos -->
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
    <?php foreach ($pagos as $index => $pago): ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($pago['efectivo_o_banco']) ?></td>
            <td><?= htmlspecialchars($pago['fecha_pago']) ?></td>
            <td><?= htmlspecialchars($pago['letra']) ?></td>
            <td>
                <input type="text" name="importe[]" class="form-control" value="<?= htmlspecialchars($pago['importe']) ?>" readonly>
            </td>
            <td><?= htmlspecialchars($pago['deuda_mora']) ?></td>
            <td>
                <input type="text" name="monto_mora[]" class="form-control" value="<?= htmlspecialchars(($pago['deuda_mora'] ?? 0) * 50) ?>" readonly>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

        </table>
        <div class="summary mt-4">
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
</div>
    <?php endif; ?>
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

        // Sumar los importes (Total Cancelado)
        importes.forEach(input => {
            const valor = parseFloat(input.value) || 0;
            totalCancelado += valor;
        });

        // Sumar los montos de mora (Deuda por Mora)
        montoMoras.forEach(input => {
            const valor = parseFloat(input.value) || 0;
            totalDeudaMora += valor;
        });

        // Calcular Total Deuda
        const montoTotal = parseFloat(<?= $datosContrato['monto_total'] ?? 0 ?>) || 0;
        const totalDeuda = montoTotal - totalCancelado;

        // Actualizar los campos en la vista
        document.getElementById('totalCancelado').textContent = `$${totalCancelado.toFixed(2)}`;
        document.getElementById('totalDeuda').textContent = `$${totalDeuda.toFixed(2)}`;
        document.getElementById('totalDeudaMora').textContent = `$${totalDeudaMora.toFixed(2)}`;
    }

    // Escucha cambios en los importes y montos de mora
    document.querySelectorAll('input[name="importe[]"]').forEach(input => {
        input.addEventListener('input', calcularTotales);
    });
    document.querySelectorAll('input[name="monto_mora[]"]').forEach(input => {
        input.addEventListener('input', calcularTotales);
    });

    // Calcular totales iniciales
    calcularTotales();

    function calcularMontoMora(input) {
        const row = input.closest('tr'); // Obtiene la fila actual
        const deudaMora = parseFloat(input.value) || 0; // Obtiene el valor de "Deuda Mora"
        const montoMora = deudaMora * 50; // Calcula "Monto Mora"
        const montoMoraInput = row.querySelector('input[name="monto_mora[]"]'); // Selecciona el campo de "Monto Mora"
        montoMoraInput.value = montoMora.toFixed(2); // Actualiza el valor en el campo
    }


    function redirigirEditar() {
    const cliente = "<?= htmlspecialchars($datosContrato['cliente'] ?? '') ?>";
    const placa = "<?= htmlspecialchars($datosContrato['placa'] ?? '') ?>";

    if (!cliente || !placa) {
        alert('No hay datos disponibles para redirigir.');
        return;
    }

    // Redirige con los parámetros del cliente y la placa
    window.location.href = `consultar_fechas.php?cliente=${encodeURIComponent(cliente)}&placa=${encodeURIComponent(placa)}`;
}


</script>

</body>
</html>
