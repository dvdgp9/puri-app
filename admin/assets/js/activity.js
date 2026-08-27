/**
 * JavaScript para la página de detalle de actividad (participantes)
 */

// ActivityPage is already defined by the inline script in activity.php
// Just ensure we have the participants array
if (!window.ActivityPage) {
  window.ActivityPage = { id: null, ctx: null, participants: [] };
}
if (!window.ActivityPage.participants) {
  window.ActivityPage.participants = [];
}
if (!window.ActivityPage.attendanceRange) {
  window.ActivityPage.attendanceRange = null;
}
ActivityPage.evaluations = ActivityPage.evaluations || [];
ActivityPage.evaluationsLoaded = false;
ActivityPage.currentEvaluationDetail = null;
const EvaluationAdminApi = Object.freeze({
  list: 'api/evaluaciones/list_by_activity.php',
  detail: 'api/evaluaciones/detail.php',
  create: 'api/evaluaciones/create.php',
  update: 'api/evaluaciones/update.php',
  archive: 'api/evaluaciones/archive.php',
  reopen: 'api/evaluaciones/reopen.php',
  updateResult: 'api/evaluaciones/update_result.php'
});

function todayIsoLocal() {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function normalizeIsoDate(value) {
  const str = String(value || '').trim();
  if (!/^\d{4}-\d{2}-\d{2}$/.test(str)) return null;
  return str;
}

function getDefaultAttendanceRange() {
  const today = todayIsoLocal();
  const startRaw = normalizeIsoDate((ActivityPage.ctx?.fecha_inicio || '').substring(0, 10));
  const endRaw = normalizeIsoDate((ActivityPage.ctx?.fecha_fin || '').substring(0, 10));
  const start = startRaw || today;
  let end = today;
  if (endRaw && endRaw < today) end = endRaw;
  if (end < start) end = start;
  return { start, end, isDefault: true };
}

function formatDateEs(isoDate) {
  const d = normalizeIsoDate(isoDate);
  if (!d) return '-';
  const [y, m, day] = d.split('-');
  return `${day}/${m}/${y}`;
}

function updateAttendanceRangeSummary() {
  const el = document.getElementById('attendance-range-summary');
  const range = ActivityPage.attendanceRange;
  if (!el || !range) return;
  const suffix = range.isDefault ? ' (por defecto)' : '';
  el.textContent = `Asistencias: ${formatDateEs(range.start)} → ${formatDateEs(range.end)}${suffix}`;
}

function openAttendanceRangeModal() {
  const form = document.getElementById('attendanceRangeForm');
  const startInput = document.getElementById('attendanceDateStart');
  const endInput = document.getElementById('attendanceDateEnd');
  const error = document.getElementById('attendanceRangeError');
  if (!form || !startInput || !endInput) return;
  const current = ActivityPage.attendanceRange || getDefaultAttendanceRange();
  const today = todayIsoLocal();
  const courseStart = normalizeIsoDate((ActivityPage.ctx?.fecha_inicio || '').substring(0, 10)) || current.start;
  const courseEndRaw = normalizeIsoDate((ActivityPage.ctx?.fecha_fin || '').substring(0, 10));
  const maxEnd = courseEndRaw && courseEndRaw < today ? courseEndRaw : today;

  startInput.min = courseStart;
  startInput.max = maxEnd;
  endInput.min = courseStart;
  endInput.max = maxEnd;
  startInput.value = current.start;
  endInput.value = current.end;
  if (error) error.textContent = '';
  openModal('attendanceRangeModal');
}

async function handleAttendanceRangeSubmit(e) {
  e.preventDefault();
  const startInput = document.getElementById('attendanceDateStart');
  const endInput = document.getElementById('attendanceDateEnd');
  const error = document.getElementById('attendanceRangeError');
  const start = normalizeIsoDate(startInput?.value);
  const end = normalizeIsoDate(endInput?.value);
  if (error) error.textContent = '';
  if (!start || !end) {
    if (error) error.textContent = 'Selecciona ambas fechas';
    return;
  }
  if (end < start) {
    if (error) error.textContent = 'La fecha fin no puede ser anterior a la fecha inicio';
    return;
  }
  closeModal('attendanceRangeModal');
  await loadParticipants({ start, end, isDefault: false });
}

async function resetAttendanceRangeToDefault() {
  closeModal('attendanceRangeModal');
  await loadParticipants(getDefaultAttendanceRange());
}

// Init
window.addEventListener('DOMContentLoaded', () => {
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
  const attendanceRangeForm = document.getElementById('attendanceRangeForm');
  if (attendanceRangeForm) attendanceRangeForm.addEventListener('submit', handleAttendanceRangeSubmit);
  const evaluationForm = document.getElementById('evaluationForm');
  if (evaluationForm) evaluationForm.addEventListener('submit', handleEvaluationSubmit);

  const participantsTab = document.getElementById('participants-tab');
  const evaluationsTab = document.getElementById('evaluations-tab');
  if (participantsTab) participantsTab.addEventListener('click', () => switchActivitySection('participants'));
  if (evaluationsTab) evaluationsTab.addEventListener('click', () => switchActivitySection('evaluations'));
  if (window.location.hash === '#evaluaciones') {
    switchActivitySection('evaluations');
  }

  // Header profile dropdown toggle
  const profileBtn = document.getElementById('profile-dropdown-btn');
  const profileDropdown = document.getElementById('profile-dropdown');
  if (profileBtn && profileDropdown) {
    profileBtn.addEventListener('click', function(e){
      e.stopPropagation();
      profileDropdown.classList.toggle('active');
    });
    document.addEventListener('click', function(){
      profileDropdown.classList.remove('active');
    });
  }
});

// Load participants
async function loadParticipants(rangeOverride) {
  const list = document.getElementById('participants-list');
  if (list) list.innerHTML = '<div class="loading-card">Cargando participantes...</div>';
  try {
    const activeRange = rangeOverride || ActivityPage.attendanceRange || getDefaultAttendanceRange();
    const params = new URLSearchParams({
      actividad_id: String(ActivityPage.id),
      fecha_inicio: activeRange.start,
      fecha_fin: activeRange.end
    });
    const resp = await fetch(`api/participantes/list_by_activity.php?${params.toString()}`);
    const data = await resp.json();
    if (!data.success) throw new Error(data.message || 'Error');

    const period = data.period || {};
    const currentStart = normalizeIsoDate(period.fecha_inicio) || activeRange.start;
    const currentEnd = normalizeIsoDate(period.fecha_fin) || activeRange.end;
    const defaultRange = getDefaultAttendanceRange();
    ActivityPage.attendanceRange = {
      start: currentStart,
      end: currentEnd,
      isDefault: (currentStart === defaultRange.start && currentEnd === defaultRange.end)
    };
    updateAttendanceRangeSummary();

    ActivityPage.participants = (data.participants || []).slice().sort((a, b) => {
      const nameA = `${(a.apellidos||'').toString()} ${(a.nombre||'').toString()}`.trim();
      const nameB = `${(b.apellidos||'').toString()} ${(b.nombre||'').toString()}`.trim();
      return nameA.localeCompare(nameB);
    });
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
            ${p.dias_con_lista > 0 ? `<span class="center-status ${p.porcentaje_asistencia_periodo >= 75 ? 'active' : p.porcentaje_asistencia_periodo >= 50 ? '' : 'inactive'}" title="${p.asistencias_periodo}/${p.dias_con_lista} días">${p.porcentaje_asistencia_periodo}%</span>` : ''}
          </div>
          <div class="center-details">
            ${p.dias_con_lista > 0 ? `<span class="center-stat" title="Asistencias en el periodo seleccionado">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
              </svg>
              ${p.asistencias_periodo} de ${p.dias_con_lista} días
            </span>` : '<span class="center-stat" style="color:#94a3b8">Sin datos de asistencia en este periodo</span>'}
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
              <a href="#" onclick="event.preventDefault(); confirmDeleteParticipant(${p.id});">Eliminar</a>
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
  const a = ActivityPage.ctx;
  if (!a) return;
  document.getElementById('editActivityId').value = a.id;
  document.getElementById('editActivityName').value = decodeHtml(a.nombre || '');
  if (document.getElementById('editActivityGroup')) document.getElementById('editActivityGroup').value = a.grupo || '';
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
  const grupo = document.getElementById('editActivityGroup') ? (String(document.getElementById('editActivityGroup').value || '').trim() || null) : null;
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
      body: JSON.stringify({ id, nombre, grupo, dias_semana, hora_inicio, hora_fin, fecha_inicio, fecha_fin })
    });
    const result = await resp.json();
    if (result.success) {
      // Update header title and ctx
      const title = document.querySelector('.center-title');
      if (title) {
        const nombreDisplay = grupo ? `${nombre} (${grupo})` : nombre;
        title.textContent = nombreDisplay;
      }
      ActivityPage.ctx.nombre = nombre;
      ActivityPage.ctx.grupo = grupo;
      ActivityPage.ctx.dias_semana = dias_semana.join(',');
      ActivityPage.ctx.hora_inicio = hora_inicio;
      ActivityPage.ctx.hora_fin = hora_fin;
      ActivityPage.ctx.fecha_inicio = fecha_inicio;
      ActivityPage.ctx.fecha_fin = fecha_fin;
      if (ActivityPage.attendanceRange?.isDefault) {
        await loadParticipants(getDefaultAttendanceRange());
      } else {
        updateAttendanceRangeSummary();
      }
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
  // Default to manual tab and correct button visibility
  switchParticipantTab('manual');
  // Initialize quick entry rows
  initializeQuickEntryRows();
  openModal('createParticipantModal');
}
function closeCreateParticipantModal() { closeModal('createParticipantModal'); }

function switchParticipantTab(tab) {
  const manualBtn = document.querySelector('.tab-navigation .tab-btn:nth-child(1)');
  const csvBtn = document.querySelector('.tab-navigation .tab-btn:nth-child(2)');
  const manualTab = document.getElementById('manualTab');
  const csvTab = document.getElementById('csvTab');
  if (!manualBtn || !csvBtn || !manualTab || !csvTab) return;
  // Toggle footer action buttons to match active tab
  const createBtn = document.getElementById('createParticipantBtn');
  const uploadBtn = document.getElementById('uploadParticipantsCsvBtn');
  if (tab === 'manual') {
    manualBtn.classList.add('active');
    csvBtn.classList.remove('active');
    manualTab.classList.add('active');
    csvTab.classList.remove('active');
    if (createBtn) createBtn.style.display = '';
    if (uploadBtn) uploadBtn.style.display = 'none';
  } else {
    manualBtn.classList.remove('active');
    csvBtn.classList.add('active');
    manualTab.classList.remove('active');
    csvTab.classList.add('active');
    if (createBtn) createBtn.style.display = 'none';
    if (uploadBtn) uploadBtn.style.display = '';
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
  const actividad_id = ActivityPage.id;
  const quickErr = document.getElementById('quickEntryError');
  if (quickErr) quickErr.textContent = '';
  const participantes = collectQuickEntries();
  if (!participantes.length) {
    if (quickErr) quickErr.textContent = 'Añade al menos una fila con Nombre y Apellidos';
    return;
  }
  try {
    const btn = document.getElementById('createParticipantBtn');
    setBtnLoading(btn, true);
    const resp = await fetch('api/participantes/create_multiple.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ actividad_id, participantes })
    });
    const result = await resp.json();
    if (result.success) {
      await loadParticipants();
      const inserted = Number(result.inserted || 0);
      const errs = (result.errors || []).length;
      showNotification(`Añadidos ${inserted} participante(s)${errs ? `, ${errs} con error` : ''}`, inserted ? 'success' : 'warning');
      // Cerrar modal tras completar correctamente
      closeCreateParticipantModal();
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

// Quick Entry helpers
function initializeQuickEntryRows() {
  const tbody = document.getElementById('quickEntryBody');
  if (!tbody) return;
  tbody.innerHTML = '';
  for (let i = 0; i < 3; i++) addQuickEntryRow();
  enableQuickEntryPaste();
  enableQuickEntryAutoAdvance();
}

function addQuickEntryRow() {
  const tbody = document.getElementById('quickEntryBody');
  if (!tbody) return;
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="text" class="form-input" placeholder="Nombre"></td>
    <td><input type="text" class="form-input" placeholder="Apellidos"></td>
    <td style="text-align:right">
      <button type="button" class="btn btn-secondary" title="Eliminar" onclick="this.closest('tr').remove()">&times;</button>
    </td>`;
  tbody.appendChild(tr);
}

function collectQuickEntries() {
  const rows = Array.from(document.querySelectorAll('#quickEntryBody tr'));
  const items = [];
  rows.forEach(r => {
    const inputs = r.querySelectorAll('input');
    const nombre = (inputs[0]?.value || '').trim();
    const apellidos = (inputs[1]?.value || '').trim();
    if (nombre || apellidos) {
      if (nombre && apellidos) items.push({ nombre, apellidos });
    }
  });
  return items;
}

// expose for onclick
if (typeof window !== 'undefined') {
  window.addQuickEntryRow = addQuickEntryRow;
}

// Paste-from-Excel support
function enableQuickEntryPaste() {
  const tbody = document.getElementById('quickEntryBody');
  if (!tbody) return;
  // Delegate paste to inputs within the table body
  tbody.addEventListener('paste', handleQuickEntryPaste);
}

function handleQuickEntryPaste(e) {
  const target = e.target;
  if (!target || target.tagName !== 'INPUT') return; // only handle pastes in inputs
  const tbody = document.getElementById('quickEntryBody');
  if (!tbody) return;
  const clip = e.clipboardData || window.clipboardData;
  if (!clip) return;
  const text = (clip.getData('text/plain') || '').trim();
  if (!text || text.indexOf('\n') === -1 && text.indexOf('\t') === -1) return; // let normal paste if single cell

  e.preventDefault();

  // Determine start row index based on target row
  const rowEl = target.closest('tr');
  const startIndex = Array.from(tbody.querySelectorAll('tr')).indexOf(rowEl);

  // Parse rows and columns (Excel uses tabs) - also support ; or ,
  const lines = text.split(/\r?\n/).filter(l => l.trim().length > 0);

  // Ensure enough rows exist
  const needed = startIndex + lines.length;
  while (tbody.querySelectorAll('tr').length < needed) addQuickEntryRow();

  lines.forEach((line, i) => {
    const cols = line.split(/\t|;|,/);
    let nombre = '';
    let apellidos = '';
    if (cols.length >= 2) {
      nombre = String(cols[0] || '').trim();
      // join remaining cols as apellidos to be safe
      apellidos = cols.slice(1).join(' ').trim();
    } else if (cols.length === 1) {
      const one = String(cols[0] || '').trim();
      // split by last space: Nombre [Apellidos]
      const parts = one.split(/\s+/);
      if (parts.length >= 2) {
        nombre = parts[0];
        apellidos = parts.slice(1).join(' ');
      } else {
        // if only one token, put into nombre and leave apellidos empty
        nombre = one;
      }
    }

    const row = tbody.querySelectorAll('tr')[startIndex + i];
    if (!row) return;
    const inputs = row.querySelectorAll('input');
    if (inputs[0]) inputs[0].value = nombre;
    if (inputs[1]) inputs[1].value = apellidos;
  });

  // Focus next row first input and create one if needed
  const nextIndex = startIndex + lines.length;
  while (tbody.querySelectorAll('tr').length <= nextIndex) addQuickEntryRow();
  const nextRow = tbody.querySelectorAll('tr')[nextIndex];
  const nextInputs = nextRow ? nextRow.querySelectorAll('input') : null;
  if (nextInputs && nextInputs[0]) {
    nextInputs[0].focus();
    nextInputs[0].select();
  }
}

function enableQuickEntryAutoAdvance() {
  const tbody = document.getElementById('quickEntryBody');
  if (!tbody) return;
  tbody.addEventListener('keydown', function(ev) {
    if (ev.key !== 'Enter') return;
    const target = ev.target;
    if (!target || target.tagName !== 'INPUT') return;
    ev.preventDefault();
    const row = target.closest('tr');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const idx = rows.indexOf(row);
    const inputs = row ? row.querySelectorAll('input') : null;
    const nombre = (inputs && inputs[0] ? inputs[0].value.trim() : '');
    const apellidos = (inputs && inputs[1] ? inputs[1].value.trim() : '');
    // Only advance if current row has some content
    if (!nombre && !apellidos) return;
    const nextIdx = idx + 1;
    while (tbody.querySelectorAll('tr').length <= nextIdx) addQuickEntryRow();
    const nextRow = tbody.querySelectorAll('tr')[nextIdx];
    const nextInputs = nextRow ? nextRow.querySelectorAll('input') : null;
    if (nextInputs && nextInputs[0]) {
      nextInputs[0].focus();
      nextInputs[0].select();
    }
  });
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
    const mode = document.getElementById('csvImportMode') ? document.getElementById('csvImportMode').value : 'append';
    const fd = new FormData();
    fd.append('csv', file);
    fd.append('actividad_id', String(ActivityPage.id));
    fd.append('mode', mode);
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

// Evaluations: contextual Admin section
function switchActivitySection(section) {
  const showEvaluations = section === 'evaluations';
  const participantsTab = document.getElementById('participants-tab');
  const evaluationsTab = document.getElementById('evaluations-tab');
  const participantsPanel = document.getElementById('participants-panel');
  const evaluationsPanel = document.getElementById('evaluations-panel');

  if (!participantsTab || !evaluationsTab || !participantsPanel || !evaluationsPanel) return;

  participantsTab.classList.toggle('active', !showEvaluations);
  participantsTab.setAttribute('aria-selected', showEvaluations ? 'false' : 'true');
  evaluationsTab.classList.toggle('active', showEvaluations);
  evaluationsTab.setAttribute('aria-selected', showEvaluations ? 'true' : 'false');
  participantsPanel.hidden = showEvaluations;
  evaluationsPanel.hidden = !showEvaluations;

  const nextHash = showEvaluations ? '#evaluaciones' : '';
  if (window.history && window.history.replaceState) {
    window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}${nextHash}`);
  }

  if (showEvaluations && !ActivityPage.evaluationsLoaded) loadEvaluations();
}

function evaluationApiMessage(result, fallback) {
  return result?.error?.message || result?.message || fallback;
}

async function evaluationApiRequest(url, options = {}) {
  const response = await fetch(url, options);
  let result;
  try {
    result = await response.json();
  } catch (error) {
    throw new Error('El servidor no devolvió una respuesta válida.');
  }
  if (!response.ok || !result.success) {
    const apiError = new Error(evaluationApiMessage(result, 'No se pudo completar la operación.'));
    apiError.fields = result?.error?.fields || {};
    throw apiError;
  }
  return result.data;
}

async function loadEvaluations() {
  const container = document.getElementById('evaluations-list');
  if (!container) return;
  container.innerHTML = `
    <div class="evaluation-list-skeleton" aria-label="Cargando evaluaciones">
      <span></span><span></span><span></span>
    </div>`;

  try {
    const data = await evaluationApiRequest(`${EvaluationAdminApi.list}?actividad_id=${encodeURIComponent(ActivityPage.id)}&include_archived=1`);
    ActivityPage.evaluations = Array.isArray(data.evaluaciones) ? data.evaluaciones : [];
    ActivityPage.evaluationsLoaded = true;
    renderEvaluations();
    updateEvaluationTabCount(ActivityPage.evaluations.length);
  } catch (error) {
    console.error('Error cargando evaluaciones de la actividad:', error);
    ActivityPage.evaluationsLoaded = false;
    container.innerHTML = `
      <div class="evaluation-empty-state evaluation-error-state">
        <strong>No se pudieron cargar las evaluaciones</strong>
        <p>${escapeHtml(error.message)}</p>
        <button class="btn btn-secondary" type="button" onclick="loadEvaluations()">Reintentar</button>
      </div>`;
  }
}

function updateEvaluationTabCount(count) {
  const badge = document.getElementById('evaluations-tab-count');
  if (!badge) return;
  badge.textContent = String(count);
  badge.hidden = count === 0;
}

function evaluationStateLabel(state) {
  return ({
    programada: 'Programada',
    pendiente: 'Pendiente',
    en_curso: 'En curso',
    en_curso_fuera_de_plazo: 'En curso · fuera de plazo',
    finalizada: 'Finalizada',
    fuera_de_plazo: 'Fuera de plazo',
    archivada: 'Archivada'
  })[state] || 'Sin estado';
}

function evaluationTypeLabel(type) {
  return ({
    entero: 'Número entero',
    decimal: 'Número decimal',
    duracion: 'Duración',
    texto_corto: 'Texto corto'
  })[type] || type || 'Dato';
}

function renderEvaluations() {
  const container = document.getElementById('evaluations-list');
  if (!container) return;

  if (!ActivityPage.evaluations.length) {
    container.innerHTML = `
      <div class="evaluation-empty-state">
        <strong>No hay evaluaciones</strong>
        <p>Crea la primera cuando tengas definido qué dato debe registrar el monitor.</p>
        <button class="btn btn-primary" type="button" onclick="openCreateEvaluationModal()">Nueva evaluación</button>
      </div>`;
    return;
  }

  const statePriority = {
    en_curso: 0,
    en_curso_fuera_de_plazo: 1,
    pendiente: 2,
    programada: 3,
    fuera_de_plazo: 4,
    finalizada: 5,
    archivada: 6
  };
  const sorted = ActivityPage.evaluations.slice().sort((a, b) => {
    const stateOrder = (statePriority[a.estado] ?? 99) - (statePriority[b.estado] ?? 99);
    if (stateOrder !== 0) return stateOrder;
    return String(b.fecha_inicio).localeCompare(String(a.fecha_inicio));
  });

  container.innerHTML = sorted.map(evaluation => {
    const field = evaluation.campos?.[0] || {};
    const coverage = evaluation.cobertura || {};
    const measured = Number(coverage.medidos || 0);
    const total = Number(coverage.total_participantes || 0);
    const hasSession = Boolean(evaluation.sesion?.id);
    const isArchived = evaluation.estado === 'archivada';
    const coverageText = hasSession
      ? `${measured} de ${total} registrados`
      : 'Todavía no realizada';
    const unitText = field.unidad ? ` · ${field.unidad}` : '';

    return `
      <article class="evaluation-row${isArchived ? ' is-archived' : ''}">
        <div class="evaluation-row-main">
          <div class="evaluation-row-heading">
            <h3>${escapeHtml(evaluation.nombre)}</h3>
            <span class="evaluation-state evaluation-state-${escapeHtml(evaluation.estado)}">${escapeHtml(evaluationStateLabel(evaluation.estado))}</span>
          </div>
          <p class="evaluation-period">${formatDateEs(evaluation.fecha_inicio)} → ${formatDateEs(evaluation.fecha_fin)}</p>
          <p class="evaluation-metadata">${escapeHtml(field.nombre || 'Dato sin nombre')} · ${escapeHtml(evaluationTypeLabel(field.tipo_dato))}${escapeHtml(unitText)} · ${escapeHtml(coverageText)}</p>
        </div>
        <div class="evaluation-row-actions">
          ${hasSession ? `<button class="btn btn-primary" type="button" onclick="openEvaluationResults(${Number(evaluation.id)})">Ver resultados</button>` : ''}
          <button class="btn btn-secondary" type="button" onclick="openEditEvaluationModal(${Number(evaluation.id)})">Editar</button>
          ${!isArchived ? `<button class="btn btn-secondary btn-subtle-danger" type="button" onclick="archiveEvaluation(${Number(evaluation.id)})">Archivar</button>` : ''}
        </div>
      </article>`;
  }).join('');
}

function clearEvaluationFormErrors() {
  document.querySelectorAll('#evaluationForm .field-error').forEach(element => { element.textContent = ''; });
  const formError = document.getElementById('evaluationFormError');
  if (formError) formError.textContent = '';
}

function setEvaluationFormError(field, message) {
  const fieldMap = {
    nombre: 'evaluationName-error',
    instrucciones: 'evaluationInstructions-error',
    fecha_inicio: 'evaluationDateStart-error',
    fecha_fin: 'evaluationDateEnd-error',
    'campo.nombre': 'evaluationFieldName-error',
    'campo.tipo_dato': 'evaluationDataType-error',
    'campo.unidad': 'evaluationUnit-error'
  };
  const target = document.getElementById(fieldMap[field] || '');
  if (target) target.textContent = message;
}

function resetEvaluationForm() {
  const form = document.getElementById('evaluationForm');
  if (form) form.reset();
  const idInput = document.getElementById('evaluationId');
  if (idInput) idInput.value = '';
  const typeInput = document.getElementById('evaluationDataType');
  if (typeInput) typeInput.value = 'entero';
  clearEvaluationFormErrors();
}

function openCreateEvaluationModal() {
  resetEvaluationForm();
  const title = document.getElementById('evaluationModalTitle');
  if (title) title.textContent = 'Nueva evaluación';
  const start = document.getElementById('evaluationDateStart');
  const end = document.getElementById('evaluationDateEnd');
  const activityStart = normalizeIsoDate(String(ActivityPage.ctx?.fecha_inicio || '').substring(0, 10));
  const activityEnd = normalizeIsoDate(String(ActivityPage.ctx?.fecha_fin || '').substring(0, 10));
  if (start) start.value = activityStart || todayIsoLocal();
  if (end) end.value = activityEnd || start?.value || todayIsoLocal();
  openModal('evaluationModal');
  document.getElementById('evaluationName')?.focus();
}

function openEditEvaluationModal(evaluationId) {
  const evaluation = ActivityPage.evaluations.find(item => Number(item.id) === Number(evaluationId));
  if (!evaluation) return;
  resetEvaluationForm();
  const field = evaluation.campos?.[0] || {};
  const values = {
    evaluationId: evaluation.id,
    evaluationName: evaluation.nombre || '',
    evaluationInstructions: evaluation.instrucciones || '',
    evaluationDateStart: evaluation.fecha_inicio || '',
    evaluationDateEnd: evaluation.fecha_fin || '',
    evaluationFieldName: field.nombre || '',
    evaluationDataType: field.tipo_dato || 'entero',
    evaluationUnit: field.unidad || ''
  };
  Object.entries(values).forEach(([id, value]) => {
    const element = document.getElementById(id);
    if (element) element.value = value;
  });
  const title = document.getElementById('evaluationModalTitle');
  if (title) title.textContent = 'Editar evaluación';
  openModal('evaluationModal');
  document.getElementById('evaluationName')?.focus();
}

async function handleEvaluationSubmit(event) {
  event.preventDefault();
  clearEvaluationFormErrors();
  const evaluationId = Number(document.getElementById('evaluationId')?.value || 0);
  const payload = {
    actividad_id: Number(ActivityPage.id),
    nombre: String(document.getElementById('evaluationName')?.value || '').trim(),
    instrucciones: String(document.getElementById('evaluationInstructions')?.value || '').trim() || null,
    fecha_inicio: document.getElementById('evaluationDateStart')?.value || '',
    fecha_fin: document.getElementById('evaluationDateEnd')?.value || '',
    campo: {
      nombre: String(document.getElementById('evaluationFieldName')?.value || '').trim(),
      tipo_dato: document.getElementById('evaluationDataType')?.value || '',
      unidad: String(document.getElementById('evaluationUnit')?.value || '').trim() || null
    }
  };
  if (evaluationId) payload.evaluacion_id = evaluationId;

  const button = document.getElementById('saveEvaluationBtn');
  setBtnLoading(button, true);
  try {
    await evaluationApiRequest(evaluationId ? EvaluationAdminApi.update : EvaluationAdminApi.create, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    closeModal('evaluationModal');
    ActivityPage.evaluationsLoaded = false;
    await loadEvaluations();
    showNotification(evaluationId ? 'Evaluación actualizada' : 'Evaluación creada', 'success');
  } catch (error) {
    console.error('Error guardando evaluación:', error);
    Object.entries(error.fields || {}).forEach(([field, message]) => setEvaluationFormError(field, message));
    const formError = document.getElementById('evaluationFormError');
    if (formError) formError.textContent = error.message;
  } finally {
    setBtnLoading(button, false);
  }
}

async function archiveEvaluation(evaluationId) {
  const evaluation = ActivityPage.evaluations.find(item => Number(item.id) === Number(evaluationId));
  if (!evaluation) return;
  if (!window.confirm(`¿Archivar “${evaluation.nombre}”? Seguirá disponible en el histórico de datos.`)) return;

  try {
    await evaluationApiRequest(EvaluationAdminApi.archive, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ evaluacion_id: Number(evaluationId) })
    });
    ActivityPage.evaluationsLoaded = false;
    await loadEvaluations();
    showNotification('Evaluación archivada', 'success');
  } catch (error) {
    console.error('Error archivando evaluación:', error);
    showNotification(error.message, 'error');
  }
}

