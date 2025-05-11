<?php
// Iniciar sesión y verificar autenticación
require_once("verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (admin o editor)
verificarRol(['admin', 'editor']);

// Conexión a la base de datos
include("conexion.php");

// Verificar si se recibió el ID del día no lectivo
if (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
    $nolectivo_id = intval($_GET['id']);
    
    try {
        // Verificar si hay alguna relación con asistencias en esta fecha
        $check_query = "
            SELECT n.fecha, COUNT(a.id) as num_asistencias
            FROM nolectivo n
            LEFT JOIN asistencias a ON a.fecha = n.fecha
            WHERE n.id = ?
            GROUP BY n.fecha
        ";
        
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $nolectivo_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            
            // Si hay asistencias registradas en esta fecha, mostrar advertencia
            if ($row['num_asistencias'] > 0) {
                $_SESSION['error_eliminar_dia'] = [
                    'mensaje' => 'No se puede eliminar este día no lectivo porque hay asistencias registradas en esta fecha.',
                    'info' => 'Hay ' . $row['num_asistencias'] . ' registro(s) de asistencia para el día ' . date('d/m/Y', strtotime($row['fecha'])) . '.'
                ];
                
                header("Location: ../error_eliminar_dia.php");
                exit();
            }
        }
        
        // Preparar y ejecutar la consulta para eliminar
        $stmt = $conn->prepare("DELETE FROM nolectivo WHERE id = ?");
        $stmt->bind_param("i", $nolectivo_id);
        
        if ($stmt->execute()) {
            // Redirigir al listado con mensaje de éxito
            header("Location: ../ListadoNoLectivo.php?success=2");
            exit();
        } else {
            throw new Exception("Error al ejecutar la eliminación: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        // Almacenar mensaje de error para mostrar
        $_SESSION['error_eliminar_dia'] = [
            'mensaje' => 'Error al eliminar el día no lectivo.',
            'error' => $e->getMessage()
        ];
        
        header("Location: ../error_eliminar_dia.php");
        exit();
    }
} else {
    // ID no válido
    $_SESSION['error_eliminar_dia'] = [
        'mensaje' => 'ID del día no lectivo no especificado o inválido.'
    ];
    
    header("Location: ../error_eliminar_dia.php");
    exit();
}
?>