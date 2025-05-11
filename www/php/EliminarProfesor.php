<?php
// Iniciar sesión y verificar autenticación
require_once("verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (solo admin)
verificarRol(['admin']);

// Conexión a la base de datos
include("conexion.php");

// Verificar si se recibió el ID del profesor
if (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
    $profesor_id = intval($_GET['id']);
    
    try {
        // Primero verificamos si el profesor tiene asignaturas asociadas
        $check_stmt = $conn->prepare("
            SELECT a.id, a.nombre_asignatura, a.grupo
            FROM asignaturas a 
            WHERE a.profesor_id = ?
        ");
        $check_stmt->bind_param("i", $profesor_id);
        $check_stmt->execute();
        $asignaturas_result = $check_stmt->get_result();
        
        // Si el profesor tiene asignaturas, no permitimos la eliminación
        if ($asignaturas_result->num_rows > 0) {
            // Construir una lista de asignaturas para mostrar
            $asignaturas = [];
            while ($row = $asignaturas_result->fetch_assoc()) {
                $asignaturas[] = $row['nombre_asignatura'] . ' (Grupo: ' . $row['grupo'] . ')';
            }
            
            // Almacenar las asignaturas en la sesión para mostrarlas en la página de error
            $_SESSION['error_eliminar_profesor'] = [
                'mensaje' => 'No se puede eliminar al profesor porque tiene las siguientes asignaturas asignadas:',
                'asignaturas' => $asignaturas
            ];
            
            // Redirigir a la página de error
            header("Location: ../error_eliminar_profesor.php");
            exit();
        }
        
        // Si no tiene asignaturas, procedemos con la eliminación
        $stmt = $conn->prepare("DELETE FROM profesores WHERE id = ?");
        $stmt->bind_param("i", $profesor_id);
        
        if ($stmt->execute()) {
            // Redirigir al listado con mensaje de éxito
            header("Location: ../ListadoProfesores.php?success=3");
            exit();
        } else {
            throw new Exception("Error al ejecutar la eliminación: " . $stmt->error);
        }
    } catch (Exception $e) {
        // Si ocurre cualquier otro error, mostrar un mensaje genérico
        $_SESSION['error_eliminar_profesor'] = [
            'mensaje' => 'Error al eliminar el profesor:',
            'error' => $e->getMessage()
        ];
        
        header("Location: ../error_eliminar_profesor.php");
        exit();
    }
} else {
    // Si no hay ID válido
    $_SESSION['error_eliminar_profesor'] = [
        'mensaje' => 'ID del profesor no especificado o inválido'
    ];
    
    header("Location: ../error_eliminar_profesor.php");
    exit();
}
?>