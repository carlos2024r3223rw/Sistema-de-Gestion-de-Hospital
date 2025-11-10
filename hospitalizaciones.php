<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigopaciente = (int)$_POST["codigopaciente"];
    $codigoempleado = (int)$_POST["codigoempleado"];
    $codigoenfermedad = (int)$_POST["codigoenfermedad"];
    $numero_habitacion = sanitizeInput($conn, $_POST["numero_habitacion"]);
    $motivo_ingreso = sanitizeInput($conn, $_POST["motivo_ingreso"]);
    $diagnostico = sanitizeInput($conn, $_POST["diagnostico"]);
    $tratamiento = sanitizeInput($conn, $_POST["tratamiento"]);
    $costo_estimado = floatval($_POST["costo_estimado"]);

    $sql = "INSERT INTO hospitalizacion (codigopaciente, codigoempleado, codigoenfermedad, fecha_ingreso, numero_habitacion, motivo_ingreso, diagnostico, tratamiento, estado, costo_estimado) 
            VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, 'en_curso', ?)";
    
    $stmt = executeQuery($conn, $sql, "iiissssd", [
        $codigopaciente, $codigoempleado, $codigoenfermedad, 
        $numero_habitacion, $motivo_ingreso, $diagnostico, 
        $tratamiento, $costo_estimado
    ]);

    if ($stmt) {
        $mensaje = "Hospitalización registrada exitosamente";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al registrar hospitalización";
        $tipoMensaje = "error";
    }
}

// Dar de alta
if (isset($_GET['alta']) && $_GET['alta']) {
    $codigohospitalizacion = (int)$_GET['alta'];
    $sql = "UPDATE hospitalizacion SET fecha_alta = NOW(), estado = 'alta_medica' WHERE codigohospitalizacion = ?";
    $stmt = executeQuery($conn, $sql, "i", [$codigohospitalizacion]);
    
    if ($stmt) {
        $mensaje = "Paciente dado de alta exitosamente";
        $tipoMensaje = "success";
    }
}

// Obtener datos para formulario
$pacientes = $conn->query("SELECT codigopaciente, nombrepaciente, dui FROM paciente ORDER BY nombrepaciente");
$empleados = $conn->query("SELECT codigoempleado, nombreempleado, especialidad FROM empleado WHERE rol IN ('doctor', 'admin') AND estado = 'activo' ORDER BY nombreempleado");
$enfermedades = $conn->query("SELECT codigoenfermedad, nombreenfermedad, tipo FROM enfermedad ORDER BY nombreenfermedad");

// Obtener hospitalizaciones
$filtro = $_GET['estado'] ?? 'todas';
$whereClause = $filtro === 'todas' ? '' : "WHERE h.estado = '" . sanitizeInput($conn, $filtro) . "'";

