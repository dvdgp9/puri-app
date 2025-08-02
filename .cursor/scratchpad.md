# Proyecto: Sistema de Gestión en Masa con Modal para Puri

## Background and Motivation

### Contexto Actual
El sistema Puri actual funciona con un flujo lineal:
- Los centros ya existen en BD (creados manualmente)
- Navegación: Centro → Instalaciones (CRUD individual) → Actividades (CRUD individual) → CSV individual por actividad
- Cada creación requiere múltiples páginas y formularios separados

### Propuesta de Mejora
El usuario solicita implementar:
1. **Creación manual de centros por admin** - Nueva funcionalidad admin
2. **Modal "Gestionar Centro"** que permita operaciones en masa:
   - Crear múltiples instalaciones de una vez
   - Crear múltiples actividades por instalación
   - Asignar archivos CSV a cada actividad creada
3. **Experiencia unificada** - Todo en un solo modal intuitivo

### Beneficios Esperados
- **Eficiencia**: Reducir tiempo de setup de nuevos centros de horas a minutos
- **UX**: Interfaz más moderna y fluida con modal responsivo
- **Escalabilidad**: Facilitar la gestión de centros con muchas instalaciones/actividades
- **Administración**: Crear un rol de administrador para gestión global de centros

## Key Challenges and Analysis

### Desafíos Técnicos
1. **Arquitectura Modal**: Implementar modal complejo con múltiples pasos/pestañas
2. **Gestión de Estados**: Mantener estado del modal durante creaciones múltiples
3. **Validación Masiva**: Validar múltiples entidades antes de persistir en BD
4. **Transacciones**: Asegurar consistencia en operaciones masivas (rollback si falla alguna)
5. **Upload Múltiple**: Manejar múltiples archivos CSV y asociarlos correctamente
6. **UI/UX Compleja**: Crear interfaz intuitiva para operaciones complejas
7. **Estructura de Horarios**: Implementar nueva estructura para actividades que se realizan varias veces por semana
8. **Migración de Datos**: Convertir horarios existentes de texto plano a estructura nueva
9. **Compatibilidad**: Mantener funcionalidad existente durante la transición

### Desafíos de Seguridad
1. **Autenticación Admin**: Implementar rol de administrador seguro
2. **Validación de Archivos**: Verificar integridad y formato de múltiples CSVs
3. **Límites de Upload**: Controlar tamaño y cantidad de archivos subidos
4. **CSRF Protection**: Proteger formularios complejos

### Consideraciones de Performance
1. **Procesamiento Asíncrono**: Para operaciones masivas que pueden tomar tiempo
2. **Progress Indicators**: Mostrar progreso en tiempo real
3. **Memoria**: Optimizar para manejar múltiples archivos CSV grandes
4. **Timeouts**: Evitar timeouts en operaciones largas

## High-level Task Breakdown

### Phase 1: Análisis y Diseño (Planner)
1. **Análisis de Viabilidad Técnica**
   - Evaluar compatibilidad con arquitectura actual
   - Identificar dependencias y librerías necesarias
   - Definir especificaciones técnicas detalladas
   - **Success Criteria**: Documento técnico con arquitectura propuesta

2. **Diseño de UX/UI del Modal**
   - Wireframes del modal multi-paso
   - Flujo de usuario para creación masiva
   - Diseño responsive para móvil y desktop
   - **Success Criteria**: Mockups interactivos del modal completo

3. **Diseño de Base de Datos**
   - Esquema para roles de administrador
   - Optimizaciones para operaciones masivas
   - Índices y constraints necesarios
   - **Success Criteria**: DDL scripts y diagrama ER actualizado

4. **Diseño de Estructura de Horarios**
   - Nueva estructura para almacenar horarios de actividades
   - Soporte para múltiples sesiones por semana
   - Estrategia de migración de datos existentes
   - **Success Criteria**: DDL para nuevos campos y script de migración

### Phase 2: Implementación Core (Executor)
4. **Sistema de Administrador**
   - Crear tabla `admins` y sistema de autenticación
   - Implementar páginas de admin para gestión de centros
   - Middleware de autorización para rutas admin
   - **Success Criteria**: Admin puede crear/editar/eliminar centros

5. **Modal Framework Base**
   - Estructura HTML/CSS del modal responsivo
   - Sistema de pasos/tabs navegables
   - Estados de loading y validación
   - **Success Criteria**: Modal vacío funcionando con navegación

