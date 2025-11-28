<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API - Visor de Mesas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        pre {
            background: #1f2937;
            color: #10b981;
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
        button {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        button:hover {
            background: #2563eb;
        }
        .info {
            background: #dbeafe;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Test API - Visor de Mesas</h1>

        <div class="info">
            <strong>📍 Ruta de la API:</strong> <code>api/get_mesas_visor.php</code><br>
            <strong>🌐 URL completa:</strong> <span id="api-url"></span>
        </div>

        <button onclick="testAPI()">🚀 Probar API</button>
        <button onclick="location.href='visor-rueda.php'">👁️ Ir al Visor</button>
        <button onclick="location.reload()">🔄 Recargar</button>

        <div id="status"></div>
        <div id="result"></div>
    </div>

    <script>
        const baseURL = window.location.origin + window.location.pathname.replace('test-visor.php', '');
        const apiURL = baseURL + 'api/get_mesas_visor.php';
        document.getElementById('api-url').textContent = apiURL;

        async function testAPI() {
            const statusDiv = document.getElementById('status');
            const resultDiv = document.getElementById('result');

            statusDiv.innerHTML = '<div class="status">⏳ Probando conexión con la API...</div>';
            resultDiv.innerHTML = '';

            try {
                const response = await fetch(apiURL);

                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                console.log('Data received:', data);

                if (data.success) {
                    statusDiv.innerHTML = `
                        <div class="status success">
                            ✅ Conexión exitosa con la API
                        </div>
                        <div class="info">
                            <strong>Datos recibidos:</strong><br>
                            • Empresas: ${data.estadisticas.total_empresas}<br>
                            • Bloques horarios: ${data.bloques.length}<br>
                            • Bloque actual: ${data.bloque_actual.hora_inicio} - ${data.bloque_actual.hora_fin}<br>
                            • Slots disponibles: ${data.estadisticas.slots_disponibles}<br>
                            • Slots ocupados: ${data.estadisticas.slots_ocupados}<br>
                            • % Ocupación: ${data.estadisticas.porcentaje_ocupacion}%
                        </div>
                    `;

                    resultDiv.innerHTML = `
                        <h3>📊 Respuesta JSON completa:</h3>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                } else {
                    statusDiv.innerHTML = `
                        <div class="status error">
                            ❌ La API respondió con error:<br>
                            ${data.error || data.mensaje}
                        </div>
                    `;

                    resultDiv.innerHTML = `
                        <h3>📄 Respuesta recibida:</h3>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }

            } catch (error) {
                console.error('Error completo:', error);

                statusDiv.innerHTML = `
                    <div class="status error">
                        ❌ Error de conexión:<br>
                        ${error.message}
                    </div>
                    <div class="info">
                        <strong>Posibles causas:</strong><br>
                        1. La base de datos no está configurada<br>
                        2. No existen bloques horarios en la tabla bloques_horarios_globales<br>
                        3. No existen empresas registradas<br>
                        4. Error de sintaxis en PHP<br>
                        5. Problema con la conexión PDO<br><br>
                        <strong>Revise:</strong><br>
                        • La consola del navegador (F12)<br>
                        • Los logs de error de PHP<br>
                        • La configuración en config/database.php
                    </div>
                `;

                resultDiv.innerHTML = `
                    <h3>🐛 Error completo:</h3>
                    <pre>${error.stack || error.message}</pre>
                `;
            }
        }

        // Test automático al cargar
        window.onload = function() {
            testAPI();
        };
    </script>
</body>
</html>
