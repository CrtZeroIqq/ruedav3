<?php
/**
 * Script para generar bloques horarios con sistema de TURNOS y MESAS
 * 
 * CONFIGURACIÓN:
 * - 10 mesas físicas disponibles
 * - Bloques de 15 minutos
 * - Pausa de 5 minutos entre bloques (total 20 min por ciclo)
 * - Sistema de turnos para rotar empresas grandes
 * 
 * LÓGICA:
 * Si hay más de 10 empresas grandes, se dividen en RONDAS
 * Ronda 1: Empresas 1-10 (Mesas 1-10)
 * Ronda 2: Empresas 11-20 (Mesas 1-10)
 * etc.
 * 
 * Acceso: https://www.bioceanicocentral.cl/rueda-negocios-arica/generar_bloques.php
 */
require_once 'config/database.php';
require_once 'config/config.php';

// ============================================
// CONFIGURACIÓN DEL EVENTO
// ============================================
$fechaEvento = '2025-11-28';
$horaInicio = '11:00:00';
$horaFin = '12:30:00';
$duracionBloque = 15; // minutos
$duracionPausa = 5;   // minutos
$totalMesas = 10;     // Mesas físicas disponibles

$cicloCompleto = $duracionBloque + $duracionPausa; // 20 minutos

try {
    // ============================================
    // PASO 1: Verificar si ya existen bloques
    // ============================================
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bloques_horarios");
    $bloquesExistentes = $stmt->fetchColumn();
    
    if ($bloquesExistentes > 0) {
        echo "<div style='background: #fee; border: 2px solid #c00; padding: 20px; margin: 20px; border-radius: 8px;'>";
        echo "<h2>⚠️ ADVERTENCIA</h2>";
        echo "<p><strong>Ya existen {$bloquesExistentes} bloques en la base de datos.</strong></p>";
        echo "<p>¿Deseas eliminarlos y regenerar? <a href='?limpiar=si' style='background: #c00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Sí, Limpiar y Regenerar</a></p>";
        echo "</div>";
        
        if (!isset($_GET['limpiar']) || $_GET['limpiar'] !== 'si') {
            exit;
        }
        
        // Limpiar bloques existentes
        $pdo->exec("DELETE FROM bloques_horarios");
        echo "<p style='color: green;'>✓ Bloques antiguos eliminados</p>";
    }
    
    // ============================================
    // PASO 2: Obtener empresas grandes activas
    // ============================================
    $stmt = $pdo->query("
        SELECT id, nombre 
        FROM empresas 
        WHERE tipo = 'grande' AND activo = 1 
        ORDER BY nombre
    ");
    $empresas = $stmt->fetchAll();
    
    if (empty($empresas)) {
        die("<h2>⚠️ No hay empresas grandes registradas</h2><p>Primero crea empresas tipo 'grande' en la base de datos.</p>");
    }
    
    $totalEmpresas = count($empresas);
    $rondasNecesarias = ceil($totalEmpresas / $totalMesas);
    
    // ============================================
    // MOSTRAR RESUMEN
    // ============================================
    echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin: 20px;'>";
    echo "<h1>🔄 Generación de Bloques Horarios con Sistema de Turnos</h1>";
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;'>";
    echo "<div><strong>📅 Fecha:</strong> $fechaEvento</div>";
    echo "<div><strong>🕐 Horario:</strong> $horaInicio - $horaFin</div>";
    echo "<div><strong>⏱️ Bloque:</strong> $duracionBloque minutos</div>";
    echo "<div><strong>⏸️ Pausa:</strong> $duracionPausa minutos</div>";
    echo "<div><strong>🔄 Ciclo completo:</strong> $cicloCompleto minutos</div>";
    echo "<div><strong>🏢 Mesas disponibles:</strong> $totalMesas</div>";
    echo "<div><strong>🏭 Empresas grandes:</strong> $totalEmpresas</div>";
    echo "<div><strong>🔁 Rondas necesarias:</strong> $rondasNecesarias</div>";
    echo "</div>";
    echo "</div>";
    
    // ============================================
    // PASO 3: Asignar mesas a empresas
    // ============================================
    echo "<div style='background: white; padding: 20px; margin: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
    echo "<h2>📋 Asignación de Mesas por Ronda</h2>";
    
    $asignaciones = [];
    for ($ronda = 1; $ronda <= $rondasNecesarias; $ronda++) {
        echo "<div style='background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 10px 0;'>";
        echo "<h3>🔵 Ronda $ronda</h3>";
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #e0f2fe;'>";
        echo "<th style='padding: 10px; text-align: left; border: 1px solid #bae6fd;'>Mesa</th>";
        echo "<th style='padding: 10px; text-align: left; border: 1px solid #bae6fd;'>Empresa</th>";
        echo "<th style='padding: 10px; text-align: left; border: 1px solid #bae6fd;'>ID</th>";
        echo "</tr>";
        
        $inicioIndice = ($ronda - 1) * $totalMesas;
        $finIndice = min($inicioIndice + $totalMesas, $totalEmpresas);
        
        for ($i = $inicioIndice; $i < $finIndice; $i++) {
            $mesa = ($i % $totalMesas) + 1;
            $empresa = $empresas[$i];
            $asignaciones[$ronda][$mesa] = $empresa;
            
            echo "<tr style='border: 1px solid #bae6fd;'>";
            echo "<td style='padding: 10px; border: 1px solid #bae6fd;'><strong>Mesa $mesa</strong></td>";
            echo "<td style='padding: 10px; border: 1px solid #bae6fd;'>{$empresa['nombre']}</td>";
            echo "<td style='padding: 10px; border: 1px solid #bae6fd; color: #666;'>#{$empresa['id']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
    echo "</div>";
    
    // ============================================
    // PASO 4: Generar bloques horarios
    // ============================================
    echo "<div style='background: white; padding: 20px; margin: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
    echo "<h2>⏰ Generación de Bloques Horarios</h2>";
    
    $stmt = $pdo->prepare("
        INSERT INTO bloques_horarios (empresa_a_id, fecha, hora_inicio, hora_fin, estado, numero_mesa, ronda)
        VALUES (?, ?, ?, ?, 'disponible', ?, ?)
    ");
    
    $totalBloquesGenerados = 0;
    $horaActual = new DateTime("$fechaEvento $horaInicio");
    $horaLimite = new DateTime("$fechaEvento $horaFin");
    
    while ($horaActual < $horaLimite) {
        // Calcular hora de fin del bloque (sin pausa)
        $horaFinBloque = clone $horaActual;
        $horaFinBloque->modify("+{$duracionBloque} minutes");
        
        // Verificar que no se pase del límite
        if ($horaFinBloque > $horaLimite) {
            break;
        }
        
        echo "<div style='border: 2px solid #10b981; background: #f0fdf4; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
        echo "<h3 style='color: #065f46; margin: 0 0 10px 0;'>🕐 Bloque: {$horaActual->format('H:i')} - {$horaFinBloque->format('H:i')}</h3>";
        
        // Generar bloques para todas las rondas
        $bloquesEnEsteCiclo = 0;
        foreach ($asignaciones as $ronda => $empresasRonda) {
            foreach ($empresasRonda as $mesa => $empresa) {
                try {
                    $stmt->execute([
                        $empresa['id'],
                        $fechaEvento,
                        $horaActual->format('H:i:s'),
                        $horaFinBloque->format('H:i:s'),
                        $mesa,
                        $ronda
                    ]);
                    
                    $bloquesEnEsteCiclo++;
                    $totalBloquesGenerados++;
                    
                    echo "<span style='display: inline-block; background: white; padding: 5px 10px; margin: 2px; border-radius: 4px; font-size: 12px;'>";
                    echo "✓ Mesa $mesa (R$ronda): {$empresa['nombre']}";
                    echo "</span>";
                    
                } catch (PDOException $e) {
                    echo "<span style='display: inline-block; background: #fee; padding: 5px 10px; margin: 2px; border-radius: 4px; font-size: 12px; color: #c00;'>";
                    echo "✗ Error Mesa $mesa: " . $e->getMessage();
                    echo "</span>";
                }
            }
        }
        
        echo "<div style='margin-top: 10px; font-size: 13px; color: #065f46;'>";
        echo "<strong>Bloques generados en este ciclo:</strong> $bloquesEnEsteCiclo";
        echo "</div>";
        echo "</div>";
        
        // Avanzar al siguiente ciclo (incluye pausa)
        $horaActual->modify("+{$cicloCompleto} minutes");
    }
    
    echo "</div>";
    
    // ============================================
    // PASO 5: Actualizar tabla empresas con mesa asignada
    // ============================================
    $stmtUpdate = $pdo->prepare("UPDATE empresas SET numero_mesa = ?, ronda = ? WHERE id = ?");
    foreach ($asignaciones as $ronda => $empresasRonda) {
        foreach ($empresasRonda as $mesa => $empresa) {
            $stmtUpdate->execute([$mesa, $ronda, $empresa['id']]);
        }
    }
    
    // ============================================
    // RESUMEN FINAL
    // ============================================
    echo "<div style='background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; margin: 20px; border-radius: 10px;'>";
    echo "<h2 style='margin: 0 0 20px 0;'>✅ Proceso Completado Exitosamente</h2>";
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 18px;'>";
    echo "<div>📊 <strong>Total bloques generados:</strong> $totalBloquesGenerados</div>";
    echo "<div>🏢 <strong>Empresas procesadas:</strong> $totalEmpresas</div>";
    echo "<div>🔁 <strong>Rondas creadas:</strong> $rondasNecesarias</div>";
    echo "<div>🏪 <strong>Mesas utilizadas:</strong> $totalMesas</div>";
    echo "</div>";
    echo "<div style='margin-top: 30px; text-align: center;'>";
    echo "<a href='views/login.php' style='display: inline-block; background: white; color: #059669; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>Ir al Sistema →</a>";
    echo "</div>";
    echo "</div>";
    
    echo "<div style='background: #fef3c7; border: 2px solid #f59e0b; padding: 20px; margin: 20px; border-radius: 8px;'>";
    echo "<h3 style='color: #92400e; margin-top: 0;'>⚠️ IMPORTANTE - Próximos Pasos:</h3>";
    echo "<ol style='color: #78350f;'>";
    echo "<li><strong>Elimina este archivo</strong> (generar_bloques.php) por seguridad</li>";
    echo "<li><strong>Verifica en el sistema</strong> que las empresas vean sus bloques correctamente</li>";
    echo "<li><strong>Imprime el horario</strong> por mesa para el día del evento</li>";
    echo "<li><strong>Señalización física:</strong> Coloca números del 1 al 10 en las mesas</li>";
    echo "<li><strong>Instruye a las empresas</strong> sobre su número de mesa y ronda asignada</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #fee; border: 2px solid #c00; padding: 20px; margin: 20px; border-radius: 8px;'>";
    echo "<h2 style='color: #c00;'>❌ Error en el Proceso</h2>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin: 0;
    padding: 20px;
}
</style>