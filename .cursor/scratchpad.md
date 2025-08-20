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

### Progreso reciente (Icono Editar)
- Se reemplazó el icono "Editar" por versión de trazo (outline) para mejorar alineación visual y consistencia:
  - `admin/center.php`: botón en `.center-header-right`
  - `admin/installation.php`: botón en `.center-header-right`
  - Cambio: `fill="currentColor"` → `fill="none"` y `stroke="currentColor"` con `stroke-width` y joins redondeados

### Progreso reciente (Actividad: Participantes)
✅ Creado endpoint `admin/api/participantes/list_by_activity.php` para listar inscritos por actividad, con autorización basada en asignaciones de centro y retorno de contexto (centro, instalación, actividad) para el breadcrumb de la página.
✅ Creada página `admin/activity.php` con breadcrumb, header (Volver, Editar, + Añadir Participantes), panel de participantes con búsqueda/orden y modales (Editar actividad, Añadir participantes con pestañas Manual/CSV).
✅ Añadido script `admin/assets/js/activity.js` que:
  - Carga y renderiza inscritos por actividad (búsqueda y ordenación en cliente).
  - Abre/precarga modal de edición y guarda cambios vía `admin/api/actividades/update.php`.
  - Crea participantes manualmente vía `admin/api/participantes/create.php` fijando `actividad_id`.
  - Sube CSV vía `admin/api/participantes/upload_csv.php` con `actividad_id` fijado.
  - Notificaciones y estados de carga básicos implementados.
✅ Corregido enlace de plantilla CSV a `public/assets/plantilla-asistentes.csv`.

### Progreso reciente (Header perfil)
- Estandarizado el dropdown de perfil del header en todas las páginas admin usando la clase `active` y cierre por clic fuera.
  - Archivos actualizados: `admin/assets/js/center.js`, `admin/assets/js/installation.js`, `admin/assets/js/activity.js`.
  - Soporte CSS ya existente en `admin/assets/css/admin.css` para `.dropdown-content.active`.

### Progreso reciente (Superadmin – Admins)
- ✅ Creado endpoint `admin/api/superadmin/admins/list.php` (solo superadmin) que devuelve `[ { id, username, role, created_at } ]` ordenado por `created_at DESC`, con respuestas JSON `{ success, data, error? }`.
  - ✅ Añadido contenedor del panel `#admins-panel` en `admin/dashboard.php` (oculto por defecto) con búsqueda y ordenación.
  - ✅ JS: incorporado `AdminAPI` (list/create/update/delete), `loadAdmins()` y `renderAdmins()` en `admin/assets/js/dashboard.js`.
  - ✅ Botón "Administradores" ahora abre el panel, carga y renderiza el listado con búsqueda/ordenación.

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
 
### Empleado: desactivar CRUD en frontend
- [x] Ocultar botón "Crear Nueva Instalación" y menú de 3 puntos (editar/borrar) en `instalaciones.php`
- [x] Ocultar botón "Crear Nueva Actividad" y menú de 3 puntos (editar/borrar) en `actividades.php`
- [x] Eliminar funciones JS y modales asociados a edición/borrado en ambas páginas
 - [x] Asistencia: eliminar "+ Añadir Inscrita/o" y UI de borrado (papelera y swipe-to-delete) en `asistencia.php` para impedir modificar participantes; mantener marcaje de asistencia, observaciones y CSV.
 - [x] Asistencia: eliminar barra inferior CSV y función `subirExcel()` en `asistencia.php` para impedir import/export desde esta vista.
  
### Infra: Acceso /admin
- [x] Crear `admin/index.php` con redirección: si `admin_logged_in` → `dashboard.php`, si no → `login.php` (soluciona 403 en `/admin`)
- [ ] Validar en producción que `https://puri.ebone.es/admin` redirige correctamente (no 403)

### UI: Header perfil (dropdown)
- [x] Estandarizar comportamiento del dropdown del perfil en header (center.js, installation.js, activity.js)

### Dashboard: Centros (CRUD)
- [x] Implementar `updateCenter` en `admin/assets/js/dashboard.js` (editar centro desde modal)
- [ ] Validar edición: notificación de éxito, cierre de modal y refresco de listado
- [ ] Probar errores de validación (nombre vacío, nombre duplicado, no autorizado)

### Instalación: UI listado de actividades
- [x] API `admin/api/actividades/list_by_installation.php` devuelve `participantes_count` (subquery sobre `inscritos`)
- [x] UI `admin/assets/js/installation.js`: icono de horario cambiado a reloj + añadido contador de participantes en la línea inferior
- [x] UI `admin/assets/js/installation.js`: icono de días cambiado a calendario consistente con estadísticas
- [ ] Validar visualmente en `installation.php` (días, horario y participantes se ven correctamente en todos los items)

