<?php
/**
 * SCRIPT DE DIAGNÓSTICO - Sistema Dinámico
 * Verifica el estado de la base de datos y configuración
 */

require_once 'config/database.php';
session_start();

echo "<html><head><meta charset='UTF-8'><title>Diagnóstico Sistema</title>";
echo "<style>
body { font-family: monospace; background: #1e1e1e; color: #00ff00; padding: 20px; }
.section { background: #2d2d2d; padding: 15px; margin: 15px 0; border-left: 4px solid #00ff00; }
.error { border-color: #ff0000; color: #ff6b6b; }
.warning { border-color: #ffaa00; color: #ffaa00; }
.success { border-color: #00ff00; color: #00ff00; }
h2 { color: #00aaff; margin: 0 0 10px 0; }
pre { background: #1a1a1a; padding: 10px; overflow-x: auto; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
td, th { border: 1px solid #444; padding: 8px; text-align: left; }
th { background: #333; }
</style></head><body>";

echo "<h1>🔍 DIAGNÓSTICO SISTEMA DINÁMICO</h1>";
echo "<p>Fecha: " . date('Y-m-d H:i:s') . "</p><hr>";

try {
    // =================================================================
    // 1. VERIFICAR SESIÓN
    // =================================================================
    echo "<div class='section " . (isset($_SESSION['empresa_id']) ? "success" : "warning") . "'>";
    echo "<h2>1. SESIÓN ACTIVA</h2>";
    if (isset($_SESSION['empresa_id'])) {
        echo "✅ Sesión iniciada<br>";
        echo "Empresa ID: " . $_SESSION['empresa_id'] . "<br>";
        echo "Rol: " . ($_SESSION['rol'] ?? 'No definido') . "<br>";
    } else {
        echo "⚠️ No hay sesión activa. Inicia sesión primero para diagnóstico completo.";
    }
    echo "</div>";

    // =================================================================
    // 2. VERIFICAR TABLAS
    // =================================================================
    echo "<div class='section'>";
    echo "<h2>2. TABLAS DE BASE DE DATOS</h2>";

    $tablasNecesarias = [
        'bloques_horarios_globales',
        'disponibilidad_empresas',
        'empresas',
        'usuarios',
        'reuniones'
    ];

    $tablasExistentes = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tablasExistentes[] = $row[0];
    }

    echo "<table>";
    echo "<tr><th>Tabla</th><th>Estado</th></tr>";
    foreach ($tablasNecesarias as $tabla) {
        $existe = in_array($tabla, $tablasExistentes);
        $class = $existe ? 'success' : 'error';
        $icon = $existe ? '✅' : '❌';
        echo "<tr class='$class'><td>$tabla</td><td>$icon " . ($existe ? 'Existe' : 'NO EXISTE') . "</td></tr>";
    }
    echo "</table>";

    // Advertencia sobre tabla vieja
    if (in_array('bloques_horarios', $tablasExistentes)) {
        echo "<div style='color: #ffaa00; margin-top: 10px;'>";
        echo "⚠️ ADVERTENCIA: Existe la tabla 'bloques_horarios' (sistema viejo). Esto puede causar confusión.";
        echo "</div>";
    }

    echo "</div>";

    // =================================================================
    // 3. BLOQUES HORARIOS GLOBALES
    // =================================================================
    echo "<div class='section'>";
    echo "<h2>3. BLOQUES HORARIOS GLOBALES</h2>";

    if (in_array('bloques_horarios_globales', $tablasExistentes)) {
        $stmt = $pdo->query("SELECT * FROM bloques_horarios_globales ORDER BY orden");
        $bloques = $stmt->fetchAll();

        if (empty($bloques)) {
            echo "<div class='error'>❌ No hay bloques horarios. Ejecuta setup_database_dinamico.php</div>";
        } else {
            echo "✅ Bloques encontrados: " . count($bloques) . "<br>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Orden</th><th>Fecha</th><th>Hora Inicio</th><th>Hora Fin</th></tr>";
            foreach ($bloques as $b) {
                echo "<tr>";
                echo "<td>{$b['id']}</td>";
                echo "<td>{$b['orden']}</td>";
                echo "<td>{$b['fecha']}</td>";
                echo "<td>{$b['hora_inicio']}</td>";
                echo "<td>{$b['hora_fin']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<div class='error'>❌ Tabla 'bloques_horarios_globales' NO EXISTE</div>";
    }

    echo "</div>";

    // =================================================================
    // 4. EMPRESAS REGISTRADAS
    // =================================================================
    echo "<div class='section'>";
    echo "<h2>4. EMPRESAS REGISTRADAS</h2>";

    $stmt = $pdo->query("SELECT id, nombre, tipo, activo FROM empresas ORDER BY id DESC LIMIT 10");
    $empresas = $stmt->fetchAll();

    if (empty($empresas)) {
        echo "<div class='warning'>⚠️ No hay empresas registradas</div>";
    } else {
        echo "✅ Total empresas: " . count($empresas) . " (mostrando últimas 10)<br>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Activo</th></tr>";
        foreach ($empresas as $e) {
            $tipoLabel = $e['tipo'] === 'grande' ? 'EMPRESA_A (Demandante)' : 'EMPRESA_B (Oferente)';
            $activoLabel = $e['activo'] ? 'Sí' : 'No';
            echo "<tr>";
            echo "<td>{$e['id']}</td>";
            echo "<td>{$e['nombre']}</td>";
            echo "<td>$tipoLabel</td>";
            echo "<td>$activoLabel</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "</div>";

    // =================================================================
    // 5. DISPONIBILIDAD (SI HAY SESIÓN)
    // =================================================================
    if (isset($_SESSION['empresa_id']) && $_SESSION['rol'] === 'empresa_a') {
        echo "<div class='section'>";
        echo "<h2>5. DISPONIBILIDAD DE TU EMPRESA</h2>";

        $empresa_id = $_SESSION['empresa_id'];

        // Verificar tipo de empresa
        $stmt = $pdo->prepare("SELECT nombre, tipo FROM empresas WHERE id = ?");
        $stmt->execute([$empresa_id]);
        $empresa = $stmt->fetch();

        echo "Empresa: {$empresa['nombre']}<br>";
        echo "Tipo: " . ($empresa['tipo'] === 'grande' ? 'EMPRESA_A (Demandante)' : 'EMPRESA_B (Oferente)') . "<br><br>";

        if ($empresa['tipo'] !== 'grande') {
            echo "<div class='error'>❌ ERROR: Esta empresa NO es tipo 'grande' (empresa_a). No debería ver el wizard.</div>";
        } else {
            // Verificar disponibilidad configurada
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM disponibilidad_empresas WHERE empresa_id = ?");
            $stmt->execute([$empresa_id]);
            $tieneDisponibilidad = $stmt->fetchColumn();

            if ($tieneDisponibilidad == 0) {
                echo "<div class='success'>✅ NO tiene disponibilidad configurada → DEBE mostrar WIZARD</div>";
            } else {
                echo "<div class='warning'>⚠️ YA tiene disponibilidad configurada ($tieneDisponibilidad registros) → NO muestra wizard</div>";

                // Mostrar disponibilidad actual
                $stmt = $pdo->prepare("
                    SELECT bg.orden, bg.hora_inicio, bg.hora_fin, de.disponible
                    FROM disponibilidad_empresas de
                    JOIN bloques_horarios_globales bg ON bg.id = de.bloque_id
                    WHERE de.empresa_id = ?
                    ORDER BY bg.orden
                ");
                $stmt->execute([$empresa_id]);
                $disps = $stmt->fetchAll();

                if (!empty($disps)) {
                    echo "<br><strong>Disponibilidad actual:</strong>";
                    echo "<table>";
                    echo "<tr><th>Bloque</th><th>Horario</th><th>Disponible</th></tr>";
                    foreach ($disps as $d) {
                        $icon = $d['disponible'] ? '✅' : '❌';
                        echo "<tr>";
                        echo "<td>Bloque {$d['orden']}</td>";
                        echo "<td>{$d['hora_inicio']} - {$d['hora_fin']}</td>";
                        echo "<td>$icon</td>";
                        echo "</tr>";
                    }
                    echo "</table>";

                    echo "<br><div style='background: #2a2a00; padding: 10px; border: 1px solid #ffaa00;'>";
                    echo "💡 <strong>SOLUCIÓN:</strong> Para volver a mostrar el wizard, elimina la disponibilidad:<br>";
                    echo "<code>DELETE FROM disponibilidad_empresas WHERE empresa_id = $empresa_id;</code>";
                    echo "</div>";
                }
            }
        }

        echo "</div>";
    }

    // =================================================================
    // 6. REUNIONES
    // =================================================================
    echo "<div class='section'>";
    echo "<h2>6. REUNIONES</h2>";

    $stmt = $pdo->query("
        SELECT
            r.id,
            r.estado,
            r.mesa_asignada,
            ea.nombre as empresa_a,
            eb.nombre as empresa_b,
            bg.hora_inicio
        FROM reuniones r
        JOIN empresas ea ON ea.id = r.empresa_a_id
        JOIN empresas eb ON eb.id = r.empresa_b_id
        LEFT JOIN bloques_horarios_globales bg ON bg.id = r.bloque_global_id
        ORDER BY r.id DESC
        LIMIT 10
    ");
    $reuniones = $stmt->fetchAll();

    if (empty($reuniones)) {
        echo "⚠️ No hay reuniones registradas";
    } else {
        echo "✅ Total reuniones: " . count($reuniones) . " (mostrando últimas 10)<br>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Empresa A</th><th>Empresa B</th><th>Bloque</th><th>Mesa</th><th>Estado</th></tr>";
        foreach ($reuniones as $r) {
            echo "<tr>";
            echo "<td>{$r['id']}</td>";
            echo "<td>{$r['empresa_a']}</td>";
            echo "<td>{$r['empresa_b']}</td>";
            echo "<td>{$r['hora_inicio']}</td>";
            echo "<td>" . ($r['mesa_asignada'] ?? '-') . "</td>";
            echo "<td>{$r['estado']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "</div>";

    // =================================================================
    // RESUMEN FINAL
    // =================================================================
    echo "<div class='section success'>";
    echo "<h2>✅ DIAGNÓSTICO COMPLETADO</h2>";
    echo "<strong>Próximos pasos:</strong><br>";
    echo "1. Si faltan tablas → Ejecuta setup_database_dinamico.php<br>";
    echo "2. Si no hay bloques → Ejecuta setup_database_dinamico.php<br>";
    echo "3. Si no aparece wizard y ya tienes disponibilidad → Elimínala manualmente (ver sección 5)<br>";
    echo "4. Si todo está OK → Registra empresas y prueba el sistema<br>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='section error'>";
    echo "<h2>❌ ERROR DE BASE DE DATOS</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>
