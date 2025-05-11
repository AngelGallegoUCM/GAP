<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si hay un mensaje de error en la sesión
if (!isset($_SESSION['error_eliminar_asignatura'])) {
    // Si no hay mensaje de error, redirigir al listado de asignaturas
    header("Location: ListadoAsignaturas.php");
    exit();
}

// Obtener los datos del error
$error_data = $_SESSION['error_eliminar_asignatura'];
$mensaje = $error_data['mensaje'];
$info = isset($error_data['info']) ? $error_data['info'] : '';
$error_tecnico = isset($error_data['error']) ? $error_data['error'] : '';

// Limpiar el mensaje de error de la sesión para que no persista
unset($_SESSION['error_eliminar_asignatura']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error al Eliminar Asignatura</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .error-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff5f5;
            border-left: 5px solid #ff5252;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .error-title {
            color: #e53935;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .error-message {
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .info-box {
            background-color: #fff;
            border: 1px solid #ffcdd2;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .buttons {
            margin-top: 25px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .tech-error {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            color: #6c757d;
            display: none;
        }
        
        .show-tech-error {
            font-size: 12px;
            color: #6c757d;
            text-decoration: underline;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->
        
        <main class="main-content">
            <div class="error-container">
                <h1 class="error-title">No se puede eliminar la asignatura</h1>
                
                <div class="error-message">
                    <p><?php echo htmlspecialchars($mensaje); ?></p>
                    
                    <?php if (!empty($info)): ?>
                        <div class="info-box">
                            <p><?php echo htmlspecialchars($info); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="buttons">
                    <a href="ListadoAsignaturas.php" class="btn">Volver al listado</a>
                    <a href="#" class="btn btn-secondary" onclick="window.history.back()">Volver atrás</a>
                </div>
                
                <?php if (!empty($error_tecnico) && $_SESSION['rol'] === 'admin'): ?>
                    <button class="show-tech-error" onclick="mostrarErrorTecnico()">Mostrar detalles técnicos</button>
                    <div id="error-tecnico" class="tech-error">
                        <?php echo htmlspecialchars($error_tecnico); ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        function mostrarErrorTecnico() {
            const errorTecnico = document.getElementById('error-tecnico');
            if (errorTecnico.style.display === 'block') {
                errorTecnico.style.display = 'none';
            } else {
                errorTecnico.style.display = 'block';
            }
        }
    </script>
</body>
</html>