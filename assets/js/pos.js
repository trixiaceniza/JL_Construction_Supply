(() => {
  const cards = [...document.querySelectorAll('.product-card')];
  const search = document.getElementById('productSearch');
  const body = document.getElementById('cartBody');
  const table = document.getElementById('cartTable');
  const empty = document.getElementById('cartEmpty');
  const totalEl = document.getElementById('grandTotal');
  const submit = document.getElementById('completeSale');
  const cart = new Map();
  const peso = n => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  cards.forEach(card => card.addEventListener('click', () => {
    const id = card.dataset.id;
    const existing = cart.get(id);
    const qty = existing ? existing.qty + 1 : 1;
    if (qty > Number(card.dataset.stock)) { alert('Not enough stock available.'); return; }
    cart.set(id, { id, name: card.dataset.name, unit: card.dataset.unit || 'pcs', price: Number(card.dataset.price), stock: Number(card.dataset.stock), qty });
    render();
  }));
  search?.addEventListener('input', () => {
    const q = search.value.toLowerCase();
    cards.forEach(c => c.hidden = !(c.dataset.name.toLowerCase().includes(q) || (c.dataset.category || '').toLowerCase().includes(q)));
  });
  function render() {
    body.innerHTML = '';
    let total = 0;
    cart.forEach(item => {
      const line = item.qty * item.price; total += line;
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${escapeHtml(item.name)}<input type="hidden" name="items[${item.id}]" value="${item.qty}"></td><td>${peso(item.price)}</td><td><div class="qty-controls"><button type="button" data-action="minus" data-id="${item.id}">−</button><span>${item.qty}</span><button type="button" data-action="plus" data-id="${item.id}">+</button></div></td><td>${peso(line)}</td><td><button type="button" class="btn danger small-btn" data-action="remove" data-id="${item.id}">Remove</button></td>`;
      body.appendChild(tr);
    });
    table.hidden = cart.size === 0; empty.hidden = cart.size > 0;
    totalEl.textContent = peso(total); submit.disabled = cart.size === 0;
  }
  body.addEventListener('click', e => {
    const btn = e.target.closest('button[data-action]'); if (!btn) return;
    const item = cart.get(btn.dataset.id); if (!item) return;
    if (btn.dataset.action === 'plus') { if (item.qty < item.stock) item.qty++; else alert('Maximum available stock reached.'); }
    if (btn.dataset.action === 'minus') { item.qty--; if (item.qty <= 0) cart.delete(btn.dataset.id); }
    if (btn.dataset.action === 'remove') cart.delete(btn.dataset.id);
    render();
  });
  document.getElementById('saleForm')?.addEventListener('submit', e => {
    if (!cart.size) { e.preventDefault(); alert('Add at least one product.'); return; }
    if (!confirm('Save this sale and deduct the quantities from inventory? No payment will be processed.')) e.preventDefault();
  });
  function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
})();
