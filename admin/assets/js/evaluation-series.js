/* Series trimestrales y Día de los retos. Los ciclos publicados son inmutables. */
const EvaluationSeriesState = { options: null, progression: null };

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('evaluationSeriesForm')?.addEventListener('submit', submitSeries);
  document.getElementById('seriesScope')?.addEventListener('change', toggleSeriesActivities);
  document.getElementById('progressionParticipant')?.addEventListener('change', renderProgression);
});

function addSeriesField() {
  const container = document.getElementById('seriesFields');
  if (!container || container.children.length >= 10) return;
  const row = document.createElement('div');
  row.className = 'series-field-row';
  row.innerHTML = `
    <div class="form-group"><label>Prueba</label><input class="series-field-name" maxlength="150" required placeholder="Ej. Burpees en 1 minuto"></div>
    <div class="form-group"><label>Formato</label><select class="series-field-type"><option value="entero">Número entero</option><option value="decimal">Número decimal</option><option value="duracion">Duración</option><option value="texto_corto">Texto corto</option></select></div>
    <div class="form-group"><label>Unidad</label><input class="series-field-unit" maxlength="50" placeholder="repeticiones"></div>
    <button class="btn btn-secondary" type="button" aria-label="Quitar prueba" onclick="if(document.querySelectorAll('.series-field-row').length > 1) this.parentElement.remove()">Quitar</button>`;
  container.appendChild(row);
}

async function openSeriesModal() {
  const form = document.getElementById('evaluationSeriesForm');
  form?.reset();
  document.getElementById('seriesFields').innerHTML = '';
  addSeriesField();
  document.getElementById('seriesFormError').textContent = '';
  const today = todayIsoLocal();
  const start = ActivityPage.ctx?.fecha_inicio && ActivityPage.ctx.fecha_inicio > today ? ActivityPage.ctx.fecha_inicio.substring(0, 10) : today;
  document.getElementById('seriesStart').value = start;
  document.getElementById('seriesEnd').value = start;
  document.getElementById('seriesUntil').value = `${start.substring(0, 4)}-12-31`;
  toggleSeriesActivities();
  openModal('evaluationSeriesModal');
  try {
    const data = await evaluationApiRequest(`api/evaluaciones/scope_options.php?actividad_id=${encodeURIComponent(ActivityPage.id)}`);
    EvaluationSeriesState.options = data;
    const list = document.getElementById('seriesActivities');
    list.innerHTML = data.actividades.map(activity => `
      <label class="series-activity-option"><input type="checkbox" value="${Number(activity.id)}" ${Number(activity.id) === Number(ActivityPage.id) ? 'checked disabled' : ''}>
        ${escapeHtml(activity.instalacion_nombre)} · ${escapeHtml(activity.nombre)}${activity.grupo ? ` (${escapeHtml(activity.grupo)})` : ''}
      </label>`).join('');
  } catch (error) {
    document.getElementById('seriesFormError').textContent = error.message;
  }
}

function toggleSeriesActivities() {
  const list = document.getElementById('seriesActivities');
  if (list) list.hidden = document.getElementById('seriesScope')?.value !== 'varias';
}