$hospitalizaciones = $conn->query("
    SELECT 
        h.*,
        p.nombrepaciente,
        p.dui,
        p.telefono as telefono_paciente,
        e.nombreempleado,
        e.especialidad,
        enf.nombreenfermedad,
        enf.tipo as tipo_enfermedad,
        DATEDIFF(COALESCE(h.fecha_alta, NOW()), h.fecha_ingreso) as dias_hospitalizacion
    FROM hospitalizacion h
    INNER JOIN paciente p ON h.codigopaciente = p.codigopaciente
    INNER JOIN empleado e ON h.codigoempleado = e.codigoempleado
    INNER JOIN enfermedad enf ON h.codigoenfermedad = enf.codigoenfermedad
    $whereClause
    ORDER BY h.fecha_ingreso DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospitalizaciones - Sistema Hospital</title>
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
            <a href="hospitalizaciones.php" class="active">🏥 Hospitalizaciones</a>
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
                <h2>➕ Registrar Nueva Hospitalización</h2>
            </div>
            
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="codigopaciente">Paciente *</label>
                        <select id="codigopaciente" name="codigopaciente" required>
                            <option value="">Seleccione un paciente...</option>
                            <?php while ($p = $pacientes->fetch_assoc()): ?>
                                <option value="<?= $p['codigopaciente'] ?>">
                                    <?= htmlspecialchars($p['nombrepaciente']) ?> - DUI: <?= htmlspecialchars($p['dui']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="codigoempleado">Médico Tratante *</label>
                        <select id="codigoempleado" name="codigoempleado" required>
                            <option value="">Seleccione un médico...</option>
                            <?php while ($e = $empleados->fetch_assoc()): ?>
                                <option value="<?= $e['codigoempleado'] ?>">
                                    <?= htmlspecialchars($e['nombreempleado']) ?>
                                    <?= $e['especialidad'] ? ' - ' . htmlspecialchars($e['especialidad']) : '' ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="codigoenfermedad">Enfermedad/Diagnóstico *</label>
                        <select id="codigoenfermedad" name="codigoenfermedad" required>
                            <option value="">Seleccione una enfermedad...</option>
                            <?php while ($enf = $enfermedades->fetch_assoc()): ?>
                                <option value="<?= $enf['codigoenfermedad'] ?>">
                                    <?= htmlspecialchars($enf['nombreenfermedad']) ?> 
                                    (<?= htmlspecialchars(ucfirst($enf['tipo'])) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="numero_habitacion">Número de Habitación *</label>
                        <input type="text" id="numero_habitacion" name="numero_habitacion" placeholder="Ej: 301-A" required>
                    </div>

                    <div class="form-group">
                        <label for="costo_estimado">Costo Estimado ($)</label>
                        <input type="number" id="costo_estimado" name="costo_estimado" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>

                <div class="form-group">
                    <label for="motivo_ingreso">Motivo de Ingreso *</label>
                    <textarea id="motivo_ingreso" name="motivo_ingreso" placeholder="Describa el motivo principal de la hospitalización" required></textarea>
                </div>

                <div class="form-group">
                    <label for="diagnostico">Diagnóstico Inicial</label>
                    <textarea id="diagnostico" name="diagnostico" placeholder="Diagnóstico preliminar del paciente"></textarea>
                </div>

                <div class="form-group">
                    <label for="tratamiento">Tratamiento Prescrito</label>
                    <textarea id="tratamiento" name="tratamiento" placeholder="Medicamentos y procedimientos indicados"></textarea>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">💾 Registrar Hospitalización</button>
                    <button type="reset" class="btn btn-secondary">🔄 Limpiar</button>
                </div>
            </form>
        </div>

        <!-- Filtros -->
        <div class="card">
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <strong>Filtrar por estado:</strong>
                <a href="?estado=todas" class="btn <?= $filtro === 'todas' ? 'btn-primary' : 'btn-secondary' ?>">Todas</a>
                <a href="?estado=en_curso" class="btn <?= $filtro === 'en_curso' ? 'btn-warning' : 'btn-secondary' ?>">En Curso</a>
                <a href="?estado=alta_medica" class="btn <?= $filtro === 'alta_medica' ? 'btn-success' : 'btn-secondary' ?>">Alta Médica</a>
            </div>
        </div>

        <!-- Lista de Hospitalizaciones -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Historial de Hospitalizaciones</h2>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Paciente</th>
                            <th>Médico</th>
                            <th>Enfermedad</th>
                            <th>Habitación</th>
                            <th>Fecha Ingreso</th>
                            <th>Días</th>
                            <th>Costo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($hospitalizaciones->num_rows > 0): ?>
                            <?php while ($h = $hospitalizaciones->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($h['codigohospitalizacion']) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($h['nombrepaciente']) ?></strong><br>
                                        <small style="color: var(--text-light);">DUI: <?= htmlspecialchars($h['dui']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($h['nombreempleado']) ?><br>
                                        <small style="color: var(--text-light);"><?= htmlspecialchars($h['especialidad'] ?? 'General') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($h['nombreenfermedad']) ?></td>
                                    <td><strong><?= htmlspecialchars($h['numero_habitacion']) ?></strong></td>
                                    <td><?= formatDate($h['fecha_ingreso'], true) ?></td>
                                    <td>
                                        <span class="badge badge-info"><?= $h['dias_hospitalizacion'] ?> días</span>
                                    </td>
                                    <td>
                                        <?php if ($h['costo_estimado']): ?>
                                            $<?= number_format($h['costo_estimado'], 2) ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-light);">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = 'badge-';
                                        switch ($h['estado']) {
                                            case 'en_curso': $badgeClass .= 'warning'; break;
                                            case 'alta_medica': $badgeClass .= 'success'; break;
                                            case 'alta_voluntaria': $badgeClass .= 'info'; break;
                                            case 'referido': $badgeClass .= 'info'; break;
                                            case 'fallecido': $badgeClass .= 'danger'; break;
                                            default: $badgeClass .= 'secondary';
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= getEstadoHospitalizacion($h['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($h['estado'] === 'en_curso'): ?>
                                                <a href="?alta=<?= $h['codigohospitalizacion'] ?>" 
                                                   class="btn btn-success"
                                                   onclick="return confirm('¿Confirma dar de alta a este paciente?')">
                                                    ✓ Alta
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-info" onclick="verDetalle(<?= $h['codigohospitalizacion'] ?>)">👁️</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" style="text-align: center; color: var(--text-light); padding: 2rem;">
                                    No hay hospitalizaciones registradas
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
            alert('Ver detalle de hospitalización ID: ' + id);
            // Implementar modal con detalles completos
        }
    </script>
</body>
</html>