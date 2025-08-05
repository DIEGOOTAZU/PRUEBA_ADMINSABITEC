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

// Configuración de paginación


// Consultar los datos de la tabla de clientes con límite
try {
    $stmt = $conn->prepare("
    SELECT c.id, c.tipo_persona, c.nombres, c.tipo_documento, c.documento, c.sexo, 
           GROUP_CONCAT(t.telefono SEPARATOR ', ') AS telefonos
    FROM clientes c
    LEFT JOIN telefonos t ON c.id = t.cliente_id
    GROUP BY c.id
");

    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener los datos: " . $e->getMessage());
}


// Configuración de paginación
$limit = 15; // Número de registros por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Consultar los datos de la tabla de clientes con límite y offset
$stmt = $conn->prepare("SELECT c.id, c.tipo_persona, c.nombres, c.tipo_documento, c.documento, c.sexo, 
           GROUP_CONCAT(t.telefono SEPARATOR ', ') AS telefonos
    FROM clientes c
    LEFT JOIN telefonos t ON c.id = t.cliente_id
    GROUP BY c.id
    LIMIT :limit OFFSET :offset");
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular el total de registros para la paginación
$totalStmt = $conn->prepare("SELECT COUNT(*) FROM clientes");
$totalStmt->execute();
$totalClientes = $totalStmt->fetchColumn();
$totalPages = ceil($totalClientes / $limit);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Clientes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

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
        .pagination {
            justify-content: center;
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
    <a href="#">Tipos de Servicios</a>
    <a href="logout.php">Cerrar Sesión</a>
</div>

<script>
    // Función para mostrar/ocultar el submenú
    function toggleSubmenu(event) {
        event.preventDefault();
        const parent = event.target.closest('.has-submenu');
        parent.classList.toggle('active');
    }
    
</script>

<div class="toast" id="toast" style="position: absolute; top: 20px; right: 20px; display: none;">
    <div class="toast-header">
        <strong class="mr-auto">Notificación</strong>
        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast">&times;</button>
    </div>
    <div class="toast-body" id="toastMessage"></div>
</div>

    <!-- Main Content -->
    <div class="main-content">
        <h2 class="mb-4">Administrar Clientes</h2>
        <!-- Botón para nuevo cliente -->
        <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#nuevoClienteModal">Nuevo Cliente</button>
        <table class="table table-bordered">
            <thead class="table-header">
                <tr>
                    <th>#</th>
                    <th>Tipo Persona</th>
                    <th>Nombres</th>
                    <th>Tipo Documento</th>
                    <th>Documento</th>
                    <th>Sexo</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    <?php foreach ($clientes as $index => $cliente): ?>
        <?php
        // Obtener los teléfonos del cliente
        $stmt = $conn->prepare("SELECT telefono FROM telefonos WHERE cliente_id = :cliente_id");
        $stmt->bindParam(':cliente_id', $cliente['id']);
        $stmt->execute();
        $telefonos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($cliente['tipo_persona']) ?></td>
            <td><?= htmlspecialchars($cliente['nombres']) ?></td>
            <td><?= htmlspecialchars($cliente['tipo_documento']) ?></td>
            <td><?= htmlspecialchars($cliente['documento']) ?></td>
            <td><?= htmlspecialchars($cliente['sexo']) ?></td>
            
            <td>
    <!-- Mostrar el primer teléfono -->
    <?= htmlspecialchars($telefonos[0]['telefono'] ?? 'Sin teléfono') ?>
    
    <!-- Botón para mostrar otros teléfonos con ícono de ojo -->
    <?php if (count($telefonos) > 1): ?>
        <button class="btn btn-link btn-sm" data-toggle="modal" data-target="#telefonosModal<?= $cliente['id'] ?>">
            <i class="bi bi-eye"></i> <!-- Ícono de ojo -->
        </button>
    <?php endif; ?>
</td>

            <td>
                <!-- Botón Editar -->
                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editarClienteModal<?= $cliente['id'] ?>">Editar</button>
                <!-- Botón Eliminar con Confirmación -->
                <form action="eliminar_cliente.php" method="POST" style="display:inline;" onsubmit="return confirmarEliminacion();">
                    <input type="hidden" name="id" value="<?= $cliente['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                </form>
            </td>
        </tr>
  <!-- Modal para Editar Cliente -->
 <!-- Modal para Editar Cliente -->
 <div class="modal fade" id="editarClienteModal<?= $cliente['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editarClienteModalLabel<?= $cliente['id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarClienteModalLabel<?= $cliente['id'] ?>">Editar Cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formEditarCliente<?= $cliente['id'] ?>" action="editar_cliente.php" method="POST">
                    <input type="hidden" name="id" value="<?= $cliente['id'] ?>">

                    <div class="form-group">
                        <label>Tipo de Persona</label>
                        <select name="tipo_persona" class="form-control" required>
                            <option value="Natural" <?= $cliente['tipo_persona'] === 'Natural' ? 'selected' : '' ?>>Natural</option>
                            <option value="Jurídica" <?= $cliente['tipo_persona'] === 'Jurídica' ? 'selected' : '' ?>>Jurídica</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Nombres o Razón Social</label>
                        <input type="text" name="nombres" class="form-control" value="<?= htmlspecialchars($cliente['nombres']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Tipo de Documento</label>
                        <select name="tipo_documento" class="form-control" required>
                            <option value="DNI" <?= $cliente['tipo_documento'] === 'DNI' ? 'selected' : '' ?>>DNI</option>
                            <option value="RUC" <?= $cliente['tipo_documento'] === 'RUC' ? 'selected' : '' ?>>RUC</option>
                            <option value="Pasaporte" <?= $cliente['tipo_documento'] === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Documento</label>
                        <input type="text" name="documento" class="form-control" value="<?= htmlspecialchars($cliente['documento']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Sexo</label><br>
                        <input type="radio" name="sexo" value="M" <?= $cliente['sexo'] === 'M' ? 'checked' : '' ?>> Masculino
                        <input type="radio" name="sexo" value="F" <?= $cliente['sexo'] === 'F' ? 'checked' : '' ?>> Femenino
                    </div>

                    <!-- Sección de Teléfonos -->
                    <div id="telefonos-container-<?= $cliente['id'] ?>" class="telefonos-container">
    <?php
    // Obtener teléfonos del cliente
    $stmt = $conn->prepare("SELECT id, telefono FROM telefonos WHERE cliente_id = :cliente_id");
    $stmt->bindParam(':cliente_id', $cliente['id']);
    $stmt->execute();
    $telefonos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si hay teléfonos existentes, listarlos
    if (!empty($telefonos)) {
        foreach ($telefonos as $telefono): ?>
            <div class="form-group telefono-item">
                <div class="input-group">
                    <input type="text" name="telefonos_existentes[<?= $telefono['id'] ?>]" class="form-control" value="<?= htmlspecialchars($telefono['telefono']) ?>" required>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-success add-telefono">
                            <i class="bi bi-plus"></i>
                        </button>
                        <button type="button" class="btn btn-danger remove-telefono">
                            <i class="bi bi-dash"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach;
    } ?>

    <!-- Campo vacío para agregar nuevos teléfonos -->
    <div class="form-group telefono-item">
        <div class="input-group">
            <input type="text" name="telefonos_nuevos[]" class="form-control" placeholder="Nuevo Teléfono">
            <div class="input-group-append">
                <button type="button" class="btn btn-success add-telefono">
                    <i class="bi bi-plus"></i>
                </button>
                <button type="button" class="btn btn-danger remove-telefono">
                    <i class="bi bi-dash"></i>
                </button>
            </div>
        </div>
    </div>
</div>


                    <button type="submit" class="btn btn-primary w-100 mt-3">Guardar Cambios</button>
                    <button type="button" class="btn btn-secondary w-100 mt-2" data-dismiss="modal">Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>


                    
        <!-- Modal para mostrar más teléfonos -->
        <div class="modal fade" id="telefonosModal<?= $cliente['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="telefonosModalLabel<?= $cliente['id'] ?>" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="telefonosModalLabel<?= $cliente['id'] ?>">Teléfonos de <?= htmlspecialchars($cliente['nombres']) ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <ul>
                            <?php foreach ($telefonos as $telefono): ?>
                                <li><?= htmlspecialchars($telefono['telefono']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</tbody>

        </table>
        
    </div>

    <!-- Modal para Nuevo Cliente -->
<!-- Modal para Nuevo Cliente -->
<div class="modal fade" id="nuevoClienteModal" tabindex="-1" role="dialog" aria-labelledby="nuevoClienteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nuevoClienteModalLabel">Agregar Nuevo Cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formNuevoCliente" action="agregar_cliente.php" method="POST">
                    <div class="form-group">
                        <label>Tipo de Persona</label>
                        <select name="tipo_persona" class="form-control" required>
                            <option value="Natural">Natural</option>
                            <option value="Jurídica">Jurídica</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nombres o Razón Social</label>
                        <input type="text" name="nombres" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Documento</label>
                        <select name="tipo_documento" class="form-control" required>
                            <option value="DNI">DNI</option>
                            <option value="RUC">RUC</option>
                            <option value="Pasaporte">Pasaporte</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Documento</label>
                        <input type="text" name="documento" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Sexo</label><br>
                        <input type="radio" name="sexo" value="M" required> Masculino
                        <input type="radio" name="sexo" value="F" required> Femenino
                    </div>
                    <div id="telefonos-container-nuevo" class="telefonos-container">
                        <div class="form-group telefono-item">
                            <div class="input-group">
                                <input type="text" name="telefonos[]" class="form-control" placeholder="Teléfono" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-success add-telefono">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger remove-telefono">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-3">Guardar</button>
                    <button type="button" class="btn btn-secondary w-100 mt-2" data-dismiss="modal">Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Incluir Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">


    </div>

    <nav aria-label="Page navigation">
    <ul class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
    <script>
    document.getElementById('add-telefono').addEventListener('click', function() {
        const telefonosContainer = document.getElementById('telefonos-container');
        
        const newTelefonoDiv = document.createElement('div');
        newTelefonoDiv.classList.add('form-group');
        
        newTelefonoDiv.innerHTML = `
            <label>Teléfono</label>
            <input type="text" name="telefonos[]" class="form-control" required>
        `;
        
        telefonosContainer.appendChild(newTelefonoDiv);
    });
</script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarEliminacion() {
            return confirm("¿Estás seguro de que deseas eliminar este cliente?");
        }
    </script>
   <script>
document.body.addEventListener('click', function (event) {
    // Agregar un nuevo campo de teléfono
    if (event.target.classList.contains('add-telefono') || event.target.closest('.add-telefono')) {
        const telefonosContainer = event.target.closest('.telefonos-container');
        const newTelefonoDiv = document.createElement('div');
        newTelefonoDiv.classList.add('form-group', 'telefono-item', 'mt-2');
        newTelefonoDiv.innerHTML = `
            <div class="input-group">
                <input type="text" name="telefonos[]" class="form-control" placeholder="Teléfono" required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-success add-telefono">
                        <i class="bi bi-plus"></i>
                    </button>
                    <button type="button" class="btn btn-danger remove-telefono">
                        <i class="bi bi-dash"></i>
                    </button>
                </div>
            </div>
        `;
        telefonosContainer.appendChild(newTelefonoDiv);
    }

    // Eliminar un campo de teléfono
    if (event.target.classList.contains('remove-telefono') || event.target.closest('.remove-telefono')) {
        const telefonoItem = event.target.closest('.telefono-item');
        if (telefonoItem) {
            telefonoItem.remove();
        }
    }
});




</script>

<script>
document.getElementById("formNuevoCliente").addEventListener("submit", function(event) {
    event.preventDefault(); // Evitar el envío predeterminado

    const form = this;
    const formData = new FormData(form);
    
    fetch("agregar_cliente.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "error") {
            alert(data.message); // Mostrar mensaje si el documento ya existe
        } else {
            alert("Cliente agregado con éxito");
            location.reload(); // Recargar la página después de agregar correctamente
        }
    })
    .catch(error => console.error("Error:", error));
});
</script>

</body>
</html>
