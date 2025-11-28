<?php
/**
 * API: Obtener disposición de mesas para el visor
 * Ruta: api/get_mesas_visor.php
 *
 * Retorna matriz con empresas grandes en filas y mesas (1-15) en columnas
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

try {
    $bloqueId = isset($_GET['bloque_id']) ? intval($_GET['bloque_id']) : null;

    // Obtener todas las empresas grandes (demandantes)
    $stmtEmpresas = $pdo->query("
        SELECT id, nombre, rubro, logo, email_contacto
        FROM empresas
        WHERE tipo = 'grande' AND activo = 1
        ORDER BY nombre
    ");
    $empresas = $stmtEmpresas->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todos los bloques horarios
    $stmtBloques = $pdo->query("
        SELECT id, fecha, hora_inicio, hora_fin, orden
        FROM bloques_horarios_globales
        ORDER BY orden
    ");
    $bloques = $stmtBloques->fetchAll(PDO::FETCH_ASSOC);

    // Si no se especifica bloque, usar el primero
    if (!$bloqueId && count($bloques) > 0) {
        $bloqueId = $bloques[0]['id'];
    }

    // Validar que exista al menos un bloque
    if (count($bloques) === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'No hay bloques horarios configurados',
            'mensaje' => 'Por favor configure los bloques horarios primero'
        ]);
        exit;
    }

    // Obtener información del bloque actual
    $bloqueActual = null;
    foreach ($bloques as $bloque) {
        if ($bloque['id'] == $bloqueId) {
            $bloqueActual = $bloque;
            break;
        }
    }

    // Si no se encontró el bloque solicitado, usar el primero
    if (!$bloqueActual && count($bloques) > 0) {
        $bloqueActual = $bloques[0];
        $bloqueId = $bloqueActual['id'];
    }

    // Crear matriz: [empresa_id][mesa_numero] = datos
    $matriz = [];

    foreach ($empresas as $empresa) {
        $empresaId = $empresa['id'];
        $matriz[$empresaId] = [];

        // Verificar si la empresa tiene disponibilidad en este bloque
        $stmtDisp = $pdo->prepare("
            SELECT disponible
            FROM disponibilidad_empresas
            WHERE empresa_id = ? AND bloque_id = ?
        ");
        $stmtDisp->execute([$empresaId, $bloqueId]);
        $tieneDisponibilidad = $stmtDisp->fetchColumn();

        // Inicializar las 15 mesas
        for ($mesa = 1; $mesa <= 15; $mesa++) {
            // Si la empresa tiene disponibilidad en el bloque, las mesas están disponibles
            // a menos que tengan una reunión asignada
            $matriz[$empresaId][$mesa] = [
                'tiene_disponibilidad' => (bool)$tieneDisponibilidad,
                'reunion' => null,
                'estado' => $tieneDisponibilidad ? 'disponible' : 'no_disponible'
            ];
        }

        // Obtener reuniones de esta empresa en este bloque
        $stmtReuniones = $pdo->prepare("
            SELECT
                r.mesa_asignada,
                r.estado,
                r.notas,
                eb.id as pyme_id,
                eb.nombre as pyme_nombre,
                eb.rubro as pyme_rubro,
                eb.logo as pyme_logo
            FROM reuniones r
            INNER JOIN empresas eb ON r.empresa_b_id = eb.id
            WHERE r.empresa_a_id = ? AND r.bloque_global_id = ?
        ");
        $stmtReuniones->execute([$empresaId, $bloqueId]);
        $reuniones = $stmtReuniones->fetchAll(PDO::FETCH_ASSOC);

        // Marcar mesas con reuniones
        foreach ($reuniones as $reunion) {
            $mesa = $reunion['mesa_asignada'];
            if ($mesa >= 1 && $mesa <= 15) {
                $matriz[$empresaId][$mesa] = [
                    'tiene_disponibilidad' => true,
                    'reunion' => [
                        'pyme_id' => $reunion['pyme_id'],
                        'pyme_nombre' => $reunion['pyme_nombre'],
                        'pyme_rubro' => $reunion['pyme_rubro'],
                        'pyme_logo' => $reunion['pyme_logo'],
                        'estado' => $reunion['estado'],
                        'notas' => $reunion['notas']
                    ],
                    'estado' => $reunion['estado']
                ];
            }
        }
    }

    // Calcular estadísticas
    $totalMesas = 15;
    $totalEmpresasGrandes = count($empresas);
    $totalSlots = $totalMesas * $totalEmpresasGrandes;

    $slotsDisponibles = 0;
    $slotsOcupados = 0;
    $slotsNoDisponibles = 0;

    foreach ($matriz as $empresaData) {
        foreach ($empresaData as $mesaData) {
            if ($mesaData['estado'] === 'disponible') {
                $slotsDisponibles++;
            } elseif ($mesaData['estado'] === 'confirmada') {
                $slotsOcupados++;
            } else {
                $slotsNoDisponibles++;
            }
        }
    }

    // Determinar si hay bloque activo ahora
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i:s');
    $bloqueActivo = null;

    if ($bloqueActual && $bloqueActual['fecha'] === $fecha_actual) {
        $hora_inicio = strtotime($bloqueActual['hora_inicio']);
        $hora_fin = strtotime($bloqueActual['hora_fin']);
        $hora_now = strtotime($hora_actual);

        if ($hora_now >= $hora_inicio && $hora_now < $hora_fin) {
            $bloqueActivo = $bloqueId;
        }
    }

    // Respuesta
    echo json_encode([
        'success' => true,
        'bloque_actual' => $bloqueActual,
        'bloques' => $bloques,
        'empresas' => $empresas,
        'matriz' => $matriz,
        'estadisticas' => [
            'total_mesas' => $totalMesas,
            'total_empresas' => $totalEmpresasGrandes,
            'total_slots' => $totalSlots,
            'slots_disponibles' => $slotsDisponibles,
            'slots_ocupados' => $slotsOcupados,
            'slots_no_disponibles' => $slotsNoDisponibles,
            'porcentaje_ocupacion' => $slotsDisponibles > 0 ? round(($slotsOcupados / ($slotsDisponibles + $slotsOcupados)) * 100, 1) : 0
        ],
        'bloque_activo' => $bloqueActivo,
        'fecha_actual' => $fecha_actual,
        'hora_actual' => date('H:i')
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener datos',
        'mensaje' => $e->getMessage()
    ]);
}
