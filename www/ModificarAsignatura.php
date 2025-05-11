<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (admin o editor)
verificarRol(['admin', 'editor']);

// Conexión a la base de datos
include("php/conexion.php");

// Obtener el ID de la asignatura desde la URL y validarlo
if (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
    $asignatura_id = intval($_GET['id']);

    try {
        // Consulta para obtener los datos de la asignatura
        $query = "
            SELECT a.*, h.id as horario_id, h.dia_semana, h.hora_inicio, h.hora_fin
            FROM asignaturas a
            LEFT JOIN horarios h ON a.id = h.asignatura_id
            WHERE a.id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $asignatura_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $asignatura = null;
        $horarios = [];

        while ($fila = $result->fetch_assoc()) {
            if (!$asignatura) {
                $asignatura = $fila; // Guarda los datos de la asignatura una sola vez
            }
            if ($fila['dia_semana']) {
                $horarios[] = [
                    'id' => $fila['horario_id'],
                    'dia_semana' => $fila['dia_semana'],
                    'hora_inicio' => $fila['hora_inicio'],
                    'hora_fin' => $fila['hora_fin']
                ];
            }
        }

        if (!$asignatura) {
            die("Asignatura no encontrada.");
        }

        // Si no hay horarios, preparar un array vacío para el JavaScript
        if (empty($horarios)) {
            $horarios[] = ['dia_semana' => '', 'hora_inicio' => '', 'hora_fin' => ''];
        }
    } catch (Exception $e) {
        die("Error al obtener datos de la asignatura: " . htmlspecialchars($e->getMessage()));
    }

    // Consultas para obtener profesores y aulas existentes
    $query_profesores = "SELECT id, CONCAT(nombre, ' ', apellidos) AS nombre_completo FROM profesores ORDER BY apellidos, nombre";
    $stmt_profesores = $conn->prepare($query_profesores);
    $stmt_profesores->execute();
    $result_profesores = $stmt_profesores->get_result();

    $query_aulas = "SELECT id, numero_aula FROM aulas ORDER BY numero_aula";
    $stmt_aulas = $conn->prepare($query_aulas);
    $stmt_aulas->execute();
    $result_aulas = $stmt_aulas->get_result();
} else {
    die("ID de la asignatura no especificado o inválido.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Asignatura</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function agregarHorario(dia = '', inicio = '', fin = '') {
            const contenedor = document.getElementById('horarios-container');
            const nuevoHorario = document.createElement('div');
            nuevoHorario.className = 'horario-item';
            nuevoHorario.innerHTML = `
                <label for="dia_semana[]">Día de la Semana:</label>
                <select name="dia_semana[]" required>
                    <option value="Lunes" ${dia === 'Lunes' ? 'selected' : ''}>Lunes</option>
                    <option value="Martes" ${dia === 'Martes' ? 'selected' : ''}>Martes</option>
                    <option value="Miércoles" ${dia === 'Miércoles' ? 'selected' : ''}>Miércoles</option>
                    <option value="Jueves" ${dia === 'Jueves' ? 'selected' : ''}>Jueves</option>
                    <option value="Viernes" ${dia === 'Viernes' ? 'selected' : ''}>Viernes</option>
                </select>
                <label for="hora_inicio[]">Hora Inicio:</label>
                <input type="time" name="hora_inicio[]" value="${inicio}" required>
                <label for="hora_fin[]">Hora Fin:</label>
                <input type="time" name="hora_fin[]" value="${fin}" required>
                <button type="button" onclick="eliminarHorario(this)" class="delete-btn small">Eliminar</button>
            `;
            contenedor.appendChild(nuevoHorario);
        }

        function eliminarHorario(boton) {
            // Verificar si este es el último horario
            const horarios = document.querySelectorAll('.horario-item');
            if (horarios.length <= 1) {
                alert('Debe haber al menos un horario');
                return;
            }
            boton.parentElement.remove();
        }
        
        // Añadir al menos un horario cuando la página cargue si no hay ninguno
        window.onload = function() {
            const horarios = document.querySelectorAll('.horario-item');
            if (horarios.length === 0) {
                agregarHorario();
            }
        };
    </script>
</head>
<body>
    <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

    <main class="content">
        <h1>Modificar Asignatura</h1>

        <!-- Formulario para modificar asignatura -->
        <form action="php/ActualizarAsignatura.php" method="POST" class="form-container">
            <!-- Campo oculto para enviar el ID de la asignatura -->
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($asignatura_id); ?>">

            <div class="form-group">
                <label for="nombre_asignatura">Nombre Asignatura:</label>
                <input type="text" id="nombre_asignatura" name="nombre_asignatura" 
                       value="<?php echo htmlspecialchars($asignatura['nombre_asignatura'] ?? ''); ?>" 
                       maxlength="100" required>

                <label for="grupo">Grupo:</label>
                <input type="text" id="grupo" name="grupo" 
                       value="<?php echo htmlspecialchars($asignatura['grupo'] ?? ''); ?>" 
                       maxlength="10" required>

                <!-- Selector de Profesor -->
                <label for="profesor_id">Profesor:</label>
                <select id="profesor_id" name="profesor_id" required>
                    <option value="">-- Seleccione un profesor --</option>
                    <?php while ($profesor = $result_profesores->fetch_assoc()) { ?>
                        <option value="<?php echo htmlspecialchars($profesor['id']); ?>" 
                                <?php if ($asignatura['profesor_id'] == $profesor['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($profesor['nombre_completo']); ?>
                        </option>
                    <?php } ?>
                </select>

                <!-- Selector de Aula -->
                <label for="aula_id">Aula:</label>
                <select id="aula_id" name="aula_id" required>
                    <option value="">-- Seleccione un aula --</option>
                    <?php while ($aula = $result_aulas->fetch_assoc()) { ?>
                        <option value="<?php echo htmlspecialchars($aula['id']); ?>" 
                                <?php if ($asignatura['aula_id'] == $aula['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($aula['numero_aula']); ?>
                        </option>
                    <?php } ?>
                </select>

                <!-- Contenedor de horarios dinámicos -->
                <div id="horarios-section">
                    <h3>Horarios</h3>
                    <p>Modifica los horarios de esta asignatura:</p>
                    <button type="button" onclick="agregarHorario()" class="add-btn small">Añadir Horario</button>
                    <div id="horarios-container">
                        <!-- Los horarios se cargarán con JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <button type="submit">Guardar Cambios</button>
            <button type="button" onclick="window.location.href='ListadoAsignaturas.php'">Volver</button>
        </form>

    </main>
    
    <!-- Cargar los horarios existentes -->
    <script>
        <?php foreach ($horarios as $horario): ?>
        agregarHorario(
            '<?php echo htmlspecialchars($horario['dia_semana']); ?>', 
            '<?php echo htmlspecialchars($horario['hora_inicio']); ?>', 
            '<?php echo htmlspecialchars($horario['hora_fin']); ?>'
        );
        <?php endforeach; ?>
    </script>
</body>
</html>