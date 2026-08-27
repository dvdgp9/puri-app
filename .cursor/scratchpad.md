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

## Mejora de Informes (2026-01-16)

### Background and Motivation
Un compañero ha reportado que al descargar informes no se puede identificar a qué grupo horario pertenecen los documentos. Además, se solicita que los nombres de los archivos descargados sean más "amigables" y descriptivos.

### Key Challenges and Analysis
- La tabla `actividades` tiene un campo `grupo` que no se está utilizando en la generación de informes ni en el nombre del archivo.
- Los nombres de archivo actuales son genéricos (`informe_centro_instalacion_fecha.xls`).
- Es necesario incluir el campo `grupo` en las consultas SQL de `generar_informe.php` y `admin/api/informes/generar.php`.
- El nombre del archivo debe incluir el nombre de la actividad y el grupo para ser "amigable".

### High-level Task Breakdown
1. **Backend (Admin API)**: Actualizar `admin/api/informes/generar.php` para incluir `grupo` en la consulta y en el nombre del archivo.
2. **Backend (Legacy/Direct)**: Actualizar `generar_informe.php` para incluir `grupo` en la consulta y en el nombre del archivo.
3. **Frontend (Helpers)**: Asegurar que `obtener_actividades.php` devuelva el grupo para que se pueda mostrar en los selectores si es necesario.
4. **Frontend (UI)**: Verificar si `informes.php` necesita mostrar el grupo en el selector de actividades.

### Success Criteria
- El archivo descargado tiene un nombre descriptivo: `Informe_[Centro]_[Actividad]_[Grupo]_[Fecha].xls`.
- El contenido del informe muestra claramente el grupo al que pertenece la actividad.
- Los nombres de los archivos no contienen caracteres extraños (sanitizados).

### Project Status Board (Mejora de Informes)
- [x] Actualizar consulta SQL y nombre de archivo en `admin/api/informes/generar.php`
- [x] Actualizar consulta SQL y nombre de archivo en `generar_informe.php`
- [x] Actualizar `obtener_actividades.php` para incluir campo `grupo`
- [x] Validar que el informe PDF/Excel muestra el grupo en la cabecera
- [x] Mostrar grupo en el selector de actividades de `informes.php`


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
- [x] Añadir campo de contraseña en el modal de edición de centros (`admin/dashboard.php`)
- [x] Actualizar lógica JS para enviar la nueva contraseña si se proporciona (`admin/assets/js/dashboard.js`)
- [x] Actualizar API para procesar el hash de la nueva contraseña (`admin/api/centros/update.php`)
- [ ] Validar edición de centro con cambio de contraseña exitoso
- [ ] Validar que si se deja la contraseña vacía, no se modifica la actual

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

### Estadísticas Mejoradas UX (2026-01-13)
- [x] **Dashboard**: Añadir total de inscritos por centro (`admin/api/centros/list_new.php` + `dashboard.js`)
- [x] **Center.php**: Añadir inscritos y asistencias totales por instalación (`admin/api/instalaciones/list_by_center.php` + `center.js`)
- [x] **Installation.php**: Añadir días con paso de lista (últimos 28 días) por actividad (`admin/api/actividades/list_by_installation.php` + `installation.js`)
- [x] **Activity.php**: Añadir nº y % de asistencias por participante (últimos 28 días) (`admin/api/participantes/list_by_activity.php` + `activity.js`)
- [ ] Validar visualmente todas las estadísticas en cada nivel de la jerarquía
- [x] Modal "Añadir Participantes": pestañas Manual y CSV con `actividad_id` fijado y campos de contexto bloqueados
- [x] Wire Manual → `admin/api/participantes/create.php`
- [x] Wire CSV → `admin/api/participantes/upload_csv.php`
- [x] Acción "Eliminar participante" en desplegable (confirmación), endpoint `admin/api/participantes/delete.php` y refresco del listado
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
- Bug informes en blanco (2026-01-21): En `admin/api/informes/generar.php` faltaba la consulta SQL a la tabla `asistencias`. Se añadió el bloque que obtiene y organiza `$asistencias_por_usuario` igual que en `generar_informe.php`.

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

### Project Status Board (Subida en Lote - Bulk Import)
- [x] Crear endpoint `admin/api/bulk_import.php` (procesa instalaciones, actividades, participantes)
- [x] Añadir botón "Subida en Lote" en modal de opciones de añadir
- [x] Crear modal `#bulkImportModal` con selector de centro y tabla editable
- [x] Implementar JS para parsear pegado desde Excel (columnas: Nombre, Apellidos, Centro[ignorar], Instalación, Actividad, Fecha, Días)
- [x] Estilos CSS para bulk import (.info-box, .bulk-table-wrapper, .bulk-import-errors, etc.)
- [ ] Probar flujo completo con datos reales del usuario
- [ ] Validar manejo de errores (participante sin apellidos, actividad duplicada con mismos días)

### Executor's Feedback or Assistance Requests
- Confirmar si prefieres que los chips de días apliquen condición "cualquiera" (OR, actual) o "todos" (AND).
- Confirmar si el sticky debe quedar bajo el header global y su offset exacto (px).

---

## Planner: Flujo trabajadores — Deep links y Favoritos (hasta 3)

### Background and Motivation
Trabajadores repiten muchos pasos para pasar lista. Objetivo: reducir fricción diaria sin comprometer seguridad. Mantener el estilo actual (tipografía, botones, dropdowns, toasts) ya usado en las vistas recientes.

