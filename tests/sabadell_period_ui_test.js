const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

// Exercise the actual controller against responses from empty and migrated centers.
const elements = new Map();
function el(id) {
  if (!elements.has(id)) elements.set(id, {
    value: '', hidden: false, disabled: false, required: false, textContent: '', dataset: { csrf: 'test-token' },
    replaceChildren(...children) { this.children = children; },
    append(child) { (this.children ||= []).push(child); }
  });
  return elements.get(id);
}
let response = { success: true, ediciones: [], edicion_id: null };
const context = vm.createContext({
  document: { getElementById: el, addEventListener() {}, querySelectorAll() { return []; } },
  Option: class { constructor(text, value) { this.text = text; this.value = value; } },
  fetch: async () => ({ ok: true, json: async () => response })
});
vm.runInContext(fs.readFileSync('admin/assets/js/sabadell-import.js', 'utf8'), context);
const run = code => vm.runInContext(code, context);

async function load(ediciones, id, destination = 'actual') {
  response = { success: true, ediciones, edicion_id: id };
  el('sabadellCenter').value = '1';
  el('sabadellDestination').value = destination;
  await run('sabadellLoadCenter()');
  run('sabadellUpdateDates()');
}

(async () => {
  await load([], null);
  assert.equal(el('sabadellEditionField').hidden, true, 'La primera carga no exige elegir un curso inexistente.');
  assert.equal(el('sabadellStart').value, '');
  assert.equal(el('sabadellStart').required, true);
  assert.equal(run('sabadellBase().edicion_id'), null);

  const valid = { id: 7, nombre: 'Curso 2026–2027', fecha_inicio: '2026-09-01', fecha_fin: '2027-06-30' };
  await load([valid], 7);
  assert.equal(el('sabadellEditionField').hidden, true, 'Una única agrupación se utiliza sin selector.');
  assert.equal(run('sabadellBase().edicion_id'), '7', 'Ocultar el selector conserva el destino y el historial.');
  assert.equal(el('sabadellStart').value, '2026-09-01');
  assert.equal(el('sabadellEnd').value, '2027-06-30');
  assert.equal(el('sabadellDatesNotice').hidden, true);
  assert.equal(el('sabadellEdition').children[0].text, '01/09/2026 — 30/06/2027');

  const legacy = { id: 8, nombre: 'Curso 0206', fecha_inicio: '0206-03-16', fecha_fin: null };
  await load([legacy], 8);
  assert.equal(el('sabadellEditionField').hidden, true);
  assert.equal(el('sabadellStart').value, '', 'No se rellena 0206 ni se inventa 2026.');
  assert.equal(el('sabadellStart').required, true);
  assert.equal(el('sabadellDatesNotice').hidden, false);
  assert.equal(run('sabadellBase().edicion_id'), '8', 'La agrupación antigua no se borra ni se reemplaza.');
  assert.equal(legacy.fecha_inicio, '0206-03-16', 'Las propuestas de la pantalla no modifican los datos recibidos.');
  assert.equal(el('sabadellEdition').children[0].text, 'Actividades existentes · fechas por revisar');

  await load([valid, legacy], 7);
  assert.equal(el('sabadellEditionField').hidden, false, 'El selector solo aparece cuando hay una elección real.');
  assert.equal(el('sabadellEdition').disabled, false);
  await load([valid, legacy], 7, 'nueva');
  assert.equal(el('sabadellEditionField').hidden, true, 'Un período nuevo no reutiliza el selector anterior.');
  assert.equal(el('sabadellStart').value, '');
  assert.equal(el('sabadellDatesNotice').hidden, true);

  assert.equal(run('sabadellDateValue("2026-02-30")'), '');
  assert.equal(run('sabadellDateValue("2024-02-29")'), '2024-02-29');
  assert.equal(run('sabadellDateValue("0000-00-00")'), '');
  console.log('[OK] Primera carga, período único/múltiple, renovación y fechas antiguas inválidas.');
})().catch(error => { console.error(error); process.exitCode = 1; });
