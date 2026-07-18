<?php
$recommendations = $recommendations ?? [];
$branches = $branches ?? [];
$promos = $promos ?? [];
$homeStats = $homeStats ?? [];
$featuredRoom = $recommendations[0] ?? null;
$featuredImage = $featuredRoom !== null ? upload_asset($featuredRoom['foto_kost'] ?? null) : site_image('pexels-binyaminmellish-106399.jpg');
$startingPrice = (float) ($homeStats['starting_price'] ?? 0);
$whatsappText = rawurlencode('Halo Admin KosOnline, saya ingin tanya kamar kos yang tersedia.');
$whatsappUrl = 'https://wa.me/6287748703029?text=' . $whatsappText;

$finalPrice = static function (array $room): float {
    $discount = (int) ($room['diskon_persen'] ?? 0);
    $price = (float) ($room['harga'] ?? 0);

    return $discount > 0 ? $price * (1 - ($discount / 100)) : $price;
};

$facilityBadges = static function (?string $facilities): array {
    $items = array_filter(array_map('trim', explode(',', (string) $facilities)));

    return array_slice($items, 0, 3);
};

ob_start();
?>

<section class="home-hero">
    <div class="home-hero-glow glow-one"></div>
    <div class="home-hero-glow glow-two"></div>

    <div class="container">
        <div class="row align-items-center g-4 g-lg-5">
            <div class="col-lg-6">
                <span class="home-eyebrow"><i class="fa-solid fa-house-circle-check"></i> Kos strategis di Cirebon</span>
                <h1>Temukan kamar kos yang pas, nyaman, dan siap huni.</h1>
                <p class="home-hero-copy">
                    Cari kamar berdasarkan cabang, fasilitas, dan promo aktif. Lihat detail foto, lokasi, harga, lalu langsung tanya admin atau booking dari satu halaman.
                </p>

                <div class="home-hero-actions">
                    <a href="<?php echo e(url('/rooms')); ?>" class="home-btn-primary"><i class="fa-solid fa-bed"></i> Lihat Kamar Tersedia</a>
                    <a href="<?php echo e($whatsappUrl); ?>" target="_blank" rel="noopener noreferrer" class="home-btn-outline"><i class="fa-brands fa-whatsapp"></i> Tanya Admin</a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="home-hero-visual">
                    <img src="<?php echo e($featuredImage); ?>" alt="Foto kos unggulan" fetchpriority="high" decoding="async">
                    <div class="hero-floating-card hero-card-price">
                        <span>Mulai dari</span>
                        <strong><?php echo $startingPrice > 0 ? 'Rp ' . number_format($startingPrice, 0, ',', '.') : 'Tanya admin'; ?></strong>
                        <small>per bulan</small>
                    </div>
                    <div class="hero-floating-card hero-card-room">
                        <i class="fa-solid fa-key"></i>
                        <div>
                            <strong><?php echo e($homeStats['available_rooms'] ?? 0); ?> kamar</strong>
                            <span>tersedia sekarang</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="home-stats-strip">
            <div class="card">
                <strong><?php echo e($homeStats['total_branches'] ?? 0); ?>+</strong>
                <span>Cabang kost</span>
            </div>
            <div class="card">
                <strong><?php echo e($homeStats['available_rooms'] ?? 0); ?></strong>
                <span>Kamar tersedia</span>
            </div>
            <div class="card">
                <strong><?php echo e($homeStats['active_promos'] ?? 0); ?></strong>
                <span>Promo aktif</span>
            </div>
            <div class="card">
                <strong>24 Jam</strong>
                <span>Admin & CS online</span>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($recommendations)): ?>
