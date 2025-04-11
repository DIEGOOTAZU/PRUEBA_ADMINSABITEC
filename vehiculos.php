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

// Consultar los vehículos de la tabla `vehiculos`
try {
    $stmt = $conn->prepare("SELECT * FROM vehiculos");
    $stmt->execute();
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener los vehículos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Vehículos</title>
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
        .modal {
            backdrop-filter: blur(5px);
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
    <h2>Administrar Vehículos</h2>
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#nuevoVehiculoModal">Nuevo Vehículo</button>
    <table class="table table-bordered">
        <thead class="table-header">
            <tr>
                <th>#</th>
                <th>Placa</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Color</th>
                <th>Año</th>
                <th>Nª Motor</th>
                <th>Nª Vin</th>
                <th>Combustible</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehiculos as $index => $vehiculo): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($vehiculo['placa']) ?></td>
                    <td><?= htmlspecialchars($vehiculo['marca']) ?></td>
                    <td><?= htmlspecialchars($vehiculo['modelo']) ?></td>
                    <td><?= htmlspecialchars($vehiculo['color']) ?></td>
                    <td><?= htmlspecialchars($vehiculo['anio']) ?></td>
                    <td><?= htmlspecialchars($vehiculo['numero_motor']) ?></td>
                    <td><?= htmlspecialchars($vehiculo['numero_vin']) ?></td>
                    <td><?= htmlspecialchars($vehiculo['combustible']) ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" 
                                onclick="editarVehiculo(<?= $vehiculo['id'] ?>, '<?= htmlspecialchars($vehiculo['placa']) ?>', '<?= htmlspecialchars($vehiculo['marca']) ?>', '<?= htmlspecialchars($vehiculo['modelo']) ?>', '<?= htmlspecialchars($vehiculo['color']) ?>', <?= $vehiculo['anio'] ?>, '<?= htmlspecialchars($vehiculo['numero_motor']) ?>', '<?= htmlspecialchars($vehiculo['numero_vin']) ?>', '<?= htmlspecialchars($vehiculo['combustible']) ?>')">
                            Editar
                        </button>
                        <button class="btn btn-danger btn-sm" 
                                onclick="eliminarVehiculo(<?= $vehiculo['id'] ?>)">
                            Eliminar
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


<!-- Botón para abrir el modal -->
<button class="btn btn-primary mb-3" data-toggle="modal" data-target="#nuevoVehiculoModal">Nuevo Vehículo</button>

<!-- Modal para agregar un nuevo vehículo -->
<div class="modal fade" id="nuevoVehiculoModal" tabindex="-1" role="dialog" aria-labelledby="nuevoVehiculoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nuevoVehiculoModalLabel">Agregar Nuevo Vehículo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formNuevoVehiculo" method="POST" action="agregar_vehiculo.php">
                    <div class="form-group">
                        <label for="placa">Placa</label>
                        <input type="text" name="placa" id="placa" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="marca">Marca</label>
                        <input type="text" name="marca" id="marca" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="modelo">Modelo</label>
                        <input type="text" name="modelo" id="modelo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="color">Color</label>
                        <input type="text" name="color" id="color" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="anio">Año</label>
                        <input type="number" name="anio" id="anio" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="numero_motor">Nª Motor</label>
                        <input type="text" name="numero_motor" id="numero_motor" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="numero_vin">Nª Vin</label>
                        <input type="text" name="numero_vin" id="numero_vin" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="combustible">Combustible</label>
                        <input type="text" name="combustible" id="combustible" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Guardar Vehículo</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar un vehículo -->
<div class="modal fade" id="editarVehiculoModal" tabindex="-1" role="dialog" aria-labelledby="editarVehiculoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarVehiculoModalLabel">Editar Vehículo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formEditarVehiculo" method="POST" action="editar_vehiculo.php">
                    <input type="hidden" name="id" id="editarVehiculoId">
                    <div class="form-group">
                        <label for="editarPlaca">Placa</label>
                        <input type="text" name="placa" id="editarPlaca" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editarMarca">Marca</label>
                        <input type="text" name="marca" id="editarMarca" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editarModelo">Modelo</label>
                        <input type="text" name="modelo" id="editarModelo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editarColor">Color</label>
                        <input type="text" name="color" id="editarColor" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editarAnio">Año</label>
                        <input type="number" name="anio" id="editarAnio" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editarnumero_motor">Nª Motor</label>
                        <input type="text" name="numero_motor" id="editarnumero_motor" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editarnumero_vin">Nª Vin</label>
                        <input type="text" name="numero_vin" id="editarnumero_vin" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editarcombustible">Combustible</label>
                        <input type="text" name="combustible" id="editarcombustible" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function () {
        $('.submenu-toggle').on('click', function (e) {
            e.preventDefault();
            const parent = $(this).closest('.has-submenu');
            parent.toggleClass('active');
        });
    });

    function eliminarVehiculo(id) {
        if (confirm('¿Está seguro de eliminar este vehículo?')) {
            $.ajax({
                url: 'eliminar_vehiculo.php',
                type: 'POST',
                data: { id: id },
                success: function(response) {
                    if (response.trim() === 'success') {
                        alert('Vehículo eliminado correctamente.');
                        location.reload();
                    } else {
                        alert(response);
                    }
                },
                error: function() {
                    alert('Error al comunicarse con el servidor.');
                }
            });
        }
    }

    function editarVehiculo(id, placa, marca, modelo, color, anio, numero_motor, numero_vin, combustible) {
    // Llenar el formulario del modal con los datos del vehículo seleccionado
    $('#editarVehiculoId').val(id);
    $('#editarPlaca').val(placa);
    $('#editarMarca').val(marca);
    $('#editarModelo').val(modelo);
    $('#editarColor').val(color);
    $('#editarAnio').val(anio);
    $('#editarnumero_motor').val(numero_motor);
    $('#editarnumero_vin').val(numero_vin);
    $('#editarcombustible').val(combustible);

    // Mostrar el modal
    $('#editarVehiculoModal').modal('show');
}

</script>

<script>
document.getElementById("formNuevoVehiculo").addEventListener("submit", function(event) {
    event.preventDefault(); // Evitar el envío predeterminado del formulario

    const form = this;
    const formData = new FormData(form);

    fetch("agregar_vehiculo.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json()) // Recibir respuesta en JSON
    .then(data => {
        if (data.status === "error") {
            alert(data.message); // Mostrar mensaje si la placa ya existe
        } else {
            alert("Vehículo agregado con éxito");
            location.reload(); // Recargar la página después de agregar correctamente
        }
    })
    .catch(error => console.error("Error:", error));
});
</script>

</body>
</html>
