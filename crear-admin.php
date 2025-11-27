<?php
/**
 * Script temporal para crear usuario administrador
 * ELIMINAR ESTE ARCHIVO DESPUÉS DE CREAR EL ADMIN
 */

require_once 'config/database.php';

try {
    $pdo->beginTransaction();

    // Verificar si ya existe un admin
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");
    if ($stmt->fetchColumn() > 0) {
        die("Ya existe un usuario administrador. Elimina este archivo.");
    }

    // Crear empresa para el admin
    $stmt = $pdo->prepare("
        INSERT INTO empresas (nombre, tipo, rubro, email_contacto, telefono, activo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    $stmt->execute([
        'Administración Sistema',
        'grande',
        'Administración del evento',
        'admin@bioceanicocentral.cl',
        '000000000'
    ]);

    $empresaId = $pdo->lastInsertId();

    // Crear usuario admin
    // Contraseña: admin123
    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nombre_completo, email, password_hash, rol, empresa_id, activo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    $stmt->execute([
        'Administrador del Sistema',
        'admin@bioceanicocentral.cl',
        $passwordHash,
        'admin',
        $empresaId
    ]);

    $pdo->commit();

    echo "<h1>✓ Usuario administrador creado exitosamente</h1>";
    echo "<p><strong>Email:</strong> admin@bioceanicocentral.cl</p>";
    echo "<p><strong>Contraseña:</strong> admin123</p>";
    echo "<p><a href='views/login.php'>Ir a Login</a></p>";
    echo "<hr>";
    echo "<p style='color: red;'><strong>IMPORTANTE:</strong> Elimina este archivo (crear-admin.php) por seguridad.</p>";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error al crear usuario admin: " . $e->getMessage());
}
?>
