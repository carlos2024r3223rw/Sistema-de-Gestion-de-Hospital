<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// ----- PROCESAR REGISTRO -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "registrar") {
    $codigopaciente = (int)$_POST["codigopaciente"];
    $codigoempleado = (int)$_POST["codigoempleado"];
    $fecha = $_POST["fecha_cita"];
    $hora = $_POST["hora_cita"];
    $motivo = sanitizeInput($conn, $_POST["motivo"]);

    $sql = "INSERT INTO cita (codigopaciente, codigoempleado, fecha_cita, hora_cita, motivo) VALUES (?, ?, ?, ?, ?)";
    $stmt = executeQuery($conn, $sql, "iisss", [$codigopaciente, $codigoempleado, $fecha, $hora, $motivo]);

    if ($stmt) {
        $mensaje = "Cita programada exitosamente";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al programar cita";
        $tipoMensaje = "error";
    }
}

// ----- PROCESAR ACTUALIZACION (ESTADO) -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "actualizar") {
    $id = (int)$_POST["codigocita"];
    $estado = $_POST["estado"];
    $stmt = $conn->prepare("UPDATE cita SET estado=? WHERE codigocita=?");
    $stmt->bind_param("si", $estado, $id);
    echo $stmt->execute() ? "OK" : "Error";
    $stmt->close();
    exit;
}

// Obtener datos
$pacientes = $conn->query("SELECT codigopaciente, nombrepaciente, dui FROM paciente ORDER BY nombrepaciente");
$medicos = $conn->query("SELECT codigoempleado, nombreempleado FROM empleado WHERE rol='doctor' AND estado='activo'");
$citas = $conn->query("
    SELECT c.*, p.nombrepaciente, e.nombreempleado 
    FROM cita c 
    JOIN paciente p ON c.codigopaciente=p.codigopaciente 
    JOIN empleado e ON c.codigoempleado=e.codigoempleado 
    ORDER BY c.fecha_cita ASC, c.hora_cita ASC
");

$pacientesList = []; while($row = $pacientes->fetch_assoc()) $pacientesList[] = $row;
$medicosList = []; while($row = $medicos->fetch_assoc()) $medicosList[] = $row;
?>
<?php
$pageTitle = "Agenda de Citas - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">📅 Agenda de Citas Médicas</h1>
    <button class="btn btn-primary" onclick="document.getElementById('formCita').scrollIntoView({behavior: 'smooth'})">
        <i data-lucide="calendar-plus"></i> Nueva Cita
    </button>
</div>

<?php if($mensaje): ?>
    <div class="badge badge-<?= $tipoMensaje === 'success' ? 'success' : 'danger' ?>" style="display: block; padding: 1rem; margin-bottom: 1.5rem;">
        <i data-lucide="<?= $tipoMensaje === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 18px; vertical-align: middle; margin-right: 0.5rem;"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    <!-- LISTA DE CITAS -->
    <div class="card">
        <div class="card-header"><h2 class="card-title">📋 Próximas Citas</h2></div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($c = $citas->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600;"><?= date('d/m/Y', strtotime($c['fecha_cita'])) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= date('h:i A', strtotime($c['hora_cita'])) ?></div>
                        </td>
                        <td><?= htmlspecialchars($c['nombrepaciente']) ?></td>
                        <td>Dr. <?= htmlspecialchars($c['nombreempleado']) ?></td>
                        <td><div style="max-width: 200px; font-size: 0.875rem;"><?= htmlspecialchars($c['motivo']) ?></div></td>
                        <td>
                            <?php
                            $bg = 'badge-warning';
                            if($c['estado']=='completada') $bg='badge-success';
                            elseif($c['estado']=='cancelada') $bg='badge-danger';
                            ?>
                            <span class="badge <?= $bg ?>"><?= ucfirst($c['estado']) ?></span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <?php if($c['estado'] == 'pendiente'): ?>
                                <a href="atencion.php?codigocita=<?= $c['codigocita'] ?>" class="btn btn-primary" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;" title="Iniciar Consulta">
                                    <i data-lucide="stethoscope"></i> Atender
                                </a>
                                <?php endif; ?>
                                <select onchange="cambiarEstado(<?= $c['codigocita'] ?>, this.value)" class="form-control" style="font-size: 0.75rem; padding: 0.2rem; width: auto;">
                                    <option value="pendiente" <?= $c['estado']=='pendiente'?'selected':'' ?>>Pendiente</option>
                                    <option value="completada" <?= $c['estado']=='completada'?'selected':'' ?>>Completada</option>
                                    <option value="cancelada" <?= $c['estado']=='cancelada'?'selected':'' ?>>Cancelar</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FORMULARIO -->
    <div class="card" id="formCita">
        <div class="card-header"><h2 class="card-title">➕ Programar Nueva Cita</h2></div>
        <form method="post">
            <input type="hidden" name="accion" value="registrar">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Paciente *</label>
                    <select name="codigopaciente" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach($pacientesList as $p): ?>
                            <option value="<?= $p['codigopaciente'] ?>"><?= htmlspecialchars($p['nombrepaciente']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Médico *</label>
                    <select name="codigoempleado" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach($medicosList as $m): ?>
                            <option value="<?= $m['codigoempleado'] ?>">Dr. <?= htmlspecialchars($m['nombreempleado']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha *</label>
                    <input type="date" name="fecha_cita" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Hora *</label>
                    <input type="time" name="hora_cita" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Motivo de la Cita</label>
                <textarea name="motivo" class="form-control" rows="2" placeholder="Ej: Chequeo general, dolor abdominal..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;"><i data-lucide="save"></i> Programar Cita</button>
        </form>
    </div>
</div>

<script>
    function cambiarEstado(id, nuevoEstado){
        const fd = new FormData();
        fd.append('accion', 'actualizar');
        fd.append('codigocita', id);
        fd.append('estado', nuevoEstado);
        fetch('citas.php', {method:'POST', body:fd})
        .then(r => r.text()).then(t => { if(t.trim()==='OK') location.reload(); });
    }
</script>

<?php require_once("includes/footer.php"); ?>
