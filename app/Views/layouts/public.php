<?php
$title = $title ?? 'KosOnline';
$showHeader = $showHeader ?? true;
$showFooter = $showFooter ?? true;
$successMessage = $successMessage ?? null;
$errorMessage = $errorMessage ?? null;
$extraHead = $extraHead ?? '';
$extraScripts = $extraScripts ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
    <?php echo $extraHead; ?>
</head>
<body>
    <?php if ($successMessage !== null): ?>
        <script>alert(<?php echo json_encode($successMessage); ?>);</script>
    <?php endif; ?>
    <?php if ($errorMessage !== null): ?>
        <script>alert(<?php echo json_encode($errorMessage); ?>);</script>
    <?php endif; ?>

    <?php if ($showHeader): ?>
        <header class="header" id="header">
            <div class="logo">
                <a href="<?php echo e(url('/')); ?>"><i class="fa-solid fa-house"></i></a>
            </div>

            <nav class="navbar" id="navbar">
                <ul class="nav-menu">
                    <li><a href="<?php echo e(url('/')); ?>">Home</a></li>
                    <li><a href="<?php echo e(url('/rooms')); ?>">Kamar</a></li>
                    <li><a href="<?php echo e(url('/contact')); ?>">Hubungi</a></li>
                    <li><a href="<?php echo e(url('/map')); ?>">Peta Lokasi</a></li>
                </ul>

                <div class="btn-lg desktop-only desktop-login">
                    <a href="<?php echo e(url('/login')); ?>" class="btn-auth">Login / Register</a>
                </div>
            </nav>
            <div class="menu-toggle" id="mobile-menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </header>
    <?php endif; ?>

    <?php echo $content; ?>

    <?php if ($showFooter): ?>
        <footer class="footer" id="footer">
            © <span id="year"></span> KosOnline. All rights reserved
        </footer>
    <?php endif; ?>

    <script src="<?php echo e(asset('js/script.js')); ?>"></script>
    <?php echo $extraScripts; ?>
</body>
</html>
