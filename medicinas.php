<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// ----- PROCESAR REGISTRO -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "registrar") {
    $nombre = sanitizeInput($conn, $_POST["nombremedicina"]);
    $desc = sanitizeInput($conn, $_POST["descripcion"]);
    $tipo = $_POST["tipo"];
    $stock = (int)$_POST["stock"];
    $precio = floatval($_POST["precio"]);

    $sql = "INSERT INTO medicina (nombremedicina, descripcion, tipo, stock, precio) VALUES (?, ?, ?, ?, ?)";
    $stmt = executeQuery($conn, $sql, "sssid", [$nombre, $desc, $tipo, $stock, $precio]);

    if ($stmt) {
        $mensaje = "Medicina registrada exitosamente";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al registrar medicina";
        $tipoMensaje = "error";
    }
}

// ----- PROCESAR ACTUALIZACION -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "actualizar") {
    $id = (int)$_POST["codigomedicina"];
    $nombre = sanitizeInput($conn, $_POST["nombremedicina"]);
    $desc = sanitizeInput($conn, $_POST["descripcion"]);
    $tipo = $_POST["tipo"];
    $stock = (int)$_POST["stock"];
    $precio = floatval($_POST["precio"]);

    $stmt = $conn->prepare("UPDATE medicina SET nombremedicina=?, descripcion=?, tipo=?, stock=?, precio=? WHERE codigomedicina=?");
    $stmt->bind_param("sssidi", $nombre, $desc, $tipo, $stock, $precio, $id);
    echo $stmt->execute() ? "OK" : "Error";
    $stmt->close();
    exit;
}

// ----- PROCESAR ELIMINACION -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "eliminar") {
    $id = (int)$_POST["codigomedicina"];
    $stmt = $conn->prepare("DELETE FROM medicina WHERE codigomedicina=?");
    $stmt->bind_param("i", $id);
    echo $stmt->execute() ? "OK" : "Error";
    $stmt->close();
    exit;
}

$medicinasQuery = $conn->query("SELECT * FROM medicina ORDER BY nombremedicina ASC");
$medicinasList = [];
while($row = $medicinasQuery->fetch_assoc()) $medicinasList[] = $row;
?>
<?php
$pageTitle = "Farmacia e Inventario - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">💊 Gestión de Medicamentos</h1>
    <button class="btn btn-primary" onclick="document.getElementById('formMedicina').scrollIntoView({behavior: 'smooth'})">
        <i data-lucide="plus"></i> Nuevo Medicamento
    </button>
</div>

