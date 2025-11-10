<?php
session_start();
require_once("includes/db.php");

requireLogin();

$mensaje = '';
$tipoMensaje = '';

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombrepaciente = sanitizeInput($conn, $_POST["nombrepaciente"]);
    $dui = sanitizeInput($conn, $_POST["dui"]);
    $fechanacimiento = $_POST["fechanacimiento"];
    $genero = $_POST["genero"];
    $direccion = sanitizeInput($conn, $_POST["direccion"]);
    $telefono = sanitizeInput($conn, $_POST["telefono"]);
    $correo = sanitizeInput($conn, $_POST["correo"]);
    $codigomunicipio = (int)$_POST["codigomunicipio"];
    $tipo_sangre = $_POST["tipo_sangre"];
    $alergias = sanitizeInput($conn, $_POST["alergias"]);
    $contacto_emergencia = sanitizeInput($conn, $_POST["contacto_emergencia"]);
    $telefono_emergencia = sanitizeInput($conn, $_POST["telefono_emergencia"]);

    $sql = "INSERT INTO paciente (nombrepaciente, dui, fechanacimiento, genero, direccion, telefono, correo, codigomunicipio, tipo_sangre, alergias, contacto_emergencia, telefono_emergencia) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = executeQuery($conn, $sql, "sssssssisss", [
        $nombrepaciente, $dui, $fechanacimiento, $genero, $direccion, 
        $telefono, $correo, $codigomunicipio, $tipo_sangre, $alergias,
        $contacto_emergencia, $telefono_emergencia
    ]);

    if ($stmt) {
        $mensaje = "Paciente registrado exitosamente";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al registrar paciente";
        $tipoMensaje = "error";
    }
}

// Obtener municipios
$municipios = $conn->query("SELECT codigomunicipio, nombremunicipio, departamento FROM municipio ORDER BY nombremunicipio");

// Obtener pacientes con información completa
$pacientes = $conn->query("
    SELECT 
        p.*,
        m.nombremunicipio,
        m.departamento,
        COUNT(DISTINCT h.codigohospitalizacion) as total_hospitalizaciones
    FROM paciente p
    LEFT JOIN municipio m ON p.codigomunicipio = m.codigomunicipio
    LEFT JOIN hospitalizacion h ON p.codigopaciente = h.codigopaciente
    GROUP BY p.codigopaciente
    ORDER BY p.created_at DESC
");
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
            <div class="card-header">
                <h2>➕ Registrar Nuevo Paciente</h2>
            </div>
            
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombrepaciente">Nombre Completo *</label>
                        <input type="text" id="nombrepaciente" name="nombrepaciente" placeholder="Ej: Juan Carlos Pérez" required>
                    </div>

                    <div class="form-group">
                        <label for="dui">DUI *</label>
                        <input type="text" id="dui" name="dui" placeholder="000000000" maxlength="10" required>
                    </div>
                </div>

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

                <div class="form-group">
                    <label for="direccion">Dirección Completa *</label>
                    <input type="text" id="direccion" name="direccion" placeholder="Colonia, calle, número" required>
                </div>

                <div class="form-group">
                    <label for="codigomunicipio">Municipio *</label>
                    <select id="codigomunicipio" name="codigomunicipio" required>
                        <option value="">Seleccione un municipio...</option>
                        <?php while ($m = $municipios->fetch_assoc()): ?>
                            <option value="<?= $m['codigomunicipio'] ?>">
                                <?= htmlspecialchars($m['nombremunicipio']) ?> - <?= htmlspecialchars($m['departamento']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="alergias">Alergias</label>
                    <textarea id="alergias" name="alergias" placeholder="Liste las alergias conocidas del paciente"></textarea>
                </div>

                <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--text);">Contacto de Emergencia</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contacto_emergencia">Nombre del Contacto</label>
                        <input type="text" id="contacto_emergencia" name="contacto_emergencia" placeholder="Nombre completo">
                    </div>

                    <div class="form-group">
                        <label for="telefono_emergencia">Teléfono de Emergencia</label>
                        <input type="tel" id="telefono_emergencia" name="telefono_emergencia" placeholder="0000-0000">
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
            <div class="card-header">
                <h2>📋 Lista de Pacientes</h2>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>DUI</th>
                            <th>Edad</th>
                            <th>Género</th>
                            <th>Sangre</th>
                            <th>Teléfono</th>
                            <th>Municipio</th>
                            <th>Hospitalizaciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pacientes->num_rows > 0): ?>
                            <?php while ($p = $pacientes->fetch_assoc()): 
                                $edad = date_diff(date_create($p['fechanacimiento']), date_create('today'))->y;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['codigopaciente']) ?></td>
                                    <td><strong><?= htmlspecialchars($p['nombrepaciente']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['dui']) ?></td>
                                    <td><?= $edad ?> años</td>
                                    <td><?= htmlspecialchars($p['genero']) ?></td>
                                    <td>
                                        <?php if ($p['tipo_sangre']): ?>
                                            <span class="badge badge-info"><?= htmlspecialchars($p['tipo_sangre']) ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--text-light);">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($p['telefono']) ?></td>
                                    <td><?= htmlspecialchars($p['nombremunicipio'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge badge-secondary"><?= $p['total_hospitalizaciones'] ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-info" onclick="verDetalle(<?= $p['codigopaciente'] ?>)">👁️</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" style="text-align: center; color: var(--text-light); padding: 2rem;">
                                    No hay pacientes registrados
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function verDetalle(id) {
            alert('Funcionalidad de detalle - ID: ' + id);
        }
    </script>
</body>
</html>