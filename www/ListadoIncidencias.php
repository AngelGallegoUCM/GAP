<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Incidencias</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        #popup, #descripcion-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .popup-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            width: 400px;
        }
        
        .popup-content h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .popup-content textarea {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .popup-content button {
            padding: 8px 15px;
            margin-right: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .popup-content button[type="submit"] {
            background-color: #4e73df;
            color: white;
        }
        
        .popup-content button[type="button"] {
            background-color: #6c757d;
            color: white;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        .incidencias-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .incidencias-table th {
            background-color: #4e73df;
            color: white;
            padding: 10px;
            text-align: left;
        }
        
        .incidencias-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .incidencias-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-buttons a, .action-buttons button {
            padding: 5px 10px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            color: white;
        }
        
        .justify-btn {
            background-color: #28a745;
        }
        
        .email-btn {
            background-color: #17a2b8;
        }
        
        .view-btn {
            background-color: #6c757d;
        }
        
        .delete-btn {
            background-color: #dc3545;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fc;
            border-radius: 5px;
        }
        
        .filter-form label {
            margin-right: 5px;
        }
        
        .filter-form input, .filter-form select {
            padding: 6px 10px;
            border: 1px solid #d1d3e2;
            border-radius: 4px;
        }
        
        .filter-form button {
            padding: 6px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-form button[type="submit"] {
            background-color: #4e73df;
            color: white;
        }
        
        .filter-form button[type="button"] {
            background-color: #6c757d;
            color: white;
        }
        
        .success-message {
            padding: 10px 15px;
            margin-bottom: 20px;
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            border-radius: 4px;
            transition: opacity 0.5s ease-in-out;
        }
        
        /* Estilos para paginación */
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            color: #4e73df;
            border-radius: 4px;
        }
        
        .pagination a:hover {
            background-color: #f8f9fc;
        }
        
        .pagination .active {
            background-color: #4e73df;
            color: white;
            border-color: #4e73df;
        }
        
        .pagination .disabled {
            color: #aaa;
            cursor: not-allowed;
        }
    </style>
    <script>
        function mostrarPopup(id) {
            const popup = document.getElementById('popup');
            const incidenciaId = document.getElementById('incidencia_id');
            incidenciaId.value = id;
            popup.style.display = 'block';
        }

        function cerrarPopup() {
            document.getElementById('popup').style.display = 'none';
            document.getElementById('justificacion').value = '';
        }
        
        function mostrarDescripcion(id, descripcion) {
            const popup = document.getElementById('descripcion-popup');
            const descripcionText = document.getElementById('descripcion-text');
            descripcionText.innerText = descripcion || 'No hay descripción disponible';
            popup.style.display = 'block';
        }

        function cerrarDescripcionPopup() {
            document.getElementById('descripcion-popup').style.display = 'none';
        }
        
        // Función para mostrar mensajes temporales
        window.onload = function() {
            const msgSuccess = document.getElementById('msg-success');
            if (msgSuccess) {
                setTimeout(function() {
                    msgSuccess.style.opacity = '0';
                    setTimeout(function() {
                        msgSuccess.style.display = 'none';
                    }, 500);
                }, 3000);
            }
            
            // Validar fechas del formulario
            document.getElementById('filter-form').addEventListener('submit', function(e) {
                const inicio = document.getElementById('inicio').value;
                const fin = document.getElementById('fin').value;
                
                if (inicio && fin) {
                    if (new Date(inicio) > new Date(fin)) {
                        alert('La fecha de inicio debe ser anterior a la fecha de fin');
                        e.preventDefault();
                        return false;
                    }
                }
                
                return true;
            });
        };
    </script>
</head>
<body>
    <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

    <div class="main-content">
        <h1>Listado de Incidencias</h1>
        <p>Informe > Incidencias</p>
        
        <?php if (isset($_GET['success'])): ?>
        <div id="msg-success" class="success-message">
            <?php 
            $mensaje = "Operación realizada con éxito.";
            if ($_GET['success'] == '1') $mensaje = "Incidencia eliminada correctamente.";
            if ($_GET['success'] == '2') $mensaje = "Incidencia justificada correctamente.";
            echo htmlspecialchars($mensaje);
            ?>
        </div>
        <?php endif; ?>

        <!-- Filtro de búsqueda mejorado -->
        <form id="filter-form" method="GET" action="" class="filter-form">
            <label for="inicio">Fecha de inicio:</label>
            <input type="date" id="inicio" name="inicio" 
                   value="<?php echo isset($_GET['inicio']) ? htmlspecialchars($_GET['inicio']) : ''; ?>">

            <label for="fin">Fecha de fin:</label>
            <input type="date" id="fin" name="fin" 
                   value="<?php echo isset($_GET['fin']) ? htmlspecialchars($_GET['fin']) : ''; ?>">

            <label for="justificada">Estado:</label>
            <select id="justificada" name="justificada">
                <option value="">Todas</option>
                <option value="1" <?php if(isset($_GET['justificada']) && $_GET['justificada'] == "1") echo 'selected'; ?>>Justificadas</option>
                <option value="0" <?php if(isset($_GET['justificada']) && $_GET['justificada'] == "0") echo 'selected'; ?>>No Justificadas</option>
            </select>

            <button type="submit">Filtrar</button>
            <button type="button" onclick="window.location.href='ListadoIncidencias.php'">Restablecer Filtro</button>
        </form>

        <!-- Tabla de Incidencias -->
        <div class="table-container">
            <table class="incidencias-table">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Asignatura</th>
                        <th>Profesor</th>
                        <th>Correo del Profesor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include("php/conexion.php");

                    // Configuración de paginación
                    $registros_por_pagina = 12;
                    $pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
                    $offset = ($pagina_actual - 1) * $registros_por_pagina;

                    // Preparar los parámetros y condiciones para la consulta
                    $conditions = [];
                    $params = [];
                    $types = "";

                    if (!empty($_GET['inicio']) && !empty($_GET['fin'])) {
                        $inicio = $_GET['inicio'];
                        $fin = $_GET['fin'];
                        $conditions[] = "DATE(i.fecha_incidencia) BETWEEN ? AND ?";
                        $params[] = $inicio;
                        $params[] = $fin;
                        $types .= "ss";
                    }

                    if (isset($_GET['justificada']) && ($_GET['justificada'] === "0" || $_GET['justificada'] === "1")) {
                        $justificada = $_GET['justificada'];
                        $conditions[] = "i.justificada = ?";
                        $params[] = $justificada;
                        $types .= "i";
                    }

                    $where = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

                    // Consulta para obtener el total de registros
                    $query_count = "
                        SELECT COUNT(*) as total
                        FROM incidencias i
                        JOIN asistencias s ON i.asistencia_id = s.id
                        JOIN asignaturas a ON s.asignatura_id = a.id
                        JOIN profesores p ON a.profesor_id = p.id
                        $where";

                    $stmt_count = $conn->prepare($query_count);
                    if (!empty($params)) {
                        $stmt_count->bind_param($types, ...$params);
                    }
                    $stmt_count->execute();
                    $result_count = $stmt_count->get_result();
                    $row_count = $result_count->fetch_assoc();
                    $total_registros = $row_count['total'];
                    $total_paginas = ceil($total_registros / $registros_por_pagina);

                    // Consulta principal con límite para paginación
                    $query = "
                        SELECT i.id, DATE_FORMAT(i.fecha_incidencia, '%d/%m/%Y') AS fecha_formateada, 
                               i.fecha_incidencia, a.nombre_asignatura, 
                               CONCAT(p.nombre, ' ', p.apellidos) AS profesor, p.CorreoPropio AS correo_profesor, 
                               i.justificada, i.descripcion
                        FROM incidencias i
                        JOIN asistencias s ON i.asistencia_id = s.id
                        JOIN asignaturas a ON s.asignatura_id = a.id
                        JOIN profesores p ON a.profesor_id = p.id
                        $where
                        ORDER BY i.fecha_incidencia DESC
                        LIMIT ? OFFSET ?";

                    $params[] = $registros_por_pagina;
                    $params[] = $offset;
                    $types .= "ii";

                    // Preparar y ejecutar la consulta
                    $stmt = $conn->prepare($query);
                    if (!empty($params)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . ($row['justificada'] ? '<span style="color:green;">✓ Justificada</span>' : '<span style="color:red;">✗ No justificada</span>') . "</td>";
                            echo "<td>" . htmlspecialchars($row['fecha_formateada']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nombre_asignatura']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['profesor']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['correo_profesor']) . "</td>";
                            echo "<td class='action-buttons'>";
                            
                            // Diferentes acciones según el estado de la incidencia
                            if (!$row['justificada']) {
                                // Para incidencias no justificadas y si el usuario tiene permisos
                                if (in_array($_SESSION['rol'], ['admin', 'editor'])) {
                                    echo "<button class='justify-btn' onclick='mostrarPopup(" . htmlspecialchars($row['id']) . ")'>Justificar</button>";
                                }
                            } else {
                                // Para incidencias justificadas
                                echo "<button class='view-btn' onclick='mostrarDescripcion(" . htmlspecialchars($row['id']) . ", \"" . htmlspecialchars(addslashes($row['descripcion'])) . "\")'>Ver Justificación</button>";
                            }
                            
                            // Solo admin puede eliminar
                            if ($_SESSION['rol'] === 'admin') {
                                echo "<a href='php/EliminarIncidencia.php?id=" . htmlspecialchars($row['id']) . "' class='delete-btn' onclick='return confirm(\"¿Seguro que deseas eliminar esta incidencia?\")'>Eliminar</a>";
                            }
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No se encontraron incidencias que coincidan con los criterios de búsqueda.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_registros > 0): ?>
        <div class="pagination">
            <?php
            // Construir la URL base para los enlaces de paginación, manteniendo los parámetros de búsqueda
            $url_params = [];
            if (isset($_GET['inicio']) && !empty($_GET['inicio'])) {
                $url_params[] = "inicio=" . urlencode($_GET['inicio']);
            }
            if (isset($_GET['fin']) && !empty($_GET['fin'])) {
                $url_params[] = "fin=" . urlencode($_GET['fin']);
            }
            if (isset($_GET['justificada']) && ($_GET['justificada'] === "0" || $_GET['justificada'] === "1")) {
                $url_params[] = "justificada=" . urlencode($_GET['justificada']);
            }
            
            $url_base = "ListadoIncidencias.php?" . implode("&", $url_params);
            
            // Agregar separador si ya hay parámetros
            $url_base .= !empty($url_params) ? "&" : "";
            
            // Enlace a la primera página
            if ($pagina_actual > 1) {
                echo "<a href='{$url_base}pagina=1'>&laquo; Primera</a>";
                echo "<a href='{$url_base}pagina=" . ($pagina_actual - 1) . "'>&lt; Anterior</a>";
            } else {
                echo "<span class='disabled'>&laquo; Primera</span>";
                echo "<span class='disabled'>&lt; Anterior</span>";
            }
            
            // Mostrar un rango de páginas
            $rango = 2; // Número de páginas a mostrar a cada lado de la página actual
            for ($i = max(1, $pagina_actual - $rango); $i <= min($total_paginas, $pagina_actual + $rango); $i++) {
                if ($i == $pagina_actual) {
                    echo "<span class='active'>{$i}</span>";
                } else {
                    echo "<a href='{$url_base}pagina={$i}'>{$i}</a>";
                }
            }
            
            // Enlace a la última página
            if ($pagina_actual < $total_paginas) {
                echo "<a href='{$url_base}pagina=" . ($pagina_actual + 1) . "'>Siguiente &gt;</a>";
                echo "<a href='{$url_base}pagina={$total_paginas}'>Última &raquo;</a>";
            } else {
                echo "<span class='disabled'>Siguiente &gt;</span>";
                echo "<span class='disabled'>Última &raquo;</span>";
            }
            ?>
        </div>
        <p style="text-align: center;">
            Mostrando <?php echo min($registros_por_pagina, $result->num_rows); ?> de <?php echo $total_registros; ?> registros
            (Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>)
        </p>
        <?php endif; ?>

        <!-- Popup de Descripción -->
        <div id="descripcion-popup">
            <div class="popup-content">
                <h3>Justificación de la Incidencia</h3>
                <p id="descripcion-text" style="white-space: pre-wrap;"></p>
                <button type="button" onclick="cerrarDescripcionPopup()">Cerrar</button>
            </div>
        </div>

        <!-- Popup de Justificación -->
        <div id="popup">
            <div class="popup-content">
                <form action="php/JustificarIncidencia.php" method="POST" onsubmit="return validarFormulario()">
                    <h3>Justificar Incidencia</h3>
                    <input type="hidden" id="incidencia_id" name="id">
                    <label for="justificacion">Motivo de la justificación:</label>
                    <textarea id="justificacion" name="justificacion" rows="4" required></textarea>
                    <button type="submit">Guardar</button>
                    <button type="button" onclick="cerrarPopup()">Cancelar</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Validar el formulario de justificación
        function validarFormulario() {
            const justificacion = document.getElementById('justificacion').value.trim();
            if (justificacion === '') {
                alert('Debe ingresar un motivo para la justificación');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>