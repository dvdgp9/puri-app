/**
 * JavaScript para la página de detalle de actividad (participantes)
 */

const ActivityPage = {
  id: null,
  ctx: null,
  participants: []
};

// Init
window.addEventListener('DOMContentLoaded', () => {
  ActivityPage.ctx = window.__ACTIVITY_CTX__ || null;
  ActivityPage.id = ActivityPage.ctx ? Number(ActivityPage.ctx.id) : null;
  if (!ActivityPage.id) {
    window.location.href = 'dashboard.php';
    return;
  }

  // Prefill locked fields in add participant modal
  prefillLockedFields();

  // Load participants
  loadParticipants();

  // Wire search/sort
  const search = document.getElementById('search-participants');
  if (search) search.addEventListener('input', filterParticipants);
  const sort = document.getElementById('sort-participants');
  if (sort) sort.addEventListener('change', sortParticipants);

  // Wire forms
  const editForm = document.getElementById('editActivityForm');
  if (editForm) editForm.addEventListener('submit', handleEditActivitySubmit);
  const createForm = document.getElementById('createParticipantForm');
  if (createForm) createForm.addEventListener('submit', handleCreateParticipantSubmit);
  const uploadCsvForm = document.getElementById('uploadParticipantCsvForm');
  if (uploadCsvForm) uploadCsvForm.addEventListener('submit', handleUploadCsvSubmit);

  // Edit participant form
  const editParticipantForm = document.getElementById('editParticipantForm');
  if (editParticipantForm) editParticipantForm.addEventListener('submit', handleEditParticipantSubmit);
});

// Load participants
async function loadParticipants() {
  const list = document.getElementById('participants-list');
  if (list) list.innerHTML = '<div class="loading-card">Cargando participantes...</div>';
  try {
    const resp = await fetch(`api/participantes/list_by_activity.php?actividad_id=${ActivityPage.id}`);
    const data = await resp.json();
    if (!data.success) throw new Error(data.message || 'Error');
    ActivityPage.participants = data.participants || [];
    renderParticipants();
  } catch (e) {
    console.error(e);
    if (list) list.innerHTML = '<div class="error-state">No se pudieron cargar los participantes</div>';
  }
}

