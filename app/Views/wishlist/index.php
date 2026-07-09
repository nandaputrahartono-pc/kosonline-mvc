<?php
$rooms = $rooms ?? [];
$reviewSummaries = $reviewSummaries ?? [];
$savedRoomIds = $savedRoomIds ?? [];

$finalPrice = static function (array $room): float {
    $price = (float) ($room['harga'] ?? 0);
    $discount = max(0, min(100, (int) ($room['diskon_persen'] ?? 0)));

    return $discount > 0 ? $price * (1 - ($discount / 100)) : $price;
};

$facilityList = static function (?string $facilities): array {
    $items = array_filter(array_map('trim', explode(',', (string) $facilities)));

    return array_slice($items, 0, 4);
};

ob_start();
?>

<main class="public-page wishlist-page">
    <section class="public-section wishlist-section">
        <div class="container">
            <div class="section-heading split-heading">
                <div>
                    <h1 class="wishlist-title">Kamar Tersimpan</h1>
                </div>
                <div class="wishlist-actions">
                    <span><?php echo e((string) count($rooms)); ?> tersimpan</span>
                    <a href="<?php echo e(url('/rooms')); ?>" class="btn btn-primary fw-bold">
                        <i class="fa-solid fa-plus me-2"></i> Tambah
                    </a>
                </div>
            </div>

            <?php if ($rooms !== []): ?>
                <div class="room-modern-grid">
                    <?php foreach ($rooms as $room): ?>
                        <?php
                        $hasPromo = (int) ($room['diskon_persen'] ?? 0) > 0;
                        $priceAfterDiscount = $finalPrice($room);
                        $roomId = (int) $room['id_kamar'];
                        $ratingSummary = $reviewSummaries[$roomId] ?? ['rating_avg' => 0, 'total_review' => 0];
                        $isSaved = in_array($roomId, $savedRoomIds, true);
                        ?>
                        <article class="card h-100 room-modern-card">
                            <a href="<?php echo e(url('/rooms/detail?id=' . $roomId)); ?>" class="room-card-image">
                                <img src="<?php echo e(upload_asset($room['foto_kost'] ?? '')); ?>" class="card-img-top" alt="Foto kamar <?php echo e($room['nomor_kamar'] ?? ''); ?>" loading="lazy" decoding="async">
                                <span class="room-card-badge"><?php echo e($room['nama_kost'] ?? 'KosOnline'); ?></span>
                                <?php if ($hasPromo): ?>
                                    <span class="room-card-promo">Diskon <?php echo e((string) $room['diskon_persen']); ?>%</span>
                                <?php endif; ?>
                            </a>
                            <div class="card-body room-card-body">
                                <div class="room-card-title">
                                    <div>
                                        <span>Lantai <?php echo e((string) ($room['lantai'] ?? '-')); ?></span>
                                        <h3><?php echo e((string) ($room['nomor_kamar'] ?? 'Kamar')); ?></h3>
                                    </div>
                                    <form method="POST" action="<?php echo e(url('/wishlist/toggle')); ?>" class="room-wishlist-form room-title-save-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id_kamar" value="<?php echo e($roomId); ?>">
                                        <input type="hidden" name="redirect" value="/wishlist">
                                        <button type="submit" class="room-title-save-btn <?php echo $isSaved ? 'saved' : ''; ?>" aria-label="Hapus dari simpanan">
                                            <i class="<?php echo $isSaved ? 'fa-solid' : 'fa-regular'; ?> fa-bookmark"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="room-rating-mini">
                                    <i class="fa-solid fa-star"></i>
                                    <strong><?php echo e((string) $ratingSummary['rating_avg']); ?></strong>
                                    <span><?php echo e((string) $ratingSummary['total_review']); ?> ulasan</span>
                                </div>
                                <p class="room-address"><i class="fa-solid fa-location-dot"></i> <?php echo e((string) ($room['alamat'] ?? 'Alamat belum tersedia')); ?></p>
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
                                    <a href="<?php echo e(url('/rooms/detail?id=' . $roomId)); ?>" class="btn btn-primary">
                                        Lihat Detail
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="public-empty-state">
                    <div class="empty-icon"><i class="fa-regular fa-bookmark"></i></div>
                    <h3>Belum ada kamar tersimpan</h3>
                    <p>Simpan kamar dari halaman Kamar Kos atau Detail Kamar. Nanti semua pilihan yang kamu incar muncul rapi di sini.</p>
                    <div class="empty-actions">
                        <a href="<?php echo e(url('/rooms')); ?>" class="btn btn-primary">Cari Kamar</a>
                        <a href="<?php echo e(url('/map')); ?>" class="btn btn-outline-primary">Lihat Peta</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
$content = ob_get_clean();
$title = 'Kamar Tersimpan - KosOnline';
require base_path('app/Views/layouts/public.php');
?>
