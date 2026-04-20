async function loadAssets() {
  const [assetsRes, healthRes] = await Promise.all([
    fetch('api/assets.php').then(r => r.json()),
    fetch('api/healthcheck.php').then(r => r.json()).catch(() => ({ statuses: {} }))
  ]);
  const statuses = healthRes.statuses || {};
  const grid = document.getElementById('assets');
  const cats = new Set();
  grid.innerHTML = '';

  let online = 0, offline = 0;
  assetsRes.forEach(a => {
    cats.add(a.category);
    const st = statuses[a.id] || 'checking';
    if (st === 'online') online++; else if (st === 'offline') offline++;
    const card = document.createElement('div');
    card.className = 'asset-card status-' + st;
    card.dataset.name = (a.name + ' ' + a.tags).toLowerCase();
    card.dataset.category = a.category;
    card.innerHTML = `
      <div class="asset-head">
        <span class="dot ${st}"></span>
        <strong>${escapeHtml(a.name)}</strong>
        <span class="env">${escapeHtml(a.environment)}</span>
      </div>
      <p class="muted small">${escapeHtml(a.description || '')}</p>
      <div class="asset-meta">
        <span>📁 ${escapeHtml(a.category)}</span>
        ${a.ip_lan ? `<span>🖥 ${escapeHtml(a.ip_lan)}</span>` : ''}
        ${a.port ? `<span>:${escapeHtml(a.port)}</span>` : ''}
        ${a.url ? `<a href="${a.url}" target="_blank" rel="noopener">↗ abrir</a>` : ''}
      </div>
    `;
    grid.appendChild(card);
  });

  document.getElementById('stats').innerHTML =
    `<span class="stat">Total: <b>${assetsRes.length}</b></span>
     <span class="stat ok">Online: <b>${online}</b></span>
     <span class="stat err">Offline: <b>${offline}</b></span>
     <span class="stat muted">Última verificação: ${new Date(healthRes.checked_at || Date.now()).toLocaleTimeString()}</span>`;

  const sel = document.getElementById('category');
  const current = sel.value;
  sel.innerHTML = '<option value="">Todas categorias</option>' + [...cats].sort().map(c => `<option ${c===current?'selected':''}>${escapeHtml(c)}</option>`).join('');
  applyFilter();
}

function applyFilter() {
  const q = document.getElementById('search').value.toLowerCase();
  const c = document.getElementById('category').value;
  document.querySelectorAll('.asset-card').forEach(el => {
    const matchQ = !q || el.dataset.name.includes(q);
    const matchC = !c || el.dataset.category === c;
    el.style.display = (matchQ && matchC) ? '' : 'none';
  });
}

function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

document.getElementById('search').addEventListener('input', applyFilter);
document.getElementById('category').addEventListener('change', applyFilter);
document.getElementById('refresh').addEventListener('click', loadAssets);
const newBtn = document.getElementById('newAsset');
if (newBtn) newBtn.addEventListener('click', () => location.href = 'assets.php');

loadAssets();
setInterval(loadAssets, 30000);
