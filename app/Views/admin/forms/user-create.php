<?php ob_start(); ?>
<h2 style="text-align: center; margin-bottom: 20px;">Tambah Penghuni Baru</h2>
<form method="POST">
    <label>Nama Lengkap</label>
    <input type="text" name="nama" placeholder="Masukan nama lengkap" required>

    <label>Email (Untuk Login)</label>
    <input type="email" name="email" placeholder="contoh@gmail.com" required>

    <label>Password</label>
    <input type="text" name="password" placeholder="Masukan password" required>

    <label>Nomor HP (WhatsApp)</label>
    <input type="number" name="no_hp" placeholder="08xxxxxxxx" required>

    <button type="submit" name="simpan">Simpan User</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Tambah Penghuni Baru'; require base_path('app/Views/admin/forms/layout.php'); ?>