### Key Decisions
- Deep link con auth requerida: `asistencia.php?centro={id}&instalacion={id}&actividad={id}`
  - Con sesión válida del centro → ir directo a la asistencia de esa actividad (saltando selectores).
  - Sin sesión → pedir contraseña del centro y, tras login, redirigir de vuelta al deep link (y luego a asistencia).
  - No se usa token en esta fase; el enlace solo pre-rellena/dirige, no salta auth.

- Favoritos por dispositivo (hasta 3):
  - UI: estrella (outline → filled) para marcar/desmarcar en:
    - Listado previo de actividades.
    - Encabezado de `asistencia.php` (participantes) para la actividad abierta.
  - Almacenamiento: `localStorage` clave `puri.favs.v1` con array limitado a 3 entradas `{centro_id, instalacion_id, actividad_id, nombre}`.
  - Home/portada: sección “Favoritos” con hasta 3 botones grandes “Continuar” (deep links). Opción “Gestionar” para quitar.

- Consistencia visual:
  - Icono estrella SVG (outline/filled) coherente con iconografía actual.
  - Botones primarios/secundarios existentes; toasts ya usados para feedback.

- QR/Compartir (opcional Fase 2):
  - Botón “Copiar enlace” y “Mostrar QR” (cliente) para colocar en sala.
  - El QR apunta al deep link sin token; mantiene requisito de contraseña si no hay sesión.

### High-level Task Breakdown (Plan)
1) Deep link routing (sin token)
   - `asistencia.php`: leer `centro/instalacion/actividad` por GET, validar y navegar directo a la actividad si hay sesión; si no, guardar `return_to` y pedir login.
   - Tras login del centro: redirigir a `return_to` y completar flujo.

2) Favoritos (máx. 3)
   - JS util `favorites` (add/remove/list, enforce limit 3, desduplicar por `{centro,instalacion,actividad}`).
   - Estrella en listado de actividades y en `asistencia.php` header.
   - Portada: render de sección “Favoritos” (orden: más reciente primero) → deep link.
   - Estados: si una actividad ya no existe/ya no está activa, mostrar opción para quitar.

3) QR/Compartir (Fase 2)
   - Botón copiar URL + modal QR (lib ligera cliente) desde `asistencia.php` y/o listado de actividades.

4) Copy y mensajes
   - "Añadir a favoritos"/"Quitar de favoritos".
   - "Continuar" en tarjetas de favoritos.
   - Avisos breves si una actividad favorita dejó de estar disponible.

### Success Criteria
- Abrir un deep link con sesión válida entra directo a la asistencia de esa actividad; sin sesión, pide contraseña y regresa automáticamente.
- El usuario puede marcar hasta 3 favoritos; aparecen en portada como accesos directos con 1 toque.
- Estrella visible y coherente con el estilo; feedback por toast al añadir/quitar.
- En caso de actividad inválida, el sistema no rompe y permite limpiar el favorito.

### Open Questions
- ¿El límite de 3 favoritos es global por dispositivo o por centro? (Propuesta: global por dispositivo)
- ¿Mostramos favoritos solo en la portada o también como bloque superior en el listado de actividades?

---

## Planner: Control de Aforo — Nueva Funcionalidad (2026-02-04)

### Background and Motivation
Existen actividades que no requieren control de asistencia nominal (lista de inscritos), sino un simple registro de aforo: cuántas personas hay en un momento dado. El monitor entra, indica día/hora y registra el número de personas presentes.

**Ejemplo de uso**: Piscina libre, sala de musculación abierta, actividades drop-in sin inscripción previa.

### Key Challenges and Analysis

#### 1. Modelo de datos
- **Tabla `actividades`**: Añadir campo `tipo_control` ENUM('asistencia', 'aforo') DEFAULT 'asistencia'
- **Nueva tabla `registros_aforo`**: Para almacenar los registros de aforo
  ```sql
  CREATE TABLE registros_aforo (
      id INT AUTO_INCREMENT PRIMARY KEY,
      actividad_id INT NOT NULL,
      fecha DATE NOT NULL,
      hora TIME NOT NULL,
      num_personas INT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE,
      INDEX idx_actividad_fecha (actividad_id, fecha)
  );
  ```

#### 2. Flujo del trabajador (vista pública)
- Al entrar a una actividad de tipo "aforo", en lugar de ver lista de inscritos, ver:
  - Selector de fecha (autoseleccionado: hoy)
  - Selector de hora (autoseleccionado: hora actual redondeada al cuarto de hora más cercano)
  - Input numérico para "Número de personas"
  - Botón "Registrar"
  - Histórico del día actual (registros previos)

#### 3. Formularios de creación/edición de actividades
Archivos afectados:
- `crear_actividad.php` — Añadir toggle/select "Tipo de control"
- `editar_actividad.php` — Añadir toggle/select "Tipo de control"
- `admin/installation.php` — Modal crear/editar actividad
- `admin/dashboard.php` — Modal crear actividad (si existe)
- `admin/api/actividades/create.php` — Procesar campo `tipo_control`
- `admin/api/actividades/update.php` — Procesar campo `tipo_control`
- `admin/api/bulk_import.php` — Añadir columna opcional "Tipo" (asistencia/aforo)

#### 4. Vista de asistencia/aforo condicional
- `asistencia.php`: Detectar `tipo_control` de la actividad
  - Si "asistencia" → flujo actual (lista de inscritos)
  - Si "aforo" → nueva UI de registro de aforo

#### 5. Informes
- `admin/api/informes/generar.php`: Lógica condicional
  - Asistencia: formato actual (matriz usuario × fecha con X/vacío)
  - Aforo: formato diferente (fecha | hora | nº personas) o resumen diario

