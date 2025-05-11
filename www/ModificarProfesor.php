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
    <title>Modificar Profesor</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

    <main class="content">
        <h1>Modificar Profesor</h1>

        <?php
        // Conexión a la base de datos
        include("php/conexion.php");

        // Validar y obtener el ID del profesor
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $profesor_id = intval($_GET['id']);

            // Consulta para obtener los datos del profesor
            $query_profesor = "SELECT id, nombre, apellidos, identificador, CorreoPropio, departamento_id FROM profesores WHERE id = ?";
            $stmt = $conn->prepare($query_profesor);
            $stmt->bind_param("i", $profesor_id);
            $stmt->execute();
            $result_profesor = $stmt->get_result();

            if ($result_profesor->num_rows > 0) {
                $profesor = $result_profesor->fetch_assoc();
            } else {
                die("Profesor no encontrado.");
            }

            // Consulta para obtener los departamentos
            $query_departamentos = "SELECT id, nombre_departamento FROM departamento ORDER BY nombre_departamento";
            $stmt = $conn->prepare($query_departamentos);
            $stmt->execute();
            $result_departamentos = $stmt->get_result();
        } else {
            die("ID del profesor no especificado o inválido.");
        }
        ?>

        <!-- Formulario para modificar profesor con validación -->
        <form action="php/ActualizarProfesor.php" method="POST" class="form-container">
            <!-- Campo oculto para el ID -->
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($profesor['id']); ?>">

            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" maxlength="100" required 
                       pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s]+" title="Solo se permiten letras y espacios"
                       value="<?php echo htmlspecialchars($profesor['nombre']); ?>">

                <label for="apellidos">Apellidos:</label>
                <input type="text" id="apellidos" name="apellidos" maxlength="150" required
                       pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s]+" title="Solo se permiten letras y espacios"
                       value="<?php echo htmlspecialchars($profesor['apellidos']); ?>">
                
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <label for="identificador">Identificador (único):</label>
                <input type="text" id="identificador" name="identificador" maxlength="50" required
                       pattern="[A-Za-z0-9\-_]+" title="Solo se permiten letras, números, guiones y guiones bajos"
                       value="<?php echo htmlspecialchars($profesor['identificador']); ?>">
                <small style="display: block; margin-top: 5px; color: #6c757d;">Este identificador debe ser único para cada profesor.</small>
                <?php else: ?>
                <input type="hidden" name="identificador" value="<?php echo htmlspecialchars($profesor['identificador']); ?>">
                <?php endif; ?>

                <label for="correoPropio">Correo Propio:</label>
                <input type="email" id="correoPropio" name="correoPropio" maxlength="255" required
                       value="<?php echo htmlspecialchars($profesor['CorreoPropio']); ?>">

                <!-- Selector de Departamento -->
                <label for="departamento">Departamento:</label>
                <select id="departamento" name="departamento_id" required>
                    <?php
                    if ($result_departamentos && $result_departamentos->num_rows > 0) {
                        while ($departamento = $result_departamentos->fetch_assoc()) {
                            $selected = ($departamento['id'] == $profesor['departamento_id']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($departamento['id']) . "' " . $selected . ">" . 
                                  htmlspecialchars($departamento['nombre_departamento']) . "</option>";
                        }
                    } else {
                        echo "<option value=''>No hay departamentos disponibles</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Botón para enviar -->
            <button type="submit">Guardar Cambios</button>

            <!-- Botón Volver -->
            <button type="button" onclick="window.location.href='VerDatosProfesor.php?id=<?php echo $profesor_id; ?>'">Volver</button>
        </form>

    </main>
</body>
</html>