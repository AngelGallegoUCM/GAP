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
    <title>Datos del Aula</title>
    <link rel="stylesheet" href="stylesDatos.css">
    <style>
        /* Estilos para el horario */
        .horario-container {
            display: none;
            margin-top: 20px;
            width: 100%;
            overflow-x: auto;
        }
        
        .horario-title {
            margin-bottom: 10px;
            font-weight: bold;
            text-align: center;
            font-size: 16px;
            text-transform: uppercase;
        }
        
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
            height: 40px;
            vertical-align: middle;
            font-size: 12px;
        }
        
        .horario-table td.hora {
            background-color: #e6f2ff; /* Azul claro para las horas */
            font-weight: bold;
        }
        
        .horario-table td.asignatura {
            font-size: 11px;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
            background-color: #f8f9fa;
        }
        
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
        
        .info-buttons {
            display: flex;
            margin-bottom: 15px;
            gap: 10px;
        }
        
        .info-btn {
            padding: 8px 15px;
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .info-btn:hover {
            background-color: #2e59d9;
        }
        
        .info-btn.active {
            background-color: #224abe;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php
    // Conexión a la base de datos
    include("php/conexion.php");

    // Validar y obtener el ID del aula
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $aula_id = intval($_GET['id']);

        // Consulta para obtener los datos del aula
        $query_aula = "SELECT * FROM aulas WHERE id = ?";
        $stmt = $conn->prepare($query_aula);
        $stmt->bind_param("i", $aula_id);
        $stmt->execute();
        $result_aula = $stmt->get_result();

        if ($result_aula->num_rows > 0) {
            $aula = $result_aula->fetch_assoc();
        } else {
            die("Aula no encontrada.");
        }

        // Consulta para obtener las asignaturas en el aula
        $query_asignaturas = "
            SELECT a.nombre_asignatura, 
                   a.grupo, 
                   CONCAT(p.nombre, ' ', p.apellidos) AS profesor_nombre, 
                   h.dia_semana,
                   h.hora_inicio, 
                   h.hora_fin
            FROM asignaturas a
            JOIN profesores p ON a.profesor_id = p.id
            LEFT JOIN horarios h ON a.id = h.asignatura_id
            WHERE a.aula_id = ?";

        $stmt = $conn->prepare($query_asignaturas);
        $stmt->bind_param("i", $aula_id);
        $stmt->execute();
        $result_asignaturas = $stmt->get_result();
        
        // Preparar el array de horarios por día y hora
        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
        
        // Las horas para mostrar en la cuadrícula (9:00 - 20:00)
        $horas_display = [];
        for ($h = 9; $h <= 20; $h++) {
            $horas_display[] = $h;
        }
        
        // Estructura para almacenar las clases con su duración
        $horario_clases = [];
        
        // Procesar los datos del horario
        $asignaturas_list = [];
        if ($result_asignaturas && $result_asignaturas->num_rows > 0) {
            // Guardar todos los resultados para la tabla de asignaturas
            while ($row = $result_asignaturas->fetch_assoc()) {
                // Guardar para la tabla de asignaturas
                $asignaturas_list[] = $row;
                
                // Si tiene horario, procesar para la tabla de horario
                if (!empty($row['dia_semana']) && !empty($row['hora_inicio']) && !empty($row['hora_fin'])) {
                    $dia = $row['dia_semana'];
                    $hora_inicio_obj = new DateTime($row['hora_inicio']);
                    $hora_fin_obj = new DateTime($row['hora_fin']);
                    
                    // Obtener la hora como número entero
                    $hora_inicio_num = (int)$hora_inicio_obj->format('G');
                    $hora_fin_num = (int)$hora_fin_obj->format('G');
                    
                    // Verificar si la hora de finalización es exactamente en punto
                    // Si no, agregar una hora para asegurar que se cubra el período completo
                    if ($hora_fin_obj->format('i') > 0) {
                        $hora_fin_num++;
                    }
                    
                    // Calcular la duración en horas
                    $duracion = $hora_fin_num - $hora_inicio_num;
                    if ($duracion < 1) $duracion = 1; // Mínimo de 1 hora
                    
                    // Verificar si la hora está dentro del rango de visualización (9-20)
                    if ($hora_inicio_num >= 9 && $hora_inicio_num <= 20) {
                        // Guardar la información de la clase para este día y hora
                        $horario_clases[$dia][$hora_inicio_num] = [
                            'asignatura' => $row['nombre_asignatura'],
                            'grupo' => $row['grupo'],
                            'profesor' => $row['profesor_nombre'],
                            'duracion' => $duracion
                        ];
                    }
                }
            }
            // Reiniciar el puntero del resultado para usarlo después
            $result_asignaturas->data_seek(0);
        }
        
    } else {
        die("ID del aula no especificado o inválido.");
    }
    ?>

    <div class="container">
        <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->
        <main class="content">
            <h1>Aulas</h1>
            <h2>Datos del Aula <?php echo htmlspecialchars($aula['numero_aula']); ?></h2>

            <!-- Mostrar los datos del aula -->
            <div class="form-container">
                <!-- Datos básicos del aula -->
                <div class="form-group">
                    <label for="numero-aula">Número de Aula</label>
                    <input type="text" id="numero-aula" value="<?php echo htmlspecialchars($aula['numero_aula']); ?>" readonly>

                    <label for="capacidad">Capacidad</label>
                    <input type="text" id="capacidad" value="<?php echo htmlspecialchars($aula['capacidad']); ?>" readonly>
                </div>
                
                <!-- Botones de información -->
                <div class="info-buttons">
                    <button type="button" id="btn-asignaturas" class="info-btn active" onclick="mostrarSeccion('asignaturas')">Asignaturas</button>
                    <button type="button" id="btn-horario" class="info-btn" onclick="mostrarSeccion('horario')">Horario</button>
                </div>

                <!-- Sección de asignaturas -->
                <div id="seccion-asignaturas" class="info-section">
                    <!-- Tabla para Asignaturas -->
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Asignatura</th>
                                <th>Grupo</th>
                                <th>Profesor</th>
                                <th>Día</th>
                                <th>Hora de Inicio</th>
                                <th>Hora de Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($asignaturas_list)) {
                                foreach ($asignaturas_list as $asignatura) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($asignatura['nombre_asignatura']) . "</td>";
                                    echo "<td>" . htmlspecialchars($asignatura['grupo']) . "</td>";
                                    echo "<td>" . htmlspecialchars($asignatura['profesor_nombre']) . "</td>";
                                    echo "<td>" . htmlspecialchars($asignatura['dia_semana'] ?? '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($asignatura['hora_inicio'] ?? '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($asignatura['hora_fin'] ?? '-') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No hay asignaturas registradas para este aula.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Sección de horario -->
                <div id="seccion-horario" class="info-section horario-container">
                    <div class="horario-title">HORARIO DEL AULA <?php echo htmlspecialchars($aula['numero_aula']); ?></div>
                    <table class="horario-table">
                        <thead>
                            <tr>
                                <th width="5%"></th> <!-- Celda para las horas -->
                                <?php foreach ($dias as $dia): ?>
                                    <th width="19%"><?php echo strtoupper($dia); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Crear un array para rastrear celdas ocupadas por rowspan
                            $ocupadas = [];
                            foreach ($dias as $dia) {
                                foreach ($horas_display as $hora) {
                                    $ocupadas[$dia][$hora] = false;
                                }
                            }
                            
                            // Generamos filas para cada hora
                            foreach ($horas_display as $hora): 
                                echo "<tr>";
                                // Primera columna: la hora
                                echo "<td class='hora'>{$hora}</td>";
                                
                                // Para cada día de la semana
                                foreach ($dias as $dia): 
                                    // Si esta celda ya está ocupada por un rowspan, la saltamos
                                    if (isset($ocupadas[$dia][$hora]) && $ocupadas[$dia][$hora] === true) {
                                        continue;
                                    }
                                    
                                    // Verificar si hay clase en este día y hora
                                    if (isset($horario_clases[$dia][$hora])) {
                                        $clase = $horario_clases[$dia][$hora];
                                        // Si la duración es mayor a 1, usar rowspan
                                        if ($clase['duracion'] > 1) {
                                            echo "<td class='asignatura' rowspan='{$clase['duracion']}'>";
                                            echo htmlspecialchars($clase['asignatura']) . "<br>";
                                            echo "Grupo: " . htmlspecialchars($clase['grupo']) . "<br>";
                                            echo htmlspecialchars($clase['profesor']);
                                            echo "</td>";
                                            
                                            // Marcar las horas siguientes como ocupadas
                                            for ($i = 1; $i < $clase['duracion']; $i++) {
                                                if (isset($ocupadas[$dia][$hora + $i])) {
                                                    $ocupadas[$dia][$hora + $i] = true;
                                                }
                                            }
                                        } else {
                                            echo "<td class='asignatura'>";
                                            echo htmlspecialchars($clase['asignatura']) . "<br>";
                                            echo "Grupo: " . htmlspecialchars($clase['grupo']) . "<br>";
                                            echo htmlspecialchars($clase['profesor']);
                                            echo "</td>";
                                        }
                                    } else {
                                        // No hay clase a esta hora
                                        echo "<td></td>";
                                    }
                                endforeach;
                                
                                echo "</tr>";
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Botones de acción según el rol del usuario -->
                <div class="action-buttons">
                    <button type="button" class="volver" onclick="window.location.href='ListadoAulas.php'">Volver</button>
                    
                    <?php if (isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'editor'])): ?>
                    <a href="ModificarAula.php?id=<?php echo $aula_id; ?>" class="edit-btn">Modificar</a>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                    <button type="button" class="delete-btn" onclick="confirmarEliminacion(<?php echo $aula_id; ?>)">Eliminar</button>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Función para mostrar la sección seleccionada
        function mostrarSeccion(seccion) {
            console.log("Mostrando sección: " + seccion); // Depuración
            
            // Obtener referencias a las secciones
            var seccionAsignaturas = document.getElementById('seccion-asignaturas');
            var seccionHorario = document.getElementById('seccion-horario');
            var btnAsignaturas = document.getElementById('btn-asignaturas');
            var btnHorario = document.getElementById('btn-horario');
            
            // Ocultar todas las secciones
            if (seccionAsignaturas) seccionAsignaturas.style.display = 'none';
            if (seccionHorario) seccionHorario.style.display = 'none';
            
            // Quitar la clase 'active' de todos los botones
            if (btnAsignaturas) btnAsignaturas.classList.remove('active');
            if (btnHorario) btnHorario.classList.remove('active');
            
            // Mostrar la sección seleccionada
            if (seccion === 'asignaturas') {
                if (seccionAsignaturas) {
                    seccionAsignaturas.style.display = 'block';
                    if (btnAsignaturas) btnAsignaturas.classList.add('active');
                }
            } else if (seccion === 'horario') {
                if (seccionHorario) {
                    seccionHorario.style.display = 'block';
                    if (btnHorario) btnHorario.classList.add('active');
                }
            }
        }
        
        // Función para confirmar la eliminación del aula
        function confirmarEliminacion(id) {
            if (confirm("¿Estás seguro de que deseas eliminar esta aula? Si tiene asignaturas asignadas, no podrá ser eliminada.")) {
                window.location.href = "php/EliminarAula.php?id=" + id;
            }
        }
        
        // Iniciar mostrando la sección de asignaturas por defecto
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Página cargada, inicializando..."); // Depuración
            mostrarSeccion('asignaturas');
        });
    </script>
</body>
</html>