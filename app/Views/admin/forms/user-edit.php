<?php ob_start(); ?>
<h2 style="text-align: center; margin-bottom: 20px;">Edit Data User</h2>
<form method="POST">
    <label>Nama Lengkap</label>
    <input type="text" name="nama" value="<?php echo e($user['nama_lengkap']); ?>" required>

    <label>Email</label>
    <input type="email" name="email" value="<?php echo e($user['email']); ?>" required>

    <label>Nomor HP (WhatsApp)</label>
    <input type="number" name="no_hp" value="<?php echo e($user['no_hp']); ?>" required>

    <label>Password Baru</label>
    <input type="text" name="password" placeholder="Biarkan kosong jika tidak ingin mengganti password">
    <small style="color: red; font-style: italic;">*Isi hanya jika ingin mengubah password</small>

    <button type="submit" name="update">Simpan Perubahan</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Edit Data User'; require base_path('app/Views/admin/forms/layout.php'); ?>
