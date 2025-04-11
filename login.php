<?php


// Incluir la conexión a la base de datos
require_once 'config/db.php';

// Iniciar la sesión
session_start();

// Manejar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar los datos del formulario
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Verificar que los campos no estén vacíos
    if (!empty($username) && !empty($password)) {
        try {
            // Preparar la consulta para buscar el usuario
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            // Obtener el resultado
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar si el usuario existe
            if ($user) {
                // Comparar la contraseña ingresada con la almacenada en la base de datos
                if ($password === $user['password']) {
                    // Credenciales correctas: guardar datos en la sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    // Redirigir al index
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Contraseña incorrecta.";
                }
            } else {
                $error = "El usuario no existe.";
            }
        } catch (PDOException $e) {
            $error = "Error en la conexión: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, complete todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            display: flex;
            height: 100vh;
            font-family: Arial, sans-serif;
        }

        .left-panel {
            width: 50%;
            background: linear-gradient(to bottom, #56ccf2, #2f80ed);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-direction: column;
            text-align: center;
            padding: 30px;
        }

        .left-panel h1 {
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .left-panel p {
            font-size: 1rem;
            opacity: 0.9;
        }

        .rocket-icon {
            font-size: 5rem;
            margin-bottom: 20px;
        }

        .right-panel {
            width: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            border-radius: 10px;
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2f80ed;
        }

        .btn-primary {
            background-color: #2f80ed;
            border: none;
        }

        .btn-primary:hover {
            background-color: #1c5db9;
        }

        .form-control {
            border-radius: 5px;
            padding: 10px;
            font-size: 14px;
        }

        .alert {
            font-size: 14px;
        }
        .left-section {
            flex: 1;
            background: linear-gradient(45deg,rgb(10, 119, 96),rgb(105, 218, 162));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .left-section img {
            max-width: 1000px;
            height: auto;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="left-section">
        <img src="assets/images/kaw.png" alt="Sabitec GPS Soporte">
        
    </div>
    <div class="right-panel">
        <div class="login-container">
            <h2>Iniciar Sesión</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Ingrese su usuario" required>
                </div>
                <div class="form-group mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Ingrese su contraseña" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Ingresar</button>
            </form>
        </div>
    </div>
</body>
</html>
