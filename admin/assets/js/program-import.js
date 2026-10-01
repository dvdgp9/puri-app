/* Full course imports use a read-only preview, followed by a fingerprint-bound save. */
const ProgramImport = { preview: null, payload: null, revision: 0, request: 0 };

function invalidateProgramPreview() {
  ProgramImport.revision++;
  ProgramImport.preview = null;
  ProgramImport.payload = null;
  document.getElementById('programImportReview')?.setAttribute('hidden', '');
  const save = document.getElementById('programImportSave');
  if (save) save.hidden = true;
  const review = document.getElementById('bulkImportBtn');
  if (review) review.hidden = false;
}

function collectProgramRows() {
  const fields = ['nombre', 'apellidos', 'instalacion', 'actividad', 'grupo', 'fecha_inicio', 'fecha_fin', 'hora_inicio', 'hora_fin', 'dias_semana', 'tipo_control'];
  const selectors = ['nombre', 'apellidos', 'instalacion', 'actividad', 'grupo', 'fecha-inicio', 'fecha-fin', 'hora-inicio', 'hora-fin', 'dias', 'tipo'];
  return Array.from(document.querySelectorAll('#bulkImportBody tr')).map(row => Object.fromEntries(fields.map((field, index) => [field, row.querySelector(`.bulk-${selectors[index]}`)?.value.trim() || ''])))
    .filter(row => Object.values(row).some(value => value !== ''));
}

function fillProgramRows(rows) {
  const columns = [['nombre', 'nombre', 'bulk-participant-column'], ['apellidos', 'apellidos', 'bulk-participant-column'],
    ['instalacion', 'instalacion', ''], ['actividad', 'actividad', ''], ['grupo', 'grupo', ''],
    ['fecha_inicio', 'fecha-inicio', ''], ['fecha_fin', 'fecha-fin', ''], ['hora_inicio', 'hora-inicio', ''],
    ['hora_fin', 'hora-fin', ''], ['dias_semana', 'dias', ''], ['tipo_control', 'tipo', 'bulk-type-column']];
  const tbody = document.getElementById('bulkImportBody');
  tbody.innerHTML = rows.map(row => `<tr>${columns.map(([key, cls, column]) => `<td class="${column}"><input type="text" class="bulk-${cls}" value="${escapeHtml(String(row[key] || ''))}"></td>`).join('')}<td><button type="button" class="btn-remove-row" onclick="removeBulkImportRow(this)" aria-label="Eliminar fila">&times;</button></td></tr>`).join('');
  invalidateProgramPreview();
  updateBulkImportRowCount();
}

async function loadProgramFile(sheet = null) {
  // A new file must never leave the previous roster available to save.
  fillProgramRows([]);
  document.getElementById('programImportSheet').hidden = true;
  const file = document.getElementById('programImportFile').files[0];
  if (!file) return;
  const status = document.getElementById('programImportFileStatus');
  const error = document.getElementById('bulkImportError');
  const version = ++ProgramImport.request;
  const revision = ProgramImport.revision;
  status.textContent = 'Leyendo archivo…';
  error.textContent = '';
  try {
    const form = new FormData();
    form.append('archivo', file);
    if (sheet !== null) form.append('hoja', sheet);
    const response = await fetch('api/program_file.php', { method: 'POST', body: form });
    const result = await response.json();
    if (version !== ProgramImport.request) return;
    if (revision !== ProgramImport.revision) { status.textContent = 'Lectura cancelada porque se ha editado el listado.'; return; }
    if (!result.success) throw new Error(result.message);
    const choice = document.getElementById('programImportSheet');
    if (result.necesita_hoja) {
      choice.replaceChildren(new Option('Selecciona la hoja con el listado', ''), ...result.hojas.map(name => new Option(name, name)));
      choice.hidden = false;
      status.textContent = file.name;
      return;
    }
    choice.hidden = true;
    fillProgramRows(result.rows);
    status.textContent = `${file.name} · ${result.rows.length} filas`;
  } catch (e) {
    if (version !== ProgramImport.request) return;
    status.textContent = '';
    error.textContent = e.message || 'No se pudo leer el archivo.';
  }
}

