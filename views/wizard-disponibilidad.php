<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Verificar rol
if ($_SESSION['rol'] !== 'empresa_a') {
    header('Location: index.php');
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

// Obtener bloques globales
$stmt = $pdo->query("SELECT * FROM bloques_horarios_globales ORDER BY orden ASC");
$bloques = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener mesas ocupadas (otras empresas)
$stmt = $pdo->query("
    SELECT mesa_numero, bloque_id, e.nombre as empresa_nombre
    FROM disponibilidad_empresas de
    INNER JOIN empresas e ON e.id = de.empresa_id
    WHERE de.empresa_id != $empresa_id AND de.disponible = 1
");
$ocupadas = [];
foreach ($stmt->fetchAll() as $row) {
    $key = $row['mesa_numero'] . '_' . $row['bloque_id'];
    $ocupadas[$key] = $row['empresa_nombre'];
}

// Obtener mis selecciones actuales
$stmt = $pdo->prepare("
    SELECT mesa_numero, bloque_id
    FROM disponibilidad_empresas
    WHERE empresa_id = ? AND disponible = 1
");
$stmt->execute([$empresa_id]);
$misSelecciones = [];
foreach ($stmt->fetchAll() as $row) {
    $misSelecciones[] = $row['mesa_numero'] . '_' . $row['bloque_id'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Selecciona tu Mesa y Horarios - Rueda de Negocios</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}

.wizard-container {
    max-width: 1400px;
    width: 100%;;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    overflow: hidden;
    animation: slideUp 0.5s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.wizard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px;
    text-align: center;
    color: white;
}

.wizard-header h1 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 10px;
}

.wizard-header p {
    font-size: 16px;
    opacity: 0.9;
}

.wizard-body {
    padding: 30px;
}

.info-box {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
}

.limit-counter {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 600;
    text-align: center;
}

.grid-container {
    overflow-x: auto;
    margin-bottom: 20px;
}

.mesa-grid {
    display: grid;
    grid-template-columns: 80px repeat(4, 1fr);
    gap: 8px;
    min-width: 700px;
}

.grid-header {
    background: #f5f5f5;
    padding: 12px;
    font-weight: 700;
    text-align: center;
    border-radius: 8px;
    color: #333;
}

.mesa-label {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

.mesa-cell {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    min-height: 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
}

.mesa-cell.disponible:hover {
    border-color: #667eea;
    background: #f0f4ff;
    transform: scale(1.05);
}

.mesa-cell.ocupada {
    background: #ffebee;
    border-color: #ef5350;
    cursor: not-allowed;
    opacity: 0.7;
}

.mesa-cell.seleccionada {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    color: white;
    font-weight: 700;
}

.mesa-cell.seleccionada i {
    font-size: 24px;
    margin-bottom: 5px;
}

.mesa-cell .empresa-nombre {
    font-size: 11px;
    color: #c62828;
    margin-top: 5px;
}

.leyenda {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin: 20px 0;
    flex-wrap: wrap;
}

.leyenda-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.leyenda-cuadro {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: 2px solid #e0e0e0;
}

.leyenda-cuadro.disponible {
    background: white;
}

.leyenda-cuadro.seleccionada {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.leyenda-cuadro.ocupada {
    background: #ffebee;
    border-color: #ef5350;
}

.btn-guardar {
    width: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 18px 30px;
    font-size: 18px;
    font-weight: 700;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 20px;
}

.btn-guardar:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.btn-guardar:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

#mensaje {
    padding: 15px 20px;
    border-radius: 10px;
    margin: 20px 0;
    display: none;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
</head>
<body>

<div class="wizard-container">
    <div class="wizard-header">
        <h1><i class="fas fa-calendar-check"></i> Selecciona tu Mesa y Horarios</h1>
        <p>Elige UNA mesa y máximo 2 bloques horarios donde estarás disponible</p>
    </div>

    <div class="wizard-body">
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Información del Evento:</strong> 28 de Noviembre, 2025 | 11:00 - 12:30 hrs | 15 mesas disponibles
        </div>

        <div class="limit-counter">
            <i class="fas fa-exclamation-triangle"></i>
            Máximo <strong>2 bloques</strong> por empresa | Seleccionadas: <span id="contador">0</span>/2
        </div>

        <form id="formDisponibilidad">
            <div class="grid-container">
                <div class="mesa-grid">
                    <!-- Header -->
                    <div class="grid-header">Mesa</div>
                    <?php foreach ($bloques as $b): ?>
                        <div class="grid-header">
                            Bloque <?= $b['orden'] ?><br>
                            <small><?= date('H:i', strtotime($b['hora_inicio'])) ?></small>
                        </div>
                    <?php endforeach; ?>

                    <!-- Filas de mesas -->
                    <?php for ($mesa = 1; $mesa <= 15; $mesa++): ?>
                        <div class="mesa-label">Mesa <?= $mesa ?></div>

                        <?php foreach ($bloques as $b): ?>
                            <?php
                            $key = $mesa . '_' . $b['id'];
                            $estaOcupada = isset($ocupadas[$key]);
                            $estoyYo = in_array($key, $misSelecciones);
                            $clases = 'mesa-cell';

                            if ($estoyYo) {
                                $clases .= ' seleccionada';
                            } elseif ($estaOcupada) {
                                $clases .= ' ocupada';
                            } else {
                                $clases .= ' disponible';
                            }
                            ?>
                            <div class="<?= $clases ?>"
                                 data-mesa="<?= $mesa ?>"
                                 data-bloque="<?= $b['id'] ?>"
                                 onclick="toggleCelda(this, <?= $estaOcupada ? 'true' : 'false' ?>)">

                                <?php if ($estoyYo): ?>
                                    <i class="fas fa-check-circle"></i>
                                    <small>TÚ</small>
                                <?php elseif ($estaOcupada): ?>
                                    <i class="fas fa-lock"></i>
                                    <div class="empresa-nombre"><?= substr($ocupadas[$key], 0, 15) ?></div>
                                <?php else: ?>
                                    <i class="fas fa-square" style="color: #ddd; font-size: 20px;"></i>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="leyenda">
                <div class="leyenda-item">
                    <div class="leyenda-cuadro disponible"></div>
                    <span>Disponible</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-cuadro seleccionada"></div>
                    <span>Tu selección</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-cuadro ocupada"></div>
                    <span>Ocupada</span>
                </div>
            </div>

            <div id="mensaje"></div>

            <button type="submit" class="btn-guardar">
                <i class="fas fa-save"></i> Guardar Disponibilidad
            </button>
        </form>
    </div>
</div>

<script>
let selecciones = <?= json_encode($misSelecciones) ?>;
const MAX_SELECCIONES = 2;

function actualizarContador() {
    document.getElementById('contador').textContent = selecciones.length;
}

function toggleCelda(elemento, estaOcupada) {
    if (estaOcupada) {
        mostrarMensaje('⚠️ Esta mesa+bloque ya está ocupada por otra empresa', 'error');
        return;
    }

    const mesa = elemento.dataset.mesa;
    const bloque = elemento.dataset.bloque;
    const key = mesa + '_' + bloque;

    if (selecciones.includes(key)) {
        // Deseleccionar
        selecciones = selecciones.filter(s => s !== key);
        elemento.classList.remove('seleccionada');
        elemento.innerHTML = '<i class="fas fa-square" style="color: #ddd; font-size: 20px;"></i>';
    } else {
        // Validar límite
        if (selecciones.length >= MAX_SELECCIONES) {
            mostrarMensaje('⚠️ Solo puedes seleccionar máximo ' + MAX_SELECCIONES + ' bloques. Deselecciona uno primero.', 'error');
            return;
        }

        // Validar que sea la misma mesa
        if (selecciones.length > 0) {
            const mesaActual = selecciones[0].split('_')[0];
            if (mesa !== mesaActual) {
                mostrarMensaje('⚠️ Debes seleccionar bloques de la MISMA mesa. Mesa actual: ' + mesaActual, 'error');
                return;
            }
        }

        // Seleccionar
        selecciones.push(key);
        elemento.classList.add('seleccionada');
        elemento.innerHTML = '<i class="fas fa-check-circle"></i><small>TÚ</small>';
    }

    actualizarContador();
}

document.getElementById('formDisponibilidad').addEventListener('submit', function(e) {
    e.preventDefault();

    if (selecciones.length === 0) {
        mostrarMensaje('⚠️ Debes seleccionar al menos 1 bloque horario', 'error');
        return;
    }

    const btnSubmit = this.querySelector('button[type="submit"]');
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    // Convertir selecciones a formato para enviar
    const formData = new FormData();
    selecciones.forEach(sel => {
        const [mesa, bloque] = sel.split('_');
        formData.append('selecciones[]', JSON.stringify({mesa: mesa, bloque: bloque}));
    });

    fetch('../api/guardar_disponibilidad.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensaje('✅ ' + data.message, 'success');
            setTimeout(() => {
                window.location.href = '../views/panel-empresa-a.php';
            }, 1500);
        } else {
            mostrarMensaje('❌ ' + data.message, 'error');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar Disponibilidad';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('❌ Error de conexión. Por favor, intenta nuevamente.', 'error');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar Disponibilidad';
    });
});

function mostrarMensaje(texto, tipo) {
    const div = document.getElementById('mensaje');
    div.innerHTML = texto;
    div.style.display = 'block';

    if (tipo === 'success') {
        div.style.background = '#d4edda';
        div.style.color = '#155724';
        div.style.borderLeft = '4px solid #28a745';
    } else {
        div.style.background = '#f8d7da';
        div.style.color = '#721c24';
        div.style.borderLeft = '4px solid #dc3545';
    }
}

// Inicializar contador
actualizarContador();
</script>

</body>
</html>
