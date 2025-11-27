<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/email_functions.php';

// Solo método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

// Solo empresa B puede solicitar
if ($_SESSION['rol'] !== 'empresa_b') {
    jsonResponse(false, 'Acceso denegado');
}

$empresa_b_id = $_SESSION['empresa_id'];
$empresa_a_id = intval($_POST['empresa_a_id'] ?? 0);
$bloque_id = intval($_POST['bloque_global_id'] ?? 0);
$mensaje = sanitize($_POST['mensaje'] ?? '');

if (!$empresa_a_id || !$bloque_id) {
    jsonResponse(false, 'Datos incompletos');
}

try {
    // Verificar disponibilidad y obtener mesa_numero
    $stmt = $pdo->prepare("
        SELECT mesa_numero
        FROM disponibilidad_empresas
        WHERE empresa_id = ? AND bloque_id = ? AND disponible = 1
    ");
    $stmt->execute([$empresa_a_id, $bloque_id]);
    $mesa_numero = $stmt->fetchColumn();

    if (!$mesa_numero) {
        jsonResponse(false, 'La empresa no está disponible en este bloque');
    }

    // Verificar que la mesa+bloque no esté ya ocupada
    $stmtMesa = $pdo->prepare("
        SELECT COUNT(*) FROM reuniones
        WHERE bloque_global_id = ? AND mesa_asignada = ? AND estado = 'confirmada'
    ");
    $stmtMesa->execute([$bloque_id, $mesa_numero]);
    if ($stmtMesa->fetchColumn() > 0) {
        jsonResponse(false, 'Esta mesa ya está ocupada en este bloque');
    }

    // Verificar que empresa_a no tenga ya reunión en este bloque
    $stmtEmpresa = $pdo->prepare("
        SELECT COUNT(*) FROM reuniones
        WHERE empresa_a_id = ? AND bloque_global_id = ? AND estado IN ('pendiente', 'confirmada')
    ");
    $stmtEmpresa->execute([$empresa_a_id, $bloque_id]);
    if ($stmtEmpresa->fetchColumn() > 0) {
        jsonResponse(false, 'Esta empresa ya tiene una reunión en este bloque');
    }

    // Obtener datos para el email
    $stmt = $pdo->prepare("
        SELECT
            ea.nombre as nombre_empresa_a,
            ea.email_contacto as email_empresa_a,
            eb.nombre as nombre_empresa_b,
            bg.fecha,
            bg.hora_inicio,
            bg.hora_fin
        FROM empresas ea
        INNER JOIN empresas eb ON eb.id = ?
        INNER JOIN bloques_horarios_globales bg ON bg.id = ?
        WHERE ea.id = ?
    ");
    $stmt->execute([$empresa_b_id, $bloque_id, $empresa_a_id]);
    $datos = $stmt->fetch();

    if (!$datos) {
        jsonResponse(false, 'Error al obtener datos de la solicitud');
    }

    // Insertar la reunión con mesa asignada desde el inicio
    $insert = $pdo->prepare("
        INSERT INTO reuniones
        (empresa_a_id, empresa_b_id, bloque_global_id, mesa_asignada, estado, notas, fecha_solicitud)
        VALUES (?, ?, ?, ?, 'pendiente', ?, NOW())
    ");

    $insert->execute([
        $empresa_a_id,
        $empresa_b_id,
        $bloque_id,
        $mesa_numero,
        $mensaje
    ]);

    // Enviar email de notificación a empresa_a
    $emailEnviado = notificarSolicitudReunion(
        $datos['email_empresa_a'],
        $datos['nombre_empresa_a'],
        $datos['nombre_empresa_b'],
        $datos['fecha'],
        $datos['hora_inicio'],
        $datos['hora_fin'],
        $mensaje
    );

    if (!$emailEnviado) {
        error_log("Advertencia: Reunión creada pero email no enviado a empresa_a ID: $empresa_a_id");
    }

    jsonResponse(true, 'Solicitud enviada correctamente');

} catch (Exception $e) {
    error_log("Error en solicitar reunion: " . $e->getMessage());
    jsonResponse(false, 'Error del servidor');
}
?>