### UI: Botón "Editar" (alineación/icono)
- [x] Reemplazar icono de lápiz por versión outline en `admin/center.php`
- [x] Reemplazar icono de lápiz por versión outline en `admin/installation.php`
- [ ] Validar visualmente alineación del icono respecto al texto en ambos headers (hard refresh para evitar caché)

### Centro: Correcciones UI/UX (detalle de centro)
- [x] Modal "Nueva Instalación" oculto por defecto y visible con `.modal-overlay.show`
- [ ] Botón "+ Nueva Instalación" abre el modal correctamente en todos los casos
- [x] Tarjetas de estadísticas igualadas al dashboard (estructura/clases)
- [ ] Comportamiento responsive revisado (modal, grid, panel)

### Actividad: Página de Participantes
- [x] API `admin/api/participantes/list_by_activity.php` (listar inscritos por actividad con contexto y auth)
- [x] Crear `admin/activity.php` (breadcrumb, header con botón Editar, botón "+ Añadir Participantes", listado)
- [x] Crear `admin/assets/js/activity.js` (carga actividad+inscritos, render, búsqueda/orden)
- [x] Modal "Añadir Participantes": pestañas Manual y CSV con `actividad_id` fijado y campos de contexto bloqueados
- [x] Wire Manual → `admin/api/participantes/create.php`
- [x] Wire CSV → `admin/api/participantes/upload_csv.php`
- [ ] Notificaciones y manejo de errores consistente con centros/instalaciones
- [ ] Pruebas E2E de flujo completo

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

---

## Planner: Superadmin – Gestión de Administradores (nueva vista en Dashboard)

### Background and Motivation
Como superadmin necesito gestionar administradores/superadmins desde el propio Dashboard (sin salir), pudiendo listarlos, crear nuevos, cambiarles el rol y eliminarlos.

### Key Challenges and Analysis
- Seguridad: Solo superadmin puede usar estos endpoints/acciones. Evitar borrar el último superadmin o degradarlo. Evitar que un admin se elimine a sí mismo por accidente.
- Backend: Falta API para CRUD de admins. Seguir patrón por recurso existente (carpetas y ficheros por acción) y usar `requireSuperAdmin()`.
- UI/Integración: Añadir botón "Administradores" en el header del `dashboard.php` visible solo para superadmin. Mostrar un panel dentro del Dashboard (no nueva página) para el listado y acciones.
- Consistencia: Reutilizar estilos de listas y modales ya presentes en el Dashboard; notificaciones/estados de carga homogéneos.

### High-level Task Breakdown
1) Backend API (carpeta nueva `admin/api/superadmin/admins/`):
   - `list.php` (GET): devuelve `[ { id, username, role, created_at } ]` ordenado por `created_at DESC`.
   - `create.php` (POST): valida `username` único, `password` (min 8), `role in {admin, superadmin}`. Inserta y devuelve el admin creado.
   - `update.php` (POST/PUT): cambiar `role` y opcionalmente resetear contraseña. Bloquear degradar el ÚNICO superadmin.
   - `delete.php` (POST/DELETE): borrar admin por `id`. Impedir borrar a sí mismo y el último superadmin.
   - Todas exigen `requireSuperAdmin()` y devuelven JSON `{ success, data?, error? }`.

2) Dashboard UI (en `admin/dashboard.php` + `admin/assets/js/dashboard.js`):
   - Header: botón "Administradores" solo si `isSuperAdmin()` (PHP) y/o chequeo en cliente. Estado activo cuando está visible el panel.
   - Panel Admins dentro del Dashboard: contenedor `#admins-panel` con:
     - Buscador y orden simple por nombre/fecha.
     - Tabla/listado con `username`, `role`, `created_at`, acciones (Editar rol/Reset pass, Eliminar).
     - Botón "+ Añadir" abre modal.
   - Modales:
     - Crear admin: `username`, `password`, `role`.
     - Editar rol/Reset pass: selector de rol, campo de nueva contraseña opcional.
     - Confirmación de eliminación.
   - JS: funciones `loadAdmins()`, `renderAdmins()`, `createAdmin()`, `updateAdmin()`, `deleteAdmin()` consumiendo la nueva API.

3) Reglas adicionales de seguridad UX
   - Deshabilitar acciones peligrosas con tooltips si incumplen reglas (p. ej. "No puedes borrar el último superadmin").
   - Evitar eliminar al usuario autenticado (self-delete) y mostrar aviso.

