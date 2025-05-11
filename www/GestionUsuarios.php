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


// Manejar mensajes de operaciones de eliminación
if (isset($_GET['success']) && $_GET['success'] == 'deleted') {
    $mensaje = "<div class='alert alert-success'>Usuario eliminado correctamente</div>";
} else if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'self':
            $mensaje = "<div class='alert alert-danger'>No puede eliminar su propio usuario</div>";
            break;
        case 'lastadmin':
            $mensaje = "<div class='alert alert-danger'>No puede eliminar el último usuario administrador del sistema</div>";
            break;
        case 'notfound':
            $mensaje = "<div class='alert alert-danger'>El usuario que intenta eliminar no existe</div>";
            break;
        case 'delete':
            $mensaje = "<div class='alert alert-danger'>Error al eliminar el usuario</div>";
            break;
        case 'noid':
            $mensaje = "<div class='alert alert-danger'>ID de usuario no proporcionado</div>";
            break;
    }
}

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger datos del formulario
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nombre = $_POST['nombre'];
    $rol = $_POST['rol'];
    
    // Validar datos
    if (empty($username) || empty($password) || empty($nombre)) {
        $mensaje = "<div class='alert alert-danger'>Todos los campos son obligatorios</div>";
    } else {
        // Verificar si el nombre de usuario ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $mensaje = "<div class='alert alert-danger'>El nombre de usuario ya existe</div>";
        } else {
            // Encriptar la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar nuevo usuario
            $stmt = $conn->prepare("INSERT INTO usuarios (username, password, nombre, rol) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $password_hash, $nombre, $rol);
            
            if ($stmt->execute()) {
                $mensaje = "<div class='alert alert-success'>Usuario creado con éxito</div>";
                // Limpiar formulario
                $username = $password = $nombre = "";
                $rol = "lector";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al crear el usuario: " . $stmt->error . "</div>";
            }
        }
    }
}

// Obtener listado de usuarios existentes
$query = "SELECT id, username, nombre, rol, fecha_creacion FROM usuarios ORDER BY fecha_creacion DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Usuarios - Sistema GAP</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
      /* Estilos adicionales específicos para la gestión de usuarios */
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
      
      .btn-create {
        background-color: #0066cc;
        color: white;
      }
      
      .btn-reset {
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
      
      /* Estilos para las tablas */
      .users-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
        background-color: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      }
      
      .users-table th,
      .users-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
      }
      
      .users-table th {
        background-color: #f5f5f5;
        font-weight: 600;
        color: #444;
      }
      
      .users-table tr:last-child td {
        border-bottom: none;
      }
      
      .users-table tr:hover td {
        background-color: #f9f9f9;
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
      
      .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: center;
      }
      
      .btn-action {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        color: white;
        font-size: 14px;
      }
      
      .btn-edit {
        background-color: #ff9933;
      }
      
      .btn-delete {
        background-color: #ff3333;
      }
      
      /* Estilos para la sección de información */
      .info-box {
        background-color: #e6f7ff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
      }
      
      .info-title {
        color: #003366;
        margin-bottom: 15px;
        font-size: 18px;
        font-weight: 600;
      }
      
      .role-info {
        margin-bottom: 10px;
      }
      
      .role-info strong {
        font-weight: 600;
        color: #333;
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
          <h1>Gestión de Usuarios</h1>
          <p>Administración de cuentas del sistema</p>
        </header>
        
        <!-- Mensaje de resultado -->
        <?php echo $mensaje; ?>
        
        <!-- Formulario para crear usuario -->
        <div class="user-form">
          <h2 class="section-title">Crear Nuevo Usuario</h2>
          <form method="POST" action="">
            <div class="form-row">
              <label for="username">Nombre de Usuario:</label>
              <input type="text" id="username" name="username" value="<?php echo isset($username) ? $username : ''; ?>" required>
            </div>
            
            <div class="form-row">
              <label for="password">Contraseña:</label>
              <div class="password-field-container">
                <input type="password" id="password" name="password" required>
                <button type="button" id="togglePassword" class="password-toggle-btn" title="Mostrar/ocultar contraseña">
                  🔒
                </button>
              </div>
            </div>
            
            <div class="form-row">
              <label for="nombre">Nombre Completo:</label>
              <input type="text" id="nombre" name="nombre" value="<?php echo isset($nombre) ? $nombre : ''; ?>" required>
            </div>
            
            <div class="form-row">
              <label for="rol">Rol:</label>
              <select id="rol" name="rol">
                <option value="lector" <?php echo (isset($rol) && $rol == 'lector') ? 'selected' : ''; ?>>Lector</option>
                <option value="editor" <?php echo (isset($rol) && $rol == 'editor') ? 'selected' : ''; ?>>Editor</option>
                <option value="admin" <?php echo (isset($rol) && $rol == 'admin') ? 'selected' : ''; ?>>Administrador</option>
              </select>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn btn-create">Crear Usuario</button>
              <button type="reset" class="btn btn-reset">Limpiar</button>
            </div>
          </form>
        </div>
        
        <!-- Listado de usuarios -->
        <h2 class="section-title">Usuarios del Sistema</h2>
        <table class="users-table">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Nombre</th>
              <th>Rol</th>
              <th>Fecha Creación</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $rolClass = 'role-' . $row['rol'];
                    
                    echo "<tr>";
                    echo "<td>" . $row['username'] . "</td>";
                    echo "<td>" . $row['nombre'] . "</td>";
                    echo "<td><span class='role-badge {$rolClass}'>" . $row['rol'] . "</span></td>";
                    echo "<td>" . $row['fecha_creacion'] . "</td>";
                    echo "<td class='action-buttons'>";
                    echo "<a href='EditarUsuario.php?id=" . $row['id'] . "' class='edit-btn' title='Editar'><span>Editar</span></a>";
                    echo "<a href='EliminarUsuario.php?id=" . $row['id'] . "' class='delete-btn' title='Eliminar' onclick='return confirm(\"¿Está seguro de eliminar este usuario?\");'><span>Eliminar</span></a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='no-data'>No hay usuarios registrados</td></tr>";
            }
            ?>
          </tbody>
        </table>
        
        <!-- Explicación de roles -->
        <div class="info-box">
          <h3 class="info-title">Información sobre Roles</h3>
          <div class="role-info">
            <p><strong>Administrador (admin):</strong> Acceso completo al sistema. Puede crear, editar y eliminar usuarios, profesores, asignaturas, y configurar todos los aspectos del sistema.</p>
          </div>
          <div class="role-info">
            <p><strong>Editor (editor):</strong> Puede gestionar profesores, asignaturas, aulas y registrar asistencias e incidencias, pero no puede configurar el sistema ni gestionar usuarios.</p>
          </div>
          <div class="role-info">
            <p><strong>Lector (lector):</strong> Solo puede ver la información, pero no puede realizar cambios en el sistema.</p>
          </div>
        </div>
      </main>
    </div>
    <script>
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