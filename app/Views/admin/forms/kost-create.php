<?php ob_start(); ?>
<h2>Tambah Data Kost</h2>
<form method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <label>Nama Kost</label>
    <input type="text" name="nama_kost" required>

    <label>Alamat</label>
    <textarea name="alamat" rows="3" required></textarea>

    <label>Deskripsi Fasilitas Umum</label>
    <textarea name="deskripsi" rows="3"></textarea>

    <label>Diskon Semua Kamar di Cabang Ini (%)</label>
    <input type="number" name="diskon_persen" min="0" max="100" value="0" required>
    <small style="display:block; margin-top:-14px; margin-bottom:18px; color:#64748b;">Diskon khusus kamar akan menggantikan diskon cabang.</small>

    <label>Foto Kost</label>
    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required>

    <button type="submit" name="simpan">Simpan Data</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Tambah Data Kost'; require base_path('app/Views/admin/forms/layout.php'); ?>