async function openEvaluationResults(evaluationId) {
  const container = document.getElementById('evaluation-results-list');
  if (!container) return;
  container.innerHTML = `
    <div class="evaluation-list-skeleton" aria-label="Cargando resultados">
      <span></span><span></span><span></span>
    </div>`;
  openModal('evaluationResultsModal');

  try {
    const data = await evaluationApiRequest(`${EvaluationAdminApi.detail}?evaluacion_id=${encodeURIComponent(evaluationId)}`);
    ActivityPage.currentEvaluationDetail = data;
    renderEvaluationResults(data);
  } catch (error) {
    console.error('Error cargando resultados de evaluación:', error);
    container.innerHTML = `
      <div class="evaluation-empty-state evaluation-error-state">
        <strong>No se pudieron cargar los resultados</strong>
        <p>${escapeHtml(error.message)}</p>
      </div>`;
  }
}

function renderEvaluationResults(data) {
  const evaluation = data.evaluacion || {};
  const participants = Array.isArray(data.participantes) ? data.participantes : [];
  const title = document.getElementById('evaluationResultsTitle');
  const meta = document.getElementById('evaluationResultsMeta');
  const coverage = document.getElementById('evaluationResultsCoverage');
  const reopenButton = document.getElementById('reopenEvaluationBtn');
  const container = document.getElementById('evaluation-results-list');
  const coverageData = evaluation.cobertura || {};

  if (title) title.textContent = evaluation.nombre || 'Resultados';
  if (meta) {
    const realDate = evaluation.sesion?.fecha_realizacion
      ? `Realizada el ${formatDateEs(evaluation.sesion.fecha_realizacion)}`
      : `Disponible del ${formatDateEs(evaluation.fecha_inicio)} al ${formatDateEs(evaluation.fecha_fin)}`;
    meta.textContent = realDate;
  }
  if (coverage) {
    coverage.textContent = `${Number(coverageData.medidos || 0)} medidos · ${Number(coverageData.sin_evaluar || 0)} sin evaluar · ${Number(coverageData.total_participantes || 0)} participantes`;
  }
  if (reopenButton) reopenButton.hidden = evaluation.sesion?.estado !== 'finalizada';
  if (!container) return;

  if (!evaluation.sesion?.id) {
    container.innerHTML = `
      <div class="evaluation-empty-state">
        <strong>Todavía no hay una realización</strong>
        <p>Los resultados aparecerán aquí cuando el monitor empiece la evaluación.</p>
      </div>`;
    return;
  }
  if (!participants.length) {
    container.innerHTML = `
      <div class="evaluation-empty-state">
        <strong>No hay resultados guardados</strong>
        <p>La realización existe, pero todavía no contiene participantes.</p>
      </div>`;
    return;
  }

  container.innerHTML = participants.map(participant => {
    const result = participant.resultados?.[0] || {};
    const isText = result.tipo_dato === 'texto_corto';
    const disabled = participant.inscripcion_eliminada ? 'disabled' : '';
    const value = isText ? (result.valor_texto ?? '') : (result.valor_numero ?? '');
    const qualifier = result.calificador || 'exacto';
    const step = result.tipo_dato === 'entero' ? '1' : '0.001';
    return `
      <div class="evaluation-results-row" data-inscrito-id="${participant.inscrito_id ?? ''}" data-sesion-id="${Number(evaluation.sesion.id)}" data-campo-id="${Number(result.campo_id)}" data-tipo-dato="${escapeHtml(result.tipo_dato || '')}">
        <div class="evaluation-participant">
          <strong>${escapeHtml(`${participant.apellidos || ''}, ${participant.nombre || ''}`.replace(/^,\s*/, ''))}</strong>
          <span>${participant.inscripcion_eliminada ? 'Inscripción eliminada · histórico conservado' : escapeHtml(result.campo_nombre || '')}</span>
        </div>
        <div class="evaluation-result-controls">
          ${!isText ? `
            <label class="evaluation-qualifier-label">
              <span class="sr-only">Calificador</span>
              <select class="evaluation-result-qualifier" ${disabled}>
                <option value="exacto"${qualifier === 'exacto' ? ' selected' : ''}>=</option>
                <option value="mayor_que"${qualifier === 'mayor_que' ? ' selected' : ''}>&gt;</option>
                <option value="menor_que"${qualifier === 'menor_que' ? ' selected' : ''}>&lt;</option>
              </select>
            </label>` : ''}
          <label class="evaluation-result-label">
            <span class="sr-only">Resultado de ${escapeHtml(`${participant.nombre || ''} ${participant.apellidos || ''}`.trim())}</span>
            <input class="evaluation-result-input" type="${isText ? 'text' : 'number'}" value="${escapeHtml(String(value))}" ${isText ? 'maxlength="255"' : `step="${step}"`} ${disabled}>
          </label>
          ${result.unidad ? `<span class="evaluation-result-unit">${escapeHtml(result.unidad)}</span>` : ''}
          <button class="btn btn-secondary evaluation-result-save" type="button" onclick="saveAdminEvaluationResult(${participant.inscrito_id ?? 'null'}, this)" ${disabled}>Guardar</button>
        </div>
        <p class="evaluation-result-status" aria-live="polite">${result.estado === 'sin_evaluar' ? 'Sin evaluar' : 'Guardado'}</p>
      </div>`;
  }).join('');
}

