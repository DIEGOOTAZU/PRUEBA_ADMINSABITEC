<?php
// Incluir la conexión a la base de datos
require_once 'config/db.php';

// Obtener los datos pasados por GET
// Ahora obtenemos prestamo_id directamente del URL
$prestamo_id = isset($_GET['prestamo_id']) ? trim($_GET['prestamo_id']) : null;
$cliente_nombre_url = isset($_GET['cliente']) ? trim($_GET['cliente']) : null; // Guardamos el nombre del cliente de la URL por si acaso


// Inicializar variables para evitar warnings si no se encuentran datos
$cliente_data = null;
$prestamo_data = null;
$pagosExistentes = [];

// Verificar que el prestamo_id esté presente
if ($prestamo_id === null) {
    die("Error: No se ha proporcionado un ID de préstamo.");
}

// Realizar consultas
try {
    // 1. Obtener los datos del PRÉSTAMO específico usando prestamo_id
    $stmtPrestamo = $conn->prepare("SELECT * FROM prestamos WHERE id = :prestamo_id");
    $stmtPrestamo->execute([':prestamo_id' => $prestamo_id]);
    $prestamo_data = $stmtPrestamo->fetch(PDO::FETCH_ASSOC);

    // Si no se encuentra el préstamo, no tiene sentido seguir
    if (!$prestamo_data) {
        // Podrías redirigir o mostrar un mensaje de error diferente
        // Por ahora, simplemente saldremos y el HTML mostrará "No se encontraron datos..."
        // die("Error: Préstamo no encontrado."); // Puedes descomentar esta línea para depuración
    } else {
        // 2. Obtener los datos del CLIENTE usando el cliente_id del préstamo encontrado
        $stmtCliente = $conn->prepare("SELECT * FROM clientes WHERE id = :cliente_id");
        $stmtCliente->execute([':cliente_id' => $prestamo_data['cliente_id']]);
        $cliente_data = $stmtCliente->fetch(PDO::FETCH_ASSOC);
        
        // 3. Obtener los pagos guardados para este PRÉSTAMO específico
        $stmtPagos = $conn->prepare("SELECT * FROM detalle_pagos_prestamo WHERE prestamo_id = :prestamo_id");
        $stmtPagos->execute([':prestamo_id' => $prestamo_id]); // Usamos el prestamo_id directamente
        $pagosExistentes = $stmtPagos->fetchAll(PDO::FETCH_ASSOC); // Recuperamos *todas* las filas de pagos
    }

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
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-top: 1px solid #eee;
        }
        .total-row:first-child {
            border-top: none;
        }
        .total-row .label {
            font-weight: bold;
        }
        .text-danger {
            color: red;
        }
        .text-warning {
            color: orange;
        }
        .add-row-button {
            cursor: pointer;
            color: #007bff;
            text-decoration: underline;
            margin-top: 10px;
            display: inline-block;
        }
        .add-row-button:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
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

    <div class="main-content">
        <h2>Detalle de Pagos</h2>
        
        <h5>Cliente: <?= htmlspecialchars($cliente_data['nombres'] ?? $cliente_nombre_url) ?></h5>

<?php if ($prestamo_data): // Ahora verificamos si $prestamo_data no es null ?>
    <h5>Mensualidad: $<?= htmlspecialchars($prestamo_data['capital']) ?></h5>
    <h5>Fecha de Contrato: <?= htmlspecialchars($prestamo_data['fecha_prestamo']) ?></h5>
    <h5>Fecha de Pago: <?= htmlspecialchars($prestamo_data['vence']) ?></h5>
    <h5>Garantía: <?= htmlspecialchars($prestamo_data['garantia']) ?></h5> <h5>Interes %: <?= htmlspecialchars($prestamo_data['interes']) ?></h5>
    <h5>Numero de Cuotas: <?= htmlspecialchars($prestamo_data['tiempo']) ?></h5>
    <h5>Total Interes: <?= htmlspecialchars($prestamo_data['interes_capital']) ?></h5>
    <h5>Monto total a pagar: <?= htmlspecialchars($prestamo_data['interes_cobrado']) ?></h5>
<?php else: ?>
    <p>No se encontraron datos de préstamo para el ID: **<?= htmlspecialchars($prestamo_id) ?>**.</p>
<?php endif; ?>

