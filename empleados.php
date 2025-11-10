<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = sanitizeInput($conn, $_POST["nombre"]);
    $apellido = sanitizeInput($conn, $_POST["apellido"]);
    $correo = sanitizeInput($conn, $_POST["correo"]);
    $rol = sanitizeInput($conn, $_POST["rol"]);

    $sql = "INSERT INTO empleado (nombre, apellido, correo, rol) VALUES (?, ?, ?, ?)";
    $stmt = executeQuery($conn, $sql, "ssss", [$nombre, $apellido, $correo, $rol]);

    if ($stmt) {
        $mensaje = "Empleado registrado exitosamente";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al registrar empleado";
        $tipoMensaje = "error";
    }
}

$empleados = $conn->query("SELECT * FROM empleado ORDER BY nombreempleado");
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
                        <label for="nombre">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" placeholder="Ej: Juan" required>
                    </div>

                    <div class="form-group">
                        <label for="apellido">Apellido *</label>
                        <input type="text" id="apellido" name="apellido" placeholder="Ej: Pérez" required>
                    </div>

                    <div class="form-group">
                        <label for="correo">Correo *</label>
                        <input type="email" id="correo" name="correo" placeholder="ejemplo@correo.com" required>
                    </div>

                    <div class="form-group">
                        <label for="rol">Rol *</label>
                        <select id="rol" name="rol" required>
                            <option value="">Seleccione...</option>
                            <option value="1">Administrador</option>
                            <option value="2">Médico</option>
                            <option value="3">Enfermero</option>
                            <option value="4">Recepción</option>
                        </select>
                    </div>
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
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Fecha Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($empleados->num_rows > 0): ?>
                            <?php while ($e = $empleados->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($e['codigoempleado']) ?></td>
                                    <td><strong><?= htmlspecialchars($e['nombreempleado']) ?></strong></td>
                                    <td><?= htmlspecialchars($e['correoempleado']) ?></td>
                                    <td><?= htmlspecialchars($e['rol']) ?></td>
                                    <td><?= formatDate($e['created_at']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-light); padding: 2rem;">
                                    No hay empleados registrados
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
