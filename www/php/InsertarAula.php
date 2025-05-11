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
    // Validación de entradas
    $errores = [];
    
    // Validar número de aula
    if (!isset($_POST['numero_aula']) || !is_numeric($_POST['numero_aula']) || 
        $_POST['numero_aula'] < 1 || $_POST['numero_aula'] > 999) {
        $errores[] = "El número de aula debe ser un valor entre 1 y 999";
    }
    
    // Validar capacidad
    if (!isset($_POST['capacidad']) || !is_numeric($_POST['capacidad']) || 
        $_POST['capacidad'] < 1 || $_POST['capacidad'] > 300) {
        $errores[] = "La capacidad debe ser un valor entre 1 y 300";
    }
    
    // Si hay errores, mostrarlos y no procesar
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
    
    // Si no hay errores, continuar con la inserción usando consulta preparada
    try {
        // Preparar la consulta
        $stmt = $conn->prepare("INSERT INTO aulas (numero_aula, capacidad) VALUES (?, ?)");
        
        // Vincular parámetros
        $stmt->bind_param("ii", $numero_aula, $capacidad);
        
        // Asignar valores a los parámetros
        $numero_aula = intval($_POST['numero_aula']);
        $capacidad = intval($_POST['capacidad']);
        
        // Ejecutar la consulta
        if ($stmt->execute()) {
            // Redirigir al listado de aulas tras éxito
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