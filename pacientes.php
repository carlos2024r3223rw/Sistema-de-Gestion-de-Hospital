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
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pacientes - Sistema Hospital</title>
<link rel="stylesheet" href="css/style.css">
<style>
.modal{display:none;position:fixed;z-index:999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;}
.modal-content{background:#fff;border-radius:10px;padding:1.5rem;width:500px;max-width:95%;max-height:90vh;overflow-y:auto;position:relative;}
.close{position:absolute;top:.5rem;right:1rem;cursor:pointer;font-size:24px;font-weight:bold;}
.table-container{overflow-x:auto;}
</style>
</head>
<body>
<header>
    <div class="container">
        <div class="logo">🏥 Sistema Hospital</div>
        <div class="user-info">
            <div class="avatar">👤</div>
            <div><strong><?=htmlspecialchars($_SESSION['nombreempleado'])?></strong><br>
            <small><?=getRolName($_SESSION['rol'])?></small></div>
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

<?php if($mensaje): ?>
<div class="alert alert-<?=$tipoMensaje?>">
<?= $tipoMensaje=="success"?"✓":"⚠" ?> <?=htmlspecialchars($mensaje)?></div>
<?php endif; ?>

<!-- FORMULARIO REGISTRO -->
<div class="card">
    <div class="card-header"><h2>➕ Registrar Nuevo Paciente</h2></div>
    <form method="post">
        <input type="hidden" name="accion" value="registrar">
        <div class="form-row">
            <div class="form-group">
                <label>Nombre *</label>
                <input name="nombrepaciente" required>
            </div>
            <div class="form-group">
                <label>DUI *</label>
                <input name="dui" maxlength="10" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Fecha Nacimiento *</label><input type="date" name="fechanacimiento" required></div>
            <div class="form-group"><label>Género *</label>
                <select name="genero" required><option value="">Seleccione...</option><option value="M">Masculino</option><option value="F">Femenino</option><option value="Otro">Otro</option></select>
            </div>
            <div class="form-group"><label>Tipo de Sangre</label>
                <select name="tipo_sangre"><option value="">Seleccione...</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option></select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Teléfono</label><input name="telefono"></div>
            <div class="form-group"><label>Correo</label><input type="email" name="correo"></div>
        </div>
        <div class="form-group"><label>Dirección *</label><input name="direccion" required></div>
        <div class="form-group">
            <label>Municipio *</label>
            <select name="codigomunicipio" required>
                <option value="">Seleccione...</option>
                <?php foreach($municipios as $m): ?>
                <option value="<?=$m['codigomunicipio']?>"><?=$m['nombremunicipio']?> - <?=$m['departamento']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Alergias</label><textarea name="alergias"></textarea></div>
        <h3>🆘 Contacto de Emergencia</h3>
        <div class="form-row">
            <div class="form-group"><label>Nombre</label><input name="contacto_emergencia"></div>
            <div class="form-group"><label>Teléfono</label><input name="telefono_emergencia"></div>
        </div>
        <div class="btn-group"><button class="btn btn-primary">💾 Guardar</button><button type="reset" class="btn btn-secondary">🔄 Limpiar</button></div>
    </form>
</div>

<!-- LISTA DE PACIENTES -->
<div class="card">
<div class="card-header"><h2>📋 Lista de Pacientes</h2></div>
<div class="table-container">
<table>
<thead>
<tr><th>Código</th><th>Nombre</th><th>DUI</th><th>Edad</th><th>Género</th><th>Sangre</th><th>Teléfono</th><th>Municipio</th><th>Alergias</th><th>Hospitalizaciones</th><th>Acciones</th></tr>
</thead><tbody>
<?php if($pacientes): foreach($pacientes as $p): 
$edad = date_diff(date_create($p['fechanacimiento']), date_create('today'))->y; ?>
<tr>
<td><?=$p['codigopaciente']?></td>
<td><?=htmlspecialchars($p['nombrepaciente'])?></td>
<td><?=htmlspecialchars($p['dui'])?></td>
<td><?=$edad?> años</td>
<td><?=$p['genero']?></td>
<td><?=$p['tipo_sangre']?:'N/A'?></td>
<td><?=$p['telefono']?></td>
<td><?=$p['nombremunicipio']?:'N/A'?></td>
<td><?=htmlspecialchars($p['alergias']?:'Ninguna')?></td>
<td><?=$p['total_hospitalizaciones']?></td>
<td><button onclick="verDetalle(<?=htmlspecialchars(json_encode($p))?>)">👁️</button></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="11" style="text-align:center;">No hay pacientes registrados</td></tr>
<?php endif; ?>
</tbody></table>
</div></div>
</div>

<!-- MODAL DETALLE -->
<div id="detalleModal" class="modal">
<div class="modal-content">
<span class="close" onclick="cerrarDetalle()">&times;</span>
<h3 id="detalleTitulo">👁️ Detalle del Paciente</h3>
<form id="detalleForm">
<input type="hidden" name="codigopaciente" id="detalleId">
<label>Nombre</label><input id="detalleNombre" name="nombrepaciente" required>
<label>DUI</label><input id="detalleDui" name="dui" required>
<label>Fecha Nacimiento</label><input type="date" id="detalleFecha" name="fechanacimiento" required>
<label>Género</label>
<select id="detalleGenero" name="genero"><option value="M">M</option><option value="F">F</option><option value="Otro">Otro</option></select>
<label>Teléfono</label><input id="detalleTelefono" name="telefono">
<label>Correo</label><input id="detalleCorreo" name="correo">
<label>Dirección</label><textarea id="detalleDireccion" name="direccion"></textarea>
<label>Alergias</label><textarea id="detalleAlergias" name="alergias"></textarea>
<h4>🆘 Contacto de Emergencia</h4>
<label>Nombre</label><input id="detalleContacto" name="contacto_emergencia">
<label>Teléfono</label><input id="detalleTelEmergencia" name="telefono_emergencia">
<div class="btn-group" style="margin-top:10px;">
<button type="button" class="btn btn-primary" onclick="guardarCambios()">💾 Guardar</button>
<button type="button" class="btn btn-danger" onclick="eliminarPaciente()">🗑️ Eliminar</button>
<button type="button" class="btn btn-secondary" onclick="cerrarDetalle()">❌ Cerrar</button>
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
}
function cerrarDetalle(){modal.style.display='none';}
window.onclick=e=>{if(e.target==modal)cerrarDetalle();}

