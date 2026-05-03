<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// ----- PROCESAR REGISTRO -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "registrar") {
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

// ----- PROCESAR ACTUALIZACION -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "actualizar") {
    $id = (int)$_POST["codigoenfermedad"];
    $nombre = sanitizeInput($conn, $_POST["nombreenfermedad"]);
    $desc = sanitizeInput($conn, $_POST["descripcion"]);
    $tipo = $_POST["tipo"];
    $gravedad = $_POST["nivel_gravedad"];
    $trat = sanitizeInput($conn, $_POST["tratamiento_recomendado"]);

    $stmt = $conn->prepare("UPDATE enfermedad SET nombreenfermedad=?, descripcion=?, tipo=?, nivel_gravedad=?, tratamiento_recomendado=? WHERE codigoenfermedad=?");
    $stmt->bind_param("sssssi", $nombre, $desc, $tipo, $gravedad, $trat, $id);
    echo $stmt->execute() ? "OK" : "Error";
    $stmt->close();
    exit;
}

// ----- PROCESAR ELIMINACION -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "eliminar") {
    $id = (int)$_POST["codigoenfermedad"];
    $stmt = $conn->prepare("DELETE FROM enfermedad WHERE codigoenfermedad=?");
    $stmt->bind_param("i", $id);
    echo $stmt->execute() ? "OK" : "Error";
    $stmt->close();
    exit;
}

