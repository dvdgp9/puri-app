/**
 * JavaScript para la página de detalle de instalación
 * Espejo de center.js pero para actividades
 */

const Installation = {
  id: null,
  activities: [],
  stats: null
};

// Init
document.addEventListener('DOMContentLoaded', () => {
  Installation.id = (window.__INSTALLATION_CTX__ && window.__INSTALLATION_CTX__.id) ? Number(window.__INSTALLATION_CTX__.id) : null;
  if (!Installation.id) {
    window.location.href = 'dashboard.php';
    return;
  }

  loadInstallationStats();
  loadActivities();

  setupSearch();
  setupCreateActivityForm();
  setupEditActivityForm();

  // Header edit (instalación)
  const headerForm = document.getElementById('editInstallationHeaderForm');
  if (headerForm) headerForm.addEventListener('submit', handleEditInstallationHeaderSubmit);
});

// Stats
async function loadInstallationStats() {
  try {
    const resp = await fetch(`api/stats/installation.php?id=${Installation.id}`);
    const data = await resp.json();
    if (data.success) {
      Installation.stats = data.data;
      renderStats();
    } else {
      throw new Error(data.error || 'Error cargando estadísticas');
    }
  } catch (e) {
    console.error(e);
    showStatsError();
  }
}

function renderStats() {
  const statsGrid = document.getElementById('stats-grid');
  if (!statsGrid || !Installation.stats) return;
  const s = Installation.stats;
  statsGrid.innerHTML = `
    <div class="stat-card">
      <div class="stat-header">
        <div class="stat-title">Actividades</div>
        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <div class="stat-value">${s.actividades_activas || 0}</div>
      <div class="stat-change">Activas • ${s.actividades_programadas || 0} programadas</div>
    </div>
    <div class="stat-card">
      <div class="stat-header">
        <div class="stat-title">Participantes</div>
        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
      </div>
      <div class="stat-value">${s.total_participantes || 0}</div>
      <div class="stat-change">Acumulado</div>
    </div>
    <div class="stat-card">
      <div class="stat-header">
        <div class="stat-title">Próximas</div>
        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
      <div class="stat-value">${s.actividades_programadas || 0}</div>
      <div class="stat-change">Próximas a iniciar</div>
    </div>
  `;
}

function showStatsError() {
  const statsGrid = document.getElementById('stats-grid');
  if (statsGrid) {
    statsGrid.innerHTML = `
      <div class="error-card">
        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Error cargando estadísticas
      </div>`;
  }
}

// Activities
async function loadActivities() {
  try {
    const resp = await fetch(`api/actividades/list_by_installation.php?instalacion_id=${Installation.id}`);
    const data = await resp.json();
    if (data.success) {
      Installation.activities = data.actividades || [];
      renderActivities();
    } else {
      throw new Error(data.message || 'Error cargando actividades');
    }
  } catch (e) {
    console.error(e);
    showActivitiesError();
  }
}

