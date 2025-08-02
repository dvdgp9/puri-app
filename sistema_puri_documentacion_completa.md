# Sistema Puri - Documentación Completa

## 📋 Resumen Ejecutivo

**Sistema Puri** es una aplicación web PHP para la gestión de asistencias y actividades deportivas en centros multiubicación. Desarrollado como Progressive Web App (PWA), permite el control diario de asistencias, gestión de inscripciones y generación de informes.

### Características Principales
- 🏢 **Multicentro**: Gestión de múltiples centros deportivos
- 📱 **PWA**: Funciona como aplicación móvil nativa
- 👥 **Gestión de Inscripciones**: Control completo de participantes
- ✅ **Control de Asistencias**: Registro diario con observaciones
- 📊 **Informes**: Generación de reportes en Excel
- 📄 **Importación Masiva**: Carga de participantes por CSV

---

## 🏗️ Arquitectura del Sistema

### Stack Tecnológico
```
Frontend: HTML5 + CSS3 + JavaScript (Vanilla)
Backend: PHP 8.x + PDO
Base de Datos: MySQL 8.x
Servidor Web: Apache/Nginx
```

### Estructura de Archivos
```
puri-app/
├── config/
│   ├── config.php          # Configuración BD y sesiones
│   └── version.php         # Control de versiones
├── includes/
│   ├── header.php          # Header común
│   └── footer.php          # Footer común
├── public/
│   ├── assets/
│   │   ├── css/style.css   # Estilos principales
│   │   └── icons/          # Iconos PWA
│   ├── manifest.json       # Configuración PWA
│   └── service-worker.js   # Service Worker
├── [módulos-funcionales].php
└── [apis-json].php
```

---

## 🗄️ Estructura de Base de Datos

### Diagrama de Relaciones
```
CENTROS (1:N) → INSTALACIONES (1:N) → ACTIVIDADES (1:N) → INSCRITOS
                                           ↓
                                    ASISTENCIAS (N:M)
                                           ↓
                                    OBSERVACIONES (1:N)
```

### Detalle de Tablas

#### **centros** (5 registros)
- `id` (PK), `nombre`, `password`
- Entidad principal del sistema multicentro

#### **instalaciones** (14 registros)
- `id` (PK), `centro_id` (FK), `nombre`
- Espacios físicos dentro de cada centro

#### **actividades** (21 registros)
- `id` (PK), `instalacion_id` (FK), `nombre`, `horario`, `fecha_inicio`, `fecha_fin`
- Actividades deportivas con fechas y horarios

#### **inscritos** (24 registros)
- `id` (PK), `nombre`, `apellidos`, `actividad_id` (FK)
- Participantes inscritos en cada actividad

#### **asistencias** (24 registros)
- `id` (PK), `actividad_id` (FK), `usuario_id` (FK), `fecha`, `asistio`, `registrado_en`
- Registro diario de asistencias

#### **observaciones** (24 registros)
- `id` (PK), `actividad_id` (FK), `fecha`, `observacion`, `created_at`, `updated_at`
- Notas adicionales por actividad y fecha

---

## 🔧 Funcionalidades del Sistema

### 1. **Gestión de Centros**
- **Autenticación**: Login por centro con contraseña
- **Multicentro**: Cada centro ve solo sus datos
- **Seguridad**: Validación de credenciales

### 2. **Gestión de Instalaciones**
- **CRUD Completo**: Crear, listar, editar, eliminar
- **Navegación**: Breadcrumbs y menús contextuales
- **Validación**: Permisos por centro

### 3. **Gestión de Actividades**
- **Estados**: Activas vs. Finalizadas
- **Programación**: Fechas inicio/fin, horarios
- **Clasificación**: Separación visual por estado

### 4. **Gestión de Inscritos**
- **CRUD Individual**: Formularios de alta/baja
- **Importación Masiva**: CSV con validación
- **Privacidad**: Eliminación de campos sensibles (DNI, teléfono)