$enfermedadesQuery = $conn->query("
    SELECT 
        e.*,
        COUNT(DISTINCT h.codigohospitalizacion) as total_casos
    FROM enfermedad e
    LEFT JOIN hospitalizacion h ON e.codigoenfermedad = h.codigoenfermedad
    GROUP BY e.codigoenfermedad
    ORDER BY e.nombreenfermedad
");
$enfermedadesList = [];
while($row = $enfermedadesQuery->fetch_assoc()) $enfermedadesList[] = $row;
?>
<?php
$pageTitle = "Catálogo de Enfermedades - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">🦠 Gestión de Patologías</h1>
    <button class="btn btn-primary" onclick="document.getElementById('formEnfermedad').scrollIntoView({behavior: 'smooth'})">
        <i data-lucide="plus"></i> Nueva Enfermedad
    </button>
</div>

<?php if($mensaje): ?>
    <div class="badge badge-<?= $tipoMensaje === 'success' ? 'success' : 'danger' ?>" style="display: block; padding: 1rem; margin-bottom: 1.5rem; text-align: left;">
        <i data-lucide="<?= $tipoMensaje === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 18px; vertical-align: middle; margin-right: 0.5rem;"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📋 Directorio de Patologías Registradas</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Enfermedad</th>
                        <th>Tipo / Clasificación</th>
                        <th>Nivel de Gravedad</th>
                        <th>Estadísticas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($enfermedadesList): foreach($enfermedadesList as $e): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($e['nombreenfermedad']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 300px;"><?= htmlspecialchars(substr($e['descripcion'], 0, 80)) ?>...</div>
                                </td>
                                <td><span class="badge badge-info"><?= htmlspecialchars($e['tipo']) ?></span></td>
                                <td>
                                    <?php
                                    $bg = 'badge-secondary';
                                    if($e['nivel_gravedad']=='leve') $bg='badge-success';
                                    elseif($e['nivel_gravedad']=='moderada') $bg='badge-warning';
                                    elseif($e['nivel_gravedad']=='grave' || $e['nivel_gravedad']=='critica') $bg='badge-danger';
                                    ?>
                                    <span class="badge <?= $bg ?>"><?= htmlspecialchars($e['nivel_gravedad']) ?></span>
                                </td>
                                <td><div style="font-weight: 600;"><?= $e['total_casos'] ?> casos</div></td>
                                <td>
                                    <button class="icon-btn" onclick="abrirEditar(<?= htmlspecialchars(json_encode($e)) ?>)" title="Editar">
                                        <i data-lucide="edit"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">No hay enfermedades registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="formEnfermedad">
        <div class="card-header"><h2 class="card-title">➕ Registrar Nueva Patología</h2></div>
        <form method="post">
            <input type="hidden" name="accion" value="registrar">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1.5rem;">
                <div class="form-group"><label class="form-label">Nombre *</label><input type="text" name="nombreenfermedad" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Clasificación *</label><select name="tipo" class="form-control" required><option value="infecciosa">Infecciosa</option><option value="cronica">Crónica</option><option value="aguda">Aguda</option><option value="degenerativa">Degenerativa</option><option value="otra">Otra</option></select></div>
                <div class="form-group"><label class="form-label">Gravedad *</label><select name="nivel_gravedad" class="form-control" required><option value="leve">Leve</option><option value="moderada">Moderada</option><option value="grave">Grave</option><option value="critica">Crítica</option></select></div>
            </div>
            <div class="form-group"><label class="form-label">Descripción</label><textarea name="descripcion" class="form-control" rows="2"></textarea></div>
            <div class="form-group"><label class="form-label">Tratamiento Base</label><textarea name="tratamiento_recomendado" class="form-control" rows="3"></textarea></div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;"><i data-lucide="save"></i> Guardar Patología</button>
        </form>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="editModal" style="display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 600px; max-width: 95%; margin: 0;">
        <div class="card-header">
            <h2 class="card-title">✏️ Editar Patología</h2>
            <button onclick="cerrarModal()" class="icon-btn"><i data-lucide="x"></i></button>
        </div>
        <form id="editForm">
            <input type="hidden" name="codigoenfermedad" id="editId">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group"><label class="form-label">Nombre</label><input id="editNombre" name="nombreenfermedad" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Clasificación</label><select id="editTipo" name="tipo" class="form-control"><option value="infecciosa">Infecciosa</option><option value="cronica">Crónica</option><option value="aguda">Aguda</option><option value="degenerativa">Degenerativa</option><option value="otra">Otra</option></select></div>
                <div class="form-group"><label class="form-label">Gravedad</label><select id="editGrav" name="nivel_gravedad" class="form-control"><option value="leve">Leve</option><option value="moderada">Moderada</option><option value="grave">Grave</option><option value="critica">Crítica</option></select></div>
            </div>
            <div class="form-group"><label class="form-label">Descripción</label><textarea id="editDesc" name="descripcion" class="form-control" rows="3"></textarea></div>
            <div class="form-group"><label class="form-label">Tratamiento Base</label><textarea id="editTrat" name="tratamiento_recomendado" class="form-control" rows="3"></textarea></div>
            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-primary" onclick="guardarCambios()" style="flex: 2;"><i data-lucide="save"></i> Guardar Cambios</button>
                <button type="button" class="btn btn-outline" style="border-color: var(--error); color: var(--error); flex: 1;" onclick="eliminarEnfermedad()"><i data-lucide="trash-2"></i> Eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('editModal');
    function abrirEditar(e){
        modal.style.display = 'flex';
        document.getElementById('editId').value = e.codigoenfermedad;
        document.getElementById('editNombre').value = e.nombreenfermedad;
        document.getElementById('editTipo').value = e.tipo;
        document.getElementById('editGrav').value = e.nivel_gravedad;
        document.getElementById('editDesc').value = e.descripcion;
        document.getElementById('editTrat').value = e.tratamiento_recomendado;
        lucide.createIcons();
    }
    function cerrarModal(){ modal.style.display='none'; }
    function guardarCambios(){
        const fd = new FormData(document.getElementById('editForm'));
        fd.append('accion', 'actualizar');
        fetch('enfermedades.php', {method:'POST', body:fd})
        .then(r => r.text()).then(t => { if(t.trim()==='OK') location.reload(); else alert(t); });
    }
    function eliminarEnfermedad(){
        if(confirm('¿Seguro que deseas eliminar esta patología?')){
            const fd = new FormData();
            fd.append('accion', 'eliminar');
            fd.append('codigoenfermedad', document.getElementById('editId').value);
            fetch('enfermedades.php', {method:'POST', body:fd})
            .then(r => r.text()).then(t => { if(t.trim()==='OK') location.reload(); else alert(t); });
        }
    }
</script>

<?php require_once("includes/footer.php"); ?>