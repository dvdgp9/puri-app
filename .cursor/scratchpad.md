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

### Progreso reciente (Detalle de Centro)
- Se ajustó el marcado del modal en `admin/center.php` para usar contenedor `div.modal-overlay > div.modal`. Ahora el estado inicial queda oculto vía CSS (`.modal-overlay` con `opacity:0; visibility:hidden`) y se muestra al añadir `.show`.
- JS existente (`openModal('createInstallationModal')`) ahora apunta al overlay con el mismo `id`, alineado con CSS que espera `.modal-overlay.show`.

- Se actualizó `admin/assets/js/center.js::renderStats()` para replicar la estructura del dashboard: `.stat-card` con `.stat-header`, `.stat-title`, `.stat-icon`, `.stat-value`, `.stat-change`. Se usaron iconos y copy del dashboard. También se alineó `showStatsError()` para usar `.error-card`.

### Siguientes pasos
- Verificar apertura del modal desde el botón "+ Nueva Instalación" y desde el empty-state.
- Alinear estructura de tarjetas de stats generadas en `admin/assets/js/center.js::renderStats()` con los selectores de `admin/assets/css/admin.css` (`.stat-header`, `.stat-title`, `.stat-value`, `.stat-icon`).
- Pruebas de interacción y responsive.

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

### ✅ PROBLEMA SOLUCIONADO: Dashboard falló tras limpieza de endpoints

**Problema**: Tras eliminar `test_stats.php` y cambiar al endpoint oficial, el dashboard volvió a fallar
**Causa raíz**: El endpoint oficial usaba `ORDER BY a.created_at DESC` pero la tabla `actividades` no tiene campo `created_at`
**Solución implementada**:
1. ✅ Creado endpoint temporal para comparar diferencias exactas
2. ✅ Identificado error en consulta SQL: `created_at` → `id` 
3. ✅ Corregido endpoint oficial `/admin/api/stats/dashboard.php`
4. ✅ **CONFIRMADO POR USUARIO**: Dashboard funciona perfectamente con endpoint oficial

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

## Background and Motivation (Login Admin roto)

El acceso al panel admin no está funcionando actualmente. El objetivo inmediato es restaurar el login y la persistencia de sesión para que `index.html` cargue la SPA y no redirija de vuelta a `login.php`.

## Key Challenges and Analysis

- Flujo actual de autenticación:
  - Formulario en `admin/login.php` POST → `admin/process_login.php` valida contra tabla `admins` y setea `$_SESSION['admin_*']`, luego `Location: index.html`.
  - La SPA (`admin/index.html`) inicializa `AdminApp` y llama `fetch('check_session.php')` para verificar sesión. Si no hay sesión válida, redirige a `login.php`.
  - Endpoints API bajo `admin/api/**` usan `auth_middleware.php` para forzar autenticación.

- Puntos delicados detectados:
  - Doble bootstrap de la SPA: tanto `admin/index.html` como `admin/assets/js/app.js` inicializan la app; puede generar condiciones de carrera y redirecciones dobles.
  - Sesión PHP: posible no persistencia del cookie de sesión por cambio de host (`localhost` vs `127.0.0.1`) o por abrir `index.html` sin pasar por `login.php`.
  - BD: si la tabla `admins` no existe o no hay registros válidos, `password_verify` siempre falla y se vuelve a `login.php` con `$_SESSION['admin_error']`.
  - Middleware: `auth_middleware.php` hace redirect en endpoints (ok), pero la verificación de la SPA se hace por `check_session.php` (sin middleware), correcto.

- Hipótesis de causa raíz (ordenadas por probabilidad):
  1) Cookie de sesión no llega a `check_session.php` tras el redirect (host/origen distinto o servidor/proxy). 2) Credenciales/tabla `admins` no inicializadas. 3) Condición de carrera por doble init de SPA que provoca redirecciones prematuras. 4) Avisos/errores rompen el JSON de `check_session.php` y el fetch falla.

## High-level Task Breakdown (fix login)

1. Diagnóstico rápido en navegador
   - Éxito: En `Network` ver `check_session.php` con 200 y body `{"authenticated":true,...}` tras login.
   - Si 401/JSON `authenticated:false`: verificar cookie y sesión.

2. Verificar tabla y usuario admin
   - Correr `admin/create_admin.php` para crear `superadmin/admin123` si no existe.
   - Éxito: Usuario creado o detectado existente.

3. Endurecer sesión en login
   - Añadir `session_regenerate_id(true)` al hacer login exitoso.
   - Éxito: Cookie nueva y válida, `check_session.php` devuelve `authenticated:true`.

4. Unificar bootstrap SPA
   - Elegir un único punto de inicialización (sugerido: inline script de `index.html`) y remover el `DOMContentLoaded` duplicado de `app.js`.
   - Éxito: Solo un `init()` en logs, sin redirecciones dobles.

5. Robustecer `check_session.php`
   - Asegurar headers JSON siempre válidos y manejo de errores silencioso (sin HTML/echo antes del JSON).
   - Éxito: `response.json()` no falla aunque haya notices.

6. Pruebas manuales end-to-end
   - Login → Dashboard con stats cargando (`api/stats/dashboard.php` 200, `success:true`).
   - Logout → vuelve a `login.php` y `check_session.php` pasa a `authenticated:false`.

## Project Status Board

- [ ] Diagnóstico en navegador de `check_session.php` tras login
- [ ] Crear/verificar `superadmin` con `create_admin.php`
- [x] Agregar `session_regenerate_id(true)` en `process_login.php`
- [x] Quitar doble inicialización en `app.js`
- [ ] Validar `check_session.php` sin ruidos en JSON
- [ ] Probar login/logout end-to-end
  
### Infra: Acceso /admin
- [x] Crear `admin/index.php` con redirección: si `admin_logged_in` → `dashboard.php`, si no → `login.php` (soluciona 403 en `/admin`)
- [ ] Validar en producción que `https://puri.ebone.es/admin` redirige correctamente (no 403)

### Centro: Correcciones UI/UX (detalle de centro)
- [x] Modal "Nueva Instalación" oculto por defecto y visible con `.modal-overlay.show`
- [ ] Botón "+ Nueva Instalación" abre el modal correctamente en todos los casos
- [x] Tarjetas de estadísticas igualadas al dashboard (estructura/clases)
- [ ] Comportamiento responsive revisado (modal, grid, panel)

## Success Criteria

- Tras credenciales válidas, `admin/index.html` muestra SPA sin redirigir, y `check_session.php` devuelve `authenticated:true` consistentemente.
- Logout destruye la sesión y redirige a `login.php`. Ningún endpoint protegido responde 200 sin sesión.

## Executor's Feedback or Assistance Requests (para el usuario)

- Confirma por favor el dominio/puerto con el que accedes (p. ej. `http://localhost:8080` o `http://127.0.0.1`), y si usas exactamente el mismo host para `login.php` y `index.html`.
- ¿Puedes confirmar si ves mensaje de error en `login.php` después de enviar? Si no aparece, ¿la redirección sucede pero vuelve a `login.php` sola?
- Si quieres que proceda ya con los cambios, indícame si continuamos en modo Executor.

Actualización rápida (2025-08-17 12:23): creado `admin/index.php` para evitar 403 al entrar a `/admin`. ¿Puedes probar en `https://puri.ebone.es/admin` y confirmar si te lleva al login (si no autenticado) o al dashboard (si ya estás autenticado)?

## Lessons

- Preferir un único bootstrap de SPA para evitar condiciones de carrera en autenticación.
- Al autenticar, regenerar el ID de sesión para mitigar fijación de sesión y estabilizar el cookie.