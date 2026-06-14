document.addEventListener('DOMContentLoaded', () => {
  const widget = document.querySelector('[data-chat-realtime]');
  if (!widget) return;

  const messagesEl = widget.querySelector('[data-chat-messages]');
  const scrollEl = widget.querySelector('[data-chat-scroll]') || messagesEl;
  const contextEl = widget.querySelector('[data-chat-context]');
  const typingEl = widget.querySelector('[data-chat-typing]');
  const form = widget.querySelector('[data-chat-compose]');
  const textarea = form ? form.querySelector('textarea[name="isi_pesan"]') : null;
  const threadInput = form ? form.querySelector('input[name="id_thread"]') : null;
  const roomInput = form ? form.querySelector('input[name="id_kamar"]') : null;
  const submitButton = form ? form.querySelector('button[type="submit"], button:not([type])') : null;

  const config = {
    threadId: widget.dataset.threadId || '',
    fetchUrl: widget.dataset.fetchUrl || '',
    typingUrl: widget.dataset.typingUrl || '',
    csrf: widget.dataset.csrf || '',
    meType: widget.dataset.meType || 'user',
    peerLabel: widget.dataset.peerLabel || 'Lawan bicara',
    meLabel: widget.dataset.meLabel || 'Saya',
    wsUrl: widget.dataset.wsUrl || '',
  };

  let lastRenderedSignature = '';
  let typingTimer = null;
  let pollTimer = null;
  let isPolling = false;
  let socket = null;
  let socketConnected = false;

  function escapeHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function messageSignature(messages) {
    return messages.map((message) => `${message.id_message}:${message.sender_type}:${message.tipe_pesan || 'text'}`).join('|');
  }

  function scrollToBottom() {
    if (!scrollEl) return;
    scrollEl.scrollTop = scrollEl.scrollHeight;
  }

  function resizeTextarea() {
    if (!textarea) return;
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 130) + 'px';
  }

  function rupiah(value) {
    return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
  }

  function initials(label) {
    const words = String(label || 'KO').trim().split(/\s+/).filter(Boolean);
    const letters = words.map((word) => word.charAt(0).toUpperCase()).join('').slice(0, 2);
    return letters || 'KO';
  }

  function renderRoomCard(card, className) {
    return `
      <a href="${escapeHtml(card.detail_url || '#')}" class="${className}">
        <img src="${escapeHtml(card.image_url || '')}" alt="Foto kamar">
        <div>
          <strong>${escapeHtml(card.title || 'Kamar Kos')}</strong>
          <span>${escapeHtml(card.subtitle || '')}</span>
          <b>${rupiah(card.harga || 0)}</b>
        </div>
        <small>${escapeHtml(card.status || '-')}</small>
      </a>
    `;
  }

  function renderContext(card) {
    if (!contextEl) return;
    if (!card) {
      contextEl.innerHTML = '';
      contextEl.hidden = true;
      return;
    }

    contextEl.hidden = false;
    contextEl.innerHTML = renderRoomCard(card, config.meType === 'admin' ? 'admin-chat-room-card' : 'chat-room-card');
  }

  function renderRoomCardMessage(message, grouped = false) {
    const card = message.room_card || null;
    if (!card) return '';

    const mine = message.sender_type === config.meType;
    const wrapperClass = config.meType === 'admin'
      ? 'admin-chat-sent-room-card user'
      : 'chat-sent-room-card mine';
    const cardClass = config.meType === 'admin'
      ? 'admin-chat-room-card admin-chat-room-card-message'
      : 'chat-room-card chat-room-card-message';
    const label = mine ? config.meLabel : config.peerLabel;
    const groupedClass = grouped ? ' grouped' : '';

    return `
      <div class="${wrapperClass}${groupedClass}" data-initials="${escapeHtml(initials(label))}">
        ${renderRoomCard(card, cardClass)}
      </div>
    `;
  }

  function renderMessages(messages) {
    if (!messagesEl) return;

    const signature = messageSignature(messages);
    if (signature === lastRenderedSignature) return;

    lastRenderedSignature = signature;
    if (messages.length === 0) {
      messagesEl.innerHTML = `
        <div class="empty-state compact">
          <i class="fa-regular fa-comments"></i>
          <p>Mulai chat baru.</p>
        </div>
      `;
      return;
    }

    const renderedMessages = messages.map((message) => {
      const isRoomCardOnly = (message.tipe_pesan || 'text') === 'room_card';
      const hasGroupedCard = Boolean(message.room_card) && !isRoomCardOnly;
      const cardMarkup = message.room_card ? renderRoomCardMessage(message, hasGroupedCard) : '';
      if (isRoomCardOnly) {
        return cardMarkup;
      }

      const mine = message.sender_type === config.meType;
      const bubbleClass = mine ? 'mine' : (config.meType === 'admin' ? 'user' : 'admin');
      const groupedClass = hasGroupedCard ? ' grouped-with-card' : '';
      const label = mine ? config.meLabel : config.peerLabel;

      return `${cardMarkup}
        <div class="${config.meType === 'admin' ? 'admin-chat-bubble' : 'chat-bubble'} ${bubbleClass}${groupedClass}" data-initials="${escapeHtml(initials(label))}">
          <span>${escapeHtml(label)}</span>
          <p>${escapeHtml(message.isi_pesan).replaceAll('\n', '<br>')}</p>
          <small>${escapeHtml(message.waktu_label || message.dibuat_pada || '')}</small>
        </div>
      `;
    }).join('');

    messagesEl.innerHTML = renderedMessages;
    scrollToBottom();
  }

  function renderTyping(isTyping, peerLabel) {
    if (!typingEl) return;
    if (!isTyping) {
      typingEl.hidden = true;
      typingEl.innerHTML = '';
      return;
    }

    typingEl.hidden = false;
    const bubbleClass = config.meType === 'admin' ? 'admin-chat-bubble user typing-bubble' : 'chat-bubble admin typing-bubble';
    typingEl.innerHTML = `
      <div class="${bubbleClass}" data-initials="${escapeHtml(initials(peerLabel || config.peerLabel))}">
        <span>${escapeHtml(peerLabel || config.peerLabel)}</span>
        <p><i></i><i></i><i></i> sedang mengetik...</p>
      </div>
    `;
  }

  function applyPayload(data) {
    if (!data || !data.ok) return;
    if (data.peer_label) config.peerLabel = data.peer_label;
    if (data.me_label) config.meLabel = data.me_label;
    if (!contextEl || contextEl.dataset.pendingContext !== '1') {
      renderContext(data.context_card || null);
    }
    renderMessages(data.messages || []);
    renderTyping(Boolean(data.peer_typing), data.peer_label);
  }

  async function pollMessages() {
    if (!config.threadId || !config.fetchUrl || isPolling) return;

    isPolling = true;
    try {
      const response = await fetch(`${config.fetchUrl}?thread=${encodeURIComponent(config.threadId)}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      if (!response.ok) return;
      const data = await response.json();
      if (!data.ok) return;

      applyPayload(data);
    } catch (error) {
      // Keep polling quietly; network hiccups should not break the dashboard.
    } finally {
      isPolling = false;
    }
  }

  async function setTyping(isTyping) {
    if (!config.threadId || !config.typingUrl || !config.csrf) return;

    const body = new URLSearchParams();
    body.set('_token', config.csrf);
    body.set('id_thread', config.threadId);
    body.set('is_typing', isTyping ? '1' : '0');

    try {
      await fetch(config.typingUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'fetch',
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'same-origin',
        body,
      });
    } catch (error) {
      // Typing indicators are best-effort.
    }
  }

  if (textarea) {
    textarea.addEventListener('input', () => {
      resizeTextarea();
      setTyping(textarea.value.trim() !== '');
      window.clearTimeout(typingTimer);
      typingTimer = window.setTimeout(() => setTyping(false), 1600);
    });

    textarea.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' || event.shiftKey || event.isComposing) return;
      event.preventDefault();

      if (!textarea.value.trim()) return;
      if (form && typeof form.requestSubmit === 'function') {
        form.requestSubmit(submitButton || undefined);
      } else if (form) {
        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
      }
    });

    textarea.addEventListener('blur', () => setTyping(false));
    resizeTextarea();
  }

  if (form && textarea) {
    form.addEventListener('submit', async (event) => {
      if (!config.threadId) return;
      event.preventDefault();

      const message = textarea.value.trim();
      if (!message) return;

      const body = new URLSearchParams(new FormData(form));
      body.set('id_thread', config.threadId);

      if (submitButton) submitButton.disabled = true;
      try {
        const response = await fetch(form.action, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'fetch',
          },
          credentials: 'same-origin',
          body,
        });

        const data = await response.json();
        if (data.ok) {
          const hadPendingRoom = roomInput && roomInput.value && roomInput.value !== '0';
          textarea.value = '';
          resizeTextarea();
          setTyping(false);
          if (threadInput) threadInput.value = String(data.thread_id || config.threadId);
          if (roomInput) roomInput.value = '';
          if (contextEl && hadPendingRoom) {
            delete contextEl.dataset.pendingContext;
            renderContext(null);
          }
          applyPayload(data);
        } else {
          form.submit();
        }
      } catch (error) {
        form.submit();
      } finally {
        if (submitButton) submitButton.disabled = false;
      }
    });
  }

  function startPolling() {
    if (pollTimer) return;
    pollMessages();
    pollTimer = window.setInterval(pollMessages, 2000);
  }

  function stopPolling() {
    if (!pollTimer) return;
    window.clearInterval(pollTimer);
    pollTimer = null;
  }

  function connectSocket() {
    if (!config.wsUrl || !config.threadId || !('WebSocket' in window)) {
      startPolling();
      return;
    }

    try {
      socket = new WebSocket(config.wsUrl);
    } catch (error) {
      startPolling();
      return;
    }

    socket.addEventListener('open', () => {
      socketConnected = true;
      stopPolling();
      socket.send(JSON.stringify({
        type: 'subscribe',
        threadId: Number(config.threadId),
        role: config.meType,
      }));
    });

    socket.addEventListener('message', (event) => {
      try {
        const data = JSON.parse(event.data);
        if (data.type === 'chat:update') {
          applyPayload(data.payload);
        }
      } catch (error) {
        // Ignore malformed WebSocket frames from development experiments.
      }
    });

    socket.addEventListener('close', () => {
      socketConnected = false;
      startPolling();
      window.setTimeout(connectSocket, 3000);
    });

    socket.addEventListener('error', () => {
      socketConnected = false;
      startPolling();
    });
  }

  connectSocket();
  if (!socketConnected) startPolling();

  window.addEventListener('beforeunload', () => {
    stopPolling();
    if (socket) socket.close();
    setTyping(false);
  });
});
