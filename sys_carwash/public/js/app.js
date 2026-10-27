document.addEventListener('DOMContentLoaded', () => {
  const sb = document.getElementById('sidebar');
  document.querySelectorAll('[data-toggle-sidebar]').forEach(b => b.addEventListener('click', () => sb && sb.classList.toggle('open')));
  // ===== Menú vertical =====
  if (sb) {
    const LS = { get: k => { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} } };
    const groups = [...sb.querySelectorAll('.sb-group')];
    let closed = []; try { closed = JSON.parse(LS.get('sbClosed') || '[]'); } catch (e) {}
    const save = () => LS.set('sbClosed', JSON.stringify(groups.filter(g => g.classList.contains('closed')).map(g => g.dataset.g)));
    groups.forEach(g => {
      if (closed.includes(g.dataset.g) && g.dataset.active !== '1') g.classList.add('closed');
      const t = g.querySelector('.sb-title');
      t.setAttribute('aria-expanded', !g.classList.contains('closed'));
      t.addEventListener('click', () => { g.classList.toggle('closed'); t.setAttribute('aria-expanded', !g.classList.contains('closed')); save(); });
    });
    const act = sb.querySelector('.sb-link.active'); if (act) act.scrollIntoView({ block: 'nearest' });
    sb.querySelectorAll('[data-sb-collapse]').forEach(b => b.addEventListener('click', () => { const m = document.body.classList.toggle('sb-mini'); LS.set('sbMini', m ? '1' : '0'); }));
    const q = document.getElementById('sbSearch'), empty = document.getElementById('sbEmpty');
    if (q) {
      q.addEventListener('input', () => {
        const v = q.value.trim().toLowerCase(); let any = false;
        groups.forEach(g => {
          let n = 0;
          g.querySelectorAll('.sb-link').forEach(a => { const ok = !v || a.dataset.label.includes(v); a.style.display = ok ? '' : 'none'; if (ok) n++; });
          g.style.display = n ? '' : 'none'; if (n) any = true;
          g.classList.toggle('force-open', !!v && n > 0);
          if (v) g.classList.remove('closed'); else if (closed.includes(g.dataset.g) && g.dataset.active !== '1') g.classList.add('closed');
        });
        if (empty) empty.hidden = any || !v;
      });
      q.addEventListener('keydown', e => { if (e.key === 'Escape') { q.value = ''; q.dispatchEvent(new Event('input')); q.blur(); } if (e.key === 'Enter') { const f = [...sb.querySelectorAll('.sb-link')].find(a => a.style.display !== 'none' && a.offsetParent); if (f) location.href = f.href; } });
      document.addEventListener('keydown', e => { if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test((document.activeElement || {}).tagName) && !document.activeElement.isContentEditable) { e.preventDefault(); document.body.classList.remove('sb-mini'); sb.classList.add('open'); q.focus(); } });
    }
  }
  // confirmación de borrado
  document.querySelectorAll('[data-confirm]').forEach(el => el.addEventListener('click', e => {
    if (!confirm(el.dataset.confirm)) e.preventDefault();
  }));
  // filtro rápido de tablas
  document.querySelectorAll('[data-table-filter]').forEach(inp => {
    const t = document.querySelector(inp.dataset.tableFilter);
    inp.addEventListener('input', () => {
      const q = inp.value.toLowerCase();
      t.querySelectorAll('tbody tr').forEach(tr => tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none');
    });
  });
  // mostrar/ocultar contraseña
  document.querySelectorAll('.toggle-pass').forEach(b => b.addEventListener('click', () => {
    const i = document.querySelector(b.dataset.target);
    i.type = i.type === 'password' ? 'text' : 'password';
    b.querySelector('i').className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  }));
  // auto-cerrar alertas
  setTimeout(() => document.querySelectorAll('.alert-auto').forEach(a => bootstrap.Alert.getOrCreateInstance(a).close()), 5000);
});
