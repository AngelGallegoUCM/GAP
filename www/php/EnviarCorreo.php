<?php
// Iniciar sesión y verificar autenticación
require_once("verificar_sesion.php");
verificarSesion();

// Verificar si el usuario tiene permisos (admin o editor)
verificarRol(['admin', 'editor']);

// Verificar los parámetros necesarios
if (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id']) && 
    isset($_GET['correo_profesor']) && !empty($_GET['correo_profesor'])) {
    
    $id = intval($_GET['id']);
    $correoProfesor = urldecode($_GET['correo_profesor']);
    
    // Validar el formato del correo electrónico
    if (!filter_var($correoProfesor, FILTER_VALIDATE_EMAIL)) {
        die("El formato del correo electrónico no es válido.");
    }

    try {
        // Conexión a la base de datos
        include("conexion.php");

        // Obtener la configuración de correo del sistema
        $config_query = "SELECT * FROM configuracion_sistema WHERE clave LIKE 'email_%'";
        $config_result = $conn->query($config_query);
        $config = [];
        
        if ($config_result && $config_result->num_rows > 0) {
            while ($row = $config_result->fetch_assoc()) {
                $config[$row['clave']] = $row['valor'];
            }
        } else {
            throw new Exception("No se ha configurado el sistema de correo electrónico. Por favor, contacte al administrador.");
        }
        
        // Verificar si tenemos la configuración mínima necesaria
        if (empty($config['email_remitente']) || empty($config['email_password'])) {
            throw new Exception("La configuración de correo electrónico está incompleta. Por favor, contacte al administrador.");
        }

        // Consulta preparada para obtener detalles de la incidencia
        $query = "
            SELECT i.fecha_incidencia, a.nombre_asignatura, CONCAT(p.nombre, ' ', p.apellidos) as nombre_profesor
            FROM incidencias i
            JOIN asistencias s ON i.asistencia_id = s.id
            JOIN asignaturas a ON s.asignatura_id = a.id
            JOIN profesores p ON a.profesor_id = p.id
            WHERE i.id = ? AND i.justificada = 0
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Formatear la fecha de incidencia
            $fecha_formateada = date('d/m/Y', strtotime($row['fecha_incidencia']));
            
            // Configuración para enviar un correo electrónico
            $asunto = "Notificación de incidencia no justificada";
            
            // Mensaje HTML más detallado y profesional
            $mensaje_html = "
            <html>
            <head>
                <title>Notificación de Incidencia</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    h2 { color: #0056b3; }
                    .info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
                    .footer { margin-top: 20px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Notificación de Incidencia No Justificada</h2>
                    <p>Estimado/a profesor/a <b>{$row['nombre_profesor']}</b>,</p>
                    <p>Le informamos que se ha registrado una incidencia no justificada en el sistema de asistencias:</p>
                    <div class='info'>
                        <p><b>Fecha:</b> {$fecha_formateada}</p>
                        <p><b>Asignatura:</b> {$row['nombre_asignatura']}</p>
                        <p><b>ID de Incidencia:</b> {$id}</p>
                    </div>
                    <p>Por favor, acceda al sistema para revisar y, si corresponde, justificar esta incidencia.</p>
                    <div class='footer'>
                        <p>Este es un mensaje automático. Por favor, no responda a este correo.</p>
                        <p>Sistema de Gestión de Asistencia de Profesores (GAP)</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Versión en texto plano para clientes de correo que no admiten HTML
            $mensaje_texto = "
            Notificación de Incidencia No Justificada
            
            Estimado/a profesor/a {$row['nombre_profesor']},
            
            Le informamos que se ha registrado una incidencia no justificada en el sistema de asistencias:
            
            Fecha: {$fecha_formateada}
            Asignatura: {$row['nombre_asignatura']}
            ID de Incidencia: {$id}
            
            Por favor, acceda al sistema para revisar y, si corresponde, justificar esta incidencia.
            
            Este es un mensaje automático. Por favor, no responda a este correo.
            Sistema de Gestión de Asistencia de Profesores (GAP)
            ";

            // Incluir la librería PHPMailer si está disponible, o usar mail() como fallback
            if (file_exists('../vendor/autoload.php')) {
                require '../vendor/autoload.php';
                
                // Usar PHPMailer
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                try {
                    // Configuración del servidor
                    $mail->isSMTP();
                    $mail->Host = $config['email_servidor'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $config['email_remitente'];
                    $mail->Password = $config['email_password']; // Nota: idealmente debería desencriptarse
                    
                    // Configuración de seguridad
                    if ($config['email_seguridad'] == 'tls') {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    } elseif ($config['email_seguridad'] == 'ssl') {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    }
                    
                    $mail->Port = intval($config['email_puerto']);
                    
                    // Remitente y destinatarios
                    $mail->setFrom($config['email_remitente'], 'Sistema GAP');
                    $mail->addAddress($correoProfesor, $row['nombre_profesor']);
                    $mail->addReplyTo('no-reply@example.com', 'No Reply');
                    
                    // Contenido
                    $mail->isHTML(true);
                    $mail->Subject = $asunto;
                    $mail->Body = $mensaje_html;
                    $mail->AltBody = $mensaje_texto;
                    
                    $mail->send();
                    $envio_exitoso = true;
                } catch (Exception $e) {
                    throw new Exception("Error al enviar el correo: " . $mail->ErrorInfo);
                }
            } else {
                // Fallback a mail() si PHPMailer no está disponible
                $cabeceras = "MIME-Version: 1.0\r\n";
                $cabeceras .= "Content-type: text/html; charset=UTF-8\r\n";
                $cabeceras .= "From: Sistema GAP <{$config['email_remitente']}>\r\n";
                $cabeceras .= "Reply-To: no-reply@example.com\r\n";
                $cabeceras .= "X-Mailer: PHP/" . phpversion();
                
                // Enviar el correo electrónico con mail() nativo
                $envio_exitoso = mail($correoProfesor, $asunto, $mensaje_html, $cabeceras);
                
                if (!$envio_exitoso) {
                    throw new Exception("Error al enviar el correo con mail() nativo.");
                }
            }
            
            if ($envio_exitoso) {
                // Mensaje de éxito
                echo "<script>
                    alert('Correo electrónico enviado correctamente a " . htmlspecialchars($row['nombre_profesor']) . ".');
                    window.location.href='../ListadoIncidencias.php';
                </script>";
            }
            
        } else {
            echo "<script>
                alert('No se encontró la incidencia especificada o ya ha sido justificada.');
                window.location.href='../ListadoIncidencias.php';
            </script>";
        }
        
        // Cerrar la conexión
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        echo "<script>
            alert('Error: " . addslashes($e->getMessage()) . "');
            window.history.back();
        </script>";
    }
} else {
    echo "<script>
        alert('No se han proporcionado los parámetros necesarios o son inválidos.');
        window.location.href='../ListadoIncidencias.php';
    </script>";
}
?>