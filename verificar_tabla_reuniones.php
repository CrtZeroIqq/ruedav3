<?php
require_once 'config/database.php';

echo "<h1>Estructura de tabla reuniones</h1>";

try {
    // Obtener estructura de la tabla
    $stmt = $pdo->query("DESCRIBE reuniones");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

    foreach ($columnas as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    // Verificar si hay datos en la tabla
    $stmt = $pdo->query("SELECT COUNT(*) FROM reuniones");
    $count = $stmt->fetchColumn();

    echo "<br><p><strong>Total de registros en reuniones:</strong> $count</p>";

    // Mostrar algunos registros de ejemplo
    if ($count > 0) {
        echo "<h2>Primeros 5 registros:</h2>";
        $stmt = $pdo->query("SELECT * FROM reuniones LIMIT 5");
        $reuniones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table border='1' cellpadding='5'>";
        if (!empty($reuniones)) {
            echo "<tr>";
            foreach (array_keys($reuniones[0]) as $campo) {
                echo "<th>" . htmlspecialchars($campo) . "</th>";
            }
            echo "</tr>";

            foreach ($reuniones as $reunion) {
                echo "<tr>";
                foreach ($reunion as $valor) {
                    echo "<td>" . htmlspecialchars($valor ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