#### 6. Elementos adicionales a revisar
- **Listado de actividades** (`actividades.php`, `admin/installation.php`): Mostrar badge/icono indicando tipo
- **API de actividades** (`admin/api/actividades/list*.php`): Incluir campo `tipo_control` en respuesta
- **Estadísticas**: ¿Contar registros de aforo de forma diferente? (p.ej. promedio diario vs asistencias totales)
- **Inscritos**: Las actividades de aforo NO tienen inscritos (tabla `inscritos` no aplica)
- **Validaciones**: Impedir añadir participantes a actividades de tipo aforo

### High-level Task Breakdown

#### Fase 1: Base de datos
1. Crear migración SQL para:
   - Añadir columna `tipo_control` a `actividades`
   - Crear tabla `registros_aforo`
2. Ejecutar migración

#### Fase 2: Backend — APIs de actividades
3. Actualizar `admin/api/actividades/create.php` para aceptar `tipo_control`
4. Actualizar `admin/api/actividades/update.php` para aceptar `tipo_control`
5. Actualizar endpoints de listado para devolver `tipo_control`
6. Actualizar `admin/api/bulk_import.php` para soportar columna "Tipo"

#### Fase 3: Backend — API de aforo
7. Crear endpoint `api/aforo/registrar.php` (POST: actividad_id, fecha, hora, num_personas)
8. Crear endpoint `api/aforo/listar.php` (GET: actividad_id, fecha → registros del día)

#### Fase 4: Frontend — Formularios de actividad
9. Actualizar `crear_actividad.php` con selector de tipo
10. Actualizar `editar_actividad.php` con selector de tipo
11. Actualizar modal en `admin/installation.php` (crear y editar)
12. Actualizar modal en `admin/dashboard.php` si aplica

#### Fase 5: Frontend — Vista de aforo
13. Modificar `asistencia.php` para detectar tipo y mostrar UI correspondiente
14. Implementar UI de registro de aforo (fecha, hora, número, histórico)
15. Añadir JS para envío y recarga de registros

#### Fase 6: Informes
16. Modificar `admin/api/informes/generar.php` para generar informe de aforo
17. Ajustar UI de informes si es necesario (filtrar por tipo o indicar tipo)

#### Fase 7: UX y pulido
18. Añadir indicador visual de tipo en listados de actividades
19. Bloquear acceso a "Añadir participantes" en actividades de aforo
20. Pruebas E2E del flujo completo

### Success Criteria
- Actividades pueden ser de tipo "asistencia" (default) o "aforo"
- El monitor puede registrar aforo (día, hora, nº personas) de forma rápida
- Los informes de aforo muestran los registros con fecha, hora y número
- La UI indica claramente el tipo de actividad
- No se pueden añadir inscritos a actividades de aforo
- Importación en lote soporta el nuevo tipo

### Project Status Board (Control de Aforo)
- [ ] Migración SQL (campo + tabla)
- [ ] API create/update actividades con `tipo_control`
- [ ] API listado actividades incluye `tipo_control`
- [ ] API bulk_import soporta tipo
- [ ] API aforo (registrar + listar)
- [ ] Frontend: formularios de actividad (crear/editar) con tipo
- [ ] Frontend: `asistencia.php` condicional (asistencia vs aforo)
- [ ] Frontend: UI de registro de aforo
- [ ] Informes: generación condicional por tipo
- [ ] UX: badges/iconos de tipo en listados
- [ ] Validaciones: bloquear inscritos en aforo
- [ ] Pruebas E2E

### Decisiones del Usuario (2026-02-04)
1. **Formato del informe de aforo**: Registros independientes (fecha | hora | nº) para ver aforo por sesión
2. **Histórico en la vista de aforo**: Usar criterio del desarrollador (se muestra día actual con opción de cambiar fecha)
3. **Edición/borrado de registros**: Solo admin puede editar/borrar, no el monitor
4. **Estadísticas dashboard**: Total acumulado diario

### Implementación Completada (2026-02-04)
- [x] Migración SQL: `migrate_control_aforo.sql` (campo `tipo_control` + tabla `registros_aforo`)
- [x] APIs actividades: create/update/list con `tipo_control`
- [x] APIs aforo: `api/aforo/registrar.php`, `api/aforo/listar.php`
- [x] Formularios: selector tipo en `admin/installation.php` (crear/editar modales)
- [x] Vista trabajador: `asistencia.php` con UI condicional (asistencia vs aforo)
- [x] Informes: `admin/api/informes/generar.php` con formato diferente para aforo
- [x] UX: badges "Aforo" en listados de actividades
- [x] CSS: estilos para UI aforo y badges

---

## Planner: Evaluaciones por actividad y correo de observaciones (2026-08-12)

### Background and Motivation

Se solicitan dos ampliaciones de Puri:

1. Registrar evaluaciones de las personas inscritas en una actividad. Cada evaluación pertenece a una actividad y tiene un período de disponibilidad, mide inicialmente una sola variable (burpees, vueltas, tiempo, distancia, etc.) y debe poder realizarse desde la vista operativa de la clase en una fecha elegida por el monitor dentro de ese período. La planificación y edición de la evaluación se realizará siempre desde Admin.
2. Enviar por correo todas las observaciones no vacías registradas al pasar lista a las personas administradoras/coordinadoras asignadas al centro de la actividad.

La prioridad es evitar que funcionalidades de uso puntual saturen una interfaz corporativa que ya contiene asistencia, aforo, participantes, informes y administración. La solución debe aplicar revelado progresivo: configuración solo en el contexto de la actividad dentro de Admin y una sección operativa secundaria que solo aparezca al monitor cuando la actividad tenga evaluaciones pendientes o en curso.

