<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Verificar autenticación
if (!isLoggedIn()) {
    jsonResponse(false, 'No autorizado');
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

$accion = sanitize($_POST['accion'] ?? '');
$reunionId = intval($_POST['reunion_id'] ?? 0);
$userId = getUserId();
$userRole = getUserRole();

if (empty($accion) || $reunionId <= 0) {
    jsonResponse(false, 'Datos incompletos');
}

try {
    // Obtener información de la reunión
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            ea.nombre as empresa_a_nombre,
            ea.email_contacto as empresa_a_email,
            eb.nombre as empresa_b_nombre,
            eb.email_contacto as empresa_b_email,
            bh.id as bloque_id,
            bh.fecha,
            bh.hora_inicio,
            bh.hora_fin
        FROM reuniones r
        INNER JOIN empresas ea ON r.empresa_a_id = ea.id
        INNER JOIN empresas eb ON r.empresa_b_id = eb.id
        INNER JOIN bloques_horarios bh ON r.bloque_id = bh.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reunionId]);
    $reunion = $stmt->fetch();
    
    if (!$reunion) {
        jsonResponse(false, 'Reunión no encontrada');
    }
    
    // Verificar permisos según la acción
    switch ($accion) {
        case 'aceptar':
        case 'rechazar':
            // Solo Empresa A puede aceptar/rechazar
            if ($userRole !== ROL_EMPRESA_A) {
                jsonResponse(false, 'No tiene permisos para esta acción');
            }
            
            // Verificar que sea SU empresa
            if ($reunion['empresa_a_id'] != getEmpresaId()) {
                jsonResponse(false, 'Esta reunión no pertenece a su empresa');
            }
            
            // Verificar que esté pendiente
            if ($reunion['estado'] !== ESTADO_PENDIENTE) {
                jsonResponse(false, 'Esta reunión ya fue procesada');
            }
            
            if ($accion === 'aceptar') {
                procesarAceptacion($reunion, $userId, $pdo);
            } else {
                procesarRechazo($reunion, $userId, $pdo);
            }
            break;
            
        case 'cancelar':
            // Admin o las empresas involucradas pueden cancelar
            if ($userRole === ROL_ADMIN || 
                $reunion['empresa_a_id'] == getEmpresaId() || 
                $reunion['empresa_b_id'] == getEmpresaId()) {
                procesarCancelacion($reunion, $userId, $pdo);
            } else {
                jsonResponse(false, 'No tiene permisos para cancelar esta reunión');
            }
            break;
            
        default:
            jsonResponse(false, 'Acción no válida');
    }
    
} catch (PDOException $e) {
    error_log("Error en reuniones.php: " . $e->getMessage());
    jsonResponse(false, 'Error al procesar la solicitud');
}

/**
 * Procesar aceptación de reunión
 */
function procesarAceptacion($reunion, $userId, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Actualizar estado de la reunión
        $stmt = $pdo->prepare("
            UPDATE reuniones 
            SET estado = ?, 
                fecha_respuesta = NOW(), 
                respondido_por = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([ESTADO_CONFIRMADA, $userId, $reunion['id']]);
        
        // Actualizar bloque horario a ocupado
        $stmt = $pdo->prepare("
            UPDATE bloques_horarios 
            SET estado = 'ocupado',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$reunion['bloque_id']]);
        
        $pdo->commit();
        
        // TODO: Enviar notificación por correo
        enviarNotificacionAceptacion($reunion);
        
        jsonResponse(true, 'Reunión aceptada exitosamente');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al aceptar reunión: " . $e->getMessage());
        jsonResponse(false, 'Error al aceptar la reunión');
    }
}

/**
 * Procesar rechazo de reunión
 */
function procesarRechazo($reunion, $userId, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Actualizar estado de la reunión
        $stmt = $pdo->prepare("
            UPDATE reuniones 
            SET estado = ?, 
                fecha_respuesta = NOW(), 
                respondido_por = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([ESTADO_RECHAZADA, $userId, $reunion['id']]);
        
        // Liberar bloque horario (volver a disponible)
        $stmt = $pdo->prepare("
            UPDATE bloques_horarios 
            SET estado = 'disponible',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$reunion['bloque_id']]);
        
        $pdo->commit();
        
        // TODO: Enviar notificación por correo
        enviarNotificacionRechazo($reunion);
        
        jsonResponse(true, 'Reunión rechazada exitosamente');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al rechazar reunión: " . $e->getMessage());
        jsonResponse(false, 'Error al rechazar la reunión');
    }
}

/**
 * Procesar cancelación de reunión
 */
function procesarCancelacion($reunion, $userId, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Actualizar estado de la reunión
        $stmt = $pdo->prepare("
            UPDATE reuniones 
            SET estado = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([ESTADO_CANCELADA, $reunion['id']]);
        
        // Liberar bloque horario
        $stmt = $pdo->prepare("
            UPDATE bloques_horarios 
            SET estado = 'disponible',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$reunion['bloque_id']]);
        
        $pdo->commit();
        
        // TODO: Enviar notificación por correo
        enviarNotificacionCancelacion($reunion);
        
        jsonResponse(true, 'Reunión cancelada exitosamente');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al cancelar reunión: " . $e->getMessage());
        jsonResponse(false, 'Error al cancelar la reunión');
    }
}

/**
 * Funciones de notificación (preparadas para futuro)
 */
function enviarNotificacionAceptacion($reunion) {
    // TODO: Implementar envío de correo
    error_log("Notificación aceptación: Reunión #{$reunion['id']} aceptada");
    return true;
}

function enviarNotificacionRechazo($reunion) {
    // TODO: Implementar envío de correo
    error_log("Notificación rechazo: Reunión #{$reunion['id']} rechazada");
    return true;
}

function enviarNotificacionCancelacion($reunion) {
    // TODO: Implementar envío de correo
    error_log("Notificación cancelación: Reunión #{$reunion['id']} cancelada");
    return true;
}
?>