### 5. **Control de Asistencias**
- **Registro Diario**: Por fecha seleccionable
- **Interfaz Táctil**: Botones Asiste/No Asiste
- **Móvil**: Swipe-to-delete functionality
- **Observaciones**: Notas por sesión
- **Transacciones**: Atomicidad en registros

### 6. **Sistema de Informes**
- **Filtros**: Por centro, instalación, actividad, período
- **Exportación**: Excel con formato específico
- **Datos**: Asistencias + observaciones + estadísticas

---

## 💻 Patrones de Desarrollo

### Seguridad
- **SQL Injection**: Prepared statements en todas las queries
- **XSS**: Sanitización con `htmlspecialchars()`
- **CSRF**: Validación de sesiones
- **Autorización**: Verificación de permisos por centro

### Base de Datos
- **Conexión**: PDO con manejo de excepciones
- **Transacciones**: Para operaciones críticas
- **Integridad**: Claves foráneas con cascada
- **Auditoría**: Timestamps en tablas críticas

### Interfaz de Usuario
- **Responsive**: Diseño móvil-first
- **PWA**: Instalable como app nativa
- **UX**: Modales, breadcrumbs, estados de carga
- **Accesibilidad**: Iconos Font Awesome, navegación clara

---

## 📱 Características PWA

### Configuración
```json
{
  "name": "Puri Ebone",
  "short_name": "Puri",
  "start_url": "/index.php",
  "display": "standalone",
  "theme_color": "#23AAC5"
}
```

### Funcionalidades Offline
- Service Worker configurado
- Cache de recursos estáticos
- Iconos adaptativos para diferentes dispositivos

---

## 🔄 Flujo de Uso Típico

1. **Acceso al Sistema**
   - Usuario selecciona centro
   - Introduce contraseña
   - Redirección a instalaciones

2. **Navegación**
   - Selección de instalación
   - Visualización de actividades (activas/finalizadas)
   - Acceso a control de asistencias

3. **Registro de Asistencias**
   - Selección de fecha
   - Marcado individual de asistencias
   - Agregado de observaciones
   - Confirmación de registro

4. **Generación de Informes**
   - Selección de criterios de filtrado
   - Autenticación por centro
   - Descarga de Excel

---

## 🛠️ Aspectos Técnicos

### Codificación
- **UTF-8**: Configuración completa
- **Conversiones**: Manejo de encoding para Excel
- **Problemas**: Entidades HTML en observaciones

### Performance
- **Consultas**: JOINs optimizados
- **Paginación**: No implementada (volúmenes bajos)
- **Cache**: Nivel navegador únicamente

### Mantenibilidad
- **Estructura**: Archivos separados por funcionalidad
- **Reutilización**: Includes para componentes comunes
- **Escalabilidad**: Diseño multicentro preparado

---

## 🚀 Recomendaciones

### Seguridad
1. **Hashear contraseñas**: Implementar bcrypt
2. **HTTPS**: Forzar conexiones seguras
3. **Validación**: Mejorar sanitización de inputs

### Funcionalidad
1. **Paginación**: Para listas grandes
2. **Filtros**: Búsqueda en listados
3. **Backup**: Sistema de respaldos automático

### UI/UX
1. **Notificaciones**: Feedback visual mejorado
2. **Shortcuts**: Atajos de teclado
3. **Dark Mode**: Tema oscuro opcional

---

## 📊 Métricas del Sistema

- **Archivos PHP**: 25+
- **Líneas de código**: ~3,000
- **Tablas DB**: 6
- **Relaciones**: 8 FK
- **Centros activos**: 5
- **Actividades**: 21
- **Participantes**: 24

---

## 🔍 Casos de Uso Principales

### Administrador de Centro
- Gestionar instalaciones y actividades
- Registrar inscripciones masivas
- Controlar asistencias diarias
- Generar reportes periódicos

### Instructor/Monitor
- Pasar lista de asistencias
- Agregar observaciones de sesión
- Consultar histórico de participantes

### Responsable de Informes
- Extraer datos para análisis
- Generar reportes ejecutivos
- Auditar registros históricos

---

*Documento generado automáticamente por el sistema de análisis de código - Fecha: 2024* 