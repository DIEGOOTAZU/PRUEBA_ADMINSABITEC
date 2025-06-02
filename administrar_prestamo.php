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

// Procesar formulario para agregar préstamos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_prestamo'])) {
    $cliente_id = $_POST['cliente_id'] ?? null;
    $garantia = $_POST['garantia'] ?? null;
    $fecha_prestamo = $_POST['fecha_prestamo'] ?? null;
    $vence = $_POST['vence'] ?? null;
    $tiempo = $_POST['tiempo'] ?? null;
    $interes = $_POST['interes'] ?? null;
    $capital = $_POST['capital'] ?? null;
    $interes_capital = $_POST['interes_capital'] ?? null;
    $interes_cobrado = $_POST['interes_cobrado'] ?? null;

    if (!$cliente_id || !$garantia || !$fecha_prestamo || !$vence || !$tiempo || !$interes || !$capital) {
        die("Error: Todos los campos son obligatorios.");
    }

    try {
        $stmt = $conn->prepare("INSERT INTO prestamos (cliente_id, garantia, fecha_prestamo, vence, tiempo, interes, capital, interes_capital, interes_cobrado) 
                                VALUES (:cliente_id, :garantia, :fecha_prestamo, :vence, :tiempo, :interes, :capital, :interes_capital, :interes_cobrado)");
        
        $stmt->execute([
            ':cliente_id' => $cliente_id,
            ':garantia' => $garantia,
            ':fecha_prestamo' => $fecha_prestamo,
            ':vence' => $vence,
            ':tiempo' => $tiempo,
            ':interes' => $interes,
            ':capital' => $capital,
            ':interes_capital' => $interes_capital,
            ':interes_cobrado' => $interes_cobrado,
        ]);

        header("Location: administrar_prestamo.php?success=1");
        exit;
    } catch (PDOException $e) {
        die("Error al guardar en la base de datos: " . $e->getMessage());
    }
}

// Procesar el formulario de edición de préstamo al enviar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_prestamo'])) {
    $id = $_POST['id'];
    $cliente_id = $_POST['cliente_id'];
    $garantia = $_POST['garantia'];
    $fecha_prestamo = $_POST['fecha_prestamo'];
    $vence = $_POST['vence'];
    $tiempo = $_POST['tiempo'];
    $interes = $_POST['interes'];
    $capital = $_POST['capital'];
    $interes_capital = $tiempo * $interes / 100 * $capital;
    $interes_cobrado = $capital + $interes_capital;

    try {
        $stmt = $conn->prepare("UPDATE prestamos SET 
                                cliente_id = :cliente_id, 
                                garantia = :garantia, 
                                fecha_prestamo = :fecha_prestamo, 
                                vence = :vence, 
                                tiempo = :tiempo, 
                                interes = :interes, 
                                capital = :capital, 
                                interes_capital = :interes_capital, 
                                interes_cobrado = :interes_cobrado 
                                WHERE id = :id");

        $stmt->execute([
            ':cliente_id' => $cliente_id,
            ':garantia' => $garantia,
            ':fecha_prestamo' => $fecha_prestamo,
            ':vence' => $vence,
            ':tiempo' => $tiempo,
            ':interes' => $interes,
            ':capital' => $capital,
            ':interes_capital' => $interes_capital,
            ':interes_cobrado' => $interes_cobrado,
            ':id' => $id
        ]);

        header("Location: administrar_prestamo.php?success=1");
        exit;
    } catch (PDOException $e) {
        die("Error al actualizar el préstamo: " . $e->getMessage());
    }
}

// Eliminar préstamo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_prestamo'])) {
    $id = $_POST['id'];

    try {
        $stmt = $conn->prepare("DELETE FROM prestamos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Redirigir a la misma página con un mensaje después de la eliminación
        header("Location: administrar_prestamo.php?mensaje=Préstamo%20eliminado%20correctamente");
        exit;
    } catch (PDOException $e) {
        die("Error al eliminar el préstamo: " . $e->getMessage());
    }
}

