<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Kostline</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/auth.css')); ?>">
</head>
<body>
    <?php if ($successMessage !== null): ?>
        <script>alert(<?php echo json_encode($successMessage); ?>);</script>
    <?php endif; ?>
    <?php if ($errorMessage !== null): ?>
        <script>alert(<?php echo json_encode($errorMessage); ?>);</script>
    <?php endif; ?>

    <div class="login-container">
        <div class="login-card">
            <h2>Login Kostline</h2>

            <form action="<?php echo e(url('/login')); ?>" method="POST">
                <input type="text" name="identifier" placeholder="Username atau Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Masuk</button>
            </form>

            <p style="font-size: 14px; text-align: center; color: #666; margin-top: 15px;">
                Belum punya akun? <a href="<?php echo e(url('/contact')); ?>">Hubungi Admin Kost.</a>
            </p>
            <p style="font-size: 14px; text-align: center; color: #666; margin-top: 5px;">
                <a href="<?php echo e(url('/')); ?>">Kembali ke Home</a>
            </p>
        </div>
    </div>
</body>
</html>
