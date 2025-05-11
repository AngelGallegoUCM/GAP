<?php
// Conexión a la base de datos
include("php/conexion.php");

// Borrar todas las asistencias del día actual
$conn->query("DELETE FROM asistencias WHERE fecha = CURDATE()");

// Mapear los días de la semana en inglés a español
$dias_es = [
    'Monday'    => 'Lunes',
    'Tuesday'   => 'Martes',
    'Wednesday' => 'Miércoles',
    'Thursday'  => 'Jueves',
    'Friday'    => 'Viernes',
    'Saturday'  => 'Sábado',
    'Sunday'    => 'Domingo'
];

// Obtener el día actual en español
$hoy = $dias_es[date('l')];

// Verificar si hoy es un día no lectivo
$query_nolectivo = "SELECT * FROM nolectivo WHERE fecha = CURDATE()";
$result_nolectivo = $conn->query($query_nolectivo);

if ($result_nolectivo->num_rows > 0) {
    echo "Día no lectivo, no se genera asistencias.";
    exit();
}

// Consulta para obtener las asignaturas del día actual
$query = "
    SELECT a.id AS asignatura_id
    FROM horarios h
    JOIN asignaturas a ON h.asignatura_id = a.id
    WHERE h.dia_semana = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $hoy);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $asignatura_id = $row['asignatura_id'];

        // Insertar asistencia con presente = false
        $insert = "
            INSERT INTO asistencias (asignatura_id, fecha, presente)
            VALUES (?, CURDATE(), FALSE)";
        
        $stmt_insert = $conn->prepare($insert);
        $stmt_insert->bind_param("i", $asignatura_id);
        if (!$stmt_insert->execute()) {
            echo "Error al insertar asistencia: " . $stmt_insert->error . "<br>";
        }
    }
    echo "Asistencias generadas correctamente.";
} else {
    echo "No hay asignaturas para el día de hoy.";
}

$conn->close();
?>
