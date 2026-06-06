<?php
$room = array_merge([
    'id_kamar' => 0,
    'nomor_kamar' => '-',
    'lantai' => '-',
    'harga' => 0,
    'diskon_persen' => 0,
    'nama_kost' => 'KosOnline',
    'foto_kost' => '',
], $room ?? []);
$user = array_merge([
    'nama_lengkap' => '-',
    'username' => '-',
    'email' => '-',
    'no_hp' => '-',
    'foto_profil' => 'default.jpg',
], $user ?? []);

$hasPromo = (int) ($room['diskon_persen'] ?? 0) > 0;
$roomPrice = (float) $room['harga'];
$roomDiscount = $hasPromo ? $roomPrice * ((int) $room['diskon_persen'] / 100) : 0;
$estimatedTotal = max(0, $roomPrice - $roomDiscount);
$today = date('Y-m-d');
$firstPeriodEnd = date('Y-m-d', strtotime($today . ' +1 month -1 day'));

ob_start();
?>

<section class="py-5" style="background: var(--bg-main);">
    <div class="container py-4">
        <div class="mb-4">
            <a href="<?php echo e(url('/rooms/detail?id=' . $room['id_kamar'])); ?>" class="text-decoration-none fw-bold" style="color: var(--accent-blue);">
                <i class="fa-solid fa-arrow-left me-2"></i> Kembali ke Detail Kamar
            </a>
        </div>

        <div class="text-center mb-5">
            <span class="text-uppercase fw-bold text-primary mb-2 d-block" style="letter-spacing: 1px; font-size: 0.85rem;">
                <i class="fa-solid fa-file-invoice-dollar me-2"></i> Booking Manual
            </span>
            <h1 class="fw-bold display-5 mb-2" style="color: var(--text-main); letter-spacing: -1px;">Pilih Tanggal & Buat Invoice</h1>
            <p class="text-muted">Belum terhubung payment gateway. Invoice akan dicatat sebagai pembayaran manual dan menunggu verifikasi admin.</p>
        </div>

        <form method="POST" action="<?php echo e(url('/rooms/payment?id=' . $room['id_kamar'])); ?>" class="row g-5">
            <?php echo csrf_field(); ?>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 22px; background: var(--card-bg);">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4" style="color: var(--text-main);"><i class="fa-solid fa-user-check me-2 text-primary"></i> Data Akun Penyewa</h5>
                        <div class="d-flex gap-3 align-items-center mb-4 p-3 rounded-4" style="background: var(--accent-blue-soft);">
                            <?php
                            $avatarSource = !empty($user['foto_profil']) && $user['foto_profil'] !== 'default.jpg'
                                ? upload_asset((string) $user['foto_profil'])
                                : site_image('images.jpg');
                            ?>
                            <img src="<?php echo e($avatarSource); ?>" alt="Foto profil" style="width: 58px; height: 58px; border-radius: 18px; object-fit: cover;">
                            <div>
                                <strong class="d-block" style="color: var(--text-main);"><?php echo e($user['nama_lengkap']); ?></strong>
                                <span class="text-muted small">@<?php echo e($user['username']); ?></span>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Email</label>
                                <div class="form-control py-2 bg-transparent"><?php echo e($user['email']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">No. Handphone</label>
                                <div class="form-control py-2 bg-transparent"><?php echo e($user['no_hp']); ?></div>
                            </div>
                            <div class="col-12">
                                <p class="text-muted small mb-0">Invoice akan dibuat atas nama akun ini. Ubah data kontak dari dashboard user kalau ada yang perlu diperbarui.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4" style="border-radius: 22px; background: var(--card-bg);">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4" style="color: var(--text-main);"><i class="fa-solid fa-calendar-days me-2 text-primary"></i> Periode Sewa Pertama</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Mulai ngekos tanggal</label>
                                <input type="date" name="tanggal_masuk" class="form-control py-2" value="<?php echo e($today); ?>" min="<?php echo e($today); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Durasi invoice pertama</label>
                                <input type="text" class="form-control py-2" value="1 bulan awal" disabled>
                            </div>
                        </div>
                        <div class="mt-3 p-3 rounded-3" style="background: var(--accent-blue-soft); color: var(--text-main);">
                            <strong>Contoh periode:</strong> jika mulai hari ini, tagihan pertama berlaku <?php echo e(date('d M Y', strtotime($today))); ?> sampai <?php echo e(date('d M Y', strtotime($firstPeriodEnd))); ?>.
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4" style="border-radius: 22px; background: var(--card-bg);">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4" style="color: var(--text-main);"><i class="fa-solid fa-ticket me-2 text-primary"></i> Kode Promo</h5>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <input type="text" name="kode_promo" class="form-control py-2 text-uppercase" placeholder="Contoh: KOSHEMAT / KOSNEW50">
                            </div>
                            <div class="col-md-4">
                                <div class="h-100 d-flex align-items-center text-muted small">Promo dihitung saat invoice dibuat.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4" style="border-radius: 22px; background: var(--card-bg);">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4" style="color: var(--text-main);"><i class="fa-solid fa-building-columns me-2 text-primary"></i> Metode Pembayaran Manual</h5>
                        <input type="hidden" name="metode_bayar" id="selected-payment-method" value="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="payment-method-item" data-method="manual_bca">
                                    <i class="fa-solid fa-building-columns text-primary d-block mb-2" style="font-size: 1.6rem;"></i>
                                    <strong>BCA Manual</strong>
                                    <small class="d-block text-muted">Admin kirim nomor rekening.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-method-item" data-method="manual_bri">
                                    <i class="fa-solid fa-building-columns text-primary d-block mb-2" style="font-size: 1.6rem;"></i>
                                    <strong>BRI Manual</strong>
                                    <small class="d-block text-muted">Transfer dan konfirmasi admin.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-method-item" data-method="manual_mandiri">
                                    <i class="fa-solid fa-building-columns text-primary d-block mb-2" style="font-size: 1.6rem;"></i>
                                    <strong>Mandiri Manual</strong>
                                    <small class="d-block text-muted">Cocok untuk transfer bank.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-method-item" data-method="manual_cash">
                                    <i class="fa-solid fa-hand-holding-dollar text-success d-block mb-2" style="font-size: 1.6rem;"></i>
                                    <strong>Bayar Saat Survei</strong>
                                    <small class="d-block text-muted">Admin verifikasi manual.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm position-sticky" style="top: 100px; border-radius: 22px; background: var(--card-bg); overflow: hidden;">
                    <img src="<?php echo e(upload_asset($room['foto_kost'])); ?>" alt="Foto Kamar" style="height: 220px; object-fit: cover;">
                    <div class="card-body p-4">
                        <span class="badge mb-2" style="background: var(--accent-blue-soft); color: var(--accent-blue);"><?php echo e($room['nama_kost']); ?></span>
                        <h4 class="fw-bold mb-1" style="color: var(--text-main);">Kamar No. <?php echo e($room['nomor_kamar']); ?></h4>
                        <p class="text-muted small mb-4">Lantai <?php echo e($room['lantai']); ?> &bull; Tagihan bulanan</p>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Harga kamar</span>
                            <strong>Rp <?php echo number_format($roomPrice, 0, ',', '.'); ?></strong>
                        </div>
                        <?php if ($hasPromo): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Diskon kamar <?php echo e((string) $room['diskon_persen']); ?>%</span>
                                <strong>- Rp <?php echo number_format($roomDiscount, 0, ',', '.'); ?></strong>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Kode promo</span>
                            <strong>Dihitung otomatis</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Deposit</span>
                            <strong>Rp 0</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Biaya admin</span>
                            <strong>Rp 0</strong>
                        </div>
                        <hr style="border-color: var(--border-soft);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold fs-5" style="color: var(--text-main);">Estimasi awal</span>
                            <strong class="fs-4" style="color: var(--accent-blue);">Rp <?php echo number_format($estimatedTotal, 0, ',', '.'); ?></strong>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold" style="border-radius: 14px;">
                            <i class="fa-solid fa-file-invoice me-2"></i> Buat Invoice Manual
                        </button>
                        <p class="text-muted small text-center mt-3 mb-0">Kamar akan ditahan sementara sampai admin mengonfirmasi atau membatalkan booking.</p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php
$content = ob_get_clean();
$title = 'Booking Manual - ' . $room['nama_kost'] . ' Kamar ' . $room['nomor_kamar'];
require base_path('app/Views/layouts/public.php');
?>
