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

// Procesar el formulario de inserción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['editar_lubricante'])) {
    $fecha = $_POST['fecha'];
    $placa = $_POST['placa'];
    $marca_anio = $_POST['marca_anio'];
    $cliente = $_POST['cliente'];
    $cambio_aceite_grado = $_POST['cambio_aceite_grado'] ?? null;
    $f_aire = $_POST['f_aire'] ?? null;
    $f_aceite = $_POST['f_aceite'] ?? null;
    $lav_motor = $_POST['lav_motor'] ?? null;
    $bujias = $_POST['bujias'];
    $hidrolina = $_POST['hidrolina'];
    $monto = $_POST['monto'];
    $forma_pago = $_POST['forma_pago'];

    try {
        $stmt = $conn->prepare("INSERT INTO lubricantes (fecha, placa, marca_anio, cliente, cambio_aceite_grado, f_aire, f_aceite, lav_motor, bujias, hidrolina, monto, forma_pago)
            VALUES (:fecha, :placa, :marca_anio, :cliente, :cambio_aceite_grado, :f_aire, :f_aceite, :lav_motor, :bujias, :hidrolina, :monto, :forma_pago)");
        $stmt->execute([
            ':fecha' => $fecha,
            ':placa' => $placa,
            ':marca_anio' => $marca_anio,
            ':cliente' => $cliente,
            ':cambio_aceite_grado' => $cambio_aceite_grado,
            ':f_aire' => $f_aire,
            ':f_aceite' => $f_aceite,
            ':lav_motor' => $lav_motor,
            ':bujias' => $bujias,
            ':hidrolina' => $hidrolina,
            ':monto' => $monto,
            ':forma_pago' => $forma_pago,
        ]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $error = "Error al guardar los datos: " . $e->getMessage();
    }
}


// Obtener datos de la base de datos
try {
    $stmt = $conn->prepare("SELECT * FROM lubricantes");
    $stmt->execute();
    $lubricantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Lubricantes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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

<div class="main-content">
    <h2 class="mb-4">Administrar Lubricantes</h2>
    
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalAgregar">Agregar Lubricante</button>

    <table class="table table-bordered">
        <thead class="table-header">
            <tr>
            <th>#</th>
                <th>Fecha</th>
                <th>Placa</th>
                <th>Marca y Año</th>
                <th>Cliente</th>
                <th>Cambio Aceite y Grado</th>
                <th>F/Aire</th>
                <th>F/Aceite</th>
                <th>Lav de Motor</th>
                <th>Bujías</th>
                <th>Hidrolina</th>
                <th>Monto</th>
                <th>Forma de Pago</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lubricantes as $index => $lubricante): ?>
                <tr>
                <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($lubricante['fecha']) ?></td>
                    <td><?= htmlspecialchars($lubricante['placa']) ?></td>
                    <td><?= htmlspecialchars($lubricante['marca_anio']) ?></td>
                    <td><?= htmlspecialchars($lubricante['cliente']) ?></td>
                    <td><?= htmlspecialchars($lubricante['cambio_aceite_grado']) ?></td>
                    <td><?= htmlspecialchars($lubricante['f_aire']) ?></td>
                    <td><?= htmlspecialchars($lubricante['f_aceite']) ?></td>
                    <td><?= htmlspecialchars($lubricante['lav_motor']) ?></td>
                    <td><?= htmlspecialchars($lubricante['bujias']) ?></td>
                    <td><?= htmlspecialchars($lubricante['hidrolina']) ?></td>
                    <td><?= htmlspecialchars($lubricante['monto']) ?></td>
                    <td><?= htmlspecialchars($lubricante['forma_pago']) ?></td>
                    <td>
                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editarModal<?= $lubricante['id'] ?>">
    Editar
</button>
                        <a href="eliminar_lubricante.php?id=<?= $lubricante['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este lubricante?');">
                            Eliminar
                        </a>
                    </td>
                </tr>

                
<!-- Modal Editar para cada lubricante -->

<div class="modal fade" id="editarModal<?= $lubricante['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editarModalLabel<?= $lubricante['id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Registro</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="editar_lubricante.php">
                    <input type="hidden" name="id" value="<?= $lubricante['id'] ?>">
                    
                    <div class="form-group">
                        <label>Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="<?= $lubricante['fecha'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Placa</label>
                        <input type="text" name="placa" class="form-control" value="<?= htmlspecialchars($lubricante['placa']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Marca y Año</label>
                        <input type="text" name="marca_anio" class="form-control" value="<?= htmlspecialchars($lubricante['marca_anio']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Cliente</label>
                        <select name="cliente" class="form-control" required>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['nombres'] ?>" <?= ($lubricante['cliente'] == $cliente['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['nombres']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cambio Aceite y Grado</label>
                        <input type="text" name="cambio_aceite_grado" class="form-control" value="<?= htmlspecialchars($lubricante['cambio_aceite_grado']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Filtro de Aire</label>
                        <input type="text" name="f_aire" class="form-control" value="<?= htmlspecialchars($lubricante['f_aire']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Filtro de Aceite</label>
                        <input type="text" name="f_aceite" class="form-control" value="<?= htmlspecialchars($lubricante['f_aceite']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Lavado de Motor</label>
                        <input type="text" name="lav_motor" class="form-control" value="<?= htmlspecialchars($lubricante['lav_motor']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Bujías</label>
                        <input type="text" name="bujias" class="form-control" value="<?= htmlspecialchars($lubricante['bujias']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Hidrolina</label>
                        <input type="text" name="hidrolina" class="form-control" value="<?= htmlspecialchars($lubricante['hidrolina']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Monto</label>
                        <input type="number" step="0.01" name="monto" class="form-control" value="<?= htmlspecialchars($lubricante['monto']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Forma de Pago</label>
                        <select name="forma_pago" class="form-control">
                            <option value="Efectivo" <?= $lubricante['forma_pago'] == "Efectivo" ? "selected" : "" ?>>Efectivo</option>
                            <option value="Tarjeta" <?= $lubricante['forma_pago'] == "Tarjeta" ? "selected" : "" ?>>Tarjeta</option>
                            <option value="Transferencia" <?= $lubricante['forma_pago'] == "Transferencia" ? "selected" : "" ?>>Transferencia</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

            <?php endforeach; ?>
        </tbody>
    </table>
</div>



<!-- Modal Agregar -->
<div class="modal fade" id="modalAgregar" tabindex="-1" role="dialog" aria-labelledby="modalAgregar" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nuevo Registro</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
             </div>
            <div class="modal-body">
            <form method="POST" action="">
                    <div class="form-group">
                        <label>Fecha</label>
                        <input type="date" name="fecha" class="form-control" required>
                    </div>
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
                        <label>Marca y Año</label>
                        <input type="text" name="marca_anio" class="form-control" required>
                    </div>
                    <div class="form-group">
                            <label for="cliente">Cliente</label>
                            <input list="clientes" name="cliente" id="cliente" class="form-control" required>
                            <datalist id="clientes">
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?= htmlspecialchars($cliente['nombres']) ?>" data-telefono="<?= htmlspecialchars($cliente['telefono']) ?>">
                                        <?= htmlspecialchars($cliente['nombres']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    <div class="form-group">
                        <label>Cambio de Aceite y Grado</label>
                        <input type="text" name="cambio_aceite_grado" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Filtro de Aire</label>
                        <input type="text" name="f_aire" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Filtro de Aceite</label>
                        <input type="text" name="f_aceite" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Lavado de Motor</label>
                        <input type="text" name="lav_motor" class="form-control" placeholder="Ej: Completo, Parcial, etc.">
                    </div>
                    <div class="form-group">
                        <label>Bujías</label>
                        <input type="text" name="bujias" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Hidrolina</label>
                        <input type="text" name="hidrolina" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Monto</label>
                        <input type="number" step="0.01" name="monto" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Forma de Pago</label>
                        <select name="forma_pago" class="form-control">
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta">Tarjeta</option>
                            <option value="Transferencia">Transferencia</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Guardar Registro</button>
            </form>
        </div>
        </div>
    </div>
</div>






<script>
$(document).ready(function() {
    // Cuando se envía el formulario de edición
    $('#modalEditar form').submit(function(event) {
        event.preventDefault(); // Evita el envío normal del formulario

        // Obtener los datos del formulario
        var formData = $(this).serialize();

        // Enviar los datos con AJAX
        $.ajax({
            type: "POST",
            url: "editar_lubricante.php",
            data: formData,
            success: function(response) {
                if (response.trim() === "ok") {
                    alert("Registro actualizado con éxito.");
                    location.reload(); // Recargar la página para ver los cambios
                } else {
                    alert("Error al actualizar: " + response);
                }
            },
            error: function() {
                alert("Hubo un error al actualizar el registro.");
            }
        });
    });

    // Cargar datos en el modal de edición
    $('.btnEditar').on('click', function() {
        $('#modalEditar input, #modalEditar select').each((_, el) => {
            $(el).val($(this).data(el.id.replace('edit-', '')));
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
