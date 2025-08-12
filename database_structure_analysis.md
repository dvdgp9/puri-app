# Análisis de Estructura de Base de Datos - Sistema Puri

## Información General
- **Base de datos**: `pasarlistabdd`
- **Servidor**: localhost:3306
- **Total de tablas**: 6
- **Total de registros**: 135 (aprox.)

## Estructura de Tablas

### 1. Tabla: `actividades`
**Total de registros**: 21

| Campo | Tipo | Clave | Descripción |
|-------|------|-------|-------------|
| `id` | INT | PK | Identificador único de la actividad |
| `instalacion_id` | INT | FK | Referencia a la instalación donde se realiza |
| `nombre` | VARCHAR | | Nombre descriptivo de la actividad |
| `horario` | VARCHAR | | Horario en formato texto (LEGACY - mantenido por compatibilidad) |
| `dias_semana` | SET | | Días de la semana estructurados (Lunes, Martes, etc.) |
| `hora_inicio` | TIME | | Hora de inicio de la actividad |
| `hora_fin` | TIME | | Hora de finalización de la actividad |
| `fecha_inicio` | DATE | | Fecha de inicio de la actividad |
| `fecha_fin` | DATE | NULL | Fecha de finalización (opcional) |

**Ejemplos de datos**:
- ID 1: "Natación Avanzada", Instalación 1, Horario: "Lunes y Miércoles 10:00-11:30", Días: "Lunes,Miércoles", Inicio: 10:00, Fin: 11:30
- ID 2: "Baloncesto Juvenil", Instalación 2, Horario: "Martes y Jueves 16:00-17:30", Días: "Martes,Jueves", Inicio: 16:00, Fin: 17:30
- ID 3: "Fitness Funcional", Instalación 3, Horario: "Lunes, Miércoles y Viernes 09:00-10:00", Días: "Lunes,Miércoles,Viernes", Inicio: 09:00, Fin: 10:00

### 2. Tabla: `asistencias`
**Total de registros**: 24

| Campo | Tipo | Clave | Descripción |
|-------|------|-------|-------------|
| `id` | INT | PK | Identificador único del registro de asistencia |
| `actividad_id` | INT | FK | Referencia a la actividad |
| `usuario_id` | INT | FK | Referencia al inscrito |
| `fecha` | DATE | | Fecha del registro de asistencia |
| `asistio` | TINYINT | | Estado de asistencia (0=No asistió, 1=Asistió) |
| `registrado_en` | TIMESTAMP | | Fecha y hora de registro del dato |

**Ejemplos de datos**:
- Actividad 3, Usuario 3, Fecha 2025-02-07, Asistió (0)
- Actividad 3, Usuario 3, Fecha 2025-02-05, Asistió (1)
- Actividad 4, Usuario 4, Fecha 2025-02-05, Asistió (1)

### 3. Tabla: `centros`
**Total de registros**: 5

| Campo | Tipo | Clave | Descripción |
|-------|------|-------|-------------|
| `id` | INT | PK | Identificador único del centro |
| `nombre` | VARCHAR | | Nombre del centro deportivo |
| `direccion` | VARCHAR | | Dirección del centro |
| `password` | VARCHAR | | Contraseña de acceso al centro |

**Ejemplos de datos**:
- ID 1: "Centro prueba 1", Dirección: "Calle de la Prueba 1", Password: "CONTRACENTRO"
- ID 2: "Centro Deportivo Municipal", Dirección: "Calle de la Prueba 2", Password: "CONTRACENTRO"
- ID 3: "Polideportivo El Parque", Dirección: "Calle de la Prueba 3", Password: "$2y$10$bcdefghijklmnopqrstuvw"

### 4. Tabla: `inscritos`
**Total de registros**: 24

| Campo | Tipo | Clave | Descripción |
|-------|------|-------|-------------|
| `id` | INT | PK | Identificador único del inscrito |
| `nombre` | VARCHAR | | Nombre del participante |
| `apellidos` | VARCHAR | | Apellidos del participante |
| `actividad_id` | INT | FK | Referencia a la actividad en la que está inscrito |

**Ejemplos de datos**:
- ID 3: "David Gutiérrez", Actividad 3
- ID 4: "Juan Pérez García", Actividad 3
- ID 5: "María López Sánchez", Actividad 3

**Nota**: Según el código, esta tabla tenía campos `dni` y `telefono` que fueron eliminados posteriormente.

### 5. Tabla: `instalaciones`
**Total de registros**: 14

| Campo | Tipo | Clave | Descripción |
|-------|------|-------|-------------|
| `id` | INT | PK | Identificador único de la instalación |
| `centro_id` | INT | FK | Referencia al centro al que pertenece |
| `nombre` | VARCHAR | | Nombre de la instalación |

**Ejemplos de datos**:
- ID 1: "Piscina Olímpica", Centro 1
- ID 2: "Cancha de Baloncesto", Centro 1
- ID 3: "Sala de Fitness", Centro 2

### 6. Tabla: `observaciones`
**Total de registros**: 24

| Campo | Tipo | Clave | Descripción |
|-------|------|-------|-------------|
| `id` | INT | PK | Identificador único de la observación |
| `actividad_id` | INT | FK | Referencia a la actividad |
| `fecha` | DATE | | Fecha de la observación |
| `observacion` | TEXT | | Contenido de la observación |
| `created_at` | TIMESTAMP | | Fecha y hora de creación |
| `updated_at` | TIMESTAMP | | Fecha y hora de última actualización |

**Ejemplos de datos**:
- ID 1: Actividad 3, Fecha 2025-03-12, "El tal Joseph P&eacute;rez Garc&iacute;a estuvo to..."
- ID 2: Actividad 3, Fecha 2025-03-10, "Los que faltan est&aacute;n de fiesta regional"
- ID 3: Actividad 3, Fecha 2025-03-13, "Maldito Paco Torre&ntilde;o"

## Consideraciones Técnicas

### Codificación
- Se observan problemas de codificación en las observaciones (entidades HTML como `&eacute;`, `&ntilde;`)
- El sistema maneja UTF-8 pero hay inconsistencias en la visualización

### Integridad Referencial
- Relaciones FK correctamente implementadas
- Eliminación en cascada configurada para `observaciones`

### Campos Calculados
- `fecha_fin` en `actividades` permite NULL para actividades sin fin definido
- `asistio` es un campo booleano (TINYINT) para optimizar espacio

### Auditoría
- La tabla `observaciones` tiene campos de auditoría (`created_at`, `updated_at`)
- La tabla `asistencias` tiene `registrado_en` para trazabilidad 