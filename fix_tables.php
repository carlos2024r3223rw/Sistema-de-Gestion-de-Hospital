<?php
require_once("includes/db.php");

$sql = "
CREATE TABLE IF NOT EXISTS medicina (
    codigomedicina INT AUTO_INCREMENT PRIMARY KEY,
    nombremedicina VARCHAR(150) NOT NULL,
    descripcion TEXT,
    tipo ENUM('pastilla', 'jarabe', 'inyeccion', 'pomada', 'otro') DEFAULT 'pastilla',
    stock INT DEFAULT 0,
    precio DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cita (
    codigocita INT AUTO_INCREMENT PRIMARY KEY,
    codigopaciente INT NOT NULL,
    codigoempleado INT NOT NULL,
    fecha_cita DATE NOT NULL,
    hora_cita TIME NOT NULL,
    motivo VARCHAR(255),
    estado ENUM('pendiente', 'completada', 'cancelada') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (codigopaciente) REFERENCES paciente(codigopaciente) ON DELETE CASCADE,
    FOREIGN KEY (codigoempleado) REFERENCES empleado(codigoempleado)
);

CREATE TABLE IF NOT EXISTS laboratorio (
    codigolab INT AUTO_INCREMENT PRIMARY KEY,
    codigopaciente INT NOT NULL,
    codigoempleado INT NOT NULL,
    tipo_examen VARCHAR(150) NOT NULL,
    resultado TEXT,
    fecha_examen DATE NOT NULL,
    archivo_adjunto VARCHAR(255) DEFAULT NULL,
    estado ENUM('pendiente', 'listo') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (codigopaciente) REFERENCES paciente(codigopaciente) ON DELETE CASCADE,
    FOREIGN KEY (codigoempleado) REFERENCES empleado(codigoempleado)
);

INSERT IGNORE INTO enfermedad (nombreenfermedad, descripcion, tipo, nivel_gravedad, tratamiento_recomendado) VALUES 
('Gripe Estacional', 'Infección viral respiratoria común.', 'infecciosa', 'leve', 'Reposo, hidratación y paracetamol.'),
('Hipertensión Arterial', 'Presión sanguínea crónicamente alta.', 'cronica', 'moderada', 'Dieta baja en sal y antihipertensivos.'),
('Asma Bronquial', 'Inflamación de las vías respiratorias.', 'cronica', 'moderada', 'Inhaladores y evitar alérgenos.'),
('Neumonía', 'Infección que inflama los sacos aéreos de los pulmones.', 'aguda', 'grave', 'Antibióticos y oxigenoterapia si es necesario.'),
('Insuficiencia Renal', 'Pérdida de la capacidad de los riñones para filtrar.', 'cronica', 'critica', 'Diálisis o trasplante renal.');
";

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "Tablas creadas y datos actualizados correctamente ✅";
} else {
    echo "Error: " . $conn->error;
}
?>
