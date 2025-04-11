<?php
// Incluir la conexión a la base de datos
require_once 'config/db.php';

// Obtener los datos pasados por GET
$cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : null;
$cliente_id = isset($_GET['cliente_id']) ? trim($_GET['cliente_id']) : null;

// Verificar que los parámetros estén presentes


// Realizar consulta para obtener los datos del cliente
try {
    // Aquí hacemos una consulta relacionada con el cliente, recuperando su nombre
    $stmtCliente = $conn->prepare("SELECT * FROM clientes WHERE id = :cliente_id");
    $stmtCliente->execute([':cliente_id' => $cliente_id]);
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);  // Esto devuelve un array con los datos del cliente
    
    // Consulta para obtener los datos del préstamo
    $stmtPrestamos = $conn->prepare("SELECT * FROM prestamos WHERE cliente_id = :cliente_id");
    $stmtPrestamos->execute([':cliente_id' => $cliente_id]);
    $prestamos = $stmtPrestamos->fetch(PDO::FETCH_ASSOC);  // Esto devuelve un array con los datos del préstamo

    // Obtener los pagos guardados para este cliente y préstamo
    $stmtPagos = $conn->prepare("SELECT * FROM detalle_pagos_prestamo WHERE prestamo_id = :prestamo_id");
    $stmtPagos->execute([':prestamo_id' => $prestamos['id']]);
    $pagosExistentes = $stmtPagos->fetchAll(PDO::FETCH_ASSOC); // Guardar todos los pagos en un array
} catch (PDOException $e) {
    die("Error al obtener los datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Pagos de Préstamos</title>
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
        .main-content {
            margin-left: 260px;
            padding: 20px;
        }
        .table-header {
            background-color: #007bff;
            color: white;
        }
        .text-success {
            color: green;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h3 class="text-center">Sabitec GPS</h3>
        <a href="index.php">Inicio</a>
        <div class="has-submenu">
            <a href="#" class="submenu-toggle">Contratos</a>
            <div class="submenu">
                <a href="administrar_contratos.php">Administrar Contratos</a>
            </div>
        </div>
        <div class="has-submenu">
            <a href="#" class="submenu-toggle">Servicios</a>
            <div class="submenu">
                <a href="consulta_pagos.php">Consulta de Pagos Vehículos</a>
                <a href="consulta_pagos_prestamos.php">Consulta de Pagos Préstamos</a>
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
        <h2>Detalle de Pagos</h2>
        
        <!-- Mostrar datos del préstamo -->
        <h5>Cliente: <?= htmlspecialchars($cliente['nombres']) ?></h5>

<?php if ($prestamos): ?>
    <h5>Mensualidad: $<?= htmlspecialchars($prestamos['capital']) ?></h5>
    <h5>Fecha de Contrato: <?= htmlspecialchars($prestamos['fecha_prestamo']) ?></h5>
    <h5>Fecha de Pago: <?= htmlspecialchars($prestamos['vence']) ?></h5>
    <h5>Monto de Prestamo: <?= htmlspecialchars($prestamos['garantia']) ?></h5>
    <h5>Interes %: <?= htmlspecialchars($prestamos['interes']) ?></h5>
    <h5>Numero de Cuotas: <?= htmlspecialchars($prestamos['tiempo']) ?></h5>
    <h5>Total Interes: <?= htmlspecialchars($prestamos['interes_capital']) ?></h5>
    <h5>Monto total a pagar: <?= htmlspecialchars($prestamos['interes_cobrado']) ?></h5>
<?php else: ?>
    <p>No se encontraron datos de préstamo para este cliente.</p>
<?php endif; ?>

<!-- Mostrar los pagos guardados -->
<form id="formPagos" method="POST" action="guardar_pagos_prestamo.php">
    <input type="hidden" name="cliente_id" value="<?= htmlspecialchars($cliente_id) ?>" />
    <input type="hidden" name="prestamo_id" value="<?= htmlspecialchars($prestamos['id']) ?>" />
    

    <table class="table table-bordered" >
        <thead class="table-header">
            <tr>
                <th>Efectivo o Banco</th>
                <th>Día de Pago</th>
                <th>Letra</th>
                <th>Importe</th>
                <th>Deuda Mora</th>
                <th>Monto Mora</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pagosExistentes)): ?>
                <?php foreach ($pagosExistentes as $pago): ?>
                    <tr data-id="<?= $pago['id'] ?>">
                        <td>
                            <select name="efectivo_banco[]" class="form-control">
                                <option value="EFECTIVO" <?= $pago['efectivo_o_banco'] === 'EFECTIVO' ? 'selected' : '' ?>>EFECTIVO</option>
                                <option value="BANCO" <?= $pago['efectivo_o_banco'] === 'BANCO' ? 'selected' : '' ?>>BANCO</option>
                            </select>
                        </td>
                        <td>
                            <input type="date" name="fecha_pago[]" class="form-control" value="<?= htmlspecialchars($pago['fecha_pago']) ?>">
                        </td>
                        <td>
                            <input type="text" name="letra[]" class="form-control" placeholder="Ej: 8/35" value="<?= htmlspecialchars($pago['letra']) ?>">
                        </td>
                        <td>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" step="0.01" name="importe[]" class="form-control" placeholder="Ej: 500.00" value="<?= htmlspecialchars($pago['importe']) ?>">
                            </div>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="deuda_mora[]" class="form-control" placeholder="Ej: 2" value="<?= htmlspecialchars($pago['deuda_mora'] ?? '0') ?>" oninput="calcularMontoMora(this)">
                        </td>
                        <td>
                            <input type="text" name="monto_mora[]" class="form-control" placeholder="Ej: 100.00" readonly value="<?= htmlspecialchars(($pago['deuda_mora'] ?? 0) * 50) ?>">
                        </td>
                        <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this, <?= $pago['id'] ?>)">Eliminar</button>

                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <!-- Fila vacía para nuevas entradas -->
            <tr>
                <td>
                    <select name="efectivo_banco[]" class="form-control">
                        <option value="">Seleccione</option>
                        <option value="EFECTIVO">EFECTIVO</option>
                        <option value="BANCO">BANCO</option>
                    </select>
                </td>
                <td>
                    <input type="date" name="fecha_pago[]" class="form-control">
                </td>
                <td>
                    <input type="text" name="letra[]" class="form-control" placeholder="Ej: 8/35">
                </td>
                <td>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                        </div>
                        <input type="number" step="0.01" name="importe[]" class="form-control" placeholder="Ej: 500.00">
                    </div>
                </td>
                <td>
                    <input type="number" step="0.01" name="deuda_mora[]" class="form-control" placeholder="Ej: 2" oninput="calcularMontoMora(this)">
                </td>
                <td>
                    <input type="text" name="monto_mora[]" class="form-control" placeholder="Ej: 100.00" readonly>
                </td>
                <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this, <?= $pago['id'] ?>)">Eliminar</button>


                </td>
            </tr>
        </tbody>
    </table>
    <div class="text-center">
        <span class="add-row-button" onclick="agregarFila()">➕ Agregar Fila</span>
    </div>
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

    <button type="submit" class="btn btn-success save-button mt-3">Guardar Cambios</button>
