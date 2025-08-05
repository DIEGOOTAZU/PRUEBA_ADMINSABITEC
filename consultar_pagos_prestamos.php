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

// --- Lógica para la petición AJAX (cuando se pide la lista de préstamos para el modal) ---
if (isset($_GET['action']) && $_GET['action'] === 'get_prestamos' && isset($_GET['cliente_id'])) {
    $clienteId = $_GET['cliente_id'];

    try {
        $stmt = $conn->prepare("SELECT id, garantia, capital FROM prestamos WHERE cliente_id = :cliente_id ORDER BY id ASC");
        $stmt->execute([':cliente_id' => $clienteId]);
        $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($prestamos);
        exit; 
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al obtener los préstamos: ' . $e->getMessage()]);
        exit;
    }
}
// --- Fin de la lógica AJAX ---


// --- Lógica para la carga inicial de la página (mostrar la tabla de clientes) ---
try {
    $stmt = $conn->prepare("
        SELECT DISTINCT c.nombres AS cliente_nombre, c.id AS cliente_id
        FROM prestamos p
        JOIN clientes c ON p.cliente_id = c.id
        ORDER BY c.nombres ASC
    ");
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener los datos de los clientes: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Pagos de Préstamos</title>
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
        <h2>Consulta de Pagos de Préstamos</h2>
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
                        <td><?= htmlspecialchars($cliente['cliente_nombre']) ?></td>
                        <td>
                            <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#modalConsultarPrestamos"
                                    onclick="cargarPrestamosPorCliente(<?= htmlspecialchars($cliente['cliente_id']) ?>, '<?= htmlspecialchars($cliente['cliente_nombre']) ?>')">
                                Consultar Préstamos
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="modalConsultarPrestamos" tabindex="-1" role="dialog" aria-labelledby="modalConsultarPrestamosLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConsultarPrestamosLabel">Préstamos del Cliente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5 id="clienteNombreEnModal" class="text-center mb-3"></h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID Préstamo</th>
                                <th>Garantía</th>
                                <th>Monto Capital</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaPrestamosDelCliente">
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
        document.addEventListener('DOMContentLoaded', function () {
            const submenuToggles = document.querySelectorAll('.has-submenu > a');
            submenuToggles.forEach(toggle => {
                toggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    const parent = this.closest('.has-submenu');
                    parent.classList.toggle('active');
                });
            });
        });

        function toggleSubmenu(event) {
            event.preventDefault();
            const parent = event.target.closest('.has-submenu');
            parent.classList.toggle('active');
        }

        function cargarPrestamosPorCliente(clienteId, clienteNombre) {
            document.getElementById('clienteNombreEnModal').textContent = `Cliente: ${clienteNombre}`;
            
            const tablaPrestamos = document.getElementById('tablaPrestamosDelCliente');
            tablaPrestamos.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Cargando préstamos...</td></tr>';

            $.ajax({
                url: 'consultar_pagos_prestamos.php',
                type: 'GET',
                data: { action: 'get_prestamos', cliente_id: clienteId },
                dataType: 'json', // ¡Esto es clave para que jQuery parsee el JSON automáticamente!
                success: function(prestamos) { 
                    tablaPrestamos.innerHTML = ''; // Limpiar mensaje de carga

                    if (prestamos.length > 0) {
                        prestamos.forEach(prestamo => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${htmlspecialchars(prestamo.id)}</td>
                                <td>${htmlspecialchars(prestamo.garantia)}</td>
                                <td>S/ ${parseFloat(prestamo.capital).toFixed(2)}</td>
                                <td>
                                    <a href="consultar_fechas_prestamos.php?prestamo_id=${encodeURIComponent(prestamo.id)}&cliente=${encodeURIComponent(clienteNombre)}" class="btn btn-primary btn-sm">
                                        Ver Detalles
                                    </a>
                                </td>
                            `;
                            tablaPrestamos.appendChild(row);
                        });
                    } else {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td colspan="4" class="text-center text-muted">No se encontraron préstamos para este cliente.</td>`;
                        tablaPrestamos.appendChild(row);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error AJAX:", status, error, xhr.responseText);
                    const tablaPrestamos = document.getElementById('tablaPrestamosDelCliente');
                    tablaPrestamos.innerHTML = `<td colspan="4" class="text-center text-danger">Error al cargar préstamos: ${error}. Consulta la consola para más detalles.</td>`;
                }
            });
        }

        // Función auxiliar para htmlspecialchars en JS (para evitar XSS en el contenido dinámico)
        function htmlspecialchars(str) {
            if (typeof str === 'undefined' || str === null) {
                return ''; 
            }
            str = String(str);
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>
</body>
</html>