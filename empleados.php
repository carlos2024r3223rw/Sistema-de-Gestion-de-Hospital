<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// ----- PROCESAR REGISTRO -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "registrar") {
    $nombre = sanitizeInput($conn, $_POST["nombre"]);
    $correo = sanitizeInput($conn, $_POST["correo"]);
    $rol = sanitizeInput($conn, $_POST["rol"]);
    $especialidad = sanitizeInput($conn, $_POST["especialidad"]);
    $telefono = sanitizeInput($conn, $_POST["telefono"]);

    $sql = "INSERT INTO empleado (nombreempleado, correoempleado, rol, especialidad, telefono) VALUES (?, ?, ?, ?, ?)";
    $stmt = executeQuery($conn, $sql, "sssss", [$nombre, $correo, $rol, $especialidad, $telefono]);

    if ($stmt) {
        $mensaje = "Empleado registrado exitosamente";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al registrar empleado";
        $tipoMensaje = "error";
    }
}

// ----- PROCESAR ACTUALIZACION -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "actualizar") {
    $id = (int)$_POST["codigoempleado"];
    $nombre = sanitizeInput($conn, $_POST["nombre"]);
    $correo = sanitizeInput($conn, $_POST["correo"]);
    $rol = sanitizeInput($conn, $_POST["rol"]);
    $especialidad = sanitizeInput($conn, $_POST["especialidad"]);
    $telefono = sanitizeInput($conn, $_POST["telefono"]);
    $estado = sanitizeInput($conn, $_POST["estado"]);

    $stmt = $conn->prepare("UPDATE empleado SET nombreempleado=?, correoempleado=?, rol=?, especialidad=?, telefono=?, estado=? WHERE codigoempleado=?");
    $stmt->bind_param("ssssssi", $nombre, $correo, $rol, $especialidad, $telefono, $estado, $id);
    echo $stmt->execute() ? "OK" : "Error";
    $stmt->close();
    exit;
}

// ----- PROCESAR ELIMINACION -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "eliminar") {
    $id = (int)$_POST["codigoempleado"];
    $stmt = $conn->prepare("DELETE FROM empleado WHERE codigoempleado=?");
    $stmt->bind_param("i", $id);
    echo $stmt->execute() ? "OK" : "Error";
    $stmt->close();
    exit;
}

$empleadosQuery = $conn->query("SELECT * FROM empleado ORDER BY nombreempleado");
$empleadosList = [];
while($row = $empleadosQuery->fetch_assoc()) $empleadosList[] = $row;
?>
<?php
$pageTitle = "Staff Médico - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">👨‍⚕️ Gestión de Personal</h1>
    <button class="btn btn-primary" onclick="document.getElementById('formEmpleado').scrollIntoView({behavior: 'smooth'})">
        <i data-lucide="user-plus"></i> Nuevo Empleado
    </button>
</div>

