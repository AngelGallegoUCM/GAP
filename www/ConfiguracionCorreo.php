<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (solo admin)
if ($_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Incluir la conexión a la base de datos
include("php/conexion.php");

// Variables para mostrar mensajes
$mensaje = '';
$tipo_mensaje = '';

// Verificar si existe la tabla de configuración
$check_table = $conn->query("SHOW TABLES LIKE 'configuracion_sistema'");
if ($check_table->num_rows == 0) {
    // Crear la tabla si no existe
    $sql_create_table = "CREATE TABLE configuracion_sistema (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        clave VARCHAR(50) NOT NULL UNIQUE,
        valor TEXT,
        descripcion VARCHAR(255),
        fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql_create_table) === TRUE) {
        // Insertar configuraciones iniciales
        $sql_insert = "INSERT INTO configuracion_sistema (clave, valor, descripcion) VALUES 
            ('email_remitente', '', 'Correo electrónico desde el que se envían las notificaciones'),
            ('email_password', '', 'Contraseña del correo electrónico (encriptada)'),
            ('email_servidor', 'smtp.gmail.com', 'Servidor SMTP para envío de correos'),
            ('email_puerto', '587', 'Puerto del servidor SMTP'),
            ('email_seguridad', 'tls', 'Tipo de seguridad (tls, ssl, ninguna)')";
        $conn->query($sql_insert);
    }
}

// Obtener configuración actual
$query = "SELECT * FROM configuracion_sistema WHERE clave LIKE 'email_%'";
$result = $conn->query($query);
$config = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $config[$row['clave']] = $row['valor'];
    }
}

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_config'])) {
    try {
        // Actualizar email remitente
        $email_remitente = filter_var($_POST['email_remitente'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email_remitente, FILTER_VALIDATE_EMAIL) && !empty($email_remitente)) {
            throw new Exception("El formato del correo electrónico no es válido.");
        }
        
        // Actualizar contraseña solo si se ha proporcionado una nueva
        $email_password = $_POST['email_password'];
        $password_encrypted = '';
        
        if (!empty($email_password)) {
            // Encriptar la contraseña antes de guardarla
            $password_encrypted = password_hash($email_password, PASSWORD_DEFAULT);
        } else {
            // Mantener la contraseña anterior si existe
            $stmt = $conn->prepare("SELECT valor FROM configuracion_sistema WHERE clave = 'email_password'");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $password_encrypted = $row['valor'];
            }
        }
        
        // Actualizar servidor SMTP
        $email_servidor = filter_var($_POST['email_servidor'], FILTER_SANITIZE_STRING);
        
        // Actualizar puerto
        $email_puerto = filter_var($_POST['email_puerto'], FILTER_SANITIZE_NUMBER_INT);
        
        // Actualizar tipo de seguridad
        $email_seguridad = filter_var($_POST['email_seguridad'], FILTER_SANITIZE_STRING);
        
        // Preparar las actualizaciones
        $updates = [
            ['email_remitente', $email_remitente],
            ['email_password', $password_encrypted],
            ['email_servidor', $email_servidor],
            ['email_puerto', $email_puerto],
            ['email_seguridad', $email_seguridad]
        ];
        
        // Actualizar cada configuración
        $stmt = $conn->prepare("UPDATE configuracion_sistema SET valor = ? WHERE clave = ?");
        
        foreach ($updates as $update) {
            $stmt->bind_param("ss", $update[1], $update[0]);
            $stmt->execute();
        }
        
        // Mensaje de éxito
        $mensaje = "Configuración guardada correctamente.";
        $tipo_mensaje = "success";
        
        // Actualizar los valores mostrados
        $result = $conn->query($query);
        $config = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $config[$row['clave']] = $row['valor'];
            }
        }
        
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Cerrar la conexión
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Correo Electrónico</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .config-container {
            max-width: 800px;
            margin: 20px auto;
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .config-title {
            color: #003366;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 15px;
        }
        
        .form-control:focus {
            border-color: #4e73df;
            outline: none;
            box-shadow: 0 0 0 2px rgba(78, 115, 223, 0.2);
        }
        
        .form-text {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #6c757d;
        }
        
        .btn-container {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }
        
        .btn-primary {
            background-color: #4e73df;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #3a5fc8;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .section-info {
            background-color: #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .section-info h3 {
            margin-top: 0;
            color: #495057;
            font-size: 16px;
        }
        
        .section-info p {
            margin-bottom: 0;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include("php/sidebar.php"); ?>
        
        <main class="main-content">
            <div class="config-container">
                <h1 class="config-title">Configuración de Correo Electrónico</h1>
                
                <?php if(!empty($mensaje)): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>
                
                <div class="section-info">
                    <h3>Información importante</h3>
                    <p>Esta configuración es necesaria para el envío de correos electrónicos desde el sistema, como las notificaciones de incidencias a profesores.</p>
                </div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="email_remitente">Correo Electrónico Remitente:</label>
                        <input type="email" id="email_remitente" name="email_remitente" class="form-control" 
                               value="<?php echo htmlspecialchars($config['email_remitente'] ?? ''); ?>" required>
                        <span class="form-text">Debe ser una dirección válida que permita envío de correos SMTP.</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="email_password">Contraseña:</label>
                        <input type="password" id="email_password" name="email_password" class="form-control" 
                               placeholder="<?php echo empty($config['email_password']) ? 'Introduzca contraseña' : 'Dejar en blanco para mantener la actual'; ?>">
                        <span class="form-text">La contraseña se almacenará de forma segura. Deje en blanco para mantener la actual.</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="email_servidor">Servidor SMTP:</label>
                        <input type="text" id="email_servidor" name="email_servidor" class="form-control" 
                               value="<?php echo htmlspecialchars($config['email_servidor'] ?? 'smtp.gmail.com'); ?>">
                        <span class="form-text">Por ejemplo: smtp.gmail.com, smtp.office365.com, etc.</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="email_puerto">Puerto SMTP:</label>
                        <input type="number" id="email_puerto" name="email_puerto" class="form-control" 
                               value="<?php echo htmlspecialchars($config['email_puerto'] ?? '587'); ?>">
                        <span class="form-text">Puertos comunes: 587 (TLS), 465 (SSL), 25 (sin cifrado).</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="email_seguridad">Tipo de Seguridad:</label>
                        <select id="email_seguridad" name="email_seguridad" class="form-control">
                            <option value="tls" <?php echo (($config['email_seguridad'] ?? 'tls') == 'tls') ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo (($config['email_seguridad'] ?? '') == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                            <option value="ninguna" <?php echo (($config['email_seguridad'] ?? '') == 'ninguna') ? 'selected' : ''; ?>>Ninguna</option>
                        </select>
                        <span class="form-text">La mayoría de proveedores requieren TLS o SSL.</span>
                    </div>
                    
                    <div class="btn-container">
                        <a href="index.php" class="btn-secondary">Cancelar</a>
                        <button type="submit" name="guardar_config" class="btn-primary">Guardar Configuración</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>