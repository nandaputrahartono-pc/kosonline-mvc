document.addEventListener('DOMContentLoaded', () => {
  const AUTO_DISMISS_MS = 4200;

  function styleCloseButton(button) {
    button.type = 'button';
    button.setAttribute('aria-label', 'Tutup notifikasi');
    button.dataset.flashClose = 'true';
    button.innerHTML = '&times;';
    button.style.cssText = [
      'margin-left:auto',
      'width:28px',
      'height:28px',
      'display:grid',
      'place-items:center',
      'border:0',
      'border-radius:999px',
      'background:rgba(15,23,42,.08)',
      'color:inherit',
      'font-size:20px',
      'line-height:1',
      'cursor:pointer',
      'flex-shrink:0',
    ].join(';');
  }

  function dismissNotification(notification) {
    if (!notification || notification.dataset.dismissed === 'true') return;
    notification.dataset.dismissed = 'true';
    notification.style.transition = 'opacity .25s ease, transform .25s ease';
    notification.style.opacity = '0';
    notification.style.transform = 'translateY(-8px)';
    notification.style.pointerEvents = 'none';
    window.setTimeout(() => notification.remove(), 260);
  }

  function prepareNotification(notification) {
    if (!notification || notification.dataset.notificationReady === 'true') return;
    notification.dataset.notificationReady = 'true';

    let closeButton = notification.querySelector('[data-flash-close], .btn-close');
    if (!closeButton) {
      closeButton = document.createElement('button');
      styleCloseButton(closeButton);
      notification.appendChild(closeButton);
    } else {
      closeButton.dataset.flashClose = 'true';
    }

    closeButton.addEventListener('click', () => dismissNotification(notification));
    window.setTimeout(() => dismissNotification(notification), AUTO_DISMISS_MS);
  }

  function notificationSelector() {
    return [
      '.app-flash',
      '.member-flash',
      '.admin-flash',
      '.admin-form-flash',
      '.alert-fixed',
      '.alert.alert-success',
      '.alert.alert-danger',
      '.contact-alert',
      '[data-notification]',
    ].join(',');
  }

  document.querySelectorAll(notificationSelector()).forEach(prepareNotification);

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof HTMLElement)) return;
        if (node.matches(notificationSelector())) {
          prepareNotification(node);
        }
        node.querySelectorAll?.(notificationSelector()).forEach(prepareNotification);
      });
    });
  });
  observer.observe(document.body, { childList: true, subtree: true });

  function stackClass() {
    if (document.querySelector('.member-flash-stack')) return 'member-flash-stack';
    if (document.querySelector('.admin-flash-stack')) return 'admin-flash-stack';
    if (document.querySelector('.admin-form-flash-stack')) return 'admin-form-flash-stack';
    return 'app-flash-stack';
  }

  function findOrCreateStack() {
    const className = stackClass();
    let stack = document.querySelector(`.${className}`);
    if (stack) return stack;

    stack = document.createElement('div');
    stack.className = className;
    stack.style.cssText = [
      'position:fixed',
      'top:24px',
      'right:24px',
      'z-index:3000',
      'display:grid',
      'gap:12px',
      'width:min(420px,calc(100vw - 32px))',
    ].join(';');
    document.body.appendChild(stack);
    return stack;
  }

  function toastClass(type, stack) {
    if (stack.classList.contains('member-flash-stack')) {
      return `member-flash ${type === 'error' ? 'danger' : 'success'}`;
    }
    if (stack.classList.contains('admin-flash-stack')) {
      return `admin-flash ${type === 'error' ? 'danger' : 'success'}`;
    }
    if (stack.classList.contains('admin-form-flash-stack')) {
      return `admin-form-flash ${type === 'error' ? 'danger' : 'success'}`;
    }
    return `app-flash ${type === 'error' ? 'app-flash-error' : 'app-flash-success'}`;
  }

  window.showAppNotification = function showAppNotification(message, type = 'success') {
    const stack = findOrCreateStack();
    const toast = document.createElement('div');
    toast.className = toastClass(type, stack);
    toast.dataset.notification = 'true';
    toast.style.cssText = toast.style.cssText || [
      'display:flex',
      'align-items:center',
      'gap:12px',
      'padding:14px 16px',
      'border-radius:18px',
      'background:#fff',
      'box-shadow:0 18px 45px rgba(15,23,42,.12)',
    ].join(';');
    toast.innerHTML = `
      <i class="fa-solid ${type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'}"></i>
      <span>${escapeHtml(message)}</span>
    `;
    stack.appendChild(toast);
    prepareNotification(toast);
  };

  function escapeHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function updateWishlistButtons(roomId, saved) {
    document.querySelectorAll('form[action*="/wishlist/toggle"]').forEach((form) => {
      const input = form.querySelector('input[name="id_kamar"]');
      if (!input || String(input.value) !== String(roomId)) return;

      const button = form.querySelector('button[type="submit"], button:not([type])');
      const icon = button ? button.querySelector('i') : null;

      if (button) {
        button.classList.toggle('saved', saved);
        button.setAttribute('aria-label', saved ? 'Hapus dari wishlist' : 'Simpan kamar');
      }

      if (icon) {
        icon.classList.toggle('fa-solid', saved);
        icon.classList.toggle('fa-regular', !saved);
      }

      if (button && button.classList.contains('wishlist-detail-btn')) {
        button.innerHTML = `<i class="${saved ? 'fa-solid' : 'fa-regular'} fa-heart me-2"></i>${saved ? 'Tersimpan di Wishlist' : 'Simpan ke Wishlist'}`;
      }
    });
  }

  function removeWishlistCard(form) {
    const card = form.closest('.order-card');
    if (!card) return;

    card.style.transition = 'opacity .22s ease, transform .22s ease';
    card.style.opacity = '0';
    card.style.transform = 'translateY(-8px)';
    window.setTimeout(() => {
      const list = card.parentElement;
      card.remove();
      if (list && !list.querySelector('.order-card')) {
        const empty = document.createElement('div');
        empty.className = 'empty-state';
        empty.innerHTML = `
          <i class="fa-regular fa-heart"></i>
          <h3>Wishlist masih kosong</h3>
          <p>Simpan kamar dari halaman detail atau daftar kamar biar gampang dibandingkan nanti.</p>
        `;
        list.appendChild(empty);
      }
    }, 240);
  }

  document.querySelectorAll('form[action*="/wishlist/toggle"]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const button = form.querySelector('button[type="submit"], button:not([type])');
      const formData = new FormData(form);
      const roomId = formData.get('id_kamar');

      if (button) button.disabled = true;
      try {
        const response = await fetch(form.action, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'fetch',
          },
          credentials: 'same-origin',
          body: formData,
        });

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
          form.submit();
          return;
        }

        const data = await response.json();
        if (!response.ok || !data.ok) {
          window.showAppNotification(data.message || 'Wishlist gagal diperbarui.', 'error');
          return;
        }

        updateWishlistButtons(data.room_id || roomId, Boolean(data.saved));
        if (!data.saved && form.querySelector('.wishlist-remove')) {
          removeWishlistCard(form);
        }
        window.showAppNotification(data.message || 'Wishlist diperbarui.', 'success');
      } catch (error) {
        form.submit();
      } finally {
        if (button) button.disabled = false;
      }
    });
  });
});