// Obtener préstamos con nombres de clientes
try {
    $stmt = $conn->prepare("
        SELECT p.id, p.cliente_id, c.nombres AS cliente, p.garantia, p.fecha_prestamo, 
               p.vence, p.tiempo, p.interes, p.capital, p.interes_capital, p.interes_cobrado
        FROM prestamos p
        JOIN clientes c ON p.cliente_id = c.id
    ");
    $stmt->execute();
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener lista de clientes
    $stmtClientes = $conn->prepare("SELECT id, nombres FROM clientes");
    $stmtClientes->execute();
    $clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Préstamos</title>
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
    <h2 class="mb-4">Administrar Préstamos</h2>
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#nuevoPrestamoModal">Agregar Préstamo</button>
    
    <table class="table table-bordered">
        <thead class="table-header">
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Garantía</th>
                <th>Fecha de Préstamo</th>
                <th>Vence</th>
                <th>Tiempo</th>
                <th>% Interés</th>
                <th>Capital(A)</th>
                <th>InteréS(B)</th>
                <th>Interés + Capital(A+B)</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($prestamos as $index => $prestamo): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($prestamo['cliente']) ?></td>
                    <td><?= htmlspecialchars($prestamo['garantia']) ?></td>
                    <td><?= htmlspecialchars($prestamo['fecha_prestamo']) ?></td>
                    <td><?= htmlspecialchars($prestamo['vence']) ?></td>
                    <td><?= htmlspecialchars($prestamo['tiempo']) ?></td>
                    <td><?= htmlspecialchars($prestamo['interes']) ?>%</td>
                    <td><?= htmlspecialchars($prestamo['capital']) ?></td>
                    <td><?= htmlspecialchars($prestamo['interes_capital']) ?></td>
                    <td><?= htmlspecialchars($prestamo['interes_cobrado']) ?></td>
                    <td>
    <!-- Botón Editar -->
    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editarPrestamoModal<?= $prestamo['id'] ?>">Editar</button>

    <!-- Botón Eliminar con Confirmación -->
    <form action="eliminar_prestamo.php" method="POST" style="display:inline;" onsubmit="return confirmarEliminacion();">
    <input type="hidden" name="id" value="<?= $prestamo['id'] ?>">
    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
</form>

</td>

                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Agregar Préstamo -->
<!-- Modal para Agregar Préstamo -->
<div class="modal fade" id="nuevoPrestamoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nuevo Préstamo</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
            <form method="POST" action="">
    <input type="hidden" name="agregar_prestamo" value="1">

    <!-- Cliente -->
    <div class="form-group">
    <label for="cliente">Cliente</label>
    <input list="clientes" name="cliente_nombre" id="cliente" class="form-control" required>
    <input type="hidden" name="cliente_id" id="cliente_id"> <!-- Campo oculto para el ID -->

    <datalist id="clientes">
        <?php foreach ($clientes as $cliente): ?>
            <option value="<?= htmlspecialchars($cliente['nombres']) ?>" data-id="<?= $cliente['id'] ?>">
                <?= htmlspecialchars($cliente['nombres']) ?>
            </option>
        <?php endforeach; ?>
    </datalist>
</div>

    <!-- Garantía -->
    <div class="form-group">
        <label>Garantía</label>
        <input type="text" name="garantia" class="form-control" required>
    </div>

    <!-- Fecha de Préstamo -->
    <div class="form-group">
        <label>Fecha de Préstamo</label>
        <input type="date" name="fecha_prestamo" class="form-control" required>
    </div>

    <!-- Fecha de Vencimiento -->
    <div class="form-group">
        <label>Vence</label>
        <input type="date" name="vence" class="form-control" required>
    </div>

    <!-- Tiempo -->
    <div class="form-group">
        <label>Tiempo (meses)</label>
        <input type="number" name="tiempo" id="tiempo" class="form-control" required>
    </div>

    <!-- % Interés -->
    <div class="form-group">
        <label>% Interés</label>
        <input type="number" step="0.01" name="interes" id="interes" class="form-control" required>
    </div>

    <!-- Capital -->
    <div class="form-group">
        <label>Capital</label>
        <input type="number" step="0.01" name="capital" id="capital" class="form-control" required>
    </div>

    <!-- Interés / B (Calculado) -->
    <div class="form-group">
        <label>Interés / B</label>
        <input type="number" step="0.01" name="interes_capital" id="interes_capital" class="form-control" readonly>
    </div>

    <!-- Interés Cobrado (A+B) (Calculado) -->
    <div class="form-group">
        <label>Interés + Capital(A+B)</label>
        <input type="number" step="0.01" name="interes_cobrado" id="interes_cobrado" class="form-control" readonly>
    </div>

    <button type="submit" name="agregar_prestamo" class="btn btn-primary w-100">Guardar</button>

</form>


            </div>
        </div>
    </div>
</div>


<!-- Modal para Editar Préstamo -->
<?php foreach ($prestamos as $prestamo): ?>
<div class="modal fade" id="editarPrestamoModal<?= $prestamo['id'] ?>" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Préstamo</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="editar_prestamo" value="1">
                    <input type="hidden" name="id" value="<?= $prestamo['id'] ?>">

                    <!-- Cliente -->
                    <div class="form-group">
    <label>Cliente</label>
    <select name="cliente_id" class="form-control" required>
        <?php foreach ($clientes as $cliente): ?>
            <option value="<?= $cliente['id'] ?>" 
                <?= (isset($prestamo['cliente_id']) && $prestamo['cliente_id'] == $cliente['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cliente['nombres']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

                    <!-- Garantía -->
                    <div class="form-group">
                        <label>Garantía</label>
                        <input type="text" name="garantia" class="form-control" value="<?= htmlspecialchars($prestamo['garantia']) ?>" required>
                    </div>

                    <!-- Fecha de Préstamo -->
                    <div class="form-group">
                        <label>Fecha de Préstamo</label>
                        <input type="date" name="fecha_prestamo" class="form-control" value="<?= $prestamo['fecha_prestamo'] ?>" required>
                    </div>

                    <!-- Fecha de Vencimiento -->
                    <div class="form-group">
                        <label>Vence</label>
                        <input type="date" name="vence" class="form-control" value="<?= $prestamo['vence'] ?>" required>
                    </div>

                    <!-- Tiempo -->
                    <div class="form-group">
                        <label>Tiempo (meses)</label>
                        <input type="number" name="tiempo" class="form-control" value="<?= $prestamo['tiempo'] ?>" required>
                    </div>

                    <!-- Interés -->
                    <div class="form-group">
                        <label>% Interés</label>
                        <input type="number" step="0.01" name="interes" class="form-control" value="<?= $prestamo['interes'] ?>" required>
                    </div>

                    <!-- Capital -->
                    <div class="form-group">
                        <label>Capital</label>
                        <input type="number" step="0.01" name="capital" class="form-control" value="<?= $prestamo['capital'] ?>" required>
                    </div>

                    <!-- Interés + Capital -->
                   

                    <!-- Interés Cobrado -->
                   

                    <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>


<script>
    function confirmarEliminacion() {
        return confirm("¿Estás seguro de que deseas eliminar este préstamo?");
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tiempoInput = document.getElementById('tiempo');
        const interesInput = document.getElementById('interes');
        const capitalInput = document.getElementById('capital');
        const interesBInput = document.getElementById('interes_capital');
        const interesCobradoInput = document.getElementById('interes_cobrado');

        function calcularValores() {
            const tiempo = parseFloat(tiempoInput.value) || 0;
            const interes = parseFloat(interesInput.value) / 100 || 0; // Convertimos % a decimal
            const capital = parseFloat(capitalInput.value) || 0;

            // Cálculo de Interés / B
            const interesB = tiempo * interes * capital;
            interesBInput.value = interesB.toFixed(2);

            // Cálculo de Interés Cobrado (A+B)
            const interesCobrado = capital + interesB;
            interesCobradoInput.value = interesCobrado.toFixed(2);
        }

        // Detectar cambios en los campos y recalcular
        tiempoInput.addEventListener('input', calcularValores);
        interesInput.addEventListener('input', calcularValores);
        capitalInput.addEventListener('input', calcularValores);
    });
</script>
<script>
    document.getElementById('cliente').addEventListener('input', function () {
        let input = this;
        let datalist = document.getElementById('clientes').options;
        let hiddenInput = document.getElementById('cliente_id');

        hiddenInput.value = ''; // Limpiar el campo oculto

        for (let i = 0; i < datalist.length; i++) {
            if (datalist[i].value === input.value) {
                hiddenInput.value = datalist[i].dataset.id;
                break;
            }
        }
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
