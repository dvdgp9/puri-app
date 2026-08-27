const MonitorEvaluationApi = Object.freeze({
  list: 'api/evaluaciones/listar.php',
  start: 'api/evaluaciones/iniciar.php',
  detail: 'api/evaluaciones/detalle.php',
  save: 'api/evaluaciones/guardar_resultado.php',
  finish: 'api/evaluaciones/finalizar.php'
});

const MonitorEvaluationState = {
  detail: null,
  filter: 'all'
};

document.addEventListener('DOMContentLoaded', () => {
  const context = window.MonitorEvaluationsContext || {};
  if (context.view === 'activity') initMonitorEvaluationsSection();
  if (context.view === 'capture') initMonitorEvaluationCapture();
});

async function monitorEvaluationRequest(url, options = {}) {
  const response = await fetch(url, options);
  let result;
  try {
    result = await response.json();
  } catch (error) {
    throw new Error('El servidor no devolvió una respuesta válida.');
  }
  if (!response.ok || !result.success) {
    const apiError = new Error(result?.error?.message || result?.message || 'No se pudo completar la operación.');
    apiError.code = result?.error?.code || 'ERROR_DESCONOCIDO';
    apiError.fields = result?.error?.fields || {};
    throw apiError;
  }
  return result.data;
}

function monitorEvaluationEscape(value) {
  const element = document.createElement('div');
  element.textContent = String(value ?? '');
  return element.innerHTML;
}

function monitorEvaluationFormatDate(value) {
  const parts = String(value || '').split('-');
  return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : value;
}

function monitorEvaluationTypeLabel(type) {
  return ({
    entero: 'Número entero',
    decimal: 'Número decimal',
    duracion: 'Duración en segundos',
    texto_corto: 'Texto corto'
  })[type] || 'Resultado';
}

async function initMonitorEvaluationsSection() {
  const section = document.getElementById('monitor-evaluations-section');
  const list = document.getElementById('monitor-evaluations-list');
  if (!section || !list) return;
  section.hidden = true;

  try {
    const context = window.MonitorEvaluationsContext;
    const data = await monitorEvaluationRequest(`${MonitorEvaluationApi.list}?actividad_id=${encodeURIComponent(context.activityId)}`);
    const activeEvaluations = [
      ...(Array.isArray(data.en_curso) ? data.en_curso : []),
      ...(Array.isArray(data.pendientes) ? data.pendientes : [])
    ];
    if (!activeEvaluations.length) return;

    section.hidden = false;
    list.innerHTML = activeEvaluations.map(renderMonitorEvaluationRow).join('');
  } catch (error) {
    console.error('Error cargando evaluaciones disponibles:', error);
    section.hidden = true;
  }
}

function renderMonitorEvaluationRow(evaluation) {
  const field = evaluation.campos?.[0] || {};
  const isPending = evaluation.estado === 'pendiente';
  const isExpired = evaluation.estado === 'en_curso_fuera_de_plazo';
  const action = isPending
    ? `<button type="button" class="monitor-evaluation-action" onclick="showEvaluationStartForm(${Number(evaluation.id)})">Realizar</button>`
    : isExpired
      ? '<span class="monitor-evaluation-blocked">Requiere ampliar el período en Admin</span>'
      : `<a class="monitor-evaluation-action" href="evaluacion.php?sesion_id=${Number(evaluation.sesion?.id)}">Continuar</a>`;
  const status = isPending ? 'Pendiente' : isExpired ? 'Fuera de plazo' : 'En curso';
  const unit = field.unidad ? ` · ${field.unidad}` : '';

  return `
    <article class="monitor-evaluation-row" id="monitor-evaluation-${Number(evaluation.id)}" data-start="${monitorEvaluationEscape(evaluation.fecha_inicio)}" data-end="${monitorEvaluationEscape(evaluation.fecha_fin)}">
      <div class="monitor-evaluation-main">
        <div class="monitor-evaluation-heading">
          <h4>${monitorEvaluationEscape(evaluation.nombre)}</h4>
          <span>${monitorEvaluationEscape(status)}</span>
        </div>
        <p>${monitorEvaluationFormatDate(evaluation.fecha_inicio)} → ${monitorEvaluationFormatDate(evaluation.fecha_fin)} · ${monitorEvaluationEscape(field.nombre || monitorEvaluationTypeLabel(field.tipo_dato))}${monitorEvaluationEscape(unit)}</p>
      </div>
      <div class="monitor-evaluation-actions">${action}</div>
      <div class="evaluation-start-slot"></div>
    </article>`;
}

