<?php
// Conexión a la base de datos
include("php/conexion.php");

// Seleccionar las asistencias no marcadas como presentes hoy
$query = "
    SELECT id, asignatura_id 
    FROM asistencias
    WHERE presente = FALSE
    AND fecha = CURDATE()";

$result = $conn->query($query);

// Verificar si hay asistencias pendientes
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $asistencia_id = $row['id'];
        $asignatura_id = $row['asignatura_id'];

        // Insertar incidencia con fecha_incidencia
        $insert = "
            INSERT INTO incidencias (asistencia_id, descripcion, fecha_incidencia)
            VALUES (?, 'Falta de asistencia no justificada', NOW())";

        $stmt = $conn->prepare($insert);
        $stmt->bind_param("i", $asistencia_id);

        if (!$stmt->execute()) {
            echo "Error al insertar incidencia: " . $stmt->error . "<br>";
        }
    }
    echo "Incidencias creadas correctamente.";
} else {
    echo "No hay asistencias pendientes para registrar como incidencia.";
}

$conn->close();
?>
