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

// Procesar formulario para agregar GPS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_gps'])) {
    $id_cliente = $_POST['cliente_id'] ?? null;
    $placa = $_POST['placa'] ?? null;
    $tecnico = $_POST['tecnico'] ?? null;
    $chip = $_POST['chip'] ?? null;
    $numero_chip = $_POST['numero_chip'] ?? null;
    $dni_ruc = $_POST['dni_ruc'] ?? null;
    $monto = $_POST['monto'] ?? null;
    $dia_pago = $_POST['dia_pago'] ?? null;
    $dia_instalacion = $_POST['dia_instalacion'] ?? null;

    if (!$id_cliente || !$placa || !$tecnico || !$chip || !$numero_chip || !$dni_ruc || !$monto || !$dia_pago || !$dia_instalacion) {
        die("Error: Todos los campos son obligatorios.");
    }

    try {
        $stmt = $conn->prepare("INSERT INTO gps (id_cliente, placa, tecnico, chip, numero_chip, dni_ruc, monto, dia_pago, dia_instalacion) 
                                VALUES (:id_cliente, :placa, :tecnico, :chip, :numero_chip, :dni_ruc, :monto, :dia_pago, :dia_instalacion)");
        $stmt->execute([
            ':id_cliente'      => $id_cliente,
            ':placa'           => $placa,
            ':tecnico'         => $tecnico,
            ':chip'            => $chip,
            ':numero_chip'     => $numero_chip,
            ':dni_ruc'         => $dni_ruc,
            ':monto'           => $monto,
            ':dia_pago'        => $dia_pago,
            ':dia_instalacion' => $dia_instalacion,
        ]);

        header("Location: administrar_gps.php?success=1");
        exit;
    } catch (PDOException $e) {
        die("Error al guardar en la base de datos: " . $e->getMessage());
    }
}

// Procesar el formulario de edición de GPS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_gps'])) {
    $id = $_POST['id'];
    $id_cliente = $_POST['cliente_id'];
    $placa = $_POST['placa'];
    $tecnico = $_POST['tecnico'];
    $chip = $_POST['chip'];
    $numero_chip = $_POST['numero_chip'];
    $dni_ruc = $_POST['dni_ruc'];
    $monto = $_POST['monto'];
    $dia_pago = $_POST['dia_pago'];
    $dia_instalacion = $_POST['dia_instalacion'];

    try {
        $stmt = $conn->prepare("UPDATE gps SET 
                                id_cliente = :id_cliente,
                                placa = :placa,
                                tecnico = :tecnico,
                                chip = :chip,
                                numero_chip = :numero_chip,
                                dni_ruc = :dni_ruc,
                                monto = :monto,
                                dia_pago = :dia_pago,
                                dia_instalacion = :dia_instalacion
                                WHERE id = :id");
        $stmt->execute([
            ':id_cliente'      => $id_cliente,
            ':placa'           => $placa,
            ':tecnico'         => $tecnico,
            ':chip'            => $chip,
            ':numero_chip'     => $numero_chip,
            ':dni_ruc'         => $dni_ruc,
            ':monto'           => $monto,
            ':dia_pago'        => $dia_pago,
            ':dia_instalacion' => $dia_instalacion,
            ':id'              => $id,
        ]);

        header("Location: administrar_gps.php?success=1");
        exit;
    } catch (PDOException $e) {
        die("Error al actualizar el GPS: " . $e->getMessage());
    }
}