function showEvaluationStartForm(evaluationId) {
  document.querySelectorAll('.evaluation-start-slot').forEach(slot => { slot.innerHTML = ''; });
  const row = document.getElementById(`monitor-evaluation-${evaluationId}`);
  const slot = row?.querySelector('.evaluation-start-slot');
  if (!row || !slot) return;
  const contextToday = window.MonitorEvaluationsContext?.today || '';
  const minDate = row.dataset.start;
  const maxDate = contextToday && contextToday < row.dataset.end ? contextToday : row.dataset.end;
  const suggestedDate = contextToday >= minDate && contextToday <= maxDate ? contextToday : maxDate;

  slot.innerHTML = `
    <form class="evaluation-start-form" onsubmit="startMonitorEvaluation(event, ${evaluationId})">
      <label for="evaluation-start-date-${evaluationId}">Fecha de realización</label>
      <input class="evaluation-start-date" id="evaluation-start-date-${evaluationId}" type="date" min="${monitorEvaluationEscape(minDate)}" max="${monitorEvaluationEscape(maxDate)}" value="${monitorEvaluationEscape(suggestedDate)}" required>
      <button type="submit" class="monitor-evaluation-action">Empezar</button>
      <button type="button" class="monitor-evaluation-cancel" onclick="hideEvaluationStartForm(${evaluationId})">Cancelar</button>
      <p class="evaluation-start-error" role="alert"></p>
    </form>`;
  slot.querySelector('input')?.focus();
}

function hideEvaluationStartForm(evaluationId) {
  const slot = document.querySelector(`#monitor-evaluation-${evaluationId} .evaluation-start-slot`);
  if (slot) slot.innerHTML = '';
}

async function startMonitorEvaluation(event, evaluationId) {
  event.preventDefault();
  const form = event.currentTarget;
  const dateInput = form.querySelector('.evaluation-start-date');
  const errorElement = form.querySelector('.evaluation-start-error');
  const submit = form.querySelector('[type="submit"]');
  if (errorElement) errorElement.textContent = '';
  if (submit) submit.disabled = true;

  try {
    const data = await monitorEvaluationRequest(MonitorEvaluationApi.start, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        evaluacion_id: Number(evaluationId),
        fecha_realizacion: dateInput?.value || ''
      })
    });
    window.location.href = `evaluacion.php?sesion_id=${Number(data.sesion.id)}`;
  } catch (error) {
    console.error('Error iniciando evaluación:', error);
    if (errorElement) errorElement.textContent = error.message;
    if (submit) submit.disabled = false;
  }
}

function initMonitorEvaluationCapture() {
  document.getElementById('evaluation-filter-all')?.addEventListener('click', () => setMonitorEvaluationFilter('all'));
  document.getElementById('evaluation-filter-pending')?.addEventListener('click', () => setMonitorEvaluationFilter('pending'));
  document.getElementById('evaluation-finish-button')?.addEventListener('click', () => finishMonitorEvaluation(false));
  loadMonitorEvaluationDetail();
}

async function loadMonitorEvaluationDetail() {
  const list = document.getElementById('evaluation-capture-list');
  if (!list) return;
  list.innerHTML = '<div class="evaluation-monitor-loading">Cargando participantes…</div>';
  try {
    const sessionId = window.MonitorEvaluationsContext?.sessionId;
    const data = await monitorEvaluationRequest(`${MonitorEvaluationApi.detail}?sesion_id=${encodeURIComponent(sessionId)}`);
    MonitorEvaluationState.detail = data;
    renderMonitorEvaluationCapture();
  } catch (error) {
    console.error('Error cargando la evaluación:', error);
    list.innerHTML = `<div class="evaluation-monitor-error"><strong>No se pudo cargar la evaluación</strong><p>${monitorEvaluationEscape(error.message)}</p><button type="button" onclick="loadMonitorEvaluationDetail()">Reintentar</button></div>`;
  }
}

