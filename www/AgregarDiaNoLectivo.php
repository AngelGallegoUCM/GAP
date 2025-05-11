<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (admin o editor)
verificarRol(['admin', 'editor']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Día No Lectivo</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

    <div class="main-content">
        <h1>Añadir Día No Lectivo</h1>
        <p>Calendario > Añadir Día No Lectivo</p>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            include("php/conexion.php");
            
            // Validación de entradas
            $errores = [];
            
            // Validar fecha
            if (empty($_POST['fecha'])) {
                $errores[] = "La fecha es obligatoria";
            } else {
                // Verificar que la fecha tiene un formato válido (YYYY-MM-DD)
                $fecha = $_POST['fecha'];
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                    $errores[] = "El formato de fecha debe ser YYYY-MM-DD";
                } else {
                    // Verificar que la fecha sea válida
                    $fecha_parts = explode('-', $fecha);
                    if (!checkdate($fecha_parts[1], $fecha_parts[2], $fecha_parts[0])) {
                        $errores[] = "La fecha proporcionada no es válida";
                    }
                    
                    // Verificar si la fecha ya existe
                    $check_stmt = $conn->prepare("SELECT id FROM nolectivo WHERE fecha = ?");
                    $check_stmt->bind_param("s", $fecha);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $errores[] = "Ya existe un día no lectivo registrado para esta fecha";
                    }
                }
            }
            
            // Validar descripción
            if (empty($_POST['descripcion'])) {
                $errores[] = "La descripción es obligatoria";
            } elseif (strlen($_POST['descripcion']) > 255) {
                $errores[] = "La descripción no debe exceder los 255 caracteres";
            }
            
            // Si hay errores, mostrarlos
            if (!empty($errores)) {
                echo "<div class='error-msg'>";
                echo "<ul>";
                foreach ($errores as $error) {
                    echo "<li>" . htmlspecialchars($error) . "</li>";
                }
                echo "</ul>";
                echo "</div>";
            } else {
                // Si no hay errores, proceder con la inserción
                try {
                    $fecha = $_POST['fecha'];
                    $descripcion = $_POST['descripcion'];
                    
                    $stmt = $conn->prepare("INSERT INTO nolectivo (fecha, descripcion) VALUES (?, ?)");
                    $stmt->bind_param("ss", $fecha, $descripcion);
                    
                    if ($stmt->execute()) {
                        header("Location: ListadoNoLectivo.php?success=1");
                        exit();
                    } else {
                        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
                    }
                } catch (Exception $e) {
                    echo "<p class='error-msg'>Error al añadir el día no lectivo: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }

            $conn->close();
        }
        ?>

        <!-- Formulario para añadir día no lectivo -->
        <form action="" method="POST" class="form-container">
            <div class="form-group">
                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" required 
                       value="<?php echo isset($_POST['fecha']) ? htmlspecialchars($_POST['fecha']) : ''; ?>">

                <label for="descripcion">Descripción:</label>
                <input type="text" id="descripcion" name="descripcion" 
                       placeholder="Descripción" required maxlength="255"
                       value="<?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?>">
            </div>

            <!-- Botones -->
            <button type="submit">Guardar Día No Lectivo</button>
            <button type="button" onclick="window.location.href='ListadoNoLectivo.php'">Volver al Listado</button>
        </form>

    </div>
</body>
</html>