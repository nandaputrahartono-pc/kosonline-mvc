<?php ob_start(); ?>
<h2>Tambah Data Kamar</h2>
<form method="POST">
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
    <input type="number" name="lantai" required>

    <label>Fasilitas</label>
    <textarea name="fasilitas" placeholder="AC, Wifi, KM Dalam..."></textarea>

    <label>Harga Per Bulan (Rp)</label>
    <input type="number" name="harga" required>

    <label style="color: #1e3a8a; font-weight: bold;">Penghuni (Opsional)</label>
    <select name="id_user">
        <option value="">-- Kosongkan Jika Belum Ada --</option>
        <?php foreach ($users as $user): ?>
            <option value="<?php echo e($user['id_user']); ?>"><?php echo e($user['nama_lengkap'] . ' (' . $user['email'] . ')'); ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" name="simpan">Simpan Kamar</button>
    <a href="<?php echo e(url('/admin/dashboard')); ?>" class="btn-batal">Batal</a>
</form>
<?php $content = ob_get_clean(); $title = 'Tambah Data Kamar'; require base_path('app/Views/admin/forms/layout.php'); ?>
