<?php
// Iniciar sesión y verificar autenticación
require_once("verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (solo admin)
verificarRol(['admin']);

// Conexión a la base de datos
include("conexion.php");

// Verificar si se recibió el ID del aula
if (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
    $aula_id = intval($_GET['id']);
    
    try {
        // Primero verificamos si el aula tiene asignaturas asociadas
        $check_stmt = $conn->prepare("
            SELECT a.id, a.nombre_asignatura, a.grupo
            FROM asignaturas a 
            WHERE a.aula_id = ?
        ");
        $check_stmt->bind_param("i", $aula_id);
        $check_stmt->execute();
        $asignaturas_result = $check_stmt->get_result();
        
        // Si el aula tiene asignaturas, no permitimos la eliminación
        if ($asignaturas_result->num_rows > 0) {
            // Construir una lista de asignaturas para mostrar
            $asignaturas = [];
            while ($row = $asignaturas_result->fetch_assoc()) {
                $asignaturas[] = $row['nombre_asignatura'] . ' (Grupo: ' . $row['grupo'] . ')';
            }
            
            // Almacenar las asignaturas en la sesión para mostrarlas en la página de error
            $_SESSION['error_eliminar_aula'] = [
                'mensaje' => 'No se puede eliminar el aula porque tiene las siguientes asignaturas asignadas:',
                'asignaturas' => $asignaturas
            ];
            
            // Redirigir a la página de error
            header("Location: ../error_eliminar_aula.php");
            exit();
        }
        
        // Si no tiene asignaturas, procedemos con la eliminación
        $stmt = $conn->prepare("DELETE FROM aulas WHERE id = ?");
        $stmt->bind_param("i", $aula_id);
        
        if ($stmt->execute()) {
            // Redirigir al listado con mensaje de éxito
            header("Location: ../ListadoAulas.php?success=3");
            exit();
        } else {
            throw new Exception("Error al ejecutar la eliminación: " . $stmt->error);
        }
    } catch (Exception $e) {
        echo "Error al eliminar el aula: " . htmlspecialchars($e->getMessage());
    }
} else {
    echo "ID del aula no especificado o inválido.";
}
?>