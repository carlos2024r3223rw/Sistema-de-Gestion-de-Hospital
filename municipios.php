<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// ----- PROCESAR REGISTRO -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "registrar") {
    $nombremunicipio = sanitizeInput($conn, $_POST["nombremunicipio"]);
    $departamento = sanitizeInput($conn, $_POST["departamento"]);

    $sql = "INSERT INTO municipio (nombremunicipio, departamento) VALUES (?, ?)";
    $stmt = executeQuery($conn, $sql, "ss", [$nombremunicipio, $departamento]);

    if ($stmt) {
        $mensaje = "Municipio registrado exitosamente";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al registrar municipio";
        $tipoMensaje = "error";
    }
}

// ----- PROCESAR ACTUALIZACION -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "actualizar") {
    $id = (int)$_POST["codigomunicipio"];
    $nombre = sanitizeInput($conn, $_POST["nombremunicipio"]);
    $depto = sanitizeInput($conn, $_POST["departamento"]);

    $stmt = $conn->prepare("UPDATE municipio SET nombremunicipio=?, departamento=? WHERE codigomunicipio=?");
    $stmt->bind_param("ssi", $nombre, $depto, $id);
    echo $stmt->execute() ? "OK" : "Error";
    $stmt->close();
    exit;
}

// ----- PROCESAR ELIMINACION -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "eliminar") {
    $id = (int)$_POST["codigomunicipio"];
    $stmt = $conn->prepare("DELETE FROM municipio WHERE codigomunicipio=?");
    $stmt->bind_param("i", $id);
    echo $stmt->execute() ? "OK" : "Error";
    $stmt->close();
    exit;
}

// Obtener municipios con estadísticas
$municipiosQuery = $conn->query("
    SELECT 
        m.*,
        COUNT(DISTINCT p.codigopaciente) as total_pacientes
    FROM municipio m
    LEFT JOIN paciente p ON m.codigomunicipio = p.codigomunicipio
    GROUP BY m.codigomunicipio
    ORDER BY m.departamento, m.nombremunicipio
");
$municipiosList = [];
while($row = $municipiosQuery->fetch_assoc()) $municipiosList[] = $row;
?>
<?php
$pageTitle = "Ubicaciones - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">📍 Gestión de Ubicaciones</h1>
    <button class="btn btn-primary" onclick="document.getElementById('formMunicipio').scrollIntoView({behavior: 'smooth'})">
        <i data-lucide="map-pin"></i> Nuevo Municipio
    </button>
</div>

<?php if($mensaje): ?>
    <div class="badge badge-<?= $tipoMensaje === 'success' ? 'success' : 'danger' ?>" style="display: block; padding: 1rem; margin-bottom: 1.5rem; text-align: left;">
        <i data-lucide="<?= $tipoMensaje === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 18px; vertical-align: middle; margin-right: 0.5rem;"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    <!-- LISTA DE MUNICIPIOS -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📋 Municipios y Departamentos</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Municipio</th>
                        <th>Departamento</th>
                        <th>Pacientes</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($municipiosList): foreach($municipiosList as $m): ?>
                            <tr>
                                <td><span class="badge badge-outline">#<?= htmlspecialchars($m['codigomunicipio']) ?></span></td>
                                <td><strong><?= htmlspecialchars($m['nombremunicipio']) ?></strong></td>
                                <td><?= htmlspecialchars($m['departamento']) ?></td>
                                <td>
                                    <span class="badge badge-info"><?= $m['total_pacientes'] ?> pacientes</span>
                                </td>
                                <td style="font-size: 0.875rem; color: var(--text-muted);">
                                    <?= date('d/m/Y', strtotime($m['created_at'])) ?>
                                </td>
                                <td>
                                    <button class="icon-btn" onclick="abrirEditar(<?= htmlspecialchars(json_encode($m)) ?>)" title="Editar">
                                        <i data-lucide="edit-2"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                No hay municipios registrados en el sistema.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FORMULARIO REGISTRO -->
    <div class="card" id="formMunicipio">
        <div class="card-header">
            <h2 class="card-title">➕ Registrar Nueva Ubicación</h2>
        </div>
        <form method="post">
            <input type="hidden" name="accion" value="registrar">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Nombre del Municipio *</label>
                    <input type="text" name="nombremunicipio" class="form-control" placeholder="Ej: Santa Tecla" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Departamento *</label>
                    <select name="departamento" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <option value="Ahuachapán">Ahuachapán</option>
                        <option value="Cabañas">Cabañas</option>
                        <option value="Chalatenango">Chalatenango</option>
                        <option value="Cuscatlán">Cuscatlán</option>
                        <option value="La Libertad">La Libertad</option>
                        <option value="La Paz">La Paz</option>
                        <option value="La Unión">La Unión</option>
                        <option value="Morazán">Morazán</option>
                        <option value="San Miguel">San Miguel</option>
                        <option value="San Salvador">San Salvador</option>
                        <option value="San Vicente">San Vicente</option>
                        <option value="Santa Ana">Santa Ana</option>
                        <option value="Sonsonate">Sonsonate</option>
                        <option value="Usulután">Usulután</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i data-lucide="save"></i> Guardar Municipio
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
    <div class="card" style="width: 500px; max-width: 95%; margin: 0;">
        <div class="card-header">
            <h2 class="card-title">✏️ Editar Municipio</h2>
            <button onclick="cerrarModal()" class="icon-btn"><i data-lucide="x"></i></button>
        </div>
        <form id="editForm">
            <input type="hidden" name="codigomunicipio" id="editId">
            <div class="form-group">
                <label class="form-label">Nombre del Municipio</label>
                <input id="editNombre" name="nombremunicipio" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Departamento</label>
                <select id="editDepto" name="departamento" class="form-control" required>
                    <?php 
                    $deptos = ["Ahuachapán", "Cabañas", "Chalatenango", "Cuscatlán", "La Libertad", "La Paz", "La Unión", "Morazán", "San Miguel", "San Salvador", "San Vicente", "Santa Ana", "Sonsonate", "Usulután"];
                    foreach($deptos as $d) echo "<option value='$d'>$d</option>";
                    ?>
                </select>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-primary" onclick="guardarCambios()" style="flex: 2;">
                    <i data-lucide="save"></i> Guardar Cambios
                </button>
                <button type="button" class="btn btn-outline" style="border-color: var(--error); color: var(--error); flex: 1;" onclick="eliminarMunicipio()">
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
        document.getElementById('editId').value = m.codigomunicipio;
        document.getElementById('editNombre').value = m.nombremunicipio;
        document.getElementById('editDepto').value = m.departamento;
        lucide.createIcons();
    }
    function cerrarModal(){ modal.style.display = 'none'; }
    window.onclick = e => { if(e.target == modal) cerrarModal(); }

    function guardarCambios(){
        const fd = new FormData(document.getElementById('editForm'));
        fd.append('accion', 'actualizar');
        fetch('municipios.php', {method:'POST', body:fd})
        .then(r => r.text()).then(t => { if(t.trim()==='OK') location.reload(); else alert(t); });
    }

    function eliminarMunicipio(){
        if(confirm('¿Seguro que deseas eliminar este municipio?')){
            const fd = new FormData();
            fd.append('accion', 'eliminar');
            fd.append('codigomunicipio', document.getElementById('editId').value);
            fetch('municipios.php', {method:'POST', body:fd})
            .then(r => r.text()).then(t => { if(t.trim()==='OK') location.reload(); else alert(t); });
        }
    }
</script>

<?php require_once("includes/footer.php"); ?>