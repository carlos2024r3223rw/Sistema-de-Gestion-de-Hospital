<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

$codigocita = (int)$_GET['codigocita'];
$citaRes = $conn->query("SELECT c.*, p.nombrepaciente, p.fechanacimiento, p.genero FROM cita c JOIN paciente p ON c.codigopaciente=p.codigopaciente WHERE c.codigocita=$codigocita");
$cita = $citaRes->fetch_assoc();

if (!$cita) {
    header("Location: citas.php");
    exit;
}

// ----- PROCESAR TODO EL FLUJO -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "finalizar") {
    $conn->begin_transaction();
    try {
        $diagnostico = sanitizeInput($conn, $_POST["diagnostico"]);
        $observaciones = sanitizeInput($conn, $_POST["observaciones"]);
        
        // 1. Marcar cita como completada y guardar diagnóstico
        $conn->query("UPDATE cita SET estado='completada', motivo=CONCAT(motivo, ' | Diag: ', '$diagnostico') WHERE codigocita=$codigocita");

        // 2. Si hay medicinas, crear receta
        if (!empty($_POST['medicinas'])) {
            $stmtR = $conn->prepare("INSERT INTO receta (codigopaciente, codigoempleado) VALUES (?, ?)");
            $stmtR->bind_param("ii", $cita['codigopaciente'], $_SESSION['codigoempleado']);
            $stmtR->execute();
            $codigoreceta = $stmtR->insert_id;

            foreach ($_POST['medicinas'] as $i => $idMed) {
                if (!$idMed) continue;
                $cant = (int)$_POST['cantidades'][$i];
                $ind = sanitizeInput($conn, $_POST['indicaciones'][$i]);
                
                $stmtDet = $conn->prepare("INSERT INTO receta_detalle (codigoreceta, codigomedicina, cantidad, indicaciones) VALUES (?, ?, ?, ?)");
                $stmtDet->bind_param("iiis", $codigoreceta, $idMed, $cant, $ind);
                $stmtDet->execute();
                
                $conn->query("UPDATE medicina SET stock = stock - $cant WHERE codigomedicina = $idMed");
            }
        }

        // 3. Si hay exámenes, crear laboratorio
        if (!empty($_POST['examenes'])) {
            foreach ($_POST['examenes'] as $tipoEx) {
                if (!$tipoEx) continue;
                $tipoEx = sanitizeInput($conn, $tipoEx);
                $stmtL = $conn->prepare("INSERT INTO laboratorio (codigopaciente, codigoempleado, tipo_examen, fecha_examen, estado) VALUES (?, ?, ?, NOW(), 'pendiente')");
                $stmtL->bind_param("iis", $cita['codigopaciente'], $_SESSION['codigoempleado'], $tipoEx);
                $stmtL->execute();
            }
        }

        // 4. Programar próxima cita (opcional)
        if (!empty($_POST['proxima_fecha'])) {
            $proxFecha = $_POST['proxima_fecha'];
            $proxHora = $_POST['proxima_hora'];
            $proxMotivo = "Seguimiento: " . $diagnostico;
            $stmtProx = $conn->prepare("INSERT INTO cita (codigopaciente, codigoempleado, fecha_cita, hora_cita, motivo, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
            $stmtProx->bind_param("iisss", $cita['codigopaciente'], $_SESSION['codigoempleado'], $proxFecha, $proxHora, $proxMotivo);
            $stmtProx->execute();
        }

        // 5. Crear factura pendiente
        $costoConsulta = 25.00;
        $conn->query("INSERT INTO factura (codigopaciente, codigocita, monto_servicios, total, estado) VALUES ({$cita['codigopaciente']}, $codigocita, $costoConsulta, $costoConsulta, 'pendiente')");

        $conn->commit();
        header("Location: citas.php?mensaje=Atención finalizada con éxito&tipo=success");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $mensaje = "Error al procesar la atención: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

$medicinas = $conn->query("SELECT * FROM medicina WHERE stock > 0 ORDER BY nombremedicina");
$medicinasList = []; while($row = $medicinas->fetch_assoc()) $medicinasList[] = $row;
?>
<?php
$pageTitle = "Consulta Médica - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">🩺 Atención Médica: <?= htmlspecialchars($cita['nombrepaciente']) ?></h1>
    <a href="citas.php" class="btn btn-outline"><i data-lucide="arrow-left"></i> Volver</a>
</div>

<div style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem;">
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <form method="post" id="atencionForm">
            <input type="hidden" name="accion" value="finalizar">
            
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header"><h2 class="card-title"><i data-lucide="clipboard-list"></i> Evaluación y Diagnóstico</h2></div>
                <div class="form-group">
                    <label class="form-label">Diagnóstico Clínico *</label>
                    <textarea name="diagnostico" class="form-control" rows="3" required placeholder="Escriba el diagnóstico principal..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Observaciones Adicionales</label>
                    <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas sobre el estado físico, etc."></textarea>
                </div>
            </div>

            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 class="card-title"><i data-lucide="pill"></i> Prescribir Medicamentos</h2>
                    <button type="button" class="btn btn-outline" style="font-size: 0.7rem;" onclick="agregarMedicina()">+ Añadir</button>
                </div>
                <div id="medicinas-container"></div>
            </div>

            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 class="card-title"><i data-lucide="microscope"></i> Solicitar Exámenes de Laboratorio</h2>
                    <button type="button" class="btn btn-outline" style="font-size: 0.7rem;" onclick="agregarExamen()">+ Añadir</button>
                </div>
                <div id="examenes-container"></div>
            </div>

            <!-- SECCIÓN 4: PRÓXIMA CITA (NUEVA) -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header"><h2 class="card-title"><i data-lucide="calendar"></i> Programar Próxima Cita</h2></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Fecha Seguimiento</label>
                        <input type="date" name="proxima_fecha" class="form-control" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora</label>
                        <input type="time" name="proxima_hora" class="form-control">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1.5rem; font-size: 1.1rem;">
                <i data-lucide="check-circle-2"></i> Finalizar Consulta y Generar Orden de Cobro
            </button>
        </form>
    </div>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div class="card">
            <div class="card-header"><h2 class="card-title">👤 Perfil del Paciente</h2></div>
            <div style="font-size: 0.875rem;">
                <p><strong>Nombre:</strong> <?= htmlspecialchars($cita['nombrepaciente']) ?></p>
                <p><strong>Edad:</strong> <?= date_diff(date_create($cita['fechanacimiento']), date_create('today'))->y ?> años</p>
                <p><strong>Género:</strong> <?= $cita['genero'] == 'M' ? 'Masculino' : 'Femenino' ?></p>
                <hr style="margin: 1rem 0; border: none; border-top: 1px solid var(--border);">
                <p><strong>Motivo Cita:</strong><br><span style="color: var(--text-muted);"><?= htmlspecialchars($cita['motivo']) ?></span></p>
            </div>
        </div>
    </div>
</div>

<script>
    function agregarMedicina() {
        const container = document.getElementById('medicinas-container');
        const div = document.createElement('div');
        div.className = 'med-row';
        div.style = 'display: grid; grid-template-columns: 2fr 1fr 2fr 40px; gap: 1rem; margin-bottom: 1rem;';
        div.innerHTML = `
            <select name="medicinas[]" class="form-control" required>
                <option value="">Medicamento...</option>
                <?php foreach($medicinasList as $m): ?>
                    <option value="<?= $m['codigomedicina'] ?>"><?= htmlspecialchars($m['nombremedicina']) ?> (Stock: <?= $m['stock'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="cantidades[]" class="form-control" value="1" min="1">
            <input type="text" name="indicaciones[]" class="form-control" placeholder="Dosis/Indicaciones">
            <button type="button" class="icon-btn" style="color:var(--error)" onclick="this.parentElement.remove()"><i data-lucide="trash-2"></i></button>
        `;
        container.appendChild(div);
        lucide.createIcons();
    }

    function agregarExamen() {
        const container = document.getElementById('examenes-container');
        const div = document.createElement('div');
        div.style = 'display: flex; gap: 1rem; margin-bottom: 1rem;';
        div.innerHTML = `
            <input type="text" name="examenes[]" class="form-control" placeholder="Nombre del examen (ej: Hemograma Completo)" required style="flex:1">
            <button type="button" class="icon-btn" style="color:var(--error)" onclick="this.parentElement.remove()"><i data-lucide="trash-2"></i></button>
        `;
        container.appendChild(div);
        lucide.createIcons();
    }
</script>

<?php require_once("includes/footer.php"); ?>