/* 🔹 GUARDAR CAMBIOS */
function guardarCambios(){
 const formData=new FormData(document.getElementById('detalleForm'));
 formData.append('accion','actualizar');
 fetch('pacientes.php',{method:'POST',body:formData})
 .then(r=>r.text()).then(()=>location.reload());
}

/* 🔹 ELIMINAR PACIENTE */
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

<?php
/* ────────────────────────────────
   🔹 ACTUALIZAR O ELIMINAR PACIENTE (AJAX)
──────────────────────────────── */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['accion'])) {
    if ($_POST['accion']==='actualizar') {
        $id=(int)$_POST['codigopaciente'];
        $stmt=$conn->prepare("UPDATE paciente SET nombrepaciente=?, dui=?, fechanacimiento=?, genero=?, telefono=?, correo=?, direccion=?, alergias=?, contacto_emergencia=?, telefono_emergencia=? WHERE codigopaciente=?");
        $stmt->bind_param("ssssssssssi",
            $_POST['nombrepaciente'], $_POST['dui'], $_POST['fechanacimiento'], $_POST['genero'],
            $_POST['telefono'], $_POST['correo'], $_POST['direccion'], $_POST['alergias'],
            $_POST['contacto_emergencia'], $_POST['telefono_emergencia'], $id);
        $stmt->execute();
        exit;
    }
    if ($_POST['accion']==='eliminar') {
        $id=(int)$_POST['codigopaciente'];
        $conn->query("DELETE FROM paciente WHERE codigopaciente=$id");
        exit;
    }
}
?>
</body></html>
