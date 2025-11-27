<?php
/**
 * Script de migración para tabla reuniones
 * Hace que bloque_id sea nullable para permitir usar bloque_global_id
 */

require_once 'config/database.php';

echo "<h1>Migración de tabla reuniones</h1>";
echo "<p>Este script hará que la columna 'bloque_id' sea nullable (opcional)</p>";

try {
    // Verificar estructura actual
    echo "<h2>Paso 1: Verificando estructura actual...</h2>";
    $stmt = $pdo->query("DESCRIBE reuniones");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tieneBloqueid = false;
    $tieneBloqueGlobalId = false;
    $bloqueIdEsNullable = false;

    foreach ($columnas as $col) {
        if ($col['Field'] === 'bloque_id') {
            $tieneBloqueid = true;
            $bloqueIdEsNullable = ($col['Null'] === 'YES');
            echo "✓ Columna bloque_id encontrada - Nullable: " . ($bloqueIdEsNullable ? 'SÍ' : 'NO') . "<br>";
        }
        if ($col['Field'] === 'bloque_global_id') {
            $tieneBloqueGlobalId = true;
            echo "✓ Columna bloque_global_id encontrada<br>";
        }
    }

    if (!$tieneBloqueid) {
        echo "<p style='color: orange;'>⚠ La columna bloque_id no existe. No se necesita migración.</p>";
        exit;
    }

    if (!$tieneBloqueGlobalId) {
        echo "<p style='color: red;'>✗ ERROR: La columna bloque_global_id no existe. Ejecute setup_database_dinamico.php primero.</p>";
        exit;
    }

    if ($bloqueIdEsNullable) {
        echo "<p style='color: green;'>✓ La columna bloque_id ya es nullable. No se necesita migración.</p>";
        exit;
    }

    // Hacer la migración
    echo "<h2>Paso 2: Modificando columna bloque_id...</h2>";

    // NOTA: ALTER TABLE hace commit implícito en MySQL, no necesitamos transacción
    $sql = "ALTER TABLE reuniones MODIFY COLUMN bloque_id INT(11) NULL DEFAULT NULL";
    $pdo->exec($sql);

    echo "✓ Columna bloque_id modificada a nullable<br>";

    echo "<h2>Paso 3: Verificando cambios...</h2>";
    $stmt = $pdo->query("DESCRIBE reuniones");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columnas as $col) {
        if ($col['Field'] === 'bloque_id') {
            echo "✓ bloque_id: Type=" . $col['Type'] . ", Null=" . $col['Null'] . ", Default=" . ($col['Default'] ?? 'NULL') . "<br>";
        }
        if ($col['Field'] === 'bloque_global_id') {
            echo "✓ bloque_global_id: Type=" . $col['Type'] . ", Null=" . $col['Null'] . ", Default=" . ($col['Default'] ?? 'NULL') . "<br>";
        }
    }

    echo "<h2 style='color: green;'>✓ Migración completada exitosamente!</h2>";
    echo "<p>Ahora la tabla reuniones puede usar bloque_global_id sin necesidad de llenar bloque_id.</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