function programDateLabel(value, missing = 'sin fin definido') {
  if (!value) return missing;
  const [year, month, day] = value.split('-');
  return `${day}/${month}/${year}`;
}

function renderProgramReview(result) {
  const panel = document.getElementById('programImportReview');
  panel.replaceChildren();
  panel.hidden = false;
  const heading = document.createElement('h4');
  heading.textContent = result.necesita_edicion ? 'Elige la edición que vas a actualizar' : `${result.nueva_edicion ? 'Nueva edición' : 'Actualizar'} · ${result.nombre_edicion}`;
  panel.append(heading);
  const period = document.createElement('p');
  period.textContent = `${programDateLabel(result.periodo.fecha_inicio)} → ${programDateLabel(result.periodo.fecha_fin)} · Período calculado a partir de las actividades.`;
  panel.append(period);
  if (!result.necesita_edicion) {
    const stats = result.stats;
    const counts = document.createElement('div');
    counts.className = 'program-review-counts';
    const descriptions = [
      ['instalaciones_creadas', 'instalaciones nuevas'], ['instalaciones_reutilizadas', 'instalaciones que continúan'],
      ['instalaciones_reactivadas', 'instalaciones reactivadas'], ['actividades_creadas', 'actividades nuevas'],
      ['actividades_reutilizadas', 'actividades que continúan'], ['actividades_reactivadas', 'actividades recuperadas'],
      ['participantes_creados', 'inscripciones nuevas'], ['participantes_mantenidos', 'inscripciones que continúan'],
      ['participantes_reactivados', 'reincorporaciones'], ['actividades_retiradas', 'actividades que dejan de aparecer'],
      ['participantes_desactivados', 'inscripciones que se darán de baja'],
    ];
    descriptions.filter(([key]) => stats[key] > 0).forEach(([key, label]) => {
      const item = document.createElement('span');
      item.textContent = `${stats[key]} ${label}`;
      counts.append(item);
    });
    panel.append(counts);
    const note = document.createElement('p');
    note.textContent = 'Las ediciones anteriores, asistencias y evaluaciones se conservan. Las bajas afectan únicamente a la edición que estás actualizando.';
    panel.append(note);
    if (getBulkImportMode() === 'aforo') {
      const capacityNote = document.createElement('p');
      capacityNote.textContent = 'Este listado actualiza solo las clases de aforo. Las actividades con participantes se conservan.';
      panel.append(capacityNote);
    }
    if (result.retirement_names?.length || result.deactivation_names?.length) {
      const detail = document.createElement('details');
      const summary = document.createElement('summary');
      summary.textContent = 'Ver actividades y personas que dejan de aparecer';
      detail.append(summary);
      const list = document.createElement('ul');
      list.className = 'program-review-changes';
      [...(result.retirement_names || []), ...(result.deactivation_names || [])].forEach(name => {
        const item = document.createElement('li'); item.textContent = name; list.append(item);
      });
      detail.append(list);
      if (stats.actividades_retiradas > 8 || stats.participantes_desactivados > 8) {
        const limit = document.createElement('p');
        limit.textContent = 'Se muestran hasta ocho actividades y ocho personas; los totales aparecen arriba.';
        detail.append(limit);
      }
      panel.append(detail);
    }
  }
  // An unobtrusive override for overlapping courses, corrections or a deliberate new edition.
  if (result.ediciones?.length) {
    const details = document.createElement(result.necesita_edicion ? 'div' : 'details');
    if (!result.necesita_edicion) {
      const summary = document.createElement('summary'); summary.textContent = 'Cambiar la edición detectada'; details.append(summary);
    }
    const label = document.createElement('label');
    label.htmlFor = 'programImportEdition'; label.textContent = 'Edición de destino'; details.append(label);
    const select = document.createElement('select');
    select.id = 'programImportEdition'; select.className = 'form-input';
    select.append(new Option('Selecciona una edición', ''), new Option('Crear una nueva edición', '0'));
    result.ediciones.forEach(edition => select.append(new Option(`${edition.nombre} · ${programDateLabel(edition.fecha_inicio, 'sin inicio definido')} → ${programDateLabel(edition.fecha_fin)}`, String(edition.id))));
    select.value = result.necesita_edicion ? '' : String(result.edicion_id ?? 0);
    select.addEventListener('change', () => { if (select.value !== '') reviewProgramImport(Number(select.value)); });
    details.append(select); panel.append(details);
  }
  document.getElementById('programImportSave').hidden = Boolean(result.necesita_edicion);
  document.getElementById('bulkImportBtn').hidden = !result.necesita_edicion;
}

