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
<title>Login - Sistema Hospital</title>
<style>
/* ---- ESTILOS GENERALES ---- */
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
    background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;
}
.login-container{
    width:100%;max-width:1000px;display:flex;background:white;border-radius:20px;overflow:hidden;
    box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:fadeInUp 0.6s ease;
}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}

/* ---- PANEL IZQUIERDO ---- */
.login-left{
    flex:1;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    padding:3rem;display:flex;flex-direction:column;justify-content:center;align-items:center;color:white;position:relative;overflow:hidden;
}
.login-left::before,.login-left::after{
    content:'';position:absolute;border-radius:50%;background:rgba(255,255,255,0.1);
}
.login-left::before{width:300px;height:300px;top:-100px;right:-100px;}
.login-left::after{width:200px;height:200px;bottom:-50px;left:-50px;}
.hospital-icon{
    width:120px;height:120px;background:rgba(255,255,255,0.2);
    border-radius:50%;display:flex;align-items:center;justify-content:center;
    margin-bottom:2rem;font-size:4rem;backdrop-filter:blur(10px);z-index:1;
}
.login-left h1{font-size:2rem;margin-bottom:1rem;z-index:1;text-align:center;}
.login-left p{text-align:center;opacity:0.9;font-size:1.1rem;z-index:1;}

/* ---- FORMULARIO ---- */
.login-form{flex:1;padding:3rem;display:flex;flex-direction:column;justify-content:center;}
.login-form h2{color:#2c3e50;margin-bottom:0.5rem;font-size:1.8rem;}
.subtitle{color:#6c757d;margin-bottom:2rem;font-size:0.95rem;}
.form-group{margin-bottom:1.5rem;position:relative;}
.form-group label{display:block;margin-bottom:0.5rem;color:#2c3e50;font-weight:500;font-size:0.9rem;}
.input-wrapper{position:relative;}
.input-icon{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#6c757d;font-size:1.2rem;}
.form-group input{
    width:100%;padding:0.875rem 1rem 0.875rem 3rem;border:2px solid #e0e0e0;border-radius:10px;
    font-size:1rem;transition:all 0.3s ease;background:#f8f9fa;
}
.form-group input:focus{outline:none;border-color:#667eea;background:white;box-shadow:0 0 0 4px rgba(102,126,234,0.1);}
.form-group input::placeholder{color:#adb5bd;}
.error,.success{
    padding:1rem;border-radius:10px;margin-bottom:1.5rem;text-align:center;font-weight:500;display:flex;align-items:center;justify-content:center;gap:0.5rem;
}
.error{background:linear-gradient(135deg,#e74c3c 0%,#c0392b 100%);color:white;animation:shake 0.5s ease;}
.success{background:linear-gradient(135deg,#2ecc71 0%,#27ae60 100%);color:white;}
@keyframes shake{0%,100%{transform:translateX(0);}25%{transform:translateX(-10px);}75%{transform:translateX(10px);}}
.error::before{content:'⚠';font-size:1.2rem;}
.success::before{content:'✅';font-size:1.2rem;}
.btn-login{
    width:100%;padding:1rem;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    color:white;border:none;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;transition:all 0.3s ease;
    box-shadow:0 4px 15px rgba(102,126,234,0.4);margin-top:1rem;
}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(102,126,234,0.5);}
.forgot-password{text-align:center;margin-top:1.5rem;}
.forgot-password a{color:#667eea;text-decoration:none;font-size:0.9rem;transition:color 0.3s ease;}
.forgot-password a:hover{color:#764ba2;text-decoration:underline;}

/* ---- MODAL DE RESET ---- */
.modal{
    display:none;position:fixed;top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.6);align-items:center;justify-content:center;z-index:1000;
}
.modal-content{
    background:white;padding:2rem;border-radius:15px;width:90%;max-width:400px;
    box-shadow:0 10px 40px rgba(0,0,0,0.3);animation:fadeInUp 0.4s ease;
}
.modal h3{text-align:center;margin-bottom:1rem;color:#2c3e50;}
.modal .btn-close{
    position:absolute;top:10px;right:20px;background:none;border:none;font-size:1.5rem;cursor:pointer;color:#666;
}
.modal .btn-close:hover{color:#000;}
.modal form .btn-login{margin-top:0.5rem;}

/* ---- RESPONSIVE ---- */
@media(max-width:768px){
    .login-container{flex-direction:column;max-width:450px;}
    .login-left{padding:2rem;}
    .hospital-icon{width:80px;height:80px;font-size:2.5rem;}
    .login-left h1{font-size:1.5rem;}
    .login-form{padding:2rem;}
}
</style>
</head>
<body>

<div class="login-container">
    <div class="login-left">
        <div class="hospital-icon">🏥</div>
        <h1>Sistema Hospital</h1>
        <p>Gestión médica y administrativa integrada</p>
    </div>
    
    <div class="login-form">
        <h2>Bienvenido</h2>
        <p class="subtitle">Ingresa tus credenciales para continuar</p>

        <?php 
        if (isset($error)) echo "<div class='error'>$error</div>"; 
        if (isset($success)) echo "<div class='success'>$success</div>"; 
        ?>

        <form method="post">
            <input type="hidden" name="login" value="1">
            <div class="form-group">
                <label for="correo">Correo Electrónico</label>
                <div class="input-wrapper">
                    <span class="input-icon">📧</span>
                    <input type="email" id="correo" name="correo" placeholder="ejemplo@hospital.com" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="clave">Contraseña</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="clave" name="clave" placeholder="Ingresa tu contraseña" required>
                </div>
            </div>
            
            <button type="submit" class="btn-login">Iniciar Sesión</button>
            
            <div class="forgot-password">
                <a href="#" id="openModal">¿Olvidaste tu contraseña?</a>
            </div>
        </form>
    </div>
</div>

<!-- MODAL PARA CAMBIAR CONTRASEÑA -->
<div class="modal" id="resetModal">
    <div class="modal-content">
        <button class="btn-close" id="closeModal">&times;</button>
        <h3>Restablecer Contraseña</h3>
        <form method="post">
            <input type="hidden" name="reset" value="1">
            <div class="form-group">
                <label for="correo_reset">Correo Electrónico</label>
                <div class="input-wrapper">
                    <span class="input-icon">📧</span>
                    <input type="email" id="correo_reset" name="correo_reset" placeholder="tu@correo.com" required>
                </div>
            </div>
            <div class="form-group">
                <label for="nueva_clave">Nueva Contraseña</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔑</span>
                    <input type="password" id="nueva_clave" name="nueva_clave" placeholder="Nueva contraseña" required>
                </div>
            </div>
            <div class="form-group">
                <label for="confirmar_clave">Confirmar Contraseña</label>
                <div class="input-wrapper">
                    <span class="input-icon">✅</span>
                    <input type="password" id="confirmar_clave" name="confirmar_clave" placeholder="Repite la contraseña" required>
                </div>
            </div>
            <button type="submit" class="btn-login">Guardar Cambios</button>
        </form>
    </div>
</div>

<script>
// Mostrar y ocultar el modal
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
