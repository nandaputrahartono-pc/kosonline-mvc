<?php ob_start(); ?>
<h2>Edit Data Kost</h2>
<form method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <label>Nama Kost</label>
    <input type="text" name="nama_kost" value="<?php echo e($kost['nama_kost']); ?>" required>

    <label>Alamat</label>
    <textarea name="alamat" rows="3" required><?php echo e($kost['alamat']); ?></textarea>

    <label>Deskripsi</label>
    <textarea name="deskripsi" rows="3"><?php echo e($kost['deskripsi']); ?></textarea>

    <label>Diskon Semua Kamar di Cabang Ini (%)</label>
    <input type="number" name="diskon_persen" min="0" max="100" value="<?php echo e($kost['diskon_persen'] ?? 0); ?>" required>
    <small style="display:block; margin-top:-14px; margin-bottom:18px; color:#64748b;">Diskon khusus kamar akan menggantikan diskon cabang.</small>

    <label>Foto Saat Ini</label><br>
    <img src="<?php echo e(upload_asset($kost['foto_kost'])); ?>" width="100" style="border-radius: 5px; margin-bottom: 10px;">
    <br>
    <label>Ganti Foto (Biarkan kosong jika tidak ingin mengganti)</label>
    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">

    <button type="submit" name="update">Update Data</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Edit Data Kost'; require base_path('app/Views/admin/forms/layout.php'); ?>