async function saveAdminEvaluationResult(inscritoId, button) {
  const row = button?.closest('.evaluation-results-row');
  if (!row || !inscritoId) return;
  const input = row.querySelector('.evaluation-result-input');
  const qualifier = row.querySelector('.evaluation-result-qualifier');
  const status = row.querySelector('.evaluation-result-status');
  const value = String(input?.value ?? '').trim();
  const isText = row.dataset.tipoDato === 'texto_corto';
  const payload = {
    sesion_id: Number(row.dataset.sesionId),
    campo_id: Number(row.dataset.campoId),
    inscrito_id: Number(inscritoId),
    estado: value === '' ? 'sin_evaluar' : 'medido'
  };
  if (value !== '') {
    if (isText) payload.valor_texto = value;
    else {
      payload.valor_numero = value;
      payload.calificador = qualifier?.value || 'exacto';
    }
  }

  button.disabled = true;
  if (status) status.textContent = 'Guardando…';
  try {
    const data = await evaluationApiRequest(EvaluationAdminApi.updateResult, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    if (status) status.textContent = payload.estado === 'sin_evaluar' ? 'Sin evaluar · guardado' : 'Guardado';
    const coverage = document.getElementById('evaluationResultsCoverage');
    const counts = data.cobertura || {};
    if (coverage) coverage.textContent = `${Number(counts.medidos || 0)} medidos · ${Number(counts.sin_evaluar || 0)} sin evaluar · ${Number(counts.total_participantes || 0)} participantes`;
    ActivityPage.evaluationsLoaded = false;
  } catch (error) {
    console.error('Error corrigiendo resultado de evaluación:', error);
    if (status) status.textContent = error.message;
  } finally {
    button.disabled = false;
  }
}

async function reopenEvaluation() {
  const sessionId = Number(ActivityPage.currentEvaluationDetail?.evaluacion?.sesion?.id || 0);
  if (!sessionId) return;
  if (!window.confirm('¿Reabrir esta evaluación? El monitor podrá modificarla de nuevo.')) return;

  const button = document.getElementById('reopenEvaluationBtn');
  if (button) button.disabled = true;
  try {
    const data = await evaluationApiRequest(EvaluationAdminApi.reopen, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ sesion_id: sessionId })
    });
    ActivityPage.currentEvaluationDetail.evaluacion = data.evaluacion;
    if (button) button.hidden = true;
    ActivityPage.evaluationsLoaded = false;
    await loadEvaluations();
    showNotification('Evaluación reabierta para el monitor', 'success');
  } catch (error) {
    console.error('Error reabriendo evaluación:', error);
    showNotification(error.message, 'error');
  } finally {
    if (button) button.disabled = false;
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

function confirmDeleteParticipant(id) {
  // close dropdown if open
  const menu = document.getElementById(`participant-dropdown-${id}`);
  if (menu) {
    menu.classList.remove('show', 'open', 'dropup');
    menu.style.top = '';
    menu.style.left = '';
    menu.style.right = '';
    menu.style.bottom = '';
  }
  const p = (ActivityPage.participants || []).find(x => String(x.id) === String(id));
  const nombre = p ? `${p.apellidos || ''}, ${p.nombre || ''}`.trim() : '';
  const ok = window.confirm(`¿Eliminar al participante${nombre ? ' "' + nombre + '"' : ''}?\n\nSe eliminará también su historial de asistencia para esta actividad.\n\nEsta acción no se puede deshacer.`);
  if (ok) {
    deleteParticipant(id);
  }
}

async function deleteParticipant(id) {
  try {
    const resp = await fetch('api/participantes/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: Number(id) })
    });
    const result = await resp.json();
    if (result.success) {
      await loadParticipants();
      showNotification('Participante eliminado', 'success');
    } else {
      showNotification(result.message || 'No se pudo eliminar el participante', 'error');
    }
  } catch (e) {
    console.error(e);
    showNotification('Error eliminando participante', 'error');
  }
}

