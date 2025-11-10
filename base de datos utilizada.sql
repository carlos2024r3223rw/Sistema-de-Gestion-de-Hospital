
CREATE DATABASE  IF NOT EXISTS `hospital`;

USE `hospital`;


CREATE TABLE `consulta` (
  `codigoconsulta` int(11) NOT NULL AUTO_INCREMENT,
  `codigopaciente` int(11) NOT NULL,
  `codigoempleado` int(11) NOT NULL,
  `fecha_consulta` datetime NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `sintomas` text DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `receta` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `presion_arterial` varchar(20) DEFAULT NULL,
  `temperatura` decimal(4,2) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `altura` decimal(5,2) DEFAULT NULL,
  `costo` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`codigoconsulta`),
  KEY `codigoempleado` (`codigoempleado`),
  KEY `idx_fecha` (`fecha_consulta`),
  KEY `idx_paciente` (`codigopaciente`),
  CONSTRAINT `consulta_ibfk_1` FOREIGN KEY (`codigopaciente`) REFERENCES `paciente` (`codigopaciente`) ON DELETE CASCADE,
  CONSTRAINT `consulta_ibfk_2` FOREIGN KEY (`codigoempleado`) REFERENCES `empleado` (`codigoempleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `empleado` (
  `codigoempleado` int(11) NOT NULL AUTO_INCREMENT,
  `nombreempleado` varchar(150) NOT NULL,
  `correoempleado` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `rol` enum('admin','doctor','enfermera','recepcionista') NOT NULL DEFAULT 'recepcionista',
  `telefono` varchar(20) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `fecha_contratacion` date DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`codigoempleado`),
  UNIQUE KEY `correoempleado` (`correoempleado`),
  KEY `idx_correo` (`correoempleado`),
  KEY `idx_rol` (`rol`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


insert  into `empleado`(`codigoempleado`,`nombreempleado`,`correoempleado`,`clave`,`rol`,`telefono`,`especialidad`,`fecha_contratacion`,`estado`,`created_at`,`updated_at`) values 
(1,'Dr. Carlos Martínez','carlos.martinez@hospital.com','$2y$10$iwauguh4eZnRhQT3eJmX8Ojrs1CXHypxLPrFwq6m1Sja8heYDL28G','admin','7777-1234','Administración','2020-01-15','activo','2025-11-06 18:39:32','2025-11-06 18:54:12'),
(2,'Dra. María González','maria.gonzalez@hospital.com','$2y$10$b5AKyHh9FVBTqeYdjW8D4./f.ou5OuU0/7CqZ7dtc3JKooJ4mOSSW','doctor','7777-5678','Cardiología','2021-03-20','activo','2025-11-06 18:39:32','2025-11-09 22:03:08'),
(3,'Dr. Roberto Pérez','roberto.perez@hospital.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','doctor','7777-9012','Pediatría','2021-06-10','activo','2025-11-06 18:39:32','2025-11-06 18:39:32'),
(4,'Enf. Ana López','ana.lopez@hospital.com','$2y$10$iwauguh4eZnRhQT3eJmX8Ojrs1CXHypxLPrFwq6m1Sja8heYDL28G','enfermera','7777-3456','Cuidados Generales','2022-01-05','activo','2025-11-06 18:39:32','2025-11-09 21:53:19'),
(5,'Enf. Luis Hernández','luis.hernandez@hospital.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','enfermera','7777-7890','UCI','2022-04-15','activo','2025-11-06 18:39:32','2025-11-06 18:39:32'),
(6,'Carmen Flores','carmen.flores@hospital.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','recepcionista','7777-2345',NULL,'2023-02-01','activo','2025-11-06 18:39:32','2025-11-06 18:39:32');


CREATE TABLE `enfermedad` (
  `codigoenfermedad` int(11) NOT NULL AUTO_INCREMENT,
  `nombreenfermedad` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('infecciosa','cronica','aguda','degenerativa','mental','otra') DEFAULT 'otra',
  `nivel_gravedad` enum('leve','moderada','grave','critica') DEFAULT 'leve',
  `tratamiento_recomendado` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`codigoenfermedad`),
  KEY `idx_nombre` (`nombreenfermedad`),
  KEY `idx_tipo` (`tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


insert  into `enfermedad`(`codigoenfermedad`,`nombreenfermedad`,`descripcion`,`tipo`,`nivel_gravedad`,`tratamiento_recomendado`,`created_at`) values 
(1,'Hipertensión Arterial','Presión arterial elevada de forma crónica','cronica','moderada','Control de presión, dieta baja en sodio, medicación antihipertensiva','2025-11-06 18:39:32'),
(2,'Diabetes Mellitus Tipo 2','Trastorno metabólico caracterizado por hiperglucemia','cronica','moderada','Control de glucosa, dieta, ejercicio, medicación','2025-11-06 18:39:32'),
(3,'Neumonía','Infección pulmonar aguda','infecciosa','grave','Antibióticos, reposo, hidratación','2025-11-06 18:39:32'),
(4,'Gastroenteritis Aguda','Inflamación del tracto gastrointestinal','infecciosa','leve','Hidratación, dieta blanda, probióticos','2025-11-06 18:39:32'),
(5,'Asma Bronquial','Enfermedad inflamatoria crónica de las vías respiratorias','cronica','moderada','Broncodilatadores, corticoides inhalados, evitar desencadenantes','2025-11-06 18:39:32'),
(6,'Infarto Agudo de Miocardio','Muerte del músculo cardíaco por falta de flujo sanguíneo','aguda','critica','Atención médica inmediata, trombolíticos, angioplastia','2025-11-06 18:39:32'),
(7,'COVID-19','Enfermedad infecciosa causada por coronavirus SARS-CoV-2','infecciosa','moderada','Aislamiento, antivirales, oxigenoterapia si es necesario','2025-11-06 18:39:32');


DROP TABLE IF EXISTS `hospitalizacion`;

CREATE TABLE `hospitalizacion` (
  `codigohospitalizacion` int(11) NOT NULL AUTO_INCREMENT,
  `codigopaciente` int(11) NOT NULL,
  `codigoempleado` int(11) NOT NULL,
  `codigoenfermedad` int(11) NOT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `fecha_alta` datetime DEFAULT NULL,
  `numero_habitacion` varchar(20) DEFAULT NULL,
  `motivo_ingreso` text NOT NULL,
  `diagnostico` text DEFAULT NULL,
  `tratamiento` text DEFAULT NULL,
  `estado` enum('en_curso','alta_medica','alta_voluntaria','referido','fallecido') DEFAULT 'en_curso',
  `notas_medicas` text DEFAULT NULL,
  `costo_estimado` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
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
(1,1,1,5,'2025-11-09 21:52:36',NULL,'302-B','Paciente viene con problemas de respiracion','Posible obstruccion en las vias respiratorias','Prednison(para desinflamr los pulmones)','en_curso',NULL,80.00,'2025-11-09 21:52:36','2025-11-09 21:52:36');


DROP TABLE IF EXISTS `municipio`;

CREATE TABLE `municipio` (
  `codigomunicipio` int(11) NOT NULL AUTO_INCREMENT,
  `nombremunicipio` varchar(100) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`codigomunicipio`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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


DROP TABLE IF EXISTS `paciente`;

CREATE TABLE `paciente` (
  `codigopaciente` int(11) NOT NULL AUTO_INCREMENT,
  `nombrepaciente` varchar(150) NOT NULL,
  `dui` varchar(10) NOT NULL,
  `fechanacimiento` date NOT NULL,
  `genero` enum('M','F','Otro') NOT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `codigomunicipio` int(11) DEFAULT NULL,
  `tipo_sangre` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `contacto_emergencia` varchar(150) DEFAULT NULL,
  `telefono_emergencia` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`codigopaciente`),
  UNIQUE KEY `dui` (`dui`),
  KEY `codigomunicipio` (`codigomunicipio`),
  KEY `idx_dui` (`dui`),
  KEY `idx_nombre` (`nombrepaciente`),
  CONSTRAINT `paciente_ibfk_1` FOREIGN KEY (`codigomunicipio`) REFERENCES `municipio` (`codigomunicipio`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


insert  into `paciente`(`codigopaciente`,`nombrepaciente`,`dui`,`fechanacimiento`,`genero`,`direccion`,`telefono`,`correo`,`codigomunicipio`,`tipo_sangre`,`alergias`,`contacto_emergencia`,`telefono_emergencia`,`created_at`,`updated_at`) values 
(1,'Juan Carlos Ramírez','012345678','1985-05-15','M','Col. Escalón, San Salvador','7777-1111','juan.ramirez@email.com',1,'O+','Penicilina','María Ramírez','7777-2222','2025-11-06 18:39:32','2025-11-06 18:39:32'),
(2,'Patricia Morales','023456789','1990-08-20','F','Col. San Benito, Santa Tecla','7777-3333','patricia.morales@email.com',2,'A+','Ninguna','Pedro Morales','7777-4444','2025-11-06 18:39:32','2025-11-06 18:39:32'),
(3,'Fernando Silva','034567890','1978-12-10','M','Col. Flor Blanca, Soyapango','7777-5555','fernando.silva@email.com',3,'B+','Aspirina','Laura Silva','7777-6666','2025-11-06 18:39:32','2025-11-06 18:39:32'),
(4,'Sofía Martínez','045678901','1995-03-25','F','Residencial Las Palmas, San Salvador','7777-7777','sofia.martinez@email.com',1,'AB+','Polen','Carlos Martínez','7777-8888','2025-11-06 18:39:32','2025-11-06 18:39:32'),
(5,'Miguel Ángel Torres','056789012','1982-07-08','M','Col. Miramonte, San Miguel','7777-9999','miguel.torres@email.com',4,'O-','Mariscos','Rosa Torres','7777-0000','2025-11-06 18:39:32','2025-11-06 18:39:32');


