<?php
/** @var array $kost Data kost yang koordinatnya diatur (dari AdminLocationController::edit). */
ob_start();
?>
<h2>Set Titik Peta: <?php echo e($kost['nama_kost']); ?></h2>
<p><?php echo e($kost['alamat']); ?></p>

<div class="tutorial">
    <strong>Tips:</strong> Buka Google Maps, Klik Kanan di lokasi kost, lalu copy angkanya. <br>
    Contoh: <code>-6.70423, 108.55612</code>
</div>

<form method="POST">
    <?php echo csrf_field(); ?>
    <label>Latitude (Garis Lintang)</label>
    <input type="number" name="latitude" min="-90" max="90" step="any" value="<?php echo e($kost['latitude']); ?>" class="form-control" placeholder="Contoh: -6.70423" required>

    <label>Longitude (Garis Bujur)</label>
    <input type="number" name="longitude" min="-180" max="180" step="any" value="<?php echo e($kost['longitude']); ?>" class="form-control" placeholder="Contoh: 108.55612" required>

    <button type="submit" name="simpan" class="btn btn-primary">Simpan Lokasi</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn btn-outline-secondary btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Set Titik Peta'; require base_path('app/Views/admin/forms/layout.php'); ?>