<form id="formPagos" method="POST" action="guardar_pagos_prestamo.php">
    <input type="hidden" name="prestamo_id" value="<?= htmlspecialchars($prestamo_id) ?>" /> 
    <?php if ($cliente_data): ?>
        <input type="hidden" name="cliente_id" value="<?= htmlspecialchars($cliente_data['id']) ?>" />
    <?php endif; ?>
    
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
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this, null)">Eliminar</button>
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
            <td>
                <input type="number" step="0.01" name="deuda_mora[]" class="form-control" placeholder="Ej: 2" oninput="calcularMontoMora(this)">
            </td>
            <td>
                <input type="text" name="monto_mora[]" class="form-control" placeholder="Ej: 100.00" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this, null)">Eliminar</button>
            </td>
        `;
        tableBody.appendChild(newRow);
        // Recalcular totales después de agregar una fila
        calcularTotales(); 
    }


    // Función para eliminar una fila
    function eliminarFila(button, id) {
        if (confirm('¿Está seguro de eliminar esta fila?')) {
            const row = button.closest('tr'); // Obtén la fila más cercana al botón

            // Solo intentar eliminar de la base de datos si tiene un ID existente
            if (id !== null) {
                $.ajax({
                    url: 'eliminar_pago_prestamos.php',  // El archivo que ejecutará la eliminación
                    type: 'POST',
                    data: { id: id },  // Envia el ID al archivo PHP
                    success: function(response) {
                        if (response.trim() === 'success') { // Usar trim() por si hay espacios extra
                            row.remove(); // Si se eliminó con éxito, eliminamos la fila de la vista
                            calcularTotales(); // Recalcular totales después de eliminar
                        } else {
                            alert('Error al eliminar el registro: ' + response); // Mostrar respuesta del servidor
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error al comunicarse con el servidor: ' + status + ', ' + error);
                        console.error("Error AJAX eliminar:", xhr.responseText);
                    }
                });
            } else {
                // Si la fila no tiene ID (es nueva y no guardada), solo la eliminamos de la vista
                row.remove();
                calcularTotales(); // Recalcular totales después de eliminar
            }
        }
    }

    function toggleSubmenu(event) {
        event.preventDefault();
        const parent = event.target.closest('.has-submenu');
        parent.classList.toggle('active');
    }

    // Asegúrate de que esta función se llama cuando se carga la página y cuando cambian los inputs
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

        // Calcular Total Deuda (necesitas el monto total del préstamo para esto)
        // Usa la variable PHP prestamo_data['interes_cobrado'] si existe
        const montoTotalPrestamo = parseFloat(<?= json_encode($prestamo_data['interes_cobrado'] ?? 0) ?>);
        const totalDeuda = montoTotalPrestamo - totalCancelado;

        // Actualizar los campos en la vista
        document.getElementById('totalCancelado').textContent = `$${totalCancelado.toFixed(2)}`;
        document.getElementById('totalDeuda').textContent = `$${totalDeuda.toFixed(2)}`;
        document.getElementById('totalDeudaMora').textContent = `$${totalDeudaMora.toFixed(2)}`;
    }

    // Escucha cambios en los inputs después de que el DOM esté completamente cargado y también para filas agregadas dinámicamente
    document.addEventListener('DOMContentLoaded', () => {
        // Inicializar los toggle de submenú
        document.querySelectorAll('.submenu-toggle').forEach(item => {
            item.addEventListener('click', toggleSubmenu);
        });

        // Recalcular totales cuando cualquier input relevante cambia
        document.querySelector('#formPagos').addEventListener('input', (event) => {
            if (event.target.name === 'importe[]' || event.target.name === 'deuda_mora[]') {
                calcularTotales();
            }
        });
        
        // Calcular totales iniciales al cargar la página
        calcularTotales();
    });


    function calcularMontoMora(input) {
        const row = input.closest('tr'); // Obtiene la fila actual
        const deudaMora = parseFloat(input.value) || 0; // Obtiene el valor de "Deuda Mora"
        const montoMora = deudaMora * 50; // Calcula "Monto Mora" (asumiendo 50 por unidad de mora)
        const montoMoraInput = row.querySelector('input[name="monto_mora[]"]'); // Selecciona el campo de "Monto Mora"
        montoMoraInput.value = montoMora.toFixed(2); // Actualiza el valor en el campo
        calcularTotales(); // Recalcular totales inmediatamente después de calcular la mora
    }
</script>

</body>
</html>