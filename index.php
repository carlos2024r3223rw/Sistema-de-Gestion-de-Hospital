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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Hospital</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-banner::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-banner h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: none;
            -webkit-text-fill-color: white;
        }

        .welcome-banner p {
            font-size: 1.2rem;
            opacity: 0.95;
        }

        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 50%;
            transition: transform 0.3s ease;
        }

        .stat-card:hover::after {
            transform: scale(1.2);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-action-btn {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: var(--text);
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            border: 2px solid transparent;
        }

        .quick-action-btn:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .quick-action-btn .icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .quick-action-btn .text {
            font-weight: 600;
            color: var(--text);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .stat-card:hover .number {
            animation: pulse 0.6s ease;
        }
    </style>
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
                    <strong><?= htmlspecialchars($nombreEmpleado) ?></strong>
                    <br>
                    <small><?= getRolName($rol) ?></small>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Navigation -->
        <nav>
            <a href="index.php" class="active">🏠 Dashboard</a>
            <a href="empleados.php">👨‍⚕️ Empleados</a>
            <a href="pacientes.php">🧑‍🦽 Pacientes</a>
            <a href="hospitalizaciones.php">🏥 Hospitalizaciones</a>
            <a href="enfermedades.php">🦠 Enfermedades</a>
            <a href="municipios.php">📍 Municipios</a>
            <a href="logout.php">🚪 Salir</a>
        </nav>

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1>¡Bienvenido, <?= htmlspecialchars($nombreEmpleado) ?>!</h1>
                <p>Panel de control - Sistema de Gestión Hospitalaria</p>
            </div>
        </div>

        <div class="dashboard-cards">
            <div class="stat-card" style="border-left-color: #667eea;">
                <div class="icon">🧑‍🦽</div>
                <div class="number"><?= $stats['pacientes'] ?></div>
                <div class="label">Total Pacientes</div>
            </div>

            <div class="stat-card" style="border-left-color: #f093fb;">
                <div class="icon">🏥</div>
                <div class="number"><?= $stats['hospitalizaciones_activas'] ?></div>
                <div class="label">Hospitalizaciones Activas</div>
            </div>

            <div class="stat-card" style="border-left-color: #4facfe;">
                <div class="icon">👨‍⚕️</div>
                <div class="number"><?= $stats['empleados'] ?></div>
                <div class="label">Empleados Activos</div>
            </div>

            <div class="stat-card" style="border-left-color: #43e97b;">
                <div class="icon">🦠</div>
                <div class="number"><?= $stats['enfermedades'] ?></div>
                <div class="label">Enfermedades Registradas</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 style="margin-bottom: 1rem; color: var(--text);">⚡ Acciones Rápidas</h2>
        <div class="quick-actions">
            <a href="pacientes.php" class="quick-action-btn">
                <div class="icon">➕</div>
                <div class="text">Nuevo Paciente</div>
            </a>
            <a href="hospitalizaciones.php" class="quick-action-btn">
                <div class="icon">🏥</div>
                <div class="text">Nueva Hospitalización</div>
            </a>
            <a href="enfermedades.php" class="quick-action-btn">
                <div class="icon">🦠</div>
                <div class="text">Registrar Enfermedad</div>
            </a>
            <a href="empleados.php" class="quick-action-btn">
                <div class="icon">👨‍⚕️</div>
                <div class="text">Gestionar Empleados</div>
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>🏥 Hospitalizaciones Activas</h2>
                <a href="hospitalizaciones.php" class="btn btn-primary">Ver Todas →</a>
            </div>
            
            <?php if ($hospitalizacionesRecientes && $hospitalizacionesRecientes->num_rows > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Doctor</th>
                                <th>Enfermedad</th>
                                <th>Habitación</th>
                                <th>Fecha Ingreso</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($h = $hospitalizacionesRecientes->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($h['nombrepaciente']) ?></strong></td>
                                    <td><?= htmlspecialchars($h['nombreempleado']) ?></td>
                                    <td><?= htmlspecialchars($h['nombreenfermedad']) ?></td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($h['numero_habitacion']) ?></span></td>
                                    <td><?= formatDate($h['fecha_ingreso'], true) ?></td>
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
                <div class="empty-state">
                    <div class="empty-state-icon">🏥</div>
                    <p>No hay hospitalizaciones activas en este momento</p>
                    <a href="hospitalizaciones.php" class="btn btn-primary" style="margin-top: 1rem;">Registrar Nueva Hospitalización</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pacientes Recientes -->
        <div class="card">
            <div class="card-header">
                <h2>🧑‍🦽 Pacientes Registrados Recientemente</h2>
                <a href="pacientes.php" class="btn btn-primary">Ver Todos →</a>
            </div>
            
            <?php if ($pacientesRecientes && $pacientesRecientes->num_rows > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>DUI</th>
                                <th>Teléfono</th>
                                <th>Fecha Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = $pacientesRecientes->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['codigopaciente']) ?></td>
                                    <td><strong><?= htmlspecialchars($p['nombrepaciente']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['dui']) ?></td>
                                    <td><?= htmlspecialchars($p['telefono']) ?></td>
                                    <td><?= formatDate($p['created_at'], true) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🧑‍🦽</div>
                    <p>No hay pacientes registrados recientemente</p>
                    <a href="pacientes.php" class="btn btn-primary" style="margin-top: 1rem;">Registrar Nuevo Paciente</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Animación de números al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const numbers = document.querySelectorAll('.stat-card .number');
            numbers.forEach(number => {
                const target = parseInt(number.textContent);
                let current = 0;
                const increment = target / 30;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        number.textContent = target;
                        clearInterval(timer);
                    } else {
                        number.textContent = Math.floor(current);
                    }
                }, 30);
            });
        });
    </script>
</body>
</html>