### Confirmed Requirements

- Una evaluación mide una sola variable en la primera versión, pero el modelo de datos no debe bloquear varias variables futuras.
- Admin define la evaluación, su período de disponibilidad y su configuración; no fija obligatoriamente el día concreto de realización.
- Una actividad puede tener distintas evaluaciones y períodos que se solapen.
- El monitor elige y registra la fecha real dentro del período cuando encuentra una clase adecuada, y rellena el resultado de cada participante.
- El monitor puede corregir resultados mientras la realización está en curso. Una vez finalizada, las correcciones posteriores y los cambios de definición se hacen desde Admin.
- Todas las observaciones no vacías deben generar correo para las personas asignadas al centro en Admin.
- El correo contendrá la observación completa y el contexto de la actividad. La entrega se hará mediante una cuenta SMTP cuyos datos se facilitarán más adelante.
- Admin incorporará un email para cada persona coordinadora destinataria.
- No se realizará investigación web para esta planificación, por indicación expresa del usuario.

### Evidence from the Example Workbooks

- `EVALUACIONES HIIT.xlsx` contiene 4 hojas con la misma estructura: participantes en filas, tres campañas (octubre, enero y mayo) y cinco pruebas por campaña.
- Las cinco pruebas HIIT son: burpees en un minuto, flexibilidad de tronco, flexiones en un minuto, zancadas en un minuto y circuito de agilidad/coordinación.
- `EVALUACIÓN 70 PLUS.xlsx` contiene una plantilla y 32 grupos con la misma estructura: participantes en filas, las mismas tres campañas y seis pruebas por campaña.
- Las seis pruebas 70 Plus son: sentadillas en 30 segundos, fondos en pared en 30 segundos, equilibrio a una pierna, vueltas caminando en seis minutos, tiempo de recorrido botando balón y equilibrio en tándem.
- Los ejemplos combinan recuentos, distancia y duración; además incluyen un resultado acotado como «más de 30 segundos». Por ello, un único campo numérico sin tipo ni unidad sería insuficiente.
- Los Excel son matrices comparativas, pero la primera necesidad expresada es registrar resultados. Comparativas, gráficas e importación/exportación quedan fuera del MVP para no añadir complejidad antes de validar el uso real.

### Key Challenges and Analysis

#### 1. Encaje UX sin aumentar el ruido global

- No añadir una nueva opción permanente al menú principal, tarjetas en el dashboard ni controles de evaluación en actividades sin evaluaciones.
- En `admin/activity.php`, incorporar navegación local compacta entre `Participantes` y `Evaluaciones`. El botón `Nueva evaluación` solo aparece dentro de la segunda vista.
- En la vista de monitor (`asistencia.php`), mostrar una sección secundaria y neutra `Evaluaciones` únicamente si la actividad tiene evaluaciones pendientes o en curso. No se vincula a la sesión ni a la fecha de asistencia actualmente seleccionada.
- La sección muestra filas compactas con nombre, estado y ventana temporal: `Pendiente · Burpees en 1 min · Del 1 al 31 de octubre`. La acción contextual será `Realizar` o `Continuar`.
- Abrir una vista de captura dedicada para que la tabla de resultados no compita visualmente con el pase de lista. Al comenzar, pedir `Fecha de realización`, proponiendo hoy y restringiendo el valor al período permitido y a fechas no futuras.
- Tras finalizar, mover la evaluación al histórico en modo lectura para el monitor; Admin conserva edición y corrección.
- Usar lista con separadores y jerarquía tipográfica en lugar de una cuadrícula de tarjetas. Mantener una sola acción primaria por contexto.

#### 2. Captura rápida y segura para el monitor

- Mostrar nombre del participante, campo de resultado y unidad visible (`repeticiones`, `vueltas`, `cm`, `segundos`, etc.).
- Admitir inicialmente tipos `entero`, `decimal`, `duración` y `texto corto`; el Admin elige el tipo y la unidad al crear la evaluación.
- Validar en la misma fila y proporcionar estados claros de guardando, guardado y error. La interacción debe funcionar con teclado y móvil, con objetivos táctiles mínimos de 44 px.
- Incluir progreso discreto (`12 de 20 registrados`) y filtros `Todos` / `Pendientes`; no añadir rankings, colores de rendimiento ni comparaciones en esta fase.
- Mantener `Sin evaluar` como estado válido para no confundir un dato ausente con un cero.

#### 3. Diferenciar configuración, registro y corrección

- Admin crea/edita la evaluación, período, instrucciones, formato y unidad.
- El monitor inicia la realización desde la clase, selecciona la fecha real y registra resultados.
- Mientras el estado sea `en curso`, el monitor puede guardar y corregir por participante. Al pulsar `Finalizar evaluación`, la vista queda bloqueada para el monitor y solo Admin puede reabrir o corregir.
- Si quedan participantes sin evaluar, `Finalizar` mostrará el número pendiente y pedirá confirmación; `Sin evaluar` seguirá siendo un resultado válido.
- Estados propuestos: `programada` (aún no abre el período), `pendiente`, `en curso`, `finalizada` y `fuera de plazo`. Una evaluación en curso que alcance el fin del período no se pierde: queda visible, pero requiere extensión/reapertura desde Admin para seguir editando.

#### 4. Modelo extensible sin sobreconstruir la primera interfaz

Modelo propuesto:

