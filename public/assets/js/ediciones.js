/* Shared by admin and monitor navigation; the center's default is resolved on the server. */
const ProgramEdition = {
  id: window.__EDITION_CTX__?.id ?? null,
  query() { return this.id === null ? '' : `&edicion_id=${encodeURIComponent(this.id)}`; },
};
document.addEventListener('DOMContentLoaded', () => {
  const selector = document.getElementById('edition-select');
  selector?.addEventListener('change', () => {
    const url = new URL(window.location.href);
    url.searchParams.set('edicion_id', selector.value);
    window.location.assign(url.href);
  });
  // Keep course context on breadcrumbs, return links and monitor navigation.
  document.querySelectorAll('a[href]').forEach(link => {
    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || !/(?:center|installation|activity|instalaciones|actividades|asistencia|evaluacion)\.php$/.test(url.pathname)) return;
    if (ProgramEdition.id !== null) url.searchParams.set('edicion_id', ProgramEdition.id);
    link.href = url.href;
  });
});