<?php if($mensaje): ?>
    <div class="badge badge-<?= $tipoMensaje === 'success' ? 'success' : 'danger' ?>" style="display: block; padding: 1rem; margin-bottom: 1.5rem; text-align: left;">
        <i data-lucide="<?= $tipoMensaje === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 18px; vertical-align: middle; margin-right: 0.5rem;"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    <!-- LISTA DE MEDICINAS -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📋 Inventario de Farmacia</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Tipo</th>
                        <th>Stock</th>
                        <th>Precio Unit.</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($medicinasList): foreach($medicinasList as $m): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($m['nombremedicina']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars(substr($m['descripcion'], 0, 50)) ?>...</div>
                                </td>
                                <td><span class="badge badge-info" style="text-transform: capitalize;"><?= $m['tipo'] ?></span></td>
                                <td>
                                    <div style="font-weight: 600; color: <?= $m['stock'] < 10 ? 'var(--error)' : 'inherit' ?>;">
                                        <?= $m['stock'] ?> unidades
                                    </div>
                                </td>
                                <td>$<?= number_format($m['precio'], 2) ?></td>
                                <td>
                                    <?php if($m['stock'] <= 0): ?>
                                        <span class="badge badge-danger">Agotado</span>
                                    <?php elseif($m['stock'] < 10): ?>
                                        <span class="badge badge-warning">Bajo Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Disponible</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="icon-btn" onclick="abrirEditar(<?= htmlspecialchars(json_encode($m)) ?>)" title="Editar">
                                        <i data-lucide="edit-2"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 2rem;">No hay medicamentos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FORMULARIO REGISTRO -->
    <div class="card" id="formMedicina">
        <div class="card-header">
            <h2 class="card-title">➕ Registrar Nuevo Medicamento</h2>
        </div>
        <form method="post">
            <input type="hidden" name="accion" value="registrar">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Nombre del Medicamento *</label>
                    <input type="text" name="nombremedicina" class="form-control" placeholder="Ej: Paracetamol 500mg" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Presentación *</label>
                    <select name="tipo" class="form-control" required>
                        <option value="pastilla">Pastilla</option>
                        <option value="jarabe">Jarabe</option>
                        <option value="inyeccion">Inyección</option>
                        <option value="pomada">Pomada</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Inicial *</label>
                    <input type="number" name="stock" class="form-control" value="0" min="0" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Precio Unitario ($) *</label>
                    <input type="number" step="0.01" name="precio" class="form-control" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="descripcion" class="form-control" placeholder="Uso, contraindicaciones...">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <i data-lucide="save"></i> Guardar Medicamento
            </button>
        </form>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="editModal" style="display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 500px; max-width: 95%; margin: 0;">
        <div class="card-header">
            <h2 class="card-title">✏️ Editar Medicamento</h2>
            <button onclick="cerrarModal()" class="icon-btn"><i data-lucide="x"></i></button>
        </div>
        <form id="editForm">
            <input type="hidden" name="codigomedicina" id="editId">
            <div class="form-group">
                <label class="form-label">Nombre del Medicamento</label>
                <input id="editNombre" name="nombremedicina" class="form-control" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Presentación</label>
                    <select id="editTipo" name="tipo" class="form-control">
                        <option value="pastilla">Pastilla</option>
                        <option value="jarabe">Jarabe</option>
                        <option value="inyeccion">Inyección</option>
                        <option value="pomada">Pomada</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Precio ($)</label>
                    <input type="number" step="0.01" id="editPrecio" name="precio" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Stock Actual</label>
                <input type="number" id="editStock" name="stock" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea id="editDesc" name="descripcion" class="form-control" rows="2"></textarea>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-primary" onclick="guardarCambios()" style="flex: 2;">
                    <i data-lucide="save"></i> Guardar Cambios
                </button>
                <button type="button" class="btn btn-outline" style="border-color: var(--error); color: var(--error); flex: 1;" onclick="eliminarMedicina()">
                    <i data-lucide="trash-2"></i> Eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('editModal');
    function abrirEditar(m){
        modal.style.display = 'flex';
        document.getElementById('editId').value = m.codigomedicina;
        document.getElementById('editNombre').value = m.nombremedicina;
        document.getElementById('editTipo').value = m.tipo;
        document.getElementById('editPrecio').value = m.precio;
        document.getElementById('editStock').value = m.stock;
        document.getElementById('editDesc').value = m.descripcion;
        lucide.createIcons();
    }
    function cerrarModal(){ modal.style.display = 'none'; }
    window.onclick = e => { if(e.target == modal) cerrarModal(); }

    function guardarCambios(){
        const fd = new FormData(document.getElementById('editForm'));
        fd.append('accion', 'actualizar');
        fetch('medicinas.php', {method:'POST', body:fd})
        .then(r => r.text()).then(t => { if(t.trim()==='OK') location.reload(); else alert(t); });
    }

    function eliminarMedicina(){
        if(confirm('¿Seguro que deseas eliminar este medicamento?')){
            const fd = new FormData();
            fd.append('accion', 'eliminar');
            fd.append('codigomedicina', document.getElementById('editId').value);
            fetch('medicinas.php', {method:'POST', body:fd})
            .then(r => r.text()).then(t => { if(t.trim()==='OK') location.reload(); else alert(t); });
        }
    }
</script>

<?php require_once("includes/footer.php"); ?>
