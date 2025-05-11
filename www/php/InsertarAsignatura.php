<?php
// Iniciar sesión y verificar autenticación
require_once("verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (admin o editor)
verificarRol(['admin', 'editor']);

// Conexión a la base de datos
include("conexion.php");

// Verificar si se enviaron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validación de entradas
    $errores = [];
    
    // Validar nombre de asignatura
    if (empty($_POST['nombre_asignatura']) || strlen($_POST['nombre_asignatura']) > 100) {
        $errores[] = "El nombre de la asignatura es requerido y no debe exceder los 100 caracteres.";
    }
    
    // Validar grupo
    if (empty($_POST['grupo']) || strlen($_POST['grupo']) > 10) {
        $errores[] = "El grupo es requerido y no debe exceder los 10 caracteres.";
    }
    
    // Validar profesor_id
    if (!isset($_POST['profesor_id']) || !is_numeric($_POST['profesor_id']) || $_POST['profesor_id'] <= 0) {
        $errores[] = "Debe seleccionar un profesor válido.";
    }
    
    // Validar aula_id
    if (!isset($_POST['aula_id']) || !is_numeric($_POST['aula_id']) || $_POST['aula_id'] <= 0) {
        $errores[] = "Debe seleccionar un aula válida.";
    }
    
    // Validar que haya al menos un horario
    if (!isset($_POST['dia_semana']) || !is_array($_POST['dia_semana']) || count($_POST['dia_semana']) === 0) {
        $errores[] = "Debe agregar al menos un horario.";
    }
    
    // Validar cada horario
    if (isset($_POST['dia_semana']) && is_array($_POST['dia_semana'])) {
        $dias_validos = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
        
        for ($i = 0; $i < count($_POST['dia_semana']); $i++) {
            // Validar día
            if (!in_array($_POST['dia_semana'][$i], $dias_validos)) {
                $errores[] = "El día seleccionado no es válido.";
            }
            
            // Validar hora de inicio - Aceptar cualquier formato de hora válido
            if (empty($_POST['hora_inicio'][$i])) {
                $errores[] = "La hora de inicio es requerida.";
            }
            
            // Validar hora de fin - Aceptar cualquier formato de hora válido
            if (empty($_POST['hora_fin'][$i])) {
                $errores[] = "La hora de fin es requerida.";
            }
            
            // Validar que la hora de fin sea posterior a la de inicio
            if (!empty($_POST['hora_inicio'][$i]) && !empty($_POST['hora_fin'][$i])) {
                $hora_inicio = strtotime($_POST['hora_inicio'][$i]);
                $hora_fin = strtotime($_POST['hora_fin'][$i]);
                
                if ($hora_inicio === false) {
                    $errores[] = "El formato de la hora de inicio no es válido.";
                }
                
                if ($hora_fin === false) {
                    $errores[] = "El formato de la hora de fin no es válido.";
                }
                
                if ($hora_inicio !== false && $hora_fin !== false && $hora_fin <= $hora_inicio) {
                    $errores[] = "La hora de fin debe ser posterior a la hora de inicio.";
                }
            }
        }
    }
    
    // Si hay errores, mostrarlos y no procesar
    if (!empty($errores)) {
        echo "<div class='error-message'>";
        echo "<h3>Se encontraron errores:</h3>";
        echo "<ul>";
        foreach ($errores as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "<p><a href='javascript:history.back()'>Volver al formulario</a></p>";
        echo "</div>";
        exit();
    }
    
    try {
        // Iniciar transacción para que los cambios sean atómicos
        $conn->begin_transaction();
        
        // Preparar la consulta para insertar la asignatura
        $stmt = $conn->prepare("INSERT INTO asignaturas (nombre_asignatura, grupo, profesor_id, aula_id) VALUES (?, ?, ?, ?)");
        
        // Vincular parámetros
        $stmt->bind_param("ssii", $nombre_asignatura, $grupo, $profesor_id, $aula_id);
        
        // Asignar valores
        $nombre_asignatura = $_POST['nombre_asignatura'];
        $grupo = $_POST['grupo'];
        $profesor_id = intval($_POST['profesor_id']);
        $aula_id = intval($_POST['aula_id']);
        
        // Ejecutar la consulta
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar la asignatura: " . $stmt->error);
        }
        
        // Obtener el ID de la asignatura recién creada
        $asignatura_id = $conn->insert_id;
        
        // Insertar los horarios relacionados
        if (!empty($_POST['dia_semana']) && !empty($_POST['hora_inicio']) && !empty($_POST['hora_fin'])) {
            $stmt_horario = $conn->prepare("INSERT INTO horarios (asignatura_id, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)");
            
            for ($i = 0; $i < count($_POST['dia_semana']); $i++) {
                $dia_semana = $_POST['dia_semana'][$i];
                $hora_inicio = trim($_POST['hora_inicio'][$i]);
                $hora_fin = trim($_POST['hora_fin'][$i]);
                
                $stmt_horario->bind_param("isss", $asignatura_id, $dia_semana, $hora_inicio, $hora_fin);
                
                if (!$stmt_horario->execute()) {
                    throw new Exception("Error al insertar el horario: " . $stmt_horario->error);
                }
            }
            
            $stmt_horario->close();
        }
        
        // Confirmar la transacción
        $conn->commit();
        
        // Redirigir al listado tras éxito
        header("Location: ../ListadoAsignaturas.php?success=1");
        exit();
        
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollback();
        echo "Error al añadir la asignatura: " . htmlspecialchars($e->getMessage());
    }
}
?>