<?php ob_start(); ?>
    <section class="home" id="home">
        <div class="home-content">
            <h1>Cari Kos Nyaman?<br><span>Tanpa Ribet</span></h1>
            <p>Temukan kos terbaik dengan fasilitas lengkap, harga terjangkau, dan lokasi strategis</p>

            <div class="home-action">
                <a href="<?php echo e(url('/rooms')); ?>" class="btn-primary">Lihat Kamar</a>
                <a href="<?php echo e(url('/contact')); ?>" class="btn-secondary">Hubungi Pemilik</a>
            </div>
        </div>

        <div class="home-image">
            <img src="<?php echo e(site_image('kossan.jpg')); ?>" alt="kos">
        </div>
    </section>

    <section class="section">
        <div class="section-title">
            <h1>Rekomendasi Terbaru</h1>
        </div>

        <div class="slider-wrapper">
            <button class="slider-btn left" onclick="slideLeft()">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div class="slider" id="slider">
                <?php foreach ($recommendations as $data): ?>
                    <div class="kost-card">
                        <div class="kost-image">
                            <img src="<?php echo e(upload_asset($data['foto_kost'])); ?>" alt="Foto Kost">
                        </div>
                        <div class="kost-body">
                            <span class="badge"><?php echo e($data['nama_kost']); ?></span>
                            <h3>Kamar <?php echo e($data['nomor_kamar']); ?></h3>
                            <p class="lokasi"><i class="fa-solid fa-location-dot"></i> <?php echo e($data['alamat']); ?></p>
                            <div class="kost-poster">
                                <p class="harga"><strong>Rp <?php echo number_format((float) $data['harga'], 0, ',', '.'); ?></strong>/bulan</p>
                                <p class="sisa">Lt. <?php echo e($data['lantai']); ?></p>
                            </div>
                        </div>
                        <a href="<?php echo e(url('/rooms')); ?>" style="display:block; text-align:center; padding:10px; background:#1e3a8a; color:white; text-decoration:none; margin-top:10px;">Lihat Detail</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="slider-btn right" onclick="slideRight()">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </section>

    <section class="section">
        <div class="section-title">
            <h1>Lokasi Populer</h1>
        </div>
        <div class="location-grid">
            <div class="location-card">
                <img src="<?php echo e(site_image('kossan.jpg')); ?>" alt="">
                <div class="location-overlay">
                    <a href="<?php echo e(url('/rooms?cari=Cirebon')); ?>"><h3>Cirebon</h3></a>
                </div>
            </div>
        </div>
    </section>
<?php
$content = ob_get_clean();
$title = 'KosOnline - Home';
require base_path('app/Views/layouts/public.php');
