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

ob_start();
?>

<section class="py-5" style="background: var(--bg-main);">
    <div class="container py-4">
        <div class="row g-4 align-items-start">
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

                <div class="card border-0 shadow-sm" style="border-radius: 24px; background: var(--card-bg);">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-3" style="color: var(--text-main);">Instruksi Pembayaran Manual</h4>
                        <ol class="text-muted mb-0" style="line-height: 1.9;">
                            <li>Simpan nomor invoice <strong><?php echo e($invoice['invoice_no']); ?></strong>.</li>
                            <li>Hubungi admin WhatsApp dan kirim nomor invoice ini.</li>
                            <li>Admin akan mengirim instruksi <?php echo e($methodLabel); ?>.</li>
                            <li>Setelah pembayaran diterima, admin menandai invoice sebagai <strong>Lunas</strong>.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm position-sticky" style="top: 100px; border-radius: 24px; background: var(--card-bg); overflow: hidden;">
                    <img src="<?php echo e(upload_asset($invoice['foto_kost'])); ?>" alt="Foto Kamar" style="height: 210px; object-fit: cover;">
                    <div class="card-body p-4">
                        <span class="badge mb-2" style="background: var(--accent-blue-soft); color: var(--accent-blue);"><?php echo e($invoice['nama_kost']); ?></span>
                        <h4 class="fw-bold mb-1">Kamar No. <?php echo e($invoice['nomor_kamar']); ?></h4>
                        <p class="text-muted small">Lantai <?php echo e($invoice['lantai']); ?> &bull; <?php echo e($invoice['alamat']); ?></p>
                        <a href="https://wa.me/6287748703029?text=<?php echo e(rawurlencode('Halo Admin KosOnline, saya ingin konfirmasi invoice ' . $invoice['invoice_no'])); ?>" target="_blank" rel="noopener" class="btn btn-success w-100 fw-bold py-3">
                            <i class="fa-brands fa-whatsapp me-2"></i> Konfirmasi ke Admin
                        </a>
                        <a href="<?php echo e(url('/rooms/detail?id=' . $invoice['id_kamar'])); ?>" class="btn btn-outline-primary w-100 fw-bold py-3 mt-2">
                            Lihat Detail Kamar
                        </a>
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
</style>
HTML;
require base_path('app/Views/layouts/public.php');
?>
