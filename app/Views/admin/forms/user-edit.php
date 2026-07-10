<?php
/** @var array $user Data user yang sedang diedit (dari AdminUserController::edit). */
ob_start();
?>
<h2 style="text-align: center; margin-bottom: 20px;">Edit Data User</h2>
<form method="POST">
    <?php echo csrf_field(); ?>
    <label>Nama Lengkap</label>
    <input type="text" name="nama" value="<?php echo e($user['nama_lengkap']); ?>" class="form-control" required>

    <label>Username</label>
    <input type="text" name="username" value="<?php echo e($user['username']); ?>" class="form-control" pattern="[a-z0-9_]{3,30}" required>

    <label>Email</label>
    <input type="email" name="email" value="<?php echo e($user['email']); ?>" class="form-control" required>

    <label>Nomor HP (WhatsApp)</label>
    <input type="tel" name="no_hp" value="<?php echo e($user['no_hp']); ?>" class="form-control" required>

    <label>Password Baru</label>
    <input type="password" name="password" minlength="6" class="form-control" placeholder="Biarkan kosong jika tidak ingin mengganti password">
    <small style="color: red; font-style: italic;">*Isi hanya jika ingin mengubah password</small>

    <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn btn-outline-secondary btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Edit Data User'; require base_path('app/Views/admin/forms/layout.php'); ?>
