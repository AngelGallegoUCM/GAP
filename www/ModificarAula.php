<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (admin o editor)
verificarRol(['admin', 'editor']);

// Conexión a la base de datos
include("php/conexion.php");

// Obtener el ID del aula desde la URL y validarlo
if (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
    $aula_id = intval($_GET['id']);

    try {
        // Consulta preparada para obtener los datos del aula
        $stmt = $conn->prepare("SELECT * FROM aulas WHERE id = ?");
        $stmt->bind_param("i", $aula_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $aula = $result->fetch_assoc();
        } else {
            die("Aula no encontrada.");
        }
    } catch (Exception $e) {
        die("Error al obtener datos del aula: " . htmlspecialchars($e->getMessage()));
    }
} else {
    die("ID del aula no especificado o inválido.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Aula</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

    <main class="content">
        <h1>Modificar Aula</h1>

        <!-- Formulario para modificar aula -->
        <form action="php/ActualizarAula.php" method="POST" class="form-container">
            <!-- Campo oculto para enviar el ID del aula -->
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($aula['id']); ?>">

            <div class="form-group">
                <label for="numero_aula">Número de Aula:</label>
                <input type="number" id="numero_aula" name="numero_aula" 
                       value="<?php echo htmlspecialchars($aula['numero_aula']); ?>" 
                       min="1" max="999" required 
                       title="Ingrese un número entre 1 y 999">

                <label for="capacidad">Capacidad:</label>
                <input type="number" id="capacidad" name="capacidad" 
                       value="<?php echo htmlspecialchars($aula['capacidad']); ?>" 
                       min="1" max="300" required
                       title="Ingrese un número entre 1 y 300">
            </div>

            <!-- Botones -->
            <button type="submit">Guardar Cambios</button>
            <button type="button" onclick="window.location.href='ListadoAulas.php'">Volver</button>
        </form>

    </main>
</body>
</html>