function renderActivities() {
  const container = document.getElementById('activities-list');
  if (!container) return;
  if (!Installation.activities || Installation.activities.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <svg width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
          <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V4.5l5.5-3a2 2 0 0 1 2 0l5.5 3z"/>
        </svg>
        <h3>No hay actividades</h3>
        <p>Crea la primera actividad para esta instalación</p>
        <button class="btn btn-primary" onclick="showCreateActivityModal()">+ Nueva Actividad</button>
      </div>`;
    return;
  }

  const items = Installation.activities.map(a => `
    <div class="center-item" style="cursor: default;">
      <div class="center-main">
        <div class="center-header">
          <h3 class="center-name">${escapeHtml(decodeHtml(a.nombre || ''))}</h3>
        </div>
        <div class="center-details">
          <span class="center-stat">
            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
              <path d="M3.5 0a.5.5 0 0 0-.5.5V2H1.5A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14H3v1.5a.5.5 0 0 0 .8.4L8 12.5l4.2 3.4a.5.5 0 0 0 .8-.4V14h1.5A1.5 1.5 0 0 0 16 12.5v-9A1.5 1.5 0 0 0 14.5 2H13V.5a.5.5 0 0 0-1 0V2H4.5V.5a.5.5 0 0 0-1 0V2z"/>
            </svg>
            ${escapeHtml(a.dias_semana || '-')}
          </span>
          <span class="center-stat">
            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8 3.5a.5.5 0 0 1 .5.5v4h4a.5.5 0 0 1 0 1H8.5v4a.5.5 0 0 1-1 0V9H3.5a.5.5 0 0 1 0-1h4V4a.5.5 0 0 1 .5-.5z"/>
            </svg>
            ${a.hora_inicio ? a.hora_inicio.substring(0,5) : '--:--'} - ${a.hora_fin ? a.hora_fin.substring(0,5) : '--:--'}
          </span>
        </div>
      </div>
      <div class="center-actions">
        <div class="dropdown" onclick="event.stopPropagation();">
          <button class="more-btn" onclick="toggleActivityDropdown(event, ${a.id}); return false;">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 1 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 1 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 1 1 0 3z"/>
            </svg>
          </button>
          <div class="dropdown-menu" id="activity-dropdown-${a.id}" onclick="event.stopPropagation();">
            <a href="#" onclick="event.preventDefault(); editActivity(${a.id});">Editar</a>
          </div>
        </div>
      </div>
    </div>
  `).join('');

  container.innerHTML = items;
}

function showActivitiesError() {
  const container = document.getElementById('activities-list');
  if (container) {
    container.innerHTML = `
      <div class="error-state">
        <p>Error cargando actividades</p>
        <button class="btn btn-secondary" onclick="loadActivities()">Reintentar</button>
      </div>`;
  }
}

// Search/sort
function setupSearch() {
  const search = document.getElementById('search-activities');
  if (search) search.addEventListener('input', filterActivities);
  const sort = document.getElementById('sort-activities');
  if (sort) sort.addEventListener('change', sortActivities);
}

function filterActivities() {
  const searchTerm = (document.getElementById('search-activities').value || '').toLowerCase();
  document.querySelectorAll('#activities-list .center-item').forEach(item => {
    const name = item.querySelector('.center-name').textContent.toLowerCase();
    item.style.display = name.includes(searchTerm) ? 'flex' : 'none';
  });
}

function sortActivities() {
  const sortValue = document.getElementById('sort-activities').value;
  const container = document.getElementById('activities-list');
  const items = Array.from(container.querySelectorAll('.center-item'));
  items.sort((a, b) => {
    const nameA = a.querySelector('.center-name').textContent;
    const nameB = b.querySelector('.center-name').textContent;
    return sortValue === '-nombre' ? nameB.localeCompare(nameA) : nameA.localeCompare(nameB);
  });
  items.forEach(it => container.appendChild(it));
}

// Forms
function setupCreateActivityForm() {
  const form = document.getElementById('createActivityForm');
  if (!form) return;
  form.addEventListener('submit', handleCreateActivity);
}

function setupEditActivityForm() {
  const form = document.getElementById('editActivityForm');
  if (!form) return;
  form.addEventListener('submit', handleEditActivity);
}

async function handleCreateActivity(e) {
  e.preventDefault();
  const form = e.target;
  const nombre = (form.querySelector('#activityName').value || '').trim();
  const dias_text = form.querySelector('#activityDays').value || '';
  const dias_semana = dias_text
    .split(',')
    .map(s => s.trim())
    .filter(Boolean);
  const hora_inicio = form.querySelector('#activityStart').value || '';
  const hora_fin = form.querySelector('#activityEnd').value || '';
  const fecha_inicio = form.querySelector('#activityDateStart').value || new Date().toISOString().slice(0,10);
  const fecha_fin = form.querySelector('#activityDateEnd').value || null;
  const err = document.getElementById('activityName-error');
  if (err) err.textContent = '';
  if (!nombre) {
    if (err) err.textContent = 'El nombre es obligatorio';
    return;
  }
  try {
    const resp = await fetch('api/actividades/create.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nombre, dias_semana, hora_inicio, hora_fin, fecha_inicio, fecha_fin, instalacion_id: Installation.id })
    });
    const result = await resp.json();
    if (result.success) {
      closeModal('createActivityModal');
      await loadActivities();
      await loadInstallationStats();
      showNotification('Actividad creada', 'success');
    } else {
      showNotification(result.message || 'No se pudo crear la actividad', 'error');
    }
  } catch (e) {
    console.error(e);
    showNotification('Error creando la actividad', 'error');
  }
}

function editActivity(id) {
  const a = (Installation.activities || []).find(x => x.id === id);
  if (!a) return;
  document.getElementById('editActivityId').value = id;
  document.getElementById('editActivityName').value = decodeHtml(a.nombre || '');
  document.getElementById('editActivityDays').value = a.dias_semana || '';
  document.getElementById('editActivityStart').value = a.hora_inicio || '';
  document.getElementById('editActivityEnd').value = a.hora_fin || '';
  openModal('editActivityModal');
}

async function handleEditActivity(e) {
  e.preventDefault();
  const id = Number(document.getElementById('editActivityId').value);
  const nombre = String(document.getElementById('editActivityName').value || '').trim();
  const dias_semana = String(document.getElementById('editActivityDays').value || '');
  const hora_inicio = String(document.getElementById('editActivityStart').value || '');
  const hora_fin = String(document.getElementById('editActivityEnd').value || '');
  const err = document.getElementById('editActivityName-error');
  if (err) err.textContent = '';
  if (!nombre) {
    if (err) err.textContent = 'El nombre es obligatorio';
    return;
  }
  try {
    const resp = await fetch('api/actividades/update.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, nombre, dias_semana, hora_inicio, hora_fin })
    });
    const result = await resp.json();
    if (result.success) {
      closeModal('editActivityModal');
      await loadActivities();
      await loadInstallationStats();
      showNotification('Actividad actualizada', 'success');
    } else {
      showNotification(result.message || 'No se pudo actualizar la actividad', 'error');
    }
  } catch (e) {
    console.error(e);
    showNotification('Error actualizando la actividad', 'error');
  }
}

// Navigation & UI helpers
function goBackToCenter(centerId) {
  window.location.href = `center.php?id=${centerId}`;
}
function showCreateActivityModal() { openModal('createActivityModal'); }
function toggleActivityDropdown(event, id) {
  event.stopPropagation();
  toggleDropdown(id, 'activity-dropdown-');
}

// Utilidades: usamos las de center.js si están globales (escapeHtml, decodeHtml, openModal, closeModal, toggleDropdown, showNotification)
// Si no existen, definimos implementaciones locales
if (typeof escapeHtml === 'undefined') {
  function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
}
if (typeof decodeHtml === 'undefined') {
  function decodeHtml(html) { const div = document.createElement('div'); div.innerHTML = html; return div.textContent || div.innerText || ''; }
}
if (typeof openModal === 'undefined') {
  function openModal(modalId) { const m = document.getElementById(modalId); if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; } }
}
if (typeof closeModal === 'undefined') {
  function closeModal(modalId) { const m = document.getElementById(modalId); if (m) { m.classList.remove('show'); document.body.style.overflow = ''; const f = m.querySelector('form'); if (f) { f.reset(); const errs = f.querySelectorAll('.field-error,.form-error'); errs.forEach(e=>e.textContent=''); } } }
}
if (typeof toggleDropdown === 'undefined') {
  function toggleDropdown(id, prefix = 'dropdown-') {
    const dropdown = document.getElementById(`${prefix}${id}`);
    if (!dropdown) return;
    const wasVisible = dropdown.classList.contains('show') || dropdown.classList.contains('open');
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
      menu.classList.remove('show');
      menu.classList.remove('open');
      menu.classList.remove('dropup');
      menu.style.top = '';
      menu.style.left = '';
      menu.style.right = '';
      menu.style.bottom = '';
    });
    if (wasVisible) return;
    const btn = dropdown.parentElement?.querySelector('.more-btn');
    const container = btn ? btn.closest('.dropdown') : dropdown.closest('.dropdown');
    if (container) {
      const rect = container.getBoundingClientRect();
      const vh = window.innerHeight || document.documentElement.clientHeight;
      const vw = window.innerWidth || document.documentElement.clientWidth;
      const bottomHalf = rect.bottom > (vh / 2);
      const nearRight = rect.right > (vw - 180);
      if (bottomHalf) {
        dropdown.classList.add('dropup');
        dropdown.style.bottom = (vh - rect.top) + 'px';
        dropdown.style.top = 'auto';
      } else {
        dropdown.style.top = rect.bottom + 'px';
        dropdown.style.bottom = 'auto';
      }
      if (nearRight) {
        dropdown.style.right = (vw - rect.right) + 'px';
        dropdown.style.left = 'auto';
      } else {
        dropdown.style.left = rect.left + 'px';
        dropdown.style.right = 'auto';
      }
    }
    dropdown.classList.add('show');
  }
  // Cerrar dropdowns al hacer clic fuera
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
      document.querySelectorAll('.dropdown-menu.show, .dropdown-menu.open').forEach(menu => {
        menu.classList.remove('show');
        menu.classList.remove('open');
        menu.classList.remove('dropup');
        menu.style.top = '';
        menu.style.left = '';
        menu.style.right = '';
        menu.style.bottom = '';
      });
    }
  });
}
if (typeof showNotification === 'undefined') {
  function showNotification(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(()=>toast.remove(), 300); }, 3000);
  }
}

// Edit header (installation name)
function editInstallationHeader(id) {
  const title = document.querySelector('.installation-title');
  const input = document.getElementById('editInstallationHeaderName');
  const idInput = document.getElementById('editInstallationHeaderId');
  if (idInput) idInput.value = String(id || Installation.id || '');
  if (input && title) input.value = (title.textContent || '').trim();
  openModal('editInstallationHeaderModal');
}

async function handleEditInstallationHeaderSubmit(e) {
  e.preventDefault();
  const id = Number(document.getElementById('editInstallationHeaderId').value);
  const nombre = String(document.getElementById('editInstallationHeaderName').value || '').trim();
  const err = document.getElementById('editInstallationHeaderName-error');
  if (err) err.textContent = '';
  if (!nombre) { if (err) err.textContent = 'El nombre es obligatorio'; return; }
  try {
    const resp = await fetch('api/instalaciones/update.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, nombre })
    });
    const result = await resp.json();
    if (result.success) {
      const title = document.querySelector('.installation-title');
      if (title) title.textContent = nombre;
      closeModal('editInstallationHeaderModal');
      showNotification('Instalación actualizada', 'success');
    } else {
      showNotification(result.message || 'No se pudo actualizar la instalación', 'error');
    }
  } catch (e) {
    console.error(e);
    showNotification('Error actualizando la instalación', 'error');
  }
}
