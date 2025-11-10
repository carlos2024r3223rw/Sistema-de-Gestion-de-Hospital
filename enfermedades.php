<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombreenfermedad = sanitizeInput($conn, $_POST["nombreenfermedad"]);
    $descripcion = sanitizeInput($conn, $_POST["descripcion"]);
    $tipo = $_POST["tipo"];
    $nivel_gravedad = $_POST["nivel_gravedad"];
    $tratamiento_recomendado = sanitizeInput($conn, $_POST["tratamiento_recomendado"]);

    $sql = "INSERT INTO enfermedad (nombreenfermedad, descripcion, tipo, nivel_gravedad, tratamiento_recomendado) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = executeQuery($conn, $sql, "sssss", [
        $nombreenfermedad, $descripcion, $tipo, $nivel_gravedad, $tratamiento_recomendado
    ]);

    if ($stmt) {
        $mensaje = "Enfermedad registrada exitosamente";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al registrar enfermedad";
        $tipoMensaje = "error";
    }
}

// Obtener enfermedades con estadísticas
$enfermedades = $conn->query("
    SELECT 
        e.*,
        COUNT(DISTINCT h.codigohospitalizacion) as total_casos
    FROM enfermedad e
    LEFT JOIN hospitalizacion h ON e.codigoenfermedad = h.codigoenfermedad
    GROUP BY e.codigoenfermedad
    ORDER BY e.nombreenfermedad
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enfermedades - Sistema Hospital</title>
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
            <a href="hospitalizaciones.php">🏥 Hospitalizaciones</a>
            <a href="enfermedades.php" class="active">🦠 Enfermedades</a>
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
                <h2>➕ Registrar Nueva Enfermedad</h2>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="nombreenfermedad">Nombre de la Enfermedad *</label>
                    <input type="text" id="nombreenfermedad" name="nombreenfermedad" 
                           placeholder="Ej: Diabetes Mellitus Tipo 2" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo">Tipo *</label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Seleccione...</option>
                            <option value="infecciosa">Infecciosa</option>
                            <option value="cronica">Crónica</option>
                            <option value="aguda">Aguda</option>
                            <option value="degenerativa">Degenerativa</option>
                            <option value="mental">Mental</option>
                            <option value="otra">Otra</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nivel_gravedad">Nivel de Gravedad *</label>
                        <select id="nivel_gravedad" name="nivel_gravedad" required>
                            <option value="">Seleccione...</option>
                            <option value="leve">Leve</option>
                            <option value="moderada">Moderada</option>
                            <option value="grave">Grave</option>
                            <option value="critica">Crítica</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" 
                              placeholder="Descripción general de la enfermedad"></textarea>
                </div>

                <div class="form-group">
                    <label for="tratamiento_recomendado">Tratamiento Recomendado</label>
                    <textarea id="tratamiento_recomendado" name="tratamiento_recomendado" 
                              placeholder="Tratamientos y medicamentos recomendados"></textarea>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">💾 Guardar Enfermedad</button>
                    <button type="reset" class="btn btn-secondary">🔄 Limpiar</button>
                </div>
            </form>
        </div>

        <!-- Lista de Enfermedades -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Catálogo de Enfermedades</h2>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Gravedad</th>
                            <th>Descripción</th>
                            <th>Casos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($enfermedades->num_rows > 0): ?>
                            <?php while ($e = $enfermedades->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($e['codigoenfermedad']) ?></td>
                                    <td><strong><?= htmlspecialchars($e['nombreenfermedad']) ?></strong></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?= htmlspecialchars(ucfirst($e['tipo'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = 'badge-';
                                        switch ($e['nivel_gravedad']) {
                                            case 'leve': $badgeClass .= 'success'; break;
                                            case 'moderada': $badgeClass .= 'warning'; break;
                                            case 'grave': $badgeClass .= 'danger'; break;
                                            case 'critica': $badgeClass .= 'danger'; break;
                                            default: $badgeClass .= 'secondary';
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars(ucfirst($e['nivel_gravedad'])) ?>
                                        </span>
                                    </td>
                                    <td style="max-width: 300px;">
                                        <?= htmlspecialchars(substr($e['descripcion'] ?? 'Sin descripción', 0, 100)) ?>
                                        <?= strlen($e['descripcion'] ?? '') > 100 ? '...' : '' ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary"><?= $e['total_casos'] ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-light); padding: 2rem;">
                                    No hay enfermedades registradas
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>