<?php /** @var array $gallery Daftar foto galeri kamar; kosong saat membuat kamar baru. */ ?>
<hr class="section-divider">

<div class="section-heading">
    <div>
        <h3>Galeri Berkategori</h3>
        <p>Gunakan kategori bebas seperti Kamar Tidur, Kamar Mandi, Dapur, atau Area Parkir.</p>
    </div>
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
            <article class="card gallery-editor-card">
                <img src="<?php echo e(upload_asset($photo['nama_file'])); ?>" class="card-img-top" alt="<?php echo e($photo['judul'] ?: $photo['kategori']); ?>">
                <div class="gallery-editor-fields">
                    <label>Kategori</label>
                    <input type="text" name="existing_gallery[<?php echo e($photo['id_galeri']); ?>][kategori]" value="<?php echo e($photo['kategori']); ?>" class="form-control" list="gallery-category-suggestions" maxlength="60">

                    <label>Judul Foto</label>
                    <input type="text" name="existing_gallery[<?php echo e($photo['id_galeri']); ?>][judul]" value="<?php echo e($photo['judul']); ?>" class="form-control" maxlength="120" placeholder="Contoh: Kamar mandi dalam">

                    <label>Urutan</label>
                    <input type="number" name="existing_gallery[<?php echo e($photo['id_galeri']); ?>][urutan]" value="<?php echo e($photo['urutan']); ?>" class="form-control" min="0" max="65535">

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

<div class="gallery-upload">
    <div class="gallery-upload-head">
        <strong><i class="fa-regular fa-images"></i> Tambah Foto Baru</strong>
        <span class="gallery-file-count" id="gallery-file-count" hidden></span>
    </div>

    <label class="gallery-batch-field">
        <span>Kategori untuk semua foto yang diunggah</span>
        <input type="text" name="gallery_batch_category" class="form-control" list="gallery-category-suggestions" maxlength="60" placeholder="Contoh: Kamar Tidur (kosongkan = Lainnya)">
    </label>

    <label class="gallery-dropzone" id="gallery-dropzone">
        <input type="file" name="gallery_photos[]" id="gallery-input" multiple accept="image/jpeg,image/png,image/webp" hidden>
        <i class="fa-solid fa-cloud-arrow-up"></i>
        <span class="gallery-dropzone-title">Seret &amp; letakkan foto di sini, atau klik untuk memilih</span>
        <span class="gallery-dropzone-hint">Bisa pilih banyak sekaligus &middot; JPG/PNG/WebP &middot; maks 5&nbsp;MB per foto &middot; maks 20 foto</span>
    </label>

    <p class="gallery-upload-error" id="gallery-upload-error" hidden></p>
    <div class="gallery-preview-grid" id="gallery-preview-grid"></div>
</div>

<script>
    (() => {
        const input = document.getElementById('gallery-input');
        const dropzone = document.getElementById('gallery-dropzone');
        const grid = document.getElementById('gallery-preview-grid');
        const countEl = document.getElementById('gallery-file-count');
        const errorEl = document.getElementById('gallery-upload-error');

        const MAX_FILES = 20;
        const MAX_SIZE = 5 * 1024 * 1024;
        const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

        const files = [];
        const urls = new Map();

        function syncInput() {
            const data = new DataTransfer();
            files.forEach((file) => data.items.add(file));
            input.files = data.files;
        }

        function updateCount() {
            countEl.hidden = files.length === 0;
            countEl.textContent = files.length + ' foto dipilih';
        }

        function render() {
            grid.innerHTML = '';
            files.forEach((file, index) => {
                let url = urls.get(file);
                if (!url) {
                    url = URL.createObjectURL(file);
                    urls.set(file, url);
                }

                const item = document.createElement('div');
                item.className = 'gallery-preview-item';

                const img = document.createElement('img');
                img.src = url;
                img.alt = file.name;

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'gallery-preview-remove';
                remove.setAttribute('aria-label', 'Hapus foto ' + file.name);
                remove.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                remove.addEventListener('click', () => removeAt(index));

                item.appendChild(img);
                item.appendChild(remove);
                grid.appendChild(item);
            });

            updateCount();
            syncInput();
        }

        function removeAt(index) {
            const file = files[index];
            if (file && urls.has(file)) {
                URL.revokeObjectURL(urls.get(file));
                urls.delete(file);
            }
            files.splice(index, 1);
            errorEl.hidden = true;
            render();
        }

        function addFiles(incoming) {
            const rejected = [];

            Array.from(incoming).forEach((file) => {
                if (!ALLOWED.includes(file.type)) {
                    rejected.push(file.name + ' (bukan JPG/PNG/WebP)');
                    return;
                }
                if (file.size > MAX_SIZE) {
                    rejected.push(file.name + ' (lebih dari 5 MB)');
                    return;
                }
                if (files.some((existing) => existing.name === file.name && existing.size === file.size)) {
                    return;
                }
                if (files.length >= MAX_FILES) {
                    rejected.push(file.name + ' (melebihi batas 20 foto)');
                    return;
                }
                files.push(file);
            });

            if (rejected.length) {
                errorEl.textContent = 'Sebagian foto dilewati: ' + rejected.join(', ') + '.';
                errorEl.hidden = false;
            } else {
                errorEl.hidden = true;
            }

            render();
        }

        input.addEventListener('change', () => addFiles(input.files));

        ['dragenter', 'dragover'].forEach((evt) => {
            dropzone.addEventListener(evt, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach((evt) => {
            dropzone.addEventListener(evt, (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            if (event.dataTransfer && event.dataTransfer.files) {
                addFiles(event.dataTransfer.files);
            }
        });
    })();
</script>
