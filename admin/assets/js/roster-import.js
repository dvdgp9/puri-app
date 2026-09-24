function selectedRosterImportMode(form) {
  return form.querySelector('input[name="mode"]:checked')?.value || 'sync';
}

function confirmRosterImportPreview(counts, mode, activityName, deactivationNames = []) {
  return new Promise(resolve => {
    const values = {
      mantenidos: Number(counts.mantenidos) || 0,
      nuevos: Number(counts.nuevos) || 0,
      reactivados: Number(counts.reactivados) || 0,
      desactivados: Number(counts.desactivados) || 0
    };
    const hasChanges = values.nuevos + values.reactivados + values.desactivados > 0;
    const previousFocus = document.activeElement;
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay show roster-preview-overlay';
    overlay.innerHTML = `
      <div class="modal roster-preview-modal" role="dialog" aria-modal="true" aria-labelledby="rosterPreviewTitle" aria-describedby="rosterPreviewDescription">
        <div class="modal-header"><h2 class="modal-title" id="rosterPreviewTitle">Revisa el listado antes de guardarlo</h2></div>
        <div class="modal-body">
          <p id="rosterPreviewDescription" class="roster-preview-description"></p>
          <dl class="roster-preview-counts">
            <div><dt>Siguen en la actividad</dt><dd data-count="mantenidos"></dd></div>
            <div><dt>Personas nuevas</dt><dd data-count="nuevos"></dd></div>
            <div><dt>Vuelven a estar activas</dt><dd data-count="reactivados"></dd></div>
            <div class="roster-preview-removals"><dt>Se desactivan</dt><dd data-count="desactivados"></dd></div>
          </dl>
          <p class="roster-preview-consequence"></p>
          <div class="roster-preview-names" hidden><strong>Personas que se desactivarán</strong><ul></ul><p></p></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary roster-preview-cancel">Volver al archivo</button>
          <button type="button" class="btn btn-primary roster-preview-confirm"></button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
    const description = overlay.querySelector('#rosterPreviewDescription');
    const consequence = overlay.querySelector('.roster-preview-consequence');
    const confirmButton = overlay.querySelector('.roster-preview-confirm');
    description.textContent = `Actividad: ${activityName || 'seleccionada'}. ${mode === 'sync' ? 'Has indicado que el archivo contiene el listado completo.' : 'Has indicado que el archivo solo contiene altas o reincorporaciones.'}`;
    Object.entries(values).forEach(([key, value]) => {
      overlay.querySelector(`[data-count="${key}"]`).textContent = String(value);
    });
    if (!hasChanges) {
      consequence.textContent = 'No hay cambios que aplicar. Puedes volver al archivo.';
      confirmButton.hidden = true;
    } else if (mode === 'sync' && values.desactivados > 0) {
      consequence.classList.add('roster-preview-warning');
      consequence.textContent = 'Las personas ausentes dejarán de aparecer en el listado actual. Sus asistencias y evaluaciones seguirán en los informes.';
      if (Array.isArray(deactivationNames) && deactivationNames.length > 0) {
        const namesBox = overlay.querySelector('.roster-preview-names');
        const list = namesBox.querySelector('ul');
        deactivationNames.forEach(name => {
          const item = document.createElement('li');
          item.textContent = String(name);
          list.appendChild(item);
        });
        const remaining = values.desactivados - deactivationNames.length;
        namesBox.querySelector('p').textContent = remaining > 0 ? `Y ${remaining} persona${remaining === 1 ? '' : 's'} más.` : '';
        namesBox.hidden = false;
      }
    } else {
      consequence.textContent = 'Se conservarán los registros y el historial de todas las personas.';
    }
    confirmButton.textContent = mode === 'sync' ? 'Confirmar actualización' : 'Confirmar altas';

    function finish(accepted) {
      document.removeEventListener('keydown', onKeyDown);
      overlay.remove();
      previousFocus?.focus?.();
      resolve(accepted);
    }
    function onKeyDown(event) {
      if (event.key === 'Escape') finish(false);
      if (event.key !== 'Tab') return;
      const controls = Array.from(overlay.querySelectorAll('button:not([hidden])'));
      const first = controls[0];
      const last = controls[controls.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault(); last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault(); first.focus();
      }
    }
    document.addEventListener('keydown', onKeyDown);
    overlay.querySelector('.roster-preview-cancel').addEventListener('click', () => finish(false));
    confirmButton.addEventListener('click', () => finish(true));
    overlay.addEventListener('click', event => { if (event.target === overlay) finish(false); });
    overlay.querySelector('.roster-preview-cancel').focus();
  });
}
