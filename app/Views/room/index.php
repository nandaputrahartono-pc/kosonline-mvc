<?php
$rooms = $rooms ?? [];
$branches = $branches ?? [];
$keyword = (string) ($keyword ?? '');
$idKost = $idKost ?? null;
$promoOnly = (bool) ($promoOnly ?? false);
$sort = (string) ($sort ?? 'recommended');
$reviewSummaries = $reviewSummaries ?? [];
$savedRoomIds = $savedRoomIds ?? [];
$summary = $roomsSummary ?? [
    'total_available' => count($rooms),
    'result_count' => count($rooms),
    'promo_count' => 0,
    'lowest_price' => 0,
];

$finalPrice = static function (array $room): float {
    $price = (float) ($room['harga'] ?? 0);
    $discount = max(0, min(100, (int) ($room['diskon_persen'] ?? 0)));

    return $discount > 0 ? $price * (1 - ($discount / 100)) : $price;
};

$facilityList = static function (?string $facilities): array {
    $items = array_filter(array_map('trim', explode(',', (string) $facilities)));

    return array_slice($items, 0, 4);
};

$selectedBranchName = 'Semua Cabang';
foreach ($branches as $branch) {
    if ($idKost === (int) $branch['id_kost']) {
        $selectedBranchName = (string) $branch['nama_kost'];
        break;
    }
}

ob_start();
?>

