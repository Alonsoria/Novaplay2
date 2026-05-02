-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-02-2026 a las 19:43:44
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `novaplay`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combos`
--

CREATE TABLE `combos` (
  `id_combo` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `combos`
--

INSERT INTO `combos` (`id_combo`, `nombre`, `descripcion`, `precio`, `imagen`, `activo`) VALUES
(1, 'Halo Infinite + Game Pass 3 Meses', 'Incluye el juego Halo Infinite más 3 meses de Xbox Game Pass Ultimate.', 899.99, './images/combos/halo_gamepass.png', 1),
(2, 'Mario Oddyssey + Animal Crossing', 'Paquete que incluye Minecraft base junto con el juego de Animal Crossing.', 499.99, './images/combos/minecraft_animal.png', 1),
(3, 'FIFA 24 + 3 Playstation Plus Deluxe 3 meses', 'Incluye FIFA 24 más una tarjeta de 3 meses de PlayStation Plus Deluxe.', 1199.00, './images/combos/fifa_combo.png', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combo_relacion`
--

CREATE TABLE `combo_relacion` (
  `id_relacion` int(11) NOT NULL,
  `id_combo` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fidelizacion`
--

CREATE TABLE `fidelizacion` (
  `id_fidelizacion` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `puntos` int(11) DEFAULT 0,
  `nivel` enum('Bronce','Plata','Oro','Diamante') DEFAULT 'Bronce'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_pedido` int(11) DEFAULT NULL,
  `metodo_pago` enum('tarjeta','paypal','transferencia','criptomoneda') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pagado','enviado','cancelado') DEFAULT 'pendiente',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plataformas`
--

CREATE TABLE `plataformas` (
  `id_plataforma` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `icono` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `plataformas`
--

INSERT INTO `plataformas` (`id_plataforma`, `nombre`, `icono`) VALUES
(1, 'Xbox', './images/plataformas/xboxlogo.png'),
(2, 'PlayStation', './images/plataformas/playstationlogo.png'),
(3, 'Steam', './images/plataformas/steamlogo.png'),
(4, 'Nintendo', './images/plataformas/nintendologo.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` enum('videojuego','tarjeta','suscripcion','paquete','DLC','moneda_virtual','accesorio') NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `codigo_digital` tinyint(1) DEFAULT 1,
  `fecha_alta` timestamp NOT NULL DEFAULT current_timestamp(),
  `imagen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `nombre`, `descripcion`, `categoria`, `precio`, `stock`, `codigo_digital`, `fecha_alta`, `imagen`) VALUES
(1, 'GTA V', 'Juego de mundo abierto de Rockstar Games', 'videojuego', 499.99, 0, 1, '2025-09-24 07:42:09', './images/gta.jpg'),
(2, 'The Last of Us', 'Aventura y supervivencia', 'videojuego', 699.99, 0, 1, '2025-09-24 07:42:09', './images/tlou.jpg'),
(3, 'FIFA 24', 'Simulador de fútbol de EA Sports', 'videojuego', 999.99, 0, 1, '2025-09-24 07:42:09', './images/fifa24.jpg'),
(4, 'Minecraft', 'Juego de construcción y aventura', 'videojuego', 299.99, 0, 1, '2025-09-24 07:42:09', './images/minecraft.jpg'),
(5, 'Friday Night Funkin', 'Impresiona a tu novia enfrentando a distintos enemigos en este juego ritmico.', 'videojuego', 399.99, 50, 1, '2025-09-24 22:34:09', './images/FridayNightFunkin2.png'),
(6, 'Xbox Game Pass Ultimate 1 mes', 'Acceso ilimitado a juegos de Xbox y PC por 1 mes', 'suscripcion', 199.00, 50, 1, '2025-09-25 01:00:22', './images/productos/GamepassLogo.png'),
(7, 'Cyberpunk 2077', 'Explora Night City en este RPG futurista lleno de aventuras cyberpunk.', 'videojuego', 799.99, 50, 1, '2025-09-25 01:18:53', './images/cyberpunk2077.jpg'),
(8, 'PlayStation Plus Deluxe 1 mes', 'Acceso a online y juegos exclusivamente gratis por 1 mes', 'suscripcion', 256.00, 100, 1, '2025-09-25 01:10:41', './images/productos/Playstationplus.jpg'),
(9, 'PlayStation Plus Deluxe 3 meses', 'Acceso a online y juegos exclusivamente gratis por 3 mes', 'suscripcion', 386.00, 100, 1, '2025-09-25 01:12:09', './images/productos/Playstationplus.jpg'),
(10, 'PlayStation Plus Deluxe 1 año', 'Acceso a online y juegos exclusivamente gratis por 1 año', 'suscripcion', 2300.00, 100, 1, '2025-09-25 01:13:31', './images/productos/Playstationplus.jpg'),
(11, 'Xbox Game Pass Ultimate 3 meses', 'Acceso ilimitado a juegos de Xbox y PC por 3 meses', 'suscripcion', 256.00, 100, 1, '2025-09-25 01:14:47', './images/productos/GamepassLogo.png'),
(12, 'Xbox Game Pass Ultimate 1 año', 'Acceso ilimitado a juegos de Xbox y PC por 1 año', 'suscripcion', 1500.00, 100, 1, '2025-09-25 01:15:39', './images/productos/GamepassLogo.png'),
(13, 'Control Inalámbrico Xbox Series X|S', 'Nuevo control Xbox con mejor ergonomía y textura antideslizante, color Rojo con Negro', 'accesorio', 1800.00, 15, 0, '2025-09-25 01:18:30', './images/productos/Xbox_controller.png'),
(14, 'Audífonos Gamer Sonido Envolvente 7.1 Conexión USB Assault | VORTRED', 'Audifonos para cualquier tipo de consola o computadora con entrada USB', 'accesorio', 800.00, 100, 1, '2025-09-25 01:33:40', './images/productos/AudifonosVortex.png'),
(15, 'Mouse Gamer Redragon M715Rgb-1 Dagger 2 Negro Rgb 8 Botones 10000 Dpi', 'Mouse para cualquier tipo de computadora o dispositivo capaz de soportar mouse', 'accesorio', 660.00, 100, 1, '2025-09-25 01:35:33', './images/productos/MouseGamer.png'),
(16, 'Numskull Official Sonic the Hedgehog Gaming Locker y Estante', 'Estante del famoso videojuego Sonic the Hedgehog, soporta hasta 4 controles y headsets', 'accesorio', 500.00, 100, 1, '2025-09-25 01:45:28', './images/productos/Sonicestante.png'),
(17, 'God of War', 'Acompaña a Kratos y Atreus en su viaje por la mitología nórdica.', 'videojuego', 799.99, 50, 1, '2025-09-25 01:18:53', './images/god_of_war.jpg'),
(19, 'Mortal Kombat 11', 'El juego de lucha más brutal con fatalities espectaculares.', 'videojuego', 549.99, 50, 1, '2025-09-25 01:18:53', './images/mk11.jpg'),
(20, 'Animal Crossing: New Horizons', 'Crea tu paraíso personal en una isla tropical.', 'videojuego', 899.99, 50, 1, '2025-09-25 01:18:53', './images/animal_crossing.jpg'),
(21, 'Super Mario Odyssey', 'Únete a Mario en una aventura 3D por todo el mundo.', 'videojuego', 849.99, 50, 1, '2025-09-25 01:18:53', './images/mario_odyssey.jpg'),
(22, 'The Legend of Zelda: Breath of the Wild', 'Explora Hyrule en la aventura más épica de Link.', 'videojuego', 899.99, 50, 1, '2025-09-25 01:18:53', './images/zelda_botw.jpg'),
(24, 'Doom Eternal', 'Shooter frenético contra demonios del infierno.', 'videojuego', 449.99, 50, 1, '2025-09-25 01:18:53', './images/doom_eternal.jpg'),
(25, 'Resident Evil 4', 'Horror y supervivencia en su máxima expresión.', 'videojuego', 699.99, 50, 1, '2025-09-25 01:18:53', './images/re4.jpg'),
(26, 'Street Fighter 6', 'El fighting game más técnico con combos espectaculares.', 'videojuego', 899.99, 50, 1, '2025-09-25 01:18:53', './images/sf6.jpg'),
(27, 'Elden Ring', 'RPG de mundo abierto de los creadores de Dark Souls.', 'videojuego', 999.99, 50, 1, '2025-09-25 01:18:53', './images/elden_ring.jpg'),
(28, 'Hogwarts Legacy', 'Vive tu propia aventura mágica en el mundo de Harry Potter.', 'videojuego', 1099.99, 50, 1, '2025-09-25 01:18:53', './images/hogwarts_legacy.jpg'),
(29, 'Stray', 'Aventura como un gato callejero en una ciudad cyberpunk.', 'videojuego', 449.99, 50, 1, '2025-09-25 01:18:53', './images/stray.jpg'),
(30, 'It Takes Two', 'Aventura cooperativa que debes jugar en pareja.', 'videojuego', 599.99, 50, 1, '2025-09-25 01:18:53', './images/it_takes_two.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_plataforma`
--

CREATE TABLE `producto_plataforma` (
  `id_producto` int(11) NOT NULL,
  `id_plataforma` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto_plataforma`
--

INSERT INTO `producto_plataforma` (`id_producto`, `id_plataforma`) VALUES
(1, 1),
(2, 2),
(3, 1),
(3, 2),
(3, 3),
(4, 4),
(5, 1),
(5, 2),
(5, 3),
(6, 1),
(7, 1),
(7, 3),
(8, 2),
(9, 2),
(10, 2),
(11, 1),
(12, 1),
(13, 1),
(13, 3),
(14, 1),
(14, 2),
(14, 3),
(14, 4),
(15, 1),
(15, 2),
(15, 3),
(16, 1),
(16, 2),
(16, 3),
(16, 4),
(17, 2),
(17, 3),
(19, 1),
(19, 2),
(19, 3),
(19, 4),
(20, 4),
(21, 4),
(22, 4),
(24, 1),
(24, 2),
(24, 3),
(24, 4),
(25, 1),
(25, 2),
(25, 3),
(26, 1),
(26, 2),
(26, 3),
(27, 1),
(27, 3),
(28, 1),
(28, 2),
(28, 3),
(28, 4),
(29, 2),
(29, 3),
(30, 1),
(30, 2),
(30, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `rol` enum('cliente','admin') DEFAULT 'cliente',
  `puntos` int(11) NOT NULL DEFAULT 0,
  `ultima_recompensa` date DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `combos`
--
ALTER TABLE `combos`
  ADD PRIMARY KEY (`id_combo`);

--
-- Indices de la tabla `combo_relacion`
--
ALTER TABLE `combo_relacion`
  ADD PRIMARY KEY (`id_relacion`),
  ADD KEY `id_combo` (`id_combo`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `fidelizacion`
--
ALTER TABLE `fidelizacion`
  ADD PRIMARY KEY (`id_fidelizacion`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `plataformas`
--
ALTER TABLE `plataformas`
  ADD PRIMARY KEY (`id_plataforma`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `producto_plataforma`
--
ALTER TABLE `producto_plataforma`
  ADD PRIMARY KEY (`id_producto`,`id_plataforma`),
  ADD KEY `id_plataforma` (`id_plataforma`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `combos`
--
ALTER TABLE `combos`
  MODIFY `id_combo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `combo_relacion`
--
ALTER TABLE `combo_relacion`
  MODIFY `id_relacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fidelizacion`
--
ALTER TABLE `fidelizacion`
  MODIFY `id_fidelizacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `plataformas`
--
ALTER TABLE `plataformas`
  MODIFY `id_plataforma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`id_usuario`)  REFERENCES `usuarios`  (`id_usuario`)  ON DELETE CASCADE,
  ADD CONSTRAINT `carrito_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `combo_relacion`
--
ALTER TABLE `combo_relacion`
  ADD CONSTRAINT `combo_relacion_ibfk_1` FOREIGN KEY (`id_combo`) REFERENCES `combos` (`id_combo`) ON DELETE CASCADE,
  ADD CONSTRAINT `combo_relacion_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE;

--
-- Datos para combo_relacion
--
INSERT INTO `combo_relacion` (`id_combo`, `id_producto`) VALUES
(1, 11),
(2, 21),
(2, 20),
(3,  3),
(3,  9);

--
-- Filtros para la tabla `fidelizacion`
--
ALTER TABLE `fidelizacion`
  ADD CONSTRAINT `fidelizacion_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `producto_plataforma`
--
ALTER TABLE `producto_plataforma`
  ADD CONSTRAINT `producto_plataforma_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE,
  ADD CONSTRAINT `producto_plataforma_ibfk_2` FOREIGN KEY (`id_plataforma`) REFERENCES `plataformas` (`id_plataforma`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