<section class="home-section home-section-soft">
    <div class="container">
        <div class="home-section-heading">
            <div>
                <span class="home-eyebrow"><i class="fa-solid fa-star"></i> Berdasarkan ulasan</span>
                <h2>Rekomendasi kamar</h2>
                <p>Kamar tersedia yang diurutkan dari rating dan jumlah ulasan terbaik.</p>
            </div>
            <div class="home-heading-actions">
                <a href="<?php echo e(url('/rooms')); ?>">Lihat semua <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="home-carousel-shell">
            <button type="button" class="home-carousel-btn home-carousel-btn-prev" data-carousel-scroll="recommendation-rail" data-scroll-direction="-1" aria-label="Geser rekomendasi ke kiri">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="home-room-rail" id="recommendation-rail" tabindex="0" aria-label="Rekomendasi kamar">
                <?php foreach ($recommendations as $room): ?>
                    <?php
                    $hasPromo = (int) ($room['diskon_persen'] ?? 0) > 0;
                    $roomFinalPrice = $finalPrice($room);
                    $ratingAvg = (float) ($room['rating_avg'] ?? 0);
                    $totalReview = (int) ($room['total_review'] ?? 0);
                    ?>
                    <article class="home-carousel-item">
                        <a href="<?php echo e(url('/rooms/detail?id=' . $room['id_kamar'])); ?>" class="card h-100 home-room-card">
                            <div class="home-room-image">
                                <img src="<?php echo e(upload_asset($room['foto_kost'])); ?>" class="card-img-top" alt="Foto <?php echo e($room['nama_kost']); ?>" loading="lazy" decoding="async">
                                <?php if ($hasPromo): ?>
                                    <span class="home-promo-badge">Diskon <?php echo e($room['diskon_persen']); ?>%</span>
                                <?php endif; ?>
                                <span class="home-status-badge">Tersedia</span>
                            </div>
                            <div class="card-body home-room-body">
                                <span class="home-branch-badge"><?php echo e($room['nama_kost']); ?></span>
                                <h3>Kamar No. <?php echo e($room['nomor_kamar']); ?></h3>
                                <div class="home-rating-mini">
                                    <i class="fa-solid fa-star"></i>
                                    <strong><?php echo e(number_format($ratingAvg, 1)); ?></strong>
                                    <span><?php echo e((string) $totalReview); ?> ulasan</span>
                                </div>
                                <p><i class="fa-solid fa-location-dot"></i> <?php echo e($room['alamat']); ?></p>
                                <div class="home-facility-list">
                                    <?php foreach ($facilityBadges($room['fasilitas'] ?? '') as $facility): ?>
                                        <span><i class="fa-solid fa-check"></i> <?php echo e($facility); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="home-room-footer">
                                    <div>
                                        <?php if ($hasPromo): ?>
                                            <small>Rp <?php echo number_format((float) $room['harga'], 0, ',', '.'); ?></small>
                                        <?php endif; ?>
                                        <strong>Rp <?php echo number_format($roomFinalPrice, 0, ',', '.'); ?></strong>
                                    </div>
                                    <span>Lihat Detail</span>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
            <button type="button" class="home-carousel-btn home-carousel-btn-next" data-carousel-scroll="recommendation-rail" data-scroll-direction="1" aria-label="Geser rekomendasi ke kanan">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($promos)): ?>
