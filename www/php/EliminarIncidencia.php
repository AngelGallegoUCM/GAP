<?php
// Iniciar sesión y verificar autenticación
require_once("verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (solo admin)
verificarRol(['admin']);

// Conexión a la base de datos
include("conexion.php");

// Verificar si se recibió el ID de la incidencia
if (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
    $incidencia_id = intval($_GET['id']);
    
    try {
        // Verificar que la incidencia existe antes de eliminarla
        $check_stmt = $conn->prepare("SELECT id FROM incidencias WHERE id = ?");
        $check_stmt->bind_param("i", $incidencia_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            throw new Exception("La incidencia especificada no existe.");
        }
        
        // Preparar y ejecutar la consulta para eliminar la incidencia
        $stmt = $conn->prepare("DELETE FROM incidencias WHERE id = ?");
        $stmt->bind_param("i", $incidencia_id);
        
        if ($stmt->execute()) {            
            // Redirigir al listado con mensaje de éxito
            header("Location: ../ListadoIncidencias.php?success=1");
            exit();
        } else {
            throw new Exception("Error al eliminar la incidencia: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        // Mostrar mensaje de error y volver al listado
        echo "<script>
            alert('Error: " . addslashes($e->getMessage()) . "');
            window.location.href='../ListadoIncidencias.php';
        </script>";
    }
} else {
    // ID no válido
    echo "<script>
        alert('ID de incidencia no especificado o inválido.');
        window.location.href='../ListadoIncidencias.php';
    </script>";
}
?>