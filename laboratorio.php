<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// ----- PROCESAR REGISTRO -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "registrar") {
    $codigopaciente = (int)$_POST["codigopaciente"];
    $codigoempleado = $_SESSION['codigoempleado'];
    $tipo = sanitizeInput($conn, $_POST["tipo_examen"]);
    $fecha = $_POST["fecha_examen"];
    $resultado = sanitizeInput($conn, $_POST["resultado"]);

    $sql = "INSERT INTO laboratorio (codigopaciente, codigoempleado, tipo_examen, fecha_examen, resultado, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')";
    $stmt = executeQuery($conn, $sql, "iisss", [$codigopaciente, $codigoempleado, $tipo, $fecha, $resultado]);

    if ($stmt) {
        $mensaje = "Examen registrado correctamente";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al registrar examen";
        $tipoMensaje = "error";
    }
}

// ----- PROCESAR ACTUALIZACION (ESTADO) -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "completar") {
    $id = (int)$_POST["codigolab"];
    $stmt = $conn->prepare("UPDATE laboratorio SET estado='listo' WHERE codigolab=?");
    $stmt->bind_param("i", $id);
    echo $stmt->execute() ? "OK" : "Error";
    $stmt->close();
    exit;
}

$examenes = $conn->query("
    SELECT l.*, p.nombrepaciente 
    FROM laboratorio l 
    JOIN paciente p ON l.codigopaciente=p.codigopaciente 
    ORDER BY l.fecha_examen DESC
");

$pacientes = $conn->query("SELECT codigopaciente, nombrepaciente FROM paciente ORDER BY nombrepaciente");
$pacientesList = []; while($row = $pacientes->fetch_assoc()) $pacientesList[] = $row;
?>
<?php
$pageTitle = "Resultados de Laboratorio - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">🧪 Resultados de Laboratorio</h1>
    <button class="btn btn-primary" onclick="document.getElementById('formLab').scrollIntoView({behavior: 'smooth'})">
        <i data-lucide="plus"></i> Nuevo Examen
    </button>
</div>

<?php if($mensaje): ?>
    <div class="badge badge-<?= $tipoMensaje === 'success' ? 'success' : 'danger' ?>" style="display: block; padding: 1rem; margin-bottom: 1.5rem;">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    <div class="card">
        <div class="card-header"><h2 class="card-title">📋 Registro de Exámenes</h2></div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Examen</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($ex = $examenes->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($ex['fecha_examen'])) ?></td>
                        <td><strong><?= htmlspecialchars($ex['nombrepaciente']) ?></strong></td>
                        <td><?= htmlspecialchars($ex['tipo_examen']) ?></td>
                        <td>
                            <span class="badge <?= $ex['estado']=='listo'?'badge-success':'badge-warning' ?>">
                                <?= ucfirst($ex['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if($ex['estado'] == 'pendiente'): ?>
                                <button class="btn btn-outline" style="font-size: 0.7rem; padding: 0.3rem;" onclick="completarExamen(<?= $ex['codigolab'] ?>)">
                                    <i data-lucide="check"></i> Marcar Listo
                                </button>
                            <?php else: ?>
                                <button class="icon-btn" onclick="alert('Resultado: <?= str_replace(["\r", "\n"], " ", htmlspecialchars($ex['resultado'])) ?>')" title="Ver Resultado">
                                    <i data-lucide="eye"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="formLab">
        <div class="card-header"><h2 class="card-title">➕ Registrar Nuevo Examen</h2></div>
        <form method="post">
            <input type="hidden" name="accion" value="registrar">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
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
                    <label class="form-label">Tipo de Examen *</label>
                    <input type="text" name="tipo_examen" class="form-control" placeholder="Ej: Hemograma, Orina, Rayos X" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha del Examen *</label>
                    <input type="date" name="fecha_examen" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Resultado Preliminar u Observaciones</label>
                <textarea name="resultado" class="form-control" rows="3" placeholder="Detalles del examen..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;"><i data-lucide="save"></i> Registrar Examen</button>
        </form>
    </div>
</div>

<script>
    function completarExamen(id){
        if(confirm('¿Confirmar que el resultado está listo?')){
            const fd = new FormData();
            fd.append('accion', 'completar');
            fd.append('codigolab', id);
            fetch('laboratorio.php', {method:'POST', body:fd})
            .then(r => r.text()).then(t => { if(t.trim()==='OK') location.reload(); });
        }
    }
</script>

<?php require_once("includes/footer.php"); ?>
