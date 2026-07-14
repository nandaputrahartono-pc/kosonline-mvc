<?php

declare(strict_types=1);

/**
 * Kartu kamar dipakai bersama oleh halaman /rooms dan blok rekomendasi di halaman detail.
 *
 * Variabel yang dibaca:
 *   $roomCard         (wajib) baris kamar: id_kamar, nomor_kamar, lantai, harga, diskon_persen,
 *                     fasilitas, nama_kost, alamat, foto_kost
 *   $reviewSummaries  (opsional) [id_kamar => ['rating_avg' =>, 'total_review' =>]]
 *   $savedRoomIds     (opsional) daftar id_kamar yang ada di wishlist user
 *   $cardRedirectUrl  (opsional) tujuan balik sesudah tombol wishlist ditekan
 *
 * Sengaja memakai $roomCard, bukan $room: halaman detail sudah punya $room sendiri
 * (kamar yang sedang dibuka) dan tak boleh tertimpa oleh kartu rekomendasi.
 */

$cardRoom = $roomCard;
$cardRoomId = (int) $cardRoom['id_kamar'];

$cardPrice = (float) ($cardRoom['harga'] ?? 0);
$cardDiscount = max(0, min(100, (int) ($cardRoom['diskon_persen'] ?? 0)));
$cardHasPromo = $cardDiscount > 0;
$cardFinalPrice = $cardHasPromo ? $cardPrice * (1 - ($cardDiscount / 100)) : $cardPrice;

$cardFacilities = array_slice(
    array_filter(array_map('trim', explode(',', (string) ($cardRoom['fasilitas'] ?? '')))),
    0,
    4
);

$cardRating = ($reviewSummaries ?? [])[$cardRoomId] ?? ['rating_avg' => 0, 'total_review' => 0];
$cardIsSaved = in_array($cardRoomId, $savedRoomIds ?? [], true);
$cardRedirect = $cardRedirectUrl ?? '/rooms';
?>
<article class="card h-100 room-modern-card">
    <a href="<?php echo e(url('/rooms/detail?id=' . $cardRoomId)); ?>" class="room-card-image">
        <img src="<?php echo e(upload_asset($cardRoom['foto_kost'])); ?>" class="card-img-top" alt="Foto kamar <?php echo e($cardRoom['nomor_kamar']); ?>" loading="lazy" decoding="async">
        <span class="room-card-badge"><?php echo e($cardRoom['nama_kost']); ?></span>
        <?php if ($cardHasPromo): ?>
            <span class="room-card-promo">Diskon <?php echo e((string) $cardDiscount); ?>%</span>
        <?php endif; ?>
    </a>
    <div class="card-body room-card-body">
        <div class="room-card-title">
            <div>
                <span>Lantai <?php echo e($cardRoom['lantai']); ?></span>
                <h3><?php echo e($cardRoom['nomor_kamar']); ?></h3>
            </div>
            <form method="POST" action="<?php echo e(url('/wishlist/toggle')); ?>" class="room-wishlist-form room-title-save-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_kamar" value="<?php echo e($cardRoomId); ?>">
                <input type="hidden" name="redirect" value="<?php echo e($cardRedirect); ?>">
                <button type="submit" class="room-title-save-btn <?php echo $cardIsSaved ? 'saved' : ''; ?>" aria-label="<?php echo $cardIsSaved ? 'Hapus dari simpanan' : 'Simpan kamar'; ?>">
                    <i class="<?php echo $cardIsSaved ? 'fa-solid' : 'fa-regular'; ?> fa-bookmark"></i>
                </button>
            </form>
        </div>
        <div class="room-rating-mini">
            <i class="fa-solid fa-star"></i>
            <strong><?php echo e((string) $cardRating['rating_avg']); ?></strong>
            <span><?php echo e((string) $cardRating['total_review']); ?> ulasan</span>
        </div>
        <p class="room-address"><i class="fa-solid fa-location-dot"></i> <?php echo e($cardRoom['alamat']); ?></p>
        <div class="room-facility-list">
            <?php foreach ($cardFacilities as $cardFacility): ?>
                <span><i class="fa-solid fa-check"></i> <?php echo e($cardFacility); ?></span>
            <?php endforeach; ?>
            <?php if ($cardFacilities === []): ?>
                <span><i class="fa-solid fa-circle-info"></i> Fasilitas bisa ditanyakan ke admin</span>
            <?php endif; ?>
        </div>
        <div class="room-price-row">
            <div>
                <?php if ($cardHasPromo): ?>
                    <small>Rp <?php echo number_format($cardPrice, 0, ',', '.'); ?></small>
                <?php endif; ?>
                <strong>Rp <?php echo number_format($cardFinalPrice, 0, ',', '.'); ?></strong>
                <span>/ bulan</span>
            </div>
        </div>
        <div class="room-card-actions">
            <a href="<?php echo e(url('/rooms/detail?id=' . $cardRoomId)); ?>" class="btn btn-primary">
                Lihat Detail
            </a>
        </div>
    </div>
</article>
