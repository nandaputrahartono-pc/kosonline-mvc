<?php
$isLoggedInUser = $isLoggedInUser ?? (($_SESSION['status'] ?? null) === 'login_user');
$currentUserName = (string) ($currentUserName ?? ($_SESSION['nama'] ?? 'User'));
$currentUserPhoto = (string) ($currentUserPhoto ?? ($_SESSION['foto_profil'] ?? 'default.jpg'));
$currentUserAvatar = (string) ($currentUserAvatar ?? (
    $currentUserPhoto !== '' && $currentUserPhoto !== 'default.jpg'
        ? upload_asset($currentUserPhoto)
        : site_image('images.jpg')
));
$currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$currentPath = rtrim((string) $currentPath, '/') ?: '/';
$navClass = static function (array $paths) use ($currentPath): string {
    foreach ($paths as $path) {
        if ($path === '/') {
            if ($currentPath === '/') {
                return ' class="active"';
            }

            continue;
        }

        if ($currentPath === $path || str_starts_with($currentPath, $path . '/')) {
            return ' class="active"';
        }
    }

    return '';
};
?>
<header class="header" id="header">
    <div class="logo">
        <a href="<?php echo e(url('/')); ?>"><span>Kos</span>Online</a>
    </div>

    <nav class="navbar ms-auto me-lg-4" id="navbar">
        <ul class="nav-menu">
            <li><a href="<?php echo e(url('/')); ?>"<?php echo $navClass(['/']); ?>>Beranda</a></li>
            <li><a href="<?php echo e(url('/rooms')); ?>"<?php echo $navClass(['/rooms']); ?>>Kamar Kos</a></li>
            <li><a href="<?php echo e(url('/contact')); ?>"<?php echo $navClass(['/contact']); ?>>Hubungi Kami</a></li>
            <li><a href="<?php echo e(url('/map')); ?>"<?php echo $navClass(['/map']); ?>>Peta Lokasi</a></li>
            <li class="d-lg-none mt-4 w-100">
                <?php if ($isLoggedInUser): ?>
                    <a href="<?php echo e(url('/wishlist')); ?>" class="btn w-100 py-2.5 fw-bold mb-2" style="background-color: #fff1f2; color: #e11d48 !important; border-radius: 50px;">Wishlist</a>
                    <a href="<?php echo e(url('/member/dashboard?tab=chat')); ?>" class="btn w-100 py-2.5 fw-bold mb-2" style="background-color: var(--accent-blue-soft); color: var(--accent-blue) !important; border-radius: 50px;">Chat Admin</a>
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
        <a href="<?php echo e($isLoggedInUser ? url('/wishlist') : url('/login')); ?>" class="public-icon-link d-none d-lg-inline-flex <?php echo $currentPath === '/wishlist' ? 'active' : ''; ?>" title="Wishlist">
            <i class="fa-regular fa-heart"></i>
        </a>
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
                    <a href="<?php echo e(url('/wishlist')); ?>"><i class="fa-regular fa-heart"></i> Wishlist</a>
                    <a href="<?php echo e(url('/member/dashboard?tab=chat')); ?>"><i class="fa-regular fa-comments"></i> Chat Admin</a>
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
