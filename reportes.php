<?php
session_start();
require_once("includes/db.php");

requireLogin();

// Estadísticas para reportes
$stats = [
    'total_pacientes' => $conn->query("SELECT COUNT(*) FROM paciente")->fetch_row()[0],
    'hospitalizados_actualmente' => $conn->query("SELECT COUNT(*) FROM hospitalizacion WHERE estado='en_curso'")->fetch_row()[0],
    'altas_mes' => $conn->query("SELECT COUNT(*) FROM hospitalizacion WHERE estado='alta_medica' AND MONTH(fecha_alta) = MONTH(CURRENT_DATE)")->fetch_row()[0],
    'stock_bajo_medicinas' => $conn->query("SELECT COUNT(*) FROM medicina WHERE stock < 10")->fetch_row()[0],
];

$topEnfermedades = $conn->query("
    SELECT enf.nombreenfermedad, COUNT(h.codigohospitalizacion) as total 
    FROM enfermedad enf 
    JOIN hospitalizacion h ON enf.codigoenfermedad=h.codigoenfermedad 
    GROUP BY enf.codigoenfermedad 
    ORDER BY total DESC LIMIT 5
");

$ocupacionHabitaciones = $conn->query("
    SELECT numero_habitacion, p.nombrepaciente, h.fecha_ingreso 
    FROM hospitalizacion h 
    JOIN paciente p ON h.codigopaciente=p.codigopaciente 
    WHERE h.estado='en_curso'
");

?>
<?php
$pageTitle = "Reportes y Análisis - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">📊 Reportes y Estadísticas</h1>
    <button class="btn btn-outline" onclick="window.print()">
        <i data-lucide="printer"></i> Imprimir Dashboard
    </button>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(14, 165, 233, 0.1); color: var(--primary);"><i data-lucide="users"></i></div>
        <div class="stat-value"><?= $stats['total_pacientes'] ?></div>
        <div class="stat-label">Total Pacientes</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);"><i data-lucide="bed"></i></div>
        <div class="stat-value"><?= $stats['hospitalizados_actualmente'] ?></div>
        <div class="stat-label">En Curso</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);"><i data-lucide="check-circle"></i></div>
        <div class="stat-value"><?= $stats['altas_mes'] ?></div>
        <div class="stat-label">Altas este Mes</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--error);"><i data-lucide="pill"></i></div>
        <div class="stat-value"><?= $stats['stock_bajo_medicinas'] ?></div>
        <div class="stat-label">Stock Crítico</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- TOP ENFERMEDADES -->
    <div class="card">
        <div class="card-header"><h2 class="card-title">🦠 Patologías más Frecuentes</h2></div>
        <div class="table-container">
            <table>
                <thead><tr><th>Enfermedad</th><th style="text-align:right;">Casos</th></tr></thead>
                <tbody>
                    <?php while($e = $topEnfermedades->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['nombreenfermedad']) ?></td>
                        <td style="text-align:right;"><strong><?= $e['total'] ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- OCUPACION -->
    <div class="card">
        <div class="card-header"><h2 class="card-title">🏨 Ocupación de Habitaciones</h2></div>
        <div class="table-container">
            <table>
                <thead><tr><th>Habitación</th><th>Paciente</th></tr></thead>
                <tbody>
                    <?php while($o = $ocupacionHabitaciones->fetch_assoc()): ?>
                    <tr>
                        <td><span class="badge badge-info"><?= htmlspecialchars($o['numero_habitacion']) ?></span></td>
                        <td><?= htmlspecialchars($o['nombrepaciente']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .card-header button, .header-actions, .header-search { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #eee !important; box-shadow: none !important; }
}
</style>

<?php require_once("includes/footer.php"); ?>
