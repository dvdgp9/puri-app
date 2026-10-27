const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const controls = new Map();
const control = id => {
  if (!controls.has(id)) controls.set(id, { value: '', hidden: false, textContent: '', innerHTML: '', dataset: { csrf: 'test' }, focus() { this.focused = true; } });
  return controls.get(id);
};
const classes = () => ({ values: new Set(), toggle(name, active) { active ? this.values.add(name) : this.values.delete(name); } });
const base = { id: 'p1', grupo: 'I-101', page: 1, instalacion: 'Sala', actividad: 'Ioga', dias_semana: 'Lunes,Miércoles',
  hora_inicio: '09:15', hora_fin: '10:15', fecha_inicio: '2026-09-01', fecha_fin: '2027-06-30', target_id: '0', action: 'new', candidates: [],
  participants: [{ id: 'p1-1', original: 'PEREZ LOPEZ ANA', nombre: 'Ana', apellidos: 'Pérez López', exclude: false }] };
const clone = value => JSON.parse(JSON.stringify(value));
function groupNode(group) {
  const fields = Object.fromEntries(Object.entries(group).filter(([key]) => !['id', 'page', 'participants', 'candidates'].includes(key))
    .map(([key, value]) => [key, { dataset: { field: key }, value, disabled: false }]));
  const people = group.participants.map(person => {
    const inputs = { nombre: { value: person.nombre }, apellidos: { value: person.apellidos }, exclude: { checked: person.exclude } };
    return { dataset: { person: person.id }, inputs, classList: classes(),
      querySelector(selector) { return inputs[/data-person-field="([^"]+)"/.exec(selector)[1]]; } };
  });
  const elements = { '[data-issues]': {}, '[data-issue-status]': {}, summary: { focus() { this.focused = true; } } };
  return { dataset: { group: group.id }, fields, people, elements, open: false, classList: classes(),
    querySelector(selector) {
      if (elements[selector]) return elements[selector];
      const match = /data-field="([^"]+)"/.exec(selector);
      if (match) return fields[match[1]];
      throw new Error('Unexpected selector: ' + selector);
    },
    querySelectorAll(selector) {
      if (selector === '[data-field]') return Object.values(fields);
      if (selector === '[data-person]') return people;
      if (selector.includes('fecha_inicio')) return [fields.fecha_inicio, fields.fecha_fin];
      throw new Error('Unexpected selector: ' + selector);
    } };
}
let nodes = [];
const requests = [];
let reply = { success: true, nueva_edicion: true, periodo: { fecha_inicio: '2026-09-01', fecha_fin: '2027-06-30' }, stats: {}, excluded_count: 0, fingerprint: 'test-preview' };
const context = vm.createContext({
  document: { getElementById: control, addEventListener() {}, querySelectorAll(selector) {
    assert.equal(selector, '[data-group]', 'No existe ninguna confirmación obligatoria por grupo.'); return nodes;
  } },
  fetch: async (url, request) => { requests.push(JSON.parse(request.body)); return { ok: reply.success, json: async () => reply }; }
});
vm.runInContext(fs.readFileSync('admin/assets/js/sabadell-import.js', 'utf8'), context);
const run = source => vm.runInContext(source, context);
const issues = group => { context.fixture = group; return run('sabadellGroupIssues(fixture)'); };
function render(groups) {
  nodes = groups.map(groupNode);
  context.fixtureGroups = groups;
  run('Sabadell.groups = fixtureGroups; sabadellRenderGroups()');
}

(async () => {
  assert.equal(issues(base).length, 0);
  const duplicate = clone(base);
  duplicate.participants.push({ ...duplicate.participants[0], id: 'p1-2', nombre: ' ANA ', apellidos: 'pérez   lópez' });
  assert(issues(duplicate).some(message => message.includes('repetidos')));
  duplicate.participants[1].exclude = true;
  assert.equal(issues(duplicate).length, 0, 'Excluir el duplicado resuelve la incidencia.');
  assert(issues({ ...base, action: '' }).some(message => message.includes('coincidencia')));
  assert(issues({ ...base, fecha_inicio: '' }).some(message => message.includes('fechas')));
  assert(issues({ ...base, fecha_fin: '2026-08-01' }).some(message => message.includes('fechas')));
  assert(issues({ ...base, hora_fin: '08:00' }).some(message => message.includes('horas')));
  assert(issues({ ...base, participants: [] }).some(message => message.includes('sin participantes')));
  assert(issues({ ...base, participants: [{ ...base.participants[0], nombre: '' }] }).some(message => message.includes('sin nombre')));

  const other = { ...clone(base), id: 'p2', grupo: 'I-102', page: 2 };
  other.participants[0].id = 'p2-1';
  control('sabadellCenter').value = '1';
  control('sabadellDestination').value = 'nueva';
  control('sabadellStart').value = base.fecha_inicio;
  control('sabadellEnd').value = base.fecha_fin;
  render([clone(base), other]);
  assert(nodes.every(node => !node.open), 'Los grupos sin incidencias no requieren abrirse.');
  assert(!control('sabadellGroups').innerHTML.includes('data-confirmed'));
  await run('sabadellReview()');
  assert.equal(requests.length, 1, 'La revisión continúa sin marcar ningún grupo.');
  assert.equal(requests[0].groups.length, 2, 'Se envían todos los grupos, no solo la muestra revisada.');
  assert(!Object.hasOwn(requests[0].groups[0], 'confirmed'));
  assert.equal(control('sabadellSave').hidden, false);

  duplicate.participants[1].exclude = false;
  render([duplicate, other]);
  assert.equal(nodes[0].open, true, 'El grupo con incidencias se abre automáticamente.');
  assert.equal(nodes[1].open, false);
  assert.equal(nodes[0].elements['[data-issue-status]'].textContent, 'Requiere atención');
  await assert.rejects(run('sabadellReview()'), /Resuelve las incidencias del grupo I-101/);
  assert.equal(requests.length, 1, 'Un duplicado sin resolver no puede llegar al guardado.');
  assert.equal(control('sabadellSave').hidden, true);
  nodes[0].people[1].inputs.exclude.checked = true;
  run('sabadellProgress()');
  assert.equal(nodes[0].elements['[data-issue-status]'].textContent, 'Sin incidencias detectadas');
  await run('sabadellReview()');
  assert.equal(requests.length, 2);
  assert.equal(requests[1].groups[0].participants[1].exclude, true);

  reply = { success: false, message: 'Grupo I-101 (página 1): Fila 2: Día desconocido.' };
  await assert.rejects(run('sabadellReview()'), /Día desconocido/);
  assert.equal(nodes[0].elements['[data-issue-status]'].textContent, 'Requiere atención', 'Los errores adicionales del servidor también se señalan en su grupo.');
  assert.equal(control('sabadellSave').hidden, true);
  assert.equal(nodes[0].elements.summary.focused, true);
  console.log('[OK] Revisión por muestra sin casillas; incidencias visibles, resolución de duplicados y validación del servidor.');
})().catch(error => { console.error(error); process.exitCode = 1; });
