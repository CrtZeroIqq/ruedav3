<?php
/**
 * Generador de Hash de Contraseña
 * 
 * Este script genera el hash correcto para la contraseña
 * que usarás en los datos de prueba.
 */

$password = 'prueba123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<style>
body {
    font-family: 'Courier New', monospace;
    background: #1e1e1e;
    color: #00ff00;
    padding: 40px;
    line-height: 1.6;
}
.container {
    max-width: 800px;
    margin: 0 auto;
    background: #2d2d2d;
    padding: 30px;
    border: 2px solid #00ff00;
    border-radius: 5px;
}
h1 {
    color: #00ff00;
    text-align: center;
    border-bottom: 2px solid #00ff00;
    padding-bottom: 10px;
}
.info {
    background: #1a1a1a;
    padding: 20px;
    margin: 20px 0;
    border-left: 4px solid #00ff00;
}
.hash {
    background: #0a0a0a;
    padding: 15px;
    margin: 10px 0;
    word-break: break-all;
    font-size: 14px;
    color: #ffff00;
}
.verify {
    margin-top: 20px;
    padding: 20px;
    background: #1a1a1a;
}
</style>";

echo "<div class='container'>";
echo "<h1>🔐 GENERADOR DE HASH</h1>";

echo "<div class='info'>";
echo "<strong>Contraseña:</strong> <span style='color: #ffff00;'>$password</span><br>";
echo "<strong>Algoritmo:</strong> PASSWORD_DEFAULT (bcrypt)<br>";
echo "<strong>PHP Version:</strong> " . phpversion();
echo "</div>";

echo "<div class='info'>";
echo "<h3>Hash Generado:</h3>";
echo "<div class='hash'>$hash</div>";
echo "</div>";

// Verificar que funciona
$verify = password_verify($password, $hash);

echo "<div class='verify'>";
echo "<h3>✅ Verificación:</h3>";
echo "<strong>password_verify('$password', hash):</strong> ";
echo $verify ? "<span style='color: #00ff00;'>✓ TRUE - Funciona correctamente</span>" : "<span style='color: #ff0000;'>✗ FALSE - Error</span>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>📋 Cómo usar:</h3>";
echo "<ol>";
echo "<li>Copia el hash de arriba</li>";
echo "<li>Abre <code>DATOS_PRUEBA_EMPRESAS.sql</code></li>";
echo "<li>Busca: <code>\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi</code></li>";
echo "<li>Reemplaza TODOS por el hash nuevo</li>";
echo "<li>Guarda el archivo</li>";
echo "<li>Ejecuta el SQL nuevamente</li>";
echo "</ol>";
echo "</div>";

// Generar algunos hashes alternativos
echo "<div class='info'>";
echo "<h3>🔑 Otros hashes útiles:</h3>";

$passwords = [
    'admin123' => password_hash('admin123', PASSWORD_DEFAULT),
    'bioceanico2025' => password_hash('@Bioceanico2025@', PASSWORD_DEFAULT),
    'test123' => password_hash('test123', PASSWORD_DEFAULT),
];

foreach ($passwords as $pass => $hash_alt) {
    echo "<strong>$pass:</strong><br>";
    echo "<div class='hash'>$hash_alt</div>";
}
echo "</div>";

echo "</div>";
?>