<?php if($mensaje): ?>
    <div class="badge badge-<?= $tipoMensaje === 'success' ? 'success' : 'danger' ?>" style="display: block; padding: 1rem; margin-bottom: 1.5rem; text-align: left;">
        <i data-lucide="<?= $tipoMensaje === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 18px; vertical-align: middle; margin-right: 0.5rem;"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    <!-- LISTA DE EMPLEADOS -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📋 Directorio de Staff</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Contacto</th>
                        <th>Rol / Especialidad</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($empleadosList): foreach($empleadosList as $e): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                            <?= strtoupper(substr($e['nombreempleado'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($e['nombreempleado']) ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">ID: #<?= $e['codigoempleado'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem;"><?= htmlspecialchars($e['correoempleado']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($e['telefono'] ?? 'Sin teléfono') ?></div>
                                </td>
                                <td>
                                    <div style="text-transform: capitalize; font-weight: 500;"><?= getRolNameHeader($e['rol']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($e['especialidad'] ?: 'Gral.') ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $e['estado'] == 'activo' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= ucfirst($e['estado']) ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.875rem; color: var(--text-muted);">
                                    <?= date('d/m/Y', strtotime($e['created_at'])) ?>
                                </td>
                                <td>
                                    <button class="icon-btn" onclick="abrirEditar(<?= htmlspecialchars(json_encode($e)) ?>)" title="Editar">
                                        <i data-lucide="edit-3"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                No hay empleados registrados en el sistema.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FORMULARIO REGISTRO -->
    <div class="card" id="formEmpleado">
        <div class="card-header">
            <h2 class="card-title">➕ Registrar Nuevo Miembro del Staff</h2>
        </div>
        <form method="post">
            <input type="hidden" name="accion" value="registrar">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Dr. Roberto Gómez" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Correo Institucional *</label>
                    <input type="email" name="correo" class="form-control" placeholder="staff@hospital.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Rol del Sistema *</label>
                    <select name="rol" class="form-control" required>
                        <option value="">Seleccione un rol...</option>
                        <option value="admin">Administrador</option>
                        <option value="doctor">Médico</option>
                        <option value="enfermera">Enfermería</option>
                        <option value="recepcionista">Recepción</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Especialidad (Si aplica)</label>
                    <input type="text" name="especialidad" class="form-control" placeholder="Ej: Cardiología">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" placeholder="7777-7777">
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i data-lucide="save"></i> Guardar Empleado
                </button>
                <button type="reset" class="btn btn-outline" style="flex: 1;">
                    <i data-lucide="refresh-cw"></i> Limpiar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="editModal" style="display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 600px; max-width: 95%; margin: 0;">
        <div class="card-header">
            <h2 class="card-title">✏️ Editar Empleado</h2>
            <button onclick="cerrarModal()" class="icon-btn"><i data-lucide="x"></i></button>
        </div>
        <form id="editForm">
            <input type="hidden" name="codigoempleado" id="editId">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input id="editNombre" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Correo</label>
                    <input id="editCorreo" name="correo" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Rol</label>
                    <select id="editRol" name="rol" class="form-control">
                        <option value="admin">Administrador</option>
                        <option value="doctor">Médico</option>
                        <option value="enfermera">Enfermería</option>
                        <option value="recepcionista">Recepción</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Especialidad</label>
                    <input id="editEspecialidad" name="especialidad" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input id="editTelefono" name="telefono" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="editEstado" name="estado" class="form-control">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-primary" onclick="guardarCambios()" style="flex: 2;">
                    <i data-lucide="save"></i> Guardar Cambios
                </button>
                <button type="button" class="btn btn-outline" style="border-color: var(--error); color: var(--error); flex: 1;" onclick="eliminarEmpleado()">
                    <i data-lucide="trash-2"></i> Eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('editModal');
    function abrirEditar(e){
        modal.style.display = 'flex';
        document.getElementById('editId').value = e.codigoempleado;
        document.getElementById('editNombre').value = e.nombreempleado;
        document.getElementById('editCorreo').value = e.correoempleado;
        document.getElementById('editRol').value = e.rol;
        document.getElementById('editEspecialidad').value = e.especialidad || '';
        document.getElementById('editTelefono').value = e.telefono || '';
        document.getElementById('editEstado').value = e.estado;
        lucide.createIcons();
    }
    function cerrarModal(){ modal.style.display = 'none'; }
    window.onclick = ev => { if(ev.target == modal) cerrarModal(); }

    function guardarCambios(){
        const fd = new FormData(document.getElementById('editForm'));
        fd.append('accion', 'actualizar');
        fetch('empleados.php', {method:'POST', body:fd})
        .then(r => r.text()).then(t => { if(t.trim()==='OK') location.reload(); else alert(t); });
    }

    function eliminarEmpleado(){
        if(confirm('¿Seguro que deseas eliminar este empleado?')){
            const fd = new FormData();
            fd.append('accion', 'eliminar');
            fd.append('codigoempleado', document.getElementById('editId').value);
            fetch('empleados.php', {method:'POST', body:fd})
            .then(r => r.text()).then(t => { if(t.trim()==='OK') location.reload(); else alert(t); });
        }
    }
</script>

<?php require_once("includes/footer.php"); ?>
