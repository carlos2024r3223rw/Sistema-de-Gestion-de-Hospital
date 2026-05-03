
CREATE DATABASE /*!32312 IF NOT EXISTS*/`hospital` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `hospital`;

/*Table structure for table `cita` */

DROP TABLE IF EXISTS `cita`;

CREATE TABLE `cita` (
  `codigocita` int NOT NULL AUTO_INCREMENT,
  `codigopaciente` int NOT NULL,
  `codigoempleado` int NOT NULL,
  `fecha_cita` date NOT NULL,
  `hora_cita` time NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','completada','cancelada') DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`codigocita`),
  KEY `codigopaciente` (`codigopaciente`),
  KEY `codigoempleado` (`codigoempleado`),
  CONSTRAINT `cita_ibfk_1` FOREIGN KEY (`codigopaciente`) REFERENCES `paciente` (`codigopaciente`) ON DELETE CASCADE,
  CONSTRAINT `cita_ibfk_2` FOREIGN KEY (`codigoempleado`) REFERENCES `empleado` (`codigoempleado`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `cita` */

insert  into `cita`(`codigocita`,`codigopaciente`,`codigoempleado`,`fecha_cita`,`hora_cita`,`motivo`,`estado`,`created_at`) values 
(1,1,2,'2026-07-14','08:50:00','Chequeo rutinario | Diag: No presenta ningun problema','completada','2026-05-02 23:00:31'),
(2,1,2,'2026-09-04','09:00:00','Seguimiento: No presenta ningun problema','pendiente','2026-05-02 23:34:09');

/*Table structure for table `consulta` */

DROP TABLE IF EXISTS `consulta`;

CREATE TABLE `consulta` (
  `codigoconsulta` int NOT NULL AUTO_INCREMENT,
  `codigopaciente` int NOT NULL,
  `codigoempleado` int NOT NULL,
  `fecha_consulta` datetime NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sintomas` text COLLATE utf8mb4_general_ci,
  `diagnostico` text COLLATE utf8mb4_general_ci,
  `receta` text COLLATE utf8mb4_general_ci,
  `observaciones` text COLLATE utf8mb4_general_ci,
  `presion_arterial` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `temperatura` decimal(4,2) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `altura` decimal(5,2) DEFAULT NULL,
  `costo` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`codigoconsulta`),
  KEY `codigoempleado` (`codigoempleado`),
  KEY `idx_fecha` (`fecha_consulta`),
  KEY `idx_paciente` (`codigopaciente`),
  CONSTRAINT `consulta_ibfk_1` FOREIGN KEY (`codigopaciente`) REFERENCES `paciente` (`codigopaciente`) ON DELETE CASCADE,
  CONSTRAINT `consulta_ibfk_2` FOREIGN KEY (`codigoempleado`) REFERENCES `empleado` (`codigoempleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `consulta` */

/*Table structure for table `empleado` */

DROP TABLE IF EXISTS `empleado`;

CREATE TABLE `empleado` (
  `codigoempleado` int NOT NULL AUTO_INCREMENT,
  `nombreempleado` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `correoempleado` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `clave` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `rol` enum('admin','doctor','enfermera','recepcionista') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'recepcionista',
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `especialidad` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_contratacion` date DEFAULT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_general_ci DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`codigoempleado`),
  UNIQUE KEY `correoempleado` (`correoempleado`),
  KEY `idx_correo` (`correoempleado`),
  KEY `idx_rol` (`rol`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `empleado` */

insert  into `empleado`(`codigoempleado`,`nombreempleado`,`correoempleado`,`clave`,`rol`,`telefono`,`especialidad`,`fecha_contratacion`,`estado`,`created_at`,`updated_at`) values 
(1,'Dr. Carlos Martínez','carlos.martinez@hospital.com','$2y$10$iwauguh4eZnRhQT3eJmX8Ojrs1CXHypxLPrFwq6m1Sja8heYDL28G','admin','7777-1234','Administración','2020-01-15','activo','2025-11-06 18:39:32','2025-11-06 18:54:12'),
(2,'Dra. María González','maria.gonzalez@hospital.com','$2y$10$b5AKyHh9FVBTqeYdjW8D4./f.ou5OuU0/7CqZ7dtc3JKooJ4mOSSW','doctor','7777-5678','Cardiología','2021-03-20','activo','2025-11-06 18:39:32','2025-11-09 22:03:08'),
(3,'Dr. Roberto Pérez','roberto.perez@hospital.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','doctor','7777-9012','Pediatría','2021-06-10','activo','2025-11-06 18:39:32','2025-11-06 18:39:32'),
(4,'Enf. Ana López','ana.lopez@hospital.com','$2y$10$iwauguh4eZnRhQT3eJmX8Ojrs1CXHypxLPrFwq6m1Sja8heYDL28G','enfermera','7777-3456','Cuidados Generales','2022-01-05','activo','2025-11-06 18:39:32','2025-11-09 21:53:19'),
(5,'Enf. Luis Hernández','luis.hernandez@hospital.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','enfermera','7777-7890','UCI','2022-04-15','activo','2025-11-06 18:39:32','2026-05-02 22:39:42'),
(6,'Carmen Flores','carmen.flores@hospital.com','$2y$10$ItohV3s3g9V8BcNusWI30eR8T5XZAp1Tr4hcfNKPPb/89xVD9J5Ky','recepcionista','7777-2345',NULL,'2023-02-01','activo','2025-11-06 18:39:32','2026-05-02 23:29:13');

/*Table structure for table `enfermedad` */

DROP TABLE IF EXISTS `enfermedad`;

CREATE TABLE `enfermedad` (
  `codigoenfermedad` int NOT NULL AUTO_INCREMENT,
  `nombreenfermedad` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `tipo` enum('infecciosa','cronica','aguda','degenerativa','mental','otra') COLLATE utf8mb4_general_ci DEFAULT 'otra',
  `nivel_gravedad` enum('leve','moderada','grave','critica') COLLATE utf8mb4_general_ci DEFAULT 'leve',
  `tratamiento_recomendado` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`codigoenfermedad`),
  KEY `idx_nombre` (`nombreenfermedad`),
  KEY `idx_tipo` (`tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `enfermedad` */

insert  into `enfermedad`(`codigoenfermedad`,`nombreenfermedad`,`descripcion`,`tipo`,`nivel_gravedad`,`tratamiento_recomendado`,`created_at`) values 
(1,'Hipertensión Arterial','Presión arterial elevada de forma crónica','cronica','moderada','Control de presión, dieta baja en sodio, medicación antihipertensiva','2025-11-06 18:39:32'),
(2,'Diabetes Mellitus Tipo 2','Trastorno metabólico caracterizado por hiperglucemia','cronica','moderada','Control de glucosa, dieta, ejercicio, medicación','2025-11-06 18:39:32'),
(3,'Neumonía','Infección pulmonar aguda','infecciosa','grave','Antibióticos, reposo, hidratación','2025-11-06 18:39:32'),
(4,'Gastroenteritis Aguda','Inflamación del tracto gastrointestinal','infecciosa','leve','Hidratación, dieta blanda, probióticos','2025-11-06 18:39:32'),
(5,'Asma Bronquial','Enfermedad inflamatoria crónica de las vías respiratorias','cronica','moderada','Broncodilatadores, corticoides inhalados, evitar desencadenantes','2025-11-06 18:39:32'),
(6,'Infarto Agudo de Miocardio','Muerte del músculo cardíaco por falta de flujo sanguíneo','aguda','critica','Atención médica inmediata, trombolíticos, angioplastia','2025-11-06 18:39:32'),
(7,'COVID-19','Enfermedad infecciosa causada por coronavirus SARS-CoV-2','infecciosa','moderada','Aislamiento, antivirales, oxigenoterapia si es necesario','2025-11-06 18:39:32'),
(8,'Gripe Estacional','Infección viral respiratoria común.','infecciosa','leve','Reposo, hidratación y paracetamol.','2026-05-02 22:58:15'),
(9,'Insuficiencia Renal','Pérdida de la capacidad de los riñones para filtrar.','cronica','critica','Diálisis o trasplante renal.','2026-05-02 22:58:15');

/*Table structure for table `factura` */

DROP TABLE IF EXISTS `factura`;

CREATE TABLE `factura` (
  `codigofactura` int NOT NULL AUTO_INCREMENT,
  `codigopaciente` int NOT NULL,
  `codigocita` int DEFAULT NULL,
  `codigohospitalizacion` int DEFAULT NULL,
  `monto_servicios` decimal(10,2) DEFAULT '0.00',
  `monto_medicinas` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pagada') DEFAULT 'pendiente',
  `metodo_pago` enum('efectivo','tarjeta','transferencia','seguro') DEFAULT NULL,
  `fecha_emision` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`codigofactura`),
  KEY `codigopaciente` (`codigopaciente`),
  CONSTRAINT `factura_ibfk_1` FOREIGN KEY (`codigopaciente`) REFERENCES `paciente` (`codigopaciente`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `factura` */

insert  into `factura`(`codigofactura`,`codigopaciente`,`codigocita`,`codigohospitalizacion`,`monto_servicios`,`monto_medicinas`,`total`,`estado`,`metodo_pago`,`fecha_emision`) values 
(1,1,1,NULL,25.00,0.00,25.00,'pagada','efectivo','2026-05-02 23:34:48');

/*Table structure for table `hospitalizacion` */

DROP TABLE IF EXISTS `hospitalizacion`;

CREATE TABLE `hospitalizacion` (
  `codigohospitalizacion` int NOT NULL AUTO_INCREMENT,
  `codigopaciente` int NOT NULL,
  `codigoempleado` int NOT NULL,
  `codigoenfermedad` int NOT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `fecha_alta` datetime DEFAULT NULL,
  `numero_habitacion` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motivo_ingreso` text COLLATE utf8mb4_general_ci NOT NULL,
  `diagnostico` text COLLATE utf8mb4_general_ci,
  `tratamiento` text COLLATE utf8mb4_general_ci,
  `estado` enum('en_curso','alta_medica','alta_voluntaria','referido','fallecido') COLLATE utf8mb4_general_ci DEFAULT 'en_curso',
  `notas_medicas` text COLLATE utf8mb4_general_ci,
  `costo_estimado` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`codigohospitalizacion`),
  KEY `codigoenfermedad` (`codigoenfermedad`),
  KEY `idx_paciente` (`codigopaciente`),
  KEY `idx_empleado` (`codigoempleado`),
  KEY `idx_fecha_ingreso` (`fecha_ingreso`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `hospitalizacion_ibfk_1` FOREIGN KEY (`codigopaciente`) REFERENCES `paciente` (`codigopaciente`) ON DELETE CASCADE,
  CONSTRAINT `hospitalizacion_ibfk_2` FOREIGN KEY (`codigoempleado`) REFERENCES `empleado` (`codigoempleado`),
  CONSTRAINT `hospitalizacion_ibfk_3` FOREIGN KEY (`codigoenfermedad`) REFERENCES `enfermedad` (`codigoenfermedad`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `hospitalizacion` */

insert  into `hospitalizacion`(`codigohospitalizacion`,`codigopaciente`,`codigoempleado`,`codigoenfermedad`,`fecha_ingreso`,`fecha_alta`,`numero_habitacion`,`motivo_ingreso`,`diagnostico`,`tratamiento`,`estado`,`notas_medicas`,`costo_estimado`,`created_at`,`updated_at`) values 
(1,1,1,5,'2025-11-09 21:52:36',NULL,'302-B','Paciente viene con problemas de respiracion','Posible obstruccion en las vias respiratorias','Prednison(para desinflamr los pulmones)','en_curso',NULL,85.00,'2025-11-09 21:52:36','2026-05-02 22:39:07');

/*Table structure for table `laboratorio` */

DROP TABLE IF EXISTS `laboratorio`;

CREATE TABLE `laboratorio` (
  `codigolab` int NOT NULL AUTO_INCREMENT,
  `codigopaciente` int NOT NULL,
  `codigoempleado` int NOT NULL,
  `tipo_examen` varchar(150) NOT NULL,
  `resultado` text,
  `fecha_examen` date NOT NULL,
  `archivo_adjunto` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','listo') DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`codigolab`),
  KEY `codigopaciente` (`codigopaciente`),
  KEY `codigoempleado` (`codigoempleado`),
  CONSTRAINT `laboratorio_ibfk_1` FOREIGN KEY (`codigopaciente`) REFERENCES `paciente` (`codigopaciente`) ON DELETE CASCADE,
  CONSTRAINT `laboratorio_ibfk_2` FOREIGN KEY (`codigoempleado`) REFERENCES `empleado` (`codigoempleado`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `laboratorio` */

insert  into `laboratorio`(`codigolab`,`codigopaciente`,`codigoempleado`,`tipo_examen`,`resultado`,`fecha_examen`,`archivo_adjunto`,`estado`,`created_at`) values 
(1,1,1,'Hemograma, glucosa, calcio y fosforo','','2026-07-01',NULL,'pendiente','2026-05-02 23:01:39'),
(2,1,2,'Hemograma completo',NULL,'2026-05-02',NULL,'pendiente','2026-05-02 23:34:09');

/*Table structure for table `medicina` */

DROP TABLE IF EXISTS `medicina`;

CREATE TABLE `medicina` (
  `codigomedicina` int NOT NULL AUTO_INCREMENT,
  `nombremedicina` varchar(150) NOT NULL,
  `descripcion` text,
  `tipo` enum('pastilla','jarabe','inyeccion','pomada','otro') DEFAULT 'pastilla',
  `stock` int DEFAULT '0',
  `precio` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`codigomedicina`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `medicina` */

insert  into `medicina`(`codigomedicina`,`nombremedicina`,`descripcion`,`tipo`,`stock`,`precio`,`created_at`) values 
(1,'Amoxicilina 500mg','Antibiótico de amplio espectro.','pastilla',100,0.45,'2026-05-02 23:08:50'),
(2,'Ibuprofeno 400mg','Analgésico y antiinflamatorio.','pastilla',250,0.20,'2026-05-02 23:08:50'),
(3,'Paracetamol 500mg','Analgésico y antipirético.','pastilla',500,0.15,'2026-05-02 23:08:50'),
(4,'Loratadina 10mg','Antihistamínico para alergias.','pastilla',150,0.30,'2026-05-02 23:08:50'),
(5,'Omeprazol 20mg','Protector gástrico.','pastilla',200,0.25,'2026-05-02 23:08:50'),
(6,'Diclofenaco Gel','Antiinflamatorio tópico.','pomada',50,3.50,'2026-05-02 23:08:50'),
(7,'Salbutamol Inhalador','Broncodilatador para asma.','otro',10,8.00,'2026-05-02 23:08:50'),
(8,'Ambrosol Jarabe','Expectorante para la tos.','jarabe',60,5.00,'2026-05-02 23:08:50'),
(9,'Dexametasona Ampolla','Corticoide inyectable.','inyeccion',40,2.75,'2026-05-02 23:08:50'),
(10,'Vitamina C 1g','Suplemento vitamínico efervescente.','pastilla',300,0.50,'2026-05-02 23:08:50'),
(11,'Metformina 850mg','Tratamiento para diabetes tipo 2.','pastilla',180,0.40,'2026-05-02 23:08:50'),
(12,'Enalapril 10mg','Control de la presión arterial.','pastilla',120,0.35,'2026-05-02 23:08:50'),
(13,'Clotrimazol Crema','Antifúngico tópico.','pomada',60,2.20,'2026-05-02 23:08:50'),
(14,'Complejo B Inyectable','Multivitamínico.','inyeccion',50,3.00,'2026-05-02 23:08:50'),
(15,'Suero Oral','Rehidratación electrolítica.','otro',200,1.25,'2026-05-02 23:08:50');

/*Table structure for table `municipio` */

DROP TABLE IF EXISTS `municipio`;

CREATE TABLE `municipio` (
  `codigomunicipio` int NOT NULL AUTO_INCREMENT,
  `nombremunicipio` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `departamento` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`codigomunicipio`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `municipio` */

insert  into `municipio`(`codigomunicipio`,`nombremunicipio`,`departamento`,`created_at`) values 
(1,'San Salvador','San Salvador','2025-11-06 18:39:32'),
(2,'Santa Tecla','La Libertad','2025-11-06 18:39:32'),
(3,'Soyapango','San Salvador','2025-11-06 18:39:32'),
(4,'San Miguel','San Miguel','2025-11-06 18:39:32'),
(5,'Santa Ana','Santa Ana','2025-11-06 18:39:32'),
(6,'Apopa','San Salvador','2025-11-06 18:39:32'),
(7,'Mejicanos','San Salvador','2025-11-06 18:39:32'),
(8,'Sonsonate','Sonsonate','2025-11-06 18:39:32'),
(9,'Ahuachapán','Ahuachapán','2025-11-06 18:39:32'),
(10,'La Unión','La Unión','2025-11-06 18:39:32');

/*Table structure for table `paciente` */

DROP TABLE IF EXISTS `paciente`;

CREATE TABLE `paciente` (
  `codigopaciente` int NOT NULL AUTO_INCREMENT,
  `nombrepaciente` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `dui` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `fechanacimiento` date NOT NULL,
  `genero` enum('M','F','Otro') COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` text COLLATE utf8mb4_general_ci,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codigomunicipio` int DEFAULT NULL,
  `tipo_sangre` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alergias` text COLLATE utf8mb4_general_ci,
  `contacto_emergencia` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono_emergencia` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`codigopaciente`),
  UNIQUE KEY `dui` (`dui`),
  KEY `codigomunicipio` (`codigomunicipio`),
  KEY `idx_dui` (`dui`),
  KEY `idx_nombre` (`nombrepaciente`),
  CONSTRAINT `paciente_ibfk_1` FOREIGN KEY (`codigomunicipio`) REFERENCES `municipio` (`codigomunicipio`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `paciente` */

insert  into `paciente`(`codigopaciente`,`nombrepaciente`,`dui`,`fechanacimiento`,`genero`,`direccion`,`telefono`,`correo`,`codigomunicipio`,`tipo_sangre`,`alergias`,`contacto_emergencia`,`telefono_emergencia`,`created_at`,`updated_at`) values 
(1,'Juan Carlos Ramírez','012345678','1985-05-15','M','Col. Escalón, San Salvador','7777-1111','juan.ramirez@email.com',1,'O+','Penicilina','María Ramírez','7777-2222','2025-11-06 18:39:32','2026-05-02 22:38:59'),
(2,'Patricia Morales','023456789','1990-08-20','F','Col. San Benito, Santa Tecla','7777-3333','patricia.morales@email.com',2,'A+','Ninguna','Pedro Morales','7777-4444','2025-11-06 18:39:32','2025-11-06 18:39:32'),
(3,'Fernando Silva','034567890','1978-12-10','M','Col. Flor Blanca, Soyapango','7777-5555','fernando.silva@email.com',3,'B+','Aspirina','Laura Silva','7777-6666','2025-11-06 18:39:32','2025-11-06 18:39:32'),
(4,'Sofía Martínez','045678901','1995-03-25','F','Residencial Las Palmas, San Salvador','7777-7777','sofia.martinez@email.com',1,'AB+','Polen','Carlos Martínez','7777-8888','2025-11-06 18:39:32','2025-11-06 18:39:32'),
(5,'Miguel Ángel Torres','056789012','1982-07-08','M','Col. Miramonte, San Miguel','7777-9999','miguel.torres@email.com',4,'O-','Mariscos','Rosa Torres','7777-0000','2025-11-06 18:39:32','2025-11-06 18:39:32');

/*Table structure for table `receta` */

DROP TABLE IF EXISTS `receta`;

CREATE TABLE `receta` (
  `codigoreceta` int NOT NULL AUTO_INCREMENT,
  `codigopaciente` int NOT NULL,
  `codigoempleado` int NOT NULL,
  `fecha_receta` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`codigoreceta`),
  KEY `codigopaciente` (`codigopaciente`),
  KEY `codigoempleado` (`codigoempleado`),
  CONSTRAINT `receta_ibfk_1` FOREIGN KEY (`codigopaciente`) REFERENCES `paciente` (`codigopaciente`) ON DELETE CASCADE,
  CONSTRAINT `receta_ibfk_2` FOREIGN KEY (`codigoempleado`) REFERENCES `empleado` (`codigoempleado`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `receta` */

insert  into `receta`(`codigoreceta`,`codigopaciente`,`codigoempleado`,`fecha_receta`) values 
(1,5,1,'2026-05-02 23:14:22'),
(2,1,2,'2026-05-02 23:34:09');

/*Table structure for table `receta_detalle` */

DROP TABLE IF EXISTS `receta_detalle`;

CREATE TABLE `receta_detalle` (
  `codigodetalle` int NOT NULL AUTO_INCREMENT,
  `codigoreceta` int NOT NULL,
  `codigomedicina` int NOT NULL,
  `cantidad` int NOT NULL,
  `indicaciones` text,
  PRIMARY KEY (`codigodetalle`),
  KEY `codigoreceta` (`codigoreceta`),
  KEY `codigomedicina` (`codigomedicina`),
  CONSTRAINT `receta_detalle_ibfk_1` FOREIGN KEY (`codigoreceta`) REFERENCES `receta` (`codigoreceta`) ON DELETE CASCADE,
  CONSTRAINT `receta_detalle_ibfk_2` FOREIGN KEY (`codigomedicina`) REFERENCES `medicina` (`codigomedicina`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `receta_detalle` */

insert  into `receta_detalle`(`codigodetalle`,`codigoreceta`,`codigomedicina`,`cantidad`,`indicaciones`) values 
(1,1,7,20,'1 cada 8 horas'),
(2,1,8,15,'1 cada 8 horas'),
(3,2,8,5,'1 cada 8 horas');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
