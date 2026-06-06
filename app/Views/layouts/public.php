<?php
$title = $title ?? 'KosOnline';
$showHeader = $showHeader ?? true;
$showFooter = $showFooter ?? true;
$successMessage = $successMessage ?? flash('success');
$errorMessage = $errorMessage ?? flash('error');
$extraHead = $extraHead ?? '';
$extraScripts = $extraScripts ?? '';
$isLoggedInUser = ($_SESSION['status'] ?? null) === 'login_user';
$currentUserName = (string) ($_SESSION['nama'] ?? 'User');
$currentUserPhoto = (string) ($_SESSION['foto_profil'] ?? 'default.jpg');
$currentUserAvatar = $currentUserPhoto !== '' && $currentUserPhoto !== 'default.jpg'
    ? upload_asset($currentUserPhoto)
    : site_image('images.jpg');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <!-- Cegah flash putih: terapkan tema sebelum CSS dimuat -->
    <script>
        (function(){var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);})();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
    <?php echo $extraHead; ?>
</head>
<body>
    <?php if ($successMessage !== null || $errorMessage !== null): ?>
        <div class="app-flash-stack" aria-live="polite">
            <?php if ($successMessage !== null): ?>
                <div class="app-flash app-flash-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo e($successMessage); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($errorMessage !== null): ?>
                <div class="app-flash app-flash-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo e($errorMessage); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($showHeader): ?>
        <header class="header" id="header">
            <div class="logo">
                <a href="<?php echo e(url('/')); ?>"><span>Kos</span>Online</a>
            </div>

            <nav class="navbar ms-auto me-lg-4" id="navbar">
                <ul class="nav-menu">
                    <li><a href="<?php echo e(url('/')); ?>">Beranda</a></li>
                    <li><a href="<?php echo e(url('/rooms')); ?>">Kamar Kos</a></li>
                    <li><a href="<?php echo e(url('/contact')); ?>">Hubungi Kami</a></li>
                    <li><a href="<?php echo e(url('/map')); ?>">Peta Lokasi</a></li>
                    <li class="d-lg-none mt-4 w-100">
                        <?php if ($isLoggedInUser): ?>
                            <a href="<?php echo e(url('/member/dashboard')); ?>" class="btn w-100 py-2.5 fw-bold mb-2" style="background-color: var(--accent-blue); color: white !important; border-radius: 50px; box-shadow: 0 4px 14px rgba(37,99,235,0.3);">Dashboard User</a>
                            <form method="POST" action="<?php echo e(url('/logout')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn w-100 py-2.5 fw-bold" style="background-color: #fee2e2; color: #b91c1c; border-radius: 50px;">Logout</button>
                            </form>
                        <?php else: ?>
                            <a href="<?php echo e(url('/login')); ?>" class="btn w-100 py-2.5 fw-bold" style="background-color: var(--accent-blue); color: white !important; border-radius: 50px; box-shadow: 0 4px 14px rgba(37,99,235,0.3);">Login / Register</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </nav>

            <div class="d-flex align-items-center gap-3">
                <button id="theme-toggle" class="btn btn-link text-decoration-none p-0 m-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%; background: var(--card-bg); box-shadow: 0 2px 10px rgba(0,0,0,0.05); color: var(--text-main);">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <?php if ($isLoggedInUser): ?>
                    <div class="public-user-menu d-none d-lg-block">
                        <button type="button" class="public-user-trigger" aria-label="Menu akun">
                            <img src="<?php echo e($currentUserAvatar); ?>" alt="Foto profil <?php echo e($currentUserName); ?>">
                            <span><?php echo e(strtok($currentUserName, ' ') ?: 'User'); ?></span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="public-user-dropdown">
                            <div class="public-user-card">
                                <img src="<?php echo e($currentUserAvatar); ?>" alt="Foto profil">
                                <div>
                                    <strong><?php echo e($currentUserName); ?></strong>
                                    <small>Sudah login</small>
                                </div>
                            </div>
                            <a href="<?php echo e(url('/member/dashboard')); ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                            <form method="POST" action="<?php echo e(url('/logout')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo e(url('/login')); ?>" class="btn-auth text-decoration-none d-none d-lg-inline-block">Login / Register</a>
                <?php endif; ?>
                
                <div class="menu-toggle ms-1" id="mobile-menu">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </header>
    <?php endif; ?>

    <?php echo $content; ?>

    <?php if ($showFooter): ?>
        <footer class="footer-premium mt-5">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-4 col-md-6">
                        <h4 class="fw-bold mb-3 logo" style="font-size: 1.5rem;"><a href="<?php echo e(url('/')); ?>"><span>Kos</span>Online</a></h4>
                        <p class="text-muted mb-4" style="font-size: 0.95rem;">Temukan hunian kos impianmu dengan mudah, aman, dan cepat. Tersedia berbagai fasilitas premium dan lokasi yang sangat strategis.</p>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <h5 class="fw-bold mb-3" style="color: var(--text-main);">Navigasi</h5>
                        <ul class="list-unstyled">
                            <li><a href="<?php echo e(url('/')); ?>" class="footer-link d-inline-block mb-2">Beranda</a></li>
                            <li><a href="<?php echo e(url('/rooms')); ?>" class="footer-link d-inline-block mb-2">Kamar Kos</a></li>
                            <li><a href="<?php echo e(url('/contact')); ?>" class="footer-link d-inline-block mb-2">Hubungi Kami</a></li>
                            <li><a href="<?php echo e(url('/map')); ?>" class="footer-link d-inline-block mb-2">Peta Lokasi</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h5 class="fw-bold mb-3" style="color: var(--text-main);">Kontak Resmi</h5>
                        <p class="text-muted mb-2" style="font-size: 0.95rem;"><i class="fa-solid fa-location-dot me-2 text-primary"></i> Cirebon, Jawa Barat, Indonesia</p>
                        <p class="text-muted mb-2" style="font-size: 0.95rem;"><i class="fa-solid fa-phone me-2 text-primary"></i> +62 877-4870-3029</p>
                        <p class="text-muted mb-2" style="font-size: 0.95rem;"><i class="fa-solid fa-envelope me-2 text-primary"></i> info@kosonline.com</p>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h5 class="fw-bold mb-3" style="color: var(--text-main);">Media Sosial</h5>
                        <div class="d-flex gap-2">
                            <a href="#" class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;"><i class="fa-brands fa-instagram"></i></a>
                            <a href="#" class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="#" class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;"><i class="fa-brands fa-tiktok"></i></a>
                        </div>
                    </div>
                </div>
                <hr class="my-4" style="border-color: var(--border-soft);">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start text-muted mb-2 mb-md-0" style="font-size: 0.9rem;">
                        &copy; <span id="year"></span> KosOnline. Hak Cipta Dilindungi.
                    </div>
                </div>
            </div>
        </footer>
    <?php endif; ?>

    <!-- Chatbot CS Widget -->
    <div class="chatbot-widget">
        <div class="chatbot-window" id="chatbot-window">
            <div class="chatbot-header">
                <span><i class="fa-solid fa-robot"></i> AI CS Assistant</span>
                <i class="fa-solid fa-xmark" id="chatbot-close"></i>
            </div>
            <div class="chatbot-body" id="chatbot-body">
                <div class="chatbot-message bot">
                    Halo! Saya asisten AI KosOnline. Ada yang bisa saya bantu terkait info kamar, harga, atau fasilitas?
                </div>
            </div>
            <div class="chatbot-footer" style="flex-direction: row; gap: 10px; padding: 12px; align-items: center;">
                <input type="text" id="chat-input" class="form-control form-control-sm border-0 shadow-none" placeholder="Ketik pesan Anda..." style="background: var(--bg-main); color: var(--text-main); border-radius: 20px; padding: 10px 15px;">
                <button id="chat-send" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: var(--accent-blue); border: none;">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
        <div class="chatbot-btn" id="chatbot-btn">
            <i class="fa-solid fa-message"></i>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo e(asset('js/script.js')); ?>"></script>
    <script>
        const yearSpan = document.getElementById('year');
        if (yearSpan) {
            yearSpan.textContent = new Date().getFullYear();
        }
    </script>
    <?php echo $extraScripts; ?>
</body>
</html>
