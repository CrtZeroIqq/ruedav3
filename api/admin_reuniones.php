<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Verificar autenticación y permisos de admin
if (!isLoggedIn() || getUserRole() !== ROL_ADMIN) {
    jsonResponse(false, 'No autorizado');
}

// Manejar solicitudes GET (consultas)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = sanitize($_GET['accion'] ?? '');

    switch ($accion) {
        case 'detalle_slot':
            detalleSlot($_GET, $pdo);
            break;

        case 'disponibilidad_empresa':
            disponibilidadEmpresa($_GET, $pdo);
            break;

        default:
            jsonResponse(false, 'Acción no válida');
    }
    exit;
}

// Manejar solicitudes POST (modificaciones)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

$accion = sanitize($_POST['accion'] ?? '');

try {
    switch ($accion) {
        case 'editar':
            $reunionId = intval($_POST['reunion_id'] ?? 0);
            if ($reunionId <= 0) jsonResponse(false, 'ID de reunión inválido');
            editarReunion($reunionId, $_POST, $pdo);
            break;

        case 'reprogramar':
            $reunionId = intval($_POST['reunion_id'] ?? 0);
            if ($reunionId <= 0) jsonResponse(false, 'ID de reunión inválido');
            reprogramarReunion($reunionId, $_POST, $pdo);
            break;

        case 'reasignar':
            $reunionId = intval($_POST['reunion_id'] ?? 0);
            if ($reunionId <= 0) jsonResponse(false, 'ID de reunión inválido');
            reasignarPyme($reunionId, $_POST, $pdo);
            break;

        case 'liberar_slot':
            liberarSlot($_POST, $pdo);
            break;

        case 'asignar_slot':
            asignarSlot($_POST, $pdo);
            break;

        case 'crear_reunion_admin':
            crearReunionAdmin($_POST, $pdo);
            break;

        default:
            jsonResponse(false, 'Acción no válida');
    }

} catch (PDOException $e) {
    error_log("Error en admin_reuniones.php: " . $e->getMessage());
    jsonResponse(false, 'Error al procesar la solicitud');
}

/**
 * Obtener detalle de un slot específico
 */
