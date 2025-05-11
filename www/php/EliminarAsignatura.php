<?php
// Iniciar sesión y verificar autenticación
require_once("verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (solo admin)
verificarRol(['admin']);

// Conexión a la base de datos
include("conexion.php");

// Verificar si se recibió el ID de la asignatura
if (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
    $asignatura_id = intval($_GET['id']);
    
    try {
        // Comprobar si hay asistencias registradas para esta asignatura
        $check_stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM asistencias 
            WHERE asignatura_id = ?
        ");
        $check_stmt->bind_param("i", $asignatura_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        // Si hay asistencias, no permitir la eliminación
        if ($row['total'] > 0) {
            $_SESSION['error_eliminar_asignatura'] = [
                'mensaje' => 'No se puede eliminar la asignatura porque tiene registros de asistencia asociados.',
                'info' => 'Para eliminar esta asignatura, primero debe eliminar los registros de asistencia relacionados.'
            ];
            
            header("Location: ../error_eliminar_asignatura.php");
            exit();
        }
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Eliminar los horarios relacionados con la asignatura
        $stmt_horarios = $conn->prepare("DELETE FROM horarios WHERE asignatura_id = ?");
        $stmt_horarios->bind_param("i", $asignatura_id);
        
        if (!$stmt_horarios->execute()) {
            throw new Exception("Error al eliminar los horarios: " . $stmt_horarios->error);
        }

        // Eliminar la asignatura
        $stmt_asignatura = $conn->prepare("DELETE FROM asignaturas WHERE id = ?");
        $stmt_asignatura->bind_param("i", $asignatura_id);
        
        if (!$stmt_asignatura->execute()) {
            throw new Exception("Error al eliminar la asignatura: " . $stmt_asignatura->error);
        }
        
        // Confirmar transacción
        $conn->commit();
        
        // Redirigir al listado con mensaje de éxito
        header("Location: ../ListadoAsignaturas.php?success=3");
        exit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        
        // Almacenar el error para mostrarlo
        $_SESSION['error_eliminar_asignatura'] = [
            'mensaje' => 'Error al eliminar la asignatura.',
            'error' => $e->getMessage()
        ];
        
        header("Location: ../error_eliminar_asignatura.php");
        exit();
    }
} else {
    // ID no válido
    $_SESSION['error_eliminar_asignatura'] = [
        'mensaje' => 'ID de asignatura no especificado o inválido.'
    ];
    
    header("Location: ../error_eliminar_asignatura.php");
    exit();
}
?>