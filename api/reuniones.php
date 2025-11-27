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
        INNER JOIN bloques_horarios_globales bh ON r.bloque_global_id = bh.id
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

        // Verificar que no haya otra reunión confirmada para empresa_a en este bloque
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM reuniones
            WHERE empresa_a_id = ?
            AND bloque_global_id = ?
            AND estado = 'confirmada'
            AND id != ?
        ");
        $stmt->execute([$reunion['empresa_a_id'], $reunion['bloque_global_id'], $reunion['id']]);

        if ($stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            jsonResponse(false, 'Ya tienes una reunión confirmada en este bloque horario');
        }

        // Buscar primera mesa libre (1 a 15)
        $mesaLibre = null;
        for ($mesa = 1; $mesa <= 15; $mesa++) {
            $check = $pdo->prepare("
                SELECT COUNT(*) FROM reuniones
                WHERE bloque_global_id = ?
                AND mesa_asignada = ?
                AND estado = 'confirmada'
            ");
            $check->execute([$reunion['bloque_global_id'], $mesa]);

            if ($check->fetchColumn() == 0) {
                $mesaLibre = $mesa;
                break;
            }
        }

        if (!$mesaLibre) {
            $pdo->rollBack();
            jsonResponse(false, 'No quedan mesas disponibles en este bloque. Por favor rechaza esta solicitud.');
        }

        // Actualizar estado de la reunión y asignar mesa
        $stmt = $pdo->prepare("
            UPDATE reuniones
            SET estado = ?,
                mesa_asignada = ?,
                fecha_respuesta = NOW(),
                respondido_por = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([ESTADO_CONFIRMADA, $mesaLibre, $userId, $reunion['id']]);

        $pdo->commit();

        // Enviar notificación por correo
        enviarNotificacionAceptacion($reunion);

        jsonResponse(true, "Reunión confirmada exitosamente. Mesa asignada: $mesaLibre", ['mesa' => $mesaLibre]);

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

        $pdo->commit();

        // Enviar notificación por correo
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

        // Actualizar estado de la reunión (libera la mesa automáticamente)
        $stmt = $pdo->prepare("
            UPDATE reuniones
            SET estado = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([ESTADO_CANCELADA, $reunion['id']]);

        $pdo->commit();

        // Enviar notificación por correo
        enviarNotificacionCancelacion($reunion);

        jsonResponse(true, 'Reunión cancelada exitosamente');

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al cancelar reunión: " . $e->getMessage());
        jsonResponse(false, 'Error al cancelar la reunión');
    }
}

/**
 * Funciones de notificación por correo
 */
function enviarNotificacionAceptacion($reunion) {
    try {
        require_once '../includes/email_functions.php';
        
        $fechaFormateada = date('d/m/Y', strtotime($reunion['fecha']));
        $horaInicio = date('H:i', strtotime($reunion['hora_inicio']));
        $horaFin = date('H:i', strtotime($reunion['hora_fin']));
        
        $correoEnviado = notificarRespuestaReunion(
            $reunion['empresa_b_email'],
            $reunion['empresa_b_nombre'],
            $reunion['empresa_a_nombre'],
            $reunion['fecha'],
            $horaInicio,
            $horaFin,
            'confirmada',
            '' // Sin motivo de rechazo
        );
        
        if (!$correoEnviado) {
            error_log("No se pudo enviar correo de confirmación a {$reunion['empresa_b_email']}");
        }
        
        return $correoEnviado;
        
    } catch (Exception $e) {
        error_log("Error al enviar notificación de aceptación: " . $e->getMessage());
        return false;
    }
}

function enviarNotificacionRechazo($reunion) {
    try {
        require_once '../includes/email_functions.php';
        
        $fechaFormateada = date('d/m/Y', strtotime($reunion['fecha']));
        $horaInicio = date('H:i', strtotime($reunion['hora_inicio']));
        $horaFin = date('H:i', strtotime($reunion['hora_fin']));
        
        // Motivo de rechazo (opcional, puede ampliarse)
        $motivoRechazo = "La empresa no puede atender en este horario. Por favor, solicita otro bloque disponible.";
        
        $correoEnviado = notificarRespuestaReunion(
            $reunion['empresa_b_email'],
            $reunion['empresa_b_nombre'],
            $reunion['empresa_a_nombre'],
            $reunion['fecha'],
            $horaInicio,
            $horaFin,
            'rechazada',
            $motivoRechazo
        );
        
        if (!$correoEnviado) {
            error_log("No se pudo enviar correo de rechazo a {$reunion['empresa_b_email']}");
        }
        
        return $correoEnviado;
        
    } catch (Exception $e) {
        error_log("Error al enviar notificación de rechazo: " . $e->getMessage());
        return false;
    }
}

function enviarNotificacionCancelacion($reunion) {
    try {
        require_once '../includes/email_functions.php';
        
        // Notificar a ambas empresas de la cancelación
        $fechaFormateada = date('d/m/Y', strtotime($reunion['fecha']));
        $horaInicio = date('H:i', strtotime($reunion['hora_inicio']));
        $horaFin = date('H:i', strtotime($reunion['hora_fin']));
        
        // Notificar a la PyME
        $correoB = notificarCancelacionReunion(
            $reunion['empresa_b_email'],
            $reunion['empresa_b_nombre'],
            $reunion['empresa_a_nombre'],
            $reunion['fecha'],
            $horaInicio,
            $horaFin
        );
        
        // Notificar a la Empresa Grande
        $correoA = notificarCancelacionReunion(
            $reunion['empresa_a_email'],
            $reunion['empresa_a_nombre'],
            $reunion['empresa_b_nombre'],
            $reunion['fecha'],
            $horaInicio,
            $horaFin
        );
        
        if (!$correoB) {
            error_log("No se pudo enviar correo de cancelación a {$reunion['empresa_b_email']}");
        }
        if (!$correoA) {
            error_log("No se pudo enviar correo de cancelación a {$reunion['empresa_a_email']}");
        }
        
        return ($correoA && $correoB);
        
    } catch (Exception $e) {
        error_log("Error al enviar notificación de cancelación: " . $e->getMessage());
        return false;
    }
}
?>