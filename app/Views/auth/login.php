<?php
$loginErrors = array_merge([
    'identifier' => null,
    'password' => null,
], $loginErrors ?? []);
$oldIdentifier = $oldIdentifier ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register - KosOnline</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 55%, #dbeafe 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            min-height: 100vh;
            padding: 24px;
        }

        h1 { font-weight: bold; margin: 0; color: #1e293b; }
        h2 { text-align: center; color: #fff; font-weight: bold; }
        p { font-size: 14px; font-weight: 400; line-height: 20px; letter-spacing: 0.5px; margin: 20px 0 30px; }
        span { font-size: 12px; }
        a { color: #3b82f6; font-size: 14px; text-decoration: none; margin: 15px 0; }

        button.btn-auth {
            border-radius: 20px;
            border: 1px solid #3b82f6;
            background-color: #3b82f6;
            color: #FFFFFF;
            font-size: 13px;
            font-weight: bold;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: transform 80ms ease-in;
            cursor: pointer;
            position: relative;
            z-index: 101;
        }

        button.btn-auth:active { transform: scale(0.95); }
        button.btn-auth:focus { outline: none; }
        button.btn-auth.ghost { background-color: transparent; border-color: #FFFFFF; }

        form {
            background-color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 50px;
            height: 100%;
            text-align: center;
        }

        .sign-up-container form {
            justify-content: flex-start;
            padding-top: 24px;
            padding-bottom: 18px;
            overflow-y: auto;
            scrollbar-width: thin;
        }

        .sign-up-container h1 {
            font-size: 2rem;
            line-height: 1.1;
        }

        .sign-up-container .social-container {
            margin: 12px 0;
        }

        .sign-up-container .input-group {
            margin-bottom: 12px;
        }

        .sign-up-container .input-group.mb-4 {
            margin-bottom: 12px !important;
        }

        .sign-up-container .btn-auth {
            margin-top: 4px;
        }

        .input-group { position: relative; margin-bottom: 15px; width: 100%; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .input-group input {
            background-color: #f1f5f9;
            border: none;
            padding: 12px 15px 12px 40px;
            width: 100%;
            border-radius: 8px;
            font-family: 'Outfit', sans-serif;
            color: #334155;
            outline: none;
        }

        .input-group input:focus {
            box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
        }

        .input-group.has-error input {
            background-color: #fff7f7;
            border: 1px solid #fecaca;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
        }

        .input-group.has-error i {
            color: #ef4444;
        }

        .field-error {
            width: 100%;
            margin: -8px 0 12px;
            color: #dc2626;
            font-size: 12px;
            font-weight: 600;
            text-align: left;
        }

        .auth-container {
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.1), 0 10px 10px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            width: 980px;
            max-width: 100%;
            min-height: 620px;
        }

        .form-container {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            transition:
                opacity 0.38s ease,
                filter 0.5s ease,
                transform 0.72s cubic-bezier(0.76, 0, 0.24, 1);
            will-change: opacity, filter, transform;
        }
        .sign-in-container {
            left: 0;
            z-index: 2;
            transform: translateX(0) scale(1);
        }
        .auth-container.right-panel-active .sign-in-container {
            transform: translateX(-18%) scale(0.96);
            opacity: 0;
            filter: blur(8px);
            pointer-events: none;
        }
        .sign-up-container {
            left: 50%;
            z-index: 2;
            opacity: 0;
            filter: blur(8px);
            transform: translateX(18%) scale(0.96);
            pointer-events: none;
        }
        .auth-container.right-panel-active .sign-up-container {
            transform: translateX(0) scale(1);
            opacity: 1;
            filter: blur(0);
            pointer-events: auto;
        }

        /* Liquid wave that morphs from one side to the other. */
        .gooey-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: linear-gradient(145deg, #1e40af, #3b82f6);
            clip-path: ellipse(52% 115% at 100% 50%);
            transform: scale(1);
            transform-origin: center;
            transition:
                clip-path 0.9s cubic-bezier(0.76, 0, 0.24, 1),
                filter 0.9s ease,
                transform 0.9s cubic-bezier(0.76, 0, 0.24, 1);
            z-index: 4;
            pointer-events: none;
            will-change: clip-path;
        }
        .gooey-background::after {
            content: '';
            position: absolute;
            inset: -30%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.16), transparent 55%);
            opacity: 0;
            transform: scale(0.55);
            transition:
                opacity 0.45s ease,
                transform 0.9s cubic-bezier(0.76, 0, 0.24, 1);
        }
        .bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition:
                transform 1.15s cubic-bezier(0.76, 0, 0.24, 1),
                opacity 0.6s ease;
            will-change: transform;
        }

        .b1 { top: -20%; left: 42%; width: 38%; height: 65%; }
        .b2 { top: 28%; left: 40%; width: 42%; height: 70%; }
        .b3 { top: 8%; right: -8%; width: 34%; height: 58%; }
        .b4 { bottom: -25%; right: -5%; width: 45%; height: 75%; }
        .b5 { top: 38%; left: 62%; width: 28%; height: 44%; }

        .auth-container.right-panel-active .gooey-background {
            clip-path: ellipse(52% 115% at 0% 50%);
        }

        .auth-container.right-panel-active .b1 { transform: translateX(-112%) rotate(18deg) scale(1.08); }
        .auth-container.right-panel-active .b2 { transform: translateX(-102%) rotate(-12deg) scale(1.12); }
        .auth-container.right-panel-active .b3 { transform: translateX(-176%) rotate(16deg) scale(1.06); }
        .auth-container.right-panel-active .b4 { transform: translateX(-120%) rotate(-15deg) scale(1.1); }
        .auth-container.right-panel-active .b5 { transform: translateX(-205%) rotate(12deg) scale(1.15); }

        .auth-container.is-transitioning .gooey-background {
            filter: saturate(1.12) brightness(1.04);
            transform: scale(1.035);
        }

        .auth-container.is-transitioning .gooey-background::after {
            opacity: 1;
            transform: scale(1);
        }


        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.82s cubic-bezier(0.76, 0, 0.24, 1);
            z-index: 100;
            pointer-events: none;
        }
        .overlay-panel { pointer-events: auto; }

        .auth-container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }

        .overlay {
            background: transparent;
            color: #FFFFFF;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.82s cubic-bezier(0.76, 0, 0.24, 1);
        }

        .auth-container.right-panel-active .overlay {
            transform: translateX(50%);
        }

        .overlay-panel {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
            transform: translateX(0);
            transition:
                opacity 0.35s ease,
                transform 0.72s cubic-bezier(0.76, 0, 0.24, 1);
        }

        .overlay-left { transform: translateX(-20%); opacity: 0; }
        .auth-container.right-panel-active .overlay-left { transform: translateX(0); opacity: 1; }

        .overlay-right { right: 0; transform: translateX(0); opacity: 1; }
        .auth-container.right-panel-active .overlay-right { transform: translateX(20%); opacity: 0; }

        .social-container { margin: 20px 0; }
        .social-container a {
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin: 0 5px;
            height: 40px;
            width: 40px;
            color: #334155;
            transition: all 0.3s;
        }
        .social-container a:hover { background-color: #3b82f6; color: white; border-color: #3b82f6; }

        /* Custom Alert Positioning */
        .alert-fixed {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            min-width: 300px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* Mobile View (Stack layout without sliding panel) */
        @media (max-width: 768px) {
            body { padding: 0; }
            .auth-container { min-height: 100vh; width: 100%; border-radius: 0; }
            /* Reset posisi desktop: tanpa ini, .sign-up-container (left:50%) tergeser
               ke kanan saat jadi position:relative → form register kepotong di HP. */
            .sign-in-container, .sign-up-container { width: 100%; height: auto; position: relative; left: 0; right: auto; transform: none; padding: 40px 0; }
            .sign-in-container { top: 0; opacity: 1; z-index: 5; }
            .sign-up-container { top: 0; opacity: 0; z-index: 1; display: none; }
            .auth-container.right-panel-active .sign-in-container { display: none; opacity: 0; }
            .auth-container.right-panel-active .sign-up-container { display: block; opacity: 1; z-index: 5; transform: none; animation: fadein 0.5s; }

            .overlay-container, .gooey-background { display: none; } /* Hide the overlay on mobile */

            .mobile-toggle { display: block !important; margin-top: 20px; color: #3b82f6; cursor: pointer; text-decoration: underline; font-weight: 600; }

            form { padding: 0 30px; }
            h1 { font-size: 1.8rem; margin-bottom: 10px; }
        }

        @media (max-height: 720px) and (min-width: 769px) {
            body {
                align-items: flex-start;
            }

            .auth-container {
                min-height: calc(100vh - 48px);
            }

            .sign-up-container form {
                padding-top: 18px;
                padding-bottom: 14px;
            }

            .sign-up-container h1 {
                font-size: 1.8rem;
            }

            .sign-up-container .social-container {
                margin: 8px 0;
            }

            .sign-up-container span.mb-3 {
                margin-bottom: 0.65rem !important;
            }

            .sign-up-container .input-group input {
                padding-top: 10px;
                padding-bottom: 10px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .form-container,
            .gooey-background,
            .gooey-background::after,
            .bubble,
            .overlay-container,
            .overlay,
            .overlay-panel {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
        }

        .mobile-toggle { display: none; }

        @keyframes fadein { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>

    <!-- SVG Filter for Gooey Effect -->
    <svg xmlns="http://www.w3.org/2000/svg" version="1.1" style="display:none;">
        <defs>
            <filter id="goo">
                <feGaussianBlur in="SourceGraphic" stdDeviation="15" result="blur" />
                <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 20 -10" result="goo" />
                <feBlend in="SourceGraphic" in2="goo" />
            </filter>
        </defs>
    </svg>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success alert-dismissible fade show alert-fixed" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo e($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($errorMessage !== null): ?>
        <div class="alert alert-danger alert-dismissible fade show alert-fixed" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo e($errorMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Cek URL hash untuk menentukan posisi awal form -->
    <div class="auth-container" id="auth-container">

        <!-- Gooey Bubbles Background -->
        <div class="gooey-background">
            <div class="bubble b1"></div>
            <div class="bubble b2"></div>
            <div class="bubble b3"></div>
            <div class="bubble b4"></div>
            <div class="bubble b5"></div>
        </div>

        <!-- Sign Up Form -->
        <div class="form-container sign-up-container">
            <form action="<?php echo e(url('/register')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <h1>Registration</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fa-brands fa-google"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
                <span class="mb-3 text-muted">atau daftar dengan email baru</span>

                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="nama_lengkap" placeholder="Nama Lengkap" required />
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-at"></i>
                    <input type="text" name="username" placeholder="Username (contoh: nanda123)" pattern="[a-z0-9_]{3,30}" title="Huruf kecil, angka, underscore, minimal 3 karakter" required />
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required />
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-phone"></i>
                    <input type="tel" name="no_hp" placeholder="No. Handphone" inputmode="numeric" pattern="[0-9]{8,15}" maxlength="15" title="Nomor handphone hanya angka, 8-15 digit" oninput="this.value=this.value.replace(/[^0-9]/g,'')" required />
                </div>
                <div class="input-group mb-4">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Password (Min. 6 Karakter)" minlength="6" required />
                </div>
                <div class="input-group mb-4">
                    <i class="fa-solid fa-shield-halved"></i>
                    <input type="password" name="password_confirmation" placeholder="Ulangi Password" minlength="6" required />
                </div>

                <button type="submit" name="register" class="btn-auth w-100 mb-3">Register</button>
                <div class="mobile-toggle text-center" id="mobile-to-login">Sudah punya akun? Login di sini</div>

                <a href="<?php echo e(url('/')); ?>" class="mt-2 text-muted fw-semibold"><i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Beranda</a>
            </form>
        </div>

        <!-- Sign In Form -->
        <div class="form-container sign-in-container">
            <form action="<?php echo e(url('/login')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <h1>Login</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fa-brands fa-google"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="social"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
                <span class="mb-3 text-muted">atau login dengan akun Anda</span>

                <div class="input-group <?php echo $loginErrors['identifier'] !== null ? 'has-error' : ''; ?>">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="text" name="identifier" placeholder="Email / Username" value="<?php echo e($oldIdentifier); ?>" required />
                </div>
                <?php if ($loginErrors['identifier'] !== null): ?>
                    <div class="field-error"><i class="fa-solid fa-circle-exclamation me-1"></i><?php echo e($loginErrors['identifier']); ?></div>
                <?php endif; ?>
                <div class="input-group mb-2 <?php echo $loginErrors['password'] !== null ? 'has-error' : ''; ?>">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required />
                </div>
                <?php if ($loginErrors['password'] !== null): ?>
                    <div class="field-error"><i class="fa-solid fa-circle-exclamation me-1"></i><?php echo e($loginErrors['password']); ?></div>
                <?php endif; ?>
                <a href="#" class="ms-auto mb-4 text-muted fw-semibold" style="font-size: 13px;">Lupa password?</a>

                <button type="submit" name="login" class="btn-auth w-100 mb-3">Login</button>
                <div class="mobile-toggle text-center" id="mobile-to-register">Belum punya akun? Register di sini</div>

                <a href="<?php echo e(url('/')); ?>" class="mt-2 text-muted fw-semibold"><i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Beranda</a>
            </form>
        </div>

        <!-- Overlay for Sliding -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h2>Welcome Back!</h2>
                    <p>Senang melihatmu kembali! Silakan login dengan informasi personalmu untuk melanjutkan pencarian kos.</p>
                    <button type="button" class="btn-auth ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h2>Hello, Welcome!</h2>
                    <p>Masukkan informasi personalmu dan mulailah perjalananmu bersama KosOnline.</p>
                    <button type="button" class="btn-auth ghost" id="signUp">Register</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo e(asset('js/notifications.js')); ?>"></script>
    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('auth-container');
        const mobileToRegister = document.getElementById('mobile-to-register');
        const mobileToLogin = document.getElementById('mobile-to-login');

        let transitionTimer;

        function switchAuthPanel(showRegister) {
            window.clearTimeout(transitionTimer);
            container.classList.add('is-transitioning');
            container.classList.toggle('right-panel-active', showRegister);
            window.location.hash = showRegister ? 'register' : 'login';

            transitionTimer = window.setTimeout(() => {
                container.classList.remove('is-transitioning');
            }, 950);
        }

        if (signUpButton) signUpButton.addEventListener('click', () => switchAuthPanel(true));
        if (signInButton) signInButton.addEventListener('click', () => switchAuthPanel(false));

        // Logic for Mobile Toggle
        if (mobileToRegister) mobileToRegister.addEventListener('click', () => switchAuthPanel(true));
        if (mobileToLogin) mobileToLogin.addEventListener('click', () => switchAuthPanel(false));

        // Check hash on load
        if(window.location.hash === '#register') {
            container.classList.add("right-panel-active");
        }
    </script>
</body>
</html>