function renderMonitorEvaluationCapture() {
  const data = MonitorEvaluationState.detail;
  if (!data) return;
  const isFinal = data.sesion.estado === 'finalizada';
  const isExpired = Boolean(data.sesion.fuera_de_plazo);
  const title = document.getElementById('evaluation-capture-title');
  const meta = document.getElementById('evaluation-capture-meta');
  const state = document.getElementById('evaluation-capture-state');
  const guidance = document.getElementById('evaluation-capture-guidance');
  const instructions = document.getElementById('evaluation-capture-instructions');
  const finishButton = document.getElementById('evaluation-finish-button');
  const finishHelp = document.getElementById('evaluation-finish-help');

  if (title) title.textContent = data.evaluacion.nombre;
  if (meta) meta.textContent = `Fecha de realización: ${monitorEvaluationFormatDate(data.sesion.fecha_realizacion)} · Período: ${monitorEvaluationFormatDate(data.evaluacion.fecha_inicio)} → ${monitorEvaluationFormatDate(data.evaluacion.fecha_fin)}`;
  if (state) {
    state.textContent = isFinal ? 'Finalizada' : isExpired ? 'Fuera de plazo' : 'En curso';
    state.dataset.state = isFinal ? 'finalizada' : isExpired ? 'fuera_de_plazo' : 'en_curso';
  }
  if (guidance && instructions) {
    const hasInstructions = Boolean(String(data.evaluacion.instrucciones || '').trim());
    guidance.hidden = !hasInstructions;
    instructions.textContent = data.evaluacion.instrucciones || '';
  }
  if (finishButton) finishButton.hidden = isFinal || isExpired;
  if (finishHelp) {
    finishHelp.textContent = isFinal
      ? 'La evaluación está cerrada. Solo un administrador puede corregirla o reabrirla.'
      : isExpired
        ? 'Un administrador debe ampliar el período para poder continuar.'
        : 'Puedes salir y continuar más tarde mientras no finalices la evaluación.';
  }

  updateMonitorEvaluationProgress(data.cobertura);
  renderMonitorEvaluationParticipants();
}

function updateMonitorEvaluationProgress(coverage) {
  const progress = document.getElementById('evaluation-progress');
  if (!progress) return;
  const measured = Number(coverage?.medidos || 0);
  const total = Number(coverage?.total || 0);
  progress.innerHTML = `<strong>${measured} de ${total}</strong> registrados`;
}

function setMonitorEvaluationFilter(filter) {
  MonitorEvaluationState.filter = filter;
  const allButton = document.getElementById('evaluation-filter-all');
  const pendingButton = document.getElementById('evaluation-filter-pending');
  const showPending = filter === 'pending';
  allButton?.classList.toggle('active', !showPending);
  pendingButton?.classList.toggle('active', showPending);
  allButton?.setAttribute('aria-pressed', showPending ? 'false' : 'true');
  pendingButton?.setAttribute('aria-pressed', showPending ? 'true' : 'false');
  renderMonitorEvaluationParticipants();
}

function renderMonitorEvaluationParticipants() {
  const data = MonitorEvaluationState.detail;
  const list = document.getElementById('evaluation-capture-list');
  if (!data || !list) return;
  const isLocked = data.sesion.estado === 'finalizada' || data.sesion.fuera_de_plazo;
  let participants = Array.isArray(data.participantes) ? data.participantes : [];
  if (MonitorEvaluationState.filter === 'pending') {
    participants = participants.filter(participant => participant.resultados?.some(result => result.estado !== 'medido'));
  }

  if (!participants.length) {
    list.innerHTML = `<div class="evaluation-monitor-empty"><strong>${MonitorEvaluationState.filter === 'pending' ? 'No quedan pendientes' : 'No hay participantes'}</strong><p>${MonitorEvaluationState.filter === 'pending' ? 'Todos los resultados están registrados.' : 'La evaluación se inició sin participantes.'}</p></div>`;
    return;
  }

  list.innerHTML = participants.map(participant => renderMonitorCaptureRow(participant, isLocked)).join('');
}

function renderMonitorCaptureRow(participant, isLocked) {
  const result = participant.resultados?.[0] || {};
  const isText = result.tipo_dato === 'texto_corto';
  const isDeleted = Boolean(participant.inscripcion_eliminada);
  const disabled = isLocked || isDeleted ? 'disabled' : '';
  const value = isText ? (result.valor_texto ?? '') : (result.valor_numero ?? '');
  const qualifier = result.calificador || 'exacto';
  const step = result.tipo_dato === 'entero' ? '1' : '0.001';

  return `
    <article class="evaluation-capture-row" data-inscrito-id="${participant.id ?? ''}" data-campo-id="${Number(result.campo_id)}" data-tipo-dato="${monitorEvaluationEscape(result.tipo_dato || '')}">
      <div class="evaluation-capture-person">
        <strong>${monitorEvaluationEscape(`${participant.apellidos || ''}, ${participant.nombre || ''}`.replace(/^,\s*/, ''))}</strong>
        <span>${isDeleted ? 'Inscripción eliminada · solo lectura' : monitorEvaluationEscape(result.campo_nombre || monitorEvaluationTypeLabel(result.tipo_dato))}</span>
      </div>
      <div class="evaluation-capture-controls">
        ${!isText ? `
          <label class="evaluation-capture-qualifier">
            <span class="evaluation-visually-hidden">Calificador</span>
            <select ${disabled}>
              <option value="exacto"${qualifier === 'exacto' ? ' selected' : ''}>=</option>
              <option value="mayor_que"${qualifier === 'mayor_que' ? ' selected' : ''}>&gt;</option>
              <option value="menor_que"${qualifier === 'menor_que' ? ' selected' : ''}>&lt;</option>
            </select>
          </label>` : ''}
        <label class="evaluation-capture-input-label">
          <span class="evaluation-visually-hidden">Resultado de ${monitorEvaluationEscape(`${participant.nombre || ''} ${participant.apellidos || ''}`.trim())}</span>
          <input class="evaluation-capture-input" type="${isText ? 'text' : 'number'}" value="${monitorEvaluationEscape(String(value))}" ${isText ? 'maxlength="255"' : `step="${step}"`} ${disabled}>
        </label>
        ${result.unidad ? `<span class="evaluation-capture-unit">${monitorEvaluationEscape(result.unidad)}</span>` : ''}
        <div class="evaluation-capture-actions">
          <button type="button" class="evaluation-row-save" onclick="saveMonitorEvaluationResult(this)" ${disabled}>Guardar</button>
          <button type="button" class="evaluation-row-empty" onclick="markMonitorResultUnevaluated(this)" ${disabled}>Sin evaluar</button>
        </div>
      </div>
      <p class="evaluation-capture-save-state" aria-live="polite">${result.estado === 'medido' ? 'Guardado' : 'Sin evaluar'}</p>
    </article>`;
}

