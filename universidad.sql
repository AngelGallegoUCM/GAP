-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 15-05-2025 a las 10:37:33
-- Versión del servidor: 8.4.5
-- Versión de PHP: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET GLOBAL time_zone = 'Europe/Madrid';


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `universidad`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`%` PROCEDURE `GenerarIncidencias` ()   BEGIN
    -- Insertar incidencias para asistencias no marcadas como presentes del día actual
    INSERT INTO incidencias (asistencia_id,justificada, descripcion, fecha_incidencia)
    SELECT 
        id,
        0,
        'No se ha pasado la tarjeta', 
        NOW()
    FROM asistencias
    WHERE presente = FALSE
    AND fecha = CURDATE();
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `generar_asistencias_diarias` ()   BEGIN
    DECLARE dia_actual VARCHAR(20);
    DECLARE es_no_lectivo INT;
    
    -- Obtener el día actual en español
    SET dia_actual = get_dia_espanol();
    
    -- Verificar si es un día no lectivo
    SELECT COUNT(*) INTO es_no_lectivo 
    FROM nolectivo 
    WHERE fecha = CURDATE();
    
    -- Si no es un día no lectivo, generar asistencias
    IF es_no_lectivo = 0 THEN
        -- Eliminar asistencias previas para el día actual (si existen)
        DELETE FROM asistencias WHERE fecha = CURDATE();
        
        -- Insertar nuevas asistencias para las asignaturas del día
        INSERT INTO asistencias (asignatura_id, fecha, presente)
        SELECT a.id, CURDATE(), FALSE
        FROM horarios h
        JOIN asignaturas a ON h.asignatura_id = a.id
        WHERE h.dia_semana = dia_actual;
    END IF;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `registrar_asistencia` (IN `p_identificador_profesor` VARCHAR(50), IN `p_numero_aula` INT)   proc_label: BEGIN  /* Añadimos la etiqueta aquí */
    -- Variables para almacenar la información
    DECLARE v_asignatura_id INT;
    DECLARE v_profesor_asignado_id INT;
    DECLARE v_nombre_asignatura VARCHAR(100);
    DECLARE v_profesor_nombre VARCHAR(50);
    DECLARE v_profesor_apellidos VARCHAR(100);
    DECLARE v_profesor_tarjeta_id INT;
    DECLARE v_profesor_tarjeta_nombre VARCHAR(150);
    DECLARE v_asistencia_id INT;
    DECLARE v_aula_id INT;
    DECLARE v_hora_actual TIME;
    DECLARE v_dia_actual VARCHAR(20);
    
    -- Inicializar variables
    SET v_hora_actual = CURTIME();
    SET v_dia_actual = get_dia_espanol();
    
    -- Obtener el ID del aula a partir del número
    SELECT id INTO v_aula_id
    FROM aulas
    WHERE numero_aula = p_numero_aula
    LIMIT 1;
    
    -- Si no se encuentra el aula, salir
    IF v_aula_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Aula no encontrada';
        LEAVE proc_label;  /* Usamos la etiqueta aquí */
    END IF;
    
    -- Paso 1: Obtener información sobre la asignatura y profesores
    SELECT 
        a.id, 
        a.profesor_id, 
        a.nombre_asignatura,
        p.nombre, 
        p.apellidos
    INTO 
        v_asignatura_id, 
        v_profesor_asignado_id, 
        v_nombre_asignatura,
        v_profesor_nombre, 
        v_profesor_apellidos
    FROM 
        asignaturas a
        JOIN horarios h ON a.id = h.asignatura_id
        JOIN profesores p ON a.profesor_id = p.id
    WHERE 
        a.aula_id = v_aula_id
        AND h.dia_semana = v_dia_actual
        AND v_hora_actual BETWEEN h.hora_inicio AND h.hora_fin
    LIMIT 1;
    
    -- Si no hay clase en este momento en esta aula, salir
    IF v_asignatura_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No hay clase en este momento en esta aula';
        LEAVE proc_label;  /* Usamos la etiqueta aquí */
    END IF;
    
    -- Paso 2: Obtener información del profesor que pasa la tarjeta
    SELECT 
        id, 
        CONCAT(nombre, ' ', apellidos) AS nombre_completo
    INTO 
        v_profesor_tarjeta_id, 
        v_profesor_tarjeta_nombre
    FROM 
        profesores
    WHERE 
        identificador = p_identificador_profesor
    LIMIT 1;
    
    -- Si no se encuentra el profesor, salir
    IF v_profesor_tarjeta_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Profesor no encontrado';
        LEAVE proc_label;  /* Usamos la etiqueta aquí */
    END IF;
    
    -- Paso 3: Verificar si ya existe la asistencia para hoy
    SELECT 
        id
    INTO 
        v_asistencia_id
    FROM 
        asistencias
    WHERE 
        asignatura_id = v_asignatura_id
        AND fecha = CURDATE();
    
    -- Si no existe la asistencia, crearla
    IF v_asistencia_id IS NULL THEN
        INSERT INTO asistencias (asignatura_id, fecha, presente)
        VALUES (v_asignatura_id, CURDATE(), 1);
        
        SET v_asistencia_id = LAST_INSERT_ID();
    ELSE
        -- Si ya existe, actualizarla
        UPDATE asistencias
        SET presente = 1
        WHERE id = v_asistencia_id;
    END IF;
    
    -- Paso 5: Crear incidencia cuando el profesor que registra NO es el asignado
    IF v_profesor_tarjeta_id <> v_profesor_asignado_id THEN
        INSERT INTO incidencias (asistencia_id, justificada, descripcion, fecha_incidencia)
        VALUES (
            v_asistencia_id,
            0,
            CONCAT(
                'Asignatura: ', v_nombre_asignatura, 
                ', Hora: ', TIME_FORMAT(v_hora_actual, '%H:%i'), 
                ', Profesor que registró: ', v_profesor_tarjeta_nombre,
                ', Profesor asignado: ', v_profesor_nombre, ' ', v_profesor_apellidos
            ),
            NOW()
        );
    END IF;
    
    -- Informar del éxito
    SELECT 
        'Asistencia registrada correctamente' AS mensaje,
        v_nombre_asignatura AS asignatura,
        v_profesor_tarjeta_nombre AS profesor_que_registra,
        v_profesor_nombre AS profesor_asignado_nombre,
        v_profesor_apellidos AS profesor_asignado_apellidos,
        (v_profesor_tarjeta_id <> v_profesor_asignado_id) AS se_creo_incidencia;
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`%` FUNCTION `get_dia_espanol` () RETURNS VARCHAR(20) CHARSET utf8mb4 DETERMINISTIC BEGIN
    DECLARE dia_es VARCHAR(20);
    DECLARE dia_en VARCHAR(20);
    
    SET dia_en = DAYNAME(CURDATE());
    
    -- Mapeo de días en inglés a español
    CASE dia_en
        WHEN 'Monday' THEN SET dia_es = 'Lunes';
        WHEN 'Tuesday' THEN SET dia_es = 'Martes';
        WHEN 'Wednesday' THEN SET dia_es = 'Miércoles';
        WHEN 'Thursday' THEN SET dia_es = 'Jueves';
        WHEN 'Friday' THEN SET dia_es = 'Viernes';
        WHEN 'Saturday' THEN SET dia_es = 'Sábado';
        WHEN 'Sunday' THEN SET dia_es = 'Domingo';
    END CASE;
    
    RETURN dia_es;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaturas`
--

CREATE TABLE `asignaturas` (
  `id` int NOT NULL,
  `profesor_id` int NOT NULL,
  `aula_id` int NOT NULL,
  `nombre_asignatura` varchar(100) NOT NULL,
  `grupo` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `asignaturas`
--

INSERT INTO `asignaturas` (`id`, `profesor_id`, `aula_id`, `nombre_asignatura`, `grupo`) VALUES
(1, 1, 1, 'Programación', '1ºA'),
(2, 28, 12, 'Álgebra', '1ºB'),
(6, 28, 12, 'Fal', '4ºB'),
(7, 5, 1, 'SO', '4ºB'),
(9, 6, 3, 'Cálculo', '1ºA'),
(10, 7, 4, 'Física', '1ºB'),
(11, 28, 12, 'Algoritmos', '2ºA'),
(12, 9, 6, 'Bases de Datos', '2ºB'),
(13, 10, 7, 'Redes', '3ºA'),
(14, 11, 8, 'Sistemas Operativos', '3ºB'),
(15, 12, 9, 'Inteligencia Artificial', '4ºA'),
(16, 13, 10, 'Compiladores', '4ºB'),
(17, 27, 13, 'Estadística', '1ºC'),
(18, 27, 13, 'Matemática Discreta', '1ºD'),
(19, 27, 13, 'Estructura de Computadores', '2ºC'),
(20, 26, 14, 'Programación Avanzada', '2ºD'),
(21, 26, 14, 'Ingeniería del Software', '3ºC'),
(22, 26, 14, 'Seguridad Informática', '3ºD'),
(23, 20, 7, 'Computación Gráfica', '4ºC'),
(24, 21, 8, 'Sistemas Distribuidos', '4ºD'),
(25, 22, 9, 'Aprendizaje Automático', '3ºA'),
(26, 23, 10, 'Minería de Datos', '3ºB'),
(27, 24, 1, 'Big Data', '4ºA'),
(28, 25, 2, 'Desarrollo Web', '2ºA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id` int NOT NULL,
  `asignatura_id` int NOT NULL,
  `fecha` date NOT NULL,
  `presente` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `asistencias`
