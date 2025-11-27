# 📋 SISTEMA RUEDA DE NEGOCIOS - DOCUMENTACIÓN COMPLETA

**Versión:** 2.0
**Fecha del Evento:** 28 de Noviembre, 2025
**Organización:** Nodo Bioceánico Central

---

## 📖 ÍNDICE

1. [Descripción General](#descripción-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Roles de Usuario](#roles-de-usuario)
4. [Funcionalidades por Rol](#funcionalidades-por-rol)
5. [Flujo de Trabajo](#flujo-de-trabajo)
6. [URLs de Acceso](#urls-de-acceso)
7. [Características Técnicas](#características-técnicas)
8. [Guía de Uso](#guía-de-uso)

---

## 🎯 DESCRIPCIÓN GENERAL

### ¿Qué es Rueda de Negocios?

Sistema web de gestión de reuniones empresariales para eventos de networking B2B. Permite la coordinación automatizada de encuentros entre empresas demandantes de servicios y empresas oferentes durante el evento del Nodo Bioceánico Central.

### Concepto Operativo

El sistema funciona como un **modelo de asientos de avión**:
- **15 mesas físicas** en el evento
- **4 bloques horarios** de 15 minutos cada uno
- Las empresas demandantes **reservan mesas específicas** con anticipación
- Las empresas oferentes **solicitan reuniones** en las mesas/horarios disponibles
- Cada reunión dura 15 minutos con 5 minutos de descanso entre bloques

### Horarios del Evento

| Bloque | Horario | Duración |
|--------|---------|----------|
| Bloque 1 | 11:00 - 11:15 | 15 min |
| Descanso | 11:15 - 11:20 | 5 min |
| Bloque 2 | 11:20 - 11:35 | 15 min |
| Descanso | 11:35 - 11:40 | 5 min |
| Bloque 3 | 11:40 - 11:55 | 15 min |
| Descanso | 11:55 - 12:00 | 5 min |
| Bloque 4 | 12:00 - 12:15 | 15 min |

### Capacidades del Sistema

- **Mesas totales:** 15 mesas físicas
- **Bloques horarios:** 4 bloques de 15 minutos
- **Slots totales:** 60 espacios (15 mesas × 4 bloques)
- **Empresas demandantes máximo:** 30 empresas
- **Reuniones máximas posibles:** 60 reuniones simultáneas
- **Empresas oferentes:** Sin límite

---

## 🏗️ ARQUITECTURA DEL SISTEMA

### Modelo de Base de Datos

#### Tablas Principales

**1. `empresas`**
- Almacena información de todas las empresas participantes
- Campos: id, nombre, tipo (grande/pyme), rubro, email_contacto, teléfono, servicios_ofrecidos, servicios_necesitados, activo

**2. `usuarios`**
- Usuarios del sistema vinculados a empresas
- Campos: id, nombre_completo, email, password_hash, rol (empresa_a/empresa_b/admin), empresa_id, activo

**3. `bloques_horarios_globales`**
- Define los 4 bloques horarios del evento
- Campos: id, fecha (2025-11-28), hora_inicio, hora_fin, orden

**4. `disponibilidad_empresas`**
- Reservas de mesa+bloque por empresas demandantes
- Campos: id, empresa_id, bloque_id, mesa_numero (1-15), disponible
- **UNIQUE constraint:** mesa_numero + bloque_id (previene doble reserva)

**5. `reuniones`**
- Registro de todas las reuniones solicitadas/confirmadas
- Campos: id, empresa_a_id, empresa_b_id, bloque_global_id, mesa_asignada, estado (pendiente/confirmada/rechazada/cancelada), notas, fecha_solicitud

### Restricciones de Negocio

1. **Una empresa demandante puede reservar máximo 2 bloques**
2. **Todos los bloques deben ser en la MISMA mesa**
3. **Una mesa+bloque solo puede tener una reunión confirmada a la vez**
4. **Máximo 30 empresas en modalidad "Busco Servicios"**
5. **Solo dominios corporativos únicos (no se permiten múltiples registros de @mismaempresa.cl)**
6. **Los dominios públicos (gmail, hotmail, etc.) SÍ permiten múltiples registros**

---

## 👥 ROLES DE USUARIO

### 1. 🏢 Empresa Demandante (Rol: empresa_a)
**Modalidad:** "Busco Servicios"
**Tipo en DB:** `tipo = 'grande'`

**Características:**
- Reserva mesa y bloques horarios de forma anticipada
- Recibe solicitudes de reunión de empresas oferentes
- Acepta o rechaza solicitudes
- Puede cancelar reuniones
- **Límite:** Máximo 30 empresas en el sistema

**Acceso:**
- Email registrado (corporativo)
- Contraseña personal

### 2. 🤝 Empresa Oferente (Rol: empresa_b)
**Modalidad:** "Ofrezco Servicios"
**Tipo en DB:** `tipo = 'pyme'`

**Características:**
- Explora agendas de empresas demandantes
- Solicita reuniones en horarios disponibles
- Ve estado de sus solicitudes (pendiente/confirmada/rechazada)
- Puede cancelar reuniones confirmadas
- **Límite:** Sin límite de empresas

**Acceso:**
- Email registrado (corporativo o personal)
- Contraseña personal

### 3. ⚙️ Administrador (Rol: admin)
**Permisos:** Superadministrador con acceso total

**Características:**
- Control total del sistema
- Puede crear reuniones manualmente SIN límites
- Asigna y libera slots de mesas
- Edita, reprograma y reasigna reuniones
- Exporta datos del evento
- Visualiza mapa completo de ocupación

**Acceso:**
- Email: `admin@bioceanicocentral.cl`
- Contraseña: `admin123` (cambiar después del primer uso)

---

## ⚡ FUNCIONALIDADES POR ROL

### 🏢 PANEL EMPRESA DEMANDANTE (`/views/panel-empresa-a.php`)

#### 1. Configurar Disponibilidad
**Ubicación:** Wizard de disponibilidad

**Funcionalidad:**
- Grid visual 15×4 mostrando todas las mesas y bloques
- Slots ocupados por otras empresas aparecen bloqueados con nombre de empresa
- Selección de hasta 2 bloques en la MISMA mesa
- Validación en tiempo real de disponibilidad
- Confirmación visual de selección

**Proceso:**
1. Usuario ve mapa de 15 mesas × 4 bloques
2. Clicks en celdas disponibles (máximo 2, misma mesa)
3. Sistema valida:
   - No más de 2 bloques
   - Misma mesa para ambos bloques
   - Mesa+bloque no ocupado
4. Guardar reserva en `disponibilidad_empresas`

**API:** `POST /api/guardar_disponibilidad.php`

#### 2. Gestionar Solicitudes Recibidas
**Ubicación:** Tab "Solicitudes Recibidas"

**Funcionalidad:**
- Lista de todas las solicitudes de empresas oferentes
- Información mostrada:
  - Nombre empresa oferente
  - Rubro
  - Fecha y hora del bloque
  - Mesa asignada
  - Mensaje/notas de la solicitud
  - Estado actual
- Acciones disponibles:
  - ✅ **Aceptar:** Confirma la reunión (estado → confirmada)
  - ❌ **Rechazar:** Rechaza la solicitud (estado → rechazada)
  - 🗑️ **Cancelar:** Cancela reunión confirmada (libera slot)

**API:** `POST /api/reuniones.php`

#### 3. Ver Agenda Confirmada
**Ubicación:** Tab "Mi Agenda"

**Funcionalidad:**
- Calendario con todas las reuniones confirmadas
- Orden cronológico por bloque
- Información completa:
  - Empresa oferente
  - Mesa número
  - Bloque horario
  - Notas/mensajes
- Opción de cancelar si es necesario

**Notificaciones Email:**
- Recibe email cuando empresa oferente solicita reunión
- Envía email cuando acepta solicitud
- Envía email cuando rechaza solicitud

---

### 🤝 PANEL EMPRESA OFERENTE (`/views/panel-empresa-b.php`)

#### 1. Explorar Empresas Demandantes
**Ubicación:** Tab "Empresas Disponibles"

**Funcionalidad:**
- Listado de todas las empresas demandantes activas
- Filtros de búsqueda por:
  - Nombre
  - Rubro
- Información mostrada:
  - Nombre empresa
  - Rubro
  - Servicios que necesita
  - Botón "Ver Agenda"

**Proceso:**
1. Usuario explora lista de empresas demandantes
2. Click en "Ver Agenda" de empresa de interés
3. Se abre modal con bloques disponibles

#### 2. Solicitar Reuniones
**Ubicación:** Modal "Agenda de Empresa"

**Funcionalidad:**
- Lista de bloques donde la empresa demandante tiene disponibilidad
- Para cada bloque muestra:
  - Horario (HH:MM - HH:MM)
  - Número de mesa
  - Estado: Disponible / Ya solicitado / Ocupado
- Formulario de solicitud:
  - Campo para mensaje/notas
  - Botón "Solicitar Reunión"

**Validaciones:**
- No puede solicitar si ya hay reunión pendiente/confirmada
- No puede solicitar en bloque ya ocupado
- Mensaje obligatorio

**API:** `POST /api/solicitar_reunion.php`

**Notificación Email:**
- Empresa demandante recibe email con:
  - Nombre empresa oferente
  - Horario solicitado
  - Mensaje
  - Link al panel para aceptar/rechazar

#### 3. Ver Mis Solicitudes
**Ubicación:** Tab "Mis Solicitudes"

**Funcionalidad:**
- Historial completo de solicitudes enviadas
- Estados posibles:
  - 🟡 **Pendiente:** Esperando respuesta
  - ✅ **Confirmada:** Aceptada, reunión agendada
  - ❌ **Rechazada:** No aceptada
  - 🚫 **Cancelada:** Cancelada por alguna de las partes
- Información por solicitud:
  - Empresa demandante
  - Fecha y hora
  - Mesa
  - Estado
  - Notas

#### 4. Ver Mi Agenda
**Ubicación:** Tab "Mi Agenda"

**Funcionalidad:**
- Solo reuniones CONFIRMADAS
- Vista cronológica ordenada
- Información completa de cada reunión:
  - Empresa demandante
  - Mesa y horario
  - Notas
- Opción de cancelar

---

### ⚙️ PANEL ADMINISTRADOR (`/views/panel-admin.php`)

#### Tab 1: Reuniones

**Funcionalidad:**
- Tabla completa de TODAS las reuniones del sistema
- Columnas:
  - ID reunión
  - Empresa Demandante
  - Empresa Oferente
  - **Mesa** (nuevo)
  - Fecha
  - Horario
  - Estado
  - Acciones

**Acciones por Reunión:**

1. **✏️ Editar**
   - Cambiar estado (pendiente/confirmada/rechazada/cancelada)
   - Agregar/modificar notas administrativas
   - **API:** `POST /api/admin_reuniones.php?accion=editar`

2. **🕐 Reprogramar**
   - Cambiar a otro bloque horario
   - Sistema verifica disponibilidad de empresa A en nuevo bloque
   - Obtiene mesa automáticamente del nuevo bloque
   - Valida que no haya conflictos
   - **API:** `POST /api/admin_reuniones.php?accion=reprogramar`

3. **🔄 Reasignar Oferente**
   - Cambiar empresa oferente (B) de la reunión
   - Mantiene demandante, horario y mesa
   - Requiere motivo del cambio
   - Se registra en notas
   - **API:** `POST /api/admin_reuniones.php?accion=reasignar`

4. **🚫 Cancelar**
   - Cancela la reunión
   - Libera el slot para nuevas solicitudes
   - **API:** `POST /api/reuniones.php?accion=cancelar`

#### Tab 2: Mapa de Mesas ⭐ (NUEVO)

**Funcionalidad:**
- **Grid visual 15×4:** Todas las mesas y bloques en tiempo real
- Visualización completa del evento

**Leyenda de colores:**
- ⚪ **Gris claro:** Slot disponible (sin reservar)
- 🔵 **Azul:** Slot reservado por empresa
- ✅ **Verde:** Indica número de reuniones confirmadas en ese slot

**Información por Slot Ocupado:**
- Nombre de empresa (truncado a 20 chars)
- Tipo: "Demandante" o "Oferente"
- Número de reuniones confirmadas
- Botones de acción:
  - 👁️ **Ver Detalles:** Muestra info completa del slot
  - ❌ **Liberar:** Elimina la reserva y reuniones pendientes

**Información por Slot Disponible:**
- Texto "Disponible"
- Botón:
  - ➕ **Asignar:** Asignar mesa+bloque a empresa demandante

**APIs:**
- Ver detalles: `GET /api/admin_reuniones.php?accion=detalle_slot`
- Liberar slot: `POST /api/admin_reuniones.php?accion=liberar_slot`
- Asignar slot: `POST /api/admin_reuniones.php?accion=asignar_slot`

**Casos de Uso:**
1. Ver ocupación completa del evento de un vistazo
2. Liberar mesas de empresas que cancelaron
3. Asignar manualmente mesas a empresas que no completaron el wizard
4. Identificar patrones de ocupación

#### Tab 3: Empresas

**Funcionalidad:**
- Listado completo de empresas registradas
- Información mostrada:
  - ID
  - Nombre
  - Tipo (Grande/Pyme = Demandante/Oferente)
  - Rubro
  - Email
  - Teléfono
  - Estado (Activa/Inactiva)

**Posibles mejoras futuras:**
- Activar/desactivar empresas
- Editar información
- Ver estadísticas por empresa

#### Tab 4: Crear Reunión ⭐ (NUEVO)

**Funcionalidad:**
- **Privilegios especiales:** El admin NO tiene límite de 2 bloques
- Puede crear reuniones manualmente en cualquier configuración

**Formulario:**

1. **Empresa Demandante (Busco Servicios)**
   - Dropdown con todas las empresas tipo 'grande'
   - Al seleccionar, carga disponibilidad actual

2. **Mesa**
   - Selector 1-15
   - Independiente de disponibilidad previa

3. **Bloque Horario**
   - Selector de 4 bloques
   - Muestra hora inicio y fin

4. **Empresa Oferente (Ofrezco Servicios)**
   - Dropdown con todas las empresas tipo 'pyme'

5. **Estado Inicial**
   - Pendiente: Requiere confirmación de empresa A
   - Confirmada: Reunión directamente confirmada

6. **Notas / Observaciones**
   - Campo libre para comentarios administrativos
   - Se agrega automáticamente tag "[CREADA POR ADMIN]"

**Proceso:**
1. Admin completa formulario
2. Sistema valida:
   - Empresas existan y sean tipos correctos
   - Bloque exista
   - No haya conflicto de mesa+bloque si se elige "confirmada"
3. Si empresa A no tiene disponibilidad en ese slot, **se crea automáticamente**
4. Se crea reunión
5. Redirige a tab Reuniones

**API:** `POST /api/admin_reuniones.php?accion=crear_reunion_admin`

**Casos de Uso:**
- Empresa demandante necesita más de 2 bloques
- Correcciones de último minuto
- Reuniones VIP prioritarias
- Asignaciones manuales por solicitud especial

#### Tab 5: Exportar

**Funcionalidad:**
- Descarga de datos en formato CSV para análisis externo

**Opciones de Exportación:**

1. **Todas las Reuniones**
   - Incluye: pendientes, confirmadas, rechazadas, canceladas
   - Campos: ID, Empresa A, Empresa B, Fecha, Hora, Mesa, Estado, Notas
   - **URL:** `/api/exportar.php?tipo=reuniones`

2. **Solo Confirmadas**
   - Solo reuniones confirmadas (agenda final del evento)
   - Útil para imprimir agenda del día
   - **URL:** `/api/exportar.php?tipo=confirmadas`

3. **Listado de Empresas**
   - Todas las empresas registradas
   - Campos: ID, Nombre, Tipo, Rubro, Email, Teléfono, Servicios
   - **URL:** `/api/exportar.php?tipo=empresas`

**Estadísticas Mostradas:**
- Total empresas grandes/pymes
- Total reuniones / confirmadas / pendientes / rechazadas
- Bloques disponibles / ocupados
- Tasa de confirmación (%)

---

## 🔄 FLUJO DE TRABAJO

### Flujo Completo del Sistema

```
1. REGISTRO
   ├─ Empresa elige modalidad: Busco Servicios / Ofrezco Servicios
   ├─ Completa datos: nombre, rubro, email, teléfono, servicios
   ├─ Validación de dominio corporativo único
   ├─ Límite: máximo 30 en "Busco Servicios"
   └─ Recibe email de bienvenida con credenciales

2. CONFIGURACIÓN (Solo Demandantes)
   ├─ Login al sistema
   ├─ Accede a Wizard de Disponibilidad
   ├─ Ve mapa 15×4 de mesas y bloques
   ├─ Selecciona 1 mesa + máximo 2 bloques
   ├─ Confirma reserva
   └─ Slots quedan visibles para oferentes

3. SOLICITUD DE REUNIÓN (Oferentes)
   ├─ Login al sistema
   ├─ Explora empresas demandantes
   ├─ Ve agenda de empresa de interés
   ├─ Solicita reunión en bloque disponible
   ├─ Agrega mensaje/notas
   ├─ Sistema envía email a demandante
   └─ Espera respuesta

4. GESTIÓN DE SOLICITUD (Demandantes)
   ├─ Recibe email con nueva solicitud
   ├─ Login al panel
   ├─ Revisa solicitudes pendientes
   ├─ Decide: Aceptar o Rechazar
   │  ├─ Si ACEPTA:
   │  │  ├─ Reunión pasa a estado "confirmada"
   │  │  ├─ Oferente recibe email de confirmación
   │  │  └─ Aparece en agenda de ambos
   │  └─ Si RECHAZA:
   │     ├─ Reunión pasa a estado "rechazada"
   │     ├─ Oferente recibe email de rechazo
   │     └─ Slot queda disponible para otros

5. DÍA DEL EVENTO
   ├─ Ambas empresas acceden a "Mi Agenda"
   ├─ Ven reuniones confirmadas con mesa y horario
   ├─ Se dirigen a mesa asignada en horario indicado
   └─ Reunión de 15 minutos

6. ADMINISTRACIÓN (Paralelo)
   ├─ Admin monitorea sistema continuamente
   ├─ Visualiza mapa de ocupación
   ├─ Gestiona cambios de último minuto
   ├─ Crea reuniones especiales si necesario
   ├─ Exporta reportes
   └─ Post-evento: análisis de datos
```

### Estados de una Reunión

```
PENDIENTE
   ↓ (Empresa A acepta)
CONFIRMADA
   ↓ (Cualquiera cancela)
CANCELADA

PENDIENTE
   ↓ (Empresa A rechaza)
RECHAZADA
```

---

## 🌐 URLS DE ACCESO

### URLs Públicas

| URL | Descripción | Acceso |
|-----|-------------|--------|
| `/views/login.php` | Pantalla de inicio de sesión | Todos |
| `/views/registro.php` | Registro de nuevas empresas | Público |
| `/api/logout.php` | Cerrar sesión | Usuarios autenticados |

### URLs por Rol

#### Empresa Demandante (empresa_a)
| URL | Descripción |
|-----|-------------|
| `/views/panel-empresa-a.php` | Panel principal |
| `/views/wizard-disponibilidad.php` | Configurar mesas y bloques |

#### Empresa Oferente (empresa_b)
| URL | Descripción |
|-----|-------------|
| `/views/panel-empresa-b.php` | Panel principal |

#### Administrador (admin)
| URL | Descripción |
|-----|-------------|
| `/views/panel-admin.php` | Panel de administración completo |
| `/crear-admin.php` | **Script temporal** para crear usuario admin (eliminar después de usar) |

### APIs Internas

#### Autenticación
- `POST /api/login.php` - Iniciar sesión
- `POST /api/registro.php` - Registrar nueva empresa
- `GET /api/logout.php` - Cerrar sesión

#### Disponibilidad
- `POST /api/guardar_disponibilidad.php` - Guardar reserva de mesa+bloques (empresa_a)
- `GET /api/bloques.php?empresa_id={id}` - Obtener disponibilidad de empresa

#### Reuniones
- `POST /api/solicitar_reunion.php` - Solicitar reunión (empresa_b)
- `POST /api/reuniones.php` - Gestionar reuniones (aceptar/rechazar/cancelar)

#### Administración
- `POST /api/admin_reuniones.php` - Todas las acciones de admin
  - Acciones GET: detalle_slot, disponibilidad_empresa
  - Acciones POST: editar, reprogramar, reasignar, liberar_slot, asignar_slot, crear_reunion_admin

#### Exportación
- `GET /api/exportar.php?tipo=reuniones` - Exportar todas las reuniones
- `GET /api/exportar.php?tipo=confirmadas` - Exportar solo confirmadas
- `GET /api/exportar.php?tipo=empresas` - Exportar empresas

---

## 💻 CARACTERÍSTICAS TÉCNICAS

### Stack Tecnológico

**Backend:**
- PHP 7.4+
- MySQL/MariaDB
- PDO (PHP Data Objects) para acceso a base de datos
- Prepared Statements (seguridad contra SQL injection)
- password_hash() para encriptación de contraseñas

**Frontend:**
- HTML5
- Tailwind CSS (CDN) para estilos
- JavaScript Vanilla (ES6+)
- Font Awesome 6.4.0 para iconos
- Google Fonts (Inter)

**Email:**
- PHPMailer para envío de correos
- SMTP configurado
- Templates HTML responsivos

### Seguridad

**Autenticación:**
- Sessions PHP
- Verificación de rol en cada página
- Función `checkAccess()` para control de acceso
- Logout seguro con destrucción de sesión

**Validación de Datos:**
- Función `sanitize()` para limpiar inputs
- Validación de emails (isValidEmail)
- Prepared statements en todas las queries
- Control de tipos de datos (intval, sanitize)

**Protección de Dominios:**
- Validación de dominio corporativo único
- Lista blanca de dominios públicos (gmail, hotmail, etc.)
- Prevención de registros duplicados

**Restricciones de Negocio:**
- UNIQUE constraints en base de datos
- Validaciones en API y UI
- Transacciones con rollback en operaciones críticas

### Base de Datos

**Motor:** MySQL/MariaDB
**Charset:** utf8mb4
**Collation:** utf8mb4_unicode_ci

**Índices:**
- Primary keys en todas las tablas
- Foreign keys para integridad referencial
- UNIQUE constraint en `disponibilidad_empresas` (mesa_numero, bloque_id)
- Índices en columnas frecuentemente consultadas

**Relaciones:**
- `usuarios.empresa_id` → `empresas.id`
- `disponibilidad_empresas.empresa_id` → `empresas.id`
- `disponibilidad_empresas.bloque_id` → `bloques_horarios_globales.id`
- `reuniones.empresa_a_id` → `empresas.id`
- `reuniones.empresa_b_id` → `empresas.id`
- `reuniones.bloque_global_id` → `bloques_horarios_globales.id`

### Notificaciones Email

**Eventos que generan emails:**

1. **Registro exitoso** → Nueva empresa
   - Template: Bienvenida con credenciales
   - Destinatario: Email de empresa

2. **Solicitud de reunión** → Empresa demandante
   - Template: Nueva solicitud con detalles
   - Incluye: Empresa oferente, horario, mesa, mensaje
   - Link: Acceso directo al panel

3. **Reunión aceptada** → Empresa oferente
   - Template: Confirmación de reunión
   - Incluye: Todos los detalles de la reunión

4. **Reunión rechazada** → Empresa oferente
   - Template: Notificación de rechazo
   - Mensaje: Slot queda disponible para solicitar otra

5. **Reunión cancelada** → Ambas empresas
   - Template: Notificación de cancelación
   - Incluye: Información de quién canceló

**Configuración:**
- Servidor SMTP configurado en `config/config.php`
- Templates HTML responsivos
- Manejo de errores (log si falla, pero no bloquea operación)

---

## 📚 GUÍA DE USO

### Para Empresas Demandantes (Busco Servicios)

#### Paso 1: Registro
1. Ir a `/views/registro.php`
2. Seleccionar "Modalidad: Busco Servicios"
3. Llenar formulario con datos de empresa
4. Usar email corporativo (dominio único por empresa)
5. Crear contraseña segura
6. Enviar registro
7. Recibirás email de confirmación

#### Paso 2: Configurar Disponibilidad
1. Iniciar sesión en `/views/login.php`
2. Serás redirigido al panel empresa A
3. Click en botón "Configurar Disponibilidad"
4. Verás mapa de 15 mesas × 4 bloques
5. Selecciona UNA mesa
6. Selecciona hasta 2 bloques en ESA mesa
   - Celdas grises = disponibles
   - Celdas azules = ocupadas por otras empresas
7. Click en "Guardar Disponibilidad"
8. Confirmación visual

#### Paso 3: Gestionar Solicitudes
1. En panel principal, ve a tab "Solicitudes Recibidas"
2. Verás lista de empresas oferentes que quieren reunirse contigo
3. Para cada solicitud:
   - Lee información de la empresa
   - Lee su mensaje
   - Ve horario y mesa
   - Decide:
     - Click "Aceptar" → Reunión confirmada ✅
     - Click "Rechazar" → Solicitud rechazada ❌
4. Empresa oferente recibirá email con tu decisión

#### Paso 4: Ver Agenda
1. Tab "Mi Agenda"
2. Verás todas tus reuniones confirmadas
3. Información incluye:
   - Empresa oferente
   - Horario exacto
   - Número de mesa
   - Notas
4. Puedes cancelar si es necesario

#### Día del Evento
1. Llega al menos 10 minutos antes del primer bloque
2. Ubica tu mesa asignada
3. Permanece en esa mesa durante tus bloques reservados
4. Reuniones de 15 minutos cada una
5. 5 minutos de descanso entre bloques

---

### Para Empresas Oferentes (Ofrezco Servicios)

#### Paso 1: Registro
1. Ir a `/views/registro.php`
2. Seleccionar "Modalidad: Ofrezco Servicios"
3. Llenar formulario con datos de empresa
4. Puedes usar email corporativo o personal
5. Crear contraseña segura
6. Enviar registro
7. Recibirás email de confirmación

#### Paso 2: Explorar Empresas
1. Iniciar sesión en `/views/login.php`
2. Serás redirigido al panel empresa B
3. En tab "Empresas Disponibles" verás listado completo
4. Usa filtros para encontrar empresas de interés
5. Lee información:
   - Rubro de la empresa
   - Servicios que necesita
6. Click "Ver Agenda" en empresa que te interese

#### Paso 3: Solicitar Reuniones
1. Se abre modal con agenda de la empresa
2. Verás bloques donde tiene disponibilidad
3. Para cada bloque:
   - Horario
   - Número de mesa
   - Estado (disponible/ya solicitado/ocupado)
4. Selecciona bloque disponible
5. Escribe mensaje para la empresa (obligatorio)
   - Preséntate
   - Explica por qué quieres reunirte
   - Menciona qué ofreces
6. Click "Solicitar Reunión"
7. Empresa demandante recibirá email

#### Paso 4: Seguimiento
1. Tab "Mis Solicitudes"
2. Verás todas tus solicitudes con estados:
   - 🟡 Pendiente: Esperando respuesta
   - ✅ Confirmada: ¡Aceptada! Ve a Mi Agenda
   - ❌ Rechazada: No aceptada, puedes solicitar otro horario
3. Recibirás emails cuando cambien estados

#### Paso 5: Ver Agenda
1. Tab "Mi Agenda"
2. Solo aparecen reuniones CONFIRMADAS
3. Información incluye:
   - Empresa demandante
   - Horario exacto
   - Número de mesa
4. Puedes cancelar si es necesario

#### Día del Evento
1. Revisa tu agenda antes del evento
2. Llega al menos 10 minutos antes de tu primera reunión
3. Dirígete a la mesa indicada en el horario exacto
4. Reunión de 15 minutos
5. Al terminar, si tienes otra reunión, ve a la siguiente mesa

---

### Para Administradores

#### Paso 1: Crear Usuario Admin (Primera vez)
1. Acceder a `/crear-admin.php` en navegador
2. Script crea automáticamente:
   - Empresa "Administración Sistema"
   - Usuario admin con credenciales:
     - Email: admin@bioceanicocentral.cl
     - Password: admin123
3. **IMPORTANTE:** Eliminar archivo `crear-admin.php` por seguridad
4. Cambiar contraseña en primer login

#### Paso 2: Acceso al Panel
1. Login en `/views/login.php`
2. Sistema detecta rol admin
3. Redirige a `/views/panel-admin.php`
4. 5 tabs disponibles

#### Paso 3: Monitorear Reuniones
**Tab "Reuniones":**
1. Ver tabla completa de reuniones
2. Filtrar por estado mentalmente
3. Acciones disponibles:
   - Editar: Cambiar estado o notas
   - Reprogramar: Mover a otro bloque
   - Reasignar: Cambiar oferente
   - Cancelar: Eliminar reunión

#### Paso 4: Visualizar Ocupación
**Tab "Mapa Mesas":**
1. Vista grid 15×4 completa
2. Identificar patrones:
   - ¿Qué mesas están más ocupadas?
   - ¿Qué bloques tienen más demanda?
   - ¿Qué empresas tienen más reuniones?
3. Acciones rápidas:
   - Ver detalles de slot ocupado
   - Liberar slot si empresa canceló
   - Asignar slot manualmente

#### Paso 5: Gestionar Empresas
**Tab "Empresas":**
1. Ver listado completo
2. Verificar datos de contacto
3. Identificar empresas activas/inactivas

#### Paso 6: Crear Reuniones Especiales
**Tab "Crear Reunión":**
1. Seleccionar empresa demandante
2. Ver su disponibilidad actual (opcional)
3. Elegir mesa y bloque (sin restricciones)
4. Seleccionar empresa oferente
5. Elegir estado inicial
6. Agregar notas
7. Crear reunión
8. Sistema crea disponibilidad si no existe

#### Paso 7: Exportar Datos
**Tab "Exportar":**
1. Opciones:
   - Todas las reuniones (CSV)
   - Solo confirmadas (CSV)
   - Empresas (CSV)
2. Abrir en Excel/Sheets para análisis
3. Generar reportes
4. Estadísticas del evento

#### Funciones Especiales del Admin

**Casos de Uso Comunes:**

1. **Empresa necesita más de 2 bloques:**
   - Usar "Crear Reunión" para bloques adicionales
   - Sistema no valida límite para admin

2. **Empresa se da de baja:**
   - Tab "Mapa Mesas"
   - Encontrar slots de esa empresa
   - Click "Liberar" en cada uno
   - Reuniones pendientes se eliminan

3. **Cambio de último minuto:**
   - Tab "Reuniones"
   - Encontrar reunión
   - "Reprogramar" a nuevo horario
   - Sistema valida disponibilidad

4. **Empresa oferente no puede asistir:**
   - Tab "Reuniones"
   - Encontrar reunión
   - "Reasignar" a otra empresa oferente
   - Agregar motivo del cambio

5. **Error en mesa asignada:**
   - Tab "Reuniones"
   - "Reprogramar" aunque sea al mismo horario
   - Sistema reasigna mesa correcta

---

## 🎯 MEJORES PRÁCTICAS

### Para Empresas Demandantes
✅ Configura tu disponibilidad lo antes posible
✅ Elige bloques consecutivos si quieres más reuniones seguidas
✅ Responde solicitudes rápido (las empresas están esperando)
✅ Lee bien los mensajes antes de aceptar/rechazar
✅ Verifica tu agenda 24h antes del evento

### Para Empresas Oferentes
✅ Explora todas las empresas disponibles
✅ Personaliza tu mensaje en cada solicitud
✅ Solicita reuniones temprano (slots se llenan rápido)
✅ Ten un plan B si te rechazan
✅ Confirma mesa y horario antes de ir al evento

### Para Administradores
✅ Monitorea el sistema diariamente
✅ Exporta backups antes del evento
✅ Ten laptop disponible el día del evento
✅ Comunica cambios de último minuto por email
✅ Revisa el mapa de mesas para balancear carga

---

## 🔧 MANTENIMIENTO

### Tareas Pre-Evento
- [ ] Verificar que bloques horarios estén creados (4 bloques)
- [ ] Confirmar configuración SMTP para emails
- [ ] Probar flujo completo: registro → disponibilidad → solicitud → confirmación
- [ ] Exportar backup de base de datos
- [ ] Verificar que hay menos de 30 empresas demandantes
- [ ] Revisar que todos los slots tengan empresas asignadas

### Tareas Durante Evento
- [ ] Monitorear panel admin continuamente
- [ ] Resolver problemas de último minuto
- [ ] Tener laptop con acceso al sistema
- [ ] Lista impresa de agenda como respaldo

### Tareas Post-Evento
- [ ] Exportar todas las reuniones confirmadas
- [ ] Generar reporte de estadísticas
- [ ] Solicitar feedback a empresas participantes
- [ ] Backup final de base de datos

---

## 📞 SOPORTE

### Problemas Comunes

**"No puedo iniciar sesión"**
- Verificar email y contraseña
- ¿Usaste el email correcto al registrarte?
- ¿La empresa está activa?
- Contactar administrador

**"No puedo seleccionar más bloques"**
- Límite de 2 bloques para empresas demandantes
- Contactar admin si necesitas más

**"El slot está ocupado"**
- Otra empresa ya reservó esa mesa+bloque
- Elige otra mesa o bloque
- Contactar admin si es urgente

**"No recibo emails"**
- Revisar carpeta spam
- Verificar email en perfil
- Contactar admin para reenvío

### Contacto
**Administrador del Sistema:**
- Email: admin@bioceanicocentral.cl
- Acceso: Panel de administración

---

## 📄 LICENCIA Y CRÉDITOS

**Desarrollado para:** Nodo Bioceánico Central
**Evento:** Rueda de Negocios 2025
**Fecha:** 28 de Noviembre, 2025

---

**Fin de la documentación**

*Versión 2.0 - Última actualización: [Fecha actual]*
