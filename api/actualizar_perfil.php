<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Verificar autenticación
if (!isLoggedIn()) {
    jsonResponse(false, 'No autorizado');
}

// Solo empresas A y B pueden editar su perfil
$rol = getUserRole();
if ($rol !== ROL_EMPRESA_A && $rol !== ROL_EMPRESA_B) {
    jsonResponse(false, 'No tiene permisos para editar perfil');
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

$empresaId = getEmpresaId();
$accion = sanitize($_POST['accion'] ?? '');

if (!$empresaId || empty($accion)) {
    jsonResponse(false, 'Datos incompletos');
}

try {
    switch ($accion) {
        case 'actualizar_datos':
            actualizarDatos($empresaId, $_POST, $pdo);
            break;
            
        case 'actualizar_logo':
            actualizarLogo($empresaId, $_FILES, $pdo);
            break;
            
        default:
            jsonResponse(false, 'Acción no válida');
    }
    
} catch (PDOException $e) {
    error_log("Error en actualizar_perfil.php: " . $e->getMessage());
    jsonResponse(false, 'Error al procesar la solicitud');
}

/**
 * Actualizar datos de la empresa
 */
function actualizarDatos($empresaId, $datos, $pdo) {
    $nombre = sanitize($datos['nombre'] ?? '');
    $rubro = sanitize($datos['rubro'] ?? '');
    $email = sanitize($datos['email_contacto'] ?? '');
    $telefono = sanitize($datos['telefono'] ?? '');
    $direccion = sanitize($datos['direccion'] ?? '');
    $descripcion = sanitize($datos['descripcion'] ?? '');
    $tagsBusqueda = $datos['tags_busqueda'] ?? '[]';
    
    // Validaciones
    if (empty($nombre)) {
        jsonResponse(false, 'El nombre es obligatorio');
    }
    
    if (empty($email) || !isValidEmail($email)) {
        jsonResponse(false, 'Email inválido');
    }
    
    // Validar y procesar tags
    $tagsArray = json_decode($tagsBusqueda, true);
    if (!is_array($tagsArray)) {
        $tagsArray = [];
    }
    
    // Limitar a 5 tags y validar cada uno
    $tagsArray = array_slice($tagsArray, 0, 5);
    $tagsArray = array_map(function($tag) {
        return substr(trim($tag), 0, 50); // Máximo 50 caracteres por tag
    }, $tagsArray);
    $tagsArray = array_filter($tagsArray); // Eliminar tags vacíos
    $tagsBusqueda = json_encode(array_values($tagsArray), JSON_UNESCAPED_UNICODE);
    
    try {
        // Verificar que el email no esté en uso por otra empresa
        $stmt = $pdo->prepare("SELECT id FROM empresas WHERE email_contacto = ? AND id != ?");
        $stmt->execute([$email, $empresaId]);
        if ($stmt->fetch()) {
            jsonResponse(false, 'El email ya está registrado por otra empresa');
        }
        
        // Actualizar empresa
        $stmt = $pdo->prepare("
            UPDATE empresas 
            SET nombre = ?,
                rubro = ?,
                email_contacto = ?,
                telefono = ?,
                direccion = ?,
                descripcion = ?,
                tags_busqueda = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $nombre,
            $rubro,
            $email,
            $telefono,
            $direccion,
            $descripcion,
            $tagsBusqueda,
            $empresaId
        ]);
        
        // Actualizar nombre en sesión si cambió
        $_SESSION['nombre'] = $nombre;
        
        jsonResponse(true, 'Perfil actualizado exitosamente');
        
    } catch (PDOException $e) {
        error_log("Error al actualizar datos: " . $e->getMessage());
        jsonResponse(false, 'Error al actualizar el perfil');
    }
}

/**
 * Actualizar logo de la empresa
 */
function actualizarLogo($empresaId, $files, $pdo) {
    if (!isset($files['logo']) || $files['logo']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, 'No se recibió ningún archivo o hubo un error en la carga');
    }
    
    $file = $files['logo'];
    
    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = $file['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        jsonResponse(false, 'Solo se permiten imágenes (JPG, PNG, GIF, WEBP)');
    }
    
    // Validar tamaño (máximo 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB en bytes
    if ($file['size'] > $maxSize) {
        jsonResponse(false, 'El archivo es demasiado grande. Máximo 5MB');
    }
    
    // Validar que sea realmente una imagen
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        jsonResponse(false, 'El archivo no es una imagen válida');
    }
    
    try {
        // Obtener logo actual
        $stmt = $pdo->prepare("SELECT logo FROM empresas WHERE id = ?");
        $stmt->execute([$empresaId]);
        $empresa = $stmt->fetch();
        
        // Generar nombre único para el archivo
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nombreArchivo = 'logo_' . $empresaId . '_' . time() . '.' . $extension;
        $rutaDestino = UPLOAD_PATH . $nombreArchivo;
        
        // Crear carpeta si no existe
        if (!file_exists(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }
        
        // Mover archivo subido
        if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
            jsonResponse(false, 'Error al guardar el archivo');
        }
        
        // Actualizar base de datos
        $stmt = $pdo->prepare("UPDATE empresas SET logo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nombreArchivo, $empresaId]);
        
        // Eliminar logo anterior si existe
        if ($empresa['logo'] && file_exists(UPLOAD_PATH . $empresa['logo'])) {
            unlink(UPLOAD_PATH . $empresa['logo']);
        }
        
        jsonResponse(true, 'Logo actualizado exitosamente', [
            'logo_url' => UPLOAD_URL . $nombreArchivo
        ]);
        
    } catch (PDOException $e) {
        // Si hubo error, eliminar el archivo subido
        if (isset($rutaDestino) && file_exists($rutaDestino)) {
            unlink($rutaDestino);
        }
        error_log("Error al actualizar logo: " . $e->getMessage());
        jsonResponse(false, 'Error al actualizar el logo');
    }
}
?>