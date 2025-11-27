<?php
/**
 * API: Obtener bloques para el visor público en tiempo real
 * Ruta: api/get_bloques_visor.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

try {
    
    // Obtener fecha y hora actual para determinar bloque activo
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i:s');
    
    // Consulta principal: obtener todos los bloques con información de empresas y reuniones
    $query = "
        SELECT 
            b.id as bloque_id,
            b.fecha,
            b.hora_inicio,
            b.hora_fin,
            b.estado as bloque_estado,
            e.id as empresa_id,
            e.nombre as empresa_nombre,
            e.rubro as empresa_rubro,
            e.logo as empresa_logo,
            r.id as reunion_id,
            r.estado as reunion_estado,
            eb.nombre as pyme_nombre,
            eb.logo as pyme_logo
        FROM bloques_horarios b
        INNER JOIN empresas e ON b.empresa_a_id = e.id
        LEFT JOIN reuniones r ON b.id = r.bloque_id AND r.estado = 'confirmada'
        LEFT JOIN empresas eb ON r.empresa_b_id = eb.id
        WHERE e.activo = 1
        ORDER BY b.hora_inicio, e.nombre
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $bloques_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar datos: primero sacar lista de empresas únicas
    $empresas = [];
    $horarios = [];
    $matriz = [];
    
    foreach ($bloques_raw as $bloque) {
        $empresa_id = $bloque['empresa_id'];
        $horario_key = $bloque['hora_inicio'] . '-' . $bloque['hora_fin'];
        
        // Agregar empresa si no existe
        if (!isset($empresas[$empresa_id])) {
            $empresas[$empresa_id] = [
                'id' => $bloque['empresa_id'],
                'nombre' => $bloque['empresa_nombre'],
                'rubro' => $bloque['empresa_rubro'],
                'logo' => $bloque['empresa_logo']
            ];
        }
        
        // Agregar horario si no existe
        if (!in_array($horario_key, $horarios)) {
            $horarios[] = $horario_key;
        }
        
        // Determinar si este bloque está activo ahora mismo
        $es_actual = false;
        if ($bloque['fecha'] === $fecha_actual) {
            $hora_inicio = strtotime($bloque['hora_inicio']);
            $hora_fin = strtotime($bloque['hora_fin']);
            $hora_now = strtotime($hora_actual);
            
            if ($hora_now >= $hora_inicio && $hora_now < $hora_fin) {
                $es_actual = true;
            }
        }
        
        // Crear celda de la matriz
        $matriz[$horario_key][$empresa_id] = [
            'bloque_id' => $bloque['bloque_id'],
            'estado' => $bloque['bloque_estado'],
            'pyme_nombre' => $bloque['pyme_nombre'],
            'pyme_logo' => $bloque['pyme_logo'],
            'es_actual' => $es_actual
        ];
    }
    
    // Calcular estadísticas
    $total_bloques = count($bloques_raw);
    $bloques_disponibles = 0;
    $bloques_ocupados = 0;
    
    foreach ($bloques_raw as $bloque) {
        if ($bloque['bloque_estado'] === 'disponible') {
            $bloques_disponibles++;
        } else {
            $bloques_ocupados++;
        }
    }
    
    // Respuesta JSON
    echo json_encode([
        'success' => true,
        'fecha_evento' => '2025-11-28',
        'fecha_actual' => $fecha_actual,
        'hora_actual' => date('H:i'),
        'empresas' => array_values($empresas),
        'horarios' => $horarios,
        'matriz' => $matriz,
        'estadisticas' => [
            'total_bloques' => $total_bloques,
            'disponibles' => $bloques_disponibles,
            'ocupados' => $bloques_ocupados,
            'porcentaje_ocupacion' => $total_bloques > 0 ? round(($bloques_ocupados / $total_bloques) * 100, 1) : 0
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener bloques',
        'mensaje' => $e->getMessage()
    ]);
}