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

// Consultar los datos de la tabla y contar los contratos
try {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_contratos FROM data_cobranzas");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalContratos = $result['total_contratos'];

    $stmt = $conn->prepare("SELECT * FROM data_cobranzas");
    $stmt->execute();
    $cobranzas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener los datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cobranza</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        .card {
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: scale(1.05);
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

<!-- Main Content -->
<div class="main-content">
    <!-- Cuadros -->
    <h2>Tablero - Panel de Control</h2>
    <div class="row mt-4">
        <div class="col-md-3">
            <a href="administrar_contratos.php" class="text-decoration-none">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3><?= $totalContratos ?></h3>
                        <p>Administrar Contratos</p>
                        <span class="text-white">Más info <i class="fas fa-info-circle"></i></span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3>6</h3>
                    <p>Administrar Cobros</p>
                    <a href="#" class="text-white">Más info <i class="fas fa-info-circle"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h3>7</h3>
                    <p>Tipos de Servicios</p>
                    <a href="#" class="text-white">Más info <i class="fas fa-info-circle"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h3>4</h3>
                    <p>Registros de Clientes</p>
                    <a href="#" class="text-white">Más info <i class="fas fa-info-circle"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <section id="contratos-section">
        <h2 class="mt-5">Data de Cobranzas</h2>
        <button class="btn btn-primary mb-3">Agregar contrato</button>
        <table class="table table-bordered">
            <thead class="table-header">
                <tr>
                    <th>N°</th>
                    <th>PLACA</th>
                    <th>CLIENTE</th>
                    <th>MENSUALIDAD REAL</th>
                    <th>LETRA (MESES)</th>
                    <th>FECHAS DE PAGO</th>
                    <th>FECHA DE CONTRATO</th>
                    <th>TELÉFONOS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cobranzas as $index => $cobranza): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($cobranza['placa']) ?></td>
                        <td><?= htmlspecialchars($cobranza['cliente']) ?></td>
                        <td><?= htmlspecialchars($cobranza['mensualidad_real']) ?></td>
                        <td><?= htmlspecialchars($cobranza['letra']) ?></td>
                        <td><?= !empty($cobranza['fecha_pago']) ? htmlspecialchars($cobranza['fecha_pago']) : 'Sin fecha' ?></td>
                        <td><?= htmlspecialchars($cobranza['fecha_contrato']) ?></td>
                        <td><?= htmlspecialchars($cobranza['telefono']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<script>
    // Función para mostrar/ocultar el submenú
    function toggleSubmenu(event) {
        event.preventDefault();
        const parent = event.target.closest('.has-submenu');
        parent.classList.toggle('active');
    }
</script>
</body>
</html>
