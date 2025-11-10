<?php
session_start();
require_once("includes/db.php");
requireLogin();

$mensaje = '';
$tipoMensaje = '';

// Procesar formulario de registro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombreempleado = sanitizeInput($conn, $_POST["nombreempleado"]);
    $cargo = sanitizeInput($conn, $_POST["cargo"]);
    $correo = sanitizeInput($conn, $_POST["correo"]);
    $telefono = sanitizeInput($conn, $_POST["telefono"]);
    $fecha_contratacion = $_POST["fecha_contratacion"];

    $sql = "INSERT INTO empleado (nombreempleado, cargo, correo, telefono, fecha_contratacion) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = executeQuery($conn, $sql, "sssss", [
        $nombreempleado, $cargo, $correo, $telefono, $fecha_contratacion
    ]);

    if ($stmt) {
        $mensaje = "Empleado registrado exitosamente";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al registrar empleado";
        $tipoMensaje = "error";
    }
}

// Obtener empleados
$empleados = $conn->query("
    SELECT * 
    FROM empleado
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados - Sistema Hospital</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <span>🏥</span>
                <span>Sistema Hospital</span>
            </div>
            <div class="user-info">
                <div class="avatar">👤</div>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['nombreempleado']) ?></strong>
                    <br>
                    <small><?= getRolName($_SESSION['rol']) ?></small>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <nav>
            <a href="index.php">🏠 Dashboard</a>
            <a href="empleados.php" class="active">👨‍⚕️ Empleados</a>
            <a href="pacientes.php">🧑‍🦽 Pacientes</a>
            <a href="hospitalizaciones.php">🏥 Hospitalizaciones</a>
            <a href="enfermedades.php">🦠 Enfermedades</a>
            <a href="municipios.php">📍 Municipios</a>
            <a href="logout.php">🚪 Salir</a>
        </nav>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipoMensaje ?>">
                <?= $tipoMensaje == 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de Registro -->
        <div class="card">
            <div class="card-header">
                <h2>➕ Registrar Nuevo Empleado</h2>
            </div>
            
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombreempleado">Nombre Completo *</label>
                        <input type="text" id="nombreempleado" name="nombreempleado" placeholder="Ej: María López" required>
                    </div>

                    <div class="form-group">
                        <label for="cargo">Cargo *</label>
                        <input type="text" id="cargo" name="cargo" placeholder="Ej: Enfermera, Doctor" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="correo">Correo Electrónico</label>
                        <input type="email" id="correo" name="correo" placeholder="correo@ejemplo.com">
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" placeholder="0000-0000">
                    </div>
                </div>

                <div class="form-group">
                    <label for="fecha_contratacion">Fecha de Contratación</label>
                    <input type="date" id="fecha_contratacion" name="fecha_contratacion">
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">💾 Guardar Empleado</button>
                    <button type="reset" class="btn btn-secondary">🔄 Limpiar</button>
                </div>
            </form>
        </div>

        <!-- Lista de Empleados -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Lista de Empleados</h2>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Cargo</th>
                            <th>Correo</th>
                            <th>Teléfono</th>
                            <th>Fecha Contratación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($empleados->num_rows > 0): ?>
                            <?php while ($e = $empleados->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($e['codigoempleado']) ?></td>
                                    <td><strong><?= htmlspecialchars($e['nombreempleado']) ?></strong></td>
                                    <td><?= htmlspecialchars($e['rol']) ?></td>
                                    <td><?= htmlspecialchars($e['correo'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($e['telefono'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($e['fecha_contratacion'] ?? 'N/A') ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-info" onclick="verDetalle(<?= $e['codigoempleado'] ?>)">👁️</button>
                                            <button class="btn btn-danger" onclick="eliminarEmpleado(<?= $e['codigoempleado'] ?>)">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-light); padding: 2rem;">
                                    No hay empleados registrados
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function verDetalle(id) {
            alert('Funcionalidad de detalle - ID: ' + id);
        }

        function eliminarEmpleado(id) {
            if (confirm('¿Seguro que deseas eliminar este empleado?')) {
                // Aquí podrías agregar una llamada fetch() o redirección para eliminar
                alert('Eliminar empleado ID: ' + id + ' (pendiente implementar)');
            }
        }
    </script>
</body>
</html>
