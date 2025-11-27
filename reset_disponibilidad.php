<?php
/**
 * SCRIPT PARA RESETEAR DISPONIBILIDAD
 * Permite a empresa_a volver a ver el wizard de configuración
 */

require_once 'config/database.php';
require_once 'config/session.php';

// Solo empresa_a puede usar este script
if (!isset($_SESSION['empresa_id']) || $_SESSION['rol'] !== 'empresa_a') {
    die("Debes iniciar sesión como Empresa A (Demandante)");
}

$empresa_id = $_SESSION['empresa_id'];

echo "<html><head><meta charset='UTF-8'><title>Resetear Disponibilidad</title>";
echo "<style>
body { font-family: Arial; background: #f0f4f8; padding: 40px; }
.box { background: white; padding: 30px; margin: 20px auto; max-width: 700px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
h1 { color: #1a73e8; }
.warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
.info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; }
.success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; }
.btn { display: inline-block; padding: 12px 24px; background: #dc3545; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 10px 5px; }
.btn-primary { background: #1a73e8; }
.btn:hover { opacity: 0.9; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
td, th { border: 1px solid #ddd; padding: 10px; text-align: left; }
th { background: #f8f9fa; }
</style></head><body>";

echo "<div class='box'>";

try {
    // Obtener datos de la empresa
    $stmt = $pdo->prepare("SELECT nombre, tipo FROM empresas WHERE id = ?");
    $stmt->execute([$empresa_id]);
    $empresa = $stmt->fetch();

    echo "<h1>🔄 Resetear Disponibilidad</h1>";
    echo "<p><strong>Empresa:</strong> {$empresa['nombre']}</p>";
    echo "<p><strong>ID:</strong> $empresa_id</p>";

    // Verificar disponibilidad actual
    $stmt = $pdo->prepare("
        SELECT
            bg.orden,
            bg.hora_inicio,
            bg.hora_fin,
            de.disponible
        FROM disponibilidad_empresas de
        JOIN bloques_horarios_globales bg ON bg.id = de.bloque_id
        WHERE de.empresa_id = ?
        ORDER BY bg.orden
    ");
    $stmt->execute([$empresa_id]);
    $disponibilidad = $stmt->fetchAll();

    if (empty($disponibilidad)) {
        echo "<div class='success'>";
        echo "✅ <strong>No hay disponibilidad configurada</strong><br>";
        echo "El wizard ya debería aparecer automáticamente.";
        echo "</div>";
        echo "<a href='views/panel-empresa-a.php' class='btn btn-primary'>Ir al Panel</a>";
    } else {
        // Mostrar disponibilidad actual
        echo "<div class='info'>";
        echo "<strong>Disponibilidad actual:</strong>";
        echo "<table>";
        echo "<tr><th>Bloque</th><th>Horario</th><th>Estado</th></tr>";
        foreach ($disponibilidad as $d) {
            $estado = $d['disponible'] ? '✅ Disponible' : '❌ No disponible';
            echo "<tr>";
            echo "<td>Bloque {$d['orden']}</td>";
            echo "<td>{$d['hora_inicio']} - {$d['hora_fin']}</td>";
            echo "<td>$estado</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";

        // Verificar si hay reuniones confirmadas
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM reuniones
            WHERE empresa_a_id = ? AND estado = 'confirmada'
        ");
        $stmt->execute([$empresa_id]);
        $reunionesConfirmadas = $stmt->fetchColumn();

        if ($reunionesConfirmadas > 0) {
            echo "<div class='warning'>";
            echo "⚠️ <strong>ADVERTENCIA:</strong> Tienes $reunionesConfirmadas reuniones confirmadas.<br>";
            echo "Si reseteas la disponibilidad, estas reuniones NO se eliminarán, pero podrías generar inconsistencias.";
            echo "</div>";
        }

        // Formulario de confirmación
        if (isset($_POST['confirmar_reset'])) {
            // EJECUTAR RESET
            $pdo->prepare("DELETE FROM disponibilidad_empresas WHERE empresa_id = ?")->execute([$empresa_id]);

            echo "<div class='success'>";
            echo "✅ <strong>Disponibilidad eliminada exitosamente</strong><br>";
            echo "Ahora cuando vayas al panel, verás el wizard de configuración.";
            echo "</div>";
            echo "<a href='views/panel-empresa-a.php' class='btn btn-primary'>Ir al Panel (verás el wizard)</a>";
        } else {
            // MOSTRAR CONFIRMACIÓN
            echo "<div class='warning'>";
            echo "<strong>⚠️ ¿Estás seguro?</strong><br>";
            echo "Esto eliminará tu disponibilidad actual y te permitirá volver a configurarla desde cero.";
            if ($reunionesConfirmadas > 0) {
                echo "<br><br><strong>Las reuniones confirmadas NO se eliminarán.</strong>";
            }
            echo "</div>";

            echo "<form method='POST'>";
            echo "<input type='hidden' name='confirmar_reset' value='1'>";
            echo "<button type='submit' class='btn'>Sí, Resetear Disponibilidad</button>";
            echo "<a href='views/panel-empresa-a.php' class='btn btn-primary'>Cancelar</a>";
            echo "</form>";
        }
    }

} catch (PDOException $e) {
    echo "<div class='warning'>";
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</div>";
echo "</body></html>";
?>
