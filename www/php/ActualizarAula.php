<?php
// Iniciar sesión y verificar autenticación
require_once("verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (admin o editor)
verificarRol(['admin', 'editor']);

// Conexión a la base de datos
include("conexion.php");

// Verificar si se enviaron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errores = [];

    // Validar ID
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        $errores[] = "ID de aula inválido.";
    }

    // Validar número de aula: exactamente 4 dígitos
    if (!isset($_POST['numero_aula']) || !preg_match('/^\d{4}$/', $_POST['numero_aula'])) {
        $errores[] = "El número de aula debe tener exactamente 4 dígitos (por ejemplo: 0013).";
    }

    // Validar capacidad
    if (!isset($_POST['capacidad']) || !is_numeric($_POST['capacidad']) || 
        $_POST['capacidad'] < 1 || $_POST['capacidad'] > 300) {
        $errores[] = "La capacidad debe ser un valor entre 1 y 300.";
    }

    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        echo "<div class='error-message'>";
        echo "<h3>Se encontraron errores:</h3>";
        echo "<ul>";
        foreach ($errores as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "<p><a href='javascript:history.back()'>Volver al formulario</a></p>";
        echo "</div>";
        exit();
    }

    // Si no hay errores, realizar la actualización
    try {
        $id = intval($_POST['id']);
        $numero_aula = $_POST['numero_aula']; // texto tipo '0013'
        $capacidad = intval($_POST['capacidad']);

        $stmt = $conn->prepare("UPDATE aulas SET numero_aula = ?, capacidad = ? WHERE id = ?");
        $stmt->bind_param("sii", $numero_aula, $capacidad, $id);

        if ($stmt->execute()) {
            header("Location: ../ListadoAulas.php?success=2");
            exit();
        } else {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
    } catch (Exception $e) {
        echo "Error al actualizar el aula: " . htmlspecialchars($e->getMessage());
    }
}
?>
