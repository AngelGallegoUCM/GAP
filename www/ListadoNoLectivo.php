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
    <title>Listado de Días No Lectivos</title>
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
        <h1>Días No Lectivos</h1>
        <p>Calendario > Listado de Días No Lectivos</p>
        
        <?php if (isset($_GET['success'])): ?>
        <div id="msg-success" class="success-message">
            <?php 
            $mensaje = "Operación realizada con éxito.";
            if ($_GET['success'] == '1') $mensaje = "Día no lectivo añadido correctamente.";
            if ($_GET['success'] == '2') $mensaje = "Día no lectivo eliminado correctamente.";
            echo htmlspecialchars($mensaje);
            ?>
        </div>
        <?php endif; ?>

        <!-- Barra de búsqueda -->
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="search" placeholder="Buscar por descripción" 
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Buscar</button>
            <button type="button" onclick="window.location.href='ListadoNoLectivo.php'">Eliminar Filtro</button>
            
            <?php if (in_array($_SESSION['rol'], ['admin', 'editor'])): ?>
            <a href="AgregarDiaNoLectivo.php" class="add-btn">Añadir Día No Lectivo</a>
            <?php endif; ?>
        </form>

        <!-- Tabla dinámica -->
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include("php/conexion.php");

                // Configuración de paginación
                $registros_por_pagina = 12;
                $pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
                $offset = ($pagina_actual - 1) * $registros_por_pagina;

                // Preparar la consulta con o sin filtro de búsqueda
                $query = "SELECT * FROM nolectivo";
                $params = [];
                $types = "";
                
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = "%" . $_GET['search'] . "%";
                    $query .= " WHERE descripcion LIKE ?";
                    $params[] = $search;
                    $types = "s";
                }
                
                // Consulta para obtener el total de registros
                $query_count = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
                $stmt_count = $conn->prepare($query_count);
                if (!empty($params)) {
                    $stmt_count->bind_param($types, ...$params);
                }
                $stmt_count->execute();
                $result_count = $stmt_count->get_result();
                $row_count = $result_count->fetch_assoc();
                $total_registros = $row_count['total'];
                $total_paginas = ceil($total_registros / $registros_por_pagina);
                
                // Añadir ordenamiento y límite para paginación
                $query .= " ORDER BY fecha DESC LIMIT ? OFFSET ?";
                $params_paginacion = $params;
                $params_paginacion[] = $registros_por_pagina;
                $params_paginacion[] = $offset;
                $types_paginacion = $types . "ii";
                
                $stmt = $conn->prepare($query);
                if (!empty($params_paginacion)) {
                    $stmt->bind_param($types_paginacion, ...$params_paginacion);
                }
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        // Formatear la fecha en formato más legible (DD/MM/YYYY)
                        $fecha_formateada = date('d/m/Y', strtotime($row['fecha']));
                        echo "<td>" . htmlspecialchars($fecha_formateada) . "</td>";
                        echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
                        echo "<td>";
                        
                        // Solo admin y editor pueden eliminar
                        if (in_array($_SESSION['rol'], ['admin', 'editor'])) {
                            echo "<a href='php/EliminarDiaNoLectivo.php?id=" . htmlspecialchars($row['id']) . "' ";
                            echo "class='delete-btn' ";
                            echo "onclick='return confirm(\"¿Estás seguro de que deseas eliminar este día no lectivo?\")'>Eliminar</a>";
                        } else {
                            echo "<span class='action-disabled'>Eliminar</span>";
                        }
                        
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No se encontraron resultados.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>