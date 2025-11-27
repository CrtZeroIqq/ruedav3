<?php
/**
 * Script de verificación post-migración
 * Confirma que bloque_id es nullable y el sistema está listo
 */

require_once 'config/database.php';

echo "<h1>✓ Verificación Post-Migración</h1>";

try {
    // Verificar estructura de reuniones
    $stmt = $pdo->query("DESCRIBE reuniones");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $bloqueIdNullable = false;
    $bloqueGlobalIdExiste = false;

    foreach ($columnas as $col) {
        if ($col['Field'] === 'bloque_id') {
            $bloqueIdNullable = ($col['Null'] === 'YES');
        }
        if ($col['Field'] === 'bloque_global_id') {
            $bloqueGlobalIdExiste = true;
        }
    }

    echo "<h2>Estado de la Base de Datos:</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Verificación</th><th>Estado</th></tr>";

    // Verificar bloque_id nullable
    echo "<tr>";
    echo "<td>bloque_id es nullable (opcional)</td>";
    if ($bloqueIdNullable) {
        echo "<td style='color: green; font-weight: bold;'>✓ SÍ</td>";
    } else {
        echo "<td style='color: red; font-weight: bold;'>✗ NO (EJECUTAR MIGRACIÓN)</td>";
    }
    echo "</tr>";

    // Verificar bloque_global_id existe
    echo "<tr>";
    echo "<td>bloque_global_id existe</td>";
    if ($bloqueGlobalIdExiste) {
        echo "<td style='color: green; font-weight: bold;'>✓ SÍ</td>";
    } else {
        echo "<td style='color: red; font-weight: bold;'>✗ NO (EJECUTAR setup_database_dinamico.php)</td>";
    }
    echo "</tr>";

    // Verificar tabla bloques_horarios_globales
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM bloques_horarios_globales");
        $countBloques = $stmt->fetchColumn();
        echo "<tr>";
        echo "<td>Bloques horarios globales configurados</td>";
        if ($countBloques > 0) {
            echo "<td style='color: green; font-weight: bold;'>✓ {$countBloques} bloques</td>";
        } else {
            echo "<td style='color: orange; font-weight: bold;'>⚠ 0 bloques (EJECUTAR setup_database_dinamico.php)</td>";
        }
        echo "</tr>";
    } catch (PDOException $e) {
        echo "<tr>";
        echo "<td>Tabla bloques_horarios_globales</td>";
        echo "<td style='color: red; font-weight: bold;'>✗ NO EXISTE (EJECUTAR setup_database_dinamico.php)</td>";
        echo "</tr>";
    }

    // Verificar tabla disponibilidad_empresas
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM disponibilidad_empresas");
        $countDisp = $stmt->fetchColumn();
        echo "<tr>";
        echo "<td>Empresas con disponibilidad configurada</td>";
        if ($countDisp > 0) {
            echo "<td style='color: green; font-weight: bold;'>✓ {$countDisp} registros</td>";
        } else {
            echo "<td style='color: orange; font-weight: bold;'>⚠ 0 registros (empresa_a debe configurar disponibilidad)</td>";
        }
        echo "</tr>";
    } catch (PDOException $e) {
        echo "<tr>";
        echo "<td>Tabla disponibilidad_empresas</td>";
        echo "<td style='color: red; font-weight: bold;'>✗ NO EXISTE (EJECUTAR setup_database_dinamico.php)</td>";
        echo "</tr>";
    }

    echo "</table>";

    // Resultado final
    if ($bloqueIdNullable && $bloqueGlobalIdExiste) {
        echo "<h2 style='color: green;'>✓✓✓ SISTEMA LISTO ✓✓✓</h2>";
        echo "<p><strong>La migración fue exitosa.</strong> Ahora empresa_b puede solicitar reuniones sin errores.</p>";
        echo "<h3>Próximos pasos:</h3>";
        echo "<ol>";
        echo "<li>Login como empresa_a → Configurar disponibilidad en wizard</li>";
        echo "<li>Login como empresa_b → Seleccionar empresa_a → Ver bloques disponibles → Solicitar reunión</li>";
        echo "<li>Login como empresa_a → Aceptar solicitud → Mesa asignada automáticamente</li>";
        echo "</ol>";
    } else {
        echo "<h2 style='color: red;'>✗ MIGRACIÓN PENDIENTE</h2>";
        echo "<p>Ejecutar scripts en orden:</p>";
        echo "<ol>";
        echo "<li>setup_database_dinamico.php (crear tablas nuevas)</li>";
        echo "<li>migrar_reuniones_tabla.php (modificar tabla reuniones)</li>";
        echo "</ol>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error de conexión: " . $e->getMessage() . "</p>";
}
?>