- `evaluaciones`: planificación con `actividad_id`, `fecha_inicio`, `fecha_fin`, `nombre`, `instrucciones`, marcas de auditoría y archivado opcional.
- `evaluacion_campos`: uno o más campos pertenecientes a una evaluación, con `nombre`, `tipo_dato`, `unidad`, `orden` y configuración opcional. La UI inicial crea exactamente un campo, pero no será necesaria una migración para admitir más en el futuro.
- `evaluacion_sesiones`: realización efectiva de una evaluación, con `evaluacion_id`, `fecha_realizacion`, estado `en_curso/finalizada` y marcas de inicio/finalización. Esta separación evita convertir la fecha planificada en una fecha ficticia y permite repeticiones futuras sin rediseñar el esquema.
- `evaluacion_resultados`: un resultado por `evaluacion_sesion_id`, `evaluacion_campo_id` e `inscrito_id`, con valor numérico o textual, estado `sin_evaluar/medido` y marcas de auditoría. La combinación sesión-campo-participante debe ser única para que el guardado sea idempotente.

Decisiones de integridad:

- No imponer unicidad entre períodos porque varias evaluaciones de una actividad pueden estar disponibles a la vez.
- Para el MVP, la propuesta es una realización por cada evaluación planificada; repetir la misma prueba en octubre, enero y mayo supone tres planificaciones, que Admin puede crear duplicando la anterior. La tabla de sesiones deja abierta la repetición futura.
- Validar que `fecha_realizacion` esté dentro del período y no sea futura. Admin puede corregirla posteriormente con auditoría.
- Verificar siempre que participante, evaluación y actividad pertenecen al mismo centro de la sesión/autorización.
- Si una evaluación ya tiene resultados, preferir archivar frente a eliminar; una eliminación definitiva debe estar protegida en Admin.
- Para duraciones, guardar un valor numérico normalizado en segundos y formatearlo en la interfaz. Valores especiales como `>30 s` requieren un calificador opcional o una representación textual controlada, no un número ambiguo.

#### 5. Correos de observaciones y deduplicación

Estado actual comprobado:

- `registrar_asistencia.php` guarda o actualiza una única observación por actividad y fecha.
- `admins` no dispone de campo `email`.
- `admin_asignaciones` relaciona administradores con centros; esta relación es la fuente de destinatarios.
- El proyecto no contiene actualmente librería de correo ni configuración SMTP documentada.

Comportamiento propuesto:

- Añadir `email` a las cuentas Admin y a sus formularios/API de creación y edición.
- Considerar destinatarios únicamente a los admins asignados explícitamente al centro y con correo válido; deduplicar direcciones. Un superadmin no debe recibir correos de todos los centros solo por su rol.
- Disparar notificación al crear una observación no vacía o cuando cambia su contenido normalizado. Reenviar el formulario con el mismo texto no debe volver a notificar. Vaciar una observación no genera correo.
- Resolver centro, instalación, actividad y fecha en servidor. Nunca aceptar destinatarios procedentes del formulario del monitor.
- El correo incluirá la observación completa y el contexto disponible: centro, instalación, actividad, grupo, días/horario, fecha de la observación y momento de registro. El sistema actual no identifica a un monitor individual porque la sesión pertenece al centro; no se inventará ese dato.
- El fallo de correo nunca debe deshacer asistencia u observación ya guardadas. Registrar un evento de salida con destinatario, hash de contenido, estado, intentos y último error para deduplicación, diagnóstico y reintento.
- Los logs técnicos deben incluir IDs, centro y número de destinatarios, pero no el texto de la observación ni datos personales.

### UX Flow Proposed for the MVP

1. En Admin, entrar en una actividad y cambiar de `Participantes` a `Evaluaciones`.
2. Crear la evaluación indicando período disponible, nombre, instrucciones, formato y unidad. La primera versión presenta un solo campo.
3. Durante ese período, la vista de clase muestra un bloque secundario `Evaluaciones` con las tareas pendientes o en curso de la actividad.
4. El monitor pulsa `Realizar`, confirma la fecha efectiva y accede a la lista de participantes.
5. Los valores se guardan por fila; puede salir y continuar mientras la evaluación esté en curso.
6. Al finalizar, el monitor confirma los pendientes y la evaluación pasa a lectura. Admin ve fecha real, tipo, unidad, cobertura (`registrados/participantes`) y puede corregir o reabrir.

### Deliberate Non-Goals for the MVP

- No añadir widgets de evaluación al dashboard ni una entrada global al menú.
- No crear rankings, puntuaciones automáticas, colores de “bueno/malo” o comparativas entre participantes.
- No añadir gráficas, comparativas entre campañas, exportación Excel ni importación masiva hasta validar el flujo básico.
- No mostrar controles de creación/configuración en la vista del monitor.
- No introducir estilos inline nuevos; todo CSS nuevo irá a `public/assets/css/style.css` o `admin/assets/css/admin.css`, según la superficie.

### High-level Task Breakdown

#### Milestone 0 — Cerrar decisiones funcionales

1. Política de corrección confirmada: el monitor edita mientras está en curso; tras finalizar, Admin corrige o reabre.
   - Éxito: decisión cerrada por el usuario.
2. Contenido confirmado: observación completa y toda la información disponible de la actividad.
   - Éxito: decisión cerrada por el usuario.
3. Transporte confirmado como SMTP; quedan pendientes las credenciales/remitente antes del Milestone 5.
   - Éxito: la implementación de evaluaciones no queda bloqueada; la entrega de correo no se activa sin configuración real.
