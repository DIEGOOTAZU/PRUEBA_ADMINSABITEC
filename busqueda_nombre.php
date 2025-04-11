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

// Variables iniciales
$resultadosBusqueda = [];
$mensajeError = "";

// Si se envió un formulario de búsqueda
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $busqueda = trim($_POST['busqueda']);

    try {
        // Buscar por nombre o DNI en la base de datos
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE nombres LIKE :busqueda OR documento LIKE :busqueda");
        $stmt->execute([':busqueda' => "%$busqueda%"]);
        $resultadosBusqueda = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($resultadosBusqueda)) {
            $mensajeError = "No se encontraron resultados para la búsqueda especificada.";
        }
    } catch (PDOException $e) {
        $mensajeError = "Error al realizar la búsqueda: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda por Nombre o DNI</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
    </style>
</head>
<body>
<div class="sidebar">
    <h3 class="text-center">Sabitec GPS</h3>
    <a href="index.php">Inicio</a>
    <div class="has-submenu">
        <a href="#" onclick="toggleSubmenu(event)">Contratos</a>
        <div class="submenu">
            <a href="administrar_contratos.php">Administrar Contratos</a>
        </div>
    </div>
    <div class="has-submenu">
        <a href="#" onclick="toggleSubmenu(event)">Servicios</a>
        <div class="submenu">
            <a href="consulta_pagos.php">Consulta de Pagos</a>
            <a href="generar_reportes.php">Generar Reportes</a>
        </div>
    </div>
    <a href="#">Cobranzas</a>
    <a href="administracion.php">Administración</a>
    <a href="clientes.php">Clientes</a>
    <a href="vehiculos.php">Vehículos</a>
    <a href="busqueda_nombre.php">Búsqueda por Nombre/DNI</a>
    <a href="logout.php">Cerrar Sesión</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>Búsqueda por Nombre o DNI</h2>

    <!-- Formulario de búsqueda -->
    <form method="POST" action="busqueda_nombre.php" class="mb-4">
        <div class="form-group">
            <label for="busqueda">Ingrese Nombre o DNI:</label>
            <input type="text" name="busqueda" id="busqueda" class="form-control" placeholder="Ej: Juan Pérez o 12345678" required>
        </div>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>

    <?php if ($mensajeError): ?>
        <div class="alert alert-danger"><?= $mensajeError ?></div>
    <?php endif; ?>

    <?php if (!empty($resultadosBusqueda)): ?>
        <h4>Resultados de la Búsqueda</h4>
        <table class="table table-bordered">
            <thead class="table-header">
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Tipo Documento</th>
                    <th>Documento</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultadosBusqueda as $index => $resultado): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($resultado['nombres']) ?></td>
                        <td><?= htmlspecialchars($resultado['tipo_documento']) ?></td>
                        <td><?= htmlspecialchars($resultado['documento']) ?></td>
                        <td><?= htmlspecialchars($resultado['telefono']) ?></td>
                        <td>
                            <a href="editar_cliente.php?id=<?= $resultado['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                            <form action="eliminar_cliente.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $resultado['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
    function toggleSubmenu(event) {
        event.preventDefault();
        const parent = event.target.closest('.has-submenu');
        parent.classList.toggle('active');
    }
</script>
</body>
</html>