<section class="home-section">
    <div class="container">
        <div class="home-section-heading">
            <div>
                <span class="home-eyebrow danger"><i class="fa-solid fa-fire"></i> Diskon terbesar</span>
                <h2>Promo kamar</h2>
                <p>Promo kamar tersedia diurutkan dari potongan terbesar ke paling kecil.</p>
            </div>
            <div class="home-heading-actions">
                <a href="<?php echo e(url('/rooms?promo=1&sort=promo')); ?>">Lihat semua promo <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="home-carousel-shell">
            <button type="button" class="home-carousel-btn home-carousel-btn-prev" data-carousel-scroll="promo-rail" data-scroll-direction="-1" aria-label="Geser promo ke kiri">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="home-room-rail" id="promo-rail" tabindex="0" aria-label="Promo kamar">
                <?php foreach ($promos as $room): ?>
                    <?php
                    $priceAfterDiscount = $finalPrice($room);
                    $ratingAvg = (float) ($room['rating_avg'] ?? 0);
                    $totalReview = (int) ($room['total_review'] ?? 0);
                    ?>
                    <article class="home-carousel-item">
                        <a href="<?php echo e(url('/rooms/detail?id=' . $room['id_kamar'])); ?>" class="card h-100 home-room-card">
                            <div class="home-room-image">
                                <img src="<?php echo e(upload_asset($room['foto_kost'])); ?>" class="card-img-top" alt="Foto <?php echo e($room['nama_kost']); ?>" loading="lazy" decoding="async">
                                <span class="home-promo-badge">Diskon <?php echo e($room['diskon_persen']); ?>%</span>
                                <span class="home-status-badge">Tersedia</span>
                            </div>
                            <div class="card-body home-room-body">
                                <span class="home-branch-badge"><?php echo e($room['nama_kost']); ?></span>
                                <h3>Kamar No. <?php echo e($room['nomor_kamar']); ?></h3>
                                <div class="home-rating-mini">
                                    <i class="fa-solid fa-star"></i>
                                    <strong><?php echo e(number_format($ratingAvg, 1)); ?></strong>
                                    <span><?php echo e((string) $totalReview); ?> ulasan</span>
                                </div>
                                <p><i class="fa-solid fa-location-dot"></i> <?php echo e($room['alamat']); ?></p>
                                <div class="home-facility-list">
                                    <?php foreach ($facilityBadges($room['fasilitas'] ?? '') as $facility): ?>
                                        <span><i class="fa-solid fa-check"></i> <?php echo e($facility); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="home-room-footer">
                                    <div>
                                        <small>Rp <?php echo number_format((float) $room['harga'], 0, ',', '.'); ?></small>
                                        <strong>Rp <?php echo number_format($priceAfterDiscount, 0, ',', '.'); ?></strong>
                                    </div>
                                    <span>Lihat Detail</span>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
            <button type="button" class="home-carousel-btn home-carousel-btn-next" data-carousel-scroll="promo-rail" data-scroll-direction="1" aria-label="Geser promo ke kanan">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="home-section">
    <div class="container">
        <div class="home-section-heading centered">
            <span class="home-eyebrow"><i class="fa-solid fa-route"></i> Cara booking</span>
            <h2>Booking kos dibuat sesederhana mungkin</h2>
            <p>Tiga langkah pendek supaya calon penghuni tidak bingung dari cari kamar sampai konfirmasi.</p>
        </div>

        <div class="home-steps">
            <div class="card home-step-card">
                <span>01</span>
                <i class="fa-solid fa-magnifying-glass-location"></i>
                <h3>Cari kamar</h3>
                <p>Pilih cabang, lihat foto, cek fasilitas, dan bandingkan harga yang paling cocok.</p>
            </div>
            <div class="card home-step-card">
                <span>02</span>
                <i class="fa-brands fa-whatsapp"></i>
                <h3>Tanya admin</h3>
                <p>Konfirmasi ketersediaan, aturan kos, dan jadwal survei kamar lewat WhatsApp.</p>
            </div>
            <div class="card home-step-card">
                <span>03</span>
                <i class="fa-solid fa-credit-card"></i>
                <h3>Booking & bayar</h3>
                <p>Lanjutkan pemesanan dari halaman detail dan simpan riwayatnya di dashboard member.</p>
            </div>
        </div>
    </div>
</section>

