<?php
/**
 * Configuración de conexión a base de datos
 * Sistema de Gestión Hospitalaria
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hospital');
define('DB_CHARSET', 'utf8mb4');

// Crear conexión
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    // Log del error (en producción, usar un archivo de log)
    error_log("Error de conexión a BD: " . $conn->connect_error);
    
    // Mostrar mensaje amigable al usuario
    die("
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Error de Conexión</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
            }
            .error-container {
                background: white;
                padding: 3rem;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                text-align: center;
                max-width: 500px;
            }
            .error-icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }
            h1 {
                color: #e74c3c;
                margin-bottom: 1rem;
                font-size: 1.8rem;
            }
            p {
                color: #6c757d;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                margin-top: 1.5rem;
                padding: 0.875rem 2rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: transform 0.3s ease;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <div class='error-icon'>🏥</div>
            <h1>Error de Conexión a Base de Datos</h1>
            <p>Lo sentimos, no podemos conectar con el servidor de base de datos en este momento.</p>
            <p><strong>Por favor, contacte al administrador del sistema.</strong></p>
            <a href='javascript:location.reload()' class='btn'>Reintentar</a>
        </div>
    </body>
    </html>
    ");
}

// Configurar charset para evitar problemas con caracteres especiales
if (!$conn->set_charset(DB_CHARSET)) {
    error_log("Error estableciendo charset: " . $conn->error);
}

// Configurar zona horaria
$conn->query("SET time_zone = '-06:00'"); // Zona horaria de El Salvador

/**
 * Función para ejecutar consultas preparadas de forma segura
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $sql Consulta SQL con placeholders
 * @param string $types Tipos de datos (s=string, i=int, d=double, b=blob)
 * @param array $params Parámetros a vincular
 * @return mysqli_stmt|false
 */
function executeQuery($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Error preparando consulta: " . $conn->error);
        return false;
    }
    
    if ($types && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Error ejecutando consulta: " . $stmt->error);
        return false;
    }
    
    return $stmt;
}

/**
 * Función para sanitizar datos de entrada
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $data Dato a sanitizar
 * @return string
 */
function sanitizeInput($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($data);
}

/**
 * Función para formatear fechas al formato de El Salvador
 * 
 * @param string $date Fecha en formato SQL
 * @param bool $includeTime Incluir hora
 * @return string
 */
function formatDate($date, $includeTime = false) {
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return 'N/A';
    }
    
    $timestamp = strtotime($date);
    if ($includeTime) {
        return date('d/m/Y H:i', $timestamp);
    }
    return date('d/m/Y', $timestamp);
}

/**
 * Función para obtener el nombre del rol en español
 * 
 * @param string $rol Rol en inglés
 * @return string
 */
function getRolName($rol) {
    $roles = [
        'admin' => 'Administrador',
        'doctor' => 'Doctor',
        'enfermera' => 'Enfermera',
        'recepcionista' => 'Recepcionista'
    ];
    return $roles[$rol] ?? $rol;
}

/**
 * Función para obtener el estado de hospitalización en español
 * 
 * @param string $estado Estado en inglés
 * @return string
 */
function getEstadoHospitalizacion($estado) {
    $estados = [
        'en_curso' => 'En Curso',
        'alta_medica' => 'Alta Médica',
        'alta_voluntaria' => 'Alta Voluntaria',
        'referido' => 'Referido',
        'fallecido' => 'Fallecido'
    ];
    return $estados[$estado] ?? $estado;
}

/**
 * Función para verificar si el usuario está autenticado
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['codigoempleado']);
}

/**
 * Función para verificar el rol del usuario
 * 
 * @param string|array $roles Rol o roles permitidos
 * @return bool
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['rol'] ?? '';
    
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    
    return $userRole === $roles;
}

/**
 * Función para requerir autenticación
 * Redirige al login si no está autenticado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Función para requerir un rol específico
 * 
 * @param string|array $roles Rol o roles requeridos
 */
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        header("Location: index.php?error=no_permission");
        exit;
    }
}
?>