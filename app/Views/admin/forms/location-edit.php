<?php ob_start(); ?>
<h2>Set Titik Peta: <?php echo e($kost['nama_kost']); ?></h2>
<p><?php echo e($kost['alamat']); ?></p>

<div class="tutorial">
    <strong>Tips:</strong> Buka Google Maps, Klik Kanan di lokasi kost, lalu copy angkanya. <br>
    Contoh: <code>-6.70423, 108.55612</code>
</div>

<form method="POST">
    <label>Latitude (Garis Lintang)</label>
    <input type="text" name="latitude" value="<?php echo e($kost['latitude']); ?>" placeholder="Contoh: -6.70423" required>

    <label>Longitude (Garis Bujur)</label>
    <input type="text" name="longitude" value="<?php echo e($kost['longitude']); ?>" placeholder="Contoh: 108.55612" required>

    <button type="submit" name="simpan">Simpan Lokasi</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Set Titik Peta'; require base_path('app/Views/admin/forms/layout.php'); ?>