function confirmDeleteAllParticipants() {
  const count = (ActivityPage.participants || []).length;
  if (!count) { showNotification('No hay participantes para eliminar', 'info'); return; }
  const ok = window.confirm(`¿Eliminar el listado completo de participantes (${count})?\n\nSe eliminarán también todas las asistencias asociadas a esta actividad.\n\nEsta acción no se puede deshacer.`);
  if (ok) deleteAllParticipants();
}

async function deleteAllParticipants() {
  try {
    const resp = await fetch('api/participantes/delete_by_activity.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ actividad_id: Number(ActivityPage.id) })
    });
    const result = await resp.json();
    if (result.success) {
      await loadParticipants();
      showNotification('Listado eliminado correctamente', 'success');
    } else {
      showNotification(result.message || 'No se pudo eliminar el listado', 'error');
    }
  } catch (e) {
    console.error(e);
    showNotification('Error eliminando el listado', 'error');
  }
}

// Expose handlers globally for inline onclick
if (typeof window !== 'undefined') {
  window.openAttendanceRangeModal = openAttendanceRangeModal;
  window.resetAttendanceRangeToDefault = resetAttendanceRangeToDefault;
  window.confirmDeleteAllParticipants = confirmDeleteAllParticipants;
  window.deleteAllParticipants = deleteAllParticipants;
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
