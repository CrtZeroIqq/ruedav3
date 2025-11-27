<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Verificar autenticación
if (!isLoggedIn()) {
    jsonResponse(false, 'No autorizado');
}

// Solo Empresa B puede solicitar
if (getUserRole() !== ROL_EMPRESA_B) {
    jsonResponse(false, 'Solo las Pymes pueden solicitar reuniones');
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

$bloqueId = intval($_POST['bloque_id'] ?? 0);
$empresaAId = intval($_POST['empresa_a_id'] ?? 0);
$notas = sanitize($_POST['notas'] ?? '');
$empresaBId = getEmpresaId();
$userId = getUserId();

if ($bloqueId <= 0 || $empresaAId <= 0) {
    jsonResponse(false, 'Datos incompletos');
}

try {
    // Verificar que el bloque exista y esté disponible
    $stmt = $pdo->prepare("
        SELECT * FROM bloques_horarios 
        WHERE id = ? AND empresa_a_id = ? AND estado = 'disponible'
    ");
    $stmt->execute([$bloqueId, $empresaAId]);
    $bloque = $stmt->fetch();
    
    if (!$bloque) {
        jsonResponse(false, 'El bloque seleccionado no está disponible');
    }
    
    // Verificar que no haya una reunión pendiente/confirmada en ese bloque
    $stmt = $pdo->prepare("
        SELECT id FROM reuniones 
        WHERE bloque_id = ? AND estado IN ('pendiente', 'confirmada')
    ");
    $stmt->execute([$bloqueId]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Ya existe una solicitud para este horario');
    }
    
    // Crear la solicitud de reunión
    $stmt = $pdo->prepare("
        INSERT INTO reuniones (empresa_a_id, empresa_b_id, bloque_id, estado, notas, solicitado_por)
        VALUES (?, ?, ?, 'pendiente', ?, ?)
    ");
    $stmt->execute([$empresaAId, $empresaBId, $bloqueId, $notas, $userId]);
    
    // ========================================
    // 📧 ENVIAR NOTIFICACIÓN POR CORREO
    // ========================================
    try {
        // Obtener datos de la empresa grande (que recibirá la notificación)
        $stmt = $pdo->prepare("
            SELECT nombre, email_contacto 
            FROM empresas 
            WHERE id = ?
        ");
        $stmt->execute([$empresaAId]);
        $empresaGrande = $stmt->fetch();
        
        // Obtener datos de mi empresa (pyme solicitante)
        $stmt = $pdo->prepare("
            SELECT nombre 
            FROM empresas 
            WHERE id = ?
        ");
        $stmt->execute([$empresaBId]);
        $miEmpresa = $stmt->fetch();
        
        // Si se obtuvieron los datos, enviar notificación
        if ($empresaGrande && $miEmpresa) {
            require_once '../includes/email_functions.php';
            
            $correoEnviado = notificarSolicitudReunion(
                $empresaGrande['email_contacto'],
                $empresaGrande['nombre'],
                $miEmpresa['nombre'],
                $bloque['fecha'],
                date('H:i', strtotime($bloque['hora_inicio'])),
                date('H:i', strtotime($bloque['hora_fin'])),
                $notas
            );
            
            if (!$correoEnviado) {
                error_log("No se pudo enviar correo de notificación a {$empresaGrande['email_contacto']}");
            }
        }
    } catch (Exception $e) {
        // No fallar la solicitud si el correo falla
        error_log("Error al enviar notificación: " . $e->getMessage());
    }
    
    jsonResponse(true, 'Solicitud enviada exitosamente. Espera la confirmación de la empresa.');
    
} catch (PDOException $e) {
    error_log("Error en solicitar_reunion.php: " . $e->getMessage());
    jsonResponse(false, 'Error al procesar la solicitud');
}
?>