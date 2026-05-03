<?php
session_start();
require_once("includes/db.php");
requireLogin();

$mensaje = '';
$tipoMensaje = '';

/* ────────────────────────────────
   🔹 OBTENER MUNICIPIOS
──────────────────────────────── */
$municipios = [];
$resultMunicipios = $conn->query("SELECT * FROM municipio ORDER BY nombremunicipio ASC");
while ($m = $resultMunicipios->fetch_assoc()) {
    $municipios[] = $m;
}

/* ────────────────────────────────
   🔹 PROCESAR REGISTRO DE PACIENTE
──────────────────────────────── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"]) && $_POST["accion"] === "registrar") {
    $nombre = sanitizeInput($conn, $_POST["nombrepaciente"]);
    $dui = sanitizeInput($conn, $_POST["dui"]);
    $fecha = $_POST["fechanacimiento"];
    $genero = sanitizeInput($conn, $_POST["genero"]);
    $direccion = sanitizeInput($conn, $_POST["direccion"]);
    $telefono = sanitizeInput($conn, $_POST["telefono"]);
    $correo = sanitizeInput($conn, $_POST["correo"]);
    $municipio = (int)$_POST["codigomunicipio"];
    $tipo_sangre = sanitizeInput($conn, $_POST["tipo_sangre"]);
    $alergias = sanitizeInput($conn, $_POST["alergias"]);
    $contacto = sanitizeInput($conn, $_POST["contacto_emergencia"]);
    $telEmergencia = sanitizeInput($conn, $_POST["telefono_emergencia"]);

    $check = $conn->prepare("SELECT codigopaciente FROM paciente WHERE dui=?");
    $check->bind_param("s", $dui);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $mensaje = "Ya existe un paciente con ese DUI";
        $tipoMensaje = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO paciente 
            (nombrepaciente, dui, fechanacimiento, genero, direccion, telefono, correo, codigomunicipio, tipo_sangre, alergias, contacto_emergencia, telefono_emergencia)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssissss", $nombre, $dui, $fecha, $genero, $direccion, $telefono, $correo, $municipio, $tipo_sangre, $alergias, $contacto, $telEmergencia);
        if ($stmt->execute()) {
            $mensaje = "Paciente registrado exitosamente";
            $tipoMensaje = "success";
        } else {
            $mensaje = "Error: " . $stmt->error;
            $tipoMensaje = "error";
        }
        $stmt->close();
    }
    $check->close();
}

// ----- ACTUALIZAR PACIENTE -----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"]) && $_POST["accion"] === "actualizar") {
    $id = (int)$_POST["codigopaciente"];
    $nombre = sanitizeInput($conn, $_POST["nombrepaciente"]);
    $dui = sanitizeInput($conn, $_POST["dui"]);
    $fecha = $_POST["fechanacimiento"];
    $genero = sanitizeInput($conn, $_POST["genero"]);
    $direccion = sanitizeInput($conn, $_POST["direccion"]);
    $telefono = sanitizeInput($conn, $_POST["telefono"]);
    $correo = sanitizeInput($conn, $_POST["correo"]);
    $alergias = sanitizeInput($conn, $_POST["alergias"]);
    $contacto = sanitizeInput($conn, $_POST["contacto_emergencia"]);
    $telEmergencia = sanitizeInput($conn, $_POST["telefono_emergencia"]);

    $sql = "UPDATE paciente SET 
            nombrepaciente=?, dui=?, fechanacimiento=?, genero=?, 
            direccion=?, telefono=?, correo=?, alergias=?, 
            contacto_emergencia=?, telefono_emergencia=? 
            WHERE codigopaciente=?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssi", $nombre, $dui, $fecha, $genero, $direccion, $telefono, $correo, $alergias, $contacto, $telEmergencia, $id);
    
    if ($stmt->execute()) {
        echo "OK";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    exit; // Importante para peticiones fetch
}

// ----- ELIMINAR PACIENTE -----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"]) && $_POST["accion"] === "eliminar") {
    $id = (int)$_POST["codigopaciente"];
    $stmt = $conn->prepare("DELETE FROM paciente WHERE codigopaciente=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "OK";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    exit;
}

/* ────────────────────────────────
   🔹 OBTENER PACIENTES
──────────────────────────────── */
$pacientes = [];
$q = $conn->query("SELECT p.*, m.nombremunicipio, COUNT(DISTINCT h.codigohospitalizacion) AS total_hospitalizaciones
                   FROM paciente p
                   LEFT JOIN municipio m ON p.codigomunicipio=m.codigomunicipio
                   LEFT JOIN hospitalizacion h ON p.codigopaciente=h.codigopaciente
                   GROUP BY p.codigopaciente ORDER BY p.created_at DESC");
while ($p = $q->fetch_assoc()) $pacientes[] = $p;
?>
<?php
$pageTitle = "Gestión de Pacientes - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">🧑‍🦽 Gestión de Pacientes</h1>
    <button class="btn btn-primary" onclick="document.getElementById('formRegistro').scrollIntoView({behavior: 'smooth'})">
        <i data-lucide="plus"></i> Nuevo Paciente
    </button>
</div>

<?php if($mensaje): ?>
    <div class="badge badge-<?= $tipoMensaje === 'success' ? 'success' : 'danger' ?>" style="display: block; padding: 1rem; margin-bottom: 1.5rem; text-align: left;">
        <i data-lucide="<?= $tipoMensaje === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 18px; vertical-align: middle; margin-right: 0.5rem;"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
    <!-- LISTA DE PACIENTES -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📋 Directorio de Pacientes</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th>DUI / Edad</th>
                        <th>Género / Sangre</th>
                        <th>Contacto</th>
                        <th>Municipio</th>
                        <th>Hosp.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($pacientes): foreach($pacientes as $p): 
                        $edad = date_diff(date_create($p['fechanacimiento']), date_create('today'))->y; ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($p['nombrepaciente']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Cod: #<?= $p['codigopaciente'] ?></div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($p['dui']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= $edad ?> años</div>
                            </td>
                            <td>
                                <span class="badge <?= $p['genero'] == 'M' ? 'badge-primary' : ($p['genero'] == 'F' ? 'badge-info' : 'badge-outline') ?>">
                                    <?= $p['genero'] ?>
                                </span>
                                <span class="badge badge-outline" style="border: 1px solid var(--border);"><?= $p['tipo_sangre'] ?: 'N/A' ?></span>
                            </td>
                            <td>
                                <div style="font-size: 0.875rem;"><i data-lucide="phone" style="width: 12px; height: 12px;"></i> <?= $p['telefono'] ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($p['correo']) ?></div>
                            </td>
                            <td><?= $p['nombremunicipio'] ?: 'N/A' ?></td>
                            <td><span class="badge badge-secondary"><?= $p['total_hospitalizaciones'] ?></span></td>
                            <td>
                                <button class="icon-btn" onclick="verDetalle(<?= htmlspecialchars(json_encode($p)) ?>)" title="Ver Detalles">
                                    <i data-lucide="eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="7" style="text-align:center;">No hay pacientes registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FORMULARIO REGISTRO -->
    <div class="card" id="formRegistro">
        <div class="card-header">
            <h2 class="card-title">➕ Registrar Nuevo Paciente</h2>
        </div>
        <form method="post">
            <input type="hidden" name="accion" value="registrar">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input name="nombrepaciente" class="form-control" placeholder="Ej. Juan Pérez" required>
                </div>
                <div class="form-group">
                    <label class="form-label">DUI *</label>
                    <input name="dui" class="form-control" maxlength="10" placeholder="00000000-0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha Nacimiento *</label>
                    <input type="date" name="fechanacimiento" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Género *</label>
                    <select name="genero" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de Sangre</label>
                    <select name="tipo_sangre" class="form-control">
                        <option value="">Seleccione...</option>
                        <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                        <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input name="telefono" class="form-control" placeholder="7777-7777">
                </div>
                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="correo" class="form-control" placeholder="paciente@ejemplo.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Municipio *</label>
                    <select name="codigomunicipio" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach($municipios as $m): ?>
                            <option value="<?=$m['codigomunicipio']?>"><?=$m['nombremunicipio']?> - <?=$m['departamento']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Dirección *</label>
                <input name="direccion" class="form-control" placeholder="Dirección completa" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Alergias</label>
                <textarea name="alergias" class="form-control" placeholder="Ej. Penicilina, Mariscos..."></textarea>
            </div>

            <h3 style="font-size: 1rem; margin: 1.5rem 0 1rem; color: var(--text-muted); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                🆘 Contacto de Emergencia
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Nombre del Contacto</label>
                    <input name="contacto_emergencia" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono de Emergencia</label>
                    <input name="telefono_emergencia" class="form-control">
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i data-lucide="save"></i> Registrar Paciente
                </button>
                <button type="reset" class="btn btn-outline" style="flex: 1;">
                    <i data-lucide="refresh-cw"></i> Limpiar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETALLE -->
<div id="detalleModal" style="display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 600px; max-width: 95%; max-height: 90vh; overflow-y:auto; margin: 0;">
        <div class="card-header">
            <h2 class="card-title">👁️ Detalle del Paciente</h2>
            <button onclick="cerrarDetalle()" class="icon-btn"><i data-lucide="x"></i></button>
        </div>
        <form id="detalleForm">
            <input type="hidden" name="codigopaciente" id="detalleId">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input id="detalleNombre" name="nombrepaciente" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">DUI</label>
                    <input id="detalleDui" name="dui" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha Nacimiento</label>
                    <input type="date" id="detalleFecha" name="fechanacimiento" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Género</label>
                    <select id="detalleGenero" name="genero" class="form-control">
                        <option value="M">M</option>
                        <option value="F">F</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input id="detalleTelefono" name="telefono" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Correo</label>
                    <input id="detalleCorreo" name="correo" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Dirección</label>
                <textarea id="detalleDireccion" name="direccion" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Alergias</label>
                <textarea id="detalleAlergias" name="alergias" class="form-control"></textarea>
            </div>
            <h4 style="font-size: 0.875rem; margin-bottom: 1rem; color: var(--text-muted);">🆘 Contacto de Emergencia</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input id="detalleContacto" name="contacto_emergencia" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input id="detalleTelEmergencia" name="telefono_emergencia" class="form-control">
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-primary" onclick="guardarCambios()" style="flex: 2;">
                    <i data-lucide="save"></i> Guardar Cambios
                </button>
                <button type="button" class="btn btn-outline" style="border-color: var(--error); color: var(--error); flex: 1;" onclick="eliminarPaciente()">
                    <i data-lucide="trash-2"></i> Eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('detalleModal');
    function verDetalle(p){
        modal.style.display='flex';
        document.getElementById('detalleId').value=p.codigopaciente;
        document.getElementById('detalleNombre').value=p.nombrepaciente;
        document.getElementById('detalleDui').value=p.dui;
        document.getElementById('detalleFecha').value=p.fechanacimiento;
        document.getElementById('detalleGenero').value=p.genero;
        document.getElementById('detalleTelefono').value=p.telefono;
        document.getElementById('detalleCorreo').value=p.correo;
        document.getElementById('detalleDireccion').value=p.direccion;
        document.getElementById('detalleAlergias').value=p.alergias;
        document.getElementById('detalleContacto').value=p.contacto_emergencia;
        document.getElementById('detalleTelEmergencia').value=p.telefono_emergencia;
        // Re-initialize icons in modal if needed
        lucide.createIcons();
    }
    function cerrarDetalle(){modal.style.display='none';}
    window.onclick=e=>{if(e.target==modal)cerrarDetalle();}

    function guardarCambios(){
        const formData=new FormData(document.getElementById('detalleForm'));
        formData.append('accion','actualizar');
        fetch('pacientes.php',{method:'POST',body:formData})
        .then(r=>r.text()).then(()=>location.reload());
    }

    function eliminarPaciente(){
        if(confirm('¿Seguro que deseas eliminar este paciente?')){
            const id=document.getElementById('detalleId').value;
            const fd=new FormData();
            fd.append('accion','eliminar');
            fd.append('codigopaciente',id);
            fetch('pacientes.php',{method:'POST',body:fd})
            .then(r=>r.text()).then(()=>location.reload());
        }
    }
</script>

<?php require_once("includes/footer.php"); ?>
