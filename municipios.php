<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombremunicipio = sanitizeInput($conn, $_POST["nombremunicipio"]);
    $departamento = sanitizeInput($conn, $_POST["departamento"]);

    $sql = "INSERT INTO municipio (nombremunicipio, departamento) VALUES (?, ?)";
    $stmt = executeQuery($conn, $sql, "ss", [$nombremunicipio, $departamento]);

    if ($stmt) {
        $mensaje = "Municipio registrado exitosamente";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al registrar municipio";
        $tipoMensaje = "error";
    }
}

// Obtener municipios con estadísticas
$municipios = $conn->query("
    SELECT 
        m.*,
        COUNT(DISTINCT p.codigopaciente) as total_pacientes
    FROM municipio m
    LEFT JOIN paciente p ON m.codigomunicipio = p.codigomunicipio
    GROUP BY m.codigomunicipio
    ORDER BY m.departamento, m.nombremunicipio
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Municipios - Sistema Hospital</title>
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
            <a href="empleados.php">👨‍⚕️ Empleados</a>
            <a href="pacientes.php">🧑‍🦽 Pacientes</a>
            <a href="hospitalizaciones.php">🏥 Hospitalizaciones</a>
            <a href="enfermedades.php">🦠 Enfermedades</a>
            <a href="municipios.php" class="active">📍 Municipios</a>
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
                <h2>➕ Registrar Nuevo Municipio</h2>
            </div>
            
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombremunicipio">Nombre del Municipio *</label>
                        <input type="text" id="nombremunicipio" name="nombremunicipio" 
                               placeholder="Ej: San Salvador" required>
                    </div>

                    <div class="form-group">
                        <label for="departamento">Departamento *</label>
                        <select id="departamento" name="departamento" required>
                            <option value="">Seleccione...</option>
                            <option value="Ahuachapán">Ahuachapán</option>
                            <option value="Cabañas">Cabañas</option>
                            <option value="Chalatenango">Chalatenango</option>
                            <option value="Cuscatlán">Cuscatlán</option>
                            <option value="La Libertad">La Libertad</option>
                            <option value="La Paz">La Paz</option>
                            <option value="La Unión">La Unión</option>
                            <option value="Morazán">Morazán</option>
                            <option value="San Miguel">San Miguel</option>
                            <option value="San Salvador">San Salvador</option>
                            <option value="San Vicente">San Vicente</option>
                            <option value="Santa Ana">Santa Ana</option>
                            <option value="Sonsonate">Sonsonate</option>
                            <option value="Usulután">Usulután</option>
                        </select>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">💾 Guardar Municipio</button>
                    <button type="reset" class="btn btn-secondary">🔄 Limpiar</button>
                </div>
            </form>
        </div>

        <!-- Lista de Municipios -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Lista de Municipios</h2>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Municipio</th>
                            <th>Departamento</th>
                            <th>Pacientes Registrados</th>
                            <th>Fecha Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($municipios->num_rows > 0): ?>
                            <?php while ($m = $municipios->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['codigomunicipio']) ?></td>
                                    <td><strong><?= htmlspecialchars($m['nombremunicipio']) ?></strong></td>
                                    <td><?= htmlspecialchars($m['departamento']) ?></td>
                                    <td>
                                        <span class="badge badge-info"><?= $m['total_pacientes'] ?></span>
                                    </td>
                                    <td><?= formatDate($m['created_at']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-light); padding: 2rem;">
                                    No hay municipios registrados
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