4. Confirmar la regla propuesta de una realización por evaluación planificada dentro del período.
   - Éxito: decisión confirmada por el usuario; cada planificación se completa una sola vez. Para repetir una prueba, Admin duplica la evaluación y define otro período.
5. Recopilar y cargar el correo de cada cuenta coordinadora asignada antes de la prueba de entrega.
   - Éxito: todas las personas que deban recibir notificaciones tienen un email válido en Admin.

#### Milestone 1 — Contrato y migración de evaluaciones

6. Escribir primero pruebas de esquema, autorización y validación para evaluaciones.
   - Éxito: las pruebas fallan por ausencia de tablas/endpoints y cubren actividad/centro incorrectos, tipos de dato y duplicados.
7. Crear una migración reversible para `evaluaciones`, `evaluacion_campos`, `evaluacion_sesiones` y `evaluacion_resultados`.
   - Éxito: se aplica en un entorno no productivo, respeta claves foráneas/índices y puede revertirse sin tocar datos existentes.
8. Documentar el contrato en `docs/api/evaluaciones.md` antes de conectar la UI.
   - Éxito: entradas, respuestas, errores, permisos y ejemplos quedan documentados.

#### Milestone 2 — API Admin de evaluaciones

9. Implementar listado y detalle por actividad con control de asignación al centro.
   - Éxito: admin asignado y superadmin acceden; admin no asignado recibe 403.
10. Implementar creación y edición de evaluación/campo único.
   - Éxito: período, nombre, tipo y unidad se validan; no se puede cambiar una actividad fuera de alcance.
11. Implementar archivado, estados y lectura/edición/reapertura de sesiones y resultados desde Admin.
   - Éxito: una evaluación con resultados no se elimina accidentalmente y Admin puede corregir valores con auditoría.

#### Milestone 3 — UI Admin contextual

12. Añadir navegación local `Participantes | Evaluaciones` a `admin/activity.php`.
    - Éxito: la vista por defecto no gana controles adicionales salvo la navegación local y conserva responsive.
13. Construir listado por estado/período con estados vacío, carga, error y cobertura.
    - Éxito: pendientes y en curso visibles primero, finalizadas en histórico y una sola acción primaria.
14. Construir modal de crear/editar y vista de resultados.
    - Éxito: formulario con etiquetas, ayuda y errores inline; soporta el campo único sin exponer aún campos múltiples.

#### Milestone 4 — API operativa y captura del monitor

15. Escribir primero pruebas para consulta de evaluaciones disponibles, inicio con fecha real, guardado idempotente y finalización.
    - Éxito: cubren sesión de centro, período, fecha futura/fuera de rango, segunda realización, participante ajeno, cero válido, vacío y tipos de dato.
16. Implementar endpoints de listado disponible, inicio/continuación, guardado y finalización.
    - Éxito: solo la sesión del centro correcto puede operar; `Continuar` recupera la misma sesión y una finalizada queda bloqueada para el monitor.
17. Añadir la sección contextual `Evaluaciones` en `asistencia.php` y la vista de captura dedicada.
    - Éxito: actividades sin evaluaciones pendientes/en curso permanecen visualmente iguales; con evaluaciones se accede sin asociarlas forzosamente a la fecha de asistencia.
18. Implementar fecha real, estados por fila, progreso, pendientes, finalizar y comportamiento móvil/teclado.
    - Éxito: el monitor puede completar o continuar una lista, corregir antes de finalizar y no perder valores por un fallo aislado.

#### Milestone 5 — Destinatarios y entrega de observaciones

19. Escribir primero pruebas de deduplicación, destinatarios y fallo de transporte.
    - Éxito: vacío o texto sin cambios no envía; cambio real envía una vez por email distinto; un fallo no revierte la asistencia.
20. Crear migración para `admins.email` y registro/outbox de notificaciones.
    - Éxito: migración reversible, email inicialmente nullable y restricción/índices de deduplicación definidos.
21. Actualizar Admin para crear, editar, listar y validar el email de coordinadores.
    - Éxito: el superadmin ve si una cuenta asignada carece de email y puede corregirlo.
22. Implementar servicio de notificación desacoplado del guardado de asistencia.
    - Éxito: destinatarios derivados en servidor, entrega registrada, reintento posible y logging sin contenido sensible.
23. Integrar el disparo por creación/cambio en `registrar_asistencia.php`.
    - Éxito: guardar asistencia/observación sigue funcionando aunque el transporte de correo falle.
24. Documentar el contrato y la configuración en `docs/api/notificaciones-observaciones.md`.
    - Éxito: remitente, variables de entorno, estados, deduplicación, reintentos y diagnóstico quedan documentados.

#### Milestone 6 — QA transversal

25. Ejecutar pruebas automatizadas y comprobaciones de regresión de asistencia/aforo.
    - Éxito: flujos existentes sin evaluaciones siguen funcionando y no aparecen errores PHP/SQL.
26. QA visual en móvil y escritorio, teclado y estados vacíos/carga/error.
    - Éxito: no hay desbordamiento horizontal, los objetivos táctiles son de al menos 44 px y el foco es visible.
27. Prueba controlada de correo con dos coordinadores asignados, uno repetido y un fallo simulado.
    - Éxito: entrega/deduplicación coinciden con el log y ningún dato de asistencia se pierde.

### Success Criteria

