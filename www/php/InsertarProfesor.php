<?php

// Iniciar sesión y verificar autenticación
require_once("verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (solo admin)
verificarRol(['admin']);

// Conexión a la base de datos
include("conexion.php");

// Verificar si se enviaron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
     // Validación de entradas
     $errores = [];
    
     // Validar nombre
     if (empty($_POST['nombre']) || !preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/', $_POST['nombre'])) {
         $errores[] = "El nombre solo debe contener letras y espacios.";
     }
     
     // Validar apellidos
     if (empty($_POST['apellidos']) || !preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/', $_POST['apellidos'])) {
         $errores[] = "Los apellidos solo deben contener letras y espacios.";
     }
     
     // Validar identificador
     if (empty($_POST['identificador']) || !preg_match('/^[A-Za-z0-9\-_]+$/', $_POST['identificador'])) {
         $errores[] = "El identificador solo debe contener letras, números, guiones y guiones bajos.";
     } else {
         // Verificar que el identificador no exista ya en la base de datos
         $stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM profesores WHERE identificador = ?");
         $stmt_check->bind_param("s", $_POST['identificador']);
         $stmt_check->execute();
         $result_check = $stmt_check->get_result();
         $row = $result_check->fetch_assoc();
         
         if ($row['total'] > 0) {
             $errores[] = "El identificador ya está en uso. Por favor, elija otro.";
         }
     }
     
     // Validar correo
     if (empty($_POST['correoPropio']) || !filter_var($_POST['correoPropio'], FILTER_VALIDATE_EMAIL)) {
         $errores[] = "Debe proporcionar un correo electrónico válido.";
     }
     
     // Validar departamento
     if (empty($_POST['departamento_id']) || !is_numeric($_POST['departamento_id'])) {
         $errores[] = "Debe seleccionar un departamento válido.";
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
         $stmt = $conn->prepare("INSERT INTO profesores (nombre, apellidos, identificador, correoPropio, departamento_id) VALUES (?, ?, ?, ?, ?)");
         
         // Vincular parámetros
         $stmt->bind_param("ssssi", $nombre, $apellidos, $identificador, $correoPropio, $departamento_id);
         
         // Asignar valores a los parámetros
         $nombre = $_POST['nombre'];
         $apellidos = $_POST['apellidos'];
         $identificador = $_POST['identificador'];
         $correoPropio = $_POST['correoPropio'];
         $departamento_id = intval($_POST['departamento_id']);
         
         // Ejecutar la consulta
         if ($stmt->execute()) {
             // Redirigir al listado de profesores tras éxito
             header("Location: ../ListadoProfesores.php?success=1");
             exit();
         } else {
             throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
         }
     } catch (Exception $e) {
         echo "Error al añadir el profesor: " . htmlspecialchars($e->getMessage());
     }
 }
?>