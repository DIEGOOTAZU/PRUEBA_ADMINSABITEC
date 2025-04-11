<?php
require_once 'config/db.php';

if (isset($_GET['cliente'])) {
    $cliente = $_GET['cliente'];

    try {
        $stmt = $conn->prepare("SELECT placa FROM vehiculos WHERE cliente = :cliente");
        $stmt->bindParam(':cliente', $cliente);
        $stmt->execute();
        $placas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($placas);
    } catch (PDOException $e) {
        echo json_encode([]);
    }
}
?>