- Una actividad sin evaluaciones conserva exactamente el flujo visual actual para el monitor.
- Admin puede crear y editar una evaluación de una variable para una actividad y período, con tipo y unidad explícitos.
- El monitor del centro correcto puede elegir una fecha válida dentro del período y registrar `Sin evaluar`, cero y valores válidos para cada participante.
- El monitor puede continuar y corregir mientras está en curso; una evaluación finalizada queda bloqueada para él y corregible/reabrible desde Admin.
- El modelo admite períodos solapados, varias realizaciones futuras y varios campos futuros sin cambiar las tablas principales.
- Admin puede consultar cobertura y corregir resultados; usuarios no asignados no pueden acceder.
- Cada creación o cambio real de una observación no vacía genera como máximo un correo por dirección asignada distinta.
- Reenviar el mismo texto, dejarlo vacío o no tener destinatarios no genera duplicados.
- Un fallo de correo queda diagnosticado y no revierte asistencia ni observación.
- No se añaden elementos globales, rankings, gráficos ni tarjetas decorativas en el MVP.

### Project Status Board (Evaluaciones y correos de observaciones)

- [x] M0. Decisiones funcionales cerradas; SMTP/remitente e emails destinatarios quedan requeridos antes de M5
- [x] M1. Pruebas de contrato + migración de evaluaciones + documentación API — validado por el usuario; prueba manual conjunta aplazada
- [x] M2. API Admin de evaluaciones — validación automática completada; prueba manual agrupada con el usuario
- [x] M3. UI Admin contextual dentro de la actividad — validación automática completada; prueba visual/manual pendiente
- [x] M4. API y captura operativa del monitor — validación automática completada; prueba funcional/visual agrupada con el usuario
- [x] M5. Emails de coordinadores, outbox/deduplicación e integración con observaciones — implementación y pruebas automáticas cerradas; configuración y entrega SMTP real pendientes
- [ ] M6. QA — automatización y revisión estática completadas; migraciones, prueba funcional/visual y entrega SMTP real pendientes con el usuario

### Current Status / Progress Tracking

**Status**: EXECUTOR MODE — M1–M3 implementados; M4 en ejecución. La migración sigue sin aplicarse a la base de datos.

**Próximo paso**: validación manual del usuario para cerrar M1. Tras su confirmación, marcar M1 completado y comenzar M2 (API Admin de evaluaciones). Los datos SMTP no bloquean M2–M4; serán imprescindibles antes de M5.

**Actualización Executor (2026-08-12)**: M1 iniciado con autorización del usuario. Alcance limitado a pruebas de contrato, archivos SQL de subida/bajada y documentación; la migración no se ejecutará contra ninguna base de datos. Antes de M2 se solicitará validación manual conforme al flujo del proyecto.

**Resultado M1 (2026-08-12)**:

- Añadido test autocontenido `tests/evaluaciones_contract_test.php`, sin dependencias ni conexión a BD.
- Registrado el ciclo TDD: 9 fallos iniciales por ausencia de artefactos; tras implementar, 9 pruebas correctas y 0 fallidas.
- Añadidas migraciones reversibles `migrations/20260812_create_evaluaciones.up.sql` y `.down.sql` con cuatro tablas y claves/índices de integridad.
- Añadido contrato `docs/api/evaluaciones.md` con endpoints Admin/monitor, permisos, errores, validación, estados, concurrencia e idempotencia.
- `git diff --check` correcto. No se aplicó la migración ni se conectó a la base de datos.
- M1 quedó validado por el usuario en el turno siguiente; la prueba manual se agrupará más adelante.

**Inicio M2 (2026-08-12)**:

- El usuario valida continuar y solicita agrupar la prueba manual para más adelante.
- Se implementará la API Admin con TDD y comprobaciones automáticas de sintaxis, pero sin conexión a BD.
- Alcance M2: listado/detalle, creación, edición, archivado, reapertura y corrección de resultados.
- La UI Admin queda fuera de este milestone y pertenece a M3.

**Resultado M2–M3 (2026-08-12)**:

- Implementada API Admin autenticada para listado/detalle, creación, edición, archivado, reapertura y corrección idempotente de resultados.
- La autorización se deriva de `admin_asignaciones`, con acceso global únicamente para superadmin; todas las mutaciones usan consultas preparadas, transacciones y auditoría.
- El resultado conserva una referencia y copia del nombre/apellidos del participante; si posteriormente se elimina su inscripción, el histórico permanece consultable y se marca como no editable.
- Añadida navegación local `Participantes | Evaluaciones` en `admin/activity.php`; Participantes sigue siendo la vista inicial y las evaluaciones se cargan bajo demanda.
- Añadidos listado compacto por estado/período, cobertura, estados carga/vacío/error, modal de configuración de un campo y vista Admin de resultados con corrección y reapertura.
- Todo el CSS nuevo se añadió a `admin/assets/css/admin.css`, con foco visible, objetivos táctiles y adaptación móvil; no se añadieron dependencias.
- TDD M3: 7 fallos iniciales por interfaz ausente; resultado final 7/7. Suite acumulada: contrato 9/9, API Admin 9/9 y UI Admin 7/7. PHP lint, `node --check` y `git diff --check` correctos.
- No se aplicó la migración ni se abrió conexión a la base de datos. La prueba funcional con datos se realizará junto al usuario después de completar varias fases.

**Resultado M4 (2026-08-12)**:

