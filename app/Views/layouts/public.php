<?php
$title = $title ?? 'KosOnline';
$showHeader = $showHeader ?? true;
$showFooter = $showFooter ?? true;
$showChatbot = $showChatbot ?? true;
$successMessage = $successMessage ?? flash('success');
$errorMessage = $errorMessage ?? flash('error');
$extraHead = $extraHead ?? '';
$extraScripts = $extraScripts ?? '';
$isLoggedInUser = ($_SESSION['status'] ?? null) === 'login_user';
$currentUserName = (string) ($_SESSION['nama'] ?? 'User');
$currentUserPhoto = (string) ($_SESSION['foto_profil'] ?? 'default.jpg');
$currentUserAvatar = profile_avatar($currentUserPhoto);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
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
        <?php require base_path('app/Views/layouts/partials/public-navbar.php'); ?>
    <?php endif; ?>

    <?php echo $content; ?>

    <?php if ($showFooter): ?>
        <?php require base_path('app/Views/layouts/partials/public-footer.php'); ?>
    <?php endif; ?>

    <?php if ($showChatbot): ?>
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
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo e(asset('js/notifications.js')); ?>"></script>
    <script src="<?php echo e(asset('js/confirm-modal.js')); ?>"></script>
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
