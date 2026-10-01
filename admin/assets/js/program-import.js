/* Full course imports use a read-only preview, followed by a fingerprint-bound save. */
const ProgramImport = { preview: null, payload: null, revision: 0, request: 0 };

function getProgramImportPurpose() {
  return document.getElementById('programImportPurpose').value;
}

function programImportSaveLabel() {
  return ProgramImport.preview?.nueva_edicion ? 'Crear edición' : 'Guardar datos';
}

function updateProgramImportPurpose() {
  const help = {
    append: 'Añade actividades y participantes al curso actual. Quienes no aparezcan en el archivo se mantienen. Si es la primera carga del centro, se crea su primera edición.',
    complete: 'Actualiza el curso actual con su listado completo. Las actividades y participantes que no aparezcan se darán de baja en esa edición, conservando su historial.',
    new: 'Crea una edición independiente para el nuevo curso. Las ediciones anteriores se conservan y las fechas se calculan con las actividades del archivo.',
  };
  if (getBulkImportMode() === 'aforo') {
    help.complete = 'Actualiza el listado completo de clases de aforo del curso actual. Las clases de aforo ausentes se retiran, conservando su historial. Las actividades con participantes se mantienen.';
  }
  document.getElementById('programImportPurposeHelp').textContent = help[getProgramImportPurpose()];
}

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
  const action = result.nueva_edicion
    ? (result.ediciones.length ? 'Crear nueva edición' : 'Crear primera edición')
    : (ProgramImport.payload.scope === 'append' ? 'Añadir datos' : 'Actualizar listado completo');
  heading.textContent = result.necesita_edicion ? 'Elige la edición que vas a actualizar' : `${action} · ${result.nombre_edicion}`;
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
    note.textContent = result.nueva_edicion
      ? 'Se crea una edición independiente. Las actividades, participantes, asistencias y evaluaciones de las ediciones anteriores se conservan.'
      : (ProgramImport.payload.scope === 'append'
        ? 'Esta carga añade datos a la edición indicada. Las actividades y participantes que no aparecen en el archivo se mantienen.'
        : (getBulkImportMode() === 'aforo'
          ? 'Este archivo sustituye el listado de clases de aforo de la edición indicada. Las clases de aforo ausentes se retiran, conservando su historial. Las otras ediciones se mantienen.'
          : 'Este archivo sustituye el listado completo de la edición indicada. Se darán de baja las actividades y participantes ausentes, conservando su historial. Las otras ediciones se mantienen.'));
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
  // Offer another existing destination only when there is more than one edition.
  if (result.ediciones?.length > 1 && getProgramImportPurpose() !== 'new') {
    const details = document.createElement(result.necesita_edicion ? 'div' : 'details');
    if (!result.necesita_edicion) {
      const summary = document.createElement('summary'); summary.textContent = 'Elegir otra edición'; details.append(summary);
    }
    const label = document.createElement('label');
    label.htmlFor = 'programImportEdition'; label.textContent = 'Edición de destino'; details.append(label);
    const select = document.createElement('select');
    select.id = 'programImportEdition'; select.className = 'form-input';
    if (result.necesita_edicion) select.append(new Option('Selecciona una edición', ''));
    result.ediciones.forEach(edition => select.append(new Option(`${edition.nombre} · ${programDateLabel(edition.fecha_inicio, 'sin inicio definido')} → ${programDateLabel(edition.fecha_fin)}`, String(edition.id))));
    select.value = result.necesita_edicion ? '' : String(result.edicion_id ?? 0);
    select.addEventListener('change', () => { if (select.value !== '') reviewProgramImport(Number(select.value)); });
    details.append(select); panel.append(details);
  }
  document.getElementById('programImportSave').hidden = Boolean(result.necesita_edicion);
  document.getElementById('programImportSave').textContent = programImportSaveLabel();
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
  const purpose = getProgramImportPurpose();
  const payload = { centro_id: Number(center), modo_importacion: getBulkImportMode(), scope: purpose === 'append' ? 'append' : 'complete', destino_edicion: purpose === 'new' ? 'nueva' : 'actual', rows };
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
    button.textContent = programImportSaveLabel();
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  const modal = document.getElementById('bulkImportModal');
  modal.addEventListener('input', event => { if (!event.target.closest('#programImportReview') && !['programImportFile', 'programImportSheet'].includes(event.target.id)) invalidateProgramPreview(); });
  modal.addEventListener('change', event => { if (!event.target.closest('#programImportReview') && !['programImportFile', 'programImportSheet'].includes(event.target.id)) invalidateProgramPreview(); });
  document.getElementById('programImportFile').addEventListener('change', () => loadProgramFile());
  document.getElementById('programImportSheet').addEventListener('change', event => { if (event.target.value) loadProgramFile(event.target.value); });
  document.getElementById('programImportPurpose').addEventListener('change', updateProgramImportPurpose);
  updateProgramImportPurpose();
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
