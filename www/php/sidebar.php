<?php
// Se asume que la sesión ya está iniciada en la página que incluye este sidebar
?>
<style>
/* Estilos mejorados para el sidebar completo */
.sidebar {
    display: flex;
    flex-direction: column;
    height: 100%;
    background-color: #003366;
    color: white;
    width: 250px;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

/* Logo */
.logo {
    text-align: center;
    padding: 20px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo img {
    width: 120px;
    height: auto;
    transition: transform 0.3s ease;
}

.logo img:hover {
    transform: scale(1.05);
}

/* Navegación */
.sidebar-nav {
    flex: 1;
    padding: 20px 0;
}

.sidebar-nav ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav li {
    margin: 0;
}

.sidebar-nav a {
    display: block;
    padding: 12px 20px;
    color: #e6e6e6;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    font-size: 15px;
}

.sidebar-nav a:hover, .sidebar-nav a.active {
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: #4e73df;
}

/* Información del usuario (colocada al final) */
.user-info {
    margin-top: auto;
    padding: 15px;
    background-color: rgba(0, 0, 0, 0.2);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.user-info p {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #e6e6e6;
}

.user-info p:first-child {
    font-weight: bold;
    font-size: 15px;
    color: white;
}

.user-role {
    display: inline-block;
    background-color: #4e73df;
    color: white;
    font-size: 12px;
    font-weight: bold;
    padding: 2px 8px;
    border-radius: 10px;
    margin-top: 3px;
    text-transform: capitalize;
}

.logout-btn {
    display: block;
    width: 100%;
    margin-top: 15px;
    padding: 8px 15px;
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 13px;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.3);
    text-align: center;
}

.logout-btn:hover {
    background-color: rgba(255, 255, 255, 0.25);
}

/* Decoraciones adicionales */
.menu-section-title {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255, 255, 255, 0.5);
    padding: 10px 20px;
    margin-top: 15px;
}

/* Detectar página actual */
.current-page {
    background-color: rgba(255, 255, 255, 0.2);
    border-left-color: white !important;
}
</style>

<aside class="sidebar">
    <div class="logo">
        <a href="index.php">
            <img src="img/logo.png" alt="Logo Universidad Complutense">
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <div class="menu-section-title">Gestión Académica</div>
        <ul>
            <li>
                <a href="ListadoProfesores.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ListadoProfesores.php' ? 'current-page' : ''; ?>">
                    Listar Profesores
                </a>
            </li>
            <li>
                <a href="ListadoAulas.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ListadoAulas.php' ? 'current-page' : ''; ?>">
                    Listar Aulas
                </a>
            </li>
            <li>
                <a href="ListadoAsignaturas.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ListadoAsignaturas.php' ? 'current-page' : ''; ?>">
                    Listar Asignaturas
                </a>
            </li>
        </ul>
        
        <div class="menu-section-title">Control de Asistencia</div>
        <ul>
            <li>
                <a href="ListadoNoLectivo.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ListadoNoLectivo.php' ? 'current-page' : ''; ?>">
                    Días no lectivos
                </a>
            </li>
            <li>
                <a href="ListadoIncidencias.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ListadoIncidencias.php' ? 'current-page' : ''; ?>">
                    Listar Incidencias
                </a>
            </li>
            <li>
                <a href="VerEstadisticas.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'VerEstadisticas.php' ? 'current-page' : ''; ?>">
                    Ver Estadísticas
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Información del usuario al final del sidebar -->
    <div class="user-info">
        <?php if (isset($_SESSION['username'])): ?>
            <p>¡Hola, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <p>Rol: <span class="user-role"><?php echo htmlspecialchars($_SESSION['rol']); ?></span></p>
            <a href="logout.php" class="logout-btn">Cerrar sesión</a>
        <?php endif; ?>
    </div>
</aside>