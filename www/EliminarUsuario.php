<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();

// Verificar si el usuario es administrador
if ($_SESSION['rol'] !== 'admin') {
    // Redirigir a la página principal si no es administrador
    header("Location: index.php");
    exit;
}

// Incluir conexión a la base de datos
require_once("php/conexion.php");

// Verificar que se proporciona un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: GestionUsuarios.php?error=noid");
    exit;
}

$usuario_id = $_GET['id'];

// No permitir eliminar el propio usuario (usuario actualmente conectado)
if ($usuario_id == $_SESSION['user_id']) {
    header("Location: GestionUsuarios.php?error=self");
    exit;
}

// Verificar que el usuario existe
$stmt = $conn->prepare("SELECT id, username, rol FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: GestionUsuarios.php?error=notfound");
    exit;
}

$usuario = $result->fetch_assoc();

// Verificar si es el último usuario administrador
if ($usuario['rol'] === 'admin') {
    // Contar cuántos administradores hay en el sistema
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['total'] <= 1) {
        // No permitir eliminar el último administrador
        header("Location: GestionUsuarios.php?error=lastadmin");
        exit;
    }
}

// Proceso de eliminación
$stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);

if ($stmt->execute()) {
    header("Location: GestionUsuarios.php?success=deleted");
} else {
    header("Location: GestionUsuarios.php?error=delete");
}
exit;
?>