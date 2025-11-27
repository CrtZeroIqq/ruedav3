<?php
/**
 * Muestra la versión actual del repositorio en el servidor
 */

echo "<html><head><meta charset='UTF-8'><title>Versión Git</title>";
echo "<style>
body { font-family: monospace; background: #1e1e1e; color: #00ff00; padding: 20px; }
.info { background: #2d2d2d; padding: 15px; margin: 15px 0; border-left: 4px solid #00ff00; }
pre { background: #1a1a1a; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 Versión Git del Servidor</h1>";
echo "<hr>";

// Obtener commit actual
$gitDir = __DIR__ . '/.git';

if (!file_exists($gitDir)) {
    echo "<div class='info' style='border-color: #ff0000; color: #ff6b6b;'>";
    echo "❌ No es un repositorio Git o .git no está accesible";
    echo "</div>";
} else {
    // Leer HEAD
    $headFile = $gitDir . '/HEAD';
    if (file_exists($headFile)) {
        $head = trim(file_get_contents($headFile));
        echo "<div class='info'>";
        echo "<strong>Branch actual:</strong><br>";
        echo "<pre>$head</pre>";
        echo "</div>";
    }

    // Intentar obtener el commit actual usando shell
    $output = [];
    $return = 0;

    exec('cd ' . escapeshellarg(__DIR__) . ' && git rev-parse HEAD 2>&1', $output, $return);

    if ($return === 0 && !empty($output)) {
        $commit = trim($output[0]);
        echo "<div class='info'>";
        echo "<strong>Commit actual (hash completo):</strong><br>";
        echo "<pre>$commit</pre>";
        echo "</div>";

        // Obtener hash corto
        $shortCommit = substr($commit, 0, 7);
        echo "<div class='info'>";
        echo "<strong>Commit actual (hash corto):</strong><br>";
        echo "<pre>$shortCommit</pre>";
        echo "</div>";
    }

    // Intentar obtener el mensaje del commit
    $output = [];
    exec('cd ' . escapeshellarg(__DIR__) . ' && git log -1 --pretty=%B 2>&1', $output, $return);

    if ($return === 0 && !empty($output)) {
        $message = implode("\n", $output);
        echo "<div class='info'>";
        echo "<strong>Último commit:</strong><br>";
        echo "<pre>" . htmlspecialchars($message) . "</pre>";
        echo "</div>";
    }

    // Mostrar últimos 5 commits
    $output = [];
    exec('cd ' . escapeshellarg(__DIR__) . ' && git log -5 --oneline 2>&1', $output, $return);

    if ($return === 0 && !empty($output)) {
        echo "<div class='info'>";
        echo "<strong>Últimos 5 commits:</strong><br>";
        echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
        echo "</div>";
    }

    // Verificar si hay cambios sin commit
    $output = [];
    exec('cd ' . escapeshellarg(__DIR__) . ' && git status --porcelain 2>&1', $output, $return);

    if ($return === 0) {
        if (empty($output)) {
            echo "<div class='info'>";
            echo "✅ <strong>Working tree limpio</strong> (no hay cambios sin commit)";
            echo "</div>";
        } else {
            echo "<div class='info' style='border-color: #ffaa00; color: #ffaa00;'>";
            echo "⚠️ <strong>Hay cambios sin commit:</strong><br>";
            echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
            echo "</div>";
        }
    }

    // Verificar si está sincronizado con remoto
    $output = [];
    exec('cd ' . escapeshellarg(__DIR__) . ' && git rev-parse @{u} 2>&1', $output, $return);

    if ($return === 0 && !empty($output)) {
        $remoteCommit = trim($output[0]);

        $output2 = [];
        exec('cd ' . escapeshellarg(__DIR__) . ' && git rev-parse HEAD 2>&1', $output2);
        $localCommit = trim($output2[0]);

        if ($remoteCommit === $localCommit) {
            echo "<div class='info'>";
            echo "✅ <strong>Sincronizado con remoto</strong>";
            echo "</div>";
        } else {
            echo "<div class='info' style='border-color: #ff0000; color: #ff6b6b;'>";
            echo "❌ <strong>DESINCRONIZADO con remoto</strong><br>";
            echo "Local: " . substr($localCommit, 0, 7) . "<br>";
            echo "Remoto: " . substr($remoteCommit, 0, 7) . "<br>";
            echo "<br><strong>NECESITAS HACER:</strong><br>";
            echo "<code>git pull</code>";
            echo "</div>";
        }
    }
}

echo "<hr>";
echo "<div class='info'>";
echo "<strong>Commits esperados en el servidor:</strong><br>";
echo "<pre>";
echo "497c1f3 - Fix: Corregir errores en wizard de disponibilidad y panel empresa A\n";
echo "46c9273 - Add: Script para resetear disponibilidad de empresa_a\n";
echo "92adda2 - Add: Script de diagnóstico para verificar estado del sistema dinámico\n";
echo "f32a9b8 - Fix: Implementar sistema dinámico de reuniones correctamente\n";
echo "</pre>";
echo "</div>";

echo "</body></html>";
?>