<main class="public-page rooms-page">
    <section class="public-page-hero rooms-hero">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="section-eyebrow">Kamar Kos Tersedia</span>
                    <h1>Temukan kamar yang paling pas, tanpa tebak-tebakan.</h1>
                    <p>
                        Ada Banyak Kamar kos yang pas untuk kamu, mulai dari yang terdekat, termurah hingga yang premium.
                        Fasilitas terjangkau, Banyak promo, dan sudah banyak testimoni dengan baik.
                    </p>
                    <div class="hero-actions">
                        <a href="#room-filter" class="btn btn-light btn-lg fw-bold">
                            <i class="fa-solid fa-sliders me-2"></i> Cari Kamar
                        </a>
                        <a href="<?php echo e(url('/map')); ?>" class="btn btn-outline-light btn-lg fw-bold">
                            <i class="fa-solid fa-map-location-dot me-2"></i> Lihat Peta
                        </a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="public-hero-panel">
                        <div>
                            <span>Kamar tersedia</span>
                            <strong><?php echo e((string) $summary['total_available']); ?></strong>
                        </div>
                        <div>
                            <span>Promo aktif</span>
                            <strong><?php echo e((string) $summary['promo_count']); ?></strong>
                        </div>
                        <div>
                            <span>Harga mulai</span>
                            <strong>Rp <?php echo number_format((float) $summary['lowest_price'], 0, ',', '.'); ?></strong>
                        </div>
                        <div>
                            <span>Filter saat ini</span>
                            <strong><?php echo e($selectedBranchName); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="public-section pt-0">
        <div class="container">
            <div id="room-filter" class="public-filter-card">
                <form action="<?php echo e(url('/rooms')); ?>" method="GET" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label for="cabang" class="form-label">Cabang Kos</label>
                        <select name="cabang" id="cabang" class="form-select">
                            <option value="">Semua Cabang</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo e($branch['id_kost']); ?>" <?php echo $idKost === (int) $branch['id_kost'] ? 'selected' : ''; ?>>
                                    <?php echo e($branch['nama_kost']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <label for="searchInput" class="form-label">Cari fasilitas / alamat</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="searchInput" name="cari" class="form-control" placeholder="Contoh: AC, WiFi, dekat kampus" value="<?php echo e($keyword); ?>">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="sort" class="form-label">Urutkan</label>
                        <select name="sort" id="sort" class="form-select">
                            <option value="recommended" <?php echo ($sort ?? 'recommended') === 'recommended' ? 'selected' : ''; ?>>Rekomendasi</option>
                            <option value="termurah" <?php echo ($sort ?? '') === 'termurah' ? 'selected' : ''; ?>>Termurah</option>
                            <option value="termahal" <?php echo ($sort ?? '') === 'termahal' ? 'selected' : ''; ?>>Termahal</option>
                            <option value="promo" <?php echo ($sort ?? '') === 'promo' ? 'selected' : ''; ?>>Promo terbesar</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="room-filter-actions">
                            <label class="promo-toggle">
                                <input type="checkbox" name="promo" value="1" <?php echo !empty($promoOnly) ? 'checked' : ''; ?>>
                                <span><i class="fa-solid fa-tags"></i> Promo saja</span>
                            </label>
                            <button type="submit" class="btn btn-primary fw-bold">Terapkan</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="section-heading split-heading">
                <div>
                    <span class="section-eyebrow">Pilihan Kamar</span>
                    <h2><?php echo e((string) $summary['result_count']); ?> kamar cocok ditemukan</h2>
                </div>
                <a href="<?php echo e(url('/rooms')); ?>" class="soft-link">Reset filter</a>
            </div>

            <?php if (!empty($rooms)): ?>
                <div class="room-modern-grid">
                    <?php foreach ($rooms as $room): ?>
                        <?php
                        $hasPromo = (int) ($room['diskon_persen'] ?? 0) > 0;
                        $priceAfterDiscount = $finalPrice($room);
                        $roomId = (int) $room['id_kamar'];
                        $ratingSummary = $reviewSummaries[$roomId] ?? ['rating_avg' => 0, 'total_review' => 0];
                        $isSaved = in_array($roomId, $savedRoomIds, true);
                        $waText = sprintf(
                            'Halo Admin KosOnline, saya tertarik dengan Kamar No. %s di %s. Boleh dibantu info ketersediaannya?',
                            (string) ($room['nomor_kamar'] ?? ''),
                            (string) ($room['nama_kost'] ?? '')
                        );
                        ?>
                        <article class="room-modern-card">
                            <a href="<?php echo e(url('/rooms/detail?id=' . $room['id_kamar'])); ?>" class="room-card-image">
                                <img src="<?php echo e(upload_asset($room['foto_kost'])); ?>" alt="Foto kamar <?php echo e($room['nomor_kamar']); ?>">
                                <span class="room-card-badge"><?php echo e($room['nama_kost']); ?></span>
                                <?php if ($hasPromo): ?>
                                    <span class="room-card-promo">Diskon <?php echo e((string) $room['diskon_persen']); ?>%</span>
                                <?php endif; ?>
                            </a>
                            <form method="POST" action="<?php echo e(url('/wishlist/toggle')); ?>" class="room-wishlist-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_kamar" value="<?php echo e($roomId); ?>">
                                <input type="hidden" name="redirect" value="<?php echo e('/rooms'); ?>">
                                <button type="submit" class="room-wishlist-btn <?php echo $isSaved ? 'saved' : ''; ?>" aria-label="Simpan kamar">
                                    <i class="<?php echo $isSaved ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                                </button>
                            </form>
                            <div class="room-card-body">
                                <div class="room-card-title">
                                    <div>
                                        <span>Lantai <?php echo e($room['lantai']); ?></span>
                                        <h3><?php echo e($room['nomor_kamar']); ?></h3>
                                    </div>
                                    <i class="fa-solid fa-door-open"></i>
                                </div>
                                <div class="room-rating-mini">
                                    <i class="fa-solid fa-star"></i>
                                    <strong><?php echo e((string) $ratingSummary['rating_avg']); ?></strong>
                                    <span><?php echo e((string) $ratingSummary['total_review']); ?> ulasan</span>
                                </div>
                                <p class="room-address"><i class="fa-solid fa-location-dot"></i> <?php echo e($room['alamat']); ?></p>
                                <div class="room-facility-list">
                                    <?php foreach ($facilityList($room['fasilitas'] ?? '') as $facility): ?>
                                        <span><i class="fa-solid fa-check"></i> <?php echo e($facility); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (empty($facilityList($room['fasilitas'] ?? ''))): ?>
                                        <span><i class="fa-solid fa-circle-info"></i> Fasilitas bisa ditanyakan ke admin</span>
                                    <?php endif; ?>
                                </div>
                                <div class="room-price-row">
                                    <div>
                                        <?php if ($hasPromo): ?>
                                            <small>Rp <?php echo number_format((float) $room['harga'], 0, ',', '.'); ?></small>
                                        <?php endif; ?>
                                        <strong>Rp <?php echo number_format($priceAfterDiscount, 0, ',', '.'); ?></strong>
                                        <span>/ bulan</span>
                                    </div>
                                </div>
                                <div class="room-card-actions">
                                    <a href="<?php echo e(url('/rooms/detail?id=' . $room['id_kamar'])); ?>" class="btn btn-primary">
                                        Lihat Detail
                                    </a>
                                    <a href="https://wa.me/6287748703029?text=<?php echo e(rawurlencode($waText)); ?>" class="btn btn-outline-success" target="_blank" rel="noopener">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="public-empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-bed"></i></div>
                    <h3>Kamar belum ketemu</h3>
                    <p>Coba longgarkan filter, atau chat admin supaya dibantu pilih kamar yang mendekati kebutuhanmu.</p>
                    <div class="empty-actions">
                        <a href="<?php echo e(url('/rooms')); ?>" class="btn btn-primary">Reset Filter</a>
                        <a href="https://wa.me/6287748703029?text=<?php echo e(rawurlencode('Halo Admin KosOnline, saya ingin dibantu cari kamar kos.')); ?>" class="btn btn-outline-success" target="_blank" rel="noopener">
                            Tanya Admin
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
$content = ob_get_clean();
$title = 'Daftar Kamar Kost - KosOnline';
require base_path('app/Views/layouts/public.php');
?>
