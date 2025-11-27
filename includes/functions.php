<?php
/**
 * Funciones auxiliares del sistema
 */

// Sanitizar entrada de usuario
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generar hash de contraseña
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verificar contraseña
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Respuesta JSON estandarizada
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Verificar permisos de acceso según rol
function checkAccess($allowedRoles = []) {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'views/login.php');
        exit;
    }
    
    if (!empty($allowedRoles) && !in_array(getUserRole(), $allowedRoles)) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

// Actualizar último acceso del usuario
function updateLastAccess($userId, $pdo) {
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    } catch (PDOException $e) {
        // Log error silenciosamente
        error_log("Error actualizando último acceso: " . $e->getMessage());
    }
}

// Obtener nombre de empresa por ID
function getNombreEmpresa($empresaId, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT nombre FROM empresas WHERE id = ?");
        $stmt->execute([$empresaId]);
        $result = $stmt->fetch();
        return $result ? $result['nombre'] : 'Sin empresa';
    } catch (PDOException $e) {
        return 'Error';
    }
}

// Formatear fecha en español
function formatearFecha($fecha) {
    $meses = [
        '01' => 'enero', '02' => 'febrero', '03' => 'marzo',
        '04' => 'abril', '05' => 'mayo', '06' => 'junio',
        '07' => 'julio', '08' => 'agosto', '09' => 'septiembre',
        '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
    ];
    
    $fecha_obj = new DateTime($fecha);
    $dia = $fecha_obj->format('d');
    $mes = $meses[$fecha_obj->format('m')];
    $anio = $fecha_obj->format('Y');
    
    return "$dia de $mes de $anio";
}
?>