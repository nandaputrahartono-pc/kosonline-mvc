<?php
$isLoggedInUser = $isLoggedInUser ?? (($_SESSION['status'] ?? null) === 'login_user');
$currentUserName = (string) ($currentUserName ?? ($_SESSION['nama'] ?? 'User'));
$currentUserPhoto = (string) ($currentUserPhoto ?? ($_SESSION['foto_profil'] ?? 'default.jpg'));
$currentUserAvatar = (string) ($currentUserAvatar ?? profile_avatar($currentUserPhoto));
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
            <li class="d-lg-none nav-menu-sep" aria-hidden="true"></li>
            <?php if ($isLoggedInUser): ?>
                <li class="d-lg-none"><a href="<?php echo e(url('/wishlist')); ?>"><i class="fa-regular fa-bookmark"></i> Kamar Tersimpan</a></li>
                <li class="d-lg-none"><a href="<?php echo e(url('/member/dashboard?tab=chat')); ?>"><i class="fa-regular fa-comments"></i> Chat Admin</a></li>
                <li class="d-lg-none"><a href="<?php echo e(url('/member/dashboard')); ?>"><i class="fa-solid fa-gauge-high"></i> Menu</a></li>
                <li class="d-lg-none">
                    <form method="POST" action="<?php echo e(url('/logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="nav-menu-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
                    </form>
                </li>
            <?php else: ?>
                <li class="d-lg-none"><a href="<?php echo e(url('/login')); ?>"><i class="fa-solid fa-right-to-bracket"></i> Login / Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="d-flex align-items-center gap-3">
        <a href="<?php echo e($isLoggedInUser ? url('/wishlist') : url('/login')); ?>" class="public-icon-link public-save-link d-none d-lg-inline-flex <?php echo $currentPath === '/wishlist' ? 'active' : ''; ?>" title="Kamar Tersimpan">
            <i class="fa-regular fa-bookmark"></i>
        </a>
        <a href="<?php echo e($isLoggedInUser ? url('/member/dashboard?tab=chat') : url('/login')); ?>" class="public-icon-link d-none d-lg-inline-flex <?php echo $currentPath === '/member/dashboard' && (string) ($_GET['tab'] ?? '') === 'chat' ? 'active' : ''; ?>" title="Chat Admin">
            <i class="fa-regular fa-comments"></i>
        </a>
        <?php if ($isLoggedInUser): ?>
            <div class="dropdown public-user-menu d-none d-lg-block">
                <button type="button" class="public-user-trigger dropdown-toggle" id="public-user-dropdown-toggle" data-bs-toggle="dropdown" aria-label="Menu akun" aria-expanded="false">
                    <img src="<?php echo e($currentUserAvatar); ?>" alt="Foto profil <?php echo e($currentUserName); ?>" decoding="async">
                    <span><?php echo e(strtok($currentUserName, ' ') ?: 'User'); ?></span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end public-user-dropdown" aria-labelledby="public-user-dropdown-toggle">
                    <div class="public-user-card">
                        <img src="<?php echo e($currentUserAvatar); ?>" alt="Foto profil" loading="lazy" decoding="async">
                        <div>
                            <strong><?php echo e($currentUserName); ?></strong>
                            <small>Sudah login</small>
                        </div>
                    </div>
                    <a href="<?php echo e(url('/member/dashboard')); ?>" class="dropdown-item"><i class="fa-solid fa-gauge-high"></i> Menu</a>
                    <form method="POST" action="<?php echo e(url('/logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="dropdown-item"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
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
<div class="nav-overlay" id="nav-overlay" aria-hidden="true"></div>
