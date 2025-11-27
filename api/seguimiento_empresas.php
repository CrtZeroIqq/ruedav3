<?php
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/email_functions.php';

checkAccess([ROL_ADMIN]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['accion'])) {
    jsonResponse(false, 'Solicitud inválida');
}

if ($input['accion'] !== 'enviar_correos') {
    jsonResponse(false, 'Acción no soportada');
}

$destinatarios = $input['destinatarios'] ?? [];
$asunto = trim($input['asunto'] ?? '');
$mensaje = trim($input['mensaje'] ?? '');

if (empty($destinatarios)) {
    jsonResponse(false, 'No se seleccionaron destinatarios');
}

$asunto = $asunto !== '' ? $asunto : 'Completa tu inscripción en la Rueda de Negocios';
$mensaje = $mensaje !== '' ? $mensaje : 'Completa tu inscripción en la plataforma para confirmar tu participación en la rueda de negocios.';

$resultados = [];
foreach ($destinatarios as $destinatario) {
    $email = isset($destinatario['email']) ? trim($destinatario['email']) : '';
    $nombre = trim($destinatario['nombre'] ?? 'Empresa');

    if (!isValidEmail($email)) {
        $resultados[] = [
            'email' => $email,
            'nombre' => $nombre,
            'enviado' => false,
            'error' => 'Correo electrónico inválido'
        ];
        continue;
    }

    $enviado = enviarCorreoSeguimientoInscripcion($email, $nombre, $mensaje, $asunto);
    $resultados[] = [
        'email' => $email,
        'nombre' => $nombre,
        'enviado' => (bool) $enviado
    ];
}

$enviados = array_filter($resultados, fn($r) => $r['enviado']);

jsonResponse(true, count($enviados) . ' correos procesados correctamente', [
    'resultados' => $resultados
]);