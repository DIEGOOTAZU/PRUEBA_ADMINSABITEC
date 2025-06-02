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

// Procesar el formulario al enviar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['editar_contrato'])) {
    $placa = $_POST['placa'];
    $cliente_nombre = $_POST['cliente_nombre'];
    $telefono = $_POST['telefono'];
    $mensualidad_real = $_POST['mensualidad_real'];
    $letra = $_POST['letra'];
    $inicial = $_POST['inicial'];
    $monto_total = $mensualidad_real * $letra;
    $fecha_pago = $_POST['fecha_pago'];
    $fecha_contrato = $_POST['fecha_contrato'];

    try {
        $stmt = $conn->prepare("INSERT INTO data_cobranzas (placa, cliente, telefono, mensualidad_real, letra, inicial, monto_total, fecha_pago, fecha_contrato)
            VALUES (:placa, :cliente, :telefono, :mensualidad_real, :letra, :inicial, :monto_total, :fecha_pago, :fecha_contrato)");
        $stmt->execute([
            ':placa' => $placa,
            ':cliente' => $cliente_nombre,
            ':telefono' => $telefono,
            ':mensualidad_real' => $mensualidad_real,
            ':letra' => $letra,
            ':inicial' => $inicial,
            ':monto_total' => $monto_total,
            ':fecha_pago' => $fecha_pago,
            ':fecha_contrato' => $fecha_contrato,
        ]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $error = "Error al guardar los datos: " . $e->getMessage();
    }
}

// Procesar el formulario de edición al enviar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_contrato'])) {
    $id = $_POST['id'];
    $placa = $_POST['placa'];
    $cliente_nombre = $_POST['cliente_nombre'];
    $telefono = $_POST['telefono'];
    $mensualidad_real = $_POST['mensualidad_real'];
    $letra = $_POST['letra'];
    $inicial = $_POST['inicial'];
    $monto_total = $mensualidad_real * $letra;
    $fecha_pago = $_POST['fecha_pago'];
    $fecha_contrato = $_POST['fecha_contrato'];

    try {
        $stmt = $conn->prepare("UPDATE data_cobranzas SET placa = :placa, cliente = :cliente, telefono = :telefono, mensualidad_real = :mensualidad_real, letra = :letra, inicial = :inicial, monto_total = :monto_total, fecha_pago = :fecha_pago, fecha_contrato = :fecha_contrato WHERE id = :id");
        $stmt->execute([
            ':placa' => $placa,
            ':cliente' => $cliente_nombre,
            ':telefono' => $telefono,
            ':mensualidad_real' => $mensualidad_real,
            ':letra' => $letra,
            ':inicial' => $inicial,
            ':monto_total' => $monto_total,
            ':fecha_pago' => $fecha_pago,
            ':fecha_contrato' => $fecha_contrato,
            ':id' => $id,
        ]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $error = "Error al actualizar los datos: " . $e->getMessage();
    }
}

// Consultar los datos de la tabla de contratos, clientes y vehículos
try {
    $stmt = $conn->prepare("SELECT * FROM data_cobranzas");
    $stmt->execute();
    $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtClientes = $conn->prepare("
        SELECT c.id, c.nombres, t.telefono 
        FROM clientes c
        LEFT JOIN telefonos t ON c.id = t.cliente_id
        GROUP BY c.id
    ");
    $stmtClientes->execute();
    $clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

    $stmtVehiculos = $conn->prepare("SELECT placa FROM vehiculos");
    $stmtVehiculos->execute();
    $vehiculos = $stmtVehiculos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener los datos: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Contratos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
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
        
        .table-header {
            background-color: #007bff;
            color: white;
        }
        .table {
            background: white;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table th {
            background-color: #007bff;
            color: white;
            text-align: center;
        }
        .table td {
            text-align: center;
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
            <a href="administrar_contratos.php">Administrar Vehiculos</a>
            <a href="administrar_prestamo.php">Administrar Prestamos</a>
            <a href="administrar_lubricante.php">Administrar Lubricantes</a>
            <a href="administrar_gps.php">Administrar GPS</a>
        </div>
    </div>
    <div class="has-submenu">
        <a href="#" onclick="toggleSubmenu(event)">Servicios</a>
        <div class="submenu">
            <a href="consulta_pagos.php">Consultar pagos vehiculos</a>
            <a href="consultar_pagos_prestamos.php">Consultar pagos prestamos</a>
            <a href="consultar_pagos_lubricantes.php">Consultar pagos lubricantes</a>
            <a href="consultar_pagos_gps.php">Consultar pagos GPS</a>
            <a href="generar_reportes.php">Generar Reportes</a>
        </div>
    </div>
    <a href="#">Cobranzas</a>
    <a href="administracion.php">Administración</a>
    <a href="busqueda_nombre.php">BUSCAR</a>

    
    <a href="clientes.php">Clientes</a>
    <a href="vehiculos.php">Vehículos</a>

    <a href="#">Tipos de Servicios</a>

    <a href="logout.php">Cerrar Sesión</a>
</div>

    <!-- Main Content -->
    <div class="main-content">
        <h2 class="mb-4">Administrar Contratos de Servicios</h2>
        <!-- Botón para abrir el modal -->
        <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#nuevoContratoModal">Agregar Contrato</button>

        <table class="table table-bordered">
            <thead class="table-header">
                <tr>
                    <th>#</th>
                    <th>Placa</th>
                    <th>Cliente</th>
                    <th>Mensualidad</th>
                    <th>Letra (Meses)</th>
                    <th>Inicial</th>
                    <th>Monto Total</th>
                    <th>Fecha de Pago</th>
                    <th>Fecha de Contrato</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    <?php foreach ($contratos as $index => $contrato): ?>
        <?php
        // Obtener los teléfonos asociados al cliente
        $stmtTelefonos = $conn->prepare("SELECT telefono FROM telefonos WHERE cliente_id = (SELECT id FROM clientes WHERE nombres = :nombre LIMIT 1)");
        $stmtTelefonos->execute([':nombre' => $contrato['cliente']]);
        $telefonosCliente = $stmtTelefonos->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($contrato['placa']) ?></td>
            <td><?= htmlspecialchars($contrato['cliente']) ?></td>
            <td><?= htmlspecialchars($contrato['mensualidad_real']) ?></td>
            <td><?= htmlspecialchars($contrato['letra'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($contrato['inicial'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($contrato['monto_total'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($contrato['fecha_pago']) ?></td>
            <td><?= htmlspecialchars($contrato['fecha_contrato']) ?></td>
            <td>
                <!-- Mostrar el primer teléfono -->
                <?= htmlspecialchars($telefonosCliente[0]['telefono'] ?? 'Sin teléfono') ?>
                <!-- Botón para mostrar otros teléfonos con ícono de ojo -->
                <?php if (count($telefonosCliente) > 1): ?>
                    <button class="btn btn-link btn-sm" data-toggle="modal" data-target="#telefonosModal<?= $index ?>">
                        <i class="bi bi-eye"></i>
                    </button>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editarContratoModal<?= $contrato['id'] ?>">Editar</button>
                
                <form action="eliminar_contrato.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este contrato?');">
                    <input type="hidden" name="id" value="<?= $contrato['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                    
                </form>
            </td>
        </tr>

        <!-- Modal para mostrar teléfonos -->
        <div class="modal fade" id="telefonosModal<?= $index ?>" tabindex="-1" role="dialog" aria-labelledby="telefonosModalLabel<?= $index ?>" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="telefonosModalLabel<?= $index ?>">Teléfonos de <?= htmlspecialchars($contrato['cliente']) ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <ul>
                            <?php foreach ($telefonosCliente as $telefono): ?>
                                <li><?= htmlspecialchars($telefono['telefono']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal para Editar Contrato -->
   <div class="modal fade" id="editarContratoModal<?= $contrato['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editarContratoModalLabel<?= $contrato['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                <h5 class="modal-title">Editar Contrato</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="id" value="<?= $contrato['id'] ?>">
                                        <div class="form-group">
                                            <label for="placa">Placa</label>
                                            <input type="text" name="placa" class="form-control" value="<?= htmlspecialchars($contrato['placa']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="cliente_nombre">Cliente</label>
                                            <input type="text" name="cliente_nombre" class="form-control" value="<?= htmlspecialchars($contrato['cliente']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="telefono">Teléfono</label>
                                            <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($contrato['telefono']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="mensualidad_real">Mensualidad Real</label>
                                            <input type="number" step="0.01" name="mensualidad_real" class="form-control" value="<?= htmlspecialchars($contrato['mensualidad_real']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="letra">Letra (Meses)</label>
                                            <input type="number" name="letra" class="form-control" value="<?= htmlspecialchars($contrato['letra']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inicial">Inicial</label>
                                            <input type="number" step="0.01" name="inicial" class="form-control" value="<?= htmlspecialchars($contrato['inicial']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="fecha_pago">Fecha de Pago</label>
                                            <input type="number" name="fecha_pago" class="form-control" value="<?= htmlspecialchars($contrato['fecha_pago']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="fecha_contrato">Fecha de Contrato</label>
                                            <input type="date" name="fecha_contrato" class="form-control" value="<?= htmlspecialchars($contrato['fecha_contrato']) ?>" required>
                                        </div>
                                        <button type="submit" name="editar_contrato" class="btn btn-primary">Guardar Cambios</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
              
    <?php endforeach; ?>
</tbody>

        </table>
    </div>

   

    <!-- Modal para Agregar Contrato -->
    <div class="modal fade" id="nuevoContratoModal" tabindex="-1" role="dialog" aria-labelledby="nuevoContratoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nuevoContratoModalLabel">Agregar Nuevo Contrato</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="placa">Placa</label>
                            <input list="vehiculos" name="placa" id="placa" class="form-control" required>
                            <datalist id="vehiculos">
                                <?php foreach ($vehiculos as $vehiculo): ?>
                                    <option value="<?= htmlspecialchars($vehiculo['placa']) ?>"><?= htmlspecialchars($vehiculo['placa']) ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="cliente">Cliente</label>
                            <input list="clientes" name="cliente_nombre" id="cliente" class="form-control" required>
                            <datalist id="clientes">
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?= htmlspecialchars($cliente['nombres']) ?>" data-telefono="<?= htmlspecialchars($cliente['telefono']) ?>">
                                        <?= htmlspecialchars($cliente['nombres']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="mensualidad_real">Mensualidad Real</label>
                            <input type="number" step="0.01" name="mensualidad_real" id="mensualidad_real" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="letra">Letra (Meses)</label>
                            <input type="number" name="letra" id="letra" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="inicial">Inicial ($)</label>
                            <input type="number" step="0.01" name="inicial" id="inicial" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="monto_total">Monto Total</label>
                            <input type="text" id="monto_total" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="fecha_pago">Fechas de Pago</label>
                            <input type="number" name="fecha_pago" id="fecha_pago" class="form-control" required>
                                </div>
                                <div class="form-group">
                            <label for="fecha_contrato">Fecha de Contrato</label>
                            <input type="date" name="fecha_contrato" id="fecha_contrato" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Guardar Contrato</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const clienteInput = document.getElementById('cliente');
            const telefonoInput = document.getElementById('telefono');
            const dataList = document.getElementById('clientes');
            const mensualidadRealInput = document.getElementById('mensualidad_real');
            const letraInput = document.getElementById('letra');
            const montoTotalInput = document.getElementById('monto_total');

            clienteInput.addEventListener('input', function () {
                const value = clienteInput.value;
                const option = Array.from(dataList.options).find(opt => opt.value === value);
                if (option) {
                    telefonoInput.value = option.getAttribute('data-telefono');
                } else {
                    telefonoInput.value = '';
                }
            });

            function calculateMontoTotal() {
                const mensualidadReal = parseFloat(mensualidadRealInput.value) || 0;
                const letra = parseInt(letraInput.value) || 0;
                montoTotalInput.value = (mensualidadReal * letra).toFixed(2);
            }

            mensualidadRealInput.addEventListener('input', calculateMontoTotal);
            letraInput.addEventListener('input', calculateMontoTotal);

            document.querySelectorAll('.submenu-toggle').forEach(toggle => {
                toggle.addEventListener('click', function () {
                    this.parentElement.classList.toggle('active');
                });
            });
        });


         // Función para mostrar/ocultar el submenú
    function toggleSubmenu(event) {
        event.preventDefault();
        const parent = event.target.closest('.has-submenu');
        parent.classList.toggle('active');
    }
    </script>
    
</body>
</html>
