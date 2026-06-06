<?php $formMaxWidth = '900px'; ob_start(); ?>
<h2>Tambah Data Kamar</h2>
<form method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <label>Pilih Kost (Cabang)</label>
    <select name="id_kost" required>
        <option value="">-- Pilih Kost --</option>
        <?php foreach ($kosts as $kost): ?>
            <option value="<?php echo e($kost['id_kost']); ?>"><?php echo e($kost['nama_kost']); ?></option>
        <?php endforeach; ?>
    </select>

    <label>Nomor Kamar</label>
    <input type="text" name="nomor_kamar" placeholder="Contoh: A1 atau 101" required>

    <label>Lantai</label>
    <input type="number" name="lantai" min="1" required>

    <label>Fasilitas</label>
    <textarea name="fasilitas" placeholder="AC, Wifi, KM Dalam..."></textarea>

    <label>Deskripsi Detail Kamar</label>
    <textarea name="deskripsi_kamar" rows="5" placeholder="Jelaskan ukuran kamar, kondisi ruangan, pencahayaan, aturan khusus, dan informasi penting lainnya."></textarea>

    <label>Harga Per Bulan (Rp)</label>
    <input type="number" name="harga" min="1" required>

    <label>Diskon Khusus Kamar (%)</label>
    <input type="number" name="diskon_persen" min="0" max="100" value="0" required>
    <small style="display:block; margin-top:-14px; margin-bottom:18px; color:#64748b;">Isi 0 untuk memakai diskon cabang jika tersedia.</small>

    <label style="color: #1e3a8a; font-weight: bold;">Penghuni (Opsional)</label>
    <select name="id_user">
        <option value="">-- Kosongkan Jika Belum Ada --</option>
        <?php foreach ($users as $user): ?>
            <option value="<?php echo e($user['id_user']); ?>"><?php echo e($user['nama_lengkap'] . ' (' . $user['email'] . ')'); ?></option>
        <?php endforeach; ?>
    </select>

    <?php require base_path('app/Views/admin/forms/room-gallery-fields.php'); ?>

    <button type="submit" name="simpan">Simpan Kamar</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Tambah Data Kamar'; require base_path('app/Views/admin/forms/layout.php'); ?>
