<?php
session_start();
require_once 'config/db.php'; // Asegúrate de que la conexión esté bien

// Verificar si se recibieron datos
if (!isset($_POST['id'])) {
    echo "Error: Datos incompletos";
    exit;
}

$id = $_POST['id'];
$fecha = $_POST['fecha'];
$placa = $_POST['placa'];
$marca_anio = $_POST['marca_anio'];
$cliente = $_POST['cliente'];
$cambio_aceite_grado = $_POST['cambio_aceite_grado'];
$f_aire = $_POST['f_aire'];
$f_aceite = $_POST['f_aceite'];
$lav_motor = $_POST['lav_motor'];
$bujias = $_POST['bujias'];
$hidrolina = $_POST['hidrolina'];
$monto = $_POST['monto'];
$forma_pago = $_POST['forma_pago'];

try {
    $stmt = $conn->prepare("UPDATE lubricantes SET 
        fecha=?, placa=?, marca_anio=?, cliente=?, cambio_aceite_grado=?, 
        f_aire=?, f_aceite=?, lav_motor=?, bujias=?, hidrolina=?, 
        monto=?, forma_pago=? WHERE id=?");

    $stmt->execute([$fecha, $placa, $marca_anio, $cliente, $cambio_aceite_grado,
        $f_aire, $f_aceite, $lav_motor, $bujias, $hidrolina, $monto, $forma_pago, $id]);
        header("Location: administrar_lubricante.php");
    echo "ok"; // Esto es lo que AJAX esperará para saber si fue exitoso
} catch (PDOException $e) {
    echo "Error al actualizar: " . $e->getMessage();
}
?>
