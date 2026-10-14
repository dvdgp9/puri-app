'use strict';

const Sabadell = { draft: null, groups: [], editions: [], preview: null, payload: null, busy: false };
const sabadellEl = id => document.getElementById(id);
const sabadellEscape = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));

function sabadellInvalidate() {
  Sabadell.preview = null;
  Sabadell.payload = null;
  sabadellEl('sabadellReview').hidden = true;
  sabadellEl('sabadellSave').hidden = true;
  sabadellEl('sabadellReviewButton').hidden = false;
}

function sabadellBase() {
  return { centro_id: Number(sabadellEl('sabadellCenter').value), destination: sabadellEl('sabadellDestination').value,
    edicion_id: sabadellEl('sabadellEdition').value || null, draft_id: Sabadell.draft,
    fecha_inicio: sabadellEl('sabadellStart').value, fecha_fin: sabadellEl('sabadellEnd').value };
}

async function sabadellRequest(payload) {
  const response = await fetch('api/sabadell_import.php', { method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ...payload, csrf: sabadellEl('sabadellApp').dataset.csrf }) });
  const result = await response.json();
  if (!response.ok || !result.success) throw new Error(result.message || 'No se pudo completar la operación.');
  return result;
}

async function sabadellRun(job) {
  if (Sabadell.busy) return;
  Sabadell.busy = true;
  sabadellEl('sabadellError').textContent = '';
  const controls = [...sabadellEl('sabadellForm').querySelectorAll('input, select, button')];
  const disabled = controls.map(el => el.disabled);
  controls.forEach(el => { el.disabled = true; });
  try { await job(); }
  catch (error) { sabadellEl('sabadellError').textContent = error.message || 'No se pudo completar la operación.'; }
  finally {
    controls.forEach((el, i) => { if (el.isConnected) el.disabled = disabled[i]; });
    Sabadell.busy = false;
    sabadellUpdateDates();
  }
}

function sabadellClearDraft() {
  sabadellInvalidate();
  Sabadell.draft = null;
  Sabadell.groups = [];
  sabadellEl('sabadellGroups').replaceChildren();
  sabadellEl('sabadellEditor').hidden = true;
  sabadellEl('sabadellFile').value = '';
  sabadellEl('sabadellStatus').textContent = '';
}

function sabadellUpdateDates() {
  const renewal = sabadellEl('sabadellDestination').value === 'nueva';
  sabadellEl('sabadellEdition').disabled = renewal || !Sabadell.editions.length;
  sabadellEl('sabadellScopeHelp').textContent = renewal
    ? 'Se creará un curso independiente. Los cursos anteriores conservarán sus datos.'
    : 'Se actualizan los participantes de los grupos incluidos. Los grupos ausentes del PDF se mantienen.';
  const edition = renewal ? null : Sabadell.editions.find(e => String(e.id) === sabadellEl('sabadellEdition').value);
  sabadellEl('sabadellStart').required = !edition;
}

async function sabadellLoadCenter() {
  sabadellClearDraft();
  Sabadell.editions = [];
  sabadellEl('sabadellEdition').replaceChildren();
  sabadellEl('sabadellStart').value = '';
  sabadellEl('sabadellEnd').value = '';
  if (!sabadellEl('sabadellCenter').value) return;
  const result = await sabadellRequest({ ...sabadellBase(), operation: 'context' });
  Sabadell.editions = result.ediciones;
  sabadellEl('sabadellEdition').replaceChildren(...result.ediciones.map(e => new Option(`${e.nombre} · ${e.fecha_inicio || 'sin inicio'} — ${e.fecha_fin || 'sin fin'}`, String(e.id))));
  if (!result.ediciones.length) sabadellEl('sabadellEdition').append(new Option('Primera edición del centro', ''));
  sabadellEl('sabadellEdition').value = result.edicion_id ? String(result.edicion_id) : '';
  sabadellSetPeriod();
}

function sabadellSetPeriod() {
  const edition = sabadellEl('sabadellDestination').value === 'nueva' ? null : Sabadell.editions.find(e => String(e.id) === sabadellEl('sabadellEdition').value);
  sabadellEl('sabadellStart').value = edition?.fecha_inicio || '';
  sabadellEl('sabadellEnd').value = edition?.fecha_fin || '';
}

async function sabadellLoadContext() {
  sabadellInvalidate();
  sabadellEl('sabadellEditor').hidden = true;
  Sabadell.groups = [];
  if (!Sabadell.draft) return;
  const result = await sabadellRequest({ ...sabadellBase(), operation: 'context' });
  Sabadell.groups = result.groups;
  sabadellRenderGroups();
  sabadellEl('sabadellEditor').hidden = false;
}