<section class="home-section home-section-soft">
    <div class="container">
        <div class="home-section-heading centered">
            <span class="home-eyebrow"><i class="fa-solid fa-network-wired"></i> Cabang kos</span>
            <h2>Pilih cabang paling dekat dengan aktivitasmu</h2>
            <p>Semua cabang terhubung ke daftar kamar, jadi kamu bisa langsung melihat unit yang tersedia.</p>
        </div>

        <div class="home-branch-grid">
            <?php foreach ($branches as $branch): ?>
                <article class="card home-branch-card">
                    <img src="<?php echo e(upload_asset($branch['foto_kost'])); ?>" class="card-img-top" alt="<?php echo e($branch['nama_kost']); ?>" loading="lazy" decoding="async">
                    <div class="card-body">
                        <span><?php echo e($branch['kamar_tersedia']); ?> kamar tersedia</span>
                        <h3><?php echo e($branch['nama_kost']); ?></h3>
                        <p><i class="fa-solid fa-location-dot"></i> <?php echo e($branch['alamat']); ?></p>
                        <a href="<?php echo e(url('/rooms?cabang=' . $branch['id_kost'])); ?>">Lihat kamar cabang ini</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="home-section">
    <div class="container">
        <div class="home-proof-grid">
            <div>
                <span class="home-eyebrow"><i class="fa-solid fa-shield-heart"></i> Kenapa KosOnline</span>
                <h2>Lebih jelas sebelum datang survei.</h2>
                <p>Calon penghuni bisa melihat harga, foto, fasilitas, lokasi, promo, dan langsung menghubungi admin tanpa pindah-pindah platform.</p>
                <div class="home-benefit-list">
                    <span><i class="fa-solid fa-circle-check"></i> Detail foto berkategori</span>
                    <span><i class="fa-solid fa-circle-check"></i> Diskon tampil transparan</span>
                    <span><i class="fa-solid fa-circle-check"></i> Lokasi cabang bisa dicek di peta</span>
                    <span><i class="fa-solid fa-circle-check"></i> Dashboard member untuk riwayat sewa</span>
                </div>
            </div>

            <div class="home-testimonial-stack">
                <article>
                    <div>
                        <strong>Nanda</strong>
                        <span>Mahasiswa</span>
                    </div>
                    <p>"Cari kamar jadi lebih cepat karena harga dan fasilitasnya sudah kelihatan dari awal."</p>
                </article>
                <article>
                    <div>
                        <strong>Pramana</strong>
                        <span>Pekerja</span>
                    </div>
                    <p>"Admin responsif, bisa tanya dulu lewat WhatsApp sebelum lihat lokasi."</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="home-section home-section-soft">
    <div class="container">
        <div class="home-faq-cta">
            <div>
                <span class="home-eyebrow"><i class="fa-solid fa-circle-question"></i> Pertanyaan umum</span>
                <h2>Masih ragu sebelum booking?</h2>
                <p>Cek jawaban singkat di bawah, atau langsung tanya admin untuk info paling terbaru.</p>
                <a href="<?php echo e($whatsappUrl); ?>" target="_blank" rel="noopener noreferrer" class="home-btn-primary"><i class="fa-brands fa-whatsapp"></i> Tanya via WhatsApp</a>
            </div>
            <div class="home-faq-list">
                <details open>
                    <summary>Apakah kamar yang tampil pasti tersedia?</summary>
                    <p>Daftar kamar mengikuti status dari admin. Sebelum booking, tetap disarankan konfirmasi cepat lewat WhatsApp.</p>
                </details>
                <details>
                    <summary>Bisa survei kamar dulu?</summary>
                    <p>Bisa. Hubungi admin untuk menentukan jadwal survei dan cabang kos yang ingin dilihat.</p>
                </details>
                <details>
                    <summary>Apakah pembayaran sudah online?</summary>
                    <p>Saat ini alur pembayaran sudah disiapkan di website. Verifikasi bukti pembayaran bisa menjadi fitur lanjutan berikutnya.</p>
                </details>
            </div>
        </div>
    </div>
</section>

<section class="home-bottom-cta">
    <div class="container">
        <div>
            <span>Kamar cocok bisa cepat terisi.</span>
            <h2>Mulai cari kamar kos terbaikmu sekarang.</h2>
        </div>
        <a href="<?php echo e(url('/rooms')); ?>" class="home-btn-primary"><i class="fa-solid fa-bed"></i> Cari Kamar</a>
    </div>
</section>

<?php
$content = ob_get_clean();
$title = 'Cari Kos Mudah & Cepat - KosOnline';
$extraHead = '<link rel="stylesheet" href="' . e(asset('css/pages/home.css')) . '">';
require base_path('app/Views/layouts/public.php');
?>