function detalleSlot($datos, $pdo) {
    $mesa = intval($datos['mesa'] ?? 0);
    $bloqueId = intval($datos['bloque_id'] ?? 0);
    $empresaId = intval($datos['empresa_id'] ?? 0);

    if ($mesa <= 0 || $bloqueId <= 0 || $empresaId <= 0) {
        jsonResponse(false, 'Datos incompletos');
    }

    try {
        // Obtener información del slot
        $stmt = $pdo->prepare("
            SELECT
                e.nombre as empresa_nombre,
                bg.hora_inicio,
                bg.hora_fin
            FROM disponibilidad_empresas de
            INNER JOIN empresas e ON e.id = de.empresa_id
            INNER JOIN bloques_horarios_globales bg ON bg.id = de.bloque_id
            WHERE de.mesa_numero = ? AND de.bloque_id = ? AND de.empresa_id = ?
        ");
        $stmt->execute([$mesa, $bloqueId, $empresaId]);
        $info = $stmt->fetch();

        if (!$info) {
            jsonResponse(false, 'Slot no encontrado');
        }

        // Obtener reuniones en este slot
        $stmt = $pdo->prepare("
            SELECT
                r.estado,
                e.nombre as empresa_b_nombre
            FROM reuniones r
            INNER JOIN empresas e ON e.id = r.empresa_b_id
            WHERE r.empresa_a_id = ? AND r.bloque_global_id = ? AND r.mesa_asignada = ?
        ");
        $stmt->execute([$empresaId, $bloqueId, $mesa]);
        $reuniones = $stmt->fetchAll();

        $info['reuniones'] = $reuniones;

        jsonResponse(true, 'Detalle del slot', $info);

    } catch (PDOException $e) {
        error_log("Error al obtener detalle del slot: " . $e->getMessage());
        jsonResponse(false, 'Error al consultar el slot');
    }
}

/**
 * Obtener disponibilidad de una empresa
 */
function disponibilidadEmpresa($datos, $pdo) {
    $empresaId = intval($datos['empresa_id'] ?? 0);

    if ($empresaId <= 0) {
        jsonResponse(false, 'ID de empresa inválido');
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                de.mesa_numero,
                de.bloque_id,
                bg.hora_inicio,
                bg.hora_fin,
                bg.orden
            FROM disponibilidad_empresas de
            INNER JOIN bloques_horarios_globales bg ON bg.id = de.bloque_id
            WHERE de.empresa_id = ? AND de.disponible = 1
            ORDER BY de.mesa_numero, bg.orden
        ");
        $stmt->execute([$empresaId]);
        $disponibilidad = $stmt->fetchAll();

        jsonResponse(true, 'Disponibilidad obtenida', $disponibilidad);

    } catch (PDOException $e) {
        error_log("Error al obtener disponibilidad: " . $e->getMessage());
        jsonResponse(false, 'Error al consultar disponibilidad');
    }
}

/**
 * Liberar un slot (eliminar disponibilidad)
 */
function liberarSlot($datos, $pdo) {
    $mesa = intval($datos['mesa_numero'] ?? 0);
    $bloqueId = intval($datos['bloque_id'] ?? 0);
    $empresaId = intval($datos['empresa_id'] ?? 0);

    if ($mesa <= 0 || $bloqueId <= 0 || $empresaId <= 0) {
        jsonResponse(false, 'Datos incompletos');
    }

    try {
        $pdo->beginTransaction();

        // Eliminar reuniones pendientes en este slot
        $stmt = $pdo->prepare("
            DELETE FROM reuniones
            WHERE empresa_a_id = ?
            AND bloque_global_id = ?
            AND mesa_asignada = ?
            AND estado = 'pendiente'
        ");
        $stmt->execute([$empresaId, $bloqueId, $mesa]);
        $reunionesEliminadas = $stmt->rowCount();

        // Eliminar disponibilidad
        $stmt = $pdo->prepare("
            DELETE FROM disponibilidad_empresas
            WHERE empresa_id = ? AND bloque_id = ? AND mesa_numero = ?
        ");
        $stmt->execute([$empresaId, $bloqueId, $mesa]);

        $pdo->commit();

        $mensaje = "Slot liberado exitosamente";
        if ($reunionesEliminadas > 0) {
            $mensaje .= " ($reunionesEliminadas reunión(es) pendiente(s) eliminada(s))";
        }

        jsonResponse(true, $mensaje);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al liberar slot: " . $e->getMessage());
        jsonResponse(false, 'Error al liberar el slot');
    }
}

/**
 * Asignar un slot a una empresa
 */
function asignarSlot($datos, $pdo) {
    $mesa = intval($datos['mesa_numero'] ?? 0);
    $bloqueId = intval($datos['bloque_id'] ?? 0);
    $empresaId = intval($datos['empresa_id'] ?? 0);

    if ($mesa <= 0 || $bloqueId <= 0 || $empresaId <= 0) {
        jsonResponse(false, 'Datos incompletos');
    }

    try {
        // Verificar que la empresa exista y sea tipo 'grande'
        $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ? AND tipo = 'grande' AND activo = 1");
        $stmt->execute([$empresaId]);
        $empresa = $stmt->fetch();

        if (!$empresa) {
            jsonResponse(false, 'Empresa no encontrada o no es demandante');
        }

        // Verificar que el slot no esté ocupado
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM disponibilidad_empresas
            WHERE mesa_numero = ? AND bloque_id = ?
        ");
        $stmt->execute([$mesa, $bloqueId]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(false, 'El slot ya está ocupado');
        }

        // Insertar disponibilidad
        $stmt = $pdo->prepare("
            INSERT INTO disponibilidad_empresas (empresa_id, bloque_id, mesa_numero, disponible, created_at)
            VALUES (?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$empresaId, $bloqueId, $mesa]);

        jsonResponse(true, 'Slot asignado exitosamente');

    } catch (PDOException $e) {
        error_log("Error al asignar slot: " . $e->getMessage());
        jsonResponse(false, 'Error al asignar el slot: ' . $e->getMessage());
    }
}

/**
 * Crear reunión manualmente (sin límites de admin)
 */
function crearReunionAdmin($datos, $pdo) {
    $empresaAId = intval($datos['empresa_a_id'] ?? 0);
    $empresaBId = intval($datos['empresa_b_id'] ?? 0);
    $bloqueId = intval($datos['bloque_id'] ?? 0);
    $mesaNumero = intval($datos['mesa_numero'] ?? 0);
    $estado = sanitize($datos['estado'] ?? 'pendiente');
    $notas = sanitize($datos['notas'] ?? '');

    if ($empresaAId <= 0 || $empresaBId <= 0 || $bloqueId <= 0 || $mesaNumero <= 0) {
        jsonResponse(false, 'Datos incompletos');
    }

    if (!in_array($estado, ['pendiente', 'confirmada'])) {
        jsonResponse(false, 'Estado inválido');
    }

    try {
        $pdo->beginTransaction();

        // Verificar que ambas empresas existan y sean de tipos correctos
        $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ? AND tipo = 'grande' AND activo = 1");
        $stmt->execute([$empresaAId]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            jsonResponse(false, 'Empresa demandante no válida');
        }

        $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ? AND tipo = 'pyme' AND activo = 1");
        $stmt->execute([$empresaBId]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            jsonResponse(false, 'Empresa oferente no válida');
        }

        // Verificar que el bloque exista
        $stmt = $pdo->prepare("SELECT * FROM bloques_horarios_globales WHERE id = ?");
        $stmt->execute([$bloqueId]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            jsonResponse(false, 'Bloque horario no encontrado');
        }

        // Verificar que no exista ya una reunión confirmada en este slot
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM reuniones
            WHERE bloque_global_id = ? AND mesa_asignada = ? AND estado = 'confirmada'
        ");
        $stmt->execute([$bloqueId, $mesaNumero]);
        if ($stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            jsonResponse(false, 'Ya existe una reunión confirmada en este horario/mesa');
        }

        // Si no existe disponibilidad para esta empresa en este slot, crearla
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM disponibilidad_empresas
            WHERE empresa_id = ? AND bloque_id = ? AND mesa_numero = ?
        ");
        $stmt->execute([$empresaAId, $bloqueId, $mesaNumero]);

        if ($stmt->fetchColumn() == 0) {
            // Crear disponibilidad automáticamente
            $stmt = $pdo->prepare("
                INSERT INTO disponibilidad_empresas (empresa_id, bloque_id, mesa_numero, disponible, created_at)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$empresaAId, $bloqueId, $mesaNumero]);
        }

        // Crear la reunión
        $notasCompletas = "[CREADA POR ADMIN]\n" . $notas;
        $stmt = $pdo->prepare("
            INSERT INTO reuniones
            (empresa_a_id, empresa_b_id, bloque_global_id, mesa_asignada, estado, notas, fecha_solicitud)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$empresaAId, $empresaBId, $bloqueId, $mesaNumero, $estado, $notasCompletas]);

        $pdo->commit();

        jsonResponse(true, 'Reunión creada exitosamente');

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al crear reunión admin: " . $e->getMessage());
        jsonResponse(false, 'Error al crear la reunión: ' . $e->getMessage());
    }
}

/**
 * Editar información de la reunión
 */
function editarReunion($reunionId, $datos, $pdo) {
    $estado = sanitize($datos['estado'] ?? '');
    $notasAdmin = sanitize($datos['notas_admin'] ?? '');

    if (!in_array($estado, ['pendiente', 'confirmada', 'rechazada', 'cancelada'])) {
        jsonResponse(false, 'Estado inválido');
    }

    try {
        // Obtener info actual
        $stmt = $pdo->prepare("SELECT * FROM reuniones WHERE id = ?");
        $stmt->execute([$reunionId]);
        $reunion = $stmt->fetch();

        if (!$reunion) {
            jsonResponse(false, 'Reunión no encontrada');
        }

        // Actualizar reunión
        $stmt = $pdo->prepare("
            UPDATE reuniones
            SET estado = ?,
                notas = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$estado, $notasAdmin, $reunionId]);

        jsonResponse(true, 'Reunión actualizada exitosamente');

    } catch (PDOException $e) {
        error_log("Error al editar reunión: " . $e->getMessage());
        jsonResponse(false, 'Error al actualizar la reunión');
    }
}

/**
 * Reprogramar reunión a otro horario
 */
function reprogramarReunion($reunionId, $datos, $pdo) {
    $nuevoBloqueId = intval($datos['nuevo_bloque_id'] ?? 0);

    if ($nuevoBloqueId <= 0) {
        jsonResponse(false, 'Bloque inválido');
    }

    try {
        $pdo->beginTransaction();

        // Obtener reunión actual
        $stmt = $pdo->prepare("SELECT * FROM reuniones WHERE id = ?");
        $stmt->execute([$reunionId]);
        $reunion = $stmt->fetch();

        if (!$reunion) {
            $pdo->rollBack();
            jsonResponse(false, 'Reunión no encontrada');
        }

        // Obtener mesa de la empresa_a en el nuevo bloque
        $stmt = $pdo->prepare("
            SELECT mesa_numero FROM disponibilidad_empresas
            WHERE empresa_id = ? AND bloque_id = ? AND disponible = 1
        ");
        $stmt->execute([$reunion['empresa_a_id'], $nuevoBloqueId]);
        $mesaNueva = $stmt->fetchColumn();

        if (!$mesaNueva) {
            $pdo->rollBack();
            jsonResponse(false, 'La empresa demandante no tiene disponibilidad en el nuevo horario');
        }

        // Verificar que no haya conflicto
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM reuniones
            WHERE bloque_global_id = ? AND mesa_asignada = ? AND estado = 'confirmada' AND id != ?
        ");
        $stmt->execute([$nuevoBloqueId, $mesaNueva, $reunionId]);

        if ($stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            jsonResponse(false, 'Ya existe una reunión confirmada en ese horario/mesa');
        }

        // Actualizar reunión
        $stmt = $pdo->prepare("
            UPDATE reuniones
            SET bloque_global_id = ?,
                mesa_asignada = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$nuevoBloqueId, $mesaNueva, $reunionId]);

        $pdo->commit();

        jsonResponse(true, 'Reunión reprogramada exitosamente');

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al reprogramar reunión: " . $e->getMessage());
        jsonResponse(false, 'Error al reprogramar la reunión');
    }
}

/**
 * Reasignar reunión a otra pyme
 */
function reasignarPyme($reunionId, $datos, $pdo) {
    $nuevaEmpresaBId = intval($datos['nueva_empresa_b_id'] ?? 0);
    $motivo = sanitize($datos['motivo'] ?? '');

    if ($nuevaEmpresaBId <= 0 || empty($motivo)) {
        jsonResponse(false, 'Datos incompletos');
    }

    try {
        $pdo->beginTransaction();

        // Verificar que la nueva empresa exista y sea pyme
        $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ? AND tipo = 'pyme' AND activo = 1");
        $stmt->execute([$nuevaEmpresaBId]);
        $nuevaPyme = $stmt->fetch();

        if (!$nuevaPyme) {
            $pdo->rollBack();
            jsonResponse(false, 'La empresa seleccionada no es válida');
        }

        // Actualizar reunión
        $stmt = $pdo->prepare("
            UPDATE reuniones
            SET empresa_b_id = ?,
                notas = CONCAT(COALESCE(notas, ''), '\n\n[REASIGNACIÓN ADMIN] ', ?),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$nuevaEmpresaBId, $motivo, $reunionId]);

        $pdo->commit();

        jsonResponse(true, 'Oferente reasignado exitosamente');

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al reasignar pyme: " . $e->getMessage());
        jsonResponse(false, 'Error al reasignar el oferente');
    }
}
?>
