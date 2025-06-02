<?php
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'] ?? null;
    $placa = $_POST['placa'] ?? null;
    $observaciones = $_POST['observacion'] ?? [];
    $estadosPago = $_POST['estado_pago'] ?? [];
    $idDetalles = $_POST['id_detalle'] ?? [];

    if (!empty($idDetalles) && is_array($idDetalles) &&
        !empty($observaciones) && is_array($observaciones) &&
        !empty($estadosPago) && is_array($estadosPago) &&
        count($idDetalles) === count($observaciones) &&
        count($idDetalles) === count($estadosPago)) {

        try {
            $stmt = $conn->prepare("UPDATE lubricantes SET observacion = :observacion, estado_pago = :estado_pago WHERE id = :id");

            for ($i = 0; $i < count($idDetalles); $i++) {
                $id = $idDetalles[$i];
                $observacion = $observaciones[$i];
                $estadoPago = $estadosPago[$i];

                $stmt->bindParam(':observacion', $observacion);
                $stmt->bindParam(':estado_pago', $estadoPago);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
            }

            header("Location: consultar_fechas_lubricantes.php?cliente=" . urlencode($id_cliente) . "&placa=" . urlencode($placa) . "&mensaje=guardado");
            exit();

        } catch (PDOException $e) {
            echo "Error al guardar los cambios: " . $e->getMessage();
            exit();
        }

    } else {
        echo "Error: Los datos del formulario son inconsistentes o incompletos.";
        exit();
    }

} else {
    header("HTTP/1.1 405 Method Not Allowed");
    echo "Método no permitido.";
    exit();
}
?>