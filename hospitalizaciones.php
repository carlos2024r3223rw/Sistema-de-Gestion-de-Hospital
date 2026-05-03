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

// ----- PROCESAR ACTUALIZACION -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "actualizar") {
    $id = (int)$_POST["codigohospitalizacion"];
    $diagnostico = sanitizeInput($conn, $_POST["diagnostico"]);
    $tratamiento = sanitizeInput($conn, $_POST["tratamiento"]);
    $habitacion = sanitizeInput($conn, $_POST["numero_habitacion"]);
    $costo = floatval($_POST["costo_estimado"]);

    $stmt = $conn->prepare("UPDATE hospitalizacion SET diagnostico=?, tratamiento=?, numero_habitacion=?, costo_estimado=? WHERE codigohospitalizacion=?");
    $stmt->bind_param("sssdi", $diagnostico, $tratamiento, $habitacion, $costo, $id);
    echo $stmt->execute() ? "OK" : "Error";
    $stmt->close();
    exit;
}

// ----- AJAX: OBTENER DETALLE -----
if (isset($_GET['get_detalle'])) {
    $id = (int)$_GET['get_detalle'];
    $res = $conn->query("SELECT h.*, p.nombrepaciente, e.nombreempleado, enf.nombreenfermedad 
                         FROM hospitalizacion h 
                         JOIN paciente p ON h.codigopaciente=p.codigopaciente 
                         JOIN empleado e ON h.codigoempleado=e.codigoempleado 
                         JOIN enfermedad enf ON h.codigoenfermedad=enf.codigoenfermedad 
                         WHERE h.codigohospitalizacion=$id");
    echo json_encode($res->fetch_assoc());
    exit;
}

// Dar de alta
if (isset($_GET['alta']) && $_GET['alta']) {
    $codigohospitalizacion = (int)$_GET['alta'];
    $sql = "UPDATE hospitalizacion SET fecha_alta = NOW(), estado = 'alta_medica' WHERE codigohospitalizacion = ?";
    $stmt = executeQuery($conn, $sql, "i", [$codigohospitalizacion]);
    
    if ($stmt) {
        header("Location: hospitalizaciones.php?mensaje=Paciente dado de alta exitosamente&tipo=success");
        exit;
    }
}

if(isset($_GET['mensaje'])){
    $mensaje = $_GET['mensaje'];
    $tipoMensaje = $_GET['tipo'];
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
        e.nombreempleado,
        enf.nombreenfermedad,
        DATEDIFF(COALESCE(h.fecha_alta, NOW()), h.fecha_ingreso) as dias_hospitalizacion
    FROM hospitalizacion h
    INNER JOIN paciente p ON h.codigopaciente = p.codigopaciente
    INNER JOIN empleado e ON h.codigoempleado = e.codigoempleado
    INNER JOIN enfermedad enf ON h.codigoenfermedad = enf.codigoenfermedad
    $whereClause
    ORDER BY h.fecha_ingreso DESC
");
?>
<?php
$pageTitle = "Hospitalizaciones - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">🏥 Gestión de Hospitalizaciones</h1>
    <button class="btn btn-primary" onclick="document.getElementById('formHosp').scrollIntoView({behavior: 'smooth'})">
        <i data-lucide="plus"></i> Nueva Hospitalización
    </button>
</div>

<?php if($mensaje): ?>
    <div class="badge badge-<?= $tipoMensaje === 'success' ? 'success' : 'danger' ?>" style="display: block; padding: 1rem; margin-bottom: 1.5rem; text-align: left;">
        <i data-lucide="<?= $tipoMensaje === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 18px; vertical-align: middle; margin-right: 0.5rem;"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div class="card" style="padding: 1rem; margin-bottom: 1.5rem;">
    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-muted);">Filtrar por:</span>
        <a href="?estado=todas" class="badge <?= $filtro === 'todas' ? 'badge-primary' : 'badge-outline' ?>" style="text-decoration:none;">Todas</a>
        <a href="?estado=en_curso" class="badge <?= $filtro === 'en_curso' ? 'badge-warning' : 'badge-outline' ?>" style="text-decoration:none;">En Curso</a>
        <a href="?estado=alta_medica" class="badge <?= $filtro === 'alta_medica' ? 'badge-success' : 'badge-outline' ?>" style="text-decoration:none;">Alta Médica</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📋 Registro de Pacientes Ingresados</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Paciente / Hab.</th>
                        <th>Médico / Diagnóstico</th>
                        <th>Fechas</th>
                        <th>Estancia</th>
                        <th>Costo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($hospitalizaciones->num_rows > 0): ?>
                        <?php while ($h = $hospitalizaciones->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($h['nombrepaciente']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        Hab: <span class="badge badge-outline" style="font-size: 0.7rem; padding: 0.1rem 0.4rem;"><?= htmlspecialchars($h['numero_habitacion']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem; font-weight: 500;">Dr. <?= htmlspecialchars($h['nombreempleado']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($h['nombreenfermedad']) ?></div>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem;">Ingreso: <?= date('d/m/Y', strtotime($h['fecha_ingreso'])) ?></div>
                                    <?php if($h['fecha_alta']): ?>
                                        <div style="font-size: 0.75rem; color: var(--success);">Alta: <?= date('d/m/Y', strtotime($h['fecha_alta'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-secondary"><?= $h['dias_hospitalizacion'] ?> días</span></td>
                                <td><div style="font-weight: 600;">$<?= number_format($h['costo_estimado'], 2) ?></div></td>
                                <td>
                                    <?php
                                    $badge = 'badge-info';
                                    if($h['estado']=='en_curso') $badge='badge-warning';
                                    elseif($h['estado']=='alta_medica') $badge='badge-success';
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= getEstadoHospitalizacion($h['estado']) ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <?php if ($h['estado'] === 'en_curso'): ?>
                                            <a href="?alta=<?= $h['codigohospitalizacion'] ?>" class="icon-btn" style="color: var(--success);" onclick="return confirm('¿Confirma dar de alta?')" title="Dar de Alta">
                                                <i data-lucide="check-square"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button class="icon-btn" onclick="abrirDetalle(<?= $h['codigohospitalizacion'] ?>)" title="Ver Detalle">
                                            <i data-lucide="info"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="formHosp">
        <div class="card-header"><h2 class="card-title">➕ Registrar Nueva Hospitalización</h2></div>
        <form method="post">
            <input type="hidden" name="accion" value="registrar">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Paciente *</label>
                    <select name="codigopaciente" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php while ($p = $pacientes->fetch_assoc()): ?>
                            <option value="<?= $p['codigopaciente'] ?>"><?= htmlspecialchars($p['nombrepaciente']) ?> (DUI: <?= $p['dui'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Médico Tratante *</label>
                    <select name="codigoempleado" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php while ($e = $empleados->fetch_assoc()): ?>
                            <option value="<?= $e['codigoempleado'] ?>">Dr. <?= htmlspecialchars($e['nombreempleado']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Enfermedad / Diagnóstico *</label>
                    <select name="codigoenfermedad" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php while ($enf = $enfermedades->fetch_assoc()): ?>
                            <option value="<?= $enf['codigoenfermedad'] ?>"><?= htmlspecialchars($enf['nombreenfermedad']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div><label class="form-label">Habitación *</label><input name="numero_habitacion" class="form-control" required></div>
                        <div><label class="form-label">Costo Est. ($)</label><input type="number" step="0.01" name="costo_estimado" class="form-control"></div>
                    </div>
                </div>
            </div>
            <div class="form-group"><label class="form-label">Motivo de Ingreso *</label><textarea name="motivo_ingreso" class="form-control" rows="2" required></textarea></div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group"><label class="form-label">Diagnóstico Inicial</label><textarea name="diagnostico" class="form-control" rows="3"></textarea></div>
                <div class="form-group"><label class="form-label">Tratamiento Prescrito</label><textarea name="tratamiento" class="form-control" rows="3"></textarea></div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;"><i data-lucide="save"></i> Registrar Ingreso</button>
        </form>
    </div>
</div>

<!-- MODAL DETALLE -->
<div id="detalleModal" style="display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 700px; max-width: 95%; max-height: 90vh; overflow-y:auto; margin: 0;">
        <div class="card-header">
            <h2 class="card-title">👁️ Detalle de Hospitalización</h2>
            <button onclick="cerrarModal()" class="icon-btn"><i data-lucide="x"></i></button>
        </div>
        <form id="editForm">
            <input type="hidden" name="codigohospitalizacion" id="detId">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; background: var(--bg-soft); padding: 1rem; border-radius: var(--radius);">
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Paciente</div>
                    <div id="detPaciente" style="font-weight: 600;"></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Médico</div>
                    <div id="detMedico" style="font-weight: 600;"></div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group"><label class="form-label">Habitación</label><input name="numero_habitacion" id="detHab" class="form-control"></div>
                <div class="form-group"><label class="form-label">Costo Actual ($)</label><input type="number" step="0.01" name="costo_estimado" id="detCosto" class="form-control"></div>
            </div>
            <div class="form-group"><label class="form-label">Diagnóstico</label><textarea name="diagnostico" id="detDiag" class="form-control" rows="4"></textarea></div>
            <div class="form-group"><label class="form-label">Tratamiento</label><textarea name="tratamiento" id="detTrat" class="form-control" rows="4"></textarea></div>
            <div style="margin-top: 1.5rem;"><button type="button" class="btn btn-primary" onclick="guardarCambios()" style="width:100%"><i data-lucide="save"></i> Guardar Cambios</button></div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('detalleModal');
    function abrirDetalle(id){
        fetch('hospitalizaciones.php?get_detalle=' + id)
        .then(r => r.json()).then(h => {
            document.getElementById('detId').value = h.codigohospitalizacion;
            document.getElementById('detPaciente').innerText = h.nombrepaciente;
            document.getElementById('detMedico').innerText = 'Dr. ' + h.nombreempleado;
            document.getElementById('detHab').value = h.numero_habitacion;
            document.getElementById('detCosto').value = h.costo_estimado;
            document.getElementById('detDiag').value = h.diagnostico;
            document.getElementById('detTrat').value = h.tratamiento;
            modal.style.display = 'flex';
            lucide.createIcons();
        });
    }
    function cerrarModal(){ modal.style.display='none'; }
    function guardarCambios(){
        const fd = new FormData(document.getElementById('editForm'));
        fd.append('accion', 'actualizar');
        fetch('hospitalizaciones.php', {method:'POST', body:fd})
        .then(r => r.text()).then(t => { if(t.trim()==='OK') location.reload(); else alert(t); });
    }
</script>

<?php require_once("includes/footer.php"); ?>