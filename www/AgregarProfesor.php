<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (solo admin)
verificarRol(['admin']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Profesor</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

    <main class="content">
        <h1>Añadir Profesor</h1>

        <?php
        // Conexión a la base de datos
        include("php/conexion.php");

        // Consulta preparada para obtener los departamentos
        $query_departamentos = "SELECT id, nombre_departamento FROM departamento ORDER BY nombre_departamento";
        $stmt = $conn->prepare($query_departamentos);
        $stmt->execute();
        $result_departamentos = $stmt->get_result();
        ?>

        <!-- Formulario para añadir profesor con validación -->
        <form action="php/InsertarProfesor.php" method="POST" class="form-container">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" maxlength="100" required 
                       pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s]+" title="Solo se permiten letras y espacios">

                <label for="apellidos">Apellidos:</label>
                <input type="text" id="apellidos" name="apellidos" maxlength="150" required
                       pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s]+" title="Solo se permiten letras y espacios">
                
                <label for="identificador">Identificador (único):</label>
                <input type="text" id="identificador" name="identificador" maxlength="50" required
                       pattern="[A-Za-z0-9\-_ ]+" title="Solo se permiten letras, números, guiones, guiones bajos y espacios">
                <small style="display: block; margin-top: 5px; color: #6c757d;">Este identificador debe ser único para cada profesor.</small>

                <label for="correoPropio">Correo Propio:</label>
                <input type="email" id="correoPropio" name="correoPropio" maxlength="255" required>

                <!-- Selector de Departamento -->
                <label for="departamento">Departamento:</label>
                <select id="departamento" name="departamento_id" required>
                    <?php
                    if ($result_departamentos && $result_departamentos->num_rows > 0) {
                        while ($departamento = $result_departamentos->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($departamento['id']) . "'>" . htmlspecialchars($departamento['nombre_departamento']) . "</option>";
                        }
                    } else {
                        echo "<option value=''>No hay departamentos disponibles</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Botón para enviar -->
            <button type="submit">Guardar Profesor</button>

            <!-- Botón Volver -->
            <button type="button" onclick="history.back()">Volver</button>
        </form>

    </main>
</body>
</html>