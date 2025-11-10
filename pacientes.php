<?php
session_start();
require_once("includes/db.php"); // aquí ya está la función sanitizeInput
requireLogin();

$mensaje = '';
$tipoMensaje = '';

// Inicializamos arrays para evitar warnings
$pacientesArray = [];
$municipios = [];

// Obtener municipios
$resultMunicipios = $conn->query("SELECT * FROM municipio ORDER BY nombremunicipio ASC");
if ($resultMunicipios && $resultMunicipios->num_rows > 0) {
    while ($m = $resultMunicipios->fetch_assoc()) {
        $municipios[] = $m;
    }
}

// Procesar formulario de registro
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["nombrepaciente"])) {
    $nombrepaciente       = sanitizeInput($conn, $_POST["nombrepaciente"] ?? '');
    $dui                  = sanitizeInput($conn, $_POST["dui"] ?? '');
    $fechanacimiento      = $_POST["fechanacimiento"] ?? '';
    $genero               = sanitizeInput($conn, $_POST["genero"] ?? '');
    $direccion            = sanitizeInput($conn, $_POST["direccion"] ?? '');
    $telefono             = sanitizeInput($conn, $_POST["telefono"] ?? '');
    $correo               = sanitizeInput($conn, $_POST["correo"] ?? '');
    $codigomunicipio      = (int)($_POST["codigomunicipio"] ?? 0);
    $tipo_sangre          = sanitizeInput($conn, $_POST["tipo_sangre"] ?? '');
    $alergias             = sanitizeInput($conn, $_POST["alergias"] ?? '');
    $contacto_emergencia  = sanitizeInput($conn, $_POST["contacto_emergencia"] ?? '');
    $telefono_emergencia  = sanitizeInput($conn, $_POST["telefono_emergencia"] ?? '');

    // Validar DUI duplicado
    $checkDUI = $conn->prepare("SELECT codigopaciente FROM paciente WHERE dui = ?");
    $checkDUI->bind_param("s", $dui);
    $checkDUI->execute();
    $checkDUI->store_result();

    if ($checkDUI->num_rows > 0) {
        $mensaje = "Ya existe un paciente con ese DUI";
        $tipoMensaje = "error";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO paciente 
            (nombrepaciente, dui, fechanacimiento, genero, direccion, telefono, correo, codigomunicipio, tipo_sangre, alergias, contacto_emergencia, telefono_emergencia)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param(
                "sssssssissss",
                $nombrepaciente,
                $dui,
                $fechanacimiento,
                $genero,
                $direccion,
                $telefono,
                $correo,
                $codigomunicipio,
                $tipo_sangre,
                $alergias,
                $contacto_emergencia,
                $telefono_emergencia
            );
            if ($stmt->execute()) {
                $mensaje = "Paciente registrado exitosamente";
                $tipoMensaje = "success";
            } else {
                $mensaje = "Error al registrar paciente: " . $stmt->error;
                $tipoMensaje = "error";
            }
            $stmt->close();
        } else {
            $mensaje = "Error al preparar la consulta: " . $conn->error;
            $tipoMensaje = "error";
        }
    }
    $checkDUI->close();
}

