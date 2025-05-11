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

// Variable para mensajes
$mensaje = "";

// Verificar que se proporciona un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: GestionUsuarios.php");
    exit;
}

$usuario_id = $_GET['id'];

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger datos del formulario
    $nombre = $_POST['nombre'];
    $rol = $_POST['rol'];
    $cambiar_password = isset($_POST['cambiar_password']) ? true : false;
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Actualizar nombre y rol
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, rol = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $rol, $usuario_id);
        $stmt->execute();
        
        // Si se marca cambiar contraseña, actualizarla
        if ($cambiar_password && !empty($_POST['password'])) {
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $password_hash, $usuario_id);
            $stmt->execute();
        }
        
        // Confirmar transacción
        $conn->commit();
        $mensaje = "<div class='alert alert-success'>Usuario actualizado con éxito</div>";
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        $mensaje = "<div class='alert alert-danger'>Error al actualizar el usuario: " . $e->getMessage() . "</div>";
    }
}

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT id, username, nombre, rol FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: GestionUsuarios.php");
    exit;
}

$usuario = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Editar Usuario - Sistema GAP</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
      /* Estilos adicionales específicos para la edición de usuarios */
      .user-form {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 25px;
        margin-bottom: 30px;
      }
      
      .form-row {
        margin-bottom: 15px;
      }
      
      .form-row label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #444;
      }
      
      .form-row input[type="text"],
      .form-row input[type="password"],
      .form-row select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
      }
      
      .form-row input[type="text"]:focus,
      .form-row input[type="password"]:focus,
      .form-row select:focus {
        border-color: #003366;
        outline: none;
        box-shadow: 0 0 0 2px rgba(0,51,102,0.1);
      }
      
      .form-row input[disabled] {
        background-color: #f5f5f5;
        cursor: not-allowed;
      }
      
      .form-row small {
        display: block;
        margin-top: 5px;
        color: #666;
        font-size: 12px;
      }
      
      .checkbox-container {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
      }
      
      .checkbox-container input[type="checkbox"] {
        margin-right: 10px;
      }
      
      .checkbox-container label {
        margin-bottom: 0;
        cursor: pointer;
      }
      
      .password-section {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 4px;
        margin-top: 10px;
        border-left: 3px solid #0066cc;
      }
      
      .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
      }
      
      .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
      }
      
      .btn-update {
        background-color: #0066cc;
        color: white;
      }
      
      .btn-cancel {
        background-color: #f2f2f2;
        color: #333;
      }
      
      .btn:hover {
        opacity: 0.9;
      }
      
      .section-title {
        margin: 0 0 20px 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        color: #003366;
        font-size: 18px;
        font-weight: 600;
      }
      
      /* Estilos para el panel de información */
      .user-info-panel {
        background-color: #e6f7ff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
      }
      
      .user-info-panel h3 {
        color: #003366;
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 18px;
        font-weight: 600;
      }
      
      .info-row {
        display: flex;
        margin-bottom: 10px;
      }
      
      .info-label {
        width: 120px;
        font-weight: 600;
        color: #444;
      }
      
      .role-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        text-transform: capitalize;
      }
      
      .role-admin {
        background-color: #e6f0ff;
        color: #0066cc;
      }
      
      .role-editor {
        background-color: #fff0e6;
        color: #ff6600;
      }
      
      .role-lector {
        background-color: #f2f2f2;
        color: #666666;
      }

       /* Estilos para el campo de contraseña con botón para mostrar/ocultar */
       .password-field-container {
        position: relative;
        width: 100%;
      }
      
      .password-field-container input {
        width: 100%;
        padding-right: 40px; /* Espacio para el botón */
      }
      
      .password-toggle-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #666;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        transition: background-color 0.2s;
      }
      
      .password-toggle-btn:hover {
        background-color: rgba(0, 0, 0, 0.05);
      }
      
      .password-toggle-btn:focus {
        outline: none;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <!-- Barra lateral -->
      <?php include("php/sidebar.php"); ?>

      <!-- Contenido principal -->
      <main class="main-content">
        <header class="header">
          <h1>Editar Usuario</h1>
          <p>Modificar información de cuenta</p>
        </header>
        
        <!-- Mensaje de resultado -->
        <?php echo $mensaje; ?>
        
        <!-- Panel de información del usuario -->
        <div class="user-info-panel">
          <h3>Información del Usuario</h3>
          <div class="info-row">
            <span class="info-label">Usuario:</span>
            <span><?php echo $usuario['username']; ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Nombre:</span>
            <span><?php echo $usuario['nombre']; ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Rol actual:</span>
            <span>
              <?php 
                $rolClass = 'role-' . $usuario['rol'];
                echo "<span class='role-badge {$rolClass}'>" . $usuario['rol'] . "</span>";
              ?>
            </span>
          </div>
        </div>
        
        <!-- Formulario para editar usuario -->
        <div class="user-form">
          <h2 class="section-title">Modificar Usuario: <?php echo $usuario['username']; ?></h2>
          <form method="POST" action="">
            <div class="form-row">
              <label for="username">Nombre de Usuario:</label>
              <input type="text" id="username" value="<?php echo $usuario['username']; ?>" disabled>
              <small>El nombre de usuario no puede ser modificado</small>
            </div>
            
            <div class="form-row">
              <label for="nombre">Nombre Completo:</label>
              <input type="text" id="nombre" name="nombre" value="<?php echo $usuario['nombre']; ?>" required>
            </div>
            
            <div class="form-row">
              <label for="rol">Rol:</label>
              <select id="rol" name="rol">
                <option value="lector" <?php echo ($usuario['rol'] == 'lector') ? 'selected' : ''; ?>>Lector</option>
                <option value="editor" <?php echo ($usuario['rol'] == 'editor') ? 'selected' : ''; ?>>Editor</option>
                <option value="admin" <?php echo ($usuario['rol'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
              </select>
            </div>
            
            <div class="checkbox-container">
              <input type="checkbox" id="cambiar_password" name="cambiar_password">
              <label for="cambiar_password">Cambiar contraseña</label>
            </div>
            
            <div id="password-section" class="password-section" style="display: none;">
              <div class="form-row">
                <label for="password">Nueva Contraseña:</label>
                <div class="password-field-container">
                  <input type="password" id="password" name="password">
                  <button type="button" id="togglePassword" class="password-toggle-btn" title="Mostrar/ocultar contraseña">
                  🔒
                  </button>
                </div>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn btn-update">Actualizar Usuario</button>
              <a href="GestionUsuarios.php" class="btn btn-cancel">Cancelar</a>
            </div>
          </form>
        </div>
      </main>
    </div>
    
    <script>
      // Script para mostrar/ocultar el campo de contraseña
      document.getElementById('cambiar_password').addEventListener('change', function() {
        const passwordSection = document.getElementById('password-section');
        passwordSection.style.display = this.checked ? 'block' : 'none';
        
        // Si se desmarca la casilla, vaciar el campo de contraseña
        if (!this.checked) {
          document.getElementById('password').value = '';
        }
      });
     
    // Script para mostrar/ocultar contraseña
    document.getElementById('togglePassword').addEventListener('click', function() {
      const passwordField = document.getElementById('password');
      
      // Cambiar el tipo de campo entre "password" y "text"
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        this.textContent = '👁️'; // Cambiar el ícono a "ocultar"
        this.title = 'Ocultar contraseña';
      } else {
        passwordField.type = 'password';
        this.textContent = '🔒'; // Cambiar el ícono a "mostrar"
        this.title = 'Mostrar contraseña';
      }
    });
    
    </script>
  </body>
</html>