### Success Criteria
- El botón "Administradores" aparece solo para superadmin en `dashboard.php` y abre un panel con el listado.
- Se puede crear admin (validaciones), cambiar rol y eliminar, con notificaciones de éxito/error.
- Endpoints rechazan accesos de no-superadmin. No se puede eliminar/degradar al último superadmin ni auto-eliminarse.
- Estilos/UX consistentes con el resto del Dashboard.

### Project Status Board (Superadmin – Admins)
- [x] Backend: `admin/api/superadmin/admins/list.php`
- [x] Backend: `admin/api/superadmin/admins/create.php`
- [x] Backend: `admin/api/superadmin/admins/update.php`
- [x] Backend: `admin/api/superadmin/admins/delete.php`
- [x] Dashboard UI: botón "Administradores" (solo superadmin)
- [x] Dashboard UI: contenedor `#admins-panel` y layout de listado
- [x] JS: `loadAdmins()` y `renderAdmins()`
- [x] UI: Modales Crear/Editar Admin en `admin/dashboard.php`
- [x] JS: Crear admin (API `create.php`, validación, loading, notificación)
- [x] JS: Editar admin (rol + reset password opcional, API `update.php`)
- [x] JS: Eliminar admin (confirmación + reglas del backend, notificación)

---

## Planner: Mejora UI/UX filtros en `actividades.php`

### Background and Motivation
Los filtros actuales funcionan pero pueden ser más claros y eficientes. Buscamos mejorar discoverability, reducir fricción en móvil y añadir patrones modernos (chips, sticky, reset) sin romper estilos existentes.

### Key Challenges and Analysis
- Mantener consistencia con `public/assets/css/style.css` (tipografía, paleta, spacing).
- Accesibilidad: roles/aria en chips (toggle), etiquetas visibles, foco claro.
- Rendimiento: filtrar/ordenar client-side con debounce para listas largas.

### High-level Task Breakdown
1) Rediseño de barra de filtros (markup en `actividades.php`)
   - Contenedor `.filters-bar` con `fieldset` + `legend "Filtrar"`.
   - Input búsqueda con icono (`.search-box`).
   - Rango de fechas compacto (`#start-date-from`, `#start-date-to`).
   - Días como chips togglables (`button.chip[data-day]`, `aria-pressed`).
   - Select de orden: Nombre A→Z, Z→A, Fecha ↑, Fecha ↓.
   - Botón "Limpiar filtros" `.btn-outline.btn-sm`.
2) Estilos (en `public/assets/css/style.css`)
   - `.filters-bar` grid responsive, sticky top dentro del contenedor.
   - `.chip` base + `.active`, estados hover/focus, tamaños táctiles.
   - Ajustes de spacing y estados (focus-visible) accesibles.
3) Lógica JS (en `public/assets/js/actividades-search.js`)
   - Debounce de búsqueda (250ms).
   - Soporte chips (buttons) además de checkboxes actuales (compat temporal).
   - Orden por fecha de inicio (asc/desc) además del nombre.
   - Botón Reset que limpia filtros y re-renderiza.
   - Persistencia ligera en `localStorage` (últimos filtros) y restauración on load.
4) QA y Accesibilidad
   - Navegación por teclado (Tab/Shift+Tab), `aria-pressed` en chips.
   - Móvil: touch targets ≥44px, wrap correcto.
   - Performance: probar con 200+ items.

### Success Criteria
- La barra de filtros es clara, compacta y sticky al hacer scroll en `actividades.php`.
- Los chips de días se pueden alternar con ratón y teclado; `aria-pressed` refleja el estado.
- Orden por fecha funciona correctamente usando `data-fecha-inicio` (`YYYY-MM-DD`).
- Botón "Limpiar filtros" restablece todos los controles y oculta el mensaje de "sin resultados".
- Los filtros se recuerdan al recargar (localStorage) y se aplican automáticamente.

### Project Status Board (UI/UX Actividades)
- [ ] Rediseñar markup filtros en `actividades.php`
- [ ] Añadir estilos `.filters-bar` y `.chip` a `style.css`
- [ ] Mejorar `actividades-search.js` (debounce, chips, ordenar por fecha, reset, persistencia)
- [ ] QA accesibilidad/Responsive y performance

### Executor's Feedback or Assistance Requests
- Confirmar si prefieres que los chips de días apliquen condición "cualquiera" (OR, actual) o "todos" (AND).
- Confirmar si el sticky debe quedar bajo el header global y su offset exacto (px).