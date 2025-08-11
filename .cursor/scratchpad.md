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

### Fase 2: Framework SPA Core ✅ COMPLETADA
- [x] Sidebar dinámico con navegación AJAX
- [x] Sistema de vistas/componentes (centros, instalaciones, actividades)
- [x] Loader/spinner para transiciones
- [x] Manejo de estados y cache local
- [x] Middleware de autorización client-side

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
- ✅ **FASE 2 COMPLETADA** - Framework SPA Core
  - ✅ Sidebar dinámico con navegación AJAX funcional
  - ✅ Sistema de vistas/componentes completamente implementado
    - `DashboardComponent` - Dashboard principal funcional con estadísticas reales
    - `CentrosComponent`, `InstalacionesComponent`, `ActividadesComponent` - Placeholders listos
    - `EstadisticasComponent`, `SuperadminComponent` - Placeholders listos
  - ✅ Loader/spinner para transiciones implementado
  - ✅ Estilos CSS completos para dashboard y componentes con paleta Puri
  - ✅ Manejo de estados y cache local implementado
  - ✅ Middleware de autorización client-side funcional
  - ✅ Sistema de permisos superadmin operativo
- 🎯 **PRÓXIMO**: Fase 3 - Gestión Dinámica de Centros (CRUD completo)

## Executor's Feedback or Assistance Requests

### 🚨 PROBLEMA IDENTIFICADO Y SOLUCIONADO: Loop de redirección en login

**Problema**: El usuario reportó que después del login exitoso, se quedaba en un loop de redirección entre login y dashboard.

**Causa raíz**: El middleware de autenticación (`auth_middleware.php`) se ejecutaba automáticamente y causaba redirecciones conflictivas entre PHP y la SPA.

**Solución implementada**:
1. ✅ Cambiado `process_login.php` para redirigir directamente a `index.html` (SPA)
2. ✅ Creado `check_session.php` - endpoint específico para verificación de sesión sin redirecciones
3. ✅ Actualizado `app.js` para usar el nuevo endpoint de verificación de sesión
4. ✅ Eliminado el conflicto entre middleware PHP y navegación SPA

**Estado**: ✅ SOLUCIONADO - Login funciona correctamente.

### 🔧 PROBLEMA ACTUAL: Dashboard falló tras limpieza de endpoints

**Problema**: Tras eliminar `test_stats.php` y cambiar al endpoint oficial, el dashboard volvió a fallar
**Causa**: El endpoint oficial `/admin/api/stats/dashboard.php` tenía problemas de conexión a BD
**Solución implementada**:
1. ✅ Agregada conexión PDO directa al endpoint oficial
2. ✅ Copiada configuración de BD que funcionaba en el test
3. ✅ Mejorado manejo de errores con mensajes específicos
4. 🔄 **Pendiente**: Confirmar que funciona correctamente

### ✅ PROBLEMAS SOLUCIONADOS PREVIAMENTE:

**Problema 1**: Dashboard no muestra estadísticas (error de carga de datos)
**Causa**: API de estadísticas usa middleware problemático y posibles errores en consultas SQL
**Solución implementada**:
1. ✅ Actualizado `/admin/api/stats/dashboard.php` para evitar middleware problemático
2. ✅ Creado `/admin/api/test_stats.php` - endpoint de debug simplificado
3. ✅ Actualizado `DashboardComponent` para usar endpoint de prueba
4. ✅ **CONFIRMADO POR USUARIO**: Dashboard funciona correctamente con estadísticas reales

**Problema 2**: Error de permisos superadmin
**Causa**: Timing issue en verificación de permisos del router
**Solución implementada**:
1. ✅ Mejorada lógica de verificación de permisos en `router.js`
2. ✅ Verificación más robusta de `window.AdminApp.currentUser`
3. ✅ **CONFIRMADO POR USUARIO**: Acceso a superadmin funciona correctamente

### Información Adicional Necesaria del Usuario:
1. **Rol de Admin**: ¿Debe ser un usuario completamente separado o un flag en la tabla de centros?
2. **Límites**: ¿Cuántas instalaciones/actividades máximo se pueden crear de una vez?
3. **Archivos CSV**: ¿Debe validarse el contenido antes de asociar o solo al procesar?
4. **Compatibilidad**: ¿Debe funcionar en algún navegador específico o solo modernos?

### Riesgos Identificados:
1. **Complejidad del Modal**: Puede ser abrumador para usuarios no técnicos
2. **Performance**: Operaciones masivas pueden causar timeouts
3. **Memoria**: Múltiples CSVs grandes pueden exceder límites PHP