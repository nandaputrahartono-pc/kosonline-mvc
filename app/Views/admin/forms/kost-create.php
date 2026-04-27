<?php ob_start(); ?>
<h2>Tambah Data Kost</h2>
<form method="POST" enctype="multipart/form-data">
    <label>Nama Kost</label>
    <input type="text" name="nama_kost" required>

    <label>Alamat</label>
    <textarea name="alamat" rows="3" required></textarea>

    <label>Deskripsi Fasilitas Umum</label>
    <textarea name="deskripsi" rows="3"></textarea>

    <label>Foto Kost</label>
    <input type="file" name="foto" required>

    <button type="submit" name="simpan">Simpan Data</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Tambah Data Kost'; require base_path('app/Views/admin/forms/layout.php'); ?>
