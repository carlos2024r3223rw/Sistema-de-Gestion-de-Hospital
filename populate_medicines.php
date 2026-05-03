<?php
require_once("includes/db.php");

$sql = "
-- Tablas de Recetas
CREATE TABLE IF NOT EXISTS receta (
    codigoreceta INT AUTO_INCREMENT PRIMARY KEY,
    codigopaciente INT NOT NULL,
    codigoempleado INT NOT NULL,
    fecha_receta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (codigopaciente) REFERENCES paciente(codigopaciente) ON DELETE CASCADE,
    FOREIGN KEY (codigoempleado) REFERENCES empleado(codigoempleado)
);

CREATE TABLE IF NOT EXISTS receta_detalle (
    codigodetalle INT AUTO_INCREMENT PRIMARY KEY,
    codigoreceta INT NOT NULL,
    codigomedicina INT NOT NULL,
    cantidad INT NOT NULL,
    indicaciones TEXT,
    FOREIGN KEY (codigoreceta) REFERENCES receta(codigoreceta) ON DELETE CASCADE,
    FOREIGN KEY (codigomedicina) REFERENCES medicina(codigomedicina)
);

-- Insertar Lote de Medicamentos
INSERT INTO medicina (nombremedicina, descripcion, tipo, stock, precio) VALUES 
('Amoxicilina 500mg', 'Antibiótico de amplio espectro.', 'pastilla', 100, 0.45),
('Ibuprofeno 400mg', 'Analgésico y antiinflamatorio.', 'pastilla', 250, 0.20),
('Paracetamol 500mg', 'Analgésico y antipirético.', 'pastilla', 500, 0.15),
('Loratadina 10mg', 'Antihistamínico para alergias.', 'pastilla', 150, 0.30),
('Omeprazol 20mg', 'Protector gástrico.', 'pastilla', 200, 0.25),
('Diclofenaco Gel', 'Antiinflamatorio tópico.', 'pomada', 50, 3.50),
('Salbutamol Inhalador', 'Broncodilatador para asma.', 'otro', 30, 8.00),
('Ambrosol Jarabe', 'Expectorante para la tos.', 'jarabe', 80, 4.50),
('Dexametasona Ampolla', 'Corticoide inyectable.', 'inyeccion', 40, 2.75),
('Vitamina C 1g', 'Suplemento vitamínico efervescente.', 'pastilla', 300, 0.50),
('Metformina 850mg', 'Tratamiento para diabetes tipo 2.', 'pastilla', 180, 0.40),
('Enalapril 10mg', 'Control de la presión arterial.', 'pastilla', 120, 0.35),
('Clotrimazol Crema', 'Antifúngico tópico.', 'pomada', 60, 2.20),
('Complejo B Inyectable', 'Multivitamínico.', 'inyeccion', 50, 3.00),
('Suero Oral', 'Rehidratación electrolítica.', 'otro', 200, 1.25);
";

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) { $result->free(); }
    } while ($conn->next_result());
    echo "Inventario de medicinas actualizado y tablas de recetas creadas ✅";
} else {
    echo "Error: " . $conn->error;
}
?>
