<?php
$formMaxWidth = '900px';
/**
 * @var array $room Data kamar yang sedang diedit (dari AdminRoomController::edit).
 * @var array $kosts Daftar cabang kost untuk dropdown.
 * @var array $users Daftar user yang bisa dijadikan penghuni.
 * @var int $currentTenant id_user penghuni aktif kamar ini, 0 jika kosong.
 */
ob_start();
?>
<h2>Edit Data Kamar</h2>
<form method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <label>Pilih Kost (Cabang)</label>
    <select name="id_kost" class="form-select" required>
        <?php foreach ($kosts as $kost): ?>
            <option value="<?php echo e($kost['id_kost']); ?>" <?php echo (int) $kost['id_kost'] === (int) $room['id_kost'] ? 'selected' : ''; ?>>
                <?php echo e($kost['nama_kost']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Nomor Kamar</label>
    <input type="text" name="nomor_kamar" value="<?php echo e($room['nomor_kamar']); ?>" class="form-control" required>

    <label>Lantai</label>
    <input type="number" name="lantai" min="1" value="<?php echo e($room['lantai']); ?>" class="form-control" required>

    <label>Fasilitas</label>
    <textarea name="fasilitas" rows="3" class="form-control"><?php echo e($room['fasilitas']); ?></textarea>

    <label>Deskripsi Detail Kamar</label>
    <textarea name="deskripsi_kamar" rows="5" class="form-control" placeholder="Jelaskan kondisi dan keunggulan khusus kamar ini."><?php echo e($room['deskripsi_kamar'] ?? ''); ?></textarea>

    <label>Harga</label>
    <input type="number" name="harga" min="1" value="<?php echo e($room['harga']); ?>" class="form-control" required>

    <label>Diskon Khusus Kamar (%)</label>
    <input type="number" name="diskon_persen" min="0" max="100" value="<?php echo e($room['diskon_persen'] ?? 0); ?>" class="form-control" required>
    <small style="display:block; margin-top:-14px; margin-bottom:18px; color:#64748b;">Isi 0 untuk memakai diskon cabang jika tersedia.</small>

    <hr style="margin: 20px 0; border-top: 1px dashed #ccc;">

    <label style="color: #1e3a8a; font-weight: bold;">Penghuni Kamar Saat Ini</label>
    <select name="id_user" class="form-select">
        <option value="">-- Kosong (Tidak Ada Penghuni) --</option>
        <?php foreach ($users as $user): ?>
            <option value="<?php echo e($user['id_user']); ?>" <?php echo (int) $user['id_user'] === (int) $currentTenant ? 'selected' : ''; ?>>
                <?php echo e($user['nama_lengkap'] . ' (' . $user['email'] . ')'); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <small style="color: #666; font-size: 12px;">*Jika Anda mengubah ini, status kamar otomatis berubah.</small>
    <br><br>

    <?php require base_path('app/Views/admin/forms/room-gallery-fields.php'); ?>

    <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn btn-outline-secondary btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Edit Data Kamar'; require base_path('app/Views/admin/forms/layout.php'); ?>
