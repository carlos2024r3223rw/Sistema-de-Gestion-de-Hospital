<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// ----- PROCESAR NUEVA RECETA -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "crear") {
    $conn->begin_transaction();
    try {
        $codigopaciente = (int)$_POST["codigopaciente"];
        $codigoempleado = $_SESSION['codigoempleado'];
        
        // Crear cabecera de receta
        $stmt = $conn->prepare("INSERT INTO receta (codigopaciente, codigoempleado) VALUES (?, ?)");
        $stmt->bind_param("ii", $codigopaciente, $codigoempleado);
        $stmt->execute();
        $codigoreceta = $stmt->insert_id;

        // Procesar medicamentos
        $medicinas = $_POST['medicinas']; // Array de IDs
        $cantidades = $_POST['cantidades']; // Array de cantidades
        $indicaciones = $_POST['indicaciones']; // Array de indicaciones

        foreach ($medicinas as $i => $idMed) {
            $idMed = (int)$idMed;
            $cant = (int)$cantidades[$i];
            $ind = sanitizeInput($conn, $indicaciones[$i]);

            // Verificar stock
            $res = $conn->query("SELECT stock, nombremedicina FROM medicina WHERE codigomedicina=$idMed");
            $medData = $res->fetch_assoc();
            
            if ($medData['stock'] < $cant) {
                throw new Exception("Stock insuficiente para: " . $medData['nombremedicina']);
            }

            // Insertar detalle
            $stmtDet = $conn->prepare("INSERT INTO receta_detalle (codigoreceta, codigomedicina, cantidad, indicaciones) VALUES (?, ?, ?, ?)");
            $stmtDet->bind_param("iiis", $codigoreceta, $idMed, $cant, $ind);
            $stmtDet->execute();

            // Descontar stock
            $conn->query("UPDATE medicina SET stock = stock - $cant WHERE codigomedicina = $idMed");
        }

        $conn->commit();
        $mensaje = "Receta creada y stock actualizado correctamente ✅";
        $tipoMensaje = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $mensaje = "Error: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

// Obtener datos
$pacientes = $conn->query("SELECT codigopaciente, nombrepaciente FROM paciente ORDER BY nombrepaciente");
$medicinas = $conn->query("SELECT * FROM medicina WHERE stock > 0 ORDER BY nombremedicina");
$recetas = $conn->query("
    SELECT r.*, p.nombrepaciente, e.nombreempleado 
    FROM receta r 
    JOIN paciente p ON r.codigopaciente=p.codigopaciente 
    JOIN empleado e ON r.codigoempleado=e.codigoempleado 
    ORDER BY r.fecha_receta DESC
");

$pacientesList = []; while($row = $pacientes->fetch_assoc()) $pacientesList[] = $row;
$medicinasList = []; while($row = $medicinas->fetch_assoc()) $medicinasList[] = $row;
?>
<?php
$pageTitle = "Recetas Médicas - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">📝 Recetas Médicas</h1>
    <button class="btn btn-primary" onclick="document.getElementById('nuevaReceta').scrollIntoView({behavior: 'smooth'})">
        <i data-lucide="plus-circle"></i> Emitir Nueva Receta
    </button>
</div>

<?php if($mensaje): ?>
    <div class="badge badge-<?= $tipoMensaje === 'success' ? 'success' : 'danger' ?>" style="display: block; padding: 1rem; margin-bottom: 1.5rem;">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    <!-- LISTA DE RECETAS -->
    <div class="card">
        <div class="card-header"><h2 class="card-title">📋 Historial de Recetas</h2></div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Medicamentos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($r = $recetas->fetch_assoc()): 
                        $idR = $r['codigoreceta'];
                        $detalles = $conn->query("SELECT rd.*, m.nombremedicina FROM receta_detalle rd JOIN medicina m ON rd.codigomedicina=m.codigomedicina WHERE rd.codigoreceta=$idR");
                    ?>
                    <tr>
                        <td><?= date('d/m/Y h:i A', strtotime($r['fecha_receta'])) ?></td>
                        <td><strong><?= htmlspecialchars($r['nombrepaciente']) ?></strong></td>
                        <td>Dr. <?= htmlspecialchars($r['nombreempleado']) ?></td>
                        <td>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                <?php while($d = $detalles->fetch_assoc()) echo "• " . htmlspecialchars($d['nombremedicina']) . " (" . $d['cantidad'] . ")<br>"; ?>
                            </div>
                        </td>
                        <td>
                            <button class="icon-btn" onclick="window.print()" title="Imprimir Receta">
                                <i data-lucide="printer"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FORMULARIO NUEVA RECETA -->
    <div class="card" id="nuevaReceta">
        <div class="card-header"><h2 class="card-title">✍️ Emitir Receta Digital</h2></div>
        <form method="post" id="recetaForm">
            <input type="hidden" name="accion" value="crear">
            <div class="form-group" style="max-width: 400px;">
                <label class="form-label">Seleccionar Paciente *</label>
                <select name="codigopaciente" class="form-control" required>
                    <option value="">Buscar paciente...</option>
                    <?php foreach($pacientesList as $p): ?>
                        <option value="<?= $p['codigopaciente'] ?>"><?= htmlspecialchars($p['nombrepaciente']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="medicamentos-container" style="margin-top: 1.5rem;">
                <h3 style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">Medicamentos Recetados</h3>
                
                <!-- Fila de ejemplo/inicial -->
                <div class="med-row" style="display: grid; grid-template-columns: 2fr 1fr 2fr 40px; gap: 1rem; margin-bottom: 1rem; align-items: end;">
                    <div class="form-group">
                        <label class="form-label">Medicamento</label>
                        <select name="medicinas[]" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($medicinasList as $m): ?>
                                <option value="<?= $m['codigomedicina'] ?>"><?= htmlspecialchars($m['nombremedicina']) ?> (Stock: <?= $m['stock'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cant.</label>
                        <input type="number" name="cantidades[]" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Indicaciones</label>
                        <input type="text" name="indicaciones[]" class="form-control" placeholder="Ej: 1 cada 8 horas">
                    </div>
                    <button type="button" class="icon-btn" style="color: var(--error); margin-bottom: 0.5rem;" onclick="this.parentElement.remove()">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            </div>

            <button type="button" class="btn btn-outline" style="margin-bottom: 1.5rem;" onclick="agregarFila()">
                <i data-lucide="plus"></i> Añadir otro medicamento
            </button>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i data-lucide="file-check"></i> Finalizar y Descontar Stock
            </button>
        </form>
    </div>
</div>

<script>
    function agregarFila(){
        const container = document.getElementById('medicamentos-container');
        const firstRow = container.querySelector('.med-row');
        const newRow = firstRow.cloneNode(true);
        // Limpiar valores
        newRow.querySelector('select').value = '';
        newRow.querySelector('input[type="number"]').value = 1;
        newRow.querySelector('input[type="text"]').value = '';
        container.appendChild(newRow);
        lucide.createIcons();
    }
</script>

<style>
@media print {
    .sidebar, .btn, .icon-btn, .card-header, #nuevaReceta { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
    table { width: 100% !important; border-collapse: collapse; }
    th, td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; }
}
</style>

<?php require_once("includes/footer.php"); ?>