6. **API Endpoints para Operaciones Masivas**
   - `POST /api/centros/crear` - Crear centro
   - `POST /api/instalaciones/crear-masivo` - Crear múltiples instalaciones
   - `POST /api/actividades/crear-masivo` - Crear múltiples actividades
   - `POST /api/actividades/upload-csv-masivo` - Upload múltiples CSVs
   - **Success Criteria**: APIs funcionando con validación y manejo de errores

### Phase 3: Funcionalidad del Modal (Executor)
7. **Paso 1: Creación de Instalaciones**
   - Formulario dinámico para agregar/quitar instalaciones
   - Validación en tiempo real
   - Preview de instalaciones a crear
   - **Success Criteria**: Se pueden crear múltiples instalaciones desde el modal

8. **Paso 2: Creación de Actividades**
   - Selector de instalación y formulario de actividades
   - Campos: nombre, horario, fechas inicio/fin
   - Validación de fechas y horarios
   - **Success Criteria**: Se pueden crear múltiples actividades por instalación

9. **Paso 3: Upload de CSVs**
   - Drag & drop para múltiples archivos
   - Asociación archivo-actividad con preview
   - Validación de formato CSV
   - **Success Criteria**: CSVs se asocian correctamente a actividades

### Phase 4: Integración y Testing (Executor)
10. **Integración Completa**
    - Unir todos los pasos en flujo completo
    - Transacciones atomicas para todo el proceso
    - Manejo de errores con rollback completo
    - **Success Criteria**: Flujo completo funciona end-to-end

11. **UI/UX Polish**
    - Animaciones y transiciones suaves
    - Indicadores de progreso detallados
    - Mensajes de éxito/error informativos
    - **Success Criteria**: Experiencia de usuario pulida y profesional

12. **Testing y Bug Fixes**
    - Tests de casos edge (archivos corruptos, timeouts, etc.)
    - Verificación en diferentes dispositivos
    - Performance testing con datos masivos
    - **Success Criteria**: Sistema robusto y sin bugs críticos

## Project Status Board

### Phase 1: Análisis y Diseño
- [ ] **Task 1**: Análisis de Viabilidad Técnica
- [ ] **Task 2**: Diseño de UX/UI del Modal  
- [ ] **Task 3**: Diseño de Base de Datos
- [ ] **Task 4**: Diseño de Estructura de Horarios

### Phase 2: Implementación Core
- [ ] **Task 4**: Sistema de Administrador
- [ ] **Task 5**: Modal Framework Base
- [ ] **Task 6**: API Endpoints para Operaciones Masivas

### Phase 3: Funcionalidad del Modal
- [ ] **Task 7**: Paso 1 - Creación de Instalaciones
- [ ] **Task 8**: Paso 2 - Creación de Actividades  
- [ ] **Task 9**: Paso 3 - Upload de CSVs

### Phase 4: Integración y Testing
- [ ] **Task 10**: Integración Completa
- [ ] **Task 11**: UI/UX Polish
- [ ] **Task 12**: Testing y Bug Fixes

## Current Status / Progress Tracking

**Status**: 📋 PLANNING PHASE - Task 1 Completed ✅
**Current Phase**: Modo Planner - Análisis de viabilidad completado, iniciando diseño UX/UI  
**Next Action**: Task 2 - Diseño de UX/UI del Modal

### Task 1 Results ✅ - Análisis de Viabilidad Técnica:

#### ✅ COMPLETAMENTE VIABLE - Arquitectura Compatible
- **Sistema de Modales Existente**: Ya implementado con `modal-backdrop`, `modal`, transiciones CSS y JavaScript
- **Patrón JavaScript**: Funciones consistentes `showModal()`, `hideModal()`, `showOptionsModal()` reutilizables
- **CSS Framework**: Variables CSS organizadas, sistema responsive, componentes modulares
- **Upload CSV**: `procesar_excel.php` ya maneja CSV con validación, transacciones y rollback

#### ✅ Especificaciones Técnicas Definidas
**Modal Multi-Paso Architecture:**
- **Base**: Extender `.modal-backdrop` y `.modal` existentes

**Estructura de Horarios:**
- **Campos nuevos**: `dias_semana` (SET), `hora_inicio` (TIME), `hora_fin` (TIME)
- **Compatibilidad**: Mantener campo `horario` existente durante transición
- **Migración**: Script para convertir texto plano a estructura nueva
- **UI**: Selectores de días (checkboxes) y horas (time inputs)
- **Navegación**: Sistema de tabs/steps con JavaScript vanilla (consistente con el sistema actual)
- **Estados**: Loading, validation, success/error con indicadores visuales
- **Responsive**: Ya implementado en CSS actual, funciona en móvil y desktop

