<?php ob_start(); ?>
<h2>Edit Data Kamar</h2>
<form method="POST">
    <label>Pilih Kost (Cabang)</label>
    <select name="id_kost" required>
        <?php foreach ($kosts as $kost): ?>
            <option value="<?php echo e($kost['id_kost']); ?>" <?php echo (int) $kost['id_kost'] === (int) $room['id_kost'] ? 'selected' : ''; ?>>
                <?php echo e($kost['nama_kost']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Nomor Kamar</label>
    <input type="text" name="nomor_kamar" value="<?php echo e($room['nomor_kamar']); ?>" required>

    <label>Lantai</label>
    <input type="number" name="lantai" value="<?php echo e($room['lantai']); ?>" required>

    <label>Fasilitas</label>
    <textarea name="fasilitas" rows="3"><?php echo e($room['fasilitas']); ?></textarea>

    <label>Harga</label>
    <input type="number" name="harga" value="<?php echo e($room['harga']); ?>" required>

    <hr style="margin: 20px 0; border-top: 1px dashed #ccc;">

    <label style="color: #1e3a8a; font-weight: bold;">Penghuni Kamar Saat Ini</label>
    <select name="id_user">
        <option value="">-- Kosong (Tidak Ada Penghuni) --</option>
        <?php foreach ($users as $user): ?>
            <option value="<?php echo e($user['id_user']); ?>" <?php echo (int) $user['id_user'] === (int) $currentTenant ? 'selected' : ''; ?>>
                <?php echo e($user['nama_lengkap'] . ' (' . $user['email'] . ')'); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <small style="color: #666; font-size: 12px;">*Jika Anda mengubah ini, status kamar otomatis berubah.</small>
    <br><br>

    <button type="submit" name="update">Simpan Perubahan</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Edit Data Kamar'; require base_path('app/Views/admin/forms/layout.php'); ?>