</form>

    </div>

<script>
    // Funciones de interacción de la tabla
    function agregarFila() {
        const tableBody = document.querySelector('tbody');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select name="efectivo_banco[]" class="form-control">
                    <option value="">Seleccione</option>
                    <option value="EFECTIVO">EFECTIVO</option>
                    <option value="BANCO">BANCO</option>
                </select>
            </td>
            <td>
                <input type="date" name="fecha_pago[]" class="form-control">
            </td>
            <td>
                <input type="text" name="letra[]" class="form-control" placeholder="Ej: 8/35">
            </td>
            <td>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">$</span>
                    </div>
                    <input type="number" step="0.01" name="importe[]" class="form-control" placeholder="Ej: 500.00">
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this, null)">Eliminar</button>
            </td>
        `;
        tableBody.appendChild(newRow);
    }


    // Función para eliminar una fila
    function eliminarFila(button, id) {
        if (confirm('¿Está seguro de eliminar esta fila?')) {
            const row = button.closest('tr'); // Obtén la fila más cercana al botón

            $.ajax({
                url: 'eliminar_pago_prestamos.php',  // El archivo que ejecutará la eliminación
                type: 'POST',
                data: { id: id },  // Envia el ID al archivo PHP
                success: function(response) {
                    if (response === 'success') {
                        row.remove(); // Si se eliminó con éxito, eliminamos la fila de la vista
                    } else {
                        alert('Error al eliminar el registro.');
                    }
                },
                error: function() {
                    alert('Error al comunicarse con el servidor.');
                }
            });
        }
    }

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
    const montoTotal = parseFloat(<?= $cobranza['monto_total'] ?? 0 ?>);
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


</script>

</body>
</html>
