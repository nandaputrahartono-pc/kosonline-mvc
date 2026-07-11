// Modal konfirmasi custom pengganti window.confirm().
// Pemakaian:
//   1. Form: tambahkan atribut data-confirm="Pesan konfirmasi" (opsional data-confirm-ok="Label" dan data-confirm-variant="primary").
//   2. JS:   window.appConfirm('Pesan').then((ok) => { ... });
(() => {
  const STYLE = `
    .app-confirm-overlay {
      position: fixed; inset: 0; z-index: 4000;
      display: flex; align-items: center; justify-content: center;
      padding: 20px;
      background: rgba(15, 23, 42, 0.55);
      backdrop-filter: blur(3px);
      opacity: 0; transition: opacity .18s ease;
    }
    .app-confirm-overlay.show { opacity: 1; }
    .app-confirm-dialog {
      width: min(420px, 100%);
      background: var(--card-bg, #ffffff);
      color: var(--text-main, #0f172a);
      border: 1px solid var(--border-soft, #e2e8f0);
      border-radius: 20px;
      padding: 26px 24px 22px;
      box-shadow: 0 24px 60px rgba(15, 23, 42, .25);
      text-align: center;
      transform: translateY(14px) scale(.96);
      transition: transform .18s ease;
    }
    .app-confirm-overlay.show .app-confirm-dialog { transform: translateY(0) scale(1); }
    .app-confirm-icon {
      width: 56px; height: 56px; margin: 0 auto 14px;
      display: grid; place-items: center;
      border-radius: 50%;
      background: #fee2e2; color: #dc2626;
      font-size: 24px;
    }
    .app-confirm-dialog.variant-primary .app-confirm-icon {
      background: var(--accent-blue-soft, #dbeafe); color: var(--accent-blue, #2563eb);
    }
    .app-confirm-title { font-size: 1.05rem; font-weight: 800; margin: 0 0 6px; }
    .app-confirm-message { color: var(--text-muted, #64748b); margin: 0 0 20px; line-height: 1.6; font-size: .95rem; }
    .app-confirm-actions { display: flex; gap: 10px; justify-content: center; }
    .app-confirm-actions button {
      border: 0; border-radius: 12px; padding: 11px 22px;
      font-weight: 700; font-size: .92rem; cursor: pointer;
      transition: filter .15s ease, transform .15s ease;
    }
    .app-confirm-actions button:hover { filter: brightness(.95); transform: translateY(-1px); }
    .app-confirm-cancel { background: var(--bg-main, #f1f5f9); color: var(--text-main, #0f172a); }
    .app-confirm-ok { background: #dc2626; color: #fff; }
    .app-confirm-dialog.variant-primary .app-confirm-ok { background: var(--accent-blue, #2563eb); }
  `;

  let overlay = null;
  let resolver = null;

  function ensureModal() {
    if (overlay) return;

    const style = document.createElement('style');
    style.textContent = STYLE;
    document.head.appendChild(style);

    overlay = document.createElement('div');
    overlay.className = 'app-confirm-overlay';
    overlay.hidden = true;
    overlay.innerHTML = `
      <div class="app-confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="app-confirm-title" aria-describedby="app-confirm-message">
        <div class="app-confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h5 class="app-confirm-title" id="app-confirm-title">Konfirmasi</h5>
        <p class="app-confirm-message" id="app-confirm-message"></p>
        <div class="app-confirm-actions">
          <button type="button" class="app-confirm-cancel">Batal</button>
          <button type="button" class="app-confirm-ok">Ya, Lanjutkan</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);

    overlay.querySelector('.app-confirm-cancel').addEventListener('click', () => settle(false));
    overlay.querySelector('.app-confirm-ok').addEventListener('click', () => settle(true));
    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) settle(false);
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !overlay.hidden) settle(false);
    });
  }

  function settle(result) {
    if (!resolver) return;
    const resolve = resolver;
    resolver = null;
    overlay.classList.remove('show');
    window.setTimeout(() => {
      overlay.hidden = true;
      resolve(result);
    }, 160);
  }

  window.appConfirm = function appConfirm(message, options = {}) {
    ensureModal();

    // Kalau masih ada dialog aktif, batalkan dulu supaya promise lama tidak menggantung.
    if (resolver) settle(false);

    const dialog = overlay.querySelector('.app-confirm-dialog');
    dialog.classList.toggle('variant-primary', options.variant === 'primary');
    overlay.querySelector('.app-confirm-message').textContent = String(message || 'Lanjutkan aksi ini?');
    overlay.querySelector('.app-confirm-ok').textContent = options.okLabel || 'Ya, Lanjutkan';

    overlay.hidden = false;
    requestAnimationFrame(() => overlay.classList.add('show'));
    overlay.querySelector('.app-confirm-ok').focus();

    return new Promise((resolve) => {
      resolver = resolve;
    });
  };

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    const message = form.dataset.confirm;
    if (!message || form.dataset.confirmed === '1') return;

    event.preventDefault();
    event.stopPropagation();

    window.appConfirm(message, {
      okLabel: form.dataset.confirmOk || undefined,
      variant: form.dataset.confirmVariant || undefined,
    }).then((ok) => {
      if (!ok) return;
      form.dataset.confirmed = '1';
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    });
  }, true);
})();