- Implementados endpoints de monitor para listar trabajo activo, iniciar/continuar una única realización, consultar detalle, guardar una fila idempotente y finalizar.
- La sesión PHP del centro es la única fuente de autorización. Actividad, evaluación, realización, campo y participante se resuelven y validan en servidor.
- El inicio exige que hoy y la fecha real pertenezcan al período y que la fecha real no sea futura; usa bloqueo transaccional, intento único e instantánea de participantes/resultados `sin_evaluar`.
- Guardar distingue el cero del vacío, reutiliza las validaciones de tipos y bloquea escritura tras finalizar o fuera del período. Finalizar es idempotente y exige confirmar el número de participantes pendientes.
- En `asistencia.php`, la sección secundaria `Evaluaciones` empieza oculta y solo aparece si hay evaluaciones pendientes o en curso; no se asocia a la fecha de asistencia seleccionada.
- La fecha real se pide mediante revelado progresivo dentro de la fila. La captura se realiza en `evaluacion.php`, con progreso, filtros Todos/Pendientes, guardado por fila, `Sin evaluar`, finalización y modo lectura.
- TDD M4: 15 fallos iniciales por API/UI ausentes; resultado final 8/8 API y 7/7 UI. Suite acumulada M1–M4: 40/40, PHP lint, ambos `node --check` y `git diff --check` correctos.
- La migración continúa sin aplicarse. La verificación funcional con MySQL y la revisión visual se agruparán con el usuario, como pidió.

**Resultado M5 (2026-08-12)**:

- Añadida migración reversible para `admins.email` y una outbox en dos niveles: evento con instantánea completa y entregas por dirección deduplicada.
- Crear/editar/listar cuentas desde `admin/admins.php` ya admite correo de avisos y señala de forma visible `Sin email`; la API valida con `FILTER_VALIDATE_EMAIL`.
- La fuente de destinatarios es exclusivamente `admin_asignaciones` del centro. Las direcciones se normalizan, validan y deduplican; superadmin no recibe por rol sin asignación explícita.
- `registrar_asistencia.php` compara contenido normalizado. Una observación nueva o modificada y no vacía crea outbox; mismo texto o vacío no envían. El contexto se resuelve en servidor e incluye centro, instalación, actividad/grupo, días, horario, período, fecha y observación completa.
- La outbox usa savepoint dentro de la transacción. La asistencia/observación se confirma y libera la sesión antes del intento SMTP; cualquier fallo de cola o entrega queda diagnosticado y no revierte los datos operativos.
- Añadidos intento inmediato post-commit, estados por dirección, error limitado, espera progresiva, máximo de cinco intentos y worker CLI `scripts/process_observation_notifications.php`.
- Añadida documentación de variables, deduplicación, estados, cron/reintento y prueba controlada en `docs/api/notificaciones-observaciones.md`. No se guardan secretos ni contenido de la observación en logs.
- Añadido PHPMailer 6.12 mediante Composer. La auditoría detectó nueve avisos preexistentes (incluidos críticos) en PhpSpreadsheet 1.30.2; se actualizó dentro de la rama compatible a 1.30.6 junto con dependencias directas necesarias. `composer audit --locked` termina sin avisos.
- TDD M5: 14 fallos iniciales; resultado final 8/8 notificaciones y 6/6 email de coordinadores. La prueba SMTP real no se ejecuta porque faltan host, puerto, cifrado, remitente y credenciales, tal como estaba previsto.

**QA automático M6 (2026-08-12)**:

- Suite acumulada M1–M5: 54/54 pruebas correctas y 0 fallidas.
- Sintaxis correcta en las 29 superficies PHP nuevas/modificadas comprobadas y en los tres JavaScript (`node --check`).
- `composer validate --strict` válido; mantiene el aviso no bloqueante preexistente de que el proyecto no declara licencia.
- `composer audit --locked`: cero avisos de seguridad tras actualizar PhpSpreadsheet a 1.30.6.
- Smoke test de PhpSpreadsheet después de la actualización: creación de libro y lectura/escritura de celda correctas.
- `git diff --check`: correcto.
- M6 no se cierra aún: faltan aplicar ambas migraciones en un entorno confirmado, prueba funcional/visual en escritorio y móvil, y prueba SMTP controlada. El usuario pidió realizar esas pruebas conjuntamente.

### Executor's Feedback or Assistance Requests

0. M1 introduce el contrato de cuatro tablas nuevas, pero no cambia datos en ejecución. La aplicación real de la migración será una acción crítica posterior y requerirá confirmar entorno, copia de seguridad y versión de MySQL.
0.1. Para validar M1 manualmente sin tocar la BD, ejecutar `php tests/evaluaciones_contract_test.php` desde la raíz y confirmar `9 correctas, 0 fallidas`.
1. Antes del Milestone 5, indicar dirección remitente, host, puerto, cifrado y credenciales SMTP. No incluir secretos en el repositorio; se configurarán mediante variables de entorno o configuración externa.
2. Antes de la prueba final de correo, facilitar o cargar en Admin el email de cada coordinador; el modelo actual no guarda este dato.

### Lessons (Evaluaciones)

- El repositorio no dispone de PHPUnit ni de otro runner de tests. Para M1 se usa un test PHP autocontenido que valida contrato y SQL sin instalar dependencias ni abrir conexiones a la base de datos.
- Separar `evaluaciones` (ventana planificada) de `evaluacion_sesiones` (fecha real) evita acoplar la evaluación a la fecha de asistencia y preserva flexibilidad operativa.
- Composer está versionado junto con `vendor`. Al añadir PHPMailer hay que regenerar autoload y auditar el lockfile. El 2026-08-12 la auditoría reveló vulnerabilidades críticas/altas preexistentes en PhpSpreadsheet 1.30.2; la versión 1.30.6 las corrige sin salir de la rama 1.x declarada.
- La outbox de observaciones debe usar un savepoint: si falla después de insertar el evento pero antes de insertar todos los destinatarios, se revierte solo la cola parcial y se conserva el guardado principal.