async function submitSeries(event) {
  event.preventDefault();
  const errorBox = document.getElementById('seriesFormError');
  errorBox.textContent = '';
  const button = document.getElementById('saveSeriesBtn');
  const fields = Array.from(document.querySelectorAll('.series-field-row')).map(row => ({
    nombre: row.querySelector('.series-field-name').value.trim(),
    tipo_dato: row.querySelector('.series-field-type').value,
    unidad: row.querySelector('.series-field-unit').value.trim() || null
  }));
  const payload = {
    actividad_id: Number(ActivityPage.id),
    nombre: document.getElementById('seriesName').value.trim(),
    tipo: document.getElementById('seriesType').value,
    instrucciones: document.getElementById('seriesInstructions').value.trim() || null,
    primer_inicio: document.getElementById('seriesStart').value,
    primer_fin: document.getElementById('seriesEnd').value,
    hasta: document.getElementById('seriesUntil').value,
    ambito: document.getElementById('seriesScope').value,
    actividad_ids: Array.from(document.querySelectorAll('#seriesActivities input:checked')).map(input => Number(input.value)),
    campos: fields
  };
  button.disabled = true;
  try {
    const data = await evaluationApiRequest('api/evaluaciones/create_series.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    });
    closeModal('evaluationSeriesModal');
    ActivityPage.evaluationsLoaded = false;
    await loadEvaluations();
    showNotification(`Serie creada: ${data.evaluaciones_creadas} evaluaciones en ${data.actividades} actividades`, 'success');
  } catch (error) {
    errorBox.textContent = error.message;
  } finally {
    button.disabled = false;
  }
}

async function archiveSeries(seriesId) {
  if (!window.confirm('¿Archivar los ciclos pendientes de esta serie? Los resultados existentes se conservarán.')) return;
  try {
    await evaluationApiRequest('api/evaluaciones/archive_series.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ serie_id: Number(seriesId) })
    });
    ActivityPage.evaluationsLoaded = false;
    await loadEvaluations();
    showNotification('Serie archivada', 'success');
  } catch (error) {
    showNotification(error.message, 'error');
  }
}

async function openProgression(seriesId) {
  const content = document.getElementById('progressionContent');
  content.textContent = 'Cargando progresión…';
  openModal('evaluationProgressionModal');
  try {
    const data = await evaluationApiRequest(`api/evaluaciones/progression.php?serie_id=${encodeURIComponent(seriesId)}&actividad_id=${encodeURIComponent(ActivityPage.id)}`);
    EvaluationSeriesState.progression = data;
    document.getElementById('progressionTitle').textContent = `Progresión · ${data.serie.nombre}`;
    const selector = document.getElementById('progressionParticipant');
    selector.innerHTML = data.participantes.map((person, index) => `<option value="${index}">${escapeHtml(person.apellidos)}, ${escapeHtml(person.nombre)}</option>`).join('');
    renderProgression();
  } catch (error) {
    content.textContent = error.message;
  }
}

function renderProgression() {
  const data = EvaluationSeriesState.progression;
  const content = document.getElementById('progressionContent');
  if (!data || !content) return;
  const index = Number(document.getElementById('progressionParticipant').value || 0);
  const person = data.participantes[index];
  if (!person) {
    content.innerHTML = '<p>Aún no hay resultados para esta serie.</p>';
    return;
  }
  const previous = {};
  const rows = data.ciclos.map(cycle => {
    const measurements = person.mediciones[Number(cycle.ciclo)] || {};
    const cells = data.pruebas.map(test => {
      const order = Number(test.orden);
      const result = measurements[order];
      if (!result || result.estado !== 'medido') return '<td>—</td>';
      const value = result.valor_texto ?? result.valor_numero;
      const qualifier = result.calificador === 'mayor_que' ? '&gt;' : result.calificador === 'menor_que' ? '&lt;' : '';
      let delta = '';
      if (result.valor_numero !== null && result.calificador === 'exacto') {
        if (previous[order] !== undefined) {
          const difference = Number(result.valor_numero) - previous[order];
          delta = ` <small class="progression-delta">(${difference > 0 ? '+' : ''}${Number(difference.toFixed(3))})</small>`;
        }
        previous[order] = Number(result.valor_numero);
      }
      return `<td>${qualifier}${escapeHtml(String(value))}${test.unidad ? ` ${escapeHtml(test.unidad)}` : ''}${delta}</td>`;
    }).join('');
    return `<tr><th scope="row">${formatDateEs(cycle.fecha_realizacion || cycle.fecha_inicio)}${cycle.estado ? '' : ' · sin realizar'}</th>${cells}</tr>`;
  }).join('');
  content.innerHTML = `<div class="progression-scroll"><table class="progression-table"><thead><tr><th>Fecha</th>${data.pruebas.map(test => `<th>${escapeHtml(test.nombre)}</th>`).join('')}</tr></thead><tbody>${rows}</tbody></table></div>`;
}
