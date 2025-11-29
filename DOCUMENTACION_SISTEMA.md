# 📘 Sistema de Rueda de Negocios Arica - Documentación Completa

## 📋 Índice

1. [Descripción General](#descripción-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Estructura de la Base de Datos](#estructura-de-la-base-de-datos)
4. [Componentes Principales](#componentes-principales)
5. [Visor de Mesas](#visor-de-mesas)
6. [Panel de Administración](#panel-de-administración)
7. [API Endpoints](#api-endpoints)
8. [Problemas Resueltos](#problemas-resueltos)
9. [Guía de Uso](#guía-de-uso)
10. [Mantenimiento y Troubleshooting](#mantenimiento-y-troubleshooting)

---

## 📖 Descripción General

El **Sistema de Rueda de Negocios Arica** es una plataforma web diseñada para gestionar y facilitar reuniones B2B entre empresas grandes (demandantes) y PYMEs (oferentes) durante eventos de networking empresarial.

### Características Principales

- ✅ **Gestión de Empresas**: Registro de empresas grandes y PYMEs
- ✅ **Sistema de Mesas**: 15 mesas físicas para reuniones
- ✅ **Bloques Horarios**: Gestión de horarios por bloques
- ✅ **Disponibilidad**: Las empresas grandes declaran su disponibilidad por mesa y horario
- ✅ **Solicitud de Reuniones**: Las PYMEs solicitan reuniones con empresas grandes
- ✅ **Visor en Tiempo Real**: Visualización del estado de las mesas
- ✅ **Panel de Administración**: Control total del sistema
- ✅ **Estados de Reuniones**: Pendiente, Confirmada, Rechazada, Cancelada

---

## 🏗️ Arquitectura del Sistema

### Stack Tecnológico

```
Frontend:
├── HTML5
├── TailwindCSS (vía CDN)
├── JavaScript (Vanilla)
└── Font Awesome 6.4.0

Backend:
├── PHP 7.4+
├── MySQL/MariaDB
└── PDO (PHP Data Objects)

Fuentes:
└── Google Fonts (Inter)
```

### Estructura de Directorios

```
ruedav3/
├── api/                          # Endpoints de la API
│   ├── admin_reuniones.php       # Gestión administrativa de reuniones
│   ├── get_mesas_visor.php       # API para el visor de mesas
│   ├── get_bloques_visor.php     # API para bloques horarios
│   ├── solicitar_reunion.php     # Solicitud de reuniones (PYMEs)
│   └── bloques.php               # Gestión de bloques
├── config/                       # Configuración
│   ├── config.php                # Constantes del sistema
│   ├── database.php              # Conexión PDO a MySQL
│   └── session.php               # Manejo de sesiones
├── includes/                     # Funciones auxiliares
│   └── functions.php             # Funciones reutilizables
├── uploads/                      # Archivos subidos
│   └── logos/                    # Logos de empresas
├── views/                        # Vistas del sistema
│   ├── visor-rueda.php           # Visor principal de mesas
│   ├── panel-admin.php           # Panel de administración
│   ├── panel-empresa-a.php       # Panel empresas grandes
│   └── panel-empresa-b.php       # Panel PYMEs
└── DOCUMENTACION_SISTEMA.md      # Este archivo
```

---

## 🗄️ Estructura de la Base de Datos

### Tabla: `empresas`

Almacena todas las empresas (grandes y PYMEs).

```sql
CREATE TABLE empresas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    rut VARCHAR(12) UNIQUE NOT NULL,
    tipo ENUM('grande', 'pyme') NOT NULL,
    rubro VARCHAR(255),
    descripcion TEXT,
    logo VARCHAR(255),
    email VARCHAR(255),
    telefono VARCHAR(20),
    contacto_nombre VARCHAR(255),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Campos importantes:**
- `tipo`: `'grande'` para demandantes, `'pyme'` para oferentes
- `activo`: `1` = activa, `0` = inactiva
- `logo`: Ruta relativa al logo (ej: `logos/empresa123.png`)

---

### Tabla: `bloques_horarios_globales`

Define los bloques horarios del evento.

```sql
CREATE TABLE bloques_horarios_globales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    orden INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Ejemplo de datos:**
```sql
INSERT INTO bloques_horarios_globales (hora_inicio, hora_fin, orden) VALUES
('09:00:00', '09:15:00', 1),
('09:15:00', '09:30:00', 2),
('09:30:00', '09:45:00', 3),
('09:45:00', '10:00:00', 4);
```

---

### Tabla: `disponibilidad_empresas`

Gestiona la disponibilidad de las **empresas grandes** por mesa y bloque horario.

```sql
CREATE TABLE disponibilidad_empresas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    bloque_id INT NOT NULL,
    mesa_numero INT NOT NULL,           -- Número de mesa (1-15)
    disponible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    FOREIGN KEY (bloque_id) REFERENCES bloques_horarios_globales(id),
    UNIQUE KEY unique_mesa_bloque (mesa_numero, bloque_id)  -- IMPORTANTE: Solo una empresa por mesa/bloque
);
```

**⚠️ CONSTRAINT CRÍTICO:**

El `UNIQUE KEY unique_mesa_bloque (mesa_numero, bloque_id)` garantiza que:
- Una mesa solo puede ser asignada a UNA empresa en un bloque horario específico
- Previene conflictos de doble asignación
- Es la clave para evitar el error "Duplicate entry '1-4' for key 'unique_mesa_bloque'"

**Ejemplo:**
```sql
-- Correcto: Mesa 1, Bloque 1, Empresa A
INSERT INTO disponibilidad_empresas (empresa_id, bloque_id, mesa_numero, disponible)
VALUES (5, 1, 1, 1);

-- ERROR: Mesa 1, Bloque 1 ya está ocupada por Empresa A
INSERT INTO disponibilidad_empresas (empresa_id, bloque_id, mesa_numero, disponible)
VALUES (8, 1, 1, 1);  -- ❌ VIOLACIÓN DEL CONSTRAINT

-- Correcto: Mesa 2, Bloque 1, Empresa B (mesa diferente)
INSERT INTO disponibilidad_empresas (empresa_id, bloque_id, mesa_numero, disponible)
VALUES (8, 1, 2, 1);  -- ✅ OK
```

---

### Tabla: `reuniones`

Almacena todas las reuniones programadas.

```sql
CREATE TABLE reuniones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_a_id INT NOT NULL,              -- Empresa grande (demandante)
    empresa_b_id INT NOT NULL,              -- PYME (oferente)
    bloque_global_id INT NOT NULL,          -- Bloque horario
    mesa_asignada INT NOT NULL,             -- Mesa física (1-15)
    estado ENUM('pendiente', 'confirmada', 'rechazada', 'cancelada') DEFAULT 'pendiente',
    notas TEXT,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_a_id) REFERENCES empresas(id),
    FOREIGN KEY (empresa_b_id) REFERENCES empresas(id),
    FOREIGN KEY (bloque_global_id) REFERENCES bloques_horarios_globales(id)
);
```

**Estados de Reunión:**

| Estado | Descripción | Quien lo asigna |
|--------|-------------|-----------------|
| `pendiente` | Reunión solicitada, esperando confirmación | Sistema (al crear) |
| `confirmada` | Reunión aprobada y confirmada | Administrador o Empresa Grande |
| `rechazada` | Reunión rechazada | Administrador o Empresa Grande |
| `cancelada` | Reunión cancelada después de confirmada | Administrador |

**Notas importantes:**
- `mesa_asignada`: Se obtiene automáticamente de `disponibilidad_empresas`
- Una reunión confirmada "reserva" el slot, impidiendo otras reuniones en la misma mesa/bloque
- El campo `notas` puede incluir observaciones del admin (ej: `[CREADA POR ADMIN]\n...`)

---

### Tabla: `usuarios`

Gestiona el acceso al sistema.

```sql
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empresa_a', 'empresa_b') NOT NULL,
    empresa_id INT,                        -- NULL para admin, ID para empresas
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);
```

**Roles del sistema:**
- `admin`: Acceso total, puede crear/modificar/eliminar reuniones
- `empresa_a`: Empresas grandes, pueden ver solicitudes y confirmar/rechazar
- `empresa_b`: PYMEs, pueden solicitar reuniones

---

## 🎯 Componentes Principales

### 1. Visor de Mesas (`views/visor-rueda.php`)

**Propósito:** Visualizar en tiempo real el estado de las 15 mesas.

**Características:**

```php
<?php
/**
 * VISOR DE MESAS - VISTA PRINCIPAL
 *
 * Eje Y: Empresas Grandes (Demandantes)
 * Eje X: 15 Mesas
 *
 * Estados visuales:
 * - Verde pastel: Disponible
 * - Azul pastel: Reunión confirmada
 * - Amarillo pastel: Reunión pendiente
 * - Gris muy claro: No disponible
 */
?>
```

**Diseño Visual Moderno:**

- **Fuente:** Inter de Google Fonts
- **Colores suaves y profesionales:**
  - Disponible: `#ecfdf5` (fondo), `#047857` (texto), borde `#d1fae5`
  - Confirmada: Gradiente `#eff6ff` → `#dbeafe`, texto `#1e40af`
  - Pendiente: Gradiente `#fffbeb` → `#fef3c7`, texto `#92400e`
  - No disponible: `#fafafa`, texto `#e5e7eb`

- **Efectos hover:** `translateY(-2px)` con sombras suaves
- **Border radius:** `6px` en todas las celdas
- **Sticky headers:** Nombres de empresas y números de mesa permanecen visibles al hacer scroll

**Optimizaciones:**

1. **Filtrado de empresas:** Solo muestra empresas con actividad (disponibilidad o reuniones)
2. **Actualización automática:** Cada 30 segundos sin recargar la página
3. **Responsive:** Se adapta a diferentes tamaños de pantalla
4. **Modo fullscreen:** Parámetro `?fullscreen=1` para pantallas grandes

**API utilizada:**
```javascript
GET /api/get_mesas_visor.php
GET /api/get_mesas_visor.php?bloque_id=4  // Para un bloque específico
```

**Ejemplo de uso:**
```
http://localhost/views/visor-rueda.php
http://localhost/views/visor-rueda.php?fullscreen=1
```

---

### 2. Panel de Administración (`views/panel-admin.php`)

**Propósito:** Gestión completa del sistema por parte del administrador.

**Pestañas principales:**

#### 📊 Pestaña 1: Dashboard General
- Estadísticas en tiempo real
- Gráficos de ocupación
- Resumen de reuniones por estado

#### ⚙️ Pestaña 2: Gestión de Disponibilidad
- Vista de matriz Mesa x Bloque
- Asignar/liberar slots manualmente
- Ver qué empresa está en cada mesa

#### 📅 Pestaña 3: Crear Reunión (Agendador Directo)

**Funcionalidad principal:** Crear reuniones sin restricciones.

```html
<!-- Formulario de creación -->
<form id="form-crear-reunion">
    <select name="empresa_a_id"><!-- Empresas grandes --></select>
    <select name="mesa_numero"><!-- Mesa 1-15 --></select>
    <select name="bloque_id"><!-- Bloques horarios --></select>
    <select name="empresa_b_id"><!-- PYMEs --></select>
    <select name="estado">
        <option value="pendiente">Pendiente</option>
        <option value="confirmada">Confirmada</option>
    </select>
    <textarea name="notas"></textarea>
    <button type="submit">Crear Reunión</button>
</form>
```

**Validaciones automáticas:**

1. ✅ Verifica que la empresa grande exista y esté activa
2. ✅ Verifica que la PYME exista y esté activa
3. ✅ Verifica que el bloque horario exista
4. ✅ **Verifica que no haya otra reunión (pendiente o confirmada) en esa mesa/bloque**
5. ✅ **Verifica que la mesa no esté asignada a otra empresa en ese bloque**
6. ✅ Si no existe disponibilidad, la crea automáticamente

**Lista de reuniones agendadas:**

Muestra las últimas 10 reuniones creadas con:
- Empresa demandante
- PYME oferente
- Horario y mesa
- Estado
- Botones de acción (Ver detalle, Eliminar)

```javascript
// Función para eliminar reunión
async function eliminarReunion(reunionId) {
    if (!confirm('¿Estás seguro de eliminar esta reunión?')) return;

    const formData = new FormData();
    formData.append('accion', 'eliminar_reunion');
    formData.append('reunion_id', reunionId);

    const response = await fetch('/api/admin_reuniones.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();
    if (data.success) {
        alert('Reunión eliminada exitosamente');
        recargarReunionesAgendadas();
    }
}
```

---

### 3. API Principal de Reuniones (`api/admin_reuniones.php`)

**Endpoint:** `/api/admin_reuniones.php`

**Métodos:** `GET`, `POST`

#### Acciones disponibles (POST):

##### 1. `crear_reunion_admin`

Crea una reunión manualmente desde el panel de administración.

**Parámetros:**
```javascript
{
    accion: 'crear_reunion_admin',
    empresa_a_id: 5,        // ID empresa grande
    empresa_b_id: 12,       // ID PYME
    bloque_id: 4,           // ID bloque horario
    mesa_numero: 14,        // Número de mesa (1-15)
    estado: 'confirmada',   // 'pendiente' o 'confirmada'
    notas: 'Observaciones...'
}
```

**Proceso interno:**

```php
function crearReunionAdmin($datos, $pdo) {
    $pdo->beginTransaction();

    // 1. Validar empresa demandante (tipo 'grande')
    $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ? AND tipo = 'grande' AND activo = 1");

    // 2. Validar PYME (tipo 'pyme')
    $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ? AND tipo = 'pyme' AND activo = 1");

    // 3. Validar bloque horario
    $stmt = $pdo->prepare("SELECT * FROM bloques_horarios_globales WHERE id = ?");

    // 4. IMPORTANTE: Verificar que no haya reunión en ese slot
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM reuniones
        WHERE bloque_global_id = ? AND mesa_asignada = ?
        AND estado IN ('pendiente', 'confirmada')
    ");
    if ($stmt->fetchColumn() > 0) {
        jsonResponse(false, 'Ya existe una reunión en este horario/mesa');
    }

    // 5. Verificar disponibilidad de la empresa en esa mesa/bloque
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM disponibilidad_empresas
        WHERE empresa_id = ? AND bloque_id = ? AND mesa_numero = ?
    ");

    // 6. Si no existe disponibilidad, crearla automáticamente
    if ($stmt->fetchColumn() == 0) {
        // Verificar que la mesa no esté ocupada por otra empresa
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM disponibilidad_empresas
            WHERE mesa_numero = ? AND bloque_id = ?
        ");
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(false, 'La mesa ya está asignada a otra empresa en este bloque');
        }

        // Crear disponibilidad
        $stmt = $pdo->prepare("
            INSERT INTO disponibilidad_empresas
            (empresa_id, bloque_id, mesa_numero, disponible)
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute([$empresaAId, $bloqueId, $mesaNumero]);
    }

    // 7. Crear la reunión
    $stmt = $pdo->prepare("
        INSERT INTO reuniones
        (empresa_a_id, empresa_b_id, bloque_global_id, mesa_asignada, estado, notas, fecha_solicitud)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$empresaAId, $empresaBId, $bloqueId, $mesaNumero, $estado, $notasCompletas]);

    $pdo->commit();
    jsonResponse(true, 'Reunión creada exitosamente');
}
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "mensaje": "Reunión creada exitosamente"
}
```

**Respuesta de error:**
```json
{
    "success": false,
    "mensaje": "Ya existe una reunión en este horario/mesa"
}
```

---

##### 2. `asignar_slot`

Asigna una mesa/bloque a una empresa grande.

**Parámetros:**
```javascript
{
    accion: 'asignar_slot',
    empresa_id: 5,
    bloque_id: 4,
    mesa_numero: 14
}
```

**Validaciones:**
```php
// 1. Verificar que sea empresa grande activa
$stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ? AND tipo = 'grande' AND activo = 1");

// 2. Verificar que la mesa/bloque no esté ocupada
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM disponibilidad_empresas
    WHERE mesa_numero = ? AND bloque_id = ?
");
if ($stmt->fetchColumn() > 0) {
    jsonResponse(false, 'Esta mesa ya está asignada a otra empresa en este bloque');
}

// 3. Insertar disponibilidad
$stmt = $pdo->prepare("
    INSERT INTO disponibilidad_empresas (empresa_id, bloque_id, mesa_numero, disponible)
    VALUES (?, ?, ?, 1)
");
```

---

##### 3. `liberar_slot`

Libera una mesa/bloque (elimina disponibilidad y reuniones pendientes).

**Parámetros:**
```javascript
{
    accion: 'liberar_slot',
    empresa_id: 5,
    bloque_id: 4,
    mesa_numero: 14
}
```

**Proceso:**
```php
$pdo->beginTransaction();

// 1. Eliminar reuniones pendientes en ese slot
$stmt = $pdo->prepare("
    DELETE FROM reuniones
    WHERE empresa_a_id = ? AND bloque_global_id = ? AND mesa_asignada = ?
    AND estado = 'pendiente'
");

// 2. Eliminar disponibilidad
$stmt = $pdo->prepare("
    DELETE FROM disponibilidad_empresas
    WHERE empresa_id = ? AND bloque_id = ? AND mesa_numero = ?
");

$pdo->commit();
```

---

##### 4. `eliminar_reunion`

Elimina completamente una reunión.

**Parámetros:**
```javascript
{
    accion: 'eliminar_reunion',
    reunion_id: 123
}
```

**Proceso:**
```php
$pdo->beginTransaction();

// Verificar que exista
$stmt = $pdo->prepare("SELECT * FROM reuniones WHERE id = ?");

// Eliminar
$stmt = $pdo->prepare("DELETE FROM reuniones WHERE id = ?");

$pdo->commit();
```

---

##### 5. `reprogramar`

Mueve una reunión a otro horario.

**Parámetros:**
```javascript
{
    accion: 'reprogramar',
    reunion_id: 123,
    nuevo_bloque_id: 5
}
```

---

### 4. API del Visor (`api/get_mesas_visor.php`)

**Endpoint:** `/api/get_mesas_visor.php`

**Método:** `GET`

**Parámetros opcionales:**
- `bloque_id`: ID del bloque horario (si no se especifica, usa el actual o el primero)

**Respuesta:**

```json
{
    "success": true,
    "bloque_actual": {
        "id": 4,
        "hora_inicio": "12:00:00",
        "hora_fin": "12:15:00",
        "orden": 4
    },
    "bloques": [
        {
            "id": 1,
            "hora_inicio": "09:00:00",
            "hora_fin": "09:15:00",
            "orden": 1
        },
        // ... más bloques
    ],
    "empresas": [
        {
            "id": 5,
            "nombre": "IMPALA Terminals Logistics Chile SPA - Minería",
            "rubro": "Logística y Transporte",
            "logo": "logos/impala.png"
        },
        // ... más empresas
    ],
    "matriz": {
        "5": {  // ID empresa
            "1": {  // Mesa 1
                "tiene_disponibilidad": true,
                "reunion": {
                    "id": 45,
                    "pyme_id": 12,
                    "pyme_nombre": "Vangperez Logística",
                    "pyme_logo": "logos/vangperez.png",
                    "pyme_rubro": "Transporte",
                    "estado": "confirmada",
                    "notas": "[CREADA POR ADMIN]..."
                },
                "estado": "confirmada"
            },
            "2": {  // Mesa 2
                "tiene_disponibilidad": true,
                "reunion": null,
                "estado": "disponible"
            },
            "3": {  // Mesa 3
                "tiene_disponibilidad": false,
                "reunion": null,
                "estado": "no_disponible"
            }
            // ... mesas 4-15
        }
        // ... más empresas
    },
    "estadisticas": {
        "total_empresas": 8,
        "total_mesas": 15,
        "slots_disponibles": 45,
        "slots_ocupados": 12,
        "porcentaje_ocupacion": 21
    }
}
```

**Estados posibles en la matriz:**

| Estado | Condición |
|--------|-----------|
| `disponible` | `tiene_disponibilidad = true` y `reunion = null` |
| `confirmada` | `reunion != null` y `reunion.estado = 'confirmada'` |
| `pendiente` | `reunion != null` y `reunion.estado = 'pendiente'` |
| `no_disponible` | `tiene_disponibilidad = false` |

**Lógica de construcción de la matriz:**

```php
// Inicializar matriz vacía
for ($mesa = 1; $mesa <= 15; $mesa++) {
    $matriz[$empresaId][$mesa] = [
        'tiene_disponibilidad' => false,
        'reunion' => null,
        'estado' => 'no_disponible'
    ];
}

// Cargar disponibilidades
$stmt = $pdo->prepare("
    SELECT mesa_numero, disponible
    FROM disponibilidad_empresas
    WHERE empresa_id = ? AND bloque_id = ?
");

foreach ($disponibilidades as $disp) {
    $mesa = $disp['mesa_numero'];
    $matriz[$empresaId][$mesa]['tiene_disponibilidad'] = true;
    $matriz[$empresaId][$mesa]['estado'] = 'disponible';
}

// Cargar reuniones
$stmt = $pdo->prepare("
    SELECT r.*, eb.nombre as pyme_nombre, eb.logo as pyme_logo, eb.rubro as pyme_rubro
    FROM reuniones r
    INNER JOIN empresas eb ON r.empresa_b_id = eb.id
    WHERE r.empresa_a_id = ? AND r.bloque_global_id = ?
    AND r.estado IN ('pendiente', 'confirmada')
");

foreach ($reuniones as $reunion) {
    $mesa = $reunion['mesa_asignada'];
    $matriz[$empresaId][$mesa]['reunion'] = [
        'id' => $reunion['id'],
        'pyme_id' => $reunion['empresa_b_id'],
        'pyme_nombre' => $reunion['pyme_nombre'],
        'pyme_logo' => $reunion['pyme_logo'],
        'pyme_rubro' => $reunion['pyme_rubro'],
        'estado' => $reunion['estado'],
        'notas' => $reunion['notas']
    ];
    $matriz[$empresaId][$mesa]['estado'] = $reunion['estado'];
}
```

---

## 🐛 Problemas Resueltos

### Problema 1: Error "Duplicate entry '1-4' for key 'unique_mesa_bloque'"

**Síntoma:**
```
SQLSTATE[23000]: Integrity constraint violation: 1062
Duplicate entry '1-4' for key 'unique_mesa_bloque'
```

**Causa raíz:**

La tabla `disponibilidad_empresas` tiene un constraint `UNIQUE (mesa_numero, bloque_id)` que impide que dos empresas tengan la misma mesa en el mismo bloque.

El código original tenía dos problemas:

1. **No incluía `mesa_numero` en los INSERT:**
```php
// ❌ CÓDIGO INCORRECTO
INSERT INTO disponibilidad_empresas (empresa_id, bloque_id, disponible)
VALUES (?, ?, 1)
```

2. **Solo validaba reuniones confirmadas, no pendientes:**
```php
// ❌ CÓDIGO INCORRECTO
SELECT COUNT(*) FROM reuniones
WHERE bloque_global_id = ? AND mesa_asignada = ?
AND estado = 'confirmada'  // Solo confirmadas
```

**Solución implementada:**

```php
// ✅ CÓDIGO CORRECTO

// 1. Incluir mesa_numero en INSERT
INSERT INTO disponibilidad_empresas (empresa_id, bloque_id, mesa_numero, disponible)
VALUES (?, ?, ?, 1)

// 2. Validar TODAS las reuniones activas (pendientes + confirmadas)
SELECT COUNT(*) FROM reuniones
WHERE bloque_global_id = ? AND mesa_asignada = ?
AND estado IN ('pendiente', 'confirmada')  // Ambos estados

// 3. Validar que la mesa no esté ocupada por otra empresa
SELECT COUNT(*) FROM disponibilidad_empresas
WHERE mesa_numero = ? AND bloque_id = ?
```

**Archivos modificados:**
- `api/admin_reuniones.php` (líneas 243-258, 324-350, 198-203, 435-445)

**Commit:**
```
c530377 Corregir inserción en disponibilidad_empresas incluyendo mesa_numero
```

---

### Problema 2: Reuniones no aparecían en "Crear Reunión"

**Síntoma:**

La pestaña "Crear Reunión" en el panel admin no mostraba las reuniones agendadas.

**Causa:**

Faltaba la sección HTML y JavaScript para cargar y mostrar las reuniones recientes.

**Solución:**

Se agregó:

1. **Sección HTML en `panel-admin.php`:**

```html
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-bold mb-4">📋 Reuniones Agendadas Recientemente</h3>
    <div id="lista-reuniones-agendadas">
        <!-- Cargado dinámicamente -->
    </div>
    <button onclick="recargarReunionesAgendadas()" class="btn-secondary">
        <i class="fas fa-sync-alt"></i> Actualizar
    </button>
</div>
```

2. **Query PHP para obtener reuniones:**

```php
$stmtRecientes = $pdo->query("
    SELECT r.*,
           ea.nombre as empresa_a_nombre,
           eb.nombre as empresa_b_nombre,
           bg.hora_inicio,
           bg.hora_fin,
           bg.orden
    FROM reuniones r
    INNER JOIN empresas ea ON r.empresa_a_id = ea.id
    INNER JOIN empresas eb ON r.empresa_b_id = eb.id
    INNER JOIN bloques_horarios_globales bg ON r.bloque_global_id = bg.id
    ORDER BY r.fecha_solicitud DESC
    LIMIT 10
");
```

3. **Función JavaScript para recargar:**

```javascript
async function recargarReunionesAgendadas() {
    const response = await fetch('/api/admin_reuniones.php?accion=listar_recientes');
    const data = await response.json();

    const container = document.getElementById('lista-reuniones-agendadas');
    container.innerHTML = data.reuniones.map(r => `
        <div class="reunion-card">
            <div class="font-bold">${r.empresa_a_nombre}</div>
            <div class="text-sm">↔ ${r.empresa_b_nombre}</div>
            <div class="text-xs text-gray-600">
                Mesa ${r.mesa_asignada} | ${r.hora_inicio} - ${r.hora_fin}
            </div>
            <span class="badge badge-${r.estado}">${r.estado}</span>
            <button onclick="eliminarReunion(${r.id})">Eliminar</button>
        </div>
    `).join('');
}
```

---

### Problema 3: Visor mostraba filas vacías

**Síntoma:**

El visor mostraba empresas sin ninguna disponibilidad ni reunión, creando muchas filas con solo guiones (—).

**Solución:**

Filtrar empresas antes de renderizar:

```javascript
// Filtrar solo empresas con actividad
const empresasActivas = data.empresas.filter(empresa => {
    const filaEmpresa = data.matriz[empresa.id];
    for (let mesa = 1; mesa <= 15; mesa++) {
        const celda = filaEmpresa[mesa];
        if (celda.estado === 'disponible' ||
            celda.estado === 'confirmada' ||
            celda.estado === 'pendiente') {
            return true;
        }
    }
    return false;
});

// Renderizar solo empresas activas
empresasActivas.forEach(empresa => {
    // ... generar fila
});
```

**Resultado:**
- Visor más limpio
- Menos scroll necesario
- Foco en información relevante

---

### Problema 4: Diseño visual "horrible"

**Síntoma:**

El usuario reportó que "el estilo visual es horrible" (múltiples veces).

**Solución:**

Rediseño completo con:

1. **Fuente moderna:**
```css
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
font-family: 'Inter', sans-serif;
```

2. **Colores pastel profesionales:**
```css
/* Antes: Colores brillantes */
.celda-disponible { background: linear-gradient(135deg, #10b981, #059669); }

/* Después: Colores suaves */
.celda-disponible {
    background: #ecfdf5;
    color: #047857;
    border: 2px solid #d1fae5;
    border-radius: 6px;
}
```

3. **Efectos hover suaves:**
```css
/* Antes: Scale agresivo */
.celda:hover { transform: scale(1.08); }

/* Después: TranslateY sutil */
.celda:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(16, 185, 129, 0.15);
}
```

4. **Spacing y bordes:**
- Border-radius: 6px en todas las celdas
- Padding ajustado: 14px en celdas de empresa
- Borders de 2px en colores coordinados
- Box-shadows sutiles

**Commits:**
```
6d61779 Rediseñar estilo visual del visor con diseño moderno
76830fc Mostrar solo empresas con actividad en el visor
```

---

## 📖 Guía de Uso

### Para Administradores

#### 1. Acceder al Panel de Administración

```
URL: http://localhost/views/panel-admin.php
Usuario: admin
Contraseña: [configurada en BD]
```

#### 2. Configurar Bloques Horarios

1. Ir a **Configuración → Bloques Horarios**
2. Agregar bloques:
   - Hora inicio: `09:00`
   - Hora fin: `09:15`
   - Orden: `1`
3. Repetir para todos los bloques del evento

#### 3. Registrar Empresas

1. Ir a **Empresas → Nueva Empresa**
2. Completar datos:
   - Nombre
   - RUT
   - Tipo: `Grande` o `PYME`
   - Rubro
   - Logo (opcional)
3. Guardar

#### 4. Asignar Disponibilidad a Empresas Grandes

1. Ir a **Gestión de Disponibilidad**
2. Seleccionar empresa grande
3. Seleccionar bloque horario
4. Seleccionar mesa (1-15)
5. Clic en **Asignar Slot**

⚠️ **Importante:** Una mesa solo puede ser asignada a UNA empresa por bloque.

#### 5. Crear Reuniones Manualmente

1. Ir a **Crear Reunión**
2. Seleccionar:
   - Empresa Demandante (grande)
   - Mesa
   - Bloque Horario
   - Empresa Oferente (PYME)
   - Estado: Pendiente o Confirmada
3. Agregar notas (opcional)
4. Clic en **Crear Reunión**

**Validaciones automáticas:**
- ✅ La empresa debe tener disponibilidad en esa mesa/bloque
- ✅ No puede haber otra reunión en ese slot
- ✅ La mesa no puede estar asignada a otra empresa

#### 6. Ver Reuniones Agendadas

En la pestaña **Crear Reunión**, al final aparece la lista de las últimas 10 reuniones con:
- Empresa demandante y oferente
- Mesa y horario
- Estado actual
- Botón para eliminar

#### 7. Usar el Visor

```
URL: http://localhost/views/visor-rueda.php
```

Características:
- Actualización automática cada 30 segundos
- Selector de bloque horario
- Estadísticas en tiempo real
- Clic en cualquier celda para ver detalle
- Modo fullscreen: agregar `?fullscreen=1`

#### 8. Exportar Datos

En el visor, clic en **Exportar** para descargar CSV con:
- Empresa
- Mesa
- Estado
- PYME (si aplica)
- Horario

---

### Para Empresas Grandes

#### 1. Acceder al Panel

```
URL: http://localhost/views/panel-empresa-a.php
Usuario: [rut de la empresa]
Contraseña: [configurada]
```

#### 2. Declarar Disponibilidad

1. En **Mi Disponibilidad**
2. Seleccionar bloques horarios disponibles
3. El sistema asigna automáticamente una mesa
4. Guardar

#### 3. Ver Solicitudes de Reunión

1. Ir a **Solicitudes Pendientes**
2. Ver lista de PYMEs que solicitaron reunión
3. Opciones:
   - **Confirmar:** Acepta la reunión
   - **Rechazar:** Rechaza la solicitud
   - **Ver perfil:** Ver detalles de la PYME

#### 4. Ver Agenda

En **Mi Agenda** ver todas las reuniones confirmadas con:
- Horario
- Mesa asignada
- PYME con quien se reunirá
- Opción de cancelar (si es necesario)

---

### Para PYMEs

#### 1. Acceder al Panel

```
URL: http://localhost/views/panel-empresa-b.php
Usuario: [rut de la empresa]
Contraseña: [configurada]
```

#### 2. Ver Empresas Disponibles

El sistema muestra automáticamente las empresas grandes que tienen disponibilidad.

#### 3. Solicitar Reunión

1. Buscar empresa de interés
2. Ver bloques horarios disponibles
3. Clic en **Solicitar Reunión**
4. Agregar mensaje (opcional)
5. Enviar solicitud

**Estado:** La reunión quedará como `pendiente` hasta que la empresa grande la confirme.

#### 4. Ver Mis Reuniones

En **Mis Reuniones** ver:
- **Pendientes:** Esperando confirmación
- **Confirmadas:** Reuniones aprobadas con horario y mesa
- **Rechazadas:** Solicitudes no aceptadas

---

## 🔧 Mantenimiento y Troubleshooting

### Logs y Debugging

#### Activar logs de PHP

```php
// En config/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
```

#### Ver errores en la consola del navegador

```javascript
// En el visor o panel admin
console.log('Datos recibidos:', data);
console.error('Error:', error);
```

#### Verificar queries SQL

```php
// En api/admin_reuniones.php
try {
    $stmt->execute($params);
} catch (PDOException $e) {
    error_log("SQL Error: " . $e->getMessage());
    error_log("Query: " . $stmt->queryString);
    error_log("Params: " . print_r($params, true));
}
```

---

### Problemas Comunes

#### Error: "No se puede conectar a la base de datos"

**Causa:** Credenciales incorrectas o servidor MySQL no iniciado.

**Solución:**
```bash
# Verificar que MySQL esté corriendo
sudo systemctl status mysql

# Iniciar MySQL
sudo systemctl start mysql

# Verificar credenciales en config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'rueda_negocios_arica');
define('DB_USER', 'seidev');
define('DB_PASS', 'Tz9!qE7#Xr2@Lm5$Vp8^');
```

---

#### Error: "Duplicate entry '1-4' for key 'unique_mesa_bloque'"

**Causa:** Intentando asignar una mesa que ya está ocupada.

**Solución:**

1. Verificar que el código incluya `mesa_numero` en INSERT:
```php
INSERT INTO disponibilidad_empresas (empresa_id, bloque_id, mesa_numero, disponible)
VALUES (?, ?, ?, 1)
```

2. Verificar que se valide antes de insertar:
```php
SELECT COUNT(*) FROM disponibilidad_empresas
WHERE mesa_numero = ? AND bloque_id = ?
```

3. Si persiste, revisar datos duplicados:
```sql
SELECT mesa_numero, bloque_id, COUNT(*) as total
FROM disponibilidad_empresas
GROUP BY mesa_numero, bloque_id
HAVING total > 1;
```

---

#### Error: "La mesa ya está asignada a otra empresa"

**Causa:** Normal. Otra empresa ya tiene esa mesa en ese bloque.

**Solución:** Elegir otra mesa disponible.

Para ver mesas disponibles:
```sql
SELECT mesa_numero
FROM (SELECT 1 as mesa_numero UNION SELECT 2 UNION ... UNION SELECT 15) as mesas
WHERE mesa_numero NOT IN (
    SELECT mesa_numero
    FROM disponibilidad_empresas
    WHERE bloque_id = 4
);
```

---

#### El visor no actualiza datos

**Causa:** Error en JavaScript o problema con la API.

**Diagnóstico:**

1. Abrir consola del navegador (F12)
2. Revisar errores en la pestaña Console
3. Ir a Network y ver si `get_mesas_visor.php` responde correctamente
4. Verificar que la respuesta sea JSON válido

**Solución:**

```javascript
// Agregar try-catch en cargarDatos()
async function cargarDatos(bloqueId = null) {
    try {
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        console.log('Datos cargados:', data);

        if (data.success) {
            actualizarInterfaz(data);
        } else {
            console.error('API error:', data.mensaje);
        }
    } catch (error) {
        console.error('Error completo:', error);
        alert('Error al cargar datos: ' + error.message);
    }
}
```

---

#### Las reuniones no aparecen en la lista

**Causa:** Query SQL incorrecto o falta JOIN.

**Solución:**

Verificar query en `panel-admin.php`:
```sql
SELECT r.*,
       ea.nombre as empresa_a_nombre,
       eb.nombre as empresa_b_nombre,
       bg.hora_inicio,
       bg.hora_fin
FROM reuniones r
INNER JOIN empresas ea ON r.empresa_a_id = ea.id
INNER JOIN empresas eb ON r.empresa_b_id = eb.id
INNER JOIN bloques_horarios_globales bg ON r.bloque_global_id = bg.id
WHERE r.estado IN ('pendiente', 'confirmada')
ORDER BY r.fecha_solicitud DESC
LIMIT 10;
```

---

### Mantenimiento de Base de Datos

#### Limpiar reuniones antiguas

```sql
-- Eliminar reuniones rechazadas/canceladas mayores a 30 días
DELETE FROM reuniones
WHERE estado IN ('rechazada', 'cancelada')
AND fecha_solicitud < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

#### Resetear disponibilidades

```sql
-- CUIDADO: Esto borra TODAS las disponibilidades
TRUNCATE TABLE disponibilidad_empresas;
```

#### Backup de base de datos

```bash
# Crear backup
mysqldump -u seidev -p rueda_negocios_arica > backup_$(date +%Y%m%d).sql

# Restaurar backup
mysql -u seidev -p rueda_negocios_arica < backup_20241129.sql
```

---

### Optimización de Rendimiento

#### Índices importantes

```sql
-- Asegurar índices en tablas principales
ALTER TABLE reuniones ADD INDEX idx_empresa_a_bloque (empresa_a_id, bloque_global_id);
ALTER TABLE reuniones ADD INDEX idx_mesa_bloque (mesa_asignada, bloque_global_id);
ALTER TABLE disponibilidad_empresas ADD INDEX idx_empresa_bloque (empresa_id, bloque_id);
```

#### Caché en el visor

```javascript
// Implementar caché simple
let ultimaActualizacion = null;
const CACHE_DURACION = 5000; // 5 segundos

async function cargarDatos(bloqueId = null) {
    const ahora = Date.now();

    if (ultimaActualizacion && (ahora - ultimaActualizacion) < CACHE_DURACION) {
        console.log('Usando caché');
        return;
    }

    // Cargar datos frescos
    const data = await fetch(url).then(r => r.json());
    ultimaActualizacion = ahora;
    actualizarInterfaz(data);
}
```

---

## 📝 Notas Finales

### Seguridad

#### Validación de entrada

Todas las APIs usan `sanitize()` para prevenir XSS:

```php
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

$empresaId = intval($_POST['empresa_id'] ?? 0);
$notas = sanitize($_POST['notas'] ?? '');
```

#### Prepared Statements

Todas las queries usan PDO prepared statements para prevenir SQL injection:

```php
// ✅ CORRECTO
$stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->execute([$id]);

// ❌ NUNCA HACER ESTO
$query = "SELECT * FROM empresas WHERE id = " . $_GET['id'];
```

#### Control de acceso

```php
// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// Verificar rol
if (getUserRole() !== ROL_ADMIN) {
    jsonResponse(false, 'No autorizado');
}
```

---

### Mejoras Futuras

#### Posibles extensiones:

1. **Notificaciones en tiempo real**
   - WebSockets para actualizar visor sin polling
   - Notificaciones push a empresas cuando se confirma una reunión

2. **Exportación avanzada**
   - PDF con agenda del día
   - Excel con todas las reuniones
   - Códigos QR para cada reunión

3. **Analytics**
   - Dashboard con KPIs
   - Gráficos de ocupación por bloque
   - Empresas más solicitadas

4. **Mobile App**
   - App nativa para empresas
   - Escaneo de QR en las mesas
   - Notificaciones push

5. **Integración con calendarios**
   - Exportar a Google Calendar
   - Sincronización con Outlook
   - iCal feeds

---

### Contacto y Soporte

Para consultas o reportar problemas:

- **Documentación:** Este archivo
- **Issues:** Crear issue en el repositorio
- **Email:** [email del administrador]

---

## ✅ Checklist de Implementación

### Antes del evento:

- [ ] Base de datos configurada y migrada
- [ ] Empresas grandes registradas
- [ ] PYMEs registradas
- [ ] Bloques horarios creados
- [ ] Usuarios de acceso creados
- [ ] Logos de empresas subidos
- [ ] Disponibilidad de empresas grandes configurada
- [ ] Visor testeado en pantalla grande
- [ ] Panel admin testeado
- [ ] Backup de base de datos creado

### Durante el evento:

- [ ] Visor proyectado en pantalla visible
- [ ] Monitor con panel admin abierto
- [ ] Conexión a internet estable
- [ ] Personal capacitado en panel admin
- [ ] Soporte técnico disponible

### Después del evento:

- [ ] Exportar todas las reuniones
- [ ] Backup final de base de datos
- [ ] Análisis de métricas
- [ ] Feedback de participantes
- [ ] Archivado de datos

---

**Última actualización:** 2024-11-29
**Versión del sistema:** 3.0
**Autor:** Sistema de Rueda de Negocios Arica

---

## 🎉 ¡Éxito en tu Rueda de Negocios!
