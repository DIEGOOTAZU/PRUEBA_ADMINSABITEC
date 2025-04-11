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

// Consultar los datos de los clientes que están registrados en la tabla de préstamos
try {
    // Ajustamos la consulta para que solo se muestren los clientes de la tabla `prestamos`
    $stmt = $conn->prepare("
        SELECT DISTINCT c.nombres AS cliente, c.id AS cliente_id
        FROM prestamos p
        JOIN clientes c ON p.cliente_id = c.id
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
            <a href="#" class="submenu-toggle">Contratos</a>
            <div class="submenu">
                <a href="administrar_contratos.php">Administrar Contratos</a>
            </div>
        </div>
        <div class="has-submenu">
            <a href="#" class="submenu-toggle">Servicios</a>
            <div class="submenu">
                <a href="consulta_pagos.php">Consulta de Pagos Vehículos</a>
                <a href="consulta_pagos_prestamos.php">Consulta de Pagos Préstamos</a> <!-- Aquí agregas la nueva opción -->
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
                        <td><?= htmlspecialchars($cliente['cliente']) ?></td>
                        <td>
                            <a href="consultar_fechas_prestamos.php?cliente_id=<?= $cliente['cliente_id'] ?>&cliente=<?= urlencode($cliente['cliente']) ?>" class="btn btn-info btn-sm">Consultar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const submenuToggles = document.querySelectorAll('.submenu-toggle');

            submenuToggles.forEach(toggle => {
                toggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    const parent = event.target.closest('.has-submenu');
                    parent.classList.toggle('active');
                });
            });
        });
    </script>
</body>
</html>
