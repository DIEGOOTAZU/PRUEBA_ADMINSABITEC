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

// Consultar los nombres de clientes de la tabla GPS
try {
    $stmt = $conn->prepare("
        SELECT DISTINCT c.id AS cliente_id, c.nombres AS cliente
        FROM gps g
        JOIN clientes c ON g.id_cliente = c.id
    ");
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener los datos: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Pagos GPS</title>
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
        table {
            width: 100%;
            margin-top: 20px;
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

    <!-- Main Content -->
    <div class="main-content">
        <h2>Consultar Pagos GPS</h2>
        <table class="table table-bordered">
            <thead class="table-header">
                <tr>
                    <th>Cliente</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td><?= htmlspecialchars($cliente['cliente']) ?></td>
                        <td>
                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#modalConsultar"
    onclick="cargarPlacas(<?= $cliente['cliente_id'] ?>, '<?= htmlspecialchars($cliente['cliente']) ?>')">
    Consultar
</button>
   </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalConsultar" tabindex="-1" role="dialog" aria-labelledby="modalConsultarLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConsultarLabel">Placas del Cliente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5 id="clienteNombre" class="text-center mb-3"></h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Placa</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaPlacas">
                            <!-- Aquí se llenan las placas dinámicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Función para cargar las placas del cliente en el modal
    function cargarPlacas(cliente_id, cliente) {
    document.getElementById('clienteNombre').textContent = `Cliente: ${cliente}`;

    $.ajax({
        url: 'consultar_placa_gps.php', 
        type: 'GET',
        data: { id_cliente: cliente_id }, // Enviamos el ID en lugar del nombre
        success: function(response) {
            const tablaPlacas = document.getElementById('tablaPlacas');
            tablaPlacas.innerHTML = ''; // Limpiar antes de agregar nuevas placas
            const placas = JSON.parse(response);

            if (placas.length === 0) {
                tablaPlacas.innerHTML = '<tr><td colspan="2" class="text-center">No hay vehículos registrados para este cliente.</td></tr>';
                return;
            }

            // Agregar las placas al modal
            placas.forEach(placa => {
                const row = document.createElement('tr');

                const placaCell = document.createElement('td');
                placaCell.textContent = placa.placa;
                row.appendChild(placaCell);

                const accionesCell = document.createElement('td');
                const button = document.createElement('button');
                button.textContent = 'Consultar';
                button.className = 'btn btn-primary btn-sm';
                button.onclick = function () {
                    window.location.href = `consultar_fechas_gps.php?id_cliente=${cliente_id}&placa=${encodeURIComponent(placa.placa)}`;
                };

                accionesCell.appendChild(button);
                row.appendChild(accionesCell);
                tablaPlacas.appendChild(row);
            });
        },
        error: function() {
            alert('Error al cargar las placas.');
        }
    });
}

 // Función para mostrar/ocultar el submenú
    function toggleSubmenu(event) {
        event.preventDefault();
        const parent = event.target.closest('.has-submenu');
        parent.classList.toggle('active');
    }
</script>

</body>
</html>
