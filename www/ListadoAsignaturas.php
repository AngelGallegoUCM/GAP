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
    <title>Listado de Asignaturas</title>
    <link rel="stylesheet" href="styles.css">
    <style>
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
        // Función para confirmar antes de eliminar
        function confirmarEliminacion(id) {
            if (confirm("¿Estás seguro de que deseas eliminar esta asignatura?")) {
                window.location.href = "php/EliminarAsignatura.php?id=" + id;
            }
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
        };
    </script>
</head>
<body>
    <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

    <div class="main-content">
        <h1>Asignaturas</h1>
        <p>Listado de todas las asignaturas registradas.</p>
        
        <?php if (isset($_GET['success'])): ?>
        <div id="msg-success" class="success-message">
            <?php 
            $mensaje = "Operación realizada con éxito.";
            if ($_GET['success'] == '1') $mensaje = "Asignatura añadida correctamente.";
            if ($_GET['success'] == '2') $mensaje = "Asignatura actualizada correctamente.";
            if ($_GET['success'] == '3') $mensaje = "Asignatura eliminada correctamente.";
            echo htmlspecialchars($mensaje);
            ?>
        </div>
        <?php endif; ?>

        <!-- Formulario de búsqueda con protección XSS -->
        <form method="GET" action="" style="margin-bottom: 20px;">
            <input type="text" name="nombre_asignatura" placeholder="Buscar por nombre de asignatura" 
                   value="<?php echo isset($_GET['nombre_asignatura']) ? htmlspecialchars($_GET['nombre_asignatura']) : ''; ?>">
            <input type="text" name="profesor" placeholder="Buscar por profesor" 
                   value="<?php echo isset($_GET['profesor']) ? htmlspecialchars($_GET['profesor']) : ''; ?>">
            <input type="text" name="aula" placeholder="Buscar por aula" 
                   value="<?php echo isset($_GET['aula']) ? htmlspecialchars($_GET['aula']) : ''; ?>">
            <button type="submit">Buscar</button>
            <!-- Botón para eliminar el filtro -->
            <button type="button" onclick="window.location.href='ListadoAsignaturas.php'">Eliminar Filtros</button>
            
            <?php if (in_array($_SESSION['rol'], ['admin', 'editor'])): ?>
            <a href="AgregarAsignatura.php" class="add-btn">Añadir Asignatura</a>
            <?php endif; ?>
        </form>

        <!-- Tabla dinámica -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nombre Asignatura</th>
                    <th>Aula</th>
                    <th>Grupo</th>
                    <th>Profesor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Conexión a la base de datos
                include("php/conexion.php");

                // Configuración de paginación
                $registros_por_pagina = 12;
                $pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
                $offset = ($pagina_actual - 1) * $registros_por_pagina;

                // Obtener los filtros de búsqueda
                $nombre_asignatura = isset($_GET['nombre_asignatura']) ? $_GET['nombre_asignatura'] : '';
                $profesor = isset($_GET['profesor']) ? $_GET['profesor'] : '';
                $aula = isset($_GET['aula']) ? $_GET['aula'] : '';

                // Consulta base
                $query = "
                        SELECT a.id,
                        a.nombre_asignatura, 
                        a.grupo, 
                        CONCAT(p.nombre, ' ', p.apellidos) AS profesor_nombre, 
                        au.numero_aula
                        FROM asignaturas a
                        JOIN profesores p ON a.profesor_id = p.id
                        JOIN aulas au ON a.aula_id = au.id";

                // Preparar los parámetros y condiciones para la consulta
                $conditions = [];
                $params = [];
                $types = "";
                
                if (!empty($nombre_asignatura)) {
                    $conditions[] = "a.nombre_asignatura LIKE ?";
                    $params[] = "%" . $nombre_asignatura . "%";
                    $types .= "s";
                }
                
                if (!empty($profesor)) {
                    $conditions[] = "(p.nombre LIKE ? OR p.apellidos LIKE ?)";
                    $params[] = "%" . $profesor . "%";
                    $params[] = "%" . $profesor . "%";
                    $types .= "ss";
                }
                
                if (!empty($aula)) {
                    $conditions[] = "CAST(au.numero_aula AS CHAR) LIKE ?";
                    $params[] = "%" . $aula . "%";
                    $types .= "s";
                }

                // Agregar condiciones a la consulta si existen
                if (count($conditions) > 0) {
                    $query .= " WHERE " . implode(" AND ", $conditions);
                }
                
                // Consulta para obtener el total de registros
                $query_count = "SELECT COUNT(*) as total FROM (" . $query . ") as subquery";
                $stmt_count = $conn->prepare($query_count);
                if (!empty($params)) {
                    $stmt_count->bind_param($types, ...$params);
                }
                $stmt_count->execute();
                $result_count = $stmt_count->get_result();
                $row_count = $result_count->fetch_assoc();
                $total_registros = $row_count['total'];
                $total_paginas = ceil($total_registros / $registros_por_pagina);
                
                // Ordenar los resultados
                $query .= " ORDER BY a.nombre_asignatura, a.grupo";
                
                // Añadir límite y offset para paginación
                $query .= " LIMIT ? OFFSET ?";
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

                // Generar filas dinámicamente
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['nombre_asignatura']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['numero_aula']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['grupo']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['profesor_nombre']) . "</td>";
                        echo "<td>";
                        
                        // Todos pueden ver
                        echo "<a href='VerDatosAsignatura.php?id=" . htmlspecialchars($row['id']) . "' class='view-btn'>Ver</a>";
                        
                        // Solo admin y editor pueden modificar
                        if (in_array($_SESSION['rol'], ['admin', 'editor'])) {
                            echo " | <a href='ModificarAsignatura.php?id=" . htmlspecialchars($row['id']) . "' class='edit-btn'>Modificar</a>";
                        }
                        
                        // Solo admin puede eliminar
                        if ($_SESSION['rol'] === 'admin') {
                            echo " | <button onclick='confirmarEliminacion(" . htmlspecialchars($row['id']) . ")' class='delete-btn'>Eliminar</button>";
                        }
                        
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No hay asignaturas registradas.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($total_registros > 0): ?>
        <div class="pagination">
            <?php
            // Construir la URL base para los enlaces de paginación, manteniendo los parámetros de búsqueda
            $url_params = [];
            if (isset($_GET['nombre_asignatura']) && !empty($_GET['nombre_asignatura'])) {
                $url_params[] = "nombre_asignatura=" . urlencode($_GET['nombre_asignatura']);
            }
            if (isset($_GET['profesor']) && !empty($_GET['profesor'])) {
                $url_params[] = "profesor=" . urlencode($_GET['profesor']);
            }
            if (isset($_GET['aula']) && !empty($_GET['aula'])) {
                $url_params[] = "aula=" . urlencode($_GET['aula']);
            }
            $url_base = "ListadoAsignaturas.php?" . implode("&", $url_params);
            
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
    </div>
</body>
</html>