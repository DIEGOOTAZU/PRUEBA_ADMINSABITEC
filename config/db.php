<?php
// Configuración de la base de datos
$host = 'localhost';       // Dirección del servidor de base de datos (localhost si usas XAMPP o similar)
$dbname = 'gestion_cobros';  // Nombre de tu base de datos
$username = 'root';        // Usuario de la base de datos (por defecto es 'root' en XAMPP)
$password = '';            // Contraseña (por defecto está vacía en XAMPP)

try {
    // Crear la conexión a la base de datos
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Configurar el modo de error de PDO para excepciones
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Manejo de errores en caso de falla de conexión
    die("Error de conexión: " . $e->getMessage());
}
?>
