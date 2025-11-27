<?php
/**
 * Script de configuración de base de datos para SISTEMA DINÁMICO
 * Rueda de Negocios Arica 2025
 *
 * Ejecutar UNA SOLA VEZ para crear las tablas necesarias
 * URL: https://tudominio.com/rueda-negocios-arica/setup_database_dinamico.php
 */

require_once 'config/database.php';

echo "<html><head><meta charset='UTF-8'><title>Setup Base de Datos Dinámico</title>";
echo "<style>
body { font-family: Arial; background: #f0f4f8; padding: 20px; }
.box { background: white; padding: 20px; margin: 20px auto; max-width: 900px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #1a73e8; }
.success { background: #d4edda; border-left: 4px solid #28a745; padding: 10px; margin: 10px 0; }
.error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 10px; margin: 10px 0; }
.info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 10px; margin: 10px 0; }
pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style></head><body>";

echo "<div class='box'>";
echo "<h1>🔧 Setup Base de Datos - Sistema Dinámico</h1>";
echo "<p><strong>Fecha del evento:</strong> 28 de Noviembre, 2025</p>";
echo "<p><strong>Horario:</strong> 11:00 - 12:30</p>";
echo "<p><strong>Mesas físicas:</strong> 15</p>";
echo "<hr>";

try {
    // =====================================================
    // 1. CREAR TABLA: bloques_horarios_globales
    // =====================================================
    echo "<h2>1. Tabla: bloques_horarios_globales</h2>";

    $sqlBloques = "
    CREATE TABLE IF NOT EXISTS bloques_horarios_globales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATE NOT NULL,
        hora_inicio TIME NOT NULL,
        hora_fin TIME NOT NULL,
        orden INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_fecha (fecha),
        INDEX idx_orden (orden)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sqlBloques);
    echo "<div class='success'>✓ Tabla 'bloques_horarios_globales' verificada/creada</div>";

    // Verificar si ya hay bloques
    $stmt = $pdo->query("SELECT COUNT(*) FROM bloques_horarios_globales");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Insertar los 4 bloques del evento
        $bloques = [
            ['2025-11-28', '11:00:00', '11:15:00', 1],
            ['2025-11-28', '11:20:00', '11:35:00', 2],
            ['2025-11-28', '11:40:00', '11:55:00', 3],
            ['2025-11-28', '12:00:00', '12:15:00', 4],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO bloques_horarios_globales (fecha, hora_inicio, hora_fin, orden)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($bloques as $b) {
            $stmt->execute($b);
        }

        echo "<div class='success'>✓ Se insertaron 4 bloques horarios globales</div>";
        echo "<pre>";
        echo "Bloque 1: 11:00 - 11:15\n";
        echo "Bloque 2: 11:20 - 11:35\n";
        echo "Bloque 3: 11:40 - 11:55\n";
        echo "Bloque 4: 12:00 - 12:15\n";
        echo "</pre>";
    } else {
        echo "<div class='info'>ℹ️ Ya existen $count bloques en la tabla</div>";
    }

    // =====================================================
    // 2. CREAR TABLA: empresas
    // =====================================================
    echo "<h2>2. Tabla: empresas</h2>";

    $sqlEmpresas = "
    CREATE TABLE IF NOT EXISTS empresas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL,
        tipo ENUM('grande', 'pyme') NOT NULL COMMENT 'grande=empresa_a (demandante), pyme=empresa_b (oferente)',
        rubro VARCHAR(255),
        email_contacto VARCHAR(255) NOT NULL,
        telefono VARCHAR(50),
        direccion TEXT,
        descripcion TEXT,
        logo VARCHAR(255),
        tags_busqueda JSON COMMENT 'Etiquetas que busca (solo empresa_a)',
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tipo (tipo),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sqlEmpresas);
    echo "<div class='success'>✓ Tabla 'empresas' verificada/creada</div>";

    // =====================================================
    // 3. CREAR TABLA: usuarios
    // =====================================================
    echo "<h2>3. Tabla: usuarios</h2>";

    $sqlUsuarios = "
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        nombre_completo VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        rol ENUM('empresa_a', 'empresa_b', 'admin') NOT NULL,
        ultimo_acceso TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        INDEX idx_email (email),
        INDEX idx_rol (rol)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sqlUsuarios);
    echo "<div class='success'>✓ Tabla 'usuarios' verificada/creada</div>";

    // =====================================================
    // 4. CREAR TABLA: disponibilidad_empresas
    // =====================================================
    echo "<h2>4. Tabla: disponibilidad_empresas</h2>";

    $sqlDisponibilidad = "
    CREATE TABLE IF NOT EXISTS disponibilidad_empresas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        bloque_id INT NOT NULL,
        disponible TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (bloque_id) REFERENCES bloques_horarios_globales(id) ON DELETE CASCADE,
        UNIQUE KEY unique_empresa_bloque (empresa_id, bloque_id),
        INDEX idx_empresa (empresa_id),
        INDEX idx_bloque (bloque_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sqlDisponibilidad);
    echo "<div class='success'>✓ Tabla 'disponibilidad_empresas' verificada/creada</div>";

    // =====================================================
    // 5. CREAR TABLA: reuniones
    // =====================================================
    echo "<h2>5. Tabla: reuniones</h2>";

    $sqlReuniones = "
    CREATE TABLE IF NOT EXISTS reuniones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_a_id INT NOT NULL COMMENT 'Demandante (busca servicios)',
        empresa_b_id INT NOT NULL COMMENT 'Oferente (ofrece servicios)',
        bloque_global_id INT NOT NULL COMMENT 'Bloque horario global',
        mesa_asignada INT NULL COMMENT 'Mesa física 1-15, se asigna al confirmar',
        estado ENUM('pendiente', 'confirmada', 'rechazada', 'cancelada') DEFAULT 'pendiente',
        notas TEXT COMMENT 'Mensaje de la solicitud',
        fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_respuesta TIMESTAMP NULL,
        respondido_por INT NULL COMMENT 'ID del usuario que respondió',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_a_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (empresa_b_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (bloque_global_id) REFERENCES bloques_horarios_globales(id) ON DELETE CASCADE,
        INDEX idx_empresa_a (empresa_a_id),
        INDEX idx_empresa_b (empresa_b_id),
        INDEX idx_bloque (bloque_global_id),
        INDEX idx_estado (estado),
        INDEX idx_mesa (mesa_asignada)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sqlReuniones);
    echo "<div class='success'>✓ Tabla 'reuniones' verificada/creada</div>";

    // =====================================================
    // 6. VERIFICAR SI EXISTE TABLA VIEJA (bloques_horarios)
    // =====================================================
    echo "<h2>6. Verificación de tablas antiguas</h2>";

    $stmt = $pdo->query("SHOW TABLES LIKE 'bloques_horarios'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='error'>⚠️ ADVERTENCIA: Existe la tabla antigua 'bloques_horarios'</div>";
        echo "<div class='info'>Esta tabla NO se usa en el sistema dinámico. Considera eliminarla manualmente si ya no la necesitas.</div>";
    } else {
        echo "<div class='success'>✓ No existe tabla antigua 'bloques_horarios'</div>";
    }

    // =====================================================
    // RESUMEN FINAL
    // =====================================================
    echo "<hr>";
    echo "<h2>✅ Setup Completado</h2>";
    echo "<div class='success'>";
    echo "<strong>Estructura de base de datos lista para sistema dinámico:</strong><br>";
    echo "✓ bloques_horarios_globales (4 bloques fijos)<br>";
    echo "✓ empresas (demandantes y oferentes)<br>";
    echo "✓ usuarios (acceso al sistema)<br>";
    echo "✓ disponibilidad_empresas (marca bloques disponibles)<br>";
    echo "✓ reuniones (con asignación dinámica de mesas 1-15)<br>";
    echo "</div>";

    echo "<div class='info'>";
    echo "<strong>Próximos pasos:</strong><br>";
    echo "1. Eliminar este archivo (setup_database_dinamico.php) por seguridad<br>";
    echo "2. Registrar empresas desde el formulario<br>";
    echo "3. Empresas tipo 'grande' (empresa_a) marcan su disponibilidad<br>";
    echo "4. Empresas tipo 'pyme' (empresa_b) solicitan reuniones<br>";
    echo "5. Sistema asigna mesas automáticamente al confirmar<br>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<strong>❌ ERROR:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</div></body></html>";
?>
