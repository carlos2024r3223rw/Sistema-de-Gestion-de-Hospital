<?php
session_start();
require_once("includes/db.php");

requireLogin();

$paciente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$paciente = null;
$timeline = [];

if ($paciente_id > 0) {
    // Info Paciente
    $resP = $conn->query("SELECT p.*, m.nombremunicipio FROM paciente p LEFT JOIN municipio m ON p.codigomunicipio=m.codigomunicipio WHERE p.codigopaciente=$paciente_id");
    $paciente = $resP->fetch_assoc();

    if ($paciente) {
        // 1. Citas / Consultas
        $citas = $conn->query("SELECT fecha_cita as fecha, 'Consulta Médica' as tipo, motivo as detalle, 'calendar' as icon, 'primary' as color FROM cita WHERE codigopaciente=$paciente_id AND estado='completada'");
        while($row = $citas->fetch_assoc()) $timeline[] = $row;

        // 2. Laboratorio
        $labs = $conn->query("SELECT fecha_examen as fecha, 'Examen Laboratorio' as tipo, CONCAT(tipo_examen, ': ', COALESCE(resultado, 'Pendiente')) as detalle, 'microscope' as icon, 'warning' as color FROM laboratorio WHERE codigopaciente=$paciente_id");
        while($row = $labs->fetch_assoc()) $timeline[] = $row;

        // 3. Recetas
        $recetas = $conn->query("SELECT fecha_receta as fecha, 'Receta Médica' as tipo, 'Se emitio una nueva receta de medicamentos' as detalle, 'pill' as icon, 'success' as color FROM receta WHERE codigopaciente=$paciente_id");
        while($row = $recetas->fetch_assoc()) $timeline[] = $row;

        // 4. Hospitalizaciones
        $hosp = $conn->query("SELECT fecha_ingreso as fecha, 'Ingreso Hospitalario' as tipo, motivo_ingreso as detalle, 'bed' as icon, 'accent' as color FROM hospitalizacion WHERE codigopaciente=$paciente_id");
        while($row = $hosp->fetch_assoc()) $timeline[] = $row;

        // Ordenar timeline por fecha descendente
        usort($timeline, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });
    }
}

$todosPacientes = $conn->query("SELECT codigopaciente, nombrepaciente, dui FROM paciente ORDER BY nombrepaciente");
?>
<?php
$pageTitle = "Expediente Clínico - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">📂 Expediente Clínico Digital</h1>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header"><h2 class="card-title">🔍 Buscar Paciente</h2></div>
    <form method="GET" style="display: flex; gap: 1rem;">
        <select name="id" class="form-control" onchange="this.form.submit()" required>
            <option value="">Seleccione un paciente para ver su historial...</option>
            <?php while($p = $todosPacientes->fetch_assoc()): ?>
                <option value="<?= $p['codigopaciente'] ?>" <?= $paciente_id == $p['codigopaciente'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nombrepaciente']) ?> (DUI: <?= $p['dui'] ?>)
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-primary">Cargar Historial</button>
    </form>
</div>

<?php if ($paciente): ?>
<div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
    <!-- PERFIL IZQUIERDA -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div class="card" style="text-align: center; padding: 2rem;">
            <div class="user-avatar" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto 1rem;">
                <?= strtoupper(substr($paciente['nombrepaciente'], 0, 1)) ?>
            </div>
            <h2 style="margin: 0; font-size: 1.25rem;"><?= htmlspecialchars($paciente['nombrepaciente']) ?></h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">DUI: <?= $paciente['dui'] ?></p>
            <div class="badge badge-outline" style="margin-top: 0.5rem;">Expediente #<?= str_pad($paciente['codigopaciente'], 5, '0', STR_PAD_LEFT) ?></div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title" style="font-size: 1rem;">Datos Generales</h3></div>
            <div style="font-size: 0.875rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <div><strong>Nacimiento:</strong><br><?= date('d/m/Y', strtotime($paciente['fechanacimiento'])) ?></div>
                <div><strong>Género:</strong><br><?= $paciente['genero'] == 'M' ? 'Masculino' : 'Femenino' ?></div>
                <div><strong>Residencia:</strong><br><?= htmlspecialchars($paciente['nombremunicipio'] ?? 'No especificado') ?></div>
                <div><strong>Teléfono:</strong><br><?= htmlspecialchars($paciente['telefono']) ?></div>
            </div>
        </div>
    </div>

    <!-- LINEA DE TIEMPO -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🕒 Historial Médico (Línea de Tiempo)</h2>
        </div>
        
        <div class="timeline-container" style="padding: 1rem 0; position: relative;">
            <?php if (empty($timeline)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                    No hay registros históricos para este paciente todavía.
                </div>
            <?php else: ?>
                <div style="position: absolute; left: 27px; top: 0; bottom: 0; width: 2px; background: var(--border);"></div>
                
                <?php foreach ($timeline as $item): ?>
                <div style="display: flex; gap: 1.5rem; margin-bottom: 2rem; position: relative;">
                    <div style="width: 56px; height: 56px; background: white; border: 2px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 1; color: var(--<?= $item['color'] ?>);">
                        <i data-lucide="<?= $item['icon'] ?>"></i>
                    </div>
                    <div style="flex: 1; background: var(--bg-soft); padding: 1.25rem; border-radius: var(--radius); border-left: 4px solid var(--<?= $item['color'] ?>);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <h3 style="margin: 0; font-size: 1rem; font-weight: 600;"><?= $item['tipo'] ?></h3>
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;"><?= date('d M, Y', strtotime($item['fecha'])) ?></span>
                        </div>
                        <p style="margin: 0; font-size: 0.875rem; color: var(--text-main); line-height: 1.5;">
                            <?= htmlspecialchars($item['detalle']) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card" style="text-align: center; padding: 5rem;">
    <div style="font-size: 4rem; margin-bottom: 1rem;">📂</div>
    <h2>Seleccione un paciente para comenzar</h2>
    <p style="color: var(--text-muted);">Toda la historia médica del paciente aparecerá aquí en orden cronológico.</p>
</div>
<?php endif; ?>

<?php require_once("includes/footer.php"); ?>
