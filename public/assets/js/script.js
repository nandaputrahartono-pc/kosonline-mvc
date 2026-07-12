document.addEventListener('DOMContentLoaded', () => {
    // --- 1. NAVBAR MOBILE TOGGLE ---
    const menuToggle = document.getElementById('mobile-menu');
    const navMenu = document.querySelector('.nav-menu');

    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('is-active');
            navMenu.classList.toggle('active');
        });

        // Close menu when clicking link
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                menuToggle.classList.remove('is-active');
                navMenu.classList.remove('active');
            });
        });
    }

    // --- 2. STICKY NAVBAR ---
    const header = document.querySelector('.header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                header.classList.add('sticky');
            } else {
                header.classList.remove('sticky');
            }
        });
    }

    // --- 4. HOME CAROUSEL CONTROLS ---
    document.querySelectorAll('[data-carousel-scroll]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-carousel-scroll');
            const direction = Number(button.getAttribute('data-scroll-direction') || 1);
            const rail = targetId ? document.getElementById(targetId) : null;
            if (!rail) return;

            const firstCard = rail.querySelector('.home-carousel-item');
            const cardWidth = firstCard ? firstCard.getBoundingClientRect().width : 340;
            rail.scrollBy({
                left: direction * (cardWidth + 20),
                behavior: 'smooth',
            });
        });
    });

    // --- 6. CHATBOT WINDOW TOGGLE & FLOW ---
    const chatbotBtn = document.getElementById('chatbot-btn');
    const chatbotWindow = document.getElementById('chatbot-window');
    const chatbotClose = document.getElementById('chatbot-close');

    if (chatbotBtn && chatbotWindow) {
        chatbotBtn.addEventListener('click', () => {
            chatbotWindow.classList.toggle('show');
        });
    }

    if (chatbotClose && chatbotWindow) {
        chatbotClose.addEventListener('click', () => {
            chatbotWindow.classList.remove('show');
        });
    }

    // Close chatbot if clicking outside
    document.addEventListener('click', (e) => {
        if (chatbotWindow && chatbotBtn && !chatbotWindow.contains(e.target) && !chatbotBtn.contains(e.target)) {
            chatbotWindow.classList.remove('show');
        }
    });

    // --- 6.1. CHATBOT INTERACTION LOGIC ---
    const chatInput = document.getElementById('chat-input');
    const chatSendBtn = document.getElementById('chat-send');
    const chatBody = document.getElementById('chatbot-body');

    if (chatInput && chatSendBtn && chatBody) {
        const sendMessage = () => {
            const messageText = chatInput.value.trim();
            if (messageText === '') return;

            // 1. Append user message
            const userMsg = document.createElement('div');
            userMsg.className = 'chatbot-message user';
            userMsg.innerText = messageText;
            chatBody.appendChild(userMsg);
            chatInput.value = '';
            
            // Scroll to bottom
            chatBody.scrollTop = chatBody.scrollHeight;

            // 2. Simulate AI response
            setTimeout(() => {
                const botMsg = document.createElement('div');
                botMsg.className = 'chatbot-message bot';
                botMsg.innerText = 'Baik, pesan kamu sudah kuterima! Saat ini saya masih versi simulasi AI. Untuk penyewaan, silakan hubungi kami di halaman "Hubungi Kami". 😊';
                chatBody.appendChild(botMsg);
                chatBody.scrollTop = chatBody.scrollHeight;
            }, 800);
        };

        chatSendBtn.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }

    // --- 7. PAYMENT METHOD INTERACTION ---
    const paymentMethods = document.querySelectorAll('.payment-method-item');
    const selectedMethodInput = document.getElementById('selected-payment-method');

    if (paymentMethods && paymentMethods.length > 0) {
        paymentMethods.forEach(method => {
            method.addEventListener('click', () => {
                paymentMethods.forEach(m => m.classList.remove('active'));
                method.classList.add('active');
                
                const methodCode = method.getAttribute('data-method');
                if (selectedMethodInput) {
                    selectedMethodInput.value = methodCode;
                }
            });
        });
    }
});