async function sabadellUpload() {
  const file = sabadellEl('sabadellFile').files[0];
  sabadellInvalidate();
  Sabadell.draft = null;
  Sabadell.groups = [];
  sabadellEl('sabadellEditor').hidden = true;
  if (!file) { sabadellEl('sabadellStatus').textContent = ''; return; }
  if (!sabadellEl('sabadellCenter').value) throw new Error('Selecciona el centro antes de subir el PDF.');
  if (!/\.pdf$/i.test(file.name) || file.size > 5 * 1024 * 1024) throw new Error('Selecciona un PDF de hasta 5 MB.');
  sabadellEl('sabadellStatus').textContent = 'Leyendo el PDF…';
  const form = new FormData();
  form.append('archivo', file);
  form.append('centro_id', sabadellEl('sabadellCenter').value);
  form.append('csrf', sabadellEl('sabadellApp').dataset.csrf);
  let result;
  try {
    const response = await fetch('api/sabadell_file.php', { method: 'POST', body: form });
    result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || 'No se pudo leer el PDF.');
  } catch (error) { sabadellEl('sabadellStatus').textContent = 'El PDF no se ha cargado.'; throw error; }
  Sabadell.draft = result.draft_id;
  await sabadellLoadContext();
  sabadellEl('sabadellStatus').textContent = `${file.name} · ${Sabadell.groups.length} grupos · ${result.source_count} filas${result.source_date ? ` · documento del ${result.source_date}` : ''}`;
}

function sabadellInput(field, label, value, type = 'text', readonly = false) {
  return `<div class="form-group"><label>${sabadellEscape(label)}<input class="form-input" data-field="${field}" type="${type}" value="${sabadellEscape(value)}" ${readonly ? 'readonly' : ''}></label></div>`;
}

function sabadellRenderGroups() {
  const html = Sabadell.groups.map(group => {
    const counts = new Map();
    group.participants.forEach(p => counts.set(p.original, (counts.get(p.original) || 0) + 1));
    const duplicate = group.participants.some(p => counts.get(p.original) > 1);
    const candidate = group.candidates.find(c => Number(c.id) === Number(group.target_id));
    const view = group.action === 'reuse' && candidate ? { ...group, ...candidate, actividad: candidate.nombre } : group;
    return `<details class="sabadell-group" data-group="${sabadellEscape(group.id)}" id="sabadell-${sabadellEscape(group.id)}">
      <summary>${sabadellEscape(group.grupo)} · ${sabadellEscape(group.actividad)} · ${group.participants.length} ${group.participants.length === 1 ? 'fila' : 'filas'} <span class="sabadell-source">Página ${group.page}${duplicate ? ' · nombres repetidos' : ''}</span></summary>
      <p class="sabadell-source">PDF: ${sabadellEscape(group.instalacion)} · ${sabadellEscape(group.dias_semana)} · ${sabadellEscape(group.hora_inicio.slice(0, 5))}–${sabadellEscape(group.hora_fin.slice(0, 5))}</p>
      <div class="sabadell-controls">
        ${sabadellInput('grupo', 'Código del grupo', group.grupo, 'text', true)}
        ${sabadellInput('instalacion', 'Instalación', view.instalacion)}
        ${sabadellInput('actividad', 'Actividad', view.actividad)}
        ${sabadellInput('dias_semana', 'Días', view.dias_semana)}
        ${sabadellInput('hora_inicio', 'Hora de inicio', view.hora_inicio.slice(0, 5), 'time')}
        ${sabadellInput('hora_fin', 'Hora de fin', view.hora_fin.slice(0, 5), 'time')}
        <div class="form-group"><label>Grupo en Puri<select class="form-input" data-field="target_id">${group.candidates.length ? '<option value="0">Selecciona la clase existente</option>' : '<option value="0">Nuevo grupo</option>'}${group.candidates.map(c => `<option value="${Number(c.id)}" ${Number(c.id) === Number(group.target_id) ? 'selected' : ''}>${sabadellEscape(c.nombre)} · ${sabadellEscape(c.instalacion)} · ${sabadellEscape(c.dias_semana)} ${sabadellEscape((c.hora_inicio || '').slice(0, 5))}${c.retirada_at ? ' · retirada' : ''}</option>`).join('')}</select></label></div>
        <div class="form-group"><label>Acción<select class="form-input" data-field="action">${group.candidates.length
          ? `<option value="">Resuelve la coincidencia</option><option value="reuse" ${group.action === 'reuse' ? 'selected' : ''}>Actualizar participantes y conservar la clase</option><option value="replace">Sustituir la clase con los datos revisados</option>`
          : '<option value="new">Crear este grupo</option>'}</select></label></div>
        ${sabadellInput('fecha_inicio', 'Inicio de esta clase', view.fecha_inicio || sabadellEl('sabadellStart').value, 'date')}
        ${sabadellInput('fecha_fin', 'Fin de esta clase (opcional)', view.fecha_fin ?? sabadellEl('sabadellEnd').value, 'date')}
      </div>
      <p class="sabadell-warning" data-resolution ${group.action === '' ? '' : 'hidden'}>Este código ya existe con datos distintos o varias coincidencias. Selecciona la clase y elige si conservarla o sustituirla. Una sustitución crea otra clase y conserva el historial de la anterior.</p>
      <table class="sabadell-people"><thead><tr><th>Nombre original del PDF</th><th>Nombre</th><th>Apellidos</th><th>Excluir</th></tr></thead><tbody>${group.participants.map(p => `<tr data-person="${sabadellEscape(p.id)}"><td class="sabadell-original">${sabadellEscape(p.original)}${counts.get(p.original) > 1 ? '<span class="sabadell-duplicate">Nombre repetido: revisa estas filas</span>' : ''}</td><td><input class="form-input" type="text" data-person-field="nombre" aria-label="Nombre de ${sabadellEscape(p.original)}" value="${sabadellEscape(p.nombre)}"></td><td><input class="form-input" type="text" data-person-field="apellidos" aria-label="Apellidos de ${sabadellEscape(p.original)}" value="${sabadellEscape(p.apellidos)}"></td><td><label><input type="checkbox" data-person-field="exclude" aria-label="Excluir fila de ${sabadellEscape(p.original)}"> Excluir</label></td></tr>`).join('')}</tbody></table>
      <label class="sabadell-check"><input type="checkbox" data-confirmed> He revisado los nombres, las exclusiones y los datos de este grupo.</label>
    </details>`;
  }).join('');
  sabadellEl('sabadellGroups').innerHTML = html;
  document.querySelectorAll('[data-group]').forEach(sabadellClassDates);
  sabadellProgress();
}

