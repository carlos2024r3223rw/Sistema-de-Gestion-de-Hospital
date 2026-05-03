<?php
require_once("includes/db.php");
$sql = "CREATE TABLE IF NOT EXISTS factura (
    codigofactura INT AUTO_INCREMENT PRIMARY KEY,
    codigopaciente INT NOT NULL,
    codigocita INT,
    codigohospitalizacion INT,
    monto_servicios DECIMAL(10,2) DEFAULT 0.00,
    monto_medicinas DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'pagada') DEFAULT 'pendiente',
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'seguro'),
    fecha_emision TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (codigopaciente) REFERENCES paciente(codigopaciente) ON DELETE CASCADE
)";
if ($conn->query($sql)) echo "OK"; else echo $conn->error;
?>
