# Proyecto: Dashboard SPA Dinámico para Sistema Puri

## Resumen Ejecutivo

### Contexto Actual
El sistema Puri actual solo permite gestión desde la perspectiva de centros individuales. Se necesita un sistema de administración centralizado y moderno.

### Propuesta de Mejora - SPA Dinámico
Implementar un dashboard Single Page Application (SPA) con:
- **Navegación AJAX**: Sin recargas de página, contenido dinámico
- **Edición inline**: Click → editar → guardar/cancelar
- **Modales reactivos**: Pop-ups para CRUD con formularios dinámicos
- **Filtros en tiempo real**: Selección de centro → actualiza instalaciones → actividades
- **Roles**: Admin (asignados) y Superadmin (acceso global)

### Estado Actual del Sistema
- ✅ Estructura de horarios actualizada (`dias_semana`, `hora_inicio`, `hora_fin`)
- ✅ Búsqueda y ordenación AJAX en actividades e instalaciones
- ✅ Sistema de autenticación de centros existente
- ✅ Interfaz responsiva implementada
- ✅ JavaScript vanilla ya en uso (base para SPA)

## Plan de Implementación - SPA Dashboard

### Fase 1: Base SPA y Autenticación ✅ COMPLETADA
- [x] Crear tabla `admins` y `admin_asignaciones`
- [x] Login de administradores (/admin/login.php)
- [x] Estructura base SPA: index.html + app.js + router.js
- [x] Sistema de rutas client-side (hash routing)
- [x] API endpoints base (/admin/api/)

### Fase 2: Framework SPA Core - EN PROGRESO
- [x] Sidebar dinámico con navegación AJAX
- [x] Sistema de vistas/componentes (centros, instalaciones, actividades)
- [x] Loader/spinner para transiciones
- [ ] Manejo de estados y cache local
- [ ] Middleware de autorización client-side

### Fase 3: Gestión Dinámica de Centros
- [ ] Vista centros con listado filtrable en tiempo real
- [ ] Edición inline: click en nombre/descripción → input → guardar
- [ ] Modal para crear nuevo centro
- [ ] Confirmación de eliminación con modal
- [ ] Selección de centro → actualiza sidebar con instalaciones

### Fase 4: Gestión Dinámica de Instalaciones
- [ ] Listado reactivo filtrado por centro seleccionado
- [ ] Edición inline de campos de instalación
- [ ] Modal CRUD para instalaciones
- [ ] Drag & drop para reordenar instalaciones
- [ ] Selección de instalación → actualiza actividades

### Fase 5: Gestión Dinámica de Actividades
- [ ] Listado reactivo con filtros múltiples (centro, instalación, estado)
- [ ] Edición inline de horarios estructurados
- [ ] Modal avanzado para crear/editar actividades
- [ ] Upload de CSV con drag & drop y preview
- [ ] Estados visuales (Programadas/Activas/Finalizadas)

### Fase 6: Dashboard y Estadísticas Reactivas
- [ ] Métricas que se actualizan al cambiar selecciones
- [ ] Gráficos dinámicos (Chart.js) que responden a filtros
- [ ] Widgets de estadísticas en tiempo real
- [ ] Descarga de informes filtrados dinámicamente

### Fase 7: Superadmin SPA
- [ ] Vista de gestión de administradores
- [ ] Asignación drag & drop de centros a admins
- [ ] Vista global sin restricciones
- [ ] Panel de logs de actividad en tiempo real

## Current Status / Progress Tracking

**Status**: 🚀 EXECUTOR MODE - Implementando SPA Dashboard
**Current Phase**: Fase 1 COMPLETADA ✅ - Iniciando Fase 2
**Next Action**: Implementar Framework SPA Core (sidebar dinámico y componentes)

### Progreso Actual:
- ✅ **FASE 1 COMPLETADA** - Base SPA y Autenticación
  - Tablas `admins` y `admin_asignaciones` creadas en BD
  - Sistema de login completo con middleware de autorización
  - Login actualizado con estilos y paleta de colores de Puri (Montserrat + #23AAC5)
  - Estructura base SPA con router client-side y sistema de navegación
  - API endpoints base implementados (auth, centros, stats)
  - CSS y JavaScript base para la SPA con estilos consistentes de Puri
- ✅ **FASE 2 CASI COMPLETADA** - Framework SPA Core
  - ✅ Sidebar dinámico con navegación AJAX (ya implementado en HTML/CSS)
  - ✅ Sistema de vistas/componentes implementado
    - `DashboardComponent` - Dashboard principal con métricas y estadísticas
    - `CentrosComponent`, `InstalacionesComponent`, `ActividadesComponent` - Placeholders
    - `EstadisticasComponent`, `SuperadminComponent` - Placeholders
  - ✅ Loader/spinner para transiciones implementado
  - ✅ Estilos CSS completos para dashboard y componentes
- 🔄 **PRÓXIMO**: Completar Fase 2 (cache local y middleware client-side)

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