function sabadellClassDates(node) {
  const reuse = node.querySelector('[data-field="action"]').value === 'reuse';
  node.querySelectorAll('[data-field="fecha_inicio"], [data-field="fecha_fin"]').forEach(input => { input.disabled = reuse; });
}

function sabadellCollect() {
  return { ...sabadellBase(), groups: [...document.querySelectorAll('[data-group]')].map(node => {
    const fields = Object.fromEntries([...node.querySelectorAll('[data-field]')].map(input => [input.dataset.field, input.value.trim()]));
    return { ...fields, id: node.dataset.group, confirmed: node.querySelector('[data-confirmed]').checked,
      participants: [...node.querySelectorAll('[data-person]')].map(row => ({ id: row.dataset.person,
        nombre: row.querySelector('[data-person-field="nombre"]').value.trim(), apellidos: row.querySelector('[data-person-field="apellidos"]').value.trim(),
        exclude: row.querySelector('[data-person-field="exclude"]').checked })) };
  }) };
}

function sabadellProgress() {
  const count = document.querySelectorAll('[data-confirmed]:checked').length;
  sabadellEl('sabadellProgress').textContent = `${count} de ${Sabadell.groups.length} grupos revisados`;
}

function sabadellGroupChange(event) {
  const node = event.target.closest('[data-group]');
  if (!node) return;
  sabadellInvalidate();
  if (!event.target.matches('[data-confirmed]')) node.querySelector('[data-confirmed]').checked = false;
  const group = Sabadell.groups.find(g => g.id === node.dataset.group);
  const action = node.querySelector('[data-field="action"]');
  const field = event.target.dataset.field;
  if (field === 'target_id' || (field && !['action', 'fecha_inicio', 'fecha_fin'].includes(field) && group.candidates.length)) {
    action.value = '';
    node.querySelector('[data-resolution]').hidden = false;
  }
  if (field === 'action' && action.value === 'reuse') {
    const candidate = group.candidates.find(c => String(c.id) === node.querySelector('[data-field="target_id"]').value);
    if (candidate) {
      Object.entries({ ...candidate, actividad: candidate.nombre }).forEach(([key, value]) => {
        const input = node.querySelector(`[data-field="${key}"]`);
        if (input && !['target_id', 'action', 'grupo'].includes(key)) input.value = value || '';
      });
      node.querySelector('[data-resolution]').hidden = true;
    }
  }
  if (field === 'action' && action.value === 'replace') {
    const today = new Date();
    const date = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    node.querySelector('[data-field="fecha_inicio"]').value = [date, sabadellEl('sabadellStart').value].sort().at(-1);
    node.querySelector('[data-field="fecha_fin"]').value = sabadellEl('sabadellEnd').value;
    node.querySelector('[data-resolution]').hidden = false;
  }
  const row = event.target.closest('[data-person]');
  if (row) row.classList.toggle('is-excluded', row.querySelector('[data-person-field="exclude"]').checked);
  sabadellClassDates(node);
  sabadellProgress();
}

