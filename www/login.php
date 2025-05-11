<?php
// Iniciar sesión
session_start();

// Redirigir si ya está autenticado
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// Variable para mensajes de error
$error = "";

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Incluir la conexión a la base de datos
    include("php/conexion.php");
    
    // Obtener y sanitizar las entradas
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password']; // No escapamos la contraseña aquí
    
    // Consulta preparada para evitar inyección SQL
    $stmt = $conn->prepare("SELECT id, username, password, rol FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verificar la contraseña con password_verify
        if (password_verify($password, $user['password'])) {
            // Guardar datos en la sesión
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['rol'] = $user['rol'];
            
            // Redirigir al usuario a la página principal
            header("Location: index.php");
            exit();
        } else {
            $error = "Usuario o contraseña incorrecta";
        }
    } else {
        $error = "Usuario o contraseña incorrecta";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema GAP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f4f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            background-color: #003366;
            padding: 30px 0;
            text-align: center;
            border-bottom: 4px solid #4e73df;
        }
        
        .login-header img {
            width: 120px;
            height: auto;
        }
        
        .login-form-container {
            padding: 30px;
        }
        
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="text"], 
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 15px;
            transition: border 0.3s ease;
        }
        
        input[type="text"]:focus, 
        input[type="password"]:focus {
            border-color: #4e73df;
            outline: none;
            box-shadow: 0 0 0 2px rgba(78, 115, 223, 0.2);
        }
        
        .login-btn {
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px 0;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        
        .login-btn:hover {
            background-color: #3a5fc8;
        }
        
        .error-message {
            background-color: #fff5f5;
            color: #e53935;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #e53935;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="img/logo.png" alt="Logo Universidad Complutense">
        </div>
        
        <div class="login-form-container">
            <h1>Iniciar Sesión</h1>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="login-btn">Iniciar Sesión</button>
            </form>
            
            <div class="footer-text">
                Sistema de Gestión de Asistencia de Profesores
            </div>
        </div>
    </div>
</body>
</html>