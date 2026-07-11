<?php
$invoice = array_merge([
    'invoice_no' => '-',
    'kode_booking' => '-',
    'status_verifikasi' => 'Menunggu',
    'status_sewa' => 'Menunggu Pembayaran',
    'nama_penyewa' => '-',
    'email_penyewa' => '-',
    'no_hp_penyewa' => '-',
    'nama_kost' => 'KosOnline',
    'nomor_kamar' => '-',
    'lantai' => '-',
    'alamat' => '',
    'foto_kost' => '',
    'periode_mulai' => null,
    'periode_selesai' => null,
    'harga_kamar' => 0,
    'diskon_kamar' => 0,
    'kode_promo' => null,
    'diskon_promo' => 0,
    'biaya_admin' => 0,
    'deposit' => 0,
    'total_bayar' => 0,
    'metode_bayar' => '-',
], $invoice ?? []);

$methodLabels = [
    'manual_bca' => 'Transfer Manual BCA',
    'manual_bri' => 'Transfer Manual BRI',
    'manual_mandiri' => 'Transfer Manual Mandiri',
    'manual_cash' => 'Bayar Saat Survei',
];
$methodLabel = $methodLabels[$invoice['metode_bayar']] ?? (string) $invoice['metode_bayar'];
$statusClass = $invoice['status_verifikasi'] === 'Lunas' ? 'bg-success' : ($invoice['status_verifikasi'] === 'Ditolak' ? 'bg-danger' : 'bg-warning text-dark');
$periodStart = !empty($invoice['periode_mulai']) ? date('d M Y', strtotime((string) $invoice['periode_mulai'])) : '-';
$periodEnd = !empty($invoice['periode_selesai']) ? date('d M Y', strtotime((string) $invoice['periode_selesai'])) : '-';

$bankAccounts = $bankAccounts ?? [];
$bankAccount = $bankAccounts[$invoice['metode_bayar']] ?? null;
$isCashMethod = $invoice['metode_bayar'] === 'manual_cash';
$isLunas = $invoice['status_verifikasi'] === 'Lunas';
$isDitolak = $invoice['status_verifikasi'] === 'Ditolak';
$hasProof = !empty($invoice['bukti_bayar']);
$isPending = ($invoice['status_sewa'] ?? '') === 'Menunggu Pembayaran';
$isBookingActive = !in_array((string) ($invoice['status_sewa'] ?? ''), ['Dibatalkan', 'Berhenti'], true);
$bankLogo = null;
if ($bankAccount !== null && !empty($bankAccount['logo']) && is_file(base_path('public/assets/images/banks/' . $bankAccount['logo']))) {
    $bankLogo = asset('images/banks/' . $bankAccount['logo']);
}

ob_start();
?>