async function sabadellReview() {
  sabadellInvalidate();
  const payload = sabadellCollect();
  const pending = payload.groups.find(g => !g.confirmed);
  if (pending) {
    const node = sabadellEl(`sabadell-${pending.id}`);
    node.open = true;
    node.querySelector('summary').focus();
    throw new Error('Revisa y confirma todos los grupos antes de continuar.');
  }
  const result = await sabadellRequest({ ...payload, operation: 'review' });
  Sabadell.payload = payload;
  Sabadell.preview = result;
  const labels = { instalaciones_creadas: 'Instalaciones nuevas', actividades_creadas: 'Clases nuevas', actividades_reutilizadas: 'Clases existentes', actividades_retiradas: 'Clases sustituidas', participantes_creados: 'Participantes nuevos', participantes_mantenidos: 'Participantes que siguen', participantes_reactivados: 'Reincorporaciones', participantes_desactivados: 'Bajas' };
  const details = result.deactivation_names?.length ? `<p>Ejemplos de bajas: ${result.deactivation_names.map(sabadellEscape).join(', ')}</p>` : '';
  sabadellEl('sabadellReview').innerHTML = `<h2>${result.nueva_edicion ? 'Crear nueva edición' : 'Actualizar grupos del curso'}</h2><p>${sabadellEscape(result.nombre_edicion)} · ${sabadellEscape(result.periodo.fecha_inicio)} — ${sabadellEscape(result.periodo.fecha_fin || 'sin fin definido')}</p><dl>${Object.entries(labels).map(([key, label]) => `<dt>${label}</dt><dd>${result.stats[key] || 0}</dd>`).join('')}<dt>Filas excluidas expresamente</dt><dd>${result.excluded_count}</dd></dl>${details}<p>Las asistencias y evaluaciones anteriores se conservan. ${result.nueva_edicion ? 'Se creará un curso independiente.' : 'Los grupos ausentes del PDF se mantienen.'}</p>`;
  sabadellEl('sabadellReview').hidden = false;
  sabadellEl('sabadellSave').hidden = false;
  sabadellEl('sabadellReviewButton').hidden = true;
  sabadellEl('sabadellReview').focus();
}

async function sabadellSave() {
  if (!Sabadell.preview || !Sabadell.payload) return;
  let result;
  try {
    result = await sabadellRequest({ ...Sabadell.payload, operation: 'save', expected_fingerprint: Sabadell.preview.fingerprint });
  } catch (error) { sabadellInvalidate(); throw error; }
  try { await sabadellLoadCenter(); }
  catch (error) { sabadellEl('sabadellError').textContent = 'El listado se ha guardado. Recarga la página para ver los cursos actualizados.'; }
  sabadellEl('sabadellStatus').textContent = result.message;
}

document.addEventListener('DOMContentLoaded', () => {
  sabadellEl('sabadellCenter').addEventListener('change', () => sabadellRun(sabadellLoadCenter));
  ['sabadellDestination', 'sabadellEdition'].forEach(id => sabadellEl(id).addEventListener('change', () => sabadellRun(async () => {
    sabadellSetPeriod();
    await sabadellLoadContext();
  })));
  ['sabadellStart', 'sabadellEnd'].forEach(id => sabadellEl(id).addEventListener('input', () => {
    sabadellInvalidate();
    const key = id === 'sabadellStart' ? 'fecha_inicio' : 'fecha_fin';
    document.querySelectorAll('[data-group]').forEach(node => {
      if (node.querySelector('[data-field="action"]').value !== 'reuse') {
        node.querySelector(`[data-field="${key}"]`).value = sabadellEl(id).value;
        node.querySelector('[data-confirmed]').checked = false;
      }
    });
    sabadellProgress();
  }));
  sabadellEl('sabadellFile').addEventListener('change', () => sabadellRun(sabadellUpload));
  sabadellEl('sabadellGroups').addEventListener('input', sabadellGroupChange);
  sabadellEl('sabadellForm').addEventListener('submit', event => { event.preventDefault(); sabadellRun(sabadellReview); });
  sabadellEl('sabadellSave').addEventListener('click', () => sabadellRun(sabadellSave));
  sabadellRun(sabadellLoadCenter);
});
