<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

$accion = sanitize($_POST['accion'] ?? '');

if ($accion !== 'registrar') {
    jsonResponse(false, 'Acción no válida');
}

// Obtener y validar datos
$tipoEmpresa = sanitize($_POST['tipo_empresa'] ?? '');
$nombreEmpresa = sanitize($_POST['nombre_empresa'] ?? '');
$rubro = sanitize($_POST['rubro'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$telefono = sanitize($_POST['telefono'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

// Validaciones
$errores = [];

if (empty($tipoEmpresa) || !in_array($tipoEmpresa, ['grande', 'pyme'])) {
    $errores[] = 'Debe seleccionar un tipo de empresa válido';
}

if (empty($nombreEmpresa) || strlen($nombreEmpresa) < 3) {
    $errores[] = 'El nombre de la empresa debe tener al menos 3 caracteres';
}

if (empty($email) || !isValidEmail($email)) {
    $errores[] = 'Email inválido';
}

if (empty($password) || strlen($password) < 6) {
    $errores[] = 'La contraseña debe tener al menos 6 caracteres';
}

if ($password !== $passwordConfirm) {
    $errores[] = 'Las contraseñas no coinciden';
}

if (!empty($errores)) {
    jsonResponse(false, implode('. ', $errores));
}

try {
    $pdo->beginTransaction();
    
    // Verificar que el email no esté registrado
    $stmt = $pdo->prepare("SELECT id FROM empresas WHERE email_contacto = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonResponse(false, 'El email ya está registrado');
    }
    
    // Verificar que el email no esté en usuarios
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonResponse(false, 'El email ya está en uso');
    }
    
    // Insertar empresa
    $stmt = $pdo->prepare("
        INSERT INTO empresas (
            nombre, 
            tipo, 
            rubro, 
            email_contacto, 
            telefono, 
            activo,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    $stmt->execute([
        $nombreEmpresa,
        $tipoEmpresa,
        $rubro,
        $email,
        $telefono
    ]);
    
    $empresaId = $pdo->lastInsertId();
    
    // Determinar el rol según el tipo de empresa
    $rol = $tipoEmpresa === 'grande' ? ROL_EMPRESA_A : ROL_EMPRESA_B;
    
    // Crear usuario asociado
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (
            nombre_completo,
            email,
            password_hash,
            rol,
            empresa_id,
            activo,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    $stmt->execute([
        $nombreEmpresa, // Usar nombre de empresa como nombre del usuario
        $email,
        $passwordHash,
        $rol,
        $empresaId
    ]);
    
    $pdo->commit();

    // Enviar correo de bienvenida
    try {
        require_once '../includes/email_functions.php';
        enviarCorreoBienvenida($email, $nombreEmpresa, $tipoEmpresa, $password);
    } catch (Exception $e) {
        error_log("Error al enviar correo de bienvenida: " . $e->getMessage());
    }
    
    jsonResponse(true, 'Registro exitoso. Ya puedes iniciar sesión', [
        'redirect' => BASE_URL . 'views/login.php?registro=exitoso'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error en registro: " . $e->getMessage());
    jsonResponse(false, 'Error al procesar el registro. Intente nuevamente');
}
?>