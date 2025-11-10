<?php
// Archivo temporal para generar hashes de contraseñas
// Crear este archivo en la raíz del proyecto y ejecutarlo una vez

echo "<h2>Generador de Hashes de Contraseñas</h2>";
echo "<style>body{font-family:Arial;padding:20px;} code{background:#f4f4f4;padding:5px;display:block;margin:10px 0;}</style>";

// Contraseña que queremos hashear
$password = "12345";

// Generar el hash
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<p><strong>Contraseña original:</strong> $password</p>";
echo "<p><strong>Hash generado:</strong></p>";
echo "<code>$hash</code>";

echo "<hr>";
echo "<h3>SQL para actualizar todos los empleados:</h3>";
echo "<code>UPDATE empleado SET clave = '$hash';</code>";

echo "<hr>";
echo "<h3>Verificación:</h3>";
if (password_verify($password, $hash)) {
    echo "<p style='color:green;'>✓ Hash verificado correctamente!</p>";
} else {
    echo "<p style='color:red;'>✗ Error en la verificación</p>";
}

echo "<hr>";
echo "<h3>Hashes para otras contraseñas comunes:</h3>";

$passwords = ['12345', 'admin', 'hospital123', 'password', '123456'];

foreach ($passwords as $pass) {
    $h = password_hash($pass, PASSWORD_DEFAULT);
    echo "<p><strong>$pass:</strong></p>";
    echo "<code>$h</code><br><br>";
}
?>