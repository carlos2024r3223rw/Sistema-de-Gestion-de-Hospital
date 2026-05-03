<?php
session_start();
require_once("includes/db.php");

requireLogin();

$nombreEmpleado = $_SESSION['nombreempleado'] ?? 'Usuario';
$rol = $_SESSION['rol'] ?? 'recepcionista';

$stats = [
    'pacientes' => $conn->query("SELECT COUNT(*) as total FROM paciente")->fetch_assoc()['total'],
    'hospitalizaciones_activas' => $conn->query("SELECT COUNT(*) as total FROM hospitalizacion WHERE estado = 'en_curso'")->fetch_assoc()['total'],
    'empleados' => $conn->query("SELECT COUNT(*) as total FROM empleado WHERE estado = 'activo'")->fetch_assoc()['total'],
    'enfermedades' => $conn->query("SELECT COUNT(*) as total FROM enfermedad")->fetch_assoc()['total']
];

$hospitalizacionesRecientes = $conn->query("
    SELECT 
        h.codigohospitalizacion,
        p.nombrepaciente,
        e.nombreempleado,
        enf.nombreenfermedad,
        h.fecha_ingreso,
        h.numero_habitacion,
        h.estado
    FROM hospitalizacion h
    INNER JOIN paciente p ON h.codigopaciente = p.codigopaciente
    INNER JOIN empleado e ON h.codigoempleado = e.codigoempleado
    INNER JOIN enfermedad enf ON h.codigoenfermedad = enf.codigoenfermedad
    WHERE h.estado = 'en_curso'
    ORDER BY h.fecha_ingreso DESC
    LIMIT 5
");

// Obtener pacientes recientes
$pacientesRecientes = $conn->query("
    SELECT 
        p.codigopaciente,
        p.nombrepaciente,
        p.dui,
        p.telefono,
        p.created_at
    FROM paciente p
    ORDER BY p.created_at DESC
    LIMIT 5
");
?>
<<?php
$pageTitle = "Dashboard - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="welcome-banner">
    <div class="welcome-content">
        <h1>¡Hola, <?php 
            $parts = explode(' ', $nombreEmpleado);
            if (in_array(strtolower($parts[0]), ['dr.', 'dra.', 'enf.'])) {
                echo htmlspecialchars($parts[0] . ' ' . ($parts[1] ?? ''));
            } else {
                echo htmlspecialchars($parts[0]);
            }
        ?>! 👋</h1>
        <p>Aquí tienes un resumen de lo que está sucediendo en el hospital hoy.</p>
    </div>
    <i data-lucide="hospital"></i>
</div>

<div class="stats-grid">
    <a href="pacientes.php" class="stat-card" style="text-decoration: none; color: inherit; display: flex; transition: transform 0.2s;">
        <div class="stat-icon" style="background-color: rgba(14, 165, 233, 0.1); color: var(--primary);">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= $stats['pacientes'] ?></span>
            <span class="stat-label">Pacientes</span>
        </div>
    </a>

    <a href="hospitalizaciones.php" class="stat-card" style="text-decoration: none; color: inherit; display: flex; transition: transform 0.2s;">
        <div class="stat-icon" style="background-color: rgba(99, 102, 241, 0.1); color: var(--secondary);">
            <i data-lucide="bed"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= $stats['hospitalizaciones_activas'] ?></span>
            <span class="stat-label">Hospitalizados</span>
        </div>
    </a>

    <a href="empleados.php" class="stat-card" style="text-decoration: none; color: inherit; display: flex; transition: transform 0.2s;">
        <div class="stat-icon" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success);">
            <i data-lucide="user-cog"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= $stats['empleados'] ?></span>
            <span class="stat-label">Staff Activo</span>
        </div>
    </a>

    <a href="enfermedades.php" class="stat-card" style="text-decoration: none; color: inherit; display: flex; transition: transform 0.2s;">
        <div class="stat-icon" style="background-color: rgba(244, 63, 94, 0.1); color: var(--accent);">
            <i data-lucide="activity"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= $stats['enfermedades'] ?></span>
            <span class="stat-label">Patologías</span>
        </div>
    </a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Hospitalizaciones Recientes -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🏥 Hospitalizaciones Activas</h2>
            <a href="hospitalizaciones.php" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">Ver todas</a>
        </div>
        
        <?php if ($hospitalizacionesRecientes && $hospitalizacionesRecientes->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Doctor</th>
                            <th>Habitación</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($h = $hospitalizacionesRecientes->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($h['nombrepaciente']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($h['nombreenfermedad']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($h['nombreempleado']) ?></td>
                                <td><span class="badge badge-info"><?= htmlspecialchars($h['numero_habitacion']) ?></span></td>
                                <td>
                                    <span class="badge badge-warning">
                                        <?= getEstadoHospitalizacion($h['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                <i data-lucide="calendar-x" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>No hay hospitalizaciones activas</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pacientes Recientes -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🆕 Pacientes Nuevos</h2>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php if ($pacientesRecientes && $pacientesRecientes->num_rows > 0): ?>
                <?php while ($p = $pacientesRecientes->fetch_assoc()): ?>
                    <div style="display: flex; align-items: center; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                        <div class="user-avatar" style="background-color: var(--bg-main); color: var(--primary); border: 1px solid var(--border);">
                            <i data-lucide="user" style="width: 18px;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 0.875rem; font-weight: 600;"><?= htmlspecialchars($p['nombrepaciente']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">DUI: <?= htmlspecialchars($p['dui']) ?></div>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-light);">
                            <?= date('d M', strtotime($p['created_at'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-muted);">Sin pacientes recientes</p>
            <?php endif; ?>
        </div>
        <a href="pacientes.php" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Gestionar Pacientes</a>
    </div>
</div>

<?php require_once("includes/footer.php"); ?>
ent);
                    }
                }, 30);
            });
        });
    </script>
</body>
</html>