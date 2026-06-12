<?php ob_start(); ?>

    <?php 
        $room = array_merge([
            'id_kamar' => 0,
            'nomor_kamar' => '-',
            'lantai' => '-',
            'fasilitas' => '',
            'deskripsi_kamar' => '',
            'harga' => 0,
            'diskon_persen' => 0,
            'nama_kost' => 'KosOnline',
            'alamat' => '',
            'foto_kost' => '',
            'deskripsi_kost' => '',
            'latitude' => null,
            'longitude' => null,
        ], $room ?? []);
        $gallery = $gallery ?? [];
        $reviews = $reviews ?? [];
        $reviewSummary = array_merge(['rating_avg' => 0, 'total_review' => 0], $reviewSummary ?? []);
        $isWishlisted = (bool) ($isWishlisted ?? false);
        $isLoggedInUser = (bool) ($isLoggedInUser ?? false);
        $hasPromo = isset($room['diskon_persen']) && $room['diskon_persen'] > 0;
        $finalPrice = $hasPromo ? $room['harga'] * (1 - ($room['diskon_persen'] / 100)) : $room['harga'];
        $facilitiesList = array_map('trim', explode(',', $room['fasilitas'] ?? ''));
        $galleryItems = $gallery ?? [];
        if ($galleryItems === []) {
            $galleryItems[] = [
                'kategori' => 'Tampak Kost',
                'judul' => 'Foto utama ' . $room['nama_kost'],
                'nama_file' => $room['foto_kost'],
                'urutan' => 0,
            ];
        }
        $mainPhoto = $galleryItems[0];
        $galleryCategories = array_values(array_unique(array_column($galleryItems, 'kategori')));
        $detailDescription = trim((string) ($room['deskripsi_kamar'] ?? ''));
        if ($detailDescription === '') {
            $detailDescription = trim((string) ($room['deskripsi_kost'] ?? ''));
        }
        if ($detailDescription === '') {
            $detailDescription = 'Hunian nyaman dengan lingkungan yang aman dan akses yang mudah.';
        }
    ?>

    <section class="py-5" style="background: var(--bg-main);">
        <div class="container py-4">
            
            <!-- Breadcrumb / Back Button -->
            <div class="mb-4">
                <a href="<?php echo e(url('/rooms')); ?>" class="text-decoration-none fw-bold" style="color: var(--accent-blue);">
                    <i class="fa-solid fa-arrow-left me-2"></i> Kembali ke Daftar Kamar
                </a>
            </div>

            <div class="row g-5">
                <!-- Gallery Section (Left) -->
                <div class="col-lg-7">
                    <div class="detail-gallery mb-4">
                        <!-- Main Large Image -->
                        <div class="mb-3 position-relative">
                            <img src="<?php echo e(upload_asset($mainPhoto['nama_file'])); ?>" id="main-gallery-img" alt="<?php echo e($mainPhoto['judul'] ?: $mainPhoto['kategori']); ?>" class="w-100 img-fluid" style="border-radius: 16px; height: 450px; object-fit: cover; transition: opacity 0.3s ease;">
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-dark bg-opacity-75 px-3 py-2 text-white" id="gallery-category" style="backdrop-filter: blur(5px); font-size: 0.85rem; border: 1px solid rgba(255,255,255,0.2);">
                                    <i class="fa-solid fa-image me-1" id="gallery-category-icon"></i>
                                    <span id="gallery-category-text"><?php echo e($mainPhoto['kategori']); ?></span>
                                </span>
                            </div>
                            <div class="gallery-caption">
                                <strong id="gallery-title"><?php echo e($mainPhoto['judul'] ?: $mainPhoto['kategori']); ?></strong>
                                <span><?php echo count($galleryItems); ?> foto tersedia</span>
                            </div>
                        </div>

                        <div class="gallery-category-filters mb-3" aria-label="Filter kategori galeri">
                            <button type="button" class="gallery-filter active" data-filter="semua">Semua</button>
                            <?php foreach ($galleryCategories as $category): ?>
                                <button type="button" class="gallery-filter" data-filter="<?php echo e(strtolower($category)); ?>"><?php echo e($category); ?></button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Thumbnails -->
                        <div class="d-flex gap-2 overflow-x-auto pb-2 custom-scrollbar" id="gallery-thumbnails">
                            <?php foreach ($galleryItems as $index => $photo): ?>
                                <div class="thumbnail-item <?php echo $index === 0 ? 'active' : 'opacity-75'; ?>" data-src="<?php echo e(upload_asset($photo['nama_file'])); ?>" data-category="<?php echo e($photo['kategori']); ?>" data-title="<?php echo e($photo['judul'] ?: $photo['kategori']); ?>" data-filter-category="<?php echo e(strtolower($photo['kategori'])); ?>">
                                    <img src="<?php echo e(upload_asset($photo['nama_file'])); ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?php echo e($photo['judul'] ?: $photo['kategori']); ?>">
                                    <div class="thumbnail-category"><?php echo e($photo['kategori']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Room Description / Facilities -->
                    <div class="p-4 bg-white border border-soft rounded-4 mb-4" style="background: var(--card-bg) !important; border-color: var(--border-soft) !important;">
                        <h4 class="fw-bold mb-4" style="color: var(--text-main);">Fasilitas Kamar</h4>
                        <div class="row row-cols-2 row-cols-md-3 g-3">
                            <?php foreach ($facilitiesList as $facility): ?>
                                <?php if (!empty($facility)): ?>
                                    <div class="col">
                                        <div class="facility-badge">
                                            <i class="fa-solid fa-circle-check text-success"></i> <?php echo e($facility); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <h4 class="fw-bold mt-5 mb-3" style="color: var(--text-main);">Detail Kamar</h4>
                        <p class="text-muted" style="line-height: 1.6;">
                            <?php echo nl2br(e($detailDescription)); ?>
                        </p>
                    </div>

                    <!-- Location Mini Map -->
                    <div class="p-4 bg-white border border-soft rounded-4" style="background: var(--card-bg) !important; border-color: var(--border-soft) !important;">
                        <h4 class="fw-bold mb-3" style="color: var(--text-main);"><i class="fa-solid fa-map-location-dot me-2 text-primary"></i>Lokasi Kos</h4>
                        <p class="text-muted mb-4"><?php echo e($room['alamat']); ?></p>
                        <div id="mini-map" style="height: 300px; width: 100%; border-radius: 12px; z-index: 1;"></div>
                    </div>
                </div>

                <!-- Info and Call to Action Column (Right) -->
                <div class="col-lg-5">
                    <div class="detail-info-card p-4 position-sticky" style="top: 100px; background: var(--card-bg); border-color: var(--border-soft);">
                        <span class="badge mb-3 px-3 py-2" style="background-color: var(--accent-blue-soft); color: var(--accent-blue); font-weight: 600;"><?php echo e($room['nama_kost']); ?></span>
                        <h2 class="fw-bold mb-2" style="color: var(--text-main); letter-spacing: -0.5px;">Kamar No. <?php echo e($room['nomor_kamar']); ?></h2>
                        <div class="detail-rating-row mb-3">
                            <span><i class="fa-solid fa-star"></i> <?php echo e((string) $reviewSummary['rating_avg']); ?></span>
                            <b><?php echo e((string) $reviewSummary['total_review']); ?> ulasan</b>
                        </div>
                        <p class="text-muted mb-4"><i class="fa-solid fa-arrow-up-1-9 me-2"></i> Lantai <?php echo e($room['lantai']); ?></p>
                        
                        <hr class="my-4" style="border-color: var(--border-soft);">

                        <div class="mb-4">
                            <span class="text-muted d-block mb-1 small fw-semibold">Biaya Sewa Bulanan</span>
                            <div class="d-flex align-items-center">
                                <?php if ($hasPromo): ?>
                                    <div class="me-3">
                                        <span class="price-slash fs-5">Rp <?php echo number_format((float) $room['harga'], 0, ',', '.'); ?></span>
                                        <span class="badge bg-danger rounded-pill px-2 py-1 small" style="font-size: 0.75rem;">DISKON <?php echo e($room['diskon_persen']); ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h3 class="fw-bold display-6 mb-0 text-primary" style="color: var(--accent-blue) !important;">
                                Rp <?php echo number_format((float) $finalPrice, 0, ',', '.'); ?> <span class="fs-5 text-muted fw-normal">/ bulan</span>
                            </h3>
                        </div>

                        <hr class="my-4" style="border-color: var(--border-soft);">

                        <!-- Action Buttons -->
                        <div class="d-flex flex-column gap-3">
                            <form method="POST" action="<?php echo e(url('/wishlist/toggle')); ?>">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_kamar" value="<?php echo e($room['id_kamar']); ?>">
                                <input type="hidden" name="redirect" value="<?php echo e('/rooms/detail?id=' . $room['id_kamar']); ?>">
                                <button type="submit" class="btn btn-lg w-100 py-3 fw-bold wishlist-detail-btn <?php echo $isWishlisted ? 'saved' : ''; ?>">
                                    <i class="<?php echo $isWishlisted ? 'fa-solid' : 'fa-regular'; ?> fa-heart me-2"></i>
                                    <?php echo $isWishlisted ? 'Tersimpan di Wishlist' : 'Simpan ke Wishlist'; ?>
                                </button>
                            </form>
                            <a href="<?php echo e(url('/rooms/payment?id=' . $room['id_kamar'])); ?>" class="btn btn-primary btn-lg w-100 py-3 fw-bold" style="background-color: var(--accent-blue); border: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(37,99,235,0.3);">
                                <i class="fa-solid fa-credit-card me-2"></i> Pesan & Bayar Sekarang
                            </a>
                            <a href="https://wa.me/6287748703029?text=<?php echo urlencode('Halo Admin, saya tertarik dengan ' . $room['nama_kost'] . ' Kamar ' . $room['nomor_kamar']); ?>" target="_blank" class="btn btn-outline-success btn-lg w-100 py-3 fw-bold" style="border-radius: 12px; border-width: 2px;">
                                <i class="fa-brands fa-whatsapp me-2"></i> Tanya via WhatsApp
                            </a>
                        </div>

                        <div class="detail-chat-card mt-4">
                            <h5><i class="fa-solid fa-comments text-primary me-2"></i>Chat Admin soal kamar ini</h5>
                            <?php if ($isLoggedInUser): ?>
                                <form method="POST" action="<?php echo e(url('/rooms/chat')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id_kamar" value="<?php echo e($room['id_kamar']); ?>">
                                    <textarea name="isi_pesan" rows="3" class="form-control" required>Halo Admin, saya mau tanya tentang <?php echo e($room['nama_kost']); ?> Kamar <?php echo e($room['nomor_kamar']); ?>.</textarea>
                                    <button type="submit" class="btn btn-primary w-100 fw-bold mt-3">
                                        <i class="fa-solid fa-paper-plane me-2"></i>Kirim ke Admin
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted small mb-3">Login dulu supaya chat tersimpan dan admin tahu akun kamu.</p>
                                <a href="<?php echo e(url('/login')); ?>" class="btn btn-primary w-100 fw-bold">Login untuk Chat</a>
                            <?php endif; ?>
                        </div>

                        <!-- Safety Info Badge -->
                        <div class="mt-4 p-3 bg-light rounded-3 text-center d-flex align-items-center justify-content-center gap-2" style="background-color: var(--bg-main) !important;">
                            <i class="fa-solid fa-shield-halved text-success" style="font-size: 1.2rem;"></i>
                            <span class="small fw-semibold text-muted">Jaminan Keamanan Transaksi 100%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4" id="ulasan">
                <div class="col-lg-7">
                    <div class="review-section-card">
                        <div class="review-heading">
                            <div>
                                <span class="section-eyebrow">Ulasan Penghuni</span>
                                <h3>Rating kamar ini <?php echo e((string) $reviewSummary['rating_avg']); ?>/5</h3>
                            </div>
                            <div class="review-score">
                                <i class="fa-solid fa-star"></i>
                                <strong><?php echo e((string) $reviewSummary['rating_avg']); ?></strong>
                                <span><?php echo e((string) $reviewSummary['total_review']); ?> ulasan</span>
                            </div>
                        </div>

                        <?php if ($reviews !== []): ?>
                            <div class="review-list">
                                <?php foreach ($reviews as $review): ?>
                                    <?php
                                    $reviewAvatar = !empty($review['foto_profil']) && $review['foto_profil'] !== 'default.jpg'
                                        ? upload_asset((string) $review['foto_profil'])
                                        : site_image('images.jpg');
                                    ?>
                                    <article class="review-item">
                                        <img src="<?php echo e($reviewAvatar); ?>" alt="Foto <?php echo e($review['nama_lengkap']); ?>">
                                        <div>
                                            <div class="review-item-head">
                                                <strong><?php echo e($review['nama_lengkap']); ?></strong>
                                                <span><?php echo e(date('d M Y', strtotime((string) $review['dibuat_pada']))); ?></span>
                                            </div>
                                            <div class="review-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?php echo $i <= (int) $review['rating'] ? 'fa-solid' : 'fa-regular'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <p><?php echo nl2br(e($review['komentar'])); ?></p>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="review-empty">
                                <i class="fa-regular fa-star"></i>
                                <p>Belum ada ulasan. Jadilah yang pertama memberi pengalaman tentang kamar ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="review-section-card">
                        <h4 class="fw-bold mb-3" style="color: var(--text-main);">Tulis Ulasan</h4>
                        <?php if ($isLoggedInUser): ?>
                            <form method="POST" action="<?php echo e(url('/rooms/review')); ?>" class="review-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_kamar" value="<?php echo e($room['id_kamar']); ?>">
                                <label class="form-label fw-bold text-muted small">Rating</label>
                                <select name="rating" class="form-select mb-3" required>
                                    <option value="">Pilih rating</option>
                                    <option value="5">5 - Sangat bagus</option>
                                    <option value="4">4 - Bagus</option>
                                    <option value="3">3 - Cukup</option>
                                    <option value="2">2 - Kurang</option>
                                    <option value="1">1 - Perlu diperbaiki</option>
                                </select>
                                <label class="form-label fw-bold text-muted small">Komentar</label>
                                <textarea name="komentar" rows="5" class="form-control mb-3" placeholder="Ceritakan pengalaman atau kesanmu tentang kamar ini..." required></textarea>
                                <button type="submit" class="btn btn-primary w-100 fw-bold py-3">
                                    <i class="fa-solid fa-star me-2"></i>Kirim Ulasan
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="text-muted">Login dulu untuk memberi rating dan komentar.</p>
                            <a href="<?php echo e(url('/login')); ?>" class="btn btn-primary fw-bold">Login untuk Ulasan</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
$content = ob_get_clean();
$title = 'Detail Kamar ' . $room['nomor_kamar'] . ' - ' . $room['nama_kost'];
$extraHead = <<<HTML
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .leaflet-container { font-family: inherit; }
        .gallery-caption {
            position: absolute;
            right: 16px;
            bottom: 16px;
            left: 16px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.72);
            color: #fff;
            backdrop-filter: blur(8px);
        }
        .gallery-caption span { opacity: 0.8; font-size: 0.85rem; }
        .gallery-category-filters { display: flex; flex-wrap: wrap; gap: 8px; }
        .gallery-filter {
            border: 1px solid var(--border-soft);
            border-radius: 999px;
            background: var(--card-bg);
            color: var(--text-muted);
            padding: 7px 13px;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .gallery-filter.active {
            border-color: var(--accent-blue);
            background: var(--accent-blue);
            color: #fff;
        }
        .thumbnail-item {
            position: relative;
            width: 120px;
            height: 86px;
            flex-shrink: 0;
            cursor: pointer;
            border-radius: 9px;
            overflow: hidden;
            border: 2px solid transparent;
            transition: opacity 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }
        .thumbnail-item.active { border-color: var(--accent-blue); transform: translateY(-2px); }
        .thumbnail-item[hidden] { display: none; }
        .thumbnail-category {
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            padding: 4px 6px;
            overflow: hidden;
            background: rgba(15, 23, 42, 0.75);
            color: #fff;
            font-size: 0.68rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .detail-rating-row {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: #fff7ed;
            color: #9a3412;
            font-weight: 800;
        }
        .detail-rating-row i,
        .review-score i,
        .review-stars i,
        .room-rating-mini i { color: #f59e0b; }
        .wishlist-detail-btn {
            border-radius: 12px;
            border: 2px solid #fecdd3;
            color: #e11d48;
            background: #fff1f2;
        }
        .wishlist-detail-btn.saved {
            background: #e11d48;
            color: #fff;
            border-color: #e11d48;
        }
        .detail-chat-card,
        .review-section-card {
            border: 1px solid var(--border-soft);
            border-radius: 24px;
            padding: 22px;
            background: var(--card-bg);
            box-shadow: var(--shadow-soft);
        }
        .detail-chat-card h5 { color: var(--text-main); font-weight: 800; margin-bottom: 14px; }
        .review-heading {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            margin-bottom: 20px;
        }
        .review-heading h3 { color: var(--text-main); font-weight: 800; }
        .review-score {
            display: grid;
            justify-items: center;
            min-width: 120px;
            padding: 14px;
            border-radius: 18px;
            background: #fff7ed;
            color: #9a3412;
        }
        .review-score strong { font-size: 2rem; line-height: 1; }
        .review-list { display: grid; gap: 14px; }
        .review-item {
            display: flex;
            gap: 14px;
            padding: 16px;
            border-radius: 18px;
            background: var(--bg-main);
        }
        .review-item img {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            object-fit: cover;
        }
        .review-item-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: var(--text-main);
        }
        .review-item-head span { color: var(--text-muted); font-size: 0.82rem; }
        .review-stars { margin: 5px 0; }
        .review-item p { color: var(--text-muted); margin-bottom: 0; line-height: 1.7; }
        .review-empty {
            display: grid;
            justify-items: center;
            gap: 10px;
            padding: 32px;
            border-radius: 18px;
            background: var(--bg-main);
            color: var(--text-muted);
            text-align: center;
        }
        @media (max-width: 576px) {
            #main-gallery-img { height: 300px !important; }
            .gallery-caption { display: block; }
            .gallery-caption span { display: block; margin-top: 3px; }
        }
    </style>
HTML;

$lat = !empty($room['latitude']) ? (float)$room['latitude'] : -6.705359;
$lng = !empty($room['longitude']) ? (float)$room['longitude'] : 108.555437;
$kostName = json_encode($room['nama_kost']);
$roomNum = json_encode($room['nomor_kamar']);

$extraScripts = <<<HTML
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Gallery Interaction
        const thumbnails = Array.from(document.querySelectorAll('.thumbnail-item'));
        const filters = Array.from(document.querySelectorAll('.gallery-filter'));

        function iconForCategory(category) {
            const normalized = category.toLowerCase();
            if (normalized.includes('mandi')) return 'fa-shower';
            if (normalized.includes('dapur')) return 'fa-kitchen-set';
            if (normalized.includes('parkir')) return 'fa-square-parking';
            if (normalized.includes('bangunan') || normalized.includes('fasad')) return 'fa-building';
            if (normalized.includes('tidur') || normalized.includes('kamar')) return 'fa-bed';
            return 'fa-image';
        }

        thumbnails.forEach(item => {
            item.addEventListener('click', function() {
                thumbnails.forEach(el => {
                    el.classList.remove('active');
                    el.style.borderColor = 'transparent';
                    el.classList.add('opacity-75');
                });
                
                // Add active styling to clicked thumbnail
                this.classList.add('active');
                this.classList.remove('opacity-75');
                this.style.borderColor = 'var(--accent-blue)';
                
                // Update main image & category badge with fade effect
                const mainImg = document.getElementById('main-gallery-img');
                const categoryIcon = document.getElementById('gallery-category-icon');
                const categoryText = document.getElementById('gallery-category-text');
                const galleryTitle = document.getElementById('gallery-title');
                
                mainImg.style.opacity = '0.5';
                setTimeout(() => {
                    mainImg.src = this.getAttribute('data-src');
                    const catText = this.getAttribute('data-category');
                    categoryIcon.className = 'fa-solid ' + iconForCategory(catText) + ' me-1';
                    categoryText.textContent = catText;
                    galleryTitle.textContent = this.getAttribute('data-title');
                    mainImg.alt = this.getAttribute('data-title');
                    mainImg.style.opacity = '1';
                }, 150);
            });
        });

        filters.forEach(filter => {
            filter.addEventListener('click', function() {
                filters.forEach(button => button.classList.remove('active'));
                this.classList.add('active');

                const selected = this.getAttribute('data-filter');
                thumbnails.forEach(item => {
                    item.hidden = selected !== 'semua' && item.getAttribute('data-filter-category') !== selected;
                });

                const firstVisible = thumbnails.find(item => !item.hidden);
                if (firstVisible) firstVisible.click();
            });
        });

        // Mini Map Initialization
        var lat = {$lat};
        var lng = {$lng};
        var kostName = {$kostName};
        var roomNum = {$roomNum};

        var map = L.map('mini-map').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup('<b>' + kostName + '</b><br>Kamar No. ' + roomNum).openPopup();
    </script>
HTML;

require base_path('app/Views/layouts/public.php');
?>
