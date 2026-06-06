<?php
$successMessage = $successMessage ?? null;
$errorMessage = $errorMessage ?? null;
$quickTopics = [
    [
        'icon' => 'fa-calendar-check',
        'title' => 'Jadwalkan Survei',
        'text' => 'Cocok kalau user sudah punya kandidat kamar dan ingin cek lokasi langsung.',
        'message' => 'Halo Admin KosOnline, saya ingin jadwalkan survei kamar kos.',
    ],
    [
        'icon' => 'fa-tags',
        'title' => 'Tanya Promo',
        'text' => 'Bantu calon penyewa tahu promo terbaru tanpa harus mencari sendiri.',
        'message' => 'Halo Admin KosOnline, saya ingin tanya promo kamar kos yang sedang tersedia.',
    ],
    [
        'icon' => 'fa-route',
        'title' => 'Rekomendasi Cabang',
        'text' => 'Untuk user yang masih bingung pilih cabang terdekat dari kampus atau kantor.',
        'message' => 'Halo Admin KosOnline, saya butuh rekomendasi cabang kos yang paling sesuai.',
    ],
];

$faqs = [
    ['question' => 'Apakah bisa survei dulu sebelum pesan?', 'answer' => 'Bisa. Chat admin untuk menyesuaikan jadwal survei dan memastikan kamar masih tersedia.'],
    ['question' => 'Apakah harga di website sudah final?', 'answer' => 'Harga mengikuti data terbaru di sistem. Kalau ada promo, harga setelah diskon akan ditampilkan di detail kamar.'],
    ['question' => 'Respon admin biasanya berapa lama?', 'answer' => 'Untuk WhatsApp biasanya lebih cepat. Form kontak tetap tersimpan di dashboard admin sebagai arsip pesan.'],
];

$socialMedia = [
    [
        'class' => 'whatsapp',
        'icon' => 'fa-brands fa-whatsapp',
        'name' => 'WhatsApp',
        'handle' => '+62 877-4870-3029',
        'url' => 'https://wa.me/6287748703029',
    ],
    [
        'class' => 'instagram',
        'icon' => 'fa-brands fa-instagram',
        'name' => 'Instagram',
        'handle' => '@kosonline',
        'url' => '#',
    ],
    [
        'class' => 'facebook',
        'icon' => 'fa-brands fa-facebook-f',
        'name' => 'Facebook',
        'handle' => 'KosOnline Cirebon',
        'url' => '#',
    ],
    [
        'class' => 'tiktok',
        'icon' => 'fa-brands fa-tiktok',
        'name' => 'TikTok',
        'handle' => '@kosonline',
        'url' => '#',
    ],
];

ob_start();
?>

