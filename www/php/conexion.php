<?php
// Datos de conexión a la base de datos
$host = "mysql"; // Dirección del servidor MySQL (normalmente localhost)
$user = "root";      // Usuario de MySQL (por defecto es root)
$password = "admin123!";      // Contraseña del usuario (por defecto está vacía en XAMPP/WAMP)
$dbname = "universidad"; // Nombre de la base de datos

// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Establecer el conjunto de caracteres a UTF-8 (opcional pero recomendado)
$conn->set_charset("utf8");
?>
