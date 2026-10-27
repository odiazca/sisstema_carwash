/* Utilidades compartidas por el Asistente IA y el Chat IA */
(() => {
  const C = (window.IA_CFG || {});
  window.APP = { base: C.base || '', csrf: C.csrf || '' };
  window.esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  window.fmt = (n, d = 2) => {
    let [a, b] = Number(n).toFixed(d).split('.'); a = a.replace(/\B(?=(\d{3})+(?!\d))/g, C.sm ?? ',');
    return b ? a + (C.sd ?? '.') + b : a;
  };
  window.money = (n) => { const v = window.fmt(n, C.dec ?? 2); return (C.pos || 'antes') === 'antes' ? (C.sim || 'S/') + ' ' + v : v + ' ' + (C.sim || 'S/'); };
  window.toast = (msg, type = 'success') => {
    let box = document.getElementById('iaToasts');
    if (!box) { box = document.createElement('div'); box.id = 'iaToasts'; box.style.cssText = 'position:fixed;top:14px;right:14px;z-index:2000;display:flex;flex-direction:column;gap:8px'; document.body.appendChild(box); }
    const d = document.createElement('div'); d.className = 'alert alert-' + (type === 'danger' ? 'danger' : type === 'warning' ? 'warning' : 'success') + ' shadow-sm py-2 px-3 mb-0'; d.textContent = msg;
    box.appendChild(d); setTimeout(() => d.remove(), 3500);
  };
  /** api('asistente/api?a=estado') GET · api('asistente/api', {a:'x'}) POST JSON con CSRF */
  window.api = async (path, body) => {
    const opt = { credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
    if (body !== undefined) { opt.method = 'POST'; opt.headers['Content-Type'] = 'application/json'; opt.headers['X-CSRF-Token'] = window.APP.csrf; opt.body = JSON.stringify(body); }
    let r; try { r = await fetch(window.APP.base + '/' + path, opt); } catch (e) { throw new Error('No hay conexión con el servidor.'); }
    let j = null; try { j = await r.json(); } catch (e) {}
    if (!j) throw new Error('Respuesta inválida del servidor (' + r.status + ').');
    if (!j.ok) throw new Error(j.error || 'Error inesperado');
    return j;
  };
})();
