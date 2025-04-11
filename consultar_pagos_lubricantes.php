<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/db.php';

try {
    $stmt = $conn->prepare("SELECT * FROM lubricantes ORDER BY fecha DESC");
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener los datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Pagos - Lubricantes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background-color: #f8f9fa; }
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
        .sidebar a:hover { background-color: #495057; }
        .sidebar .submenu { display: none; padding-left: 20px; }
        .sidebar .has-submenu.active .submenu { display: block; }
        .main-content {
            margin-left: 260px;
            padding: 20px;
        }
        table { width: 100%; margin-top: 20px; }
        .table-header { background-color: #007bff; color: white; }
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
            <a href="consulta_pagos.php">Consulta de Pagos</a>
            <a href="consultar_pagos_lubricantes.php">Pagos Lubricantes</a>
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
    <h2>Consulta de Pagos - Lubricantes</h2>
    <table class="table table-bordered">
        <thead class="table-header">
            <tr>
                <th>Fecha</th>
                <th>Marca/Año</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $registro): ?>
                <tr>
                    <td><?= htmlspecialchars($registro['fecha']) ?></td>
                    <td><?= htmlspecialchars($registro['marca_ano']) ?></td>
                    <td>
                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#modalDetalle"
                                onclick='mostrarDetalle(<?= json_encode($registro) ?>)'>
                            Consultar
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="modalDetalle" tabindex="-1" role="dialog" aria-labelledby="modalDetalleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Servicio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detalleContenido">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.submenu-toggle').forEach(toggle => {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                const parent = event.target.closest('.has-submenu');
                parent.classList.toggle('active');
            });
        });
    });

    function mostrarDetalle(data) {
        let html = '<table class="table table-bordered">';
        for (const [key, value] of Object.entries(data)) {
            if (key !== 'id') {
                const campo = key.replace(/_/g, ' ').toUpperCase();
                html += `<tr><th>${campo}</th><td>${value}</td></tr>`;
            }
        }
        html += '</table>';
        document.getElementById('detalleContenido').innerHTML = html;
    }
</script>

</body>
</html>
