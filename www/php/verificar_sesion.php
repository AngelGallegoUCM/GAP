<?php
// Inicia la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
function verificarSesion() {
    // Si no hay sesión de usuario, redirigir al login
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit();
    }
    
    return true;
}

// Verificar si el usuario tiene el rol requerido
function verificarRol($roles_permitidos) {
    // Si no hay sesión, verificar la sesión y redirigir
    if (!isset($_SESSION['usuario_id'])) {
        verificarSesion();
    }
    
    // Si el rol no está en el array de roles permitidos
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        header("Location: acceso_denegado.php");
        exit();
    }
    
    return true;
}

// Función para cerrar sesión
function cerrarSesion() {
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la sesión
    session_destroy();
    
    // Redirigir al login
    header("Location: login.php");
    exit();
}