<?php
require_once 'config/db.php';

if (!isset($_GET['cliente'])) {
    echo json_encode([]);
    exit;
}

$cliente = $_GET['cliente'];

try {
    $stmt = $conn->prepare("SELECT DISTINCT placa FROM lubricantes WHERE cliente = ?");
    $stmt->execute([$cliente]);
    $placas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($placas);
} catch (PDOException $e) {
    echo json_encode([]);
}
