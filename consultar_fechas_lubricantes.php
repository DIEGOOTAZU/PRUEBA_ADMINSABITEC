<?php
require_once 'config/db.php';

$cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : null;
$placa = isset($_GET['placa']) ? trim($_GET['placa']) : null;

if (!$cliente || !$placa) {
    die(json_encode(['error' => 'ID del cliente o placa no especificados.']));
}

try {
    // Consulta para obtener TODOS los registros de lubricantes para la placa específica
    $queryLubricantes = $conn->prepare("SELECT * FROM lubricantes WHERE placa = ?");
    $queryLubricantes->execute([$placa]);
    $filasLubricantes = $queryLubricantes->fetchAll(PDO::FETCH_ASSOC);

    if (empty($filasLubricantes)) {
        die(json_encode(['error' => 'No se encontraron registros de lubricantes para la placa especificada.']));
    }

} catch (PDOException $e) {
    die(json_encode(['error' => 'Error al obtener los datos: ' . $e->getMessage()]));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Pagos Lubricantes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
        .sidebar .submenu a {
            font-size: 14px;
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
            gap: 20pxF;
        }
        .details-section {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
       .month-table {
            margin-top: 20px;
        }
        .table-header {
            background-color: #007bff;
            color: white;
        }
        .add-row-button {
            cursor: pointer;
            color: #007bff;
            font-size: 20px;
        }

        .summary {
        text-align: left; /* Mantiene todo alineado a la izquierda */
        font-size: 1rem; /* Tamaño uniforme para todos */
    }
    .total-row {
        display: flex;
        align-items: center;
        margin-bottom: 8px; /* Espaciado entre filas */
    }
    .label {
        font-weight: bold;
        margin-right: 10px; /* Espaciado entre el texto y el número */
        color: #495057;
        min-width: 120px; /* Asegura una alineación uniforme */
    }
    .value {
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 4px;
    }
    .text-success {
        background-color: #d4edda; /* Fondo verde claro */
        color: #155724;
    }
    .text-danger {
        background-color: #f8d7da; /* Fondo rojo claro */
        color: #721c24;
        margin-left: 4px; /* Ajuste para nivelar con el verde */
    }
    .estado-select {
    font-weight: bold;
    color: white;
}
.estado-select option[value="PAGADO"] {
    background-color: green;
    color: white;
}
.estado-select option[value="DEBE"] {
    background-color: red;
    color: white;
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
<div class="details-container">
<div class="details-section">
    <h2>Detalle de Pagos de Lubricantes</h2>
    <h5>Cliente: <?= htmlspecialchars($cliente) ?></h5>
    <h5>Placa: <?= htmlspecialchars($placa) ?></h5>
    <h5>Monto Total: <span id="montoTotalCalculado">S/ 0.00</span></h5>
</div>

    <div class="details-section">

        <h2>SERVICIOS / PRODUCTOS</h2>
        <h5>Cambio aceite y grado: <?= htmlspecialchars($lubricante['cambio_aceite_grado'] ?? 'N/A') ?></h5>
        <h5>F/Aire: <?= htmlspecialchars($lubricante['f_aire'] ?? 'N/A') ?></h5>
        <h5>F/Aceite: <?= htmlspecialchars($lubricante['f_aceite'] ?? 'N/A') ?></h5>
        <h5>Lav de motor:<?= htmlspecialchars($lubricante['lav_motor'] ?? '0.00') ?></h5>
        <h5>Bujias: <?= htmlspecialchars($lubricante['bujias'] ?? 'N/A') ?></h5>
        <h5>Hidrolina: <?= htmlspecialchars($lubricante['hidrolina'] ?? 'N/A') ?></h5>
    </div>
</div>
    <form id="pagosForm" action="guardar_pagos_lubricante.php" method="POST">
        <input type="hidden" name="id_cliente" value="<?= htmlspecialchars($cliente) ?>">
        <input type="hidden" name="placa" value="<?= htmlspecialchars($placa) ?>">
        

        <table class="table table-bordered">
        <thead class="thead-dark text-center">
        <tr>
                        <th>Fecha</th>
                        <th>Cambio Aceite</th>
                        <th>F. Aire</th>
                        <th>F. Aceite</th>
                        <th>Rev. Motor</th>
                        <th>Bujías</th>
                        <th>Hidrolina</th>
                        <th>Monto</th>
                        <th>Forma de Pago</th>
                        <th>Observación</th>
                        <th>Estado</th>
                    </tr>
        </thead>
        <tbody>
            <?php foreach ($filasLubricantes as $fila): ?>
                <tr class="text-center">
                     <input type="hidden" name="id_detalle[]" value="<?= htmlspecialchars($fila['id']) ?>">
                    <td><?= htmlspecialchars($fila['fecha']) ?></td>
                    <td><?= htmlspecialchars($fila['cambio_aceite_grado']) ?></td>
                    <td><?= htmlspecialchars($fila['f_aire']) ?></td>
                    <td><?= htmlspecialchars($fila['f_aceite']) ?></td>
                    <td><?= htmlspecialchars($fila['lav_motor']) ?></td>
                    <td><?= htmlspecialchars($fila['bujias']) ?></td>
                    <td><?= htmlspecialchars($fila['hidrolina']) ?></td>
                    <td><?= number_format($fila['monto'], 2) ?></td>
                    <td><?= htmlspecialchars($fila['forma_pago']) ?></td>
                    <td><input type="text" name="observacion[]" class="form-control" value="<?= htmlspecialchars($fila['observacion'] ?? '') ?>"></td>

                    <td>
        <?php
            $estado = $fila['estado_pago'] ?? 'DEBE';
            $color = $estado === 'PAGADO' ? 'background-color: green; color: white;' : 'background-color: red; color: white;';
        ?>
        <select name="estado_pago[]" class="form-control estado-select" style="<?= $color ?>">
            <option value="PAGADO" <?= $estado === 'PAGADO' ? 'selected' : '' ?>>PAGADO</option>
            <option value="DEBE" <?= $estado === 'DEBE' ? 'selected' : '' ?>>DEBE</option>
        </select>
    </td>


                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

        
        <div class="summary">
            <div><span class="label">Total Cancelado:</span><span id="totalCancelado" class="text-success">S/ 0.00</span></div>
            <div><span class="label">Total Deuda:</span><span id="totalDeuda" class="text-danger">S/ 0.00</span></div>
            <div><span class="label">Deuda por Mora:</span><span id="totalDeudaMora" class="text-warning">S/ 0.00</span></div>
        </div>

        <button type="submit" class="btn btn-success mt-3">Guardar Cambios</button>
    </form>
</div>

<script>

     function calcularMontoTotal() {
        let total = 0;
        const montoColumnas = document.querySelectorAll('table.table-bordered tbody tr td:nth-child(8)'); // Selecciona la 8ª columna (Monto)

        montoColumnas.forEach(function(columna) {
            const monto = parseFloat(columna.textContent);
            if (!isNaN(monto)) {
                total += monto;
            }
        });

        document.getElementById('montoTotalCalculado').textContent = 'S/ ' + total.toFixed(2);
    }

    // Llama a la función para calcular el total una vez que la página se carga
    window.onload = calcularMontoTotal;
    
    function agregarFila() {
        const tbody = document.querySelector('#pagosTable tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><select name="efectivo_banco[]" class="form-control"><option value="">Seleccione</option><option value="EFECTIVO">EFECTIVO</option><option value="BANCO">BANCO</option></select></td>
            <td><input type="date" name="fecha_pago[]" class="form-control"></td>
            <td><input type="text" name="letra[]" class="form-control"></td>
            <td><input type="number" name="importe[]" class="form-control" step="0.01"></td>
            <td><input type="number" name="deuda_mora[]" class="form-control" step="0.01" oninput="calcularMontoMora(this)"></td>
            <td><input type="text" name="monto_mora[]" class="form-control" readonly></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this, null)">Eliminar</button></td>
        `;
        tbody.appendChild(tr);
    }

    function eliminarFila(button, id) {
        const row = button.closest('tr');
        if (id) {
            $.post('eliminar_fecha_lubricante.php', { id: id }, function(response) {
                if (response === 'success') row.remove();
                else alert('Error al eliminar el registro.');
            });
        } else {
            row.remove();
        }
    }

    function calcularMontoMora(input) {
        const row = input.closest('tr');
        const mora = parseFloat(input.value) || 0;
        row.querySelector('input[name="monto_mora[]"]').value = (mora * 50).toFixed(2);
        calcularTotales();
    }

    function calcularTotales() {
        let total = 0, moraTotal = 0;
        document.querySelectorAll('input[name="importe[]"]').forEach(input => total += parseFloat(input.value) || 0);
        document.querySelectorAll('input[name="monto_mora[]"]').forEach(input => moraTotal += parseFloat(input.value) || 0);
        const totalContrato = parseFloat(<?= $lubricante['monto'] ?? 0 ?>);
        document.getElementById('totalCancelado').textContent = `S/ ${total.toFixed(2)}`;
        document.getElementById('totalDeuda').textContent = `S/ ${(totalContrato - total).toFixed(2)}`;
        document.getElementById('totalDeudaMora').textContent = `S/ ${moraTotal.toFixed(2)}`;
    }

    document.querySelectorAll('input[name="importe[]"], input[name="monto_mora[]"]').forEach(input => {
        input.addEventListener('input', calcularTotales);
    });

    calcularTotales();


    function actualizarColoresEstado() {
    document.querySelectorAll('.estado-select').forEach(select => {
        if (select.value === 'PAGADO') {
            select.style.backgroundColor = 'green';
            select.style.color = 'white';
        } else if (select.value === 'DEBE') {
            select.style.backgroundColor = 'red';
            select.style.color = 'white';
        } else {
            select.style.backgroundColor = '';
            select.style.color = '';
        }
    });
}

document.querySelectorAll('.estado-select').forEach(select => {
    select.addEventListener('change', actualizarColoresEstado);
});

actualizarColoresEstado();

</script>

</body>
</html>