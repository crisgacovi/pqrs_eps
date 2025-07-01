-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 01-07-2025 a las 20:30:43
-- Versión del servidor: 10.11.10-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u152423165_pqrs_eps`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciudades`
--

CREATE TABLE `ciudades` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `estado` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ciudades`
--

INSERT INTO `ciudades` (`id`, `nombre`, `departamento`, `estado`) VALUES
(1, 'Boavita', 'Boyacá', 1),
(2, 'Caldas', 'Boyacá', 1),
(3, 'El Espino', 'Boyacá', 1),
(4, 'Guacamayas', 'Boyacá', 1),
(5, 'Güicán', 'Boyacá', 1),
(6, 'Moniquirá', 'Boyacá', 1),
(7, 'Samacá', 'Boyacá', 1),
(8, 'Siachoque', 'Boyacá', 1),
(9, 'Sotaquirá', 'Boyacá', 1),
(10, 'Susacón', 'Boyacá', 1),
(11, 'Tinjacá', 'Boyacá', 1),
(12, 'Toca', 'Boyacá', 1),
(13, 'Tuta', 'Boyacá', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eps`
--

CREATE TABLE `eps` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `estado` int(10) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eps`
--

INSERT INTO `eps` (`id`, `nombre`, `email`, `estado`) VALUES
(1, 'Nueva EPS', 'auditorias_boyaca@nuevaeps.com.co', 1),
(2, 'Sanitas', 'notificajudiciales@keralty.com', 1),
(3, 'Famisanar', 'notificaciones@famisanar.com.co', 1),
(4, 'Coosalud', 'rurodriguez@coosalud.com', 1),
(5, 'Cajacopi', 'leydi.gutierrez@cajacopieps.com', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pqrss`
--

CREATE TABLE `pqrss` (
  `id` int(11) NOT NULL,
  `nombre_paciente` varchar(100) NOT NULL,
  `documento_identidad` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `ciudad_id` int(11) NOT NULL,
  `eps_id` int(11) NOT NULL,
  `tipo_pqrs_id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `respuesta` text DEFAULT NULL,
  `archivo_respuesta` varchar(255) DEFAULT NULL,
  `fecha_respuesta` date DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','En Proceso','Resuelto') DEFAULT 'Pendiente',
  `archivo_adjunto` varchar(255) DEFAULT NULL,
  `email_enviado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_pqrs`
--

CREATE TABLE `tipos_pqrs` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` int(10) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_pqrs`
--

INSERT INTO `tipos_pqrs` (`id`, `nombre`, `descripcion`, `estado`) VALUES
(1, 'Demora en la entrega de medicamentos o no entrega completa', 'Demora en la entrega de medicamentos o no entrega completa', 1),
(2, 'Falta de oportunidad en la generación de las autorizaciones', 'Falta de oportunidad en la generación de las autorizaciones', 1),
(3, 'Falta de oportunidad en la asignación de cita de medicina general y/o odontología general', 'Falta de oportunidad en la asignación de cita de medicina general y/o odontología general', 1),
(4, 'Falta de oportunidad en la asignación de citas por medicina especializada y /o ayudas diagnósticas (', 'Falta de oportunidad en la asignación de citas por medicina especializada y /o ayudas diagnósticas (exámenes)', 1),
(5, 'Falta de oportunidad en la remisión intrahospitalaria', 'Falta de oportunidad en la remisión intrahospitalaria', 1),
(6, 'Fallas en la atención por parte del funcionario de la EPS a la que se encuentra afiliado', 'Fallas en la atención por parte del funcionario de la EPS a la que se encuentra afiliado', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','editor','consultor_ciudad') NOT NULL DEFAULT 'editor',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre_completo`, `email`, `role`, `estado`, `ultimo_login`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'admin', '$2y$10$ul0SaUFvkl3W8X/Ftn1yBe9ygpOGKE1ZEc/cdwDUfh8aVi6BvC0Uq', 'Administrador del Sistema', 'admin@sistema.com', 'admin', 1, '2025-07-01 17:42:04', '2025-04-23 21:10:28', '2025-07-01 17:42:04'),
(2, 'editor', '$2y$10$H2HBFAnfN2.56/08Ad6L3uA1cX.PlSfSbukYusfgLg4qRROybHc9y', 'Editor del Sistema', 'editor@sistema.com', 'editor', 1, '2025-05-12 19:53:47', '2025-04-23 21:10:28', '2025-05-13 00:53:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_ciudad`
--

CREATE TABLE `usuario_ciudad` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ciudad_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ciudades`
--
ALTER TABLE `ciudades`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `eps`
--
ALTER TABLE `eps`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pqrss`
--
ALTER TABLE `pqrss`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ciudad_id` (`ciudad_id`),
  ADD KEY `eps_id` (`eps_id`),
  ADD KEY `tipo_pqrs_id` (`tipo_pqrs_id`);

--
-- Indices de la tabla `tipos_pqrs`
--
ALTER TABLE `tipos_pqrs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `usuario_ciudad`
--
ALTER TABLE `usuario_ciudad`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usuario_ciudad` (`usuario_id`,`ciudad_id`),
  ADD KEY `ciudad_id` (`ciudad_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ciudades`
--
ALTER TABLE `ciudades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `eps`
--
ALTER TABLE `eps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pqrss`
--
ALTER TABLE `pqrss`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_pqrs`
--
ALTER TABLE `tipos_pqrs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuario_ciudad`
--
ALTER TABLE `usuario_ciudad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `pqrss`
--
ALTER TABLE `pqrss`
  ADD CONSTRAINT `pqrss_ibfk_1` FOREIGN KEY (`ciudad_id`) REFERENCES `ciudades` (`id`),
  ADD CONSTRAINT `pqrss_ibfk_2` FOREIGN KEY (`eps_id`) REFERENCES `eps` (`id`),
  ADD CONSTRAINT `pqrss_ibfk_3` FOREIGN KEY (`tipo_pqrs_id`) REFERENCES `tipos_pqrs` (`id`);

--
-- Filtros para la tabla `usuario_ciudad`
--
ALTER TABLE `usuario_ciudad`
  ADD CONSTRAINT `usuario_ciudad_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuario_ciudad_ibfk_2` FOREIGN KEY (`ciudad_id`) REFERENCES `ciudades` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
