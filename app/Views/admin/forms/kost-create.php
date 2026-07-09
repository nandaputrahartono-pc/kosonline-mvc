<?php ob_start(); ?>
<h2>Tambah Data Kost</h2>
<form method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <label>Nama Kost</label>
    <input type="text" name="nama_kost" class="form-control" required>

    <label>Alamat</label>
    <textarea name="alamat" rows="3" class="form-control" required></textarea>

    <label>Deskripsi Fasilitas Umum</label>
    <textarea name="deskripsi" rows="3" class="form-control"></textarea>

    <label>Diskon Semua Kamar di Cabang Ini (%)</label>
    <input type="number" name="diskon_persen" min="0" max="100" value="0" class="form-control" required>
    <small style="display:block; margin-top:-14px; margin-bottom:18px; color:#64748b;">Diskon khusus kamar akan menggantikan diskon cabang.</small>

    <label>Foto Kost</label>
    <div class="input-group">
        <label class="input-group-text" for="foto-kost">Browse</label>
        <input type="file" id="foto-kost" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp" required>
    </div>

    <button type="submit" name="simpan" class="btn btn-primary">Simpan Data</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn btn-outline-secondary btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Tambah Data Kost'; require base_path('app/Views/admin/forms/layout.php'); ?>
