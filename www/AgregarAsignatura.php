<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (admin o editor)
verificarRol(['admin', 'editor']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Asignatura</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function agregarHorario() {
            const contenedor = document.getElementById('horarios-container');
            const nuevoHorario = document.createElement('div');
            nuevoHorario.className = 'horario-item';
            nuevoHorario.innerHTML = `
                <label for="dia_semana[]">Día de la Semana:</label>
                <select name="dia_semana[]" required>
                    <option value="Lunes">Lunes</option>
                    <option value="Martes">Martes</option>
                    <option value="Miércoles">Miércoles</option>
                    <option value="Jueves">Jueves</option>
                    <option value="Viernes">Viernes</option>
                </select>
                <label for="hora_inicio[]">Hora Inicio:</label>
                <input type="time" name="hora_inicio[]" required>
                <label for="hora_fin[]">Hora Fin:</label>
                <input type="time" name="hora_fin[]" required>
                <button type="button" onclick="eliminarHorario(this)">Eliminar</button>
            `;
            contenedor.appendChild(nuevoHorario);
        }

        function eliminarHorario(boton) {
            boton.parentElement.remove();
        }
        
        // Añadir al menos un horario cuando cargue la página
        window.onload = function() {
            // Verificar si no hay horarios ya creados
            if (document.querySelectorAll('.horario-item').length === 0) {
                agregarHorario();
            }
        };
    </script>
</head>
<body>
    <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

    <main class="content">
        <h1>Añadir Asignatura</h1>

        <?php
        // Conexión a la base de datos
        include("php/conexion.php");

        // Consulta preparada para obtener los profesores existentes
        $query_profesores = "SELECT id, CONCAT(nombre, ' ', apellidos) AS nombre_completo FROM profesores ORDER BY apellidos, nombre";
        $stmt_profesores = $conn->prepare($query_profesores);
        $stmt_profesores->execute();
        $result_profesores = $stmt_profesores->get_result();

        // Consulta preparada para obtener las aulas existentes
        $query_aulas = "SELECT id, numero_aula FROM aulas ORDER BY numero_aula";
        $stmt_aulas = $conn->prepare($query_aulas);
        $stmt_aulas->execute();
        $result_aulas = $stmt_aulas->get_result();
        ?>

        <!-- Formulario para añadir asignatura -->
        <form action="php/InsertarAsignatura.php" method="POST" class="form-container">
            <div class="form-group">
                <label for="nombre_asignatura">Nombre Asignatura:</label>
                <input type="text" id="nombre_asignatura" name="nombre_asignatura" 
                       maxlength="100" required>

                <label for="grupo">Grupo:</label>
                <input type="text" id="grupo" name="grupo" 
                       maxlength="10" required
                       placeholder="Ej: 1ºA">

                <!-- Selector de Profesor -->
                <label for="profesor_id">Profesor:</label>
                <select id="profesor_id" name="profesor_id" required>
                    <option value="">-- Seleccione un profesor --</option>
                    <?php while ($profesor = $result_profesores->fetch_assoc()) { ?>
                        <option value="<?php echo htmlspecialchars($profesor['id']); ?>">
                            <?php echo htmlspecialchars($profesor['nombre_completo']); ?>
                        </option>
                    <?php } ?>
                </select>

                <!-- Selector de Aula -->
                <label for="aula_id">Aula:</label>
                <select id="aula_id" name="aula_id" required>
                    <option value="">-- Seleccione un aula --</option>
                    <?php while ($aula = $result_aulas->fetch_assoc()) { ?>
                        <option value="<?php echo htmlspecialchars($aula['id']); ?>">
                            <?php echo htmlspecialchars($aula['numero_aula']); ?>
                        </option>
                    <?php } ?>
                </select>

                <!-- Contenedor de horarios dinámicos -->
                <div id="horarios-section">
                    <h3>Horarios</h3>
                    <p>Añade los horarios para esta asignatura:</p>
                    <button type="button" onclick="agregarHorario()" class="add-btn small">Añadir Horario</button>
                    <div id="horarios-container">
                        <!-- Aquí se añadirán los horarios dinámicamente -->
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <button type="submit">Guardar Asignatura</button>
            <button type="button" onclick="window.location.href='ListadoAsignaturas.php'">Volver</button>
        </form>

    </main>
</body>
</html>