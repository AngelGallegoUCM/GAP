<?php
// Iniciar sesión y verificar autenticación
require_once("verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (solo admin)
verificarRol(['admin']);

// Establecer el tipo de contenido de la respuesta como JSON
header('Content-Type: application/json');

// Conexión a la base de datos
include("conexion.php");

// Verificar si se recibió el ID del profesor
if (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
    $profesor_id = intval($_GET['id']);
    
    try {
        // Consulta para verificar si el profesor tiene asignaturas asociadas
        $check_stmt = $conn->prepare("
            SELECT a.id, a.nombre_asignatura, a.grupo
            FROM asignaturas a 
            WHERE a.profesor_id = ?
        ");
        $check_stmt->bind_param("i", $profesor_id);
        $check_stmt->execute();
        $asignaturas_result = $check_stmt->get_result();
        
        // Preparar la respuesta
        $response = [
            'tiene_asignaturas' => false,
            'asignaturas' => []
        ];
        
        // Si el profesor tiene asignaturas, preparamos la lista
        if ($asignaturas_result->num_rows > 0) {
            $response['tiene_asignaturas'] = true;
            
            while ($row = $asignaturas_result->fetch_assoc()) {
                $response['asignaturas'][] = $row['nombre_asignatura'] . ' (Grupo: ' . $row['grupo'] . ')';
            }
        }
        
        // Enviar la respuesta JSON
        echo json_encode($response);
        
    } catch (Exception $e) {
        // En caso de error, enviamos una respuesta de error
        echo json_encode([
            'error' => true,
            'mensaje' => 'Error al verificar las asignaturas: ' . $e->getMessage()
        ]);
    }
} else {
    // Si no hay ID válido
    echo json_encode([
        'error' => true,
        'mensaje' => 'ID del profesor no especificado o inválido'
    ]);
}
?>