<?php ob_start(); ?>
    <main class="kamar-page">
        <div class="search-bar">
            <form action="<?php echo e(url('/rooms')); ?>" method="GET" style="display: flex; width: 100%; justify-content: center; gap: 10px;">
                <div class="search-input" style="flex: 1; max-width: 500px;">
                    <i class="fa-solid fa-location-crosshairs"></i>
                    <div class="search-text">
                        <span>Cari Kost</span>
                        <input type="text" id="searchInput" name="cari" placeholder="Nama kost / alamat..." value="<?php echo e($keyword); ?>">
                    </div>
                </div>
                <button type="submit" class="btn-search" id="searchBtn">
                    <i class="fa-solid fa-magnifying-glass"></i> Cari
                </button>
            </form>
        </div>

        <div class="kamar-grid">
            <?php if ($rooms !== []): ?>
                <?php foreach ($rooms as $room): ?>
                    <div class="kost-card">
                        <div class="kost-image">
                            <img src="<?php echo e(upload_asset($room['foto_kost'])); ?>" alt="Kost Image">
                        </div>
                        <div class="kost-body">
                            <span class="badge" style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:4px; font-size:12px;"><?php echo e($room['nama_kost']); ?></span>
                            <h3 style="margin:10px 0;">Kamar No. <?php echo e($room['nomor_kamar']); ?></h3>
                            <p style="color:#666; font-size:14px; margin-bottom:10px;">
                                <i class="fa-solid fa-map-pin"></i> <?php echo e($room['alamat']); ?>
                            </p>
                            <p style="color:#666; font-size:13px;">Fasilitas: <?php echo e($room['fasilitas']); ?></p>
                            <hr style="margin: 10px 0; border: 0; border-top: 1px solid #eee;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div style="font-size:16px; font-weight:bold; color:#1e3a8a;">
                                    Rp <?php echo number_format((float) $room['harga'], 0, ',', '.'); ?> <span style="font-size:12px; font-weight:normal; color:#888;">/bulan</span>
                                </div>
                            </div>
                            <a href="https://wa.me/6287748703029?text=<?php echo urlencode('Halo, saya tertarik dengan ' . $room['nama_kost'] . ' Kamar ' . $room['nomor_kamar']); ?>" target="_blank" class="btn-wa">
                                <i class="fa-brands fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; grid-column: 1/-1;">Data kost tidak ditemukan.</p>
            <?php endif; ?>
        </div>
    </main>
<?php
$content = ob_get_clean();
$title = 'Daftar Kamar - KosOnline';
$extraHead = <<<HTML
    <style>
        .kamar-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; padding: 20px 0; }
        .kost-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .kost-image img { width: 100%; height: 200px; object-fit: cover; }
        .kost-body { padding: 15px; }
        .btn-wa { display: block; background: #25D366; color: white; text-align: center; padding: 10px; text-decoration: none; border-radius: 5px; margin-top: 10px; font-weight: bold; }
        @media (max-width: 768px) {
            .kamar-grid { grid-template-columns: 1fr; padding: 0; }
        }
    </style>
HTML;
$extraScripts = <<<HTML
    <script>
        document.getElementById("searchBtn").addEventListener("click", function () {
            let keyword = document.getElementById("searchInput").value.toLowerCase();
            let cards = document.querySelectorAll(".kost-card");

            cards.forEach((card) => {
                let title = card.querySelector("h3").innerText.toLowerCase();
                card.style.display = title.includes(keyword) ? "block" : "none";
            });
        });
    </script>
HTML;
require base_path('app/Views/layouts/public.php');
