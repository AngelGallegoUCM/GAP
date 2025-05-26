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

    // Validar número de aula: exactamente 4 dígitos numéricos
    if (!isset($_POST['numero_aula']) || !preg_match('/^\d{4}$/', $_POST['numero_aula'])) {
        $errores[] = "El número de aula debe tener exactamente 4 dígitos (por ejemplo: 0013)";
    }

    // Validar capacidad
    if (!isset($_POST['capacidad']) || !is_numeric($_POST['capacidad']) || 
        $_POST['capacidad'] < 1 || $_POST['capacidad'] > 300) {
        $errores[] = "La capacidad debe ser un valor entre 1 y 300";
    }

    // Mostrar errores si los hay
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

    // Si todo está bien, insertar en la base de datos
    try {
        $stmt = $conn->prepare("INSERT INTO aulas (numero_aula, capacidad) VALUES (?, ?)");
        $stmt->bind_param("si", $numero_aula, $capacidad); // "s" para string (numero_aula), "i" para int (capacidad)

        // Asignar valores
        $numero_aula = $_POST['numero_aula'];
        $capacidad = intval($_POST['capacidad']);

        if ($stmt->execute()) {
            header("Location: ../ListadoAulas.php?success=1");
            exit();
        } else {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
    } catch (Exception $e) {
        echo "Error al añadir el aula: " . htmlspecialchars($e->getMessage());
    }
}
?>