<main class="public-page contact-page">
    <section class="public-page-hero contact-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="section-eyebrow">Hubungi Kami</span>
                    <h1>Butuh kamar cepat? Mulai dari chat yang tepat.</h1>
                    <p>
                        KosOnline dibuat supaya calon penyewa tidak muter-muter tanya informasi dasar. Pilih kebutuhanmu,
                        lalu admin bisa bantu dari rekomendasi cabang sampai jadwal survei.
                    </p>
                    <div class="hero-actions">
                        <a href="https://wa.me/6287748703029?text=<?php echo e(rawurlencode('Halo Admin KosOnline, saya ingin tanya kamar kos.')); ?>" class="btn btn-light btn-lg fw-bold" target="_blank" rel="noopener">
                            <i class="fa-brands fa-whatsapp me-2"></i> Chat WhatsApp
                        </a>
                        <a href="#contact-form" class="btn btn-outline-light btn-lg fw-bold">
                            <i class="fa-solid fa-paper-plane me-2"></i> Kirim Pesan
                        </a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="contact-orbit-card">
                        <div class="contact-status">
                            <span></span>
                            Admin siap bantu
                        </div>
                        <div class="contact-orbit-main">
                            <i class="fa-brands fa-whatsapp"></i>
                            <strong>Chat cepat</strong>
                        </div>
                        <div class="contact-orbit-item item-ig"><i class="fa-brands fa-instagram"></i></div>
                        <div class="contact-orbit-item item-fb"><i class="fa-brands fa-facebook-f"></i></div>
                        <div class="contact-orbit-item item-tt"><i class="fa-brands fa-tiktok"></i></div>
                        <div class="contact-number-card">
                            <span>Nomor admin</span>
                            <h2>+62 877-4870-3029</h2>
                            <p>Prioritaskan WhatsApp untuk pertanyaan cepat, survei, dan ketersediaan kamar hari ini.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="public-section contact-channel-section">
        <div class="container">
            <div class="contact-channel-grid">
                <article class="contact-channel-card">
                    <i class="fa-brands fa-whatsapp"></i>
                    <h3>WhatsApp</h3>
                    <p>Jalur tercepat untuk tanya kamar, harga, promo, dan jadwal survei.</p>
                    <a href="https://wa.me/6287748703029" target="_blank" rel="noopener">Chat sekarang</a>
                </article>
                <article class="contact-channel-card">
                    <i class="fa-solid fa-location-dot"></i>
                    <h3>Alamat</h3>
                    <p>Desa Jadimulya, Kec. Gunung Jati, Kab. Cirebon, Jawa Barat.</p>
                    <a href="<?php echo e(url('/map')); ?>">Lihat peta</a>
                </article>
                <article class="contact-channel-card">
                    <i class="fa-solid fa-envelope"></i>
                    <h3>Email</h3>
                    <p>Untuk pesan yang lebih formal atau arsip komunikasi penyewa.</p>
                    <a href="mailto:info@kosonline.com">info@kosonline.com</a>
                </article>
            </div>
        </div>
    </section>

    <section class="public-section pt-0 social-section">
        <div class="container">
            <div class="section-heading split-heading">
                <div>
                    <span class="section-eyebrow">Media Sosial</span>
                    <h2>Ikuti update kos dari channel favoritmu</h2>
                    <p>Konten tiap platform bisa dibuat beda: promo di WhatsApp, foto kamar di Instagram, update cabang di Facebook, dan video pendek di TikTok.</p>
                </div>
            </div>
            <div class="social-brand-grid">
                <?php foreach ($socialMedia as $social): ?>
                    <a href="<?php echo e($social['url']); ?>" class="social-brand-card <?php echo e($social['class']); ?>" target="_blank" rel="noopener">
                        <span><i class="<?php echo e($social['icon']); ?>"></i></span>
                        <div>
                            <strong><?php echo e($social['name']); ?></strong>
                            <small><?php echo e($social['handle']); ?></small>
                        </div>
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="public-section pt-0">
        <div class="container">
            <div class="section-heading text-center">
                <span class="section-eyebrow">Pilih Topik</span>
                <h2>Biar chat pertama langsung jelas</h2>
                <p>Pilihan ini membantu user yang masih bingung harus mulai bertanya dari mana.</p>
            </div>
            <div class="quick-topic-grid">
                <?php foreach ($quickTopics as $topic): ?>
                    <a href="https://wa.me/6287748703029?text=<?php echo e(rawurlencode($topic['message'])); ?>" class="quick-topic-card" target="_blank" rel="noopener">
                        <div><i class="fa-solid <?php echo e($topic['icon']); ?>"></i></div>
                        <h3><?php echo e($topic['title']); ?></h3>
                        <p><?php echo e($topic['text']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="contact-form" class="public-section contact-form-section">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <span class="section-eyebrow">Form Pesan</span>
                    <h2>Kirim pesan untuk dicatat admin.</h2>
                    <p class="section-lead">
                        Form ini cocok untuk pertanyaan panjang. Pesan akan masuk ke dashboard admin, jadi tidak hilang di tengah chat.
                    </p>
                    <div class="contact-note-card">
                        <i class="fa-solid fa-circle-info"></i>
                        <p>Untuk kebutuhan mendesak seperti booking hari ini, WhatsApp tetap lebih disarankan.</p>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="public-form-card">
                        <?php if (!empty($successMessage)): ?>
                            <div class="alert alert-success"><?php echo e($successMessage); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($errorMessage)): ?>
                            <div class="alert alert-danger"><?php echo e($errorMessage); ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control" placeholder="Nama kamu" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Aktif</label>
                                    <input type="email" name="email" class="form-control" placeholder="email@example.com" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Isi Pesan</label>
                                    <textarea name="pesan" rows="6" class="form-control" placeholder="Tulis pertanyaan, kebutuhan kamar, atau jadwal survei..." required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="kirim_pesan" class="btn btn-primary w-100 fw-bold">
                                        <i class="fa-solid fa-paper-plane me-2"></i> Kirim Pesan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="public-section pt-0">
        <div class="container">
            <div class="faq-strip">
                <?php foreach ($faqs as $faq): ?>
                    <article>
                        <h3><?php echo e($faq['question']); ?></h3>
                        <p><?php echo e($faq['answer']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php
$content = ob_get_clean();
$title = 'Hubungi Kami - KosOnline';
require base_path('app/Views/layouts/public.php');
?>