async function reviewProgramImport(edition = null) {
  const center = document.getElementById('bulkImportCenter').value;
  const error = document.getElementById('bulkImportError');
  const button = document.getElementById('bulkImportBtn');
  if (!center) { showFieldError('bulkImportCenter', 'Selecciona un centro'); return; }
  const rows = collectProgramRows();
  if (!rows.length) { error.textContent = 'Carga el listado o pega los datos desde Excel.'; return; }
  invalidateProgramPreview();
  const revision = ProgramImport.revision;
  const request = ++ProgramImport.request;
  button.disabled = true;
  button.classList.add('loading');
  error.textContent = '';
  const payload = { centro_id: Number(center), modo_importacion: getBulkImportMode(), scope: document.getElementById('programImportComplete').checked ? 'complete' : 'append', rows };
  if (edition !== null) payload.edicion_id = edition;
  try {
    const response = await fetch('api/bulk_import.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ...payload, preview: true }) });
    const result = await response.json();
    if (revision !== ProgramImport.revision || request !== ProgramImport.request) return;
    if (!result.success) throw new Error(result.message);
    ProgramImport.preview = result;
    ProgramImport.payload = payload;
    renderProgramReview(result);
  } catch (e) {
    if (request === ProgramImport.request) error.textContent = e.message || 'No se pudo revisar el listado.';
  } finally { button.disabled = false; button.classList.remove('loading'); }
}

async function saveProgramImport() {
  if (!ProgramImport.preview || !ProgramImport.payload || ProgramImport.preview.necesita_edicion) return;
  const button = document.getElementById('programImportSave');
  const error = document.getElementById('bulkImportError');
  button.disabled = true;
  document.getElementById('bulkImportBtn').disabled = true;
  button.textContent = 'Guardando…';
  // Freeze the reviewed form while the transaction is being applied.
  const fields = [...document.querySelectorAll('#bulkImportModal .modal-body input, #bulkImportModal .modal-body select, #bulkImportModal .modal-body button')];
  const disabledBefore = fields.map(field => field.disabled);
  fields.forEach(field => { field.disabled = true; });
  try {
    const response = await fetch('api/bulk_import.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ...ProgramImport.payload, preview: false, expected_fingerprint: ProgramImport.preview.fingerprint }) });
    const result = await response.json();
    if (!result.success) {
      invalidateProgramPreview();
      throw new Error(result.message);
    }
    showNotification(result.message, 'success');
    closeBulkImportModal();
    await Promise.all([loadCenters(), loadStats()]);
  } catch (e) { error.textContent = e.message || 'No se pudo guardar el listado.'; }
  finally {
    fields.forEach((field, index) => { field.disabled = disabledBefore[index]; });
    button.disabled = false;
    document.getElementById('bulkImportBtn').disabled = false;
    button.textContent = 'Guardar edición';
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  const modal = document.getElementById('bulkImportModal');
  modal.addEventListener('input', event => { if (!event.target.closest('#programImportReview') && !['programImportFile', 'programImportSheet'].includes(event.target.id)) invalidateProgramPreview(); });
  modal.addEventListener('change', event => { if (!event.target.closest('#programImportReview') && !['programImportFile', 'programImportSheet'].includes(event.target.id)) invalidateProgramPreview(); });
  document.getElementById('programImportFile').addEventListener('change', () => loadProgramFile());
  document.getElementById('programImportSheet').addEventListener('change', event => { if (event.target.value) loadProgramFile(event.target.value); });
  const params = new URLSearchParams(window.location.search);
  if (params.get('importar_programa') === '1') {
    showBulkImportModal();
    await loadBulkImportCenters();
    const center = bulkImportCenters.find(item => String(item.id) === params.get('centro_id'));
    if (center) {
      document.getElementById('bulkImportCenter').value = center.id;
      document.getElementById('bulkImportCenterSearch').value = center.nombre;
    }
  }
});
