<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();

// Para acceder a las estadísticas se requiere ser admin
verificarRol(['admin']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Asistencias</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .statistics-container {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .stats-table th, .stats-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        
        .stats-table th {
            background-color: #4e73df;
            color: white;
        }
        
        .stats-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .date-range {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e8f4ff;
            border-left: 4px solid #4e73df;
            border-radius: 4px;
        }
        
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 10px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

    <div class="main-content">
        <h1>Estadísticas de Asistencias</h1>
        <p>Informe > Estadísticas de Asistencias</p>

        <!-- Formulario de selección de fecha con validación -->
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
            <label for="inicio">Fecha de inicio:</label>
            <input type="date" id="inicio" name="inicio" 
                   value="<?php echo isset($_GET['inicio']) ? htmlspecialchars($_GET['inicio']) : ''; ?>" required>

            <label for="fin">Fecha de fin:</label>
            <input type="date" id="fin" name="fin" 
                   value="<?php echo isset($_GET['fin']) ? htmlspecialchars($_GET['fin']) : ''; ?>" required>

            <button type="submit">Generar Estadísticas</button>
        </form>

        <?php
        if (isset($_GET['inicio']) && isset($_GET['fin'])) {
            include("php/conexion.php");
            
            // Validar fechas
            $errores = [];
            
            // Verificar formato de fecha de inicio
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['inicio'])) {
                $errores[] = "El formato de la fecha de inicio debe ser YYYY-MM-DD";
            }
            
            // Verificar formato de fecha de fin
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fin'])) {
                $errores[] = "El formato de la fecha de fin debe ser YYYY-MM-DD";
            }
            
            // Verificar que la fecha de inicio sea anterior a la de fin
            if (empty($errores) && strtotime($_GET['inicio']) > strtotime($_GET['fin'])) {
                $errores[] = "La fecha de inicio debe ser anterior a la fecha de fin";
            }
            
            // Si hay errores, mostrarlos
            if (!empty($errores)) {
                echo "<div class='error-message'>";
                echo "<ul>";
                foreach ($errores as $error) {
                    echo "<li>" . htmlspecialchars($error) . "</li>";
                }
                echo "</ul>";
                echo "</div>";
            } else {
                // No hay errores, proceder con la consulta
                try {
                    $inicio = $_GET['inicio'];
                    $fin = $_GET['fin'];
                    
                    // Obtener el número de asistencias
                    $query_asistencias = "SELECT COUNT(*) AS total_asistencias FROM asistencias WHERE fecha BETWEEN ? AND ?";
                    $stmt_asistencias = $conn->prepare($query_asistencias);
                    $stmt_asistencias->bind_param("ss", $inicio, $fin);
                    $stmt_asistencias->execute();
                    $result_asistencias = $stmt_asistencias->get_result();
                    $total_asistencias = $result_asistencias->fetch_assoc()['total_asistencias'];
                    
                    // Obtener el número de incidencias justificadas
                    $query_justificadas = "SELECT COUNT(*) AS total_justificadas FROM incidencias 
                                          WHERE justificada = 1 AND DATE(fecha_incidencia) BETWEEN ? AND ?";
                    $stmt_justificadas = $conn->prepare($query_justificadas);
                    $stmt_justificadas->bind_param("ss", $inicio, $fin);
                    $stmt_justificadas->execute();
                    $result_justificadas = $stmt_justificadas->get_result();
                    $total_justificadas = $result_justificadas->fetch_assoc()['total_justificadas'];
                    
                    // Obtener el número de incidencias sin justificar
                    $query_no_justificadas = "SELECT COUNT(*) AS total_no_justificadas FROM incidencias 
                                             WHERE justificada = 0 AND DATE(fecha_incidencia) BETWEEN ? AND ?";
                    $stmt_no_justificadas = $conn->prepare($query_no_justificadas);
                    $stmt_no_justificadas->bind_param("ss", $inicio, $fin);
                    $stmt_no_justificadas->execute();
                    $result_no_justificadas = $stmt_no_justificadas->get_result();
                    $total_no_justificadas = $result_no_justificadas->fetch_assoc()['total_no_justificadas'];
                    
                    // Obtener el total de incidencias
                    $total_incidencias = $total_justificadas + $total_no_justificadas;
                    
                    // Obtener el número de días no lectivos
                    $query_nolectivos = "SELECT COUNT(*) AS total_nolectivos FROM nolectivo WHERE fecha BETWEEN ? AND ?";
                    $stmt_nolectivos = $conn->prepare($query_nolectivos);
                    $stmt_nolectivos->bind_param("ss", $inicio, $fin);
                    $stmt_nolectivos->execute();
                    $result_nolectivos = $stmt_nolectivos->get_result();
                    $total_nolectivos = $result_nolectivos->fetch_assoc()['total_nolectivos'];
                    
                    // Formatear fechas para mostrar
                    $inicio_formateado = date('d/m/Y', strtotime($inicio));
                    $fin_formateado = date('d/m/Y', strtotime($fin));
                    
                    // Mostrar los resultados
                    echo "<div class='statistics-container'>";
                    echo "<div class='date-range'>Período: del <strong>" . htmlspecialchars($inicio_formateado) . "</strong> al <strong>" . htmlspecialchars($fin_formateado) . "</strong></div>";
                    
                    echo "<table class='stats-table'>";
                    echo "<thead><tr>
                            <th>Total de Asistencias</th>
                            <th>Días No Lectivos</th>
                            <th>Incidencias Justificadas</th>
                            <th>Incidencias Sin Justificar</th>
                            <th>Total de Incidencias</th>
                          </tr></thead>";
                    echo "<tbody><tr>";
                    echo "<td>" . number_format($total_asistencias, 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($total_nolectivos, 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($total_justificadas, 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($total_no_justificadas, 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($total_incidencias, 0, ',', '.') . "</td>";
                    echo "</tr></tbody>";
                    echo "</table>";
                    
                    // Calcular proporciones y porcentajes
                    if ($total_incidencias > 0) {
                        $porcentaje_justificadas = round(($total_justificadas / $total_incidencias) * 100, 2);
                        $porcentaje_no_justificadas = round(($total_no_justificadas / $total_incidencias) * 100, 2);
                        
                        echo "<p><strong>Análisis de incidencias:</strong></p>";
                        echo "<ul>";
                        echo "<li>Incidencias justificadas: " . $porcentaje_justificadas . "%</li>";
                        echo "<li>Incidencias sin justificar: " . $porcentaje_no_justificadas . "%</li>";
                        echo "</ul>";
                    }
                    
                    echo "</div>";
                    
                } catch (Exception $e) {
                    echo "<div class='error-message'>Error al generar las estadísticas: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }

            $conn->close();
        }
        ?>
    </div>
    
    <script>
        // Validación del lado del cliente para las fechas
        document.querySelector('form').addEventListener('submit', function(e) {
            const fechaInicio = document.getElementById('inicio').value;
            const fechaFin = document.getElementById('fin').value;
            
            // Verificar que ambas fechas estén establecidas
            if (!fechaInicio || !fechaFin) {
                alert('Por favor, seleccione ambas fechas');
                e.preventDefault();
                return false;
            }
            
            // Verificar que la fecha de inicio sea anterior a la de fin
            if (new Date(fechaInicio) > new Date(fechaFin)) {
                alert('La fecha de inicio debe ser anterior a la fecha de fin');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>