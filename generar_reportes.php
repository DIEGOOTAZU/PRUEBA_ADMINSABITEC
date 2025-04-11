<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Reporte</title>
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
            margin-left: 20px;
            padding: 20px;
        }
        .invoice-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: auto;
            border: 1px solid #000;
        }
        .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 5px; /* Reduce el margen inferior */
}

.header img {
    max-width: 300px; /* Ajusta el tamaño máximo de la imagen */
    height: auto; /* Mantén las proporciones */
    margin: 0; /* Asegúrate de eliminar cualquier margen */
    padding: 0; /* Elimina cualquier relleno */
}

.invoice-title {
    text-align: right;
    margin: 0; /* Elimina márgenes adicionales */
    padding-left: 10px; /* Espacio entre imagen y texto */
}

.client-details {
    margin-top: 10px; /* Ajusta el espacio superior de los detalles del cliente */
    padding-top: 0; /* Evita relleno innecesario */
}



        .header .invoice-title {
            text-align: right;
        }
        ..invoice-details {
            margin: 20px 0;
            font-size: 14px;
        }
      

        .details-table, .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .details-table td, .details-table th, .items-table td, .items-table th {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color:rgb(137, 153, 170);
            color: white;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
        }
        .total-section h4 {
            margin: 5px 0;
        }
        .qr-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .qr-section img {
            max-width: 150px;
        }
        .btn-primary {
            margin: 10px 0;
        }

       
.card-title {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 8px;
}

.row {
    margin-top: 20px; /* Añade espacio entre la línea y las tarjetas */
}
.card {
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    padding: 10px; /* Opcional, para separar el contenido del borde */
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

<!-- Main Content -->
<div class="main-content">
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
    <img src="assets/images/enca.png" alt="Logo">
    <div class="invoice-title">
        <h3>Factura Electrónica</h3>
        <p>FFF1-000134</p>
    </div>
</div>

        <!-- Cliente Details -->
        <div class="row">
    <!-- Tarjeta: Detalles del Cliente -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Datos del Cliente</h5>
                <p><strong>RUC:</strong> 123456789</p>
                <p><strong>Denominación:</strong> Cliente Demo</p>
                <p><strong>Dirección:</strong> Av. Principal 123</p>
            </div>
        </div>
    </div>

    <!-- Tarjeta: Detalles de la Factura -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Información de la Factura</h5>
                <p><strong>Fecha Emisión:</strong> <?= date("d/m/Y") ?></p>
                <p><strong>Fecha Venc.:</strong> <?= date("d/m/Y", strtotime("+1 day")) ?></p>
                <p><strong>Moneda:</strong> Soles</p>
            </div>
        </div>
    </div>
</div>


        <!-- Items -->
        <div class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Cant.</th>
                        <th>UM</th>
                        <th>Cód.</th>
                        <th>Descripción</th>
                        <th>V/U</th>
                        <th>P/U</th>
                        <th>Importe</th>
                    </tr>
                </thead>
                <tbody id="invoice-body">
                    <tr>
                        <td>1</td>
                        <td>NIU</td>
                        <td>001</td>
                        <td>Detalle del Producto</td>
                        <td>500.00</td>
                        <td>590.00</td>
                        <td>590.00</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>ZZ</td>
                        <td>002</td>
                        <td>Detalle del Servicio</td>
                        <td>20.00</td>
                        <td>23.60</td>
                        <td>118.00</td>
                    </tr>
                </tbody>
            </table>
            <button class="btn btn-primary add-row" onclick="addRow()">Agregar Fila</button>
        </div>

        <!-- Totales -->
        <div class="total-section">
            <h4>Gravada: S/ 600.00</h4>
            <h4>IGV (18%): S/ 108.00</h4>
            <h4>Total: S/ 708.00</h4>
        </div>

        <!-- QR Section -->
        <div class="qr-section">
            <p>
                Importe en Letras: Setecientos ocho con 00/100 Soles<br>
                Guía de Remisión Remitente: 123
            </p>
            <img src="images/qr-code.png" alt="QR Code">
        </div>
        <button class="btn btn-primary" onclick="downloadPDF()">Guardar como PDF</button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const invoiceElement = document.querySelector('.invoice-container');
        const clone = invoiceElement.cloneNode(true);
        clone.querySelectorAll('.btn-primary, .add-row').forEach(element => element.remove());

        const options = {
            margin: 0.5,
            filename: 'Factura_Electronica.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        html2pdf().set(options).from(clone).save();
    }
</script>

</body>
</html>
