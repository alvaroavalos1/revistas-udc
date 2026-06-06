-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: plataforma_revistas
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accesos_log`
--

DROP TABLE IF EXISTS `accesos_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accesos_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `exitoso` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_accion` (`ip`,`accion`,`creado_en`),
  KEY `idx_usuario` (`usuario_id`),
  CONSTRAINT `accesos_log_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accesos_log`
--

LOCK TABLES `accesos_log` WRITE;
/*!40000 ALTER TABLE `accesos_log` DISABLE KEYS */;
INSERT INTO `accesos_log` VALUES (1,1,'admin@revista.com','::1','login_exitoso',1,'2026-06-06 00:31:25');
/*!40000 ALTER TABLE `accesos_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_es` varchar(100) NOT NULL,
  `nombre_en` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `descripcion_es` text DEFAULT NULL,
  `descripcion_en` text DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_activa` (`activa`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Ciencias de la Salud','Health Sciences','ciencias-de-la-salud','Facultades e instalaciones enfocadas en el bienestar humano y animal.','Faculties and facilities focused on human and animal well-being.',1,'2026-06-06 00:35:36'),(2,'Ingenierías y Tecnología','Engineering and Technology','ingenierias-y-tecnologia','Facultades dedicadas al desarrollo técnico, la infraestructura y las ciencias computacionales.','Faculties dedicated to technical development, infrastructure and computer sciences.',1,'2026-06-06 00:35:36'),(3,'Ciencias Marítimas y del Mar','Maritime and Ocean Sciences','ciencias-maritimas-y-del-mar','Especializada en el entorno costero, el comercio internacional y los recursos biológicos del océano.','Specialized in the coastal environment, international trade and biological ocean resources.',1,'2026-06-06 00:35:36'),(4,'Ciencias Sociales y Derecho','Social Sciences and Law','ciencias-sociales-y-derecho','Archivos sobre las facultades que analizan la estructura legal, política, económica y social.','Files on faculties that analyze legal, political, economic and social structures.',1,'2026-06-06 00:35:36'),(5,'Humanidades, Artes y Educación','Humanities, Arts and Education','humanidades-artes-y-educacion','Facultades que preservan, estudian y transmiten la cultura, las letras y la enseñanza.','Faculties that preserve, study and transmit culture, literature and teaching.',1,'2026-06-06 00:35:36'),(6,'Ciencias Exactas y Naturales','Exact and Natural Sciences','ciencias-exactas-y-naturales','Investigación pura, desarrollo matemático e investigación de campo agropecuaria.','Pure research, mathematical development and agricultural field research.',1,'2026-06-06 00:35:36'),(7,'Administración y Negocios','Business Administration','administracion-y-negocios','Facultades que mueven el ecosistema económico, fiscal y de gestión corporativa.','Faculties that drive the economic, fiscal and corporate management ecosystem.',1,'2026-06-06 00:35:36'),(8,'Institutos de Investigación Especializada','Specialized Research Institutes','institutos-de-investigacion-especializada','Centros de investigación científica avanzada con alto impacto en el desarrollo y seguridad del país.','Advanced scientific research centers with high impact on national development and security.',1,'2026-06-06 00:35:36'),(9,'Coordinaciones y Servicios Académicos','Academic Services and Coordination','coordinaciones-y-servicios-academicos','Sistemas operativos de gestión de información, tecnologías educativas e infraestructura de red institucional.','Information management systems, educational technologies and institutional network infrastructure.',1,'2026-06-06 00:35:36'),(10,'Gobierno Universitario y Normatividad','University Government and Regulations','gobierno-universitario-y-normatividad','Estructura legal, gobernanza democrática y reglamentación interna de la autonomía universitaria.','Legal structure, democratic governance and internal regulations of university autonomy.',1,'2026-06-06 00:35:36');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revistas`
--

DROP TABLE IF EXISTS `revistas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revistas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `categoria_id` int(10) unsigned NOT NULL,
  `subida_por` int(10) unsigned NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `portada_url` varchar(500) DEFAULT NULL,
  `pdf_url` varchar(500) DEFAULT NULL,
  `estado` enum('borrador','publicada','archivada') NOT NULL DEFAULT 'borrador',
  `publicada_en` timestamp NULL DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `visitas` int(10) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `subida_por` (`subida_por`),
  KEY `idx_categoria` (`categoria_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_publicada` (`publicada_en`),
  CONSTRAINT `revistas_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  CONSTRAINT `revistas_ibfk_2` FOREIGN KEY (`subida_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revistas`
--

LOCK TABLES `revistas` WRITE;
/*!40000 ALTER TABLE `revistas` DISABLE KEYS */;
INSERT INTO `revistas` VALUES (1,1,1,'Facultad de Medicina — Plan Clínico','Estructura formal de los ciclos de rotación hospitalaria, internado de pregrado y laboratorios de simulación clínica avanzada de alta fidelidad.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(2,1,1,'Facultad de Enfermería — Práctica Comunitaria','Modelos de intervención en salud preventiva, diagnóstico social y brigadas de atención integral en comunidades rurales e indígenas del estado.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(3,1,1,'Facultad de Psicología — Clínicas y Atención','Esquema operativo y de supervisión de los centros de atención comunitaria abiertos al público para terapia, diagnóstico y orientación psicológica.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(4,1,1,'Veterinaria y Zootecnia Tecomán','Operación técnica de los hospitales de pequeñas y grandes especies, así como los establos y granjas didácticas del campus Tecomán.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(5,2,1,'FIME Coquimatlán — Talleres Pesados','Inventario y metodologías didácticas aplicadas en los laboratorios de termo-fluidos, manufactura asistida por computadora (CNC) y subestaciones eléctricas.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(6,2,1,'Facultad de Telemática — Desarrollo de Software','Proyectos de ingeniería de software, seguridad de la información, gestión de redes convergentes y despliegue de soluciones en la nube.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(7,2,1,'Facultad de Ciencias Químicas — Investigación','Líneas de investigación en ingeniería de procesos, análisis de metales pesados, control de calidad alimentaria y metalurgia extractiva.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(8,2,1,'Ingeniería Electromecánica Manzanillo','Enfoque de la ingeniería electromecánica orientada a grúas portuarias pórtico, sistemas de refrigeración industrial y plantas de cogeneración.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(9,3,1,'Facultad de Ciencias Marinas — Oceanografía','Protocolos de muestreo planctónico, dinámica costera, corrientes de marea e investigaciones biológicas en el litoral colimense.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(10,3,1,'Comercio Exterior y Aduanas — Logística','Plan curricular conectado directamente a la legislación aduanera mexicana, tramitología de comercio internacional y cadena de suministro portuaria.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(11,3,1,'Infraestructura de Buques y Muestreo','Especificaciones y equipamiento de las estaciones de monitoreo costero, lanchas y laboratorios húmedos para la investigación marina.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(12,4,1,'Facultad de Derecho — Bufetes Gratuitos','Estructura organizativa de las clínicas jurídicas y los bufetes de asistencia social donde los estudiantes manejan juicios reales orales y civiles.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(13,4,1,'Facultad de Economía — Análisis de Mercados','Modelos predictivos de canastas básicas, análisis microeconómico y el impacto financiero del puerto en el Producto Interno Bruto estatal.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(14,4,1,'Facultad de Ciencias Políticas y Sociales','Estudios de opinión pública, comportamiento electoral regional, gobernanza municipal y dinámicas de participación ciudadana en el occidente.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(15,5,1,'FALCOM — Letras y Periodismo','Agencias informativas internas, producción radiofónica y televisiva universitaria, laboratorios editoriales y talleres de creación literaria.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(16,5,1,'Facultad de Arquitectura y Diseño — Talleres','Planes de ordenamiento urbano sustentable, talleres de modelado volumétrico tridimensional y diseño de productos industriales con materiales locales.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(17,5,1,'Escuela de Artes — Música y Danza','Planes curriculares avanzados en ejecución instrumental de concierto, artes plásticas y el rigor de la danza escénica contemporánea y folklórica.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(18,6,1,'Facultad de Ciencias — Física y Matemáticas','Líneas de investigación en física de partículas, mecánica estadística y la formación de científicos puros integrados a redes globales.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(19,6,1,'Ciencias Biológicas y Agropecuarias Tecomán','Manejo integrado de plagas fitosanitarias (HLB en cítricos), biotecnología reproductiva bovina y optimización del suelo agrícola regional.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(20,7,1,'FCA Colima — Contabilidad y Administración','Planes académicos corporativos: auditoría fiscal, consultoría empresarial, finanzas corporativas mundiales y gestión del capital humano.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(21,7,1,'FCA Manzanillo — Gestión y Negocios','Especialización en administración turística y hotelera, regímenes fiscales aduaneros e intermediación de negocios en mercados asiáticos.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(22,8,1,'Centro Universitario de Investigaciones Vulcanológicas','Red de monitoreo sísmico y telemétrico del Volcán de Fuego de Colima, análisis geoquímico de gases y generación de mapas de riesgo civil.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(23,8,1,'Centro de Investigaciones Sociales — CUIS','Proyectos multidisciplinarios sobre dinámicas migratorias de retorno, estudios de género, equidad y desarrollo urbano sustentable.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(24,9,1,'Red de Bibliotecas y Acceso al Conocimiento','Arquitectura tecnológica del software SIABUC (creado por la UdeC) y catálogos de indexación para el acceso a bases de datos científicas globales.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(25,9,1,'Coordinación General de Tecnologías de Información','Administración de la red dorsal de fibra óptica universitaria, la plataforma virtual EDUC y los servidores de supercómputo.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(26,10,1,'Ley Orgánica y Estatuto General UdeC','Análisis normativo de la autonomía universitaria otorgada por el congreso, facultades del Ejecutivo y derechos y obligaciones de la comunidad.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0),(27,10,1,'Consejo Universitario — Máximo Órgano de Gobierno','Composición paritaria del Máximo Órgano de Gobierno: representatividad de directores, profesores y alumnos electos democráticamente.',NULL,NULL,'publicada','2026-06-06 00:35:36','2026-06-06 00:35:36','2026-06-06 00:35:36',0);
/*!40000 ALTER TABLE `revistas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revistas_en`
--

DROP TABLE IF EXISTS `revistas_en`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revistas_en` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `revista_id` int(10) unsigned NOT NULL,
  `subida_por` int(10) unsigned NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `portada_url` varchar(500) DEFAULT NULL,
  `pdf_url` varchar(500) DEFAULT NULL,
  `estado` enum('borrador','publicada','archivada') NOT NULL DEFAULT 'borrador',
  `publicada_en` timestamp NULL DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `visitas` int(10) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `revista_id` (`revista_id`),
  KEY `subida_por` (`subida_por`),
  KEY `idx_revista` (`revista_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `revistas_en_ibfk_1` FOREIGN KEY (`revista_id`) REFERENCES `revistas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `revistas_en_ibfk_2` FOREIGN KEY (`subida_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revistas_en`
--

LOCK TABLES `revistas_en` WRITE;
/*!40000 ALTER TABLE `revistas_en` DISABLE KEYS */;
/*!40000 ALTER TABLE `revistas_en` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ui_textos`
--

DROP TABLE IF EXISTS `ui_textos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ui_textos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `texto_es` varchar(500) NOT NULL,
  `texto_en` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ui_textos`
--

LOCK TABLES `ui_textos` WRITE;
/*!40000 ALTER TABLE `ui_textos` DISABLE KEYS */;
INSERT INTO `ui_textos` VALUES (1,'nav.catalogo','Catálogo','Catalogue'),(2,'nav.revistas','Revistas','Magazines'),(3,'nav.categorias','Categorías','Categories'),(4,'nav.usuarios','Usuarios','Users'),(5,'nav.idioma','Idioma','Language'),(6,'nav.config','Configuración','Settings'),(7,'btn.nueva','Nueva revista','New magazine'),(8,'btn.editar','Editar','Edit'),(9,'btn.eliminar','Eliminar','Delete'),(10,'btn.publicar','Publicar','Publish'),(11,'btn.descargar','Descargar PDF','Download PDF'),(12,'btn.ver_pdf','Ver revista','Read magazine'),(13,'estado.publicada','Publicada','Published'),(14,'estado.borrador','Borrador','Draft'),(15,'estado.archivada','Archivada','Archived'),(16,'label.buscar','Buscar revista...','Search magazine...'),(17,'label.bienvenida','Bienvenido','Welcome'),(18,'label.sin_revista','Sin revistas aún','No magazines yet');
/*!40000 ALTER TABLE `ui_textos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','editor') NOT NULL DEFAULT 'editor',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por` int(10) unsigned DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `creado_por` (`creado_por`),
  KEY `idx_email` (`email`),
  KEY `idx_rol` (`rol`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','admin@revista.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',1,NULL,'2026-05-14 20:34:44','2026-05-14 20:34:44'),(2,'Editor Uno','editor1@revista.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','editor',1,1,'2026-05-14 20:34:44','2026-05-19 23:39:58'),(3,'Editor Dos','editor2@revista.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','editor',1,1,'2026-05-14 20:34:44','2026-05-19 23:39:55');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'plataforma_revistas'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-05 18:39:28
