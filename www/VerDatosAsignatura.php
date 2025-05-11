<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();
// Para ver los datos solo se necesita estar autenticado, cualquier rol puede acceder
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos de Asignatura</title>
    <link rel="stylesheet" href="stylesDatos.css">
    <style>
        /* Estilos para botones de acción */
        .action-buttons {
            display: flex;
            margin-top: 20px;
            gap: 10px;
        }
        
        .action-buttons button, 
        .action-buttons a {
            padding: 8px 15px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            display: inline-block;
            text-align: center;
            font-size: 14px;
        }
        
        .volver {
            background-color: #6c757d;
        }
        
        .edit-btn {
            background-color: #28a745;
        }
        
        .delete-btn {
            background-color: #dc3545;
        }
        
        /* Estilos para el horario */
        .horario-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .horario-table th {
            background-color: #000080; /* Azul oscuro para los encabezados */
            color: white;
            text-align: center;
            padding: 8px;
            border: 1px solid #000;
        }
        
        .horario-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php
    // Conexión a la base de datos
    include("php/conexion.php");

    // Validar y obtener el ID de la asignatura
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $asignatura_id = intval($_GET['id']);

        // Consulta para obtener los datos básicos de la asignatura
        $query_asignatura = "
            SELECT a.id, a.nombre_asignatura, a.grupo, au.numero_aula, au.capacidad,
                   p.id as profesor_id, CONCAT(p.nombre, ' ', p.apellidos) AS profesor_nombre, 
                   p.CorreoPropio as profesor_correo
            FROM asignaturas a
            LEFT JOIN profesores p ON a.profesor_id = p.id
            LEFT JOIN aulas au ON a.aula_id = au.id
            WHERE a.id = ?";

        $stmt = $conn->prepare($query_asignatura);
        $stmt->bind_param("i", $asignatura_id);
        $stmt->execute();
        $result_asignatura = $stmt->get_result();

        if ($result_asignatura->num_rows > 0) {
            $asignatura = $result_asignatura->fetch_assoc();
        } else {
            die("Asignatura no encontrada.");
        }
        
        // Consulta para obtener los horarios de la asignatura
        $query_horarios = "
            SELECT dia_semana, hora_inicio, hora_fin
            FROM horarios
            WHERE asignatura_id = ?
            ORDER BY FIELD(dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'),
                     hora_inicio";

        $stmt_horarios = $conn->prepare($query_horarios);
        $stmt_horarios->bind_param("i", $asignatura_id);
        $stmt_horarios->execute();
        $result_horarios = $stmt_horarios->get_result();
        
    } else {
        die("ID de la asignatura no especificado o inválido.");
    }
    ?>

    <div class="container">
        <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->
        <main class="content">
            <h1>Asignaturas</h1>
            <h2>Datos de Asignatura: <?php echo htmlspecialchars($asignatura['nombre_asignatura']); ?></h2>

            <div class="form-container">
                <!-- Datos básicos de la asignatura -->
                <form>
                    <div class="form-group">
                        <label for="nombre_asignatura">Nombre de la Asignatura</label>
                        <input type="text" id="nombre_asignatura" value="<?php echo htmlspecialchars($asignatura['nombre_asignatura']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="grupo">Grupo</label>
                        <input type="text" id="grupo" value="<?php echo htmlspecialchars($asignatura['grupo']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="numero_aula">Número de Aula</label>
                        <input type="text" id="numero_aula" value="<?php echo htmlspecialchars($asignatura['numero_aula']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="capacidad_aula">Capacidad del Aula</label>
                        <input type="text" id="capacidad_aula" value="<?php echo htmlspecialchars($asignatura['capacidad']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="profesor">Profesor</label>
                        <input type="text" id="profesor" value="<?php echo htmlspecialchars($asignatura['profesor_nombre']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="profesor_correo">Correo del Profesor</label>
                        <input type="text" id="profesor_correo" value="<?php echo htmlspecialchars($asignatura['profesor_correo']); ?>" readonly>
                    </div>

                    <!-- Tabla de horarios -->
                    <h3>Horarios de la asignatura</h3>
                    <table class="horario-table">
                        <thead>
                            <tr>
                                <th>Día de la Semana</th>
                                <th>Hora de Inicio</th>
                                <th>Hora de Fin</th>
                                <th>Duración</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_horarios && $result_horarios->num_rows > 0) {
                                while ($horario = $result_horarios->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($horario['dia_semana']) . "</td>";
                                    echo "<td>" . htmlspecialchars($horario['hora_inicio']) . "</td>";
                                    echo "<td>" . htmlspecialchars($horario['hora_fin']) . "</td>";
                                    
                                    // Calcular la duración
                                    $inicio = new DateTime($horario['hora_inicio']);
                                    $fin = new DateTime($horario['hora_fin']);
                                    $intervalo = $inicio->diff($fin);
                                    
                                    $horas = $intervalo->h;
                                    $minutos = $intervalo->i;
                                    
                                    $duracion = '';
                                    if ($horas > 0) {
                                        $duracion .= $horas . ' hora' . ($horas > 1 ? 's' : '');
                                    }
                                    
                                    if ($minutos > 0) {
                                        if (!empty($duracion)) $duracion .= ' y ';
                                        $duracion .= $minutos . ' minuto' . ($minutos > 1 ? 's' : '');
                                    }
                                    
                                    echo "<td>" . $duracion . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>No hay horarios registrados para esta asignatura.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </form>

                <!-- Botones de acción según el rol del usuario -->
                <div class="action-buttons">
                    <button type="button" class="volver" onclick="window.location.href='ListadoAsignaturas.php'">Volver</button>
                    
                    <?php if (isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'editor'])): ?>
                    <a href="ModificarAsignatura.php?id=<?php echo $asignatura_id; ?>" class="edit-btn">Modificar</a>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                    <button type="button" class="delete-btn" onclick="confirmarEliminacion(<?php echo $asignatura_id; ?>)">Eliminar</button>
                    <?php endif; ?>
                </div>
                
                <!-- Enlaces adicionales -->
                <div style="margin-top: 20px;">
                    <a href="VerDatosProfesor.php?id=<?php echo htmlspecialchars($asignatura['profesor_id']); ?>">Ver datos del profesor</a>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Función para confirmar la eliminación de la asignatura
        function confirmarEliminacion(id) {
            if (confirm("¿Estás seguro de que deseas eliminar esta asignatura?")) {
                window.location.href = "php/EliminarAsignatura.php?id=" + id;
            }
        }
    </script>
</body>
</html>