// Obtener datos de GPS con nombres de clientes
try {
    $stmt = $conn->prepare("SELECT g.id, g.id_cliente, c.nombres AS cliente, g.placa, g.tecnico, g.chip, g.numero_chip, g.dni_ruc, g.monto, g.dia_pago, g.dia_instalacion 
                            FROM gps g
                            JOIN clientes c ON g.id_cliente = c.id");
    $stmt->execute();
    $gpsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener lista de clientes
    $stmtClientes = $conn->prepare("SELECT id, nombres FROM clientes");
    $stmtClientes->execute();
    $clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

    // Obtener lista de veh\u00edculos para las placas (valores v\u00e1lidos)\n
    $stmtVehiculos = $conn->prepare("SELECT placa FROM vehiculos");
    $stmtVehiculos->execute();
    $vehiculos = $stmtVehiculos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar GPS</title>
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
        .main-content {
            margin-left: 260px;
            padding: 20px;
        }
        .table-header {
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
<div class="sidebar">
    <h3 class="text-center">Sabitec GPS</h3>
    <a href="index.php">Inicio</a>
    <a href="administrar_contratos.php">Contratos</a>
    <a href="administrar_prestamos.php">Préstamos</a>
    <a href="clientes.php">Clientes</a>
    <a href="administrar_gps.php">GPS</a>
    <a href="vehiculos.php">Vehículos</a>
    <a href="logout.php">Cerrar Sesión</a>
</div>

<div class="main-content">
    <h2 class="mb-4">Administrar GPS</h2>
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#nuevoGPSModal">Agregar GPS</button>
    <table class="table table-bordered">
        <thead class="table-header">
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Placa</th>
                <th>Técnico</th>
                <th>Chip</th>
                <th>Número Chip</th>
                <th>DNI/RUC</th>
                <th>Monto</th>
                <th>Día de Pago</th>
                <th>Día de Instalación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($gpsList as $index => $gps): ?>
               
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($gps['cliente']) ?></td>
                    <td><?= htmlspecialchars($gps['placa']) ?></td>
                    <td><?= htmlspecialchars($gps['tecnico']) ?></td>
                    <td><?= htmlspecialchars($gps['chip']) ?></td>
                    <td><?= htmlspecialchars($gps['numero_chip']) ?></td>
                    <td><?= htmlspecialchars($gps['dni_ruc']) ?></td>
                    <td><?= htmlspecialchars($gps['monto']) ?></td>
                    <td><?= htmlspecialchars($gps['dia_pago']) ?></td>
                    <td><?= htmlspecialchars($gps['dia_instalacion']) ?></td>
                    <td>
                        <!-- Botón Editar -->
                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editarGPSModal<?= $gps['id'] ?>">Editar</button>
                        <!-- Botón Eliminar con Confirmación -->
                        <form action="eliminar_gps.php" method="POST" style="display:inline;" onsubmit="return confirmarEliminacion();">
                            <input type="hidden" name="id" value="<?= $gps['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>


<!-- Modal para Agregar GPS -->
<div class="modal fade" id="nuevoGPSModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nuevo GPS</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="agregar_gps" value="1">
                    <!-- Cliente -->
                    <div class="form-group">
    <label for="cliente">Cliente</label>
    <input list="clientes" id="cliente_nombre" class="form-control" required>
    <input type="hidden" name="cliente_id" id="cliente_id">
    
    <datalist id="clientes">
        <?php foreach ($clientes as $cliente): ?>
            <option data-id="<?= $cliente['id'] ?>" value="<?= htmlspecialchars($cliente['nombres']) ?>">
                <?= htmlspecialchars($cliente['nombres']) ?>
            </option>
        <?php endforeach; ?>
    </datalist>
</div>
                    <!-- Vehículo (Placa) --> 
                    <div class="form-group">
                            <label for="placa">Placa</label>
                            <input list="vehiculos" name="placa" id="placa" class="form-control" required>
                            <datalist id="vehiculos">
                                <?php foreach ($vehiculos as $vehiculo): ?>
                                    <option value="<?= htmlspecialchars($vehiculo['placa']) ?>"><?= htmlspecialchars($vehiculo['placa']) ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    <!-- Técnico -->
                    <div class="form-group">
                        <label>Técnico</label>
                        <input type="text" name="tecnico" class="form-control" required>
                    </div>
                    <!-- Chip -->
                    <div class="form-group">
                        <label>Chip</label>
                        <input type="text" name="chip" class="form-control" required>
                    </div>
                    <!-- Número de Chip -->
                    <div class="form-group">
                        <label>Número de Chip</label>
                        <input type="text" name="numero_chip" class="form-control" required>
                    </div>
                    <!-- DNI/RUC -->
                    <div class="form-group">
                        <label>DNI/RUC</label>
                        <input type="text" name="dni_ruc" class="form-control" required>
                    </div>
                    <!-- Teléfono -->
                    
                    <!-- Monto -->
                    <div class="form-group">
                        <label>Monto</label>
                        <input type="number" step="0.01" name="monto" class="form-control" required>
                    </div>
                    <!-- Día de Pago -->
                    <div class="form-group">
                        <label>Día de Pago</label>
                        <input type="date" name="dia_pago" class="form-control" required>
                    </div>
                    <!-- Día de Instalación -->
                    <div class="form-group">
                        <label>Día de Instalación</label>
                        <input type="date" name="dia_instalacion" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
<!-- Modal para Editar GPS -->
<?php foreach ($gpsList as $gps): ?>
<div class="modal fade" id="editarGPSModal<?= $gps['id'] ?>" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar GPS</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="editar_gps" value="1">
                    <input type="hidden" name="id" value="<?= $gps['id'] ?>">
                    <!-- Cliente -->
                    <div class="form-group">
                        <label>Cliente</label>
                        <select name="cliente_id" class="form-control" required>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>" <?= ($gps['id_cliente'] == $cliente['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['nombres']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Vehículo (Placa) --> 
                    <div class="form-group">
                        <label>Vehículo (Placa)</label>
                        <select name="placa" class="form-control" required>
                            <?php foreach ($vehiculos as $vehiculo): ?>
                                <option value="<?= $vehiculo['placa'] ?>" <?= ($gps['placa'] == $vehiculo['placa']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vehiculo['placa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Técnico -->
                    <div class="form-group">
                        <label>Técnico</label>
                        <input type="text" name="tecnico" class="form-control" value="<?= htmlspecialchars($gps['tecnico']) ?>" required>
                    </div>
                    <!-- Chip -->
                    <div class="form-group">
                        <label>Chip</label>
                        <input type="text" name="chip" class="form-control" value="<?= htmlspecialchars($gps['chip']) ?>" required>
                    </div>
                    <!-- Número de Chip -->
                    <div class="form-group">
                        <label>Número de Chip</label>
                        <input type="text" name="numero_chip" class="form-control" value="<?= htmlspecialchars($gps['numero_chip']) ?>" required>
                    </div>
                    <!-- DNI/RUC -->
                    <div class="form-group">
                        <label>DNI/RUC</label>
                        <input type="text" name="dni_ruc" class="form-control" value="<?= htmlspecialchars($gps['dni_ruc']) ?>" required>
                    </div>
                    <!-- Teléfono -->
                    
                    <!-- Monto -->
                    <div class="form-group">
                        <label>Monto</label>
                        <input type="number" step="0.01" name="monto" class="form-control" value="<?= htmlspecialchars($gps['monto']) ?>" required>
                    </div>
                    <!-- Día de Pago -->
                    <div class="form-group">
                        <label>Día de Pago</label>
                        <input type="date" name="dia_pago" class="form-control" value="<?= htmlspecialchars($gps['dia_pago']) ?>" required>
                    </div>
                    <!-- Día de Instalación -->
                    <div class="form-group">
                        <label>Día de Instalación</label>
                        <input type="date" name="dia_instalacion" class="form-control" value="<?= htmlspecialchars($gps['dia_instalacion']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
    function confirmarEliminacion() {
        return confirm("¿Estás seguro de que deseas eliminar este GPS?");
    }
</script>

<script>
        document.addEventListener('DOMContentLoaded', function () {
        
           
            const dataList = document.getElementById('clientes');
            const mensualidadRealInput = document.getElementById('mensualidad_real');
            const letraInput = document.getElementById('letra');
            const montoTotalInput = document.getElementById('monto_total');

           

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
    </script>
    
    <script>
    document.getElementById('cliente_nombre').addEventListener('input', function() {
        let input = this.value;
        let dataList = document.getElementById('clientes');
        let hiddenInput = document.getElementById('cliente_id');

        hiddenInput.value = ''; // Reinicia el valor

        for (let option of dataList.options) {
            if (option.value === input) {
                hiddenInput.value = option.getAttribute('data-id');
                break;
            }
        }
    });
</script>
</body>
</html>