<section class="py-5" style="background: var(--bg-main);">
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 24px; background: var(--card-bg);">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                            <div>
                                <span class="text-uppercase fw-bold text-primary small">Invoice Booking Kos</span>
                                <h1 class="fw-bold mt-2 mb-1" style="color: var(--text-main);"><?php echo e($invoice['invoice_no']); ?></h1>
                                <p class="text-muted mb-0">Kode booking: <?php echo e($invoice['kode_booking']); ?></p>
                            </div>
                            <span class="badge <?php echo e($statusClass); ?> px-3 py-2"><?php echo e($invoice['status_verifikasi']); ?></span>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 rounded-4" style="background: var(--bg-main);">
                                    <small class="text-muted fw-semibold">Calon Penyewa</small>
                                    <h5 class="fw-bold mb-1"><?php echo e($invoice['nama_penyewa']); ?></h5>
                                    <p class="text-muted mb-0"><?php echo e($invoice['email_penyewa']); ?> &bull; <?php echo e($invoice['no_hp_penyewa']); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 rounded-4" style="background: var(--bg-main);">
                                    <small class="text-muted fw-semibold">Periode Sewa Pertama</small>
                                    <h5 class="fw-bold mb-1"><?php echo e($periodStart); ?> - <?php echo e($periodEnd); ?></h5>
                                    <p class="text-muted mb-0">Jatuh tempo bulanan mengikuti tanggal mulai ngekos.</p>
                                </div>
                            </div>
                        </div>

                        <h4 class="fw-bold mb-3" style="color: var(--text-main);">Rincian Biaya</h4>
                        <div class="invoice-breakdown">
                            <div><span>Harga kamar / bulan</span><strong>Rp <?php echo number_format((float) $invoice['harga_kamar'], 0, ',', '.'); ?></strong></div>
                            <div><span>Diskon kamar</span><strong>- Rp <?php echo number_format((float) $invoice['diskon_kamar'], 0, ',', '.'); ?></strong></div>
                            <div><span>Kode promo <?php echo !empty($invoice['kode_promo']) ? '(' . e($invoice['kode_promo']) . ')' : ''; ?></span><strong>- Rp <?php echo number_format((float) $invoice['diskon_promo'], 0, ',', '.'); ?></strong></div>
                            <div><span>Deposit</span><strong>Rp <?php echo number_format((float) $invoice['deposit'], 0, ',', '.'); ?></strong></div>
                            <div><span>Biaya admin</span><strong>Rp <?php echo number_format((float) $invoice['biaya_admin'], 0, ',', '.'); ?></strong></div>
                            <div class="total"><span>Total dibayar</span><strong>Rp <?php echo number_format((float) $invoice['total_bayar'], 0, ',', '.'); ?></strong></div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4" style="border-radius: 24px; background: var(--card-bg);">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-3" style="color: var(--text-main);">Instruksi Pembayaran</h4>

                        <?php if ($isCashMethod): ?>
                            <ol class="text-muted mb-0" style="line-height: 1.9;">
                                <li>Simpan nomor invoice <strong><?php echo e($invoice['invoice_no']); ?></strong>.</li>
                                <li>Datang ke lokasi kost sesuai jadwal survei untuk membayar tunai.</li>
                                <li>Admin akan menandai invoice sebagai <strong>Lunas</strong> setelah pembayaran diterima.</li>
                            </ol>
                        <?php else: ?>
                            <?php if ($bankAccount !== null && !empty($bankAccount['no_rekening'])): ?>
                                <div class="p-3 rounded-4 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-3" style="background: var(--bg-main); border: 1px dashed var(--accent-blue);">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if ($bankLogo !== null): ?>
                                            <img src="<?php echo e($bankLogo); ?>" alt="Logo <?php echo e($bankAccount['bank']); ?>" style="height: 40px; width: auto; object-fit: contain;">
                                        <?php endif; ?>
                                        <div>
                                            <small class="text-muted fw-semibold text-uppercase">Transfer ke <?php echo e($bankAccount['bank']); ?></small>
                                            <div class="fs-4 fw-bold" style="color: var(--accent-blue); letter-spacing: 1px;"><?php echo e($bankAccount['no_rekening']); ?></div>
                                            <p class="text-muted mb-0">a.n. <?php echo e($bankAccount['atas_nama']); ?></p>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold" onclick="navigator.clipboard&&navigator.clipboard.writeText('<?php echo e($bankAccount['no_rekening']); ?>');this.innerText='Tersalin!';setTimeout(()=>this.innerText='Salin Nomor',1500);">Salin Nomor</button>
                                </div>
                            <?php endif; ?>
                            <ol class="text-muted mb-0" style="line-height: 1.9;">
                                <li>Transfer sesuai <strong>Total Dibayar</strong> di atas ke rekening <?php echo e($methodLabel); ?> tersebut.</li>
                                <li>Screenshot/foto bukti transfer, lalu upload lewat form di bawah ini.</li>
                                <li>Admin memverifikasi bukti transfer lalu menandai invoice sebagai <strong>Lunas</strong>.</li>
                            </ol>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!$isCashMethod): ?>
                <div class="card border-0 shadow-sm" style="border-radius: 24px; background: var(--card-bg);">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-3" style="color: var(--text-main);">Bukti Pembayaran</h4>

                        <?php if ($isDitolak): ?>
                            <div class="alert alert-danger">Bukti transfer sebelumnya <strong>ditolak</strong>. Silakan upload ulang bukti yang benar.</div>
                        <?php endif; ?>

                        <?php if ($hasProof): ?>
                            <p class="text-muted mb-2">Bukti yang sudah diupload:</p>
                            <a href="<?php echo e(upload_asset($invoice['bukti_bayar'])); ?>" target="_blank" rel="noopener">
                                <img src="<?php echo e(upload_asset($invoice['bukti_bayar'])); ?>" alt="Bukti transfer" class="img-fluid rounded-4 mb-3" style="max-height: 320px; border: 1px solid var(--border-soft);">
                            </a>
                        <?php endif; ?>

                        <?php if ($isLunas): ?>
                            <div class="alert alert-success mb-0">Pembayaran sudah <strong>diverifikasi lunas</strong>. Terima kasih!</div>
                        <?php elseif ($isBookingActive): ?>
                            <form method="POST" action="<?php echo e(url('/rooms/invoice/upload-proof')); ?>" enctype="multipart/form-data" class="mt-2">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_pembayaran" value="<?php echo e($invoice['id_pembayaran'] ?? ''); ?>">
                                <div class="mb-3">
                                    <label for="bukti_bayar" class="form-label fw-semibold">
                                        <?php echo $hasProof ? 'Upload ulang bukti transfer' : 'Upload bukti transfer'; ?>
                                    </label>
                                    <input type="file" name="bukti_bayar" id="bukti_bayar" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                                    <div class="form-text">Format JPG, PNG, atau WebP. Maksimal 5 MB.</div>
                                </div>
                                <button type="submit" class="btn btn-primary fw-bold py-2 px-4">
                                    <i class="fa-solid fa-upload me-2"></i> <?php echo $hasProof ? 'Kirim Ulang Bukti' : 'Kirim Bukti Transfer'; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="text-muted mb-0">Booking ini sudah tidak aktif, upload bukti dinonaktifkan.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm position-sticky" style="top: 100px; border-radius: 24px; background: var(--card-bg); overflow: hidden;">
                    <img src="<?php echo e(upload_asset($invoice['foto_kost'])); ?>" alt="Foto Kamar" style="height: 210px; object-fit: cover;">
                    <div class="card-body p-4">
                        <span class="badge mb-2" style="background: var(--accent-blue-soft); color: var(--accent-blue);"><?php echo e($invoice['nama_kost']); ?></span>
                        <h4 class="fw-bold mb-1">Kamar No. <?php echo e($invoice['nomor_kamar']); ?></h4>
                        <p class="text-muted small">Lantai <?php echo e($invoice['lantai']); ?> &bull; <?php echo e($invoice['alamat']); ?></p>
                        <form method="POST" action="<?php echo e(url('/rooms/invoice/confirm-chat')); ?>">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id_pembayaran" value="<?php echo e($invoice['id_pembayaran'] ?? ''); ?>">
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-3">
                                <i class="fa-regular fa-comments me-2"></i> Konfirmasi ke Admin
                            </button>
                        </form>

                        <div class="invoice-quick-links">
                            <a href="https://wa.me/6287748703029?text=<?php echo e(rawurlencode('Halo Admin KosOnline, saya ingin konfirmasi invoice ' . $invoice['invoice_no'])); ?>" target="_blank" rel="noopener" class="invoice-quick-link is-wa" title="Konfirmasi via WhatsApp">
                                <i class="fa-brands fa-whatsapp"></i> WhatsApp
                            </a>
                            <span class="invoice-quick-sep"></span>
                            <a href="<?php echo e(url('/rooms/detail?id=' . $invoice['id_kamar'])); ?>" class="invoice-quick-link" title="Lihat detail kamar">
                                <i class="fa-regular fa-eye"></i> Detail Kamar
                            </a>
                        </div>

                        <?php if ($isPending && !$isLunas): ?>
                            <div class="invoice-cancel">
                                <form method="POST" action="<?php echo e(url('/rooms/invoice/cancel')); ?>" data-confirm="Yakin batalkan booking ini? Kamar akan dilepas kembali dan tidak bisa diurungkan." data-confirm-ok="Ya, Batalkan">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id_pembayaran" value="<?php echo e($invoice['id_pembayaran'] ?? ''); ?>">
                                    <button type="submit" class="invoice-cancel-link">
                                        <i class="fa-solid fa-ban"></i> Batalkan Booking
                                    </button>
                                </form>
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
$title = 'Invoice ' . $invoice['invoice_no'] . ' - KosOnline';
$extraHead = <<<HTML
<style>
    .invoice-breakdown {
        border: 1px solid var(--border-soft);
        border-radius: 18px;
        overflow: hidden;
    }
    .invoice-breakdown div {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-soft);
    }
    .invoice-breakdown div:last-child { border-bottom: 0; }
    .invoice-breakdown span { color: var(--text-muted); }
    .invoice-breakdown strong { color: var(--text-main); }
    .invoice-breakdown .total {
        background: var(--accent-blue-soft);
        font-size: 1.15rem;
        font-weight: 800;
    }
    .invoice-breakdown .total strong { color: var(--accent-blue); }

    .invoice-quick-links {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        margin-top: 16px;
    }
    .invoice-quick-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .invoice-quick-link:hover { color: var(--accent-blue); }
    .invoice-quick-link.is-wa:hover { color: #25D366; }
    .invoice-quick-link i { font-size: 1.05rem; }
    .invoice-quick-sep {
        width: 1px;
        height: 16px;
        background: var(--border-soft);
    }
    .invoice-cancel {
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid var(--border-soft);
        text-align: center;
    }
    .invoice-cancel-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: none;
        border: 0;
        padding: 0;
        cursor: pointer;
        color: #dc2626;
        font-weight: 700;
        font-size: 0.9rem;
    }
    .invoice-cancel-link:hover { text-decoration: underline; }
</style>
HTML;
require base_path('app/Views/layouts/public.php');
?>