--

INSERT INTO `asistencias` (`id`, `asignatura_id`, `fecha`, `presente`) VALUES
(5, 1, '2025-03-03', 0),
(6, 6, '2025-03-14', 0),
(7, 7, '2025-03-14', 0),
(8, 9, '2025-03-04', 0),
(9, 10, '2025-03-04', 0),
(10, 11, '2025-03-04', 0),
(11, 12, '2025-03-04', 0),
(12, 13, '2025-03-04', 0),
(13, 14, '2025-03-05', 0),
(14, 15, '2025-03-05', 0),
(15, 16, '2025-03-05', 0),
(16, 17, '2025-03-05', 0),
(17, 18, '2025-03-05', 0),
(18, 19, '2025-03-06', 0),
(19, 20, '2025-03-06', 0),
(20, 21, '2025-03-06', 0),
(21, 22, '2025-03-06', 0),
(22, 23, '2025-03-06', 0),
(23, 24, '2025-03-07', 0),
(24, 25, '2025-03-07', 0),
(25, 26, '2025-03-07', 0),
(26, 27, '2025-03-07', 0),
(27, 28, '2025-03-07', 0),
(28, 9, '2025-04-01', 0),
(29, 10, '2025-04-01', 0),
(30, 11, '2025-04-01', 0),
(31, 12, '2025-04-01', 0),
(32, 13, '2025-04-01', 0),
(33, 14, '2025-04-02', 0),
(34, 15, '2025-04-02', 0),
(35, 16, '2025-04-02', 0),
(36, 17, '2025-04-02', 0),
(37, 18, '2025-04-02', 0),
(38, 19, '2025-04-03', 0),
(39, 20, '2025-04-03', 0),
(40, 21, '2025-04-03', 0),
(41, 22, '2025-04-03', 0),
(42, 23, '2025-04-03', 0),
(43, 24, '2025-04-04', 0),
(44, 25, '2025-04-04', 0),
(45, 26, '2025-04-04', 0),
(46, 27, '2025-04-04', 0),
(47, 28, '2025-04-04', 0),
(55, 2, '2025-05-13', 1),
(56, 14, '2025-05-13', 0),
(57, 15, '2025-05-13', 0),
(58, 16, '2025-05-13', 0),
(59, 17, '2025-05-13', 1),
(60, 18, '2025-05-13', 0),
(78, 14, '2025-05-15', 0),
(79, 15, '2025-05-15', 0),
(80, 16, '2025-05-15', 0),
(81, 23, '2025-05-15', 0),
(82, 24, '2025-05-15', 0),
(83, 25, '2025-05-15', 0),
(84, 26, '2025-05-15', 0),
(85, 27, '2025-05-15', 0),
(86, 2, '2025-05-15', 1),
(87, 11, '2025-05-15', 0),
(88, 6, '2025-05-15', 0),
(89, 17, '2025-05-15', 0),
(90, 19, '2025-05-15', 0),
(91, 18, '2025-05-15', 0),
(92, 21, '2025-05-15', 0),
(93, 20, '2025-05-15', 0),
(94, 22, '2025-05-15', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aulas`
--

CREATE TABLE `aulas` (
  `id` int NOT NULL,
  `numero_aula` int NOT NULL,
  `capacidad` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `aulas`
--

INSERT INTO `aulas` (`id`, `numero_aula`, `capacidad`) VALUES
(1, 101, 24),
(2, 102, 25),
(3, 103, 40),
(4, 104, 30),
(5, 105, 25),
(6, 106, 35),
(7, 201, 40),
(8, 202, 45),
(9, 203, 30),
(10, 204, 35),
(11, 205, 28),
(12, 301, 50),
(13, 302, 45),
(14, 303, 30);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamento`
--

CREATE TABLE `departamento` (
  `id` int NOT NULL,
  `nombre_departamento` varchar(100) NOT NULL,
  `jefe_id` int DEFAULT NULL,
  `correo_departamento` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `departamento`
--

INSERT INTO `departamento` (`id`, `nombre_departamento`, `jefe_id`, `correo_departamento`) VALUES
(1, 'Computadores', 1, 'computadores@complutense.es'),
(2, 'Redes', 2, 'redes@complutense.es'),
(3, 'Software', 3, 'software@complutense.es');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios`
--

CREATE TABLE `horarios` (
  `id` int NOT NULL,
  `asignatura_id` int NOT NULL,
  `dia_semana` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `horarios`
--

INSERT INTO `horarios` (`id`, `asignatura_id`, `dia_semana`, `hora_inicio`, `hora_fin`) VALUES
(1, 1, 'Lunes', '09:00:00', '11:00:00'),
(12, 7, 'Viernes', '16:00:00', '18:00:00'),
(13, 9, 'Lunes', '08:00:00', '10:00:00'),
(14, 9, 'Miércoles', '08:00:00', '10:00:00'),
(15, 10, 'Lunes', '10:00:00', '12:00:00'),
(16, 10, 'Miércoles', '10:00:00', '12:00:00'),
(19, 12, 'Lunes', '15:00:00', '17:00:00'),
(20, 12, 'Miércoles', '15:00:00', '17:00:00'),
(21, 13, 'Lunes', '17:00:00', '19:00:00'),
(22, 13, 'Miércoles', '17:00:00', '19:00:00'),
(23, 14, 'Martes', '08:00:00', '10:00:00'),
(24, 14, 'Jueves', '08:00:00', '10:00:00'),
(25, 15, 'Martes', '10:00:00', '12:00:00'),
(26, 15, 'Jueves', '10:00:00', '12:00:00'),
(27, 16, 'Martes', '12:00:00', '14:00:00'),
(28, 16, 'Jueves', '12:00:00', '14:00:00'),
(41, 23, 'Jueves', '08:00:00', '10:00:00'),
(42, 23, 'Viernes', '17:00:00', '19:00:00'),
(43, 24, 'Jueves', '10:00:00', '12:00:00'),
(44, 24, 'Viernes', '10:00:00', '12:00:00'),
(45, 25, 'Jueves', '12:00:00', '14:00:00'),
(46, 25, 'Viernes', '12:00:00', '14:00:00'),
(47, 26, 'Jueves', '15:00:00', '17:00:00'),
(48, 26, 'Viernes', '15:00:00', '17:00:00'),
(49, 27, 'Jueves', '17:00:00', '19:00:00'),
(50, 27, 'Viernes', '17:00:00', '19:00:00'),
(51, 28, 'Lunes', '08:00:00', '10:00:00'),
(52, 28, 'Viernes', '08:00:00', '10:00:00'),
(61, 2, 'Lunes', '11:00:00', '13:00:00'),
(62, 2, 'Martes', '11:00:00', '13:00:00'),
(63, 2, 'Miércoles', '11:00:00', '13:00:00'),
(64, 2, 'Jueves', '11:00:00', '13:00:00'),
(65, 2, 'Viernes', '11:00:00', '13:00:00'),
(66, 2, 'Sábado', '11:00:00', '13:00:00'),
(67, 2, 'Domingo', '11:00:00', '13:00:00'),
(70, 11, 'Lunes', '09:00:00', '11:00:00'),
(71, 11, 'Martes', '09:00:00', '11:00:00'),
(72, 11, 'Miércoles', '09:00:00', '11:00:00'),
(73, 11, 'Jueves', '09:00:00', '11:00:00'),
(74, 11, 'Viernes', '09:00:00', '11:00:00'),
(75, 11, 'Sábado', '09:00:00', '11:00:00'),
(76, 11, 'Domingo', '09:00:00', '11:00:00'),
(126, 6, 'Lunes', '14:00:00', '17:00:00'),
(127, 6, 'Martes', '14:00:00', '17:00:00'),
(128, 6, 'Miércoles', '14:00:00', '17:00:00'),
(129, 6, 'Jueves', '14:00:00', '17:00:00'),
(130, 6, 'Viernes', '14:00:00', '17:00:00'),
(131, 6, 'Lunes', '14:00:00', '17:00:00'),
(132, 6, 'Lunes', '14:00:00', '17:00:00'),
(133, 17, 'Lunes', '09:00:00', '11:00:00'),
(134, 17, 'Martes', '09:00:00', '11:00:00'),
(135, 17, 'Miércoles', '09:00:00', '11:00:00'),
(136, 17, 'Jueves', '09:00:00', '11:00:00'),
(137, 17, 'Viernes', '09:00:00', '11:00:00'),
(138, 17, 'Lunes', '09:00:00', '11:00:00'),
(139, 17, 'Lunes', '09:00:00', '11:00:00'),
(140, 19, 'Lunes', '14:00:00', '17:00:00'),
(141, 19, 'Martes', '14:00:00', '17:00:00'),
(142, 19, 'Miércoles', '14:00:00', '17:00:00'),
(143, 19, 'Jueves', '14:00:00', '17:00:00'),
(144, 19, 'Viernes', '14:00:00', '17:00:00'),
(145, 19, 'Lunes', '14:00:00', '17:00:00'),
(146, 19, 'Lunes', '14:00:00', '17:00:00'),
(147, 18, 'Lunes', '11:00:00', '13:00:00'),
(148, 18, 'Martes', '11:00:00', '13:00:00'),
(149, 18, 'Miércoles', '11:00:00', '13:00:00'),
(150, 18, 'Jueves', '11:00:00', '13:00:00'),
(151, 18, 'Viernes', '11:00:00', '13:00:00'),
(152, 18, 'Lunes', '11:00:00', '13:00:00'),
(153, 18, 'Lunes', '11:00:00', '13:00:00'),
(154, 21, 'Lunes', '11:00:00', '13:00:00'),
(155, 21, 'Martes', '11:00:00', '13:00:00'),
(156, 21, 'Miércoles', '11:00:00', '13:00:00'),
(157, 21, 'Jueves', '11:00:00', '13:00:00'),
(158, 21, 'Viernes', '11:00:00', '13:00:00'),
(159, 21, 'Lunes', '11:00:00', '13:00:00'),
(160, 21, 'Lunes', '11:00:00', '13:00:00'),
(161, 20, 'Lunes', '09:00:00', '11:00:00'),
(162, 20, 'Martes', '09:00:00', '11:00:00'),
(163, 20, 'Miércoles', '09:00:00', '11:00:00'),
(164, 20, 'Jueves', '09:00:00', '11:00:00'),
(165, 20, 'Viernes', '09:00:00', '11:00:00'),
(166, 20, 'Lunes', '09:00:00', '11:00:00'),
(167, 20, 'Lunes', '09:00:00', '11:00:00'),
(168, 22, 'Lunes', '14:00:00', '17:00:00'),
(169, 22, 'Martes', '14:00:00', '17:00:00'),
(170, 22, 'Miércoles', '14:00:00', '17:00:00'),
(171, 22, 'Jueves', '14:00:00', '17:00:00'),
(172, 22, 'Viernes', '14:00:00', '17:00:00'),
(173, 22, 'Lunes', '14:00:00', '17:00:00'),
(174, 22, 'Lunes', '14:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias`
--

CREATE TABLE `incidencias` (
  `id` int NOT NULL,
  `asistencia_id` int NOT NULL,
  `justificada` tinyint(1) NOT NULL,
  `descripcion` text,
  `fecha_incidencia` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `incidencias`
--

INSERT INTO `incidencias` (`id`, `asistencia_id`, `justificada`, `descripcion`, `fecha_incidencia`) VALUES
(10, 8, 0, 'No se paso la tarjeta', '2025-03-04 09:15:00'),
(11, 9, 1, 'Cita médica urgente', '2025-03-04 10:30:00'),
(12, 10, 0, 'No se paso la tarjeta', '2025-03-04 12:45:00'),
(13, 11, 1, 'Problema familiar', '2025-03-04 15:20:00'),
(14, 12, 0, 'No se paso la tarjeta', '2025-03-04 17:10:00'),
(15, 13, 1, 'Enfermedad', '2025-03-05 09:05:00'),
(16, 14, 0, 'No se paso la tarjeta', '2025-03-05 10:15:00'),
(17, 15, 1, 'Asistencia a congreso académico', '2025-03-05 12:30:00'),
(18, 16, 0, 'No se paso la tarjeta', '2025-03-05 15:40:00'),
(19, 17, 1, 'Accidente de tráfico', '2025-03-05 17:25:00'),
(20, 18, 0, 'No se paso la tarjeta', '2025-03-06 09:00:00'),
(21, 19, 1, 'Problema con transporte público', '2025-03-06 10:45:00'),
(22, 20, 0, 'No se paso la tarjeta', '2025-03-06 12:50:00'),
(23, 21, 1, 'Trámites administrativos urgentes', '2025-03-06 15:15:00'),
(24, 22, 0, 'No se paso la tarjeta', '2025-03-06 17:30:00'),
(25, 23, 1, 'Enfermedad', '2025-03-07 09:20:00'),
(26, 24, 0, 'No se paso la tarjeta', '2025-03-07 10:10:00'),
(27, 25, 1, 'Asistencia a seminario', '2025-03-07 12:40:00'),
(28, 26, 0, 'No se paso la tarjeta', '2025-03-07 15:30:00'),
(29, 27, 1, 'Problemas personales', '2025-03-07 17:00:00'),
(30, 28, 0, 'No se paso la tarjeta', '2025-04-01 09:05:00'),
(31, 29, 1, 'Enfermedad', '2025-04-01 10:25:00'),
(32, 30, 0, 'No se paso la tarjeta', '2025-04-01 12:35:00'),
(33, 31, 1, 'Asistencia a evento académico', '2025-04-01 15:50:00'),
(34, 32, 0, 'No se paso la tarjeta', '2025-04-01 17:15:00'),
(35, 33, 1, 'Problemas de salud', '2025-04-02 09:10:00'),
(36, 34, 0, 'No se paso la tarjeta', '2025-04-02 10:05:00'),
(37, 35, 1, 'Fallecimiento familiar', '2025-04-02 12:20:00'),
(38, 36, 0, 'No se paso la tarjeta', '2025-04-02 15:45:00'),
(39, 37, 1, 'Cita médica especialista', '2025-04-02 17:05:00'),
(40, 38, 0, 'No se paso la tarjeta', '2025-04-03 09:25:00'),
(41, 39, 1, 'Avería en vehículo', '2025-04-03 10:35:00'),
(42, 40, 0, 'No se paso la tarjeta', '2025-04-03 12:15:00'),
(43, 41, 1, 'Asistencia a defensa de TFG', '2025-04-03 15:10:00'),
(44, 42, 0, 'No se paso la tarjeta', '2025-04-03 17:20:00'),
(45, 43, 1, 'Huelga de transportes', '2025-04-04 09:30:00'),
(46, 44, 0, 'No se paso la tarjeta', '2025-04-04 10:00:00'),
(47, 45, 1, 'Ingreso hospitalario urgente', '2025-04-04 12:25:00'),
(48, 46, 0, 'No se paso la tarjeta', '2025-04-04 15:35:00'),
(49, 47, 1, 'Citación judicial', '2025-04-04 17:40:00'),
(57, 56, 0, 'No se paso la tarjeta', '2025-05-13 21:00:00'),
(58, 57, 0, 'No se paso la tarjeta', '2025-05-13 21:00:00'),
(59, 58, 0, 'No se paso la tarjeta', '2025-05-13 21:00:00'),
(60, 60, 0, 'No se paso la tarjeta', '2025-05-13 21:00:00'),
(64, 86, 0, 'No se paso la tarjeta', '2025-05-15 12:35:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nolectivo`
--

CREATE TABLE `nolectivo` (
  `id` int NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` varchar(255) DEFAULT 'Día restringido'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `nolectivo`
--

INSERT INTO `nolectivo` (`id`, `fecha`, `descripcion`) VALUES
(2, '2025-03-11', 'Prueba'),
(3, '2025-05-01', 'Día del Trabajo'),
(4, '2025-05-02', 'Día de la Comunidad de Madrid'),
(6, '2025-06-24', 'San Juan'),
(7, '2025-07-25', 'Santiago Apóstol'),
(8, '2025-08-15', 'Asunción de la Virgen'),
(9, '2025-10-12', 'Día de la Hispanidad'),
(10, '2025-11-01', 'Día de Todos los Santos'),
(11, '2025-11-09', 'Día de la Almudena'),
(12, '2025-12-06', 'Día de la Constitución'),
(13, '2025-12-08', 'Inmaculada Concepción'),
(14, '2025-12-25', 'Navidad');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `id` int NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `identificador` varchar(50) DEFAULT NULL,
  `CorreoPropio` varchar(100) DEFAULT NULL,
  `departamento_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`id`, `nombre`, `apellidos`, `identificador`, `CorreoPropio`, `departamento_id`) VALUES
(1, 'Raquel', 'Díaz Sánchez', '04 aa bb cc 24 02 89', 'raquel.diaz@complutense.es', 1),
(2, 'Carlos', 'López García', '04 dd ee ff 24 02 89', 'carlos.lopez@complutense.es', 2),
(3, 'Maríant', 'Pérez Gómez', '04 11 22 33 24 02 89', 'maria.perez@complutense.es', 3),
(5, 'Ángel', 'Gallego Muñoz', '04 44 55 66 24 02 89', 'anggal02@ucm.es', 1),
(6, 'Elena', 'Martínez Rodríguez', '04 77 88 99 24 02 89', 'elena.martinez@complutense.es', 1),
(7, 'David', 'García Sánchez', '04 ab cd ef 24 02 89', 'david.garcia@complutense.es', 2),
(8, 'Laura', 'Hernández López', '04 fe dc ba 24 02 89', 'laura.hernandez@complutense.es', 3),
(9, 'Javier', 'Fernández González', '04 12 34 56 24 02 89', 'javier.fernandez@complutense.es', 1),
(10, 'Ana', 'González Pérez', '04 78 9a bc 24 02 89', 'ana.gonzalez@complutense.es', 2),
(11, 'Pablo', 'Sánchez Martínez', '04 de f0 12 24 02 89', 'pablo.sanchez@complutense.es', 3),
(12, 'Lucía', 'López García', '04 34 56 78 24 02 89', 'lucia.lopez@complutense.es', 1),
(13, 'Jorge', 'Pérez Hernández', '04 9a bc de 24 02 89', 'jorge.perez@complutense.es', 2),
(14, 'Sara', 'Rodríguez Fernández', '04 f0 12 34 24 02 89', 'sara.rodriguez@complutense.es', 3),
(15, 'Miguel', 'González García', '04 56 78 9a 24 02 89', 'miguel.gonzalez@complutense.es', 1),
(16, 'Carmen', 'López Rodríguez', '04 bc de f0 24 02 89', 'carmen.lopez@complutense.es', 2),
(17, 'Luis', 'Martínez González', '04 13 57 9b 24 02 89', 'luis.martinez@complutense.es', 3),
(18, 'Paula', 'Sánchez López', '04 24 68 ac 24 02 89', 'paula.sanchez@complutense.es', 1),
(19, 'Alberto', 'García Martínez', '04 35 79 bd 24 02 89', 'alberto.garcia@complutense.es', 2),
(20, 'Eva', 'Fernández Sánchez', '04 46 8a ce 24 02 89', 'eva.fernandez@complutense.es', 3),
(21, 'Daniel', 'Hernández González', '04 57 9b df 24 02 89', 'daniel.hernandez@complutense.es', 1),
(22, 'Marina', 'Pérez García', '04 68 ac e0 24 02 89', 'marina.perez@complutense.es', 2),
(23, 'Adrián', 'Rodríguez López', '04 79 bd f1 24 02 89', 'adrian.rodriguez@complutense.es', 3),
(24, 'Marta', 'González Hernández', '04 8a ce 02 24 02 89', 'marta.gonzalez@complutense.es', 1),
(25, 'Diego', 'López Pérez', '04 9b df 13 24 02 89', 'diego.lopez@complutense.es', 2),
(26, 'María', 'González Rodríguez', '04 41 73 46 24 02 89', 'maria.gonzalez@complutense.es', 1),
(27, 'Dorzhi', 'García España', '04 31 2d 4b 24 02 89', 'dorzhi.garcia@ucm.es', 1),
(28, 'Daniel', 'Lopez Escobar', '04 01 53 67 24 02 89', 'daniel.lopez@ucm.es', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rol` enum('admin','editor','lector') NOT NULL DEFAULT 'lector',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `rol`, `fecha_creacion`) VALUES
(5, 'admin', '$2y$10$Slf/MTCSdKc7NV96IJUw5uDGcYMcFpFydb4hDLtWET96ntAEmRqn2', 'Administrador', 'admin', '2025-04-30 09:25:56');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aula_id` (`aula_id`),
  ADD KEY `asignaturas_ibfk_1` (`profesor_id`);

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asignatura_id` (`asignatura_id`);

--
-- Indices de la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `departamento`
--
ALTER TABLE `departamento`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asignatura_id` (`asignatura_id`);

--
-- Indices de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incidencias_ibfk_1` (`asistencia_id`);

--
-- Indices de la tabla `nolectivo`
--
ALTER TABLE `nolectivo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fecha` (`fecha`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `identificador_UNIQUE` (`identificador`),
  ADD KEY `departamento_id` (`departamento_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT de la tabla `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `departamento`
--
ALTER TABLE `departamento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de la tabla `nolectivo`
--
ALTER TABLE `nolectivo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD CONSTRAINT `asignaturas_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asignaturas_ibfk_2` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`);

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`);

--
-- Filtros para la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD CONSTRAINT `horarios_ibfk_1` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`);

--
-- Filtros para la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD CONSTRAINT `incidencias_ibfk_1` FOREIGN KEY (`asistencia_id`) REFERENCES `asistencias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD CONSTRAINT `profesores_ibfk_1` FOREIGN KEY (`departamento_id`) REFERENCES `departamento` (`id`);

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`root`@`%` EVENT `evento_generar_incidencias` ON SCHEDULE EVERY 1 DAY STARTS '2025-05-13 21:00:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
        IF WEEKDAY(CURRENT_DATE) BETWEEN 0 AND 4 THEN
            CALL GenerarIncidencias();
        END IF;
    END$$

CREATE DEFINER=`root`@`%` EVENT `GeneradorAsistencias` ON SCHEDULE EVERY 1 DAY STARTS '2025-05-13 08:00:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    IF WEEKDAY(CURRENT_DATE) BETWEEN 0 AND 4 THEN
        CALL generar_asistencias_diarias();
    END IF;
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