// Obtener pacientes
$resultPacientes = $conn->query("
    SELECT 
        p.*,
        m.nombremunicipio,
        m.departamento,
        COUNT(DISTINCT h.codigohospitalizacion) AS total_hospitalizaciones
    FROM paciente p
    LEFT JOIN municipio m ON p.codigomunicipio = m.codigomunicipio
    LEFT JOIN hospitalizacion h ON p.codigopaciente = h.codigopaciente
    GROUP BY p.codigopaciente
    ORDER BY p.created_at DESC
");

if ($resultPacientes && $resultPacientes->num_rows > 0) {
    while ($p = $resultPacientes->fetch_assoc()) {
        $pacientesArray[] = $p;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes - Sistema Hospital</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <div class="container">
        <div class="logo">🏥 Sistema Hospital</div>
        <div class="user-info">
            <div class="avatar">👤</div>
            <div>
                <strong><?= htmlspecialchars($_SESSION['nombreempleado']) ?></strong><br>
                <small><?= getRolName($_SESSION['rol']) ?></small>
            </div>
        </div>
    </div>
</header>

<div class="container">
<nav>
    <a href="index.php">🏠 Dashboard</a>
    <a href="empleados.php">👨‍⚕️ Empleados</a>
    <a href="pacientes.php" class="active">🧑‍🦽 Pacientes</a>
    <a href="hospitalizaciones.php">🏥 Hospitalizaciones</a>
    <a href="enfermedades.php">🦠 Enfermedades</a>
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
    <div class="card-header"><h2>➕ Registrar Nuevo Paciente</h2></div>
    <form method="post">
        <!-- Nombre y DUI -->
        <div class="form-row">
            <div class="form-group">
                <label for="nombrepaciente">Nombre Completo *</label>
                <input type="text" id="nombrepaciente" name="nombrepaciente" placeholder="Ej: Juan Pérez" required>
            </div>
            <div class="form-group">
                <label for="dui">DUI *</label>
                <input type="text" id="dui" name="dui" placeholder="00000000-0" maxlength="10" required>
            </div>
        </div>
        <!-- Fecha, género y tipo sangre -->
        <div class="form-row">
            <div class="form-group">
                <label for="fechanacimiento">Fecha de Nacimiento *</label>
                <input type="date" id="fechanacimiento" name="fechanacimiento" required>
            </div>
            <div class="form-group">
                <label for="genero">Género *</label>
                <select id="genero" name="genero" required>
                    <option value="">Seleccione...</option>
                    <option value="M">Masculino</option>
                    <option value="F">Femenino</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
            <div class="form-group">
                <label for="tipo_sangre">Tipo de Sangre</label>
                <select id="tipo_sangre" name="tipo_sangre">
                    <option value="">Seleccione...</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                </select>
            </div>
        </div>
        <!-- Teléfono y correo -->
        <div class="form-row">
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" placeholder="0000-0000">
            </div>
            <div class="form-group">
                <label for="correo">Correo Electrónico</label>
                <input type="email" id="correo" name="correo" placeholder="correo@ejemplo.com">
            </div>
        </div>
        <!-- Dirección y municipio -->
        <div class="form-group">
            <label for="direccion">Dirección Completa *</label>
            <input type="text" id="direccion" name="direccion" placeholder="Colonia, calle, número" required>
        </div>
        <div class="form-group">
            <label for="codigomunicipio">Municipio *</label>
            <select id="codigomunicipio" name="codigomunicipio" required>
                <option value="">Seleccione un municipio...</option>
                <?php foreach ($municipios as $m): ?>
                    <option value="<?= $m['codigomunicipio'] ?>">
                        <?= htmlspecialchars($m['nombremunicipio']) ?> - <?= htmlspecialchars($m['departamento']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Alergias -->
        <div class="form-group">
            <label for="alergias">Alergias</label>
            <textarea id="alergias" name="alergias"></textarea>
        </div>
        <!-- Contacto de emergencia -->
        <h3>Contacto de Emergencia</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="contacto_emergencia">Nombre del Contacto</label>
                <input type="text" id="contacto_emergencia" name="contacto_emergencia">
            </div>
            <div class="form-group">
                <label for="telefono_emergencia">Teléfono de Emergencia</label>
                <input type="tel" id="telefono_emergencia" name="telefono_emergencia">
            </div>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">💾 Guardar Paciente</button>
            <button type="reset" class="btn btn-secondary">🔄 Limpiar</button>
        </div>
    </form>
</div>

<!-- Lista de Pacientes -->
<div class="card">
    <div class="card-header"><h2>📋 Lista de Pacientes</h2></div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Código</th><th>Nombre</th><th>DUI</th><th>Edad</th><th>Género</th><th>Sangre</th>
                    <th>Teléfono</th><th>Municipio</th><th>Hospitalizaciones</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pacientesArray)): ?>
                    <?php foreach ($pacientesArray as $p): 
                        $edad = date_diff(date_create($p['fechanacimiento']), date_create('today'))->y;
                    ?>
                    <tr>
                        <td><?= $p['codigopaciente'] ?></td>
                        <td><?= htmlspecialchars($p['nombrepaciente']) ?></td>
                        <td><?= htmlspecialchars($p['dui']) ?></td>
                        <td><?= $edad ?> años</td>
                        <td><?= htmlspecialchars($p['genero']) ?></td>
                        <td><?= $p['tipo_sangre'] ?: 'N/A' ?></td>
                        <td><?= htmlspecialchars($p['telefono']) ?></td>
                        <td><?= htmlspecialchars($p['nombremunicipio'] ?? 'N/A') ?></td>
                        <td><?= $p['total_hospitalizaciones'] ?></td>
                        <td>
                            <div class="btn-group">
                                <button onclick="verDetalle(<?= $p['codigopaciente'] ?>)">👁️</button>
                                <button onclick="abrirModal(<?= $p['codigopaciente'] ?>)">✏️</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" style="text-align:center;">No hay pacientes registrados</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modales de edición -->
<?php foreach ($pacientesArray as $p): ?>
<div id="modal-<?= $p['codigopaciente'] ?>" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModal(<?= $p['codigopaciente'] ?>)">&times;</span>
        <h3>Editar Paciente #<?= $p['codigopaciente'] ?></h3>
        <form method="post">
            <input type="hidden" name="codigopaciente" value="<?= $p['codigopaciente'] ?>">
            <label>Nombre *</label>
            <input type="text" name="nombrepaciente" value="<?= htmlspecialchars($p['nombrepaciente']) ?>" required>
            <label>DUI *</label>
            <input type="text" name="dui" value="<?= htmlspecialchars($p['dui']) ?>" required>
            <label>Fecha de Nacimiento *</label>
            <input type="date" name="fechanacimiento" value="<?= $p['fechanacimiento'] ?>" required>
            <label>Género *</label>
            <select name="genero" required>
                <option value="M" <?= $p['genero']=='M'?'selected':'' ?>>Masculino</option>
                <option value="F" <?= $p['genero']=='F'?'selected':'' ?>>Femenino</option>
                <option value="Otro" <?= $p['genero']=='Otro'?'selected':'' ?>>Otro</option>
            </select>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">💾 Guardar</button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal(<?= $p['codigopaciente'] ?>)">❌ Cancelar</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<style>
.modal {display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;}
.modal-content {background:#fff; border-radius:10px; padding:1.5rem; width:350px; max-width:90%; position:relative;}
.close {position:absolute; top:0.5rem; right:1rem; cursor:pointer; font-size:24px; font-weight:bold;}
</style>

<script>
function abrirModal(id){document.getElementById('modal-'+id).style.display='flex';}
function cerrarModal(id){document.getElementById('modal-'+id).style.display='none';}
window.onclick=function(e){if(e.target.classList.contains('modal')){e.target.style.display='none';}}
function verDetalle(id){alert('Funcionalidad de detalle - ID: '+id);}
</script>
</body>
</html>
