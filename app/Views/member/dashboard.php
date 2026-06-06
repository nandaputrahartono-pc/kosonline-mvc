<?php
$user = array_merge([
    'nama_lengkap' => 'User',
    'username' => 'user',
    'email' => '-',
    'no_hp' => '-',
    'foto_profil' => 'default.jpg',
], $user ?? []);
$rentals = $rentals ?? [];
$paymentHistory = $paymentHistory ?? [];
$latestInvoice = $latestInvoice ?? null;
$summary = array_merge([
    'nama_kost' => '-',
    'kamar_info' => 'Belum Sewa',
    'status_bayar' => 'Tidak Ada Tagihan',
    'class_badge' => 'success',
    'jatuh_tempo' => '-',
    'total_pesanan' => 0,
    'total_invoice' => 0,
    'tagihan_terdekat' => 0,
], $summary ?? []);

$formatRupiah = static fn(float $value): string => 'Rp ' . number_format($value, 0, ',', '.');
$formatDate = static function (?string $date): string {
    if ($date === null || $date === '' || $date === '0000-00-00' || $date === '-') {
        return '-';
    }

    return date('d M Y', strtotime($date));
};
$statusClass = static function (?string $status): string {
    return match ($status) {
        'Lunas', 'Aktif' => 'success',
        'Ditolak', 'Dibatalkan', 'Berhenti' => 'danger',
        default => 'warning',
    };
};
$avatarSource = !empty($user['foto_profil']) && $user['foto_profil'] !== 'default.jpg'
    ? upload_asset((string) $user['foto_profil'])
    : site_image('images.jpg');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - KosOnline</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/member.css')); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php if ($successMessage !== null || $errorMessage !== null): ?>
        <div class="member-flash-stack">
            <?php if ($successMessage !== null): ?>
                <div class="member-flash success"><i class="fa-solid fa-circle-check"></i><?php echo e($successMessage); ?></div>
            <?php endif; ?>
            <?php if ($errorMessage !== null): ?>
                <div class="member-flash danger"><i class="fa-solid fa-circle-exclamation"></i><?php echo e($errorMessage); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <a href="<?php echo e(url('/')); ?>"><span>Kos</span>Online</a>
            <small>User Panel</small>
        </div>
        <div class="sidebar-profile">
            <img src="<?php echo e($avatarSource); ?>" alt="Foto profil">
            <div>
                <strong><?php echo e($user['nama_lengkap']); ?></strong>
                <small>@<?php echo e($user['username']); ?></small>
            </div>
        </div>
        <ul class="menu">
            <li><a href="#" data-page="dashboard" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
            <li><a href="#" data-page="pesananku"><i class="fa-solid fa-bed"></i> Pesananku</a></li>
            <li><a href="#" data-page="pembayaran"><i class="fa-solid fa-file-invoice-dollar"></i> Invoice</a></li>
            <li><a href="#" data-page="profil"><i class="fa-solid fa-user-gear"></i> Profil</a></li>
        </ul>
        <div class="logout">
            <form method="POST" action="<?php echo e(url('/logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
            </form>
        </div>
    </aside>

    <main class="content">
        <div class="header-mobile">
            <button id="sidebar-toggle" class="btn-toggle" type="button"><i class="fa-solid fa-bars"></i></button>
            <h3>KosOnline</h3>
        </div>

        <section class="page active" id="dashboard">
            <div class="topbar">
                <div>
                    <p class="eyebrow">Dashboard User</p>
                    <h1>Halo, <?php echo e(strtok((string) $user['nama_lengkap'], ' ') ?: 'User'); ?></h1>
                    <p class="page-subtitle">Pantau pesanan, invoice, dan deadline sewa kos kamu dari satu tempat.</p>
                </div>
                <a href="<?php echo e(url('/rooms')); ?>" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Cari Kamar</a>
            </div>

            <div class="stats">
                <div class="stats-card">
                    <i class="fa-solid fa-receipt"></i>
                    <small>Tagihan Terdekat</small>
                    <strong><?php echo e($formatRupiah((float) $summary['tagihan_terdekat'])); ?></strong>
                </div>
                <div class="stats-card">
                    <i class="fa-solid fa-clock"></i>
                    <small>Deadline</small>
                    <strong><?php echo e($formatDate((string) $summary['jatuh_tempo'])); ?></strong>
                </div>
                <div class="stats-card">
                    <i class="fa-solid fa-bed"></i>
                    <small>Pesanan</small>
                    <strong><?php echo e((string) $summary['total_pesanan']); ?></strong>
                </div>
                <div class="stats-card">
                    <i class="fa-solid fa-shield-halved"></i>
                    <small>Status</small>
                    <strong><span class="badge <?php echo e($summary['class_badge']); ?>"><?php echo e($summary['status_bayar']); ?></span></strong>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="panel panel-hero">
                    <div>
                        <p class="eyebrow">Kamar Saat Ini</p>
                        <h2><?php echo e($summary['nama_kost']); ?></h2>
                        <p><?php echo e($summary['kamar_info']); ?></p>
                    </div>
                    <?php if ($rental !== null): ?>
                        <span class="badge <?php echo e($statusClass((string) $rental['status_sewa'])); ?>"><?php echo e($rental['status_sewa']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="panel invoice-highlight">
                    <p class="eyebrow">Invoice Terbaru</p>
                    <?php if ($latestInvoice !== null): ?>
                        <h2><?php echo e($latestInvoice['invoice_no'] ?? 'Invoice'); ?></h2>
                        <p><?php echo e($latestInvoice['nama_kost']); ?> - Kamar <?php echo e($latestInvoice['nomor_kamar']); ?></p>
                        <div class="invoice-row">
                            <span>Total</span>
                            <strong><?php echo e($formatRupiah((float) ($latestInvoice['total_bayar'] ?? $latestInvoice['nominal'] ?? 0))); ?></strong>
                        </div>
                        <div class="invoice-actions">
                            <a href="<?php echo e(url('/rooms/invoice?id=' . $latestInvoice['id_pembayaran'])); ?>" class="btn-primary soft"><i class="fa-solid fa-eye"></i> Lihat Invoice</a>
                            <a href="https://wa.me/6287748703029?text=<?php echo rawurlencode('Halo Admin, saya ingin konfirmasi invoice ' . ($latestInvoice['invoice_no'] ?? '')); ?>" target="_blank" class="btn-whatsapp"><i class="fa-brands fa-whatsapp"></i> Konfirmasi</a>
                        </div>
                    <?php else: ?>
                        <h2>Belum ada invoice</h2>
                        <p>Invoice akan muncul setelah kamu membuat booking kamar.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="page" id="pesananku">
            <div class="topbar">
                <div>
                    <p class="eyebrow">Pesananku</p>
                    <h1>Riwayat Pesanan Kamar</h1>
                    <p class="page-subtitle">Cek status booking, tanggal masuk, dan jatuh tempo sewa.</p>
                </div>
            </div>

            <div class="order-list">
                <?php if ($rentals !== []): ?>
                    <?php foreach ($rentals as $item): ?>
                        <?php
                        $imageSource = !empty($item['foto_kost']) ? upload_asset((string) $item['foto_kost']) : site_image('images.jpg');
                        ?>
                        <article class="order-card">
                            <img src="<?php echo e($imageSource); ?>" alt="Foto kos">
                            <div class="order-body">
                                <div class="order-title">
                                    <div>
                                        <h3><?php echo e($item['nama_kost']); ?></h3>
                                        <p>Kamar <?php echo e($item['nomor_kamar']); ?>, Lantai <?php echo e($item['lantai']); ?></p>
                                    </div>
                                    <span class="badge <?php echo e($statusClass((string) $item['status_sewa'])); ?>"><?php echo e($item['status_sewa']); ?></span>
                                </div>
                                <div class="mini-grid">
                                    <span><b>Tanggal masuk</b><?php echo e($formatDate((string) $item['tanggal_masuk'])); ?></span>
                                    <span><b>Jatuh tempo</b><?php echo e($formatDate((string) $item['jatuh_tempo'])); ?></span>
                                    <span><b>Tagihan</b><?php echo e($formatRupiah((float) ($item['total_bayar'] ?? $item['harga']))); ?></span>
                                </div>
                                <p class="muted"><i class="fa-solid fa-location-dot"></i> <?php echo e($item['alamat']); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-bed"></i>
                        <h3>Belum ada pesanan</h3>
                        <p>Mulai cari kamar yang cocok, lalu buat invoice booking manual.</p>
                        <a href="<?php echo e(url('/rooms')); ?>" class="btn-primary">Lihat Kamar</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="page" id="pembayaran">
            <div class="topbar">
                <div>
                    <p class="eyebrow">Invoice</p>
                    <h1>Pembayaran & Konfirmasi</h1>
                    <p class="page-subtitle">Semua tagihan dibuat sebagai card invoice, siap nanti disambungkan ke payment gateway.</p>
                </div>
            </div>

            <div class="invoice-list">
                <?php if ($paymentHistory !== []): ?>
                    <?php foreach ($paymentHistory as $payment): ?>
                        <article class="invoice-card">
                            <div>
                                <span class="badge <?php echo e($statusClass((string) $payment['status_verifikasi'])); ?>"><?php echo e($payment['status_verifikasi']); ?></span>
                                <h3><?php echo e($payment['invoice_no'] ?? 'Invoice'); ?></h3>
                                <p><?php echo e($payment['nama_kost']); ?> - Kamar <?php echo e($payment['nomor_kamar']); ?></p>
                            </div>
                            <div class="invoice-meta">
                                <span><b>Periode</b><?php echo e($formatDate((string) ($payment['periode_mulai'] ?? ''))); ?> - <?php echo e($formatDate((string) ($payment['periode_selesai'] ?? ''))); ?></span>
                                <span><b>Total</b><?php echo e($formatRupiah((float) ($payment['total_bayar'] ?? $payment['nominal'] ?? 0))); ?></span>
                                <span><b>Metode</b><?php echo e(str_replace('_', ' ', (string) ($payment['metode_bayar'] ?? '-'))); ?></span>
                            </div>
                            <div class="invoice-actions">
                                <a href="<?php echo e(url('/rooms/invoice?id=' . $payment['id_pembayaran'])); ?>" class="btn-primary soft"><i class="fa-solid fa-file-lines"></i> Detail</a>
                                <a href="https://wa.me/6287748703029?text=<?php echo rawurlencode('Halo Admin, saya ingin konfirmasi invoice ' . ($payment['invoice_no'] ?? '')); ?>" target="_blank" class="btn-whatsapp"><i class="fa-brands fa-whatsapp"></i> Konfirmasi</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <h3>Belum ada invoice</h3>
                        <p>Invoice otomatis muncul setelah kamu booking kamar.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="page" id="profil">
            <div class="topbar">
                <div>
                    <p class="eyebrow">Profil</p>
                    <h1>Data Akun</h1>
                    <p class="page-subtitle">Data ini akan dipakai otomatis saat membuat invoice booking.</p>
                </div>
            </div>

            <div class="profile-layout">
                <div class="profile-card">
                    <img src="<?php echo e($avatarSource); ?>" alt="Foto profil">
                    <h2><?php echo e($user['nama_lengkap']); ?></h2>
                    <p>@<?php echo e($user['username']); ?></p>
                    <span><?php echo e($user['email']); ?></span>
                </div>

                <form method="POST" class="form-panel">
                    <?php echo csrf_field(); ?>
                    <label>Nama Lengkap
                        <input type="text" name="nama" value="<?php echo e($user['nama_lengkap']); ?>" required>
                    </label>
                    <label>Username
                        <input type="text" name="username" value="<?php echo e($user['username']); ?>" pattern="[a-z0-9_]{3,30}" required>
                    </label>
                    <label>Email
                        <input type="email" name="email" value="<?php echo e($user['email']); ?>" required>
                    </label>
                    <label>No. Handphone
                        <input type="text" name="no_hp" value="<?php echo e($user['no_hp']); ?>" required>
                    </label>
                    <label>Password Baru
                        <input type="password" name="password" minlength="6" placeholder="Kosongkan kalau tidak diganti">
                    </label>
                    <button type="submit" name="update_profil" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
                </form>
            </div>
        </section>
    </main>

    <script src="<?php echo e(asset('js/admin.js')); ?>"></script>
</body>
</html>
