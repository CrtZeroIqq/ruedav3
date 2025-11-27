<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/email_functions.php';

checkAccess([ROL_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'Método no permitido');
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$accion = $payload['accion'] ?? '';

if ($accion !== 'enviar_invitacion') {
    jsonResponse(false, 'Acción no válida');
}

$destinatarios = $payload['destinatarios'] ?? [];
$asunto = trim($payload['asunto'] ?? '');
$mensaje = $payload['mensaje'] ?? '';

if (!is_array($destinatarios)) {
    jsonResponse(false, 'No se recibieron destinatarios válidos');
}

// Normalizar destinatarios y evitar duplicados por correo electrónico
$destinatariosUnicos = [];
$omitidos = [
    'sin_email' => 0,
    'invalidos' => 0,
    'duplicados' => 0,
];

foreach ($destinatarios as $destinatario) {
    $emailCrudo = isset($destinatario['email']) ? trim($destinatario['email']) : '';

    if ($emailCrudo === '') {
        $omitidos['sin_email']++;
        continue;
    }

    $emailNormalizado = strtolower($emailCrudo);

    if (!isValidEmail($emailNormalizado)) {
        $omitidos['invalidos']++;
        continue;
    }

    if (isset($destinatariosUnicos[$emailNormalizado])) {
        $omitidos['duplicados']++;
        continue;
    }

    $destinatariosUnicos[$emailNormalizado] = [
        'email' => $emailNormalizado,
        'nombre' => isset($destinatario['nombre']) ? trim($destinatario['nombre']) : ''
    ];
}

$destinatarios = array_values($destinatariosUnicos);

if (!is_array($destinatarios) || empty($destinatarios)) {
    jsonResponse(false, 'No se recibieron destinatarios válidos');
}

$asunto = $asunto !== ''
    ? $asunto
    : 'Completa tu inscripción - Rueda de Negocios Nodo Bioceánico 2025';

$enviados = [];
$errores = [];

foreach ($destinatarios as $destinatario) {
    $email = isset($destinatario['email']) ? trim($destinatario['email']) : '';
    $nombre = isset($destinatario['nombre']) ? trim($destinatario['nombre']) : '';

    if (!isValidEmail($email)) {
        $errores[] = "Correo inválido: {$email}";
        continue;
    }

    $resultado = enviarCorreoInvitacionRueda($email, $nombre, $asunto, $mensaje);

    if ($resultado) {
        $enviados[] = $email;
    } else {
        $errores[] = "No se pudo enviar a {$email}";
    }
}

jsonResponse(true, 'Seguimiento finalizado', [
    'enviados' => $enviados,
    'errores' => $errores,
    'total' => count($destinatarios),
    'omitidos' => $omitidos
]);