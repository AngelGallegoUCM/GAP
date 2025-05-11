<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Asistencia de Profesores</title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <div class="container">
      <!-- Barra lateral -->
      <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

      <!-- Contenido principal -->
      <main class="main-content">
        <header class="header">
          <h1>Bienvenido a Gestión de Asistencia de Profesores</h1>
          <p>Panel de Control - Sistema GAP</p>
        </header>
        
        <!-- Panel de acceso rápido -->
        <div class="quick-access">
          <div class="section-heading">
            <h3>Gestión de Personal</h3>
          </div>
          <div class="modules-grid">
            <!-- Módulo de Profesores -->
            <a href="ListadoProfesores.php" class="module-card">
              <div class="module-icon">👨‍🏫</div>
              <h4>Profesores</h4>
              <p>Listado y gestión del personal docente</p>
            </a>
            
            <!-- Módulo de Añadir Profesor (solo admin y editor) -->
            <?php if (in_array($_SESSION['rol'], ['admin', 'editor'])): ?>
            <a href="AgregarProfesor.php" class="module-card">
              <div class="module-icon">➕</div>
              <h4>Añadir Profesor</h4>
              <p>Registrar nuevo docente en el sistema</p>
            </a>
            <?php endif; ?>
            
            <!-- Módulo de Aulas -->
            <a href="ListadoAulas.php" class="module-card">
              <div class="module-icon">🏫</div>
              <h4>Aulas</h4>
              <p>Gestión de espacios y capacidades</p>
            </a>
            
            <!-- Módulo de Asignaturas -->
            <a href="ListadoAsignaturas.php" class="module-card">
              <div class="module-icon">📚</div>
              <h4>Asignaturas</h4>
              <p>Administración de cursos y grupos</p>
            </a>
          </div>
        </div>
        
        <!-- Panel de calendario y asistencias -->
        <div class="quick-access">
          <div class="section-heading">
            <h3>Calendario y Asistencias</h3>
          </div>
          <div class="modules-grid">
            <!-- Módulo de Días No Lectivos -->
            <a href="ListadoNoLectivo.php" class="module-card">
              <div class="module-icon">📅</div>
              <h4>Días No Lectivos</h4>
              <p>Gestión del calendario académico</p>
            </a>
            
            <!-- Módulo de Incidencias -->
            <a href="ListadoIncidencias.php" class="module-card">
              <div class="module-icon">⚠️</div>
              <h4>Incidencias</h4>
              <p>Gestión de faltas y justificaciones</p>
            </a>
            
            <!-- Módulo de Estadísticas (solo admin) -->
            <?php if ($_SESSION['rol'] === 'admin'): ?>
            <a href="VerEstadisticas.php" class="module-card admin-module">
              <div class="module-icon">📊</div>
              <h4>Estadísticas</h4>
              <p>Reportes y análisis de asistencia</p>
            </a>
            <?php endif; ?>
            
            <!-- Módulo de configuración (solo admin) -->
            <?php if ($_SESSION['rol'] === 'admin'): ?>
            <a href="GestionUsuarios.php" class="module-card admin-module">
              <div class="module-icon">⚙️</div>
              <h4>Configuración</h4>
              <p>Ajustes del sistema</p>
            </a>
            <?php endif; ?>
          </div>
        </div>
        
        <?php if ($_SESSION['rol'] === 'admin'): ?>
        <!-- Panel de administración - solo visible para administradores -->
        <div class="admin-panel">
          <h3>Panel de Administración</h3>
          <p>Como administrador del sistema, tienes acceso completo a todas las funcionalidades:</p>
          <ul>
            <li>Gestión completa de profesores, aulas y asignaturas (añadir, modificar, eliminar)</li>
            <li>Administración del calendario académico y días no lectivos</li>
            <li>Gestión de incidencias y justificaciones</li>
            <li>Acceso a estadísticas y reportes completos del sistema</li>
            <li>Configuración general del sistema GAP</li>
          </ul>
        </div>
        
        
        
        <?php endif; ?>

      </main>
    </div>
  </body>
</html>