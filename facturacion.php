<?php
session_start();
require_once("includes/db.php");

requireLogin();
if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'recepcionista') {
    header("Location: index.php");
    exit;
}

$mensaje = '';
$tipoMensaje = '';

// ----- PROCESAR PAGO -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "pagar") {
    $id = (int)$_POST["codigofactura"];
    $metodo = $_POST["metodo_pago"];
    
    $stmt = $conn->prepare("UPDATE factura SET estado='pagada', metodo_pago=?, fecha_emision=NOW() WHERE codigofactura=?");
    $stmt->bind_param("si", $metodo, $id);
    
    if ($stmt->execute()) {
        $mensaje = "Factura pagada exitosamente ✅";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al procesar el pago";
        $tipoMensaje = "error";
    }
}

// Obtener facturas pendientes y pagadas
$pendientes = $conn->query("
    SELECT f.*, p.nombrepaciente, p.dui 
    FROM factura f 
    JOIN paciente p ON f.codigopaciente = p.codigopaciente 
    WHERE f.estado = 'pendiente'
    ORDER BY f.fecha_emision DESC
");

$pagadas = $conn->query("
    SELECT f.*, p.nombrepaciente 
    FROM factura f 
    JOIN paciente p ON f.codigopaciente = p.codigopaciente 
    WHERE f.estado = 'pagada'
    ORDER BY f.fecha_emision DESC LIMIT 10
");
?>
<?php
$pageTitle = "Módulo de Facturación - MediFlow";
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="card-header" style="margin-bottom: 2rem;">
    <h1 style="margin: 0;">💳 Gestión de Pagos y Facturación</h1>
</div>

<?php if($mensaje): ?>
    <div class="badge badge-<?= $tipoMensaje === 'success' ? 'success' : 'danger' ?>" style="display: block; padding: 1rem; margin-bottom: 1.5rem;">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    <!-- FACTURAS PENDIENTES -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title" style="color: var(--warning);">⏳ Cobros Pendientes</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Concepto</th>
                        <th>Monto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pendientes->num_rows > 0): while($f = $pendientes->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($f['fecha_emision'])) ?></td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($f['nombrepaciente']) ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">DUI: <?= $f['dui'] ?></div>
                        </td>
                        <td>
                            <?php if($f['codigocita']) echo "Consulta Médica"; elseif($f['codigohospitalizacion']) echo "Hospitalización"; ?>
                        </td>
                        <td style="font-weight:700; color:var(--primary);">$<?= number_format($f['total'], 2) ?></td>
                        <td>
                            <form method="post" style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="hidden" name="accion" value="pagar">
                                <input type="hidden" name="codigofactura" value="<?= $f['codigofactura'] ?>">
                                <select name="metodo_pago" class="form-control" style="font-size: 0.75rem; padding: 0.2rem;" required>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>
                                <button type="submit" class="btn btn-success" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;">
                                    Cobrar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 2rem; color:var(--text-muted);">No hay cobros pendientes</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ULTIMOS PAGOS -->
    <div class="card">
        <div class="card-header"><h2 class="card-title">✅ Últimos Pagos Recibidos</h2></div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha Pago</th>
                        <th>Paciente</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($f = $pagadas->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d/m/Y h:i A', strtotime($f['fecha_emision'])) ?></td>
                        <td><?= htmlspecialchars($f['nombrepaciente']) ?></td>
                        <td style="font-weight:600;">$<?= number_format($f['total'], 2) ?></td>
                        <td><span class="badge badge-info"><?= ucfirst($f['metodo_pago']) ?></span></td>
                        <td>
                            <button class="icon-btn" onclick="window.print()" title="Imprimir Recibo">
                                <i data-lucide="printer"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once("includes/footer.php"); ?>
