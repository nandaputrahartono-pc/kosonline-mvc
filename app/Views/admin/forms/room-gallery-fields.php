<hr class="section-divider">

<div class="section-heading">
    <div>
        <h3>Galeri Berkategori</h3>
        <p>Gunakan kategori bebas seperti Kamar Tidur, Kamar Mandi, Dapur, atau Area Parkir.</p>
    </div>
    <button type="button" class="btn-add-gallery" id="add-gallery-row">
        <i class="fa-solid fa-plus"></i> Tambah Foto
    </button>
</div>

<datalist id="gallery-category-suggestions">
    <option value="Kamar Tidur">
    <option value="Kamar Mandi">
    <option value="Dapur">
    <option value="Area Parkir">
    <option value="Fasad Bangunan">
    <option value="Fasilitas Umum">
</datalist>

<?php if (!empty($gallery)): ?>
    <div class="existing-gallery-grid">
        <?php foreach ($gallery as $photo): ?>
            <article class="gallery-editor-card">
                <img src="<?php echo e(upload_asset($photo['nama_file'])); ?>" alt="<?php echo e($photo['judul'] ?: $photo['kategori']); ?>">
                <div class="gallery-editor-fields">
                    <label>Kategori</label>
                    <input type="text" name="existing_gallery[<?php echo e($photo['id_galeri']); ?>][kategori]" value="<?php echo e($photo['kategori']); ?>" list="gallery-category-suggestions" maxlength="60">

                    <label>Judul Foto</label>
                    <input type="text" name="existing_gallery[<?php echo e($photo['id_galeri']); ?>][judul]" value="<?php echo e($photo['judul']); ?>" maxlength="120" placeholder="Contoh: Kamar mandi dalam">

                    <label>Urutan</label>
                    <input type="number" name="existing_gallery[<?php echo e($photo['id_galeri']); ?>][urutan]" value="<?php echo e($photo['urutan']); ?>" min="0" max="65535">

                    <label class="delete-photo-option">
                        <input type="checkbox" name="existing_gallery[<?php echo e($photo['id_galeri']); ?>][hapus]" value="1">
                        Hapus foto ini
                    </label>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-gallery-note">
        Belum ada foto khusus kamar. Selama galeri kosong, halaman detail memakai foto utama kost.
    </div>
<?php endif; ?>

<div id="new-gallery-rows"></div>

<template id="gallery-row-template">
    <article class="new-gallery-row">
        <div class="new-gallery-row-header">
            <strong>Foto Galeri Baru</strong>
            <button type="button" class="btn-remove-gallery" aria-label="Hapus baris foto">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <label>File Foto</label>
        <input type="file" name="gallery_photos[]" accept="image/jpeg,image/png,image/webp">

        <div class="field-grid">
            <div>
                <label>Kategori</label>
                <input type="text" name="gallery_categories[]" list="gallery-category-suggestions" maxlength="60" placeholder="Contoh: Kamar Mandi">
            </div>
            <div>
                <label>Urutan</label>
                <input type="number" name="gallery_orders[]" min="0" max="65535" value="0">
            </div>
        </div>

        <label>Judul Foto (Opsional)</label>
        <input type="text" name="gallery_titles[]" maxlength="120" placeholder="Contoh: Kamar mandi dalam dengan shower">
    </article>
</template>

<script>
    (() => {
        const rows = document.getElementById('new-gallery-rows');
        const template = document.getElementById('gallery-row-template');
        const addButton = document.getElementById('add-gallery-row');

        function addGalleryRow() {
            rows.appendChild(template.content.cloneNode(true));
        }

        addButton.addEventListener('click', addGalleryRow);
        rows.addEventListener('click', (event) => {
            const removeButton = event.target.closest('.btn-remove-gallery');
            if (removeButton) {
                removeButton.closest('.new-gallery-row').remove();
            }
        });

        addGalleryRow();
    })();
</script>