**Backend APIs Necesarias:**
```php
// Nuevos endpoints para operaciones masivas
POST /api/admin/centros/crear
POST /api/instalaciones/crear-masivo  
POST /api/actividades/crear-masivo
POST /api/upload/csv-multiple
```

**Base de Datos:**
- **Nueva tabla**: `admins` (id, username, password_hash, created_at)
- **Modificar**: Agregar session handling para admin
- **Usar**: Transacciones PDO existentes para atomicidad

#### ✅ Dependencias y Librerías Identificadas
**NO se necesitan librerías adicionales:**
- ✅ **Modal System**: Ya implementado en CSS/JS vanilla
- ✅ **File Upload**: `$_FILES` PHP nativo + validación existente
- ✅ **Form Validation**: Funciones de sanitización ya implementadas
- ✅ **Database**: PDO con prepared statements ya configurado
- ✅ **UI Components**: Sistema de botones, formularios, responsive ya funcional

**Opcional (mejoras futuras):**
- Progress bars con CSS animations
- Drag & drop con HTML5 File API
- AJAX con fetch() API nativo

### Análisis Inicial Completado
- ✅ **Sistema Actual Entendido**: Arquitectura PHP tradicional con MySQL
- ✅ **Flujo Actual Mapeado**: Centro → Instalación → Actividad → CSV

### Diseño de Estructura de Horarios

#### Requisitos
- Soportar actividades que se realizan múltiples veces por semana
- Permitir filtrado y ordenamiento por días y horas
- Mantener compatibilidad con datos existentes
- Facilitar la migración de datos

#### Propuesta de Estructura
```sql
-- Campos adicionales para horarios estructurados
ALTER TABLE actividades 
ADD COLUMN dias_semana SET('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo') NULL,
ADD COLUMN hora_inicio TIME NULL,
ADD COLUMN hora_fin TIME NULL;
```

#### Estrategia de Migración
1. **Fase 1**: Añadir nuevos campos manteniendo el campo `horario` existente
2. **Fase 2**: Crear script de migración para convertir texto a estructura nueva
3. **Fase 3**: Actualizar interfaces para usar nuevos campos
4. **Fase 4**: Opcionalmente, eliminar campo `horario` en versión futura

#### Consideraciones Técnicas
- Usar tipo SET para `dias_semana` para permitir múltiples selecciones
- Usar tipo TIME para `hora_inicio` y `hora_fin` para facilitar operaciones
- Mantener el campo `horario` durante transición para compatibilidad
- Crear funciones para convertir entre formatos

#### UI/UX para Horarios
- **Selector de días**: Checkboxes para seleccionar múltiples días
- **Selectores de hora**: Inputs tipo time para hora inicio/fin
- **Validación**: Asegurar que hora fin > hora inicio
- **Visualización**: Mostrar horario de forma clara en listados
- ✅ **Tecnologías Identificadas**: PHP + PDO, CSS personalizado, JavaScript vanilla
- ✅ **Puntos de Integración**: Modal se integrará en página principal de instalaciones
- ✅ **Viabilidad Confirmada**: 100% compatible con arquitectura actual

## Executor's Feedback or Assistance Requests

### Información Adicional Necesaria del Usuario:
1. **Rol de Admin**: ¿Debe ser un usuario completamente separado o un flag en la tabla de centros?
2. **Límites**: ¿Cuántas instalaciones/actividades máximo se pueden crear de una vez?
3. **Archivos CSV**: ¿Debe validarse el contenido antes de asociar o solo al procesar?
4. **Compatibilidad**: ¿Debe funcionar en algún navegador específico o solo modernos?

### Riesgos Identificados:
1. **Complejidad del Modal**: Puede ser abrumador para usuarios no técnicos
2. **Performance**: Operaciones masivas pueden causar timeouts
3. **Memoria**: Múltiples CSVs grandes pueden exceder límites PHP

## Lessons

### Aprendizajes del Sistema Actual:
- Sistema usa PDO con prepared statements (buena base de seguridad)
- CSS personalizado bien estructurado (fácil de extender)
- JavaScript vanilla con modales existentes (patrón ya establecido)
- Validación robusta en backend (reutilizable para operaciones masivas)

### Lecciones para el Desarrollo:
- Mantener consistencia con patrones existentes del sistema
- Usar las funciones de validación y sanitización ya implementadas
- Aprovechar el sistema de modales existente como base
- Implementar gradualmente para evitar romper funcionalidad actual
- Considerar siempre la compatibilidad hacia atrás en cambios estructurales
- Planificar migraciones de datos como parte integral del desarrollo