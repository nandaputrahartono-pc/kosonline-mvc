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
        $ratingCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($reviews as $rev) {
            $r = (int) ($rev['rating'] ?? 0);
            if (isset($ratingCounts[$r])) {
                $ratingCounts[$r]++;
            }
        }
        $isLoggedInUser = (bool) ($isLoggedInUser ?? false);
        $hasPromo = isset($room['diskon_persen']) && $room['diskon_persen'] > 0;
        $finalPrice = $hasPromo ? $room['harga'] * (1 - ($room['diskon_persen'] / 100)) : $room['harga'];
        $roomLabel = static function (mixed $roomNumber): string {
            $label = trim((string) $roomNumber);
            if ($label === '') {
                return 'Kamar -';
            }

            return preg_match('/^kamar\b/i', $label) === 1 ? $label : 'Kamar ' . $label;
        };
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
                    <div class="card p-4 bg-white border border-soft rounded-4 mb-4" style="background: var(--card-bg) !important; border-color: var(--border-soft) !important;">
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
                    <div class="card p-4 bg-white border border-soft rounded-4" style="background: var(--card-bg) !important; border-color: var(--border-soft) !important;">
                        <h4 class="fw-bold mb-3" style="color: var(--text-main);"><i class="fa-solid fa-map-location-dot me-2 text-primary"></i>Lokasi Kos</h4>
                        <p class="text-muted mb-4"><?php echo e($room['alamat']); ?></p>
                        <div id="mini-map" style="height: 300px; width: 100%; border-radius: 12px; z-index: 1;"></div>
                    </div>
                </div>

                <!-- Info and Call to Action Column (Right) -->
                <div class="col-lg-5">
                    <div class="card detail-info-card p-4 position-sticky" style="top: 100px; background: var(--card-bg); border-color: var(--border-soft);">
                        <div class="detail-card-top">
                            <span class="badge px-3 py-2" style="background-color: var(--accent-blue-soft); color: var(--accent-blue); font-weight: 600; border-radius: 8px;"><?php echo e($room['nama_kost']); ?></span>
                            <form method="POST" action="<?php echo e(url('/wishlist/toggle')); ?>" class="detail-wishlist-inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_kamar" value="<?php echo e($room['id_kamar']); ?>">
                                <input type="hidden" name="redirect" value="<?php echo e('/rooms/detail?id=' . $room['id_kamar']); ?>">
                                <button type="submit" class="<?php echo $isWishlisted ? 'saved' : ''; ?>" aria-label="<?php echo $isWishlisted ? 'Hapus dari simpanan' : 'Simpan kamar'; ?>">
                                    <i class="<?php echo $isWishlisted ? 'fa-solid' : 'fa-regular'; ?> fa-bookmark"></i>
                                    <span><?php echo $isWishlisted ? 'Tersimpan' : 'Simpan'; ?></span>
                                </button>
                            </form>
                        </div>
                        <h2 class="fw-bold mb-2" style="color: var(--text-main); letter-spacing: -0.5px; font-size: 1.65rem;">Kamar No. <?php echo e($room['nomor_kamar']); ?></h2>

                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="d-flex align-items-center gap-1 px-2.5 py-1 rounded-pill bg-warning bg-opacity-10 text-warning fw-bold" style="font-size: 0.8rem;">
                                <i class="fa-solid fa-star"></i>
                                <span><?php echo e(number_format((float)$reviewSummary['rating_avg'], 1)); ?></span>
                            </div>
                            <span class="text-muted small">|</span>
                            <span class="text-muted small fw-semibold"><?php echo e($reviewSummary['total_review']); ?> Ulasan</span>
                        </div>

                        <p class="text-muted mb-4 small"><i class="fa-solid fa-arrow-up-1-9 me-2 text-primary"></i> Lantai <?php echo e($room['lantai']); ?></p>

                        <hr class="my-4" style="border-color: var(--border-soft);">

                        <div class="mb-4">
                            <span class="text-muted d-block mb-1 small fw-semibold">Biaya Sewa Bulanan</span>
                            <div class="d-flex align-items-baseline gap-2 flex-wrap mb-1">
                                <h3 class="fw-bold mb-0 text-primary" style="color: var(--accent-blue) !important; font-size: 1.85rem; letter-spacing: -0.5px;">
                                    Rp <?php echo number_format((float) $finalPrice, 0, ',', '.'); ?>
                                </h3>
                                <span class="fs-6 text-muted fw-normal">/ bulan</span>
                            </div>
                            <?php if ($hasPromo): ?>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="text-muted text-decoration-line-through small">Rp <?php echo number_format((float) $room['harga'], 0, ',', '.'); ?></span>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem; font-weight: 700;">DISKON <?php echo e($room['diskon_persen']); ?>%</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr class="my-4" style="border-color: var(--border-soft);">

                        <!-- Action Buttons -->
                        <div class="detail-action-stack">
                            <a href="<?php echo e(url('/rooms/payment?id=' . $room['id_kamar'])); ?>" class="btn btn-primary btn-lg w-100 py-3 fw-bold btn-pesan-sekarang">
                                <i class="fa-solid fa-credit-card me-2"></i> Pesan & Bayar Sekarang
                            </a>
                            <?php if ($isLoggedInUser): ?>
                                <form method="POST" action="<?php echo e(url('/rooms/chat')); ?>" class="m-0">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id_kamar" value="<?php echo e($room['id_kamar']); ?>">
                                    <button type="submit" class="btn btn-lg w-100 py-3 fw-bold btn-chat-admin-room">
                                        <i class="fa-regular fa-comments me-2"></i> Chat Admin
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="<?php echo e(url('/login')); ?>" class="btn btn-lg w-100 py-3 fw-bold btn-chat-admin-room">
                                    <i class="fa-solid fa-right-to-bracket me-2"></i> Login untuk Chat Admin
                                </a>
                            <?php endif; ?>
                            <div class="detail-tertiary-actions">
                                <a href="https://wa.me/6287748703029?text=<?php echo urlencode('Halo Admin, saya tertarik dengan ' . $room['nama_kost'] . ' - ' . $roomLabel($room['nomor_kamar'])); ?>" target="_blank" rel="noopener" class="btn-tanya-whatsapp">
                                    <i class="fa-brands fa-whatsapp"></i> Tanya via WhatsApp
                                </a>
                                <span>Chat admin akan membawa detail kamar ini otomatis.</span>
                            </div>
                        </div>

                        <!-- Safety Info Badge -->
                        <div class="mt-4 p-3 bg-light rounded-3 text-center d-flex align-items-center justify-content-center gap-2" style="background-color: var(--bg-main) !important; border: 1px solid var(--border-soft);">
                            <i class="fa-solid fa-shield-halved text-success" style="font-size: 1.1rem;"></i>
                            <span class="small fw-semibold text-muted" style="font-size: 0.82rem;">Jaminan Keamanan Transaksi 100%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unified Review Section -->
            <div class="row mt-5" id="ulasan">
                <div class="col-lg-12">
                    <div class="p-4 p-md-5 bg-white border border-soft rounded-4 shadow-sm" style="background: var(--card-bg) !important; border-color: var(--border-soft) !important;">

                        <!-- Header -->
                        <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom" style="border-color: var(--border-soft) !important;">
                            <h3 class="fw-bold mb-0 text-main" style="color: var(--text-main); font-size: 1.5rem; letter-spacing: -0.5px;">
                                <i class="fa-solid fa-comments me-2 text-primary" style="color: var(--accent-blue) !important;"></i> Ulasan & Penilaian Kamar
                            </h3>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold" style="color: var(--accent-blue) !important; background-color: var(--accent-blue-soft) !important; font-size: 0.85rem;">
                                <?php echo e((string) $reviewSummary['total_review']); ?> Ulasan
                            </span>
                        </div>

                        <!-- Rating summary and distribution -->
                        <div class="row g-4 mb-5">
                            <!-- Left: Rating Card -->
                            <div class="col-md-4">
                                <div class="p-4 rounded-4 text-center d-flex flex-column align-items-center justify-content-center h-100" style="background: var(--bg-main); border: 1px solid var(--border-soft); min-height: 180px;">
                                    <span class="text-muted small fw-bold text-uppercase mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">RATING RATA-RATA</span>
                                    <h1 class="display-3 fw-bold text-main mb-1" style="color: var(--text-main); line-height: 1; font-weight: 800;">
                                        <?php echo e((string) $reviewSummary['rating_avg']); ?>
                                    </h1>
                                    <div class="my-2" style="font-size: 1.25rem;">
                                        <?php
                                        $avg = (float) $reviewSummary['rating_avg'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= floor($avg)) {
                                                echo '<i class="fa-solid fa-star text-warning"></i>';
                                            } elseif ($i - $avg < 1) {
                                                echo '<i class="fa-solid fa-star-half-stroke text-warning"></i>';
                                            } else {
                                                echo '<i class="fa-regular fa-star text-warning"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="text-muted small">Berdasarkan <?php echo e((string) $reviewSummary['total_review']); ?> ulasan</span>
                                </div>
                            </div>

                            <!-- Right: Rating Bar Breakdown -->
                            <div class="col-md-8">
                                <div class="p-4 rounded-4 h-100 d-flex flex-column justify-content-center" style="background: var(--bg-main); border: 1px solid var(--border-soft);">
                                    <?php
                                    $totalReviewsCount = max(1, $reviewSummary['total_review']);
                                    for ($ratingVal = 5; $ratingVal >= 1; $ratingVal--):
                                        $count = $ratingCounts[$ratingVal] ?? 0;
                                        $pct = round(($count / $totalReviewsCount) * 100);
                                    ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="text-muted small fw-semibold me-3" style="width: 70px; text-align: right;"><?php echo $ratingVal; ?> Bintang</span>
                                            <div class="progress flex-grow-1" style="height: 8px; border-radius: 4px; background-color: var(--border-soft);">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $pct; ?>%; border-radius: 4px;" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="text-muted small fw-semibold ms-3" style="width: 40px; text-align: left;"><?php echo $pct; ?>%</span>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Write Review Panel -->
                        <div class="p-4 rounded-4 mb-5" style="background: var(--card-bg); border: 2px dashed var(--border-soft);">
                            <h4 class="fw-bold mb-3 text-main" style="color: var(--text-main); font-size: 1.15rem;">
                                <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Tulis Ulasan Anda
                            </h4>
                            <?php if ($isLoggedInUser): ?>
                                <form method="POST" action="<?php echo e(url('/rooms/review')); ?>" class="review-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id_kamar" value="<?php echo e($room['id_kamar']); ?>">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Rating Kamar</label>
                                            <select name="rating" class="form-select" style="border-radius: 8px; padding: 10px; border-color: var(--border-soft); background-color: var(--bg-main); color: var(--text-main);" required>
                                                <option value="">Pilih rating...</option>
                                                <option value="5">⭐⭐⭐⭐⭐ (5 - Sangat Bagus)</option>
                                                <option value="4">⭐⭐⭐⭐ (4 - Bagus)</option>
                                                <option value="3">⭐⭐⭐ (3 - Cukup)</option>
                                                <option value="2">⭐⭐ (2 - Kurang)</option>
                                                <option value="1">⭐ (1 - Perlu Perbaikan)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label fw-bold text-muted small">Komentar / Pengalaman</label>
                                            <textarea name="komentar" rows="3" class="form-control" placeholder="Ceritakan pengalaman Anda selama tinggal di kamar ini..." style="border-radius: 8px; border-color: var(--border-soft); background-color: var(--bg-main); color: var(--text-main);" required></textarea>
                                        </div>
                                        <div class="col-12 text-end">
                                            <button type="submit" class="btn btn-primary px-4 py-2.5 fw-bold btn-animate-hover" style="background-color: var(--accent-blue); border: none; border-radius: 8px;">
                                                <i class="fa-solid fa-paper-plane me-2"></i> Kirim Ulasan
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 py-2">
                                    <p class="text-muted mb-0 small"><i class="fa-solid fa-info-circle me-1 text-primary"></i> Anda harus login terlebih dahulu untuk memberikan ulasan pada kamar ini.</p>
                                    <a href="<?php echo e(url('/login')); ?>" class="btn btn-primary fw-bold px-4 py-2 btn-animate-hover" style="background-color: var(--accent-blue); border: none; border-radius: 8px;">
                                        <i class="fa-solid fa-right-to-bracket me-2"></i> Login untuk Ulasan
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Review List (Comments at the bottom) -->
                        <div class="mt-4">
                            <h4 class="fw-bold mb-4 text-main" style="color: var(--text-main); font-size: 1.25rem;">
                                <i class="fa-solid fa-list-check me-2 text-primary"></i> Ulasan dari Penghuni
                            </h4>

                            <?php if ($reviews !== []): ?>
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($reviews as $review): ?>
                                        <?php
                                        $reviewAvatar = !empty($review['foto_profil']) && $review['foto_profil'] !== 'default.jpg'
                                            ? upload_asset((string) $review['foto_profil'])
                                            : site_image('images.jpg');
                                        ?>
                                        <div class="p-4 rounded-4 transition-hover" style="background: var(--bg-main); border: 1px solid var(--border-soft);">
                                            <div class="d-flex align-items-start gap-3">
                                                <img src="<?php echo e($reviewAvatar); ?>" class="rounded-circle border" style="width: 48px; height: 48px; object-fit: cover;" alt="Foto <?php echo e($review['nama_lengkap']); ?>">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1 mb-2">
                                                        <div>
                                                            <h6 class="fw-bold text-main mb-0" style="color: var(--text-main);"><?php echo e($review['nama_lengkap']); ?></h6>
                                                            <span class="text-muted small"><?php echo e(date('d M Y', strtotime((string) $review['dibuat_pada']))); ?></span>
                                                        </div>
                                                        <div class="rating-stars">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="<?php echo $i <= (int) $review['rating'] ? 'fa-solid' : 'fa-regular'; ?> fa-star text-warning" style="font-size: 0.9rem;"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                    <p class="text-muted mb-0" style="line-height: 1.6; font-size: 0.95rem;">
                                                        <?php echo nl2br(e($review['komentar'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 rounded-4" style="background: var(--bg-main); border: 1px dashed var(--border-soft);">
                                    <div class="mb-3">
                                        <i class="fa-regular fa-star text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                                    </div>
                                    <h6 class="fw-bold text-muted">Belum Ada Ulasan</h6>
                                    <p class="text-muted mb-0 small">Jadilah yang pertama memberikan ulasan dan pengalaman Anda tinggal di kamar ini!</p>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
$content = ob_get_clean();
$title = 'Detail ' . $roomLabel($room['nomor_kamar']) . ' - ' . $room['nama_kost'];
$detailPageCss = e(asset('css/pages/detail.css'));
$extraHead = <<<HTML
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="{$detailPageCss}">
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
        .detail-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }
        .detail-wishlist-inline {
            flex: 0 0 auto;
            margin: 0;
        }
        .detail-wishlist-inline button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 8px 12px;
            border: 1px solid rgba(37, 99, 235, 0.18);
            border-radius: 999px;
            background: var(--accent-blue-soft);
            color: var(--accent-blue);
            font-weight: 800;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .detail-wishlist-inline button:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.14);
        }
        .detail-wishlist-inline button.saved {
            background: var(--accent-blue);
            color: #fff;
            border-color: var(--accent-blue);
        }
        .detail-action-stack {
            display: grid;
            gap: 12px;
        }
        .btn-pesan-sekarang {
            background-color: var(--accent-blue) !important;
            border: none !important;
            border-radius: 14px !important;
            box-shadow: 0 10px 24px rgba(37,99,235,0.28) !important;
            transition: all 0.25s ease !important;
            color: #fff !important;
        }
        .btn-pesan-sekarang:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 22px rgba(37,99,235,0.4) !important;
            filter: brightness(1.05);
        }
        .btn-chat-admin-room {
            border: 1px solid var(--border-soft) !important;
            border-radius: 14px !important;
            background: var(--bg-main) !important;
            color: var(--text-main) !important;
            box-shadow: none !important;
            transition: all 0.25s ease !important;
        }
        .btn-chat-admin-room:hover {
            transform: translateY(-1px);
            border-color: var(--accent-blue) !important;
            color: var(--accent-blue) !important;
            background: var(--accent-blue-soft) !important;
        }
        .detail-tertiary-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-top: 4px;
        }
        .btn-tanya-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #15803d;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-tanya-whatsapp:hover {
            color: #166534;
        }
        .detail-tertiary-actions span {
            color: var(--text-muted);
            font-size: 0.82rem;
            line-height: 1.4;
            text-align: right;
        }
        .btn-animate-hover {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-animate-hover:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
            box-shadow: 0 8px 20px rgba(37,99,235,0.3) !important;
        }
        .transition-hover {
            transition: all 0.25s ease;
        }
        .transition-hover:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
            border-color: var(--accent-blue) !important;
        }
        /* Chat Input - Modern Messenger Style */
        .chat-input-wrapper {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            padding: 8px 8px 8px 20px;
            border-radius: 28px;
            background: var(--card-bg);
            border: 1.5px solid var(--border-soft);
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }
        .chat-input-wrapper:focus-within {
            border-color: var(--accent-blue);
            box-shadow: 0 2px 16px rgba(37, 99, 235, 0.12);
        }
        .chat-input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            color: var(--text-main);
            font-size: 0.9rem;
            line-height: 1.5;
            resize: none;
            max-height: 120px;
            padding: 8px 0;
            font-family: inherit;
        }
        .chat-input::placeholder {
            color: var(--text-muted);
            opacity: 0.65;
        }
        .chat-send-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 50%;
            background: var(--accent-blue);
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s ease;
            text-decoration: none;
            flex-shrink: 0;
        }
        .chat-send-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            filter: brightness(1.08);
            color: #fff;
        }
        .chat-send-btn:active {
            transform: scale(0.95);
        }
        .chat-input-disabled {
            opacity: 0.7;
            cursor: default;
        }
        .chat-input-placeholder {
            flex: 1;
            color: var(--text-muted);
            font-size: 0.88rem;
            padding: 10px 0;
        }
        @media (max-width: 576px) {
            #main-gallery-img { height: 300px !important; }
            .gallery-caption { display: block; }
            .gallery-caption span { display: block; margin-top: 3px; }
            .detail-card-top { align-items: flex-start; flex-direction: column; }
            .detail-wishlist-inline,
            .detail-wishlist-inline button { width: 100%; justify-content: center; }
            .detail-tertiary-actions { align-items: flex-start; flex-direction: column; }
            .detail-tertiary-actions span { text-align: left; }
            .chat-input-wrapper { padding: 6px 6px 6px 14px; }
            .chat-send-btn { width: 38px; height: 38px; min-width: 38px; font-size: 0.9rem; }
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

        // Chat Input: auto-resize + Enter to send
        (function() {
            const chatInput = document.getElementById('chatInput');
            const chatForm = document.getElementById('chatForm');
            if (!chatInput || !chatForm) return;

            chatInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });

            chatInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (this.value.trim()) {
                        chatForm.submit();
                    }
                }
            });
        })();
    </script>
HTML;

require base_path('app/Views/layouts/public.php');
?>
