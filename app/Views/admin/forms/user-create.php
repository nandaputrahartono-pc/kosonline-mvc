<?php ob_start(); ?>
<h2 style="text-align: center; margin-bottom: 20px;">Tambah Penghuni Baru</h2>
<form method="POST">
    <?php echo csrf_field(); ?>
    <label>Nama Lengkap</label>
    <input type="text" name="nama" class="form-control" placeholder="Masukan nama lengkap" required>

    <label>Username (Untuk Login)</label>
    <input type="text" name="username" class="form-control" placeholder="contoh: nanda123" pattern="[a-z0-9_]{3,30}" required>

    <label>Email (Untuk Login)</label>
    <input type="email" name="email" class="form-control" placeholder="contoh@gmail.com" required>

    <label>Password</label>
    <input type="password" name="password" minlength="6" class="form-control" placeholder="Masukan password" required>

    <label>Nomor HP (WhatsApp)</label>
    <input type="tel" name="no_hp" class="form-control" placeholder="08xxxxxxxx" required>

    <button type="submit" name="simpan" class="btn btn-primary">Simpan User</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn btn-outline-secondary btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Tambah Penghuni Baru'; require base_path('app/Views/admin/forms/layout.php'); ?>
