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
$availablePromos = $availablePromos ?? [];

$hasPromo = (int) ($room['diskon_persen'] ?? 0) > 0;
$roomPrice = (float) $room['harga'];
$roomDiscount = $hasPromo ? $roomPrice * ((int) $room['diskon_persen'] / 100) : 0;
$adminFee = 0.0;
$deposit = 0.0;
$subtotalAfterRoomDiscount = max(0, $roomPrice - $roomDiscount);
$estimatedTotal = max(0, $subtotalAfterRoomDiscount + $adminFee + $deposit);
$today = date('Y-m-d');
$firstPeriodEnd = date('Y-m-d', strtotime($today . ' +1 month -1 day'));
$formatRupiah = static fn(float $value): string => 'Rp ' . number_format($value, 0, ',', '.');
$methodLabels = [
    'manual_bca' => 'BCA Manual',
    'manual_bri' => 'BRI Manual',
    'manual_mandiri' => 'Mandiri Manual',
    'manual_cash' => 'Bayar Saat Survei',
];

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

        <form method="POST" action="<?php echo e(url('/rooms/payment?id=' . $room['id_kamar'])); ?>" class="row g-5" id="booking-payment-form">
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
                                <input type="date" name="tanggal_masuk" id="move-in-date" class="form-control py-2" value="<?php echo e($today); ?>" min="<?php echo e($today); ?>" required>
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
                                <input type="text" name="kode_promo" id="promo-code-input" class="form-control py-2 text-uppercase" placeholder="Contoh: KOSHEMAT / KOSNEW50">
                            </div>
                            <div class="col-md-4">
                                <div class="h-100 d-flex align-items-center text-muted small">Estimasi promo tampil sebelum konfirmasi.</div>
                            </div>
                            <?php if ($availablePromos !== []): ?>
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($availablePromos as $promo): ?>
                                            <?php
                                            $promoText = $promo['tipe_diskon'] === 'nominal'
                                                ? $formatRupiah((float) $promo['nilai_diskon'])
                                                : number_format((float) $promo['nilai_diskon'], 0, ',', '.') . '%';
                                            ?>
                                            <button type="button" class="promo-chip" data-promo-code="<?php echo e($promo['kode']); ?>">
                                                <strong><?php echo e($promo['kode']); ?></strong>
                                                <span><?php echo e($promoText); ?> off</span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="col-12">
                                <div id="promo-estimate-note" class="payment-note neutral">
                                    Masukkan kode promo untuk melihat estimasi potongan sebelum invoice dibuat.
                                </div>
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

                        <div class="payment-preview-card mb-3">
                            <div class="payment-preview-row">
                                <span>Harga kamar / bulan</span>
                                <strong><?php echo e($formatRupiah($roomPrice)); ?></strong>
                            </div>
                            <div class="payment-preview-row text-success">
                                <span>Diskon kamar <?php echo $hasPromo ? e((string) $room['diskon_persen']) . '%' : ''; ?></span>
                                <strong>- <?php echo e($formatRupiah($roomDiscount)); ?></strong>
                            </div>
                            <div class="payment-preview-row text-success">
                                <span id="promo-code-label">Kode promo</span>
                                <strong id="promo-discount-amount">- <?php echo e($formatRupiah(0)); ?></strong>
                            </div>
                            <div class="payment-preview-row">
                                <span>Deposit</span>
                                <strong><?php echo e($formatRupiah($deposit)); ?></strong>
                            </div>
                            <div class="payment-preview-row">
                                <span>Biaya admin</span>
                                <strong><?php echo e($formatRupiah($adminFee)); ?></strong>
                            </div>
                            <div class="payment-preview-row subtotal">
                                <span>Estimasi total</span>
                                <strong id="estimated-total"><?php echo e($formatRupiah($estimatedTotal)); ?></strong>
                            </div>
                        </div>

                        <div class="payment-note warning mb-3">
                            <strong>Belum final sampai kamu setuju.</strong> Setelah dikonfirmasi, kamar akan ditahan sementara dan invoice dibuat.
                        </div>
                        <div id="booking-form-note" class="payment-note danger mb-3 d-none"></div>

                        <button type="button" id="open-booking-confirmation" class="btn btn-primary btn-lg w-100 py-3 fw-bold" style="border-radius: 14px;">
                            <i class="fa-solid fa-shield-halved me-2"></i> Cek & Konfirmasi Invoice
                        </button>
                        <p class="text-muted small text-center mt-3 mb-0">Kamar akan ditahan sementara sampai admin mengonfirmasi atau membatalkan booking.</p>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="bookingConfirmModal" tabindex="-1" aria-labelledby="bookingConfirmModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content booking-confirm-modal">
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <span class="text-uppercase fw-bold text-primary small">Konfirmasi Booking</span>
                                <h4 class="modal-title fw-bold" id="bookingConfirmModalLabel">Setujui sebelum invoice dibuat</h4>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <div class="booking-confirm-room">
                                        <img src="<?php echo e(upload_asset($room['foto_kost'])); ?>" alt="Foto kamar">
                                        <h5><?php echo e($room['nama_kost']); ?></h5>
                                        <p>Kamar No. <?php echo e($room['nomor_kamar']); ?>, Lantai <?php echo e($room['lantai']); ?></p>
                                        <div><i class="fa-solid fa-calendar-days"></i> <span id="confirm-period-text"><?php echo e(date('d M Y', strtotime($today))); ?> - <?php echo e(date('d M Y', strtotime($firstPeriodEnd))); ?></span></div>
                                        <div><i class="fa-solid fa-building-columns"></i> <span id="confirm-method-text">Pilih metode pembayaran</span></div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="payment-preview-card mb-3">
                                        <div class="payment-preview-row"><span>Harga kamar / bulan</span><strong><?php echo e($formatRupiah($roomPrice)); ?></strong></div>
                                        <div class="payment-preview-row text-success"><span>Diskon kamar</span><strong>- <?php echo e($formatRupiah($roomDiscount)); ?></strong></div>
                                        <div class="payment-preview-row text-success"><span id="confirm-promo-label">Kode promo</span><strong id="confirm-promo-amount">- <?php echo e($formatRupiah(0)); ?></strong></div>
                                        <div class="payment-preview-row"><span>Deposit</span><strong><?php echo e($formatRupiah($deposit)); ?></strong></div>
                                        <div class="payment-preview-row"><span>Biaya admin</span><strong><?php echo e($formatRupiah($adminFee)); ?></strong></div>
                                        <div class="payment-preview-row subtotal"><span>Total estimasi</span><strong id="confirm-estimated-total"><?php echo e($formatRupiah($estimatedTotal)); ?></strong></div>
                                    </div>
                                    <div class="payment-note danger mb-3">
                                        Pastikan tanggal masuk, kode promo, dan metode pembayaran sudah benar. Setelah disetujui, invoice booking akan dibuat dan kamar ditahan sementara.
                                    </div>
                                    <label class="booking-confirm-check">
                                        <input type="checkbox" id="booking-agreement">
                                        <span>Saya sudah membaca rincian biaya dan setuju membuat invoice booking manual ini.</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="konfirmasi_booking" value="1" id="confirm-booking-submit" class="btn btn-primary fw-bold px-4" disabled>
                                <i class="fa-solid fa-file-invoice me-2"></i> Setuju, Buat Invoice
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php
$content = ob_get_clean();
$title = 'Booking Manual - ' . $room['nama_kost'] . ' Kamar ' . $room['nomor_kamar'];
$paymentConfig = [
    'roomPrice' => $roomPrice,
    'roomDiscount' => $roomDiscount,
    'adminFee' => $adminFee,
    'deposit' => $deposit,
    'subtotal' => $subtotalAfterRoomDiscount,
    'methodLabels' => $methodLabels,
    'promos' => array_map(static fn(array $promo): array => [
        'kode' => (string) $promo['kode'],
        'nama_promo' => (string) $promo['nama_promo'],
        'tipe_diskon' => (string) $promo['tipe_diskon'],
        'nilai_diskon' => (float) $promo['nilai_diskon'],
        'minimal_transaksi' => (float) $promo['minimal_transaksi'],
    ], $availablePromos),
];
$extraHead = <<<HTML
<style>
    .promo-chip {
        border: 1px solid var(--border-soft);
        background: var(--card-bg);
        color: var(--text-main);
        border-radius: 999px;
        padding: 8px 12px;
        display: inline-flex;
        gap: 8px;
        align-items: center;
        transition: 0.2s ease;
    }
    .promo-chip:hover {
        border-color: var(--accent-blue);
        color: var(--accent-blue);
        transform: translateY(-1px);
    }
    .promo-chip span {
        color: var(--text-muted);
        font-size: 0.82rem;
    }
    .payment-preview-card {
        border: 1px solid var(--border-soft);
        border-radius: 18px;
        overflow: hidden;
        background: var(--card-bg);
    }
    .payment-preview-row {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        padding: 13px 15px;
        border-bottom: 1px solid var(--border-soft);
    }
    .payment-preview-row:last-child { border-bottom: 0; }
    .payment-preview-row span { color: var(--text-muted); }
    .payment-preview-row strong { color: var(--text-main); text-align: right; }
    .payment-preview-row.subtotal {
        background: var(--accent-blue-soft);
        font-size: 1.08rem;
        font-weight: 800;
    }
    .payment-preview-row.subtotal strong { color: var(--accent-blue); }
    .payment-note {
        border-radius: 16px;
        padding: 12px 14px;
        font-size: 0.92rem;
        line-height: 1.55;
    }
    .payment-note.neutral {
        background: var(--accent-blue-soft);
        color: var(--text-main);
    }
    .payment-note.warning {
        background: #fff7ed;
        color: #9a3412;
        border: 1px solid #fed7aa;
    }
    .payment-note.danger {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    [data-theme="dark"] .payment-note.warning {
        background: rgba(234, 88, 12, 0.14);
        border-color: rgba(251, 146, 60, 0.28);
        color: #fdba74;
    }
    [data-theme="dark"] .payment-note.danger {
        background: rgba(220, 38, 38, 0.14);
        border-color: rgba(248, 113, 113, 0.28);
        color: #fca5a5;
    }
    .booking-confirm-modal {
        border: 0;
        border-radius: 26px;
        background: var(--card-bg);
        color: var(--text-main);
    }
    .booking-confirm-room {
        border-radius: 22px;
        padding: 14px;
        background: var(--bg-main);
        height: 100%;
    }
    .booking-confirm-room img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        border-radius: 18px;
        margin-bottom: 14px;
    }
    .booking-confirm-room div {
        color: var(--text-muted);
        font-weight: 700;
        margin-top: 10px;
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .booking-confirm-check {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        color: var(--text-main);
        font-weight: 700;
    }
    .booking-confirm-check input {
        width: 18px;
        height: 18px;
        margin-top: 3px;
        accent-color: var(--accent-blue);
    }
</style>
HTML;
$extraScripts = '<script>window.paymentSummaryConfig = ' . json_encode($paymentConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ';</script>' . <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
    const config = window.paymentSummaryConfig || {};
    const promoInput = document.getElementById('promo-code-input');
    const moveInInput = document.getElementById('move-in-date');
    const methodInput = document.getElementById('selected-payment-method');
    const openButton = document.getElementById('open-booking-confirmation');
    const agreement = document.getElementById('booking-agreement');
    const submitButton = document.getElementById('confirm-booking-submit');
    const modalElement = document.getElementById('bookingConfirmModal');
    const note = document.getElementById('promo-estimate-note');
    const formNote = document.getElementById('booking-form-note');

    const promoLabel = document.getElementById('promo-code-label');
    const promoAmount = document.getElementById('promo-discount-amount');
    const estimatedTotal = document.getElementById('estimated-total');
    const confirmPromoLabel = document.getElementById('confirm-promo-label');
    const confirmPromoAmount = document.getElementById('confirm-promo-amount');
    const confirmEstimatedTotal = document.getElementById('confirm-estimated-total');
    const confirmPeriodText = document.getElementById('confirm-period-text');
    const confirmMethodText = document.getElementById('confirm-method-text');

    function rupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function formatDate(date) {
        if (!date) return '-';
        return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
    }

    function addMonth(date) {
        const result = new Date(date.getTime());
        result.setMonth(result.getMonth() + 1);
        return result;
    }

    function getPromoResult() {
        const code = (promoInput?.value || '').trim().toUpperCase();
        const subtotal = Number(config.subtotal || 0);
        if (!code) {
            return { code: '', discount: 0, label: 'Kode promo', note: 'Masukkan kode promo untuk melihat estimasi potongan sebelum invoice dibuat.', state: 'neutral' };
        }

        const promo = (config.promos || []).find((item) => item.kode === code);
        if (!promo) {
            return { code, discount: 0, label: 'Kode promo (' + code + ')', note: 'Kode promo belum tersedia atau tidak aktif. Jika tetap dikirim, server akan menolak invoice.', state: 'danger' };
        }

        const minimum = Number(promo.minimal_transaksi || 0);
        if (subtotal < minimum) {
            return { code, discount: 0, label: 'Kode promo (' + code + ')', note: 'Minimal transaksi untuk kode ini ' + rupiah(minimum) + '.', state: 'danger' };
        }

        let discount = 0;
        if (promo.tipe_diskon === 'nominal') {
            discount = Math.min(subtotal, Number(promo.nilai_diskon || 0));
        } else {
            discount = Math.min(subtotal, subtotal * (Number(promo.nilai_diskon || 0) / 100));
        }

        return { code, discount, label: 'Kode promo (' + code + ')', note: 'Promo "' + promo.nama_promo + '" akan memotong estimasi ' + rupiah(discount) + '.', state: 'neutral' };
    }

    function updateSummary() {
        const promo = getPromoResult();
        const total = Math.max(0, Number(config.subtotal || 0) - promo.discount + Number(config.adminFee || 0) + Number(config.deposit || 0));

        promoLabel.textContent = promo.label;
        promoAmount.textContent = '- ' + rupiah(promo.discount);
        estimatedTotal.textContent = rupiah(total);
        confirmPromoLabel.textContent = promo.label;
        confirmPromoAmount.textContent = '- ' + rupiah(promo.discount);
        confirmEstimatedTotal.textContent = rupiah(total);

        note.textContent = promo.note;
        note.classList.remove('neutral', 'danger');
        note.classList.add(promo.state === 'danger' ? 'danger' : 'neutral');

        const moveIn = moveInInput?.value ? new Date(moveInInput.value + 'T00:00:00') : null;
        if (moveIn) {
            const end = addMonth(moveIn);
            end.setDate(end.getDate() - 1);
            confirmPeriodText.textContent = formatDate(moveIn) + ' - ' + formatDate(end);
        }

        confirmMethodText.textContent = config.methodLabels?.[methodInput?.value || ''] || 'Pilih metode pembayaran';
    }

    promoInput?.addEventListener('input', function () {
        promoInput.value = promoInput.value.toUpperCase();
        updateSummary();
    });
    moveInInput?.addEventListener('change', updateSummary);
    document.querySelectorAll('.payment-method-item').forEach(function (item) {
        item.addEventListener('click', function () {
            window.setTimeout(updateSummary, 0);
        });
    });
    document.querySelectorAll('.promo-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            if (!promoInput) return;
            promoInput.value = chip.getAttribute('data-promo-code') || '';
            promoInput.dispatchEvent(new Event('input'));
        });
    });

    agreement?.addEventListener('change', function () {
        submitButton.disabled = !agreement.checked;
    });

    openButton?.addEventListener('click', function () {
        updateSummary();
        if (!moveInInput?.value || !methodInput?.value) {
            formNote.textContent = 'Pilih tanggal mulai ngekos dan metode pembayaran dulu ya.';
            formNote.classList.remove('d-none');
            return;
        }

        formNote.classList.add('d-none');
        agreement.checked = false;
        submitButton.disabled = true;
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    });

    updateSummary();
});
</script>
HTML;
require base_path('app/Views/layouts/public.php');
?>
