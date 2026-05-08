-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: localhost    Database: novaplay2
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `carrito`
--

DROP TABLE IF EXISTS `carrito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrito` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `carrito_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carrito`
--

LOCK TABLES `carrito` WRITE;
/*!40000 ALTER TABLE `carrito` DISABLE KEYS */;
INSERT INTO `carrito` VALUES (58,1,2,4),(62,1,31,1);
/*!40000 ALTER TABLE `carrito` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codigos_activacion`
--

DROP TABLE IF EXISTS `codigos_activacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `codigos_activacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `imagen_producto` varchar(255) DEFAULT NULL,
  `codigo` char(16) NOT NULL COMMENT '16 chars alfanumérico único',
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `id_pedido` (`id_pedido`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `codigos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codigos_activacion`
--

LOCK TABLES `codigos_activacion` WRITE;
/*!40000 ALTER TABLE `codigos_activacion` DISABLE KEYS */;
INSERT INTO `codigos_activacion` VALUES (1,4,2,7,'Cyberpunk 2077','./images/cyberpunk2077.jpg','R678TEHETAL37XTE','2026-05-02 05:14:13'),(2,4,2,3,'FIFA 24','./images/fifa24.jpg','9D4QKRQAJ1S5YYES','2026-05-02 05:14:13'),(3,5,2,7,'Cyberpunk 2077','./images/cyberpunk2077.jpg','8MFW2WD3SOJ2WWWE','2026-05-02 07:00:14'),(4,5,2,14,'Audífonos Gamer Sonido Envolvente 7.1 Conexión USB Assault | VORTRED','./images/productos/AudifonosVortex.png','XTLUQYFQ0HTCO7UV','2026-05-02 07:00:14'),(5,6,2,2,'The Last of Us','./images/tlou.jpg','FDUW4WG4YQ1SOMDO','2026-05-02 07:27:36'),(6,6,2,1,'GTA V','./images/gta.jpg','UR4VO423U3SBDPCL','2026-05-02 07:27:36'),(7,7,2,2,'The Last of Us','./images/tlou.jpg','QT5JZHICT36F7NJF','2026-05-02 07:28:46'),(8,7,2,3,'FIFA 24','./images/fifa24.jpg','LY4LLGI4PA5E15AT','2026-05-02 07:28:46'),(9,8,2,2,'The Last of Us','./images/tlou.jpg','18BIG9WVU3GG3I78','2026-05-02 07:29:39'),(10,9,2,7,'Cyberpunk 2077','./images/cyberpunk2077.jpg','5OJJYNYIYWGZR3RF','2026-05-02 07:30:22'),(11,10,1,3,'FIFA 24','./images/fifa24.jpg','V1QU2RFBQWHNREY6','2026-05-02 17:43:16'),(12,10,1,17,'God of War','./images/god_of_war.jpg','216D28QAN2P72SQ9','2026-05-02 17:43:16'),(13,10,1,25,'Resident Evil 4','./images/re4.jpg','96OBGTHDMY0Z19N2','2026-05-02 17:43:16'),(14,10,1,24,'Doom Eternal','./images/doom_eternal.jpg','CBRB70EG5F46OJGX','2026-05-02 17:43:16'),(15,11,1,2,'The Last of Us','./images/tlou.jpg','UUBABDSPMG9Y9MC9','2026-05-02 17:45:15'),(16,11,1,3,'FIFA 24','./images/fifa24.jpg','6U07ZY94QIX3XYDD','2026-05-02 17:45:15'),(17,12,1,3,'FIFA 24','./images/fifa24.jpg','73V2EIXCV0GYAGP1','2026-05-02 19:09:09'),(18,12,1,2,'The Last of Us','./images/tlou.jpg','9EZSPX81M74OCDQU','2026-05-02 19:09:09'),(19,13,1,3,'FIFA 24','./images/fifa24.jpg','WF2WHBJZENIIFYSD','2026-05-02 22:09:00'),(20,13,1,4,'Minecraft','./images/minecraft.jpg','QUSRGZR3HP2HV98S','2026-05-02 22:09:00'),(21,14,1,7,'Cyberpunk 2077','./images/cyberpunk2077.jpg','QL8V9FCULWICT136','2026-05-02 23:19:19'),(22,14,1,8,'PlayStation Plus Deluxe 1 mes','./images/productos/Playstationplus.jpg','CJKZZ02SPFLHX7U3','2026-05-02 23:19:19'),(23,14,1,1,'GTA V','./images/gta.jpg','TW0K0OVW8I6E3J18','2026-05-02 23:19:19'),(24,14,1,2,'The Last of Us','./images/tlou.jpg','DTZP6902UQE1BZCI','2026-05-02 23:19:19'),(25,15,3,3,'FIFA 24','./images/fifa24.jpg','IO0ADTXK2YE8Y4H1','2026-05-03 23:05:28'),(26,15,3,20,'Animal Crossing: New Horizons','./images/animal_crossing.jpg','RS08HEC1BIJAG3BT','2026-05-03 23:05:28'),(27,16,3,2,'The Last of Us','./images/tlou.jpg','XDY76EESLDVJ3PWM','2026-05-03 23:06:53'),(28,17,3,8,'PlayStation Plus Deluxe 1 mes','./images/productos/Playstationplus.jpg','5T7TLLSUSACVI3L3','2026-05-03 23:10:48'),(29,18,1,2,'The Last of Us','./images/tlou.jpg','JT6VPVU00I1JJEHT','2026-05-04 04:42:46'),(30,18,1,4,'Minecraft','./images/minecraft.jpg','VT7B3VX07HFK0VFQ','2026-05-04 04:42:46'),(31,19,1,2,'The Last of Us','./images/tlou.jpg','8CR1MHPZP8G2FOBM','2026-05-04 04:51:51'),(32,19,1,2,'The Last of Us','./images/tlou.jpg','C01X6G4TXHFKTK22','2026-05-04 04:51:51'),(33,19,1,2,'The Last of Us','./images/tlou.jpg','9WSO9HCX7XCWIT87','2026-05-04 04:51:51'),(34,20,1,2,'The Last of Us','./images/tlou.jpg','BYBGM0BJ33XISJVL','2026-05-04 04:53:30'),(35,20,1,2,'The Last of Us','./images/tlou.jpg','S7HOKDSROM2KJJY4','2026-05-04 04:53:30'),(36,21,1,1,'GTA V','./images/gta.jpg','CZYC80K8M84ASCC1','2026-05-04 05:40:25'),(37,22,2,2,'The Last of Us','./images/tlou.jpg','BACE9XJB1EKXU23O','2026-05-04 05:41:06'),(38,23,5,2,'The Last of Us','./images/tlou.jpg','BHGXHV4F74RIQMJZ','2026-05-04 06:21:39'),(39,24,2,2,'The Last of Us','./images/tlou.jpg','PHJE46VM88UGCTCP','2026-05-04 06:35:33'),(40,25,6,4,'Minecraft','./images/minecraft.jpg','SAR60XZ3GBT8BYQY','2026-05-04 15:20:04'),(41,26,6,7,'Cyberpunk 2077','./images/cyberpunk2077.jpg','UWZW5LXSQXCDWHCB','2026-05-04 15:32:23'),(42,27,6,1,'GTA V','./images/gta.jpg','JCOOUX5KNGFNAE79','2026-05-04 15:33:05'),(43,28,7,3,'FIFA 24','./images/fifa24.jpg','U52UVK3LT74UUT0R','2026-05-04 16:54:40'),(44,28,7,3,'FIFA 24','./images/fifa24.jpg','AWNSKIJGA7LHDDR0','2026-05-04 16:54:40'),(45,28,7,3,'FIFA 24','./images/fifa24.jpg','02ZJJYFO6W65OPIK','2026-05-04 16:54:40'),(46,29,7,3,'FIFA 24','./images/fifa24.jpg','LICOBOJMUGDCNBOV','2026-05-04 16:57:48'),(47,29,7,3,'FIFA 24','./images/fifa24.jpg','D422KYHRBO7WXVJF','2026-05-04 16:57:48'),(48,29,7,4,'Minecraft','./images/minecraft.jpg','TDEAOE0TEEBT3SWH','2026-05-04 16:57:48'),(49,29,7,4,'Minecraft','./images/minecraft.jpg','1BPVOR1G1V3NB29C','2026-05-04 16:57:48'),(50,29,7,5,'Friday Night Funkin','./images/FridayNightFunkin2.png','0N7XY3GBZ6774DU7','2026-05-04 16:57:48'),(51,30,2,1,'GTA V','./images/gta.jpg','39O8ZAYIRUO00GDX','2026-05-05 22:52:33'),(52,31,8,1,'GTA V','./images/gta.jpg','G7GT2UGA7XK5T6Q8','2026-05-05 23:03:37'),(53,32,8,3,'FIFA 24','./images/fifa24.jpg','BAPV7Y5HETNBCYH7','2026-05-05 23:05:21');
/*!40000 ALTER TABLE `codigos_activacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `combo_relacion`
--

DROP TABLE IF EXISTS `combo_relacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combo_relacion` (
  `id_relacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_combo` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  PRIMARY KEY (`id_relacion`),
  KEY `id_combo` (`id_combo`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `combo_relacion_ibfk_1` FOREIGN KEY (`id_combo`) REFERENCES `combos` (`id_combo`) ON DELETE CASCADE,
  CONSTRAINT `combo_relacion_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `combo_relacion`
--

LOCK TABLES `combo_relacion` WRITE;
/*!40000 ALTER TABLE `combo_relacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `combo_relacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `combos`
--

DROP TABLE IF EXISTS `combos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combos` (
  `id_combo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_combo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `combos`
--

LOCK TABLES `combos` WRITE;
/*!40000 ALTER TABLE `combos` DISABLE KEYS */;
INSERT INTO `combos` VALUES (1,'Halo Infinite + Game Pass 3 Meses','Incluye el juego Halo Infinite más 3 meses de Xbox Game Pass Ultimate.',899.99,'./images/combos/halo_gamepass.png',1),(2,'Mario Oddyssey + Animal Crossing','Paquete que incluye Minecraft base junto con el juego de Animal Crossing.',499.99,'./images/combos/minecraft_animal.png',1),(3,'FIFA 24 + 3 Playstation Plus Deluxe 3 meses','Incluye FIFA 24 más una tarjeta de 3 meses de PlayStation Plus Deluxe.',1199.00,'./images/combos/fifa_combo.png',1);
/*!40000 ALTER TABLE `combos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fidelizacion`
--

DROP TABLE IF EXISTS `fidelizacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fidelizacion` (
  `id_fidelizacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `puntos` int(11) DEFAULT 0,
  `nivel` enum('Bronce','Plata','Oro','Diamante') DEFAULT 'Bronce',
  PRIMARY KEY (`id_fidelizacion`),
  UNIQUE KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fidelizacion_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fidelizacion`
--

LOCK TABLES `fidelizacion` WRITE;
/*!40000 ALTER TABLE `fidelizacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `fidelizacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `notif_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
INSERT INTO `notificaciones` VALUES (1,1,'Pago con tarjeta de $1,699.98 procesado. Ganaste 169 puntos.',0,'2026-05-02 01:49:00'),(2,1,'Pago con tarjeta de $555.99 procesado. Ganaste 55 puntos.',0,'2026-05-02 01:50:35'),(3,2,'Pago con tarjeta de $999.99 procesado. Ganaste 99 puntos.',0,'2026-05-02 02:21:40'),(4,2,'Pago con tarjeta de $1,799.98 procesado. Ganaste 179 puntos.',0,'2026-05-02 05:14:13'),(5,2,'Pago con tarjeta de $1,599.99 procesado. Ganaste 159 puntos.',0,'2026-05-02 07:00:14'),(6,2,'Pago de $1199.98 procesado con PayPal. Ganaste 119 puntos de cashback.',0,'2026-05-02 07:27:36'),(7,2,'Pago de $1099.98 procesado con PayPal. Ganaste 109 puntos de cashback.',0,'2026-05-02 07:28:46'),(8,2,'Pago con tarjeta de $699.99 procesado. Ganaste 69 puntos.',0,'2026-05-02 07:29:39'),(9,2,'Pago con tarjeta de $799.99 procesado. Ganaste 79 puntos.',0,'2026-05-02 07:30:22'),(10,1,'Pago con tarjeta de $2,949.96 procesado. Ganaste 294 puntos.',0,'2026-05-02 17:43:16'),(11,1,'Pago con tarjeta de $1,699.98 procesado. Ganaste 169 puntos.',0,'2026-05-02 17:45:15'),(12,1,'Pago de $1699.98 procesado con PayPal. Ganaste 169 puntos de cashback.',0,'2026-05-02 19:09:09'),(13,1,'Pago de $1299.98 procesado con PayPal. Ganaste 129 puntos de cashback.',0,'2026-05-02 22:09:00'),(14,1,'Pago de $2255.97 procesado con PayPal. Ganaste 225 puntos de cashback.',0,'2026-05-02 23:19:19'),(15,3,'Pago con tarjeta de $1,899.98 procesado. Ganaste 189 puntos.',0,'2026-05-03 23:05:28'),(16,3,'Pago de $539.99 procesado con PayPal. Ganaste 53 puntos de cashback.',0,'2026-05-03 23:06:53'),(17,3,'Pago con tarjeta de $256.00 procesado. Ganaste 25 puntos.',0,'2026-05-03 23:10:48'),(18,1,'Pago de $999.98 procesado con PayPal. Ganaste 99 puntos de cashback.',0,'2026-05-04 04:42:46'),(19,1,'Pago con tarjeta de $2,099.97 procesado. Ganaste 209 puntos.',0,'2026-05-04 04:51:51'),(20,1,'Pago con tarjeta de $1,399.98 procesado. Ganaste 139 puntos.',0,'2026-05-04 04:53:30'),(21,1,'Pago con tarjeta de $499.99 procesado. Ganaste 49 puntos.',0,'2026-05-04 05:40:25'),(22,2,'Pago con tarjeta de $699.99 procesado. Ganaste 69 puntos.',0,'2026-05-04 05:41:06'),(23,5,'Pago con tarjeta de $699.99 procesado. Ganaste 69 puntos.',0,'2026-05-04 06:21:39'),(24,2,'Pago de $699.99 procesado con PayPal. Ganaste 69 puntos de cashback.',0,'2026-05-04 06:35:33'),(25,6,'Pago con tarjeta de $299.99 procesado. Ganaste 29 puntos.',0,'2026-05-04 15:20:04'),(26,6,'Pago de $770.99 procesado con PayPal. Ganaste 77 puntos de cashback.',0,'2026-05-04 15:32:23'),(27,6,'Pago de $499.99 procesado con PayPal. Ganaste 49 puntos de cashback.',0,'2026-05-04 15:33:05'),(28,7,'Pago con tarjeta de $2,999.97 procesado. Ganaste 299 puntos.',0,'2026-05-04 16:54:40'),(29,7,'Pago de $2700.95 procesado con PayPal. Ganaste 270 puntos de cashback.',0,'2026-05-04 16:57:48'),(30,2,'Pago de $499.99 procesado con PayPal. Ganaste 49 puntos de cashback.',0,'2026-05-05 22:52:33'),(31,8,'Pago de $499.99 procesado con PayPal. Ganaste 49 puntos de cashback.',0,'2026-05-05 23:03:37'),(32,8,'Pago de $999.99 procesado con PayPal. Ganaste 99 puntos de cashback.',0,'2026-05-05 23:05:21');
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) DEFAULT NULL,
  `metodo_pago` enum('tarjeta','paypal','transferencia','criptomoneda') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pago`),
  KEY `id_pedido` (`id_pedido`),
  CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pagado','enviado','cancelado') DEFAULT 'pendiente',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pedido`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES (1,1,1699.98,'pagado','2026-05-02 01:49:00'),(2,1,555.99,'pagado','2026-05-02 01:50:35'),(3,2,999.99,'pagado','2026-05-02 02:21:40'),(4,2,1799.98,'pagado','2026-05-02 05:14:13'),(5,2,1599.99,'pagado','2026-05-02 07:00:14'),(6,2,1199.98,'pagado','2026-05-02 07:27:36'),(7,2,1099.98,'pagado','2026-05-02 07:28:46'),(8,2,699.99,'pagado','2026-05-02 07:29:39'),(9,2,799.99,'pagado','2026-05-02 07:30:22'),(10,1,2949.96,'pagado','2026-05-02 17:43:16'),(11,1,1699.98,'pagado','2026-05-02 17:45:15'),(12,1,1699.98,'pagado','2026-05-02 19:09:09'),(13,1,1299.98,'pagado','2026-05-02 22:09:00'),(14,1,2255.97,'pagado','2026-05-02 23:19:19'),(15,3,1899.98,'pagado','2026-05-03 23:05:28'),(16,3,539.99,'pagado','2026-05-03 23:06:53'),(17,3,256.00,'pagado','2026-05-03 23:10:48'),(18,1,999.98,'pagado','2026-05-04 04:42:46'),(19,1,2099.97,'pagado','2026-05-04 04:51:51'),(20,1,1399.98,'pagado','2026-05-04 04:53:30'),(21,1,499.99,'pagado','2026-05-04 05:40:25'),(22,2,699.99,'pagado','2026-05-04 05:41:06'),(23,5,699.99,'pagado','2026-05-04 06:21:39'),(24,2,699.99,'pagado','2026-05-04 06:35:33'),(25,6,299.99,'pagado','2026-05-04 15:20:04'),(26,6,770.99,'pagado','2026-05-04 15:32:23'),(27,6,499.99,'pagado','2026-05-04 15:33:05'),(28,7,2999.97,'pagado','2026-05-04 16:54:40'),(29,7,2700.95,'pagado','2026-05-04 16:57:48'),(30,2,499.99,'pagado','2026-05-05 22:52:33'),(31,8,499.99,'pagado','2026-05-05 23:03:37'),(32,8,999.99,'pagado','2026-05-05 23:05:21');
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plataformas`
--

DROP TABLE IF EXISTS `plataformas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plataformas` (
  `id_plataforma` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `icono` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_plataforma`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plataformas`
--

LOCK TABLES `plataformas` WRITE;
/*!40000 ALTER TABLE `plataformas` DISABLE KEYS */;
INSERT INTO `plataformas` VALUES (1,'Xbox','./images/plataformas/xboxlogo.png'),(2,'PlayStation','./images/plataformas/playstationlogo.png'),(3,'Steam','./images/plataformas/steamlogo.png'),(4,'Nintendo','./images/plataformas/nintendologo.png');
/*!40000 ALTER TABLE `plataformas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto_plataforma`
--

DROP TABLE IF EXISTS `producto_plataforma`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto_plataforma` (
  `id_producto` int(11) NOT NULL,
  `id_plataforma` int(11) NOT NULL,
  PRIMARY KEY (`id_producto`,`id_plataforma`),
  KEY `id_plataforma` (`id_plataforma`),
  CONSTRAINT `producto_plataforma_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `producto_plataforma_ibfk_2` FOREIGN KEY (`id_plataforma`) REFERENCES `plataformas` (`id_plataforma`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_plataforma`
--

LOCK TABLES `producto_plataforma` WRITE;
/*!40000 ALTER TABLE `producto_plataforma` DISABLE KEYS */;
INSERT INTO `producto_plataforma` VALUES (1,1),(2,2),(3,1),(3,2),(3,3),(4,4),(5,1),(5,2),(5,3),(6,1),(7,1),(7,3),(8,2),(9,2),(10,2),(11,1),(12,1),(13,1),(13,3),(14,1),(14,2),(14,3),(14,4),(15,1),(15,2),(15,3),(16,1),(16,2),(16,3),(16,4),(17,2),(17,3),(19,1),(19,2),(19,3),(19,4),(20,4),(21,4),(22,4),(24,1),(24,2),(24,3),(24,4),(25,1),(25,2),(25,3),(26,1),(26,2),(26,3),(27,1),(27,3),(28,1),(28,2),(28,3),(28,4),(29,2),(29,3),(30,1),(30,2),(30,3),(31,1),(32,1),(33,1),(34,2),(35,2),(36,2),(37,4),(38,4);
/*!40000 ALTER TABLE `producto_plataforma` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` enum('videojuego','tarjeta','suscripcion','paquete','DLC','moneda_virtual','accesorio') NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `codigo_digital` tinyint(1) DEFAULT 1,
  `fecha_alta` timestamp NOT NULL DEFAULT current_timestamp(),
  `imagen` varchar(255) DEFAULT NULL,
  `es_suscripcion` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,'GTA V','Juego de mundo abierto de Rockstar Games','videojuego',499.99,0,1,'2025-09-24 07:42:09','./images/gta.jpg',0),(2,'The Last of Us','Aventura y supervivencia','videojuego',699.99,0,1,'2025-09-24 07:42:09','./images/tlou.jpg',0),(3,'FIFA 24','Simulador de fútbol de EA Sports','videojuego',999.99,0,1,'2025-09-24 07:42:09','./images/fifa24.jpg',0),(4,'Minecraft','Juego de construcción y aventura','videojuego',299.99,0,1,'2025-09-24 07:42:09','./images/minecraft.jpg',0),(5,'Friday Night Funkin','Impresiona a tu novia enfrentando a distintos enemigos en este juego ritmico.','videojuego',399.99,50,1,'2025-09-24 22:34:09','./images/FridayNightFunkin2.png',0),(6,'Xbox Game Pass Ultimate 1 mes','Acceso ilimitado a juegos de Xbox y PC por 1 mes','suscripcion',199.00,50,1,'2025-09-25 01:00:22','./images/productos/GamepassLogo.png',0),(7,'Cyberpunk 2077','Explora Night City en este RPG futurista lleno de aventuras cyberpunk.','videojuego',799.99,50,1,'2025-09-25 01:18:53','./images/cyberpunk2077.jpg',0),(8,'PlayStation Plus Deluxe 1 mes','Acceso a online y juegos exclusivamente gratis por 1 mes','suscripcion',256.00,100,1,'2025-09-25 01:10:41','./images/productos/Playstationplus.jpg',0),(9,'PlayStation Plus Deluxe 3 meses','Acceso a online y juegos exclusivamente gratis por 3 mes','suscripcion',386.00,100,1,'2025-09-25 01:12:09','./images/productos/Playstationplus.jpg',0),(10,'PlayStation Plus Deluxe 1 año','Acceso a online y juegos exclusivamente gratis por 1 año','suscripcion',2300.00,100,1,'2025-09-25 01:13:31','./images/productos/Playstationplus.jpg',0),(11,'Xbox Game Pass Ultimate 3 meses','Acceso ilimitado a juegos de Xbox y PC por 3 meses','suscripcion',256.00,100,1,'2025-09-25 01:14:47','./images/productos/GamepassLogo.png',0),(12,'Xbox Game Pass Ultimate 1 año','Acceso ilimitado a juegos de Xbox y PC por 1 año','suscripcion',1500.00,100,1,'2025-09-25 01:15:39','./images/productos/GamepassLogo.png',0),(13,'Control Inalámbrico Xbox Series X|S','Nuevo control Xbox con mejor ergonomía y textura antideslizante, color Rojo con Negro','accesorio',1800.00,15,0,'2025-09-25 01:18:30','./images/productos/Xbox_controller.png',0),(14,'Audífonos Gamer Sonido Envolvente 7.1 Conexión USB Assault | VORTRED','Audifonos para cualquier tipo de consola o computadora con entrada USB','accesorio',800.00,100,1,'2025-09-25 01:33:40','./images/productos/AudifonosVortex.png',0),(15,'Mouse Gamer Redragon M715Rgb-1 Dagger 2 Negro Rgb 8 Botones 10000 Dpi','Mouse para cualquier tipo de computadora o dispositivo capaz de soportar mouse','accesorio',660.00,100,1,'2025-09-25 01:35:33','./images/productos/MouseGamer.png',0),(16,'Numskull Official Sonic the Hedgehog Gaming Locker y Estante','Estante del famoso videojuego Sonic the Hedgehog, soporta hasta 4 controles y headsets','accesorio',500.00,100,1,'2025-09-25 01:45:28','./images/productos/Sonicestante.png',0),(17,'God of War','Acompaña a Kratos y Atreus en su viaje por la mitología nórdica.','videojuego',799.99,50,1,'2025-09-25 01:18:53','./images/god_of_war.jpg',0),(19,'Mortal Kombat 11','El juego de lucha más brutal con fatalities espectaculares.','videojuego',549.99,50,1,'2025-09-25 01:18:53','./images/mk11.jpg',0),(20,'Animal Crossing: New Horizons','Crea tu paraíso personal en una isla tropical.','videojuego',899.99,50,1,'2025-09-25 01:18:53','./images/animal_crossing.jpg',0),(21,'Super Mario Odyssey','Únete a Mario en una aventura 3D por todo el mundo.','videojuego',849.99,50,1,'2025-09-25 01:18:53','./images/mario_odyssey.jpg',0),(22,'The Legend of Zelda: Breath of the Wild','Explora Hyrule en la aventura más épica de Link.','videojuego',899.99,50,1,'2025-09-25 01:18:53','./images/zelda_botw.jpg',0),(24,'Doom Eternal','Shooter frenético contra demonios del infierno.','videojuego',449.99,50,1,'2025-09-25 01:18:53','./images/doom_eternal.jpg',0),(25,'Resident Evil 4','Horror y supervivencia en su máxima expresión.','videojuego',699.99,50,1,'2025-09-25 01:18:53','./images/re4.jpg',0),(26,'Street Fighter 6','El fighting game más técnico con combos espectaculares.','videojuego',899.99,50,1,'2025-09-25 01:18:53','./images/sf6.jpg',0),(27,'Elden Ring','RPG de mundo abierto de los creadores de Dark Souls.','videojuego',999.99,50,1,'2025-09-25 01:18:53','./images/elden_ring.jpg',0),(28,'Hogwarts Legacy','Vive tu propia aventura mágica en el mundo de Harry Potter.','videojuego',1099.99,50,1,'2025-09-25 01:18:53','./images/hogwarts_legacy.jpg',0),(29,'Stray','Aventura como un gato callejero en una ciudad cyberpunk.','videojuego',449.99,50,1,'2025-09-25 01:18:53','./images/stray.jpg',0),(30,'It Takes Two','Aventura cooperativa que debes jugar en pareja.','videojuego',599.99,50,1,'2025-09-25 01:18:53','./images/it_takes_two.jpg',0),(31,'Xbox Game Pass Essential','Más de 50 juegos en la consola Xbox, PC y dispositivos compatibles. Multijugador en línea.','videojuego',129.00,0,1,'2026-05-08 04:47:06',NULL,1),(32,'Xbox Game Pass Premium','Más de 200 juegos en la consola Xbox, PC y dispositivos. Multijugador en línea.','videojuego',179.00,0,1,'2026-05-08 04:47:06',NULL,1),(33,'Xbox Game Pass Ultimate','Más de 400 juegos desde el día de lanzamiento. EA Play, Ubisoft+ Classics, cloud gaming.','videojuego',289.00,0,1,'2026-05-08 04:47:06',NULL,1),(34,'PS Plus Essential','Juegos mensuales, multijugador online y descuentos exclusivos en PlayStation Store.','videojuego',119.00,0,1,'2026-05-08 04:47:06',NULL,1),(35,'PS Plus Extra','Todo lo de Essential + catálogo de cientos de juegos PS4/PS5 + Ubisoft+.','videojuego',179.00,0,1,'2026-05-08 04:47:06',NULL,1),(36,'PS Plus Deluxe','Todo lo de Extra + catálogo clásico PS1/PS2/PSP + streaming + pruebas de tiempo limitado.','videojuego',209.00,0,1,'2026-05-08 04:47:06',NULL,1),(37,'Nintendo Switch Online','Multijugador online, NES + SNES clásicos, almacenamiento en la nube y ofertas exclusivas.','videojuego',309.00,0,1,'2026-05-08 04:47:06',NULL,1),(38,'Nintendo Switch Online + Paquete Expansión','Todo el plan básico + juegos clásicos (N64, Genesis, GBA, GBC) + DLC gratuito.','videojuego',859.00,0,1,'2026-05-08 04:47:06',NULL,1);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registro_intentos`
--

DROP TABLE IF EXISTS `registro_intentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registro_intentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL COMMENT 'IPv4 o IPv6',
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ip_creado` (`ip`,`creado`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registro_intentos`
--

LOCK TABLES `registro_intentos` WRITE;
/*!40000 ALTER TABLE `registro_intentos` DISABLE KEYS */;
INSERT INTO `registro_intentos` VALUES (5,'::1','2026-05-05 23:02:43');
/*!40000 ALTER TABLE `registro_intentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes_devolucion`
--

DROP TABLE IF EXISTS `solicitudes_devolucion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes_devolucion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `productos` text NOT NULL COMMENT 'JSON array con los nombres de productos a devolver',
  `estado` enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pedido` (`id_pedido`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_devolucion`
--

LOCK TABLES `solicitudes_devolucion` WRITE;
/*!40000 ALTER TABLE `solicitudes_devolucion` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitudes_devolucion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tarjetas_guardadas`
--

DROP TABLE IF EXISTS `tarjetas_guardadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarjetas_guardadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `token` char(64) NOT NULL COMMENT 'Token único de la tarjeta',
  `ultimos4` char(4) NOT NULL COMMENT 'Últimos 4 dígitos',
  `marca` varchar(20) DEFAULT 'Desconocida' COMMENT 'Visa / Mastercard / etc.',
  `expiry` char(5) NOT NULL COMMENT 'MM/AA',
  `alias` varchar(60) DEFAULT NULL,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `tarjetas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tarjetas_guardadas`
--

LOCK TABLES `tarjetas_guardadas` WRITE;
/*!40000 ALTER TABLE `tarjetas_guardadas` DISABLE KEYS */;
INSERT INTO `tarjetas_guardadas` VALUES (1,1,'8aa3231f9dddd591fb2396e18df9772bd11fe4aeb6c140bbac619fc86d093fd8','1234','Desconocida','11/27',NULL,'2026-05-02 01:49:00'),(2,1,'7ef217d516e81c7c0f5a884d6b19f4a9bc1085da7bfe4c5c9aca9371253a5c94','3455','Desconocida','04/30',NULL,'2026-05-02 01:50:35'),(3,2,'d380a7c97268b221d7682116781100d5fc42771145e9bc5790fe0b5bbb5047b9','9569','Visa','12/34',NULL,'2026-05-02 02:21:40'),(4,2,'54bde324ec40886cbb328a7d197950cc97d68ab8eaee5e569e8a29d2321b1324','6566','Desconocida','04/29',NULL,'2026-05-02 07:29:39'),(5,1,'79cbc7d30da199a08d21432efee7323d3e9ff54b495abe48f8ee461e68d9dca6','6756','Amex','04/34',NULL,'2026-05-02 17:43:16'),(6,3,'9e0edcf88b2cdbc71937fb5e1dfb1ff71f834028af422b6135ee3ca301217a32','4567','Visa','11/34',NULL,'2026-05-03 23:05:28'),(7,5,'684ea5e46bbde3689fa6fdaec46f156a1daf5aad6b04ab1827966c737715fc68','4654','Mastercard','04/33',NULL,'2026-05-04 06:21:39'),(8,6,'4189b713682ccf97696904bfbd1f0034b337a8be7d4bbd4a93111424e2e3bba7','3677','Mastercard','06/32',NULL,'2026-05-04 15:20:04'),(9,7,'ea29d1c90721c7954c06baaf7c123d69ef3c9eb767679d59b96f912817bf3a1d','6577','Mastercard','09/34',NULL,'2026-05-04 16:54:40');
/*!40000 ALTER TABLE `tarjetas_guardadas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `rol` enum('cliente','admin') DEFAULT 'cliente',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `puntos` int(11) NOT NULL DEFAULT 0,
  `ultima_recompensa` date DEFAULT NULL,
  `puntos_reset_mes` varchar(7) DEFAULT NULL COMMENT 'YYYY-MM del último reset mensual de puntos',
  `razon_social` varchar(150) DEFAULT NULL COMMENT 'Razón social para facturación',
  `racha_recompensa` int(11) NOT NULL DEFAULT 0 COMMENT 'Número de día consecutivo actual (1-7), se resetea al completar el ciclo',
  `verificado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = pendiente verificación, 1 = cuenta verificada',
  `verification_code` varchar(8) DEFAULT NULL COMMENT 'Código OTP de 6 chars (se borra tras verificar)',
  `verification_expires` datetime DEFAULT NULL COMMENT 'Expiración del código (15 minutos desde generación)',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin',NULL,'admin@outlook.com','$2y$12$l3jfCkdFjGhDmqKMa5L56.8YpRQslcpWdnQtd2ER5wvkOXwgtSj/q',NULL,NULL,'cliente','2026-05-01 23:40:01',1706,'2026-05-02','2026-05',NULL,0,1,NULL,NULL),(2,'alonsoria30',NULL,'alonsoria@outlook.com','$2y$12$ZAOVHHft2Fr/4CIxAC6QI.wZ/5W1O.3IFaq/D3YklSV6Fbn7ESocG',NULL,NULL,'cliente','2026-05-02 02:18:16',450,'2026-05-02','2026-05',NULL,0,1,NULL,NULL),(3,'zero',NULL,'tungtungyahir@outlook.com','$2y$12$Sxt7Q2mAXuXkzA/6k4yycuBmnyZRTWegws4TQdWWZXn/EAiW6VUPi',NULL,NULL,'cliente','2026-05-03 23:03:03',107,'2026-05-03','2026-05',NULL,1,1,NULL,NULL),(4,'Alonsoria',NULL,'a27177@halcones.ual.mx','$2y$12$lh9v.Tv4xyBtmWS/tDvji.YtZ2VsokklPmiM1P/CFMo4v7NGHs65i',NULL,NULL,'cliente','2026-05-04 05:38:08',0,NULL,NULL,NULL,0,0,'8VXS3Z','2026-05-04 07:54:46'),(5,'Alonsoria',NULL,'pau@outlook.com','$2y$12$jPKTQFPGGkyWMK.WEV09ruz36FLnr7AZCcNkuQH6f7U65Dn3Vl3EG',NULL,NULL,'cliente','2026-05-04 06:18:38',69,NULL,'2026-05',NULL,0,1,NULL,NULL),(6,'walkingnewdiego','diego landeros','diego@hotmail.com','$2y$12$7kOJHJW79kxaq6G.v97.S.wliqWa6Bn8vsJPEWDR055FSHabjpQby','871129984','ampl los angeles','cliente','2026-05-04 15:11:45',126,'2026-05-04','2026-05','persona no moral',1,1,NULL,NULL),(7,'MONA',NULL,'mona@gmail.com','$2y$12$XuqUu7NrYwfykQLj1t7YceM1WL3B.14n8bY2cdYa5tDMN8KFs.MZG',NULL,NULL,'cliente','2026-05-04 16:49:00',270,'2026-05-04','2026-05',NULL,1,1,NULL,NULL),(8,'kreikgamer',NULL,'angryangelito@gmail.com','$2y$12$jN3WKUpVRM3anvz/t3dUT.PJvwDq6s9LoC2ruJcIJR9Qnu.CceVzq',NULL,NULL,'cliente','2026-05-05 23:02:43',148,NULL,'2026-05',NULL,0,1,NULL,NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-07 22:58:09
