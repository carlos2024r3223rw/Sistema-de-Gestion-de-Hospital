<?php
session_start();
include("includes/db.php");

// ----- LOGIN -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $correo = $_POST["correo"];
    $clave = $_POST["clave"];

    $sql = "SELECT * FROM empleado WHERE correoempleado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $empleado = $result->fetch_assoc();
        if (password_verify($clave, $empleado['clave'])) {
            $_SESSION['codigoempleado'] = $empleado['codigoempleado'];
            $_SESSION['nombreempleado'] = $empleado['nombreempleado'];
            $_SESSION['rol'] = $empleado['rol'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado";
    }
}

// ----- CAMBIO DE CONTRASEÑA -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset"])) {
    $correo = $_POST["correo_reset"];
    $nueva_clave = $_POST["nueva_clave"];
    $confirmar_clave = $_POST["confirmar_clave"];

    if ($nueva_clave === $confirmar_clave) {
        $hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
        $sql = "UPDATE empleado SET clave = ? WHERE correoempleado = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hash, $correo);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $success = "Contraseña actualizada correctamente ✅";
        } else {
            $error = "No se encontró una cuenta con ese correo.";
        }
    } else {
        $error = "Las contraseñas no coinciden.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - MediFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <div class="logo-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                    <i data-lucide="hospital"></i>
                </div>
            </div>
            <h1 class="login-title">Bienvenido</h1>
            <p class="login-subtitle">Ingresa tus credenciales para acceder</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="badge badge-danger" style="display: block; text-align: center; padding: 0.75rem; margin-bottom: 1.5rem; border-radius: var(--radius);">
                <i data-lucide="alert-circle" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 0.5rem;"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="badge badge-success" style="display: block; text-align: center; padding: 0.75rem; margin-bottom: 1.5rem; border-radius: var(--radius);">
                <i data-lucide="check-circle" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 0.5rem;"></i>
                <?= $success ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="login" value="1">
            <div class="form-group">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="correo" class="form-control" placeholder="ejemplo@hospital.com" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" name="clave" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.875rem;">
                Iniciar Sesión
            </button>
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="#" id="openModal" style="font-size: 0.875rem; color: var(--primary); font-weight: 500;">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
        </form>
    </div>

    <!-- MODAL PARA CAMBIAR CONTRASEÑA -->
    <div id="resetModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000; backdrop-filter: blur(4px);">
        <div class="card" style="width: 90%; max-width: 400px; margin-bottom: 0;">
            <div class="card-header">
                <h3 class="card-title">Restablecer Contraseña</h3>
                <button id="closeModal" style="background:none; border:none; cursor:pointer; color:var(--text-muted);">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="post">
                <input type="hidden" name="reset" value="1">
                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="correo_reset" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña</label>
                    <input type="password" name="nueva_clave" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmar Contraseña</label>
                    <input type="password" name="confirmar_clave" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Guardar Cambios
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        document.getElementById('openModal').addEventListener('click', function(e){
            e.preventDefault();
            document.getElementById('resetModal').style.display='flex';
        });
        document.getElementById('closeModal').addEventListener('click', function(){
            document.getElementById('resetModal').style.display='none';
        });
        window.onclick=function(e){
            if(e.target==document.getElementById('resetModal')){
                document.getElementById('resetModal').style.display='none';
            }
        };
    </script>
</body>
</html>
