const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const context = vm.createContext({ document: { addEventListener() {} }, window: {} });
vm.runInContext(fs.readFileSync('public/assets/js/evaluaciones-monitor.js', 'utf8'), context);

const detail = { participantes: [
  { id: 10, resultados: [
    { campo_id: 1, valor_numero: null, calificador: null },
    { campo_id: 2, valor_numero: 5, calificador: 'exacto' },
    { campo_id: 3, valor_texto: 'Anterior' }
  ] }
] };
const drafts = {
  '10:1': { inscrito_id: 10, campo_id: 1, tipo_dato: 'entero', value: '12', qualifier: 'exacto' },
  '10:2': { inscrito_id: 10, campo_id: 2, tipo_dato: 'entero', value: '5', qualifier: 'mayor_que' },
  '10:3': { inscrito_id: 10, campo_id: 3, tipo_dato: 'texto_corto', value: '', qualifier: 'exacto' }
};
const changes = JSON.parse(JSON.stringify(context.monitorEvaluationPendingChanges(drafts, detail)));
assert.strictEqual(changes.length, 3);
assert.deepStrictEqual(changes[0], { campo_id: 1, inscrito_id: 10, estado: 'medido', valor_numero: '12', calificador: 'exacto' });
assert.deepStrictEqual(changes[1], { campo_id: 2, inscrito_id: 10, estado: 'medido', valor_numero: '5', calificador: 'mayor_que' });
assert.deepStrictEqual(changes[2], { campo_id: 3, inscrito_id: 10, estado: 'sin_evaluar' });
assert.strictEqual(context.monitorEvaluationPendingChanges({ '10:2': { ...drafts['10:2'], qualifier: 'exacto' } }, detail).length, 0);
console.log('[OK] Finalizar incluye los cambios pendientes de todas las pruebas.');
