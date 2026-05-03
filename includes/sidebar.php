<?php
$current_page = basename($_SERVER['PHP_SELF']);
$nombreEmpleado = $_SESSION['nombreempleado'] ?? 'Usuario';
$rol = $_SESSION['rol'] ?? 'recepcionista';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">
                <i data-lucide="hospital"></i>
            </div>
            <span class="logo-text">MediFlow</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <span class="nav-label">Principal</span>
            <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <?php if ($rol !== 'recepcionista'): ?>
        <div class="nav-group">
            <span class="nav-label">Gestión Médica</span>
            <a href="pacientes.php" class="nav-link <?= $current_page == 'pacientes.php' ? 'active' : '' ?>">
                <i data-lucide="users"></i>
                <span>Pacientes</span>
            </a>
            <a href="expediente.php" class="nav-link <?= $current_page == 'expediente.php' ? 'active' : '' ?>">
                <i data-lucide="folder-heart"></i>
                <span>Expediente Clínico</span>
            </a>
            <a href="hospitalizaciones.php" class="nav-link <?= $current_page == 'hospitalizaciones.php' ? 'active' : '' ?>">
                <i data-lucide="bed"></i>
                <span>Hospitalizaciones</span>
            </a>
            <a href="citas.php" class="nav-link <?= $current_page == 'citas.php' ? 'active' : '' ?>">
                <i data-lucide="calendar"></i>
                <span>Citas Médicas</span>
            </a>
            <a href="laboratorio.php" class="nav-link <?= $current_page == 'laboratorio.php' ? 'active' : '' ?>">
                <i data-lucide="microscope"></i>
                <span>Laboratorio</span>
            </a>
            <a href="enfermedades.php" class="nav-link <?= $current_page == 'enfermedades.php' ? 'active' : '' ?>">
                <i data-lucide="activity"></i>
                <span>Enfermedades</span>
            </a>
            <a href="medicinas.php" class="nav-link <?= $current_page == 'medicinas.php' ? 'active' : '' ?>">
                <i data-lucide="pill"></i>
                <span>Medicinas</span>
            </a>
            <a href="recetas.php" class="nav-link <?= $current_page == 'recetas.php' ? 'active' : '' ?>">
                <i data-lucide="file-text"></i>
                <span>Recetas Médicas</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($rol === 'admin'): ?>
        <div class="nav-group">
            <span class="nav-label">Administración</span>
            <a href="empleados.php" class="nav-link <?= $current_page == 'empleados.php' ? 'active' : '' ?>">
                <i data-lucide="user-cog"></i>
                <span>Empleados</span>
            </a>
            <a href="municipios.php" class="nav-link <?= $current_page == 'municipios.php' ? 'active' : '' ?>">
                <i data-lucide="map-pin"></i>
                <span>Municipios</span>
            </a>
        </div>
        <?php endif; ?>

        <div class="nav-group">
            <span class="nav-label">Análisis</span>
            <a href="reportes.php" class="nav-link <?= $current_page == 'reportes.php' ? 'active' : '' ?>">
                <i data-lucide="file-bar-chart"></i>
                <span>Reportes</span>
            </a>
            <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
            <a href="facturacion.php" class="nav-link <?= $current_page == 'facturacion.php' ? 'active' : '' ?>">
                <i data-lucide="credit-card"></i>
                <span>Facturación</span>
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">
                <?= strtoupper(substr($nombreEmpleado, 0, 1)) ?>
            </div>
            <div class="user-details">
                <span class="user-name"><?= htmlspecialchars($nombreEmpleado) ?></span>
                <span class="user-role"><?= getRolNameHeader($rol) ?></span>
            </div>
        </div>
        <a href="logout.php" class="logout-btn" title="Cerrar Sesión">
            <i data-lucide="log-out"></i>
        </a>
    </div>
</aside>

<main class="main-content">
    <header class="content-header">
        <form action="pacientes.php" method="GET" class="header-search">
            <i data-lucide="search"></i>
            <input type="text" name="buscar" placeholder="Buscar paciente, doctor...">
        </form>
        <div class="header-actions">
            <button class="icon-btn" title="Notificaciones" onclick="alert('No hay notificaciones nuevas')">
                <i data-lucide="bell"></i>
                <span class="badge-dot"></span>
            </button>
            <a href="empleados.php" class="icon-btn" title="Ajustes">
                <i data-lucide="settings"></i>
            </a>
        </div>
    </header>
    <style>
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            cursor: pointer;
        }
        .header-search:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
    </style>
    <div class="content-body">
