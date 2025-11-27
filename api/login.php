<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

// Obtener y sanitizar datos
$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validaciones básicas
if (empty($email) || empty($password)) {
    jsonResponse(false, 'Por favor complete todos los campos');
}

if (!isValidEmail($email)) {
    jsonResponse(false, 'Correo electrónico inválido');
}

try {
    // Buscar usuario por email
    $stmt = $pdo->prepare("
        SELECT u.*, e.nombre as empresa_nombre, e.tipo as empresa_tipo
        FROM usuarios u
        LEFT JOIN empresas e ON u.empresa_id = e.id
        WHERE u.email = ? AND u.activo = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Verificar si existe el usuario
    if (!$user) {
        jsonResponse(false, 'Credenciales incorrectas');
    }
    
    // Verificar contraseña
    if (!verifyPassword($password, $user['password_hash'])) {
        jsonResponse(false, 'Credenciales incorrectas');
    }
    
    // Actualizar último acceso
    updateLastAccess($user['id'], $pdo);
    
    // Crear sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['rol'] = $user['rol'];
    $_SESSION['empresa_id'] = $user['empresa_id'];
    $_SESSION['nombre'] = $user['nombre_completo'] ?? $user['empresa_nombre'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['empresa_tipo'] = $user['empresa_tipo'];
    $_SESSION['login_time'] = time();
    
    // Determinar URL de redirección según rol
    switch ($user['rol']) {
        case ROL_EMPRESA_A:
            $redirect = BASE_URL . 'views/panel-empresa-a.php';
            break;
        case ROL_EMPRESA_B:
            $redirect = BASE_URL . 'views/panel-empresa-b.php';
            break;
        case ROL_ADMIN:
            $redirect = BASE_URL . 'views/panel-admin.php';
            break;
        default:
            $redirect = BASE_URL . 'index.php';
    }
    
    // Enviar respuesta JSON con redirect
    jsonResponse(true, 'Bienvenido/a', ['redirect' => $redirect]);
    
} catch (PDOException $e) {
    error_log("Error en login: " . $e->getMessage());
    jsonResponse(false, 'Error del servidor. Intente nuevamente.');
}
?>