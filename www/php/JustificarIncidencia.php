<?php
// Iniciar sesión y verificar autenticación
require_once("verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (admin o editor)
verificarRol(['admin', 'editor']);

// Conexión a la base de datos
include("conexion.php");

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validación de entradas
    $errores = [];
    
    // Validar ID
    if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
        $errores[] = "ID de incidencia inválido.";
    }
    
    // Validar justificación
    if (empty($_POST['justificacion'])) {
        $errores[] = "La justificación es obligatoria.";
    }
    
    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        echo "<script>
            alert('Errores: " . addslashes(implode("\\n", $errores)) . "');
            window.history.back();
        </script>";
        exit();
    }
    
    try {
        // Sanitizar y convertir entradas
        $id = intval($_POST['id']);
        $justificacion = $_POST['justificacion'];
        
        // Verificar que la incidencia existe y no está ya justificada
        $check_stmt = $conn->prepare("SELECT justificada FROM incidencias WHERE id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            throw new Exception("La incidencia especificada no existe.");
        }
        
        $incidencia = $check_result->fetch_assoc();
        if ($incidencia['justificada'] == 1) {
            throw new Exception("Esta incidencia ya está justificada.");
        }

        // Actualizar la incidencia como justificada
        $query = "UPDATE incidencias SET justificada = 1, descripcion = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $justificacion, $id);

        if ($stmt->execute()) {
            
            // Mensaje de éxito y redirección
            echo "<script>
                alert('Incidencia justificada correctamente.');
                window.location.href='../ListadoIncidencias.php?success=2';
            </script>";
        } else {
            throw new Exception("Error al justificar la incidencia: " . $stmt->error);
        }

        $stmt->close();
        
    } catch (Exception $e) {
        echo "<script>
            alert('Error: " . addslashes($e->getMessage()) . "');
            window.history.back();
        </script>";
    }
    
    $conn->close();
} else {
    // Método de acceso no permitido
    echo "<script>
        alert('Método de acceso no permitido.');
        window.location.href='../ListadoIncidencias.php';
    </script>";
}
?>