async function saveMonitorEvaluationResult(button, forceUnevaluated = false) {
  const row = button?.closest('.evaluation-capture-row');
  if (!row) return;
  const input = row.querySelector('.evaluation-capture-input');
  const qualifier = row.querySelector('.evaluation-capture-qualifier select');
  const stateElement = row.querySelector('.evaluation-capture-save-state');
  if (forceUnevaluated && input) input.value = '';
  const value = String(input?.value ?? '').trim();
  const resultState = value === '' ? 'sin_evaluar' : 'medido';
  const isText = row.dataset.tipoDato === 'texto_corto';
  const payload = {
    sesion_id: Number(window.MonitorEvaluationsContext.sessionId),
    campo_id: Number(row.dataset.campoId),
    inscrito_id: Number(row.dataset.inscritoId),
    estado: resultState
  };
  if (resultState === 'medido') {
    if (isText) payload.valor_texto = value;
    else {
      payload.valor_numero = value;
      payload.calificador = qualifier?.value || 'exacto';
    }
  }

  row.querySelectorAll('button').forEach(control => { control.disabled = true; });
  if (stateElement) stateElement.textContent = 'Guardando…';
  try {
    const data = await monitorEvaluationRequest(MonitorEvaluationApi.save, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const participant = MonitorEvaluationState.detail.participantes.find(item => Number(item.id) === Number(row.dataset.inscritoId));
    const result = participant?.resultados?.find(item => Number(item.campo_id) === Number(row.dataset.campoId));
    if (result) Object.assign(result, data.resultado);
    MonitorEvaluationState.detail.cobertura = data.cobertura;
    updateMonitorEvaluationProgress(data.cobertura);
    if (stateElement) stateElement.textContent = resultState === 'medido' ? 'Guardado' : 'Sin evaluar · guardado';
    if (MonitorEvaluationState.filter === 'pending' && resultState === 'medido') {
      row.remove();
      if (!document.querySelector('.evaluation-capture-row')) renderMonitorEvaluationParticipants();
    }
  } catch (error) {
    console.error('Error guardando resultado:', error);
    if (stateElement) stateElement.textContent = error.message;
  } finally {
    row.querySelectorAll('button').forEach(control => { control.disabled = false; });
  }
}

function markMonitorResultUnevaluated(button) {
  saveMonitorEvaluationResult(button, true);
}

async function finishMonitorEvaluation(confirmPending) {
  const button = document.getElementById('evaluation-finish-button');
  if (button) button.disabled = true;
  try {
    const data = await monitorEvaluationRequest(MonitorEvaluationApi.finish, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        sesion_id: Number(window.MonitorEvaluationsContext.sessionId),
        confirmar_pendientes: confirmPending
      })
    });
    MonitorEvaluationState.detail = data;
    renderMonitorEvaluationCapture();
    if (typeof showTempMessage === 'function') showTempMessage('Evaluación finalizada');
  } catch (error) {
    if (error.code === 'HAY_RESULTADOS_PENDIENTES') {
      const pending = Number(error.fields?.pendientes || 0);
      const accepted = window.confirm(`Quedan ${pending} participante${pending === 1 ? '' : 's'} sin evaluar. ¿Finalizar de todas formas?`);
      if (accepted) {
        if (button) button.disabled = false;
        return finishMonitorEvaluation(true);
      }
    } else {
      console.error('Error finalizando evaluación:', error);
      window.alert(error.message);
    }
  } finally {
    if (button) button.disabled = false;
  }
}