function renderParticipants() {
  const container = document.getElementById('participants-list');
  if (!container) return;
  if (!ActivityPage.participants || ActivityPage.participants.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <svg width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
          <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
        </svg>
        <h3>No hay participantes</h3>
        <p>Añade participantes manualmente o mediante CSV</p>
        <button class="btn btn-primary" onclick="openAddParticipantsModal()">+ Añadir Participantes</button>
      </div>`;
    return;
  }

  const items = ActivityPage.participants
    .map(p => `
      <div class="center-item" style="cursor: default;">
        <div class="center-main">
          <div class="center-header">
            <h3 class="center-name">${escapeHtml((p.apellidos || '') + ', ' + (p.nombre || ''))}</h3>
          </div>
        </div>
        <div class="center-actions">
          <div class="dropdown" onclick="event.stopPropagation()">
            <button class="more-btn" onclick="event.stopPropagation(); toggleParticipantDropdown(${p.id}, this); return false;">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 1 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 1 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 1 1 0 3z"/>
              </svg>
            </button>
            <div class="dropdown-menu" id="participant-dropdown-${p.id}" onclick="event.stopPropagation()">
              <a href="#" onclick="event.preventDefault(); editParticipant(${p.id});">Editar</a>
            </div>
          </div>
        </div>
      </div>
    `)
    .join('');

  container.innerHTML = items;
}

function filterParticipants() {
  const q = (document.getElementById('search-participants').value || '').toLowerCase();
  document.querySelectorAll('#participants-list .center-item').forEach(item => {
    const name = (item.querySelector('.center-name')?.textContent || '').toLowerCase();
    item.style.display = name.includes(q) ? 'flex' : 'none';
  });
}

function sortParticipants() {
  const sortValue = document.getElementById('sort-participants').value;
  const container = document.getElementById('participants-list');
  const items = Array.from(container.querySelectorAll('.center-item'));
  items.sort((a, b) => {
    const nameA = a.querySelector('.center-name').textContent;
    const nameB = b.querySelector('.center-name').textContent;
    return sortValue === '-apellidos' ? nameB.localeCompare(nameA) : nameA.localeCompare(nameB);
  });
  items.forEach(it => container.appendChild(it));
}

// Edit Activity
function openEditActivityModal() {
  // Prefill
  const a = ActivityPage.ctx || {};
  if (document.getElementById('editActivityId')) document.getElementById('editActivityId').value = String(ActivityPage.id);
  if (document.getElementById('editActivityName')) document.getElementById('editActivityName').value = decodeHtml(a.nombre || '');
  const diasArr = String(a.dias_semana || '').split(',').map(s => s.trim()).filter(Boolean);
  document.querySelectorAll('input[name="edit_dias_semana[]"]').forEach(cb => {
    cb.checked = diasArr.includes(cb.value);
  });
  if (document.getElementById('editActivityStart')) document.getElementById('editActivityStart').value = a.hora_inicio || '';
  if (document.getElementById('editActivityEnd')) document.getElementById('editActivityEnd').value = a.hora_fin || '';
  if (document.getElementById('editActivityDateStart')) document.getElementById('editActivityDateStart').value = (a.fecha_inicio || '').substring(0,10);
  if (document.getElementById('editActivityDateEnd')) document.getElementById('editActivityDateEnd').value = a.fecha_fin ? String(a.fecha_fin).substring(0,10) : '';
  openModal('editActivityModal');
}

async function handleEditActivitySubmit(e) {
  e.preventDefault();
  const id = Number(document.getElementById('editActivityId').value);
  const nombre = String(document.getElementById('editActivityName').value || '').trim();
  const dias_semana = Array.from(document.querySelectorAll('input[name="edit_dias_semana[]"]:checked')).map(el => el.value);
  const hora_inicio = String(document.getElementById('editActivityStart').value || '');
  const hora_fin = String(document.getElementById('editActivityEnd').value || '');
  const fecha_inicio = document.getElementById('editActivityDateStart') ? String(document.getElementById('editActivityDateStart').value || '') : '';
  const fecha_fin = document.getElementById('editActivityDateEnd') ? (document.getElementById('editActivityDateEnd').value || null) : null;
  const err = document.getElementById('editActivityName-error');
  const errDays = document.getElementById('edit_dias_semana-error');
  if (err) err.textContent = '';
  if (errDays) errDays.textContent = '';
  if (!nombre) { if (err) err.textContent = 'El nombre es obligatorio'; return; }
  if (!dias_semana.length) { if (errDays) errDays.textContent = 'Selecciona al menos un día'; return; }
  if (!fecha_inicio) { showNotification('La fecha de inicio es obligatoria', 'error'); return; }
  try {
    const resp = await fetch('api/actividades/update.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, nombre, dias_semana, hora_inicio, hora_fin, fecha_inicio, fecha_fin })
    });
    const result = await resp.json();
    if (result.success) {
      // Update header title and ctx
      const title = document.querySelector('.center-title');
      if (title) title.textContent = nombre;
      ActivityPage.ctx.nombre = nombre;
      ActivityPage.ctx.dias_semana = dias_semana.join(',');
      ActivityPage.ctx.hora_inicio = hora_inicio;
      ActivityPage.ctx.hora_fin = hora_fin;
      ActivityPage.ctx.fecha_inicio = fecha_inicio;
      ActivityPage.ctx.fecha_fin = fecha_fin;
      closeModal('editActivityModal');
      showNotification('Actividad actualizada', 'success');
    } else {
      showNotification(result.message || 'No se pudo actualizar la actividad', 'error');
    }
  } catch (e) {
    console.error(e);
    showNotification('Error actualizando la actividad', 'error');
  }
}

// Add Participants Modal
function openAddParticipantsModal() {
  prefillLockedFields();
  openModal('createParticipantModal');
}
function closeCreateParticipantModal() { closeModal('createParticipantModal'); }

function switchParticipantTab(tab) {
  const manualBtn = document.querySelector('.tab-navigation .tab-btn:nth-child(1)');
  const csvBtn = document.querySelector('.tab-navigation .tab-btn:nth-child(2)');
  const manualTab = document.getElementById('manualTab');
  const csvTab = document.getElementById('csvTab');
  if (!manualBtn || !csvBtn || !manualTab || !csvTab) return;
  if (tab === 'manual') {
    manualBtn.classList.add('active');
    csvBtn.classList.remove('active');
    manualTab.classList.add('active');
    csvTab.classList.remove('active');
  } else {
    manualBtn.classList.remove('active');
    csvBtn.classList.add('active');
    manualTab.classList.remove('active');
    csvTab.classList.add('active');
  }
}

function prefillLockedFields() {
  const a = ActivityPage.ctx || {};
  const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
  setVal('lockedCenterName', a.centro_nombre || '');
  setVal('lockedInstallationName', a.instalacion_nombre || '');
  setVal('lockedActivityName', a.nombre || '');
  const hid = document.getElementById('lockedActivityId');
  if (hid) hid.value = String(ActivityPage.id);

  setVal('csvLockedCenterName', a.centro_nombre || '');
  setVal('csvLockedInstallationName', a.instalacion_nombre || '');
  setVal('csvLockedActivityName', a.nombre || '');
  const hidCsv = document.getElementById('csvLockedActivityId');
  if (hidCsv) hidCsv.value = String(ActivityPage.id);
}

// Create participant (manual)
async function handleCreateParticipantSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const nombre = (form.querySelector('#participantName').value || '').trim();
  const apellidos = (form.querySelector('#participantLastName').value || '').trim();
  const actividad_id = ActivityPage.id;
  const errName = document.getElementById('participantName-error');
  const errLast = document.getElementById('participantLastName-error');
  if (errName) errName.textContent = '';
  if (errLast) errLast.textContent = '';
  if (!nombre) { if (errName) errName.textContent = 'El nombre es obligatorio'; return; }
  if (!apellidos) { if (errLast) errLast.textContent = 'Los apellidos son obligatorios'; return; }
  try {
    const btn = document.getElementById('createParticipantBtn');
    setBtnLoading(btn, true);
    const resp = await fetch('api/participantes/create.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nombre, apellidos, actividad_id })
    });
    const result = await resp.json();
    if (result.success) {
      await loadParticipants();
      closeCreateParticipantModal();
      showNotification('Participante añadido', 'success');
      form.reset();
      prefillLockedFields();
    } else {
      showNotification(result.message || 'No se pudo añadir el participante', 'error');
    }
  } catch (e) {
    console.error(e);
    showNotification('Error añadiendo participante', 'error');
  } finally {
    const btn = document.getElementById('createParticipantBtn');
    setBtnLoading(btn, false);
  }
}

// Upload CSV
async function handleUploadCsvSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const fileInput = document.getElementById('participantsCsv');
  const file = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
  if (!file) { showNotification('Selecciona un archivo CSV', 'error'); return; }
  try {
    const btn = document.getElementById('uploadParticipantsCsvBtn');
    setBtnLoading(btn, true);
    const fd = new FormData();
    fd.append('csv', file);
    fd.append('actividad_id', String(ActivityPage.id));
    const resp = await fetch('api/participantes/upload_csv.php', { method: 'POST', body: fd });
    const result = await resp.json();
    if (result.success) {
      await loadParticipants();
      closeCreateParticipantModal();
      showNotification('CSV procesado correctamente', 'success');
      form.reset();
      prefillLockedFields();
    } else {
      const msg = result.message || 'No se pudo procesar el CSV';
      showNotification(msg, 'error');
    }
  } catch (e) {
    console.error(e);
    showNotification('Error subiendo CSV', 'error');
  } finally {
    const btn = document.getElementById('uploadParticipantsCsvBtn');
    setBtnLoading(btn, false);
  }
}

// Helpers
function openModal(modalId) { const m = document.getElementById(modalId); if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; } }
function closeModal(modalId) { const m = document.getElementById(modalId); if (m) { m.classList.remove('show'); document.body.style.overflow = ''; const f = m.querySelector('form'); if (f) { const errs = f.querySelectorAll('.field-error,.form-error'); errs.forEach(e=>e.textContent=''); } } }
function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
function decodeHtml(html) { const div = document.createElement('div'); div.innerHTML = html; return div.textContent || div.innerText || ''; }
function showNotification(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => toast.classList.add('show'), 10);
  setTimeout(() => { toast.classList.remove('show'); setTimeout(()=>toast.remove(), 300); }, 3000);
}
function setBtnLoading(btn, loading) {
  if (!btn) return;
  btn.disabled = !!loading;
  const text = btn.querySelector('.btn-text');
  const spinner = btn.querySelector('.btn-loading');
  if (text && spinner) {
    if (loading) { text.style.display = 'none'; spinner.style.display = 'inline-block'; }
    else { text.style.display = ''; spinner.style.display = 'none'; }
  }
}

// Participants edit dropdown/modal
function toggleParticipantDropdown(id, btnEl) {
  const dropdown = document.getElementById(`participant-dropdown-${id}`);
  const wasVisible = dropdown.classList.contains('show');
  document.querySelectorAll('.dropdown-menu').forEach(menu => {
    menu.classList.remove('show');
    menu.classList.remove('dropup');
    menu.style.top = '';
    menu.style.left = '';
    menu.style.right = '';
    menu.style.bottom = '';
  });
  if (wasVisible) return;
  const container = btnEl.closest('.dropdown');
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

document.addEventListener('click', function(event) {
  if (!event.target.closest('.dropdown')) {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
      menu.classList.remove('show');
      menu.classList.remove('dropup');
      menu.style.top = '';
      menu.style.left = '';
      menu.style.right = '';
      menu.style.bottom = '';
    });
  }
});

function editParticipant(id) {
  const p = (ActivityPage.participants || []).find(x => String(x.id) === String(id));
  if (!p) return;
  const idInput = document.getElementById('editParticipantId');
  const nameInput = document.getElementById('editParticipantName');
  const lastInput = document.getElementById('editParticipantLastName');
  if (idInput) idInput.value = String(p.id);
  if (nameInput) nameInput.value = p.nombre || '';
  if (lastInput) lastInput.value = p.apellidos || '';
  // close dropdown if open
  const menu = document.getElementById(`participant-dropdown-${id}`);
  if (menu) {
    menu.classList.remove('show', 'open', 'dropup');
    menu.style.top = '';
    menu.style.left = '';
    menu.style.right = '';
    menu.style.bottom = '';
  }
  openModal('editParticipantModal');
}

async function handleEditParticipantSubmit(e) {
  e.preventDefault();
  const id = Number(document.getElementById('editParticipantId').value);
  const nombre = String(document.getElementById('editParticipantName').value || '').trim();
  const apellidos = String(document.getElementById('editParticipantLastName').value || '').trim();
  const errName = document.getElementById('editParticipantName-error');
  const errLast = document.getElementById('editParticipantLastName-error');
  if (errName) errName.textContent = '';
  if (errLast) errLast.textContent = '';
  if (!nombre) { if (errName) errName.textContent = 'El nombre es obligatorio'; return; }
  if (!apellidos) { if (errLast) errLast.textContent = 'Los apellidos son obligatorios'; return; }
  try {
    const btn = document.getElementById('saveEditParticipantBtn');
    setBtnLoading(btn, true);
    const resp = await fetch('api/participantes/update.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, nombre, apellidos })
    });
    const result = await resp.json();
    if (result.success) {
      await loadParticipants();
      closeModal('editParticipantModal');
      showNotification('Participante actualizado', 'success');
    } else {
      showNotification(result.message || 'No se pudo actualizar el participante', 'error');
    }
  } catch (err) {
    console.error(err);
    showNotification('Error actualizando participante', 'error');
  } finally {
    const btn = document.getElementById('saveEditParticipantBtn');
    setBtnLoading(btn, false);
  }
}
