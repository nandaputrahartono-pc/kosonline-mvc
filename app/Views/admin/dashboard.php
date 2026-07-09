<?php
$successMessage = $successMessage ?? null;
$errorMessage = $errorMessage ?? null;
$adminName = (string) ($adminName ?? 'Admin');
$billingMonth = (string) ($billingMonth ?? date('F Y'));
$revenueTrend = array_merge([
    'labels' => [],
    'values' => [],
    'total' => 0,
], (array) ($revenueTrend ?? []));
$stats = array_merge([
    'total_kost' => 0,
    'total_kamar' => 0,
    'terisi' => 0,
    'kosong' => 0,
    'penghuni_aktif' => 0,
    'belum_bayar' => 0,
    'pendapatan_total' => 0,
    'pendapatan_bulan_ini' => 0,
    'rasio_okupansi' => 0,
    'rasio_pembayaran' => 0,
], (array) ($stats ?? []));
$priorityBillings = $priorityBillings ?? [];
$kosts = $kosts ?? [];
$rooms = $rooms ?? [];
$roomPagination = array_merge([
    'current_page' => 1,
    'per_page' => 10,
    'total_pages' => 1,
    'total_items' => count($rooms),
    'from' => count($rooms) > 0 ? 1 : 0,
    'to' => count($rooms),
], (array) ($roomPagination ?? []));
$adminRoomsPageUrl = static function (int $page): string {
    return url('/admin/dashboard?' . http_build_query([
        'tab' => 'data-kamar',
        'rooms_page' => $page,
    ]));
};
$users = $users ?? [];
$billings = $billings ?? [];
$messages = $messages ?? [];
$locations = $locations ?? [];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$revenueLabelsJson = json_encode($revenueTrend['labels'] ?? [], $jsonFlags);
$revenueValuesJson = json_encode($revenueTrend['values'] ?? [], $jsonFlags | JSON_NUMERIC_CHECK);
$hour = (int) date('H');
$greeting = $hour < 11 ? 'Good Morning' : ($hour < 15 ? 'Good Afternoon' : 'Good Evening');
$todayLabel = date('d M Y');
$trendTotal = (float) ($revenueTrend['total'] ?? 0);
$activeTab = (string) ($activeTab ?? 'dashboard');
$chatThreads = $chatThreads ?? [];
$currentChatThread = $currentChatThread ?? null;
$chatMessages = $chatMessages ?? [];
$roomLabel = static function (mixed $roomNumber): string {
    $label = trim((string) $roomNumber);
    if ($label === '') {
        return 'Kamar -';
    }

    return preg_match('/^kamar\b/i', $label) === 1 ? $label : 'Kamar ' . $label;
};
$roomCardFromMessage = static function (array $message) use ($roomLabel): ?array {
    if (empty($message['id_kamar'])) {
        return null;
    }

    return [
        'title' => ($message['nama_kost'] ?? 'KosOnline') . ' - ' . $roomLabel($message['nomor_kamar'] ?? null),
        'subtitle' => (string) ($message['alamat'] ?? ''),
        'harga' => (float) ($message['harga'] ?? 0),
        'status' => (string) ($message['status_kamar'] ?? '-'),
        'image' => upload_asset((string) ($message['foto_kost'] ?? '')),
        'url' => url('/rooms/detail?id=' . (int) $message['id_kamar']),
    ];
};
$chatInitials = static function (?string $name): string {
    $words = preg_split('/\s+/', trim((string) $name)) ?: [];
    $letters = '';

    foreach ($words as $word) {
        if ($word !== '') {
            $letters .= strtoupper(substr($word, 0, 1));
        }

        if (strlen($letters) >= 2) {
            break;
        }
    }

    return $letters !== '' ? $letters : 'KO';
};
$wsHost = preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1'));
$wsHost = $wsHost !== '' ? $wsHost : '127.0.0.1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KosOnline</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin.css')); ?>">
</head>
<body>
    <?php if ($successMessage !== null || $errorMessage !== null): ?>
        <div class="admin-flash-stack" aria-live="polite">
            <?php if ($successMessage !== null): ?>
                <div class="admin-flash success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo e($successMessage); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($errorMessage !== null): ?>
                <div class="admin-flash danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo e($errorMessage); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand-icon">K</div>
            <h2>Kos<span>Admin</span></h2>
        </div>
        <ul class="menu">
            <li class="menu-label">Main Menu</li>
            <li><a href="#" data-page="dashboard" class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
            <li class="menu-label">Manajemen Kost</li>
            <li><a href="#" data-page="data-kost"><i class="fa-solid fa-building"></i> Data Kost</a></li>
            <li><a href="#" data-page="data-kamar" class="<?php echo $activeTab === 'data-kamar' ? 'active' : ''; ?>"><i class="fa-solid fa-bed"></i> Data Kamar</a></li>
            <li><a href="#" data-page="data-user"><i class="fa-solid fa-users"></i> Data User</a></li>
            <li><a href="#" data-page="pembayaran"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a></li>
            <li class="menu-label">Support</li>
            <li><a href="#" data-page="chat-user" class="<?php echo $activeTab === 'chat-user' ? 'active' : ''; ?>"><i class="fa-regular fa-comments"></i> Chat User</a></li>
            <li><a href="#" data-page="pesan-masuk"><i class="fa-solid fa-envelope"></i> Pesan Masuk</a></li>
            <li><a href="#" data-page="atur-lokasi"><i class="fa-solid fa-map-location-dot"></i> Atur Lokasi</a></li>
        </ul>
        <div class="logout">
            <form method="POST" action="<?php echo e(url('/admin/logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
            </form>
        </div>
    </aside>

    <main class="content">
        <div class="admin-topbar">
            <div class="header-mobile d-md-none d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="btn-toggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h3 class="mb-0 fs-5">KosAdmin</h3>
            </div>

            <div class="topbar-greeting">
                <p><?php echo e($greeting); ?>, <strong><?php echo e($adminName); ?></strong></p>
                <span>Monitor performa kost, pembayaran, dan kamar dari satu tempat.</span>
            </div>

            <div class="topbar-actions">
                <div class="topbar-date">
                    <i class="fa-regular fa-calendar"></i>
                    <?php echo e($todayLabel); ?>
                </div>
                <button type="button" class="topbar-icon" title="Cari">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <button type="button" class="topbar-icon" title="Notifikasi">
                    <i class="fa-regular fa-bell"></i>
                    <?php if ((int) $stats['belum_bayar'] > 0): ?>
                        <span class="notification-dot"></span>
                    <?php endif; ?>
                </button>
                <button id="theme-toggle" class="topbar-icon" title="Ubah tema">
                    <i class="fa-solid fa-moon"></i>
                </button>
            </div>
        </div>

        <section class="page <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>" id="dashboard">
            <div class="dashboard-hero">
                <div>
                    <span class="eyebrow">Overview</span>
                    <h1>Dashboard Pendapatan Kost</h1>
                    <p>Ringkasan bulan <?php echo e($billingMonth); ?> dengan grafik pendapatan dari pembayaran yang sudah lunas.</p>
                </div>
                <div class="hero-actions">
                    <button type="button" class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
                    <a href="#" data-page="pembayaran" class="btn hero-primary"><i class="fa-solid fa-wallet"></i> Cek Pembayaran</a>
                </div>
            </div>

            <div class="metric-grid">
                <article class="card metric-card primary">
                    <div class="metric-icon"><i class="fa-solid fa-rupiah-sign"></i></div>
                    <p>Total Pendapatan</p>
                    <h3>Rp <?php echo number_format((float) $stats['pendapatan_total'], 0, ',', '.'); ?></h3>
                    <span class="metric-note positive">Akumulasi pembayaran lunas</span>
                </article>
                <article class="card metric-card">
                    <div class="metric-icon soft-blue"><i class="fa-solid fa-calendar-check"></i></div>
                    <p>Pendapatan Bulan Ini</p>
                    <h3>Rp <?php echo number_format((float) $stats['pendapatan_bulan_ini'], 0, ',', '.'); ?></h3>
                    <span class="metric-note"><?php echo e($billingMonth); ?></span>
                </article>
                <article class="card metric-card">
                    <div class="metric-icon soft-green"><i class="fa-solid fa-bed"></i></div>
                    <p>Okupansi Kamar</p>
                    <h3><?php echo e($stats['rasio_okupansi']); ?>%</h3>
                    <span class="metric-note"><?php echo e($stats['terisi']); ?> terisi dari <?php echo e($stats['total_kamar']); ?> kamar</span>
                </article>
                <article class="card metric-card">
                    <div class="metric-icon soft-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <p>Belum Bayar</p>
                    <h3><?php echo e($stats['belum_bayar']); ?></h3>
                    <span class="metric-note danger">Perlu ditagih bulan ini</span>
                </article>
            </div>

            <div class="dashboard-grid">
                <article class="card dashboard-card chart-card">
                    <div class="card-heading">
                        <div>
                            <h3>Grafik Pendapatan</h3>
                            <p>Total 6 bulan terakhir: Rp <?php echo number_format($trendTotal, 0, ',', '.'); ?></p>
                        </div>
                        <span class="chart-legend"><i></i> Pembayaran lunas</span>
                    </div>
                    <div class="chart-shell">
                        <canvas id="revenueChart" height="130"></canvas>
                    </div>
                </article>

                <aside class="card dashboard-card status-summary">
                    <div class="status-blue">
                        <span>Status Pembayaran</span>
                        <strong><?php echo e($stats['rasio_pembayaran']); ?>%</strong>
                        <small><?php echo e($billingMonth); ?></small>
                        <div class="sparkline" aria-hidden="true"></div>
                    </div>
                    <div class="status-metrics">
                        <div>
                            <span>Penghuni Aktif</span>
                            <strong><?php echo e($stats['penghuni_aktif']); ?></strong>
                        </div>
                        <div>
                            <span>Kamar Kosong</span>
                            <strong><?php echo e($stats['kosong']); ?></strong>
                        </div>
                    </div>
                </aside>

                <article class="card dashboard-card">
                    <div class="card-heading">
                        <div>
                            <h3>Tagihan Prioritas</h3>
                            <p>Penyewa yang belum lunas bulan ini.</p>
                        </div>
                    </div>
                    <div class="priority-list">
                        <?php if (!empty($priorityBillings)): ?>
                            <?php foreach ($priorityBillings as $billing): ?>
                                <div class="priority-item">
                                    <div>
                                        <strong><?php echo e($billing['nama_lengkap']); ?></strong>
                                        <span><?php echo e($billing['nama_kost'] . ' - ' . $billing['nomor_kamar']); ?></span>
                                    </div>
                                    <b>Rp <?php echo number_format((float) $billing['harga'], 0, ',', '.'); ?></b>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">Semua tagihan aktif bulan ini sudah aman.</div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="card dashboard-card">
                    <div class="card-heading">
                        <div>
                            <h3>Ringkasan Aset</h3>
                            <p>Kondisi unit kost yang dikelola.</p>
                        </div>
                    </div>
                    <div class="asset-overview">
                        <div class="asset-row">
                            <span>Total Cabang</span>
                            <strong><?php echo e($stats['total_kost']); ?></strong>
                        </div>
                        <div class="asset-row">
                            <span>Total Kamar</span>
                            <strong><?php echo e($stats['total_kamar']); ?></strong>
                        </div>
                        <div class="progress-wrap">
                            <div class="progress-label">
                                <span>Okupansi</span>
                                <strong><?php echo e($stats['rasio_okupansi']); ?>%</strong>
                            </div>
                            <div class="progress-track">
                                <span style="width: <?php echo e($stats['rasio_okupansi']); ?>%;"></span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="page" id="data-kost">
            <h2 class="page-title">Data Kost</h2>
            <div class="table-header">
                <a href="<?php echo e(url('/admin/kost/create')); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Kost</a>
            </div>
            <div class="table-responsive table-wrapper">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama Kost</th>
                            <th>Alamat</th>
                            <th>Diskon Cabang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kosts as $kost): ?>
                            <tr>
                                <td><?php echo e($kost['nama_kost']); ?></td>
                                <td><?php echo e($kost['alamat']); ?></td>
                                <td><?php echo (int) ($kost['diskon_persen'] ?? 0) > 0 ? e($kost['diskon_persen']) . '%' : '-'; ?></td>
                                <td>
                                    <a href="<?php echo e(url('/admin/kost/edit?id=' . $kost['id_kost'])); ?>" class="btn btn-sm btn-edit"><i class="fa-solid fa-pen"></i></a>
                                    <form method="POST" action="<?php echo e(url('/admin/kost/delete')); ?>" class="inline-action-form" onsubmit="return confirm('Yakin hapus?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($kost['id_kost']); ?>">
                                        <button type="submit" class="btn btn-sm btn-delete"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="page <?php echo $activeTab === 'data-kamar' ? 'active' : ''; ?>" id="data-kamar">
            <h2 class="page-title">Data Kamar</h2>
            <div class="table-header">
                <a href="<?php echo e(url('/admin/rooms/create')); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Kamar</a>
                <span class="table-result-note">
                    Menampilkan <?php echo e((string) $roomPagination['from']); ?>-<?php echo e((string) $roomPagination['to']); ?>
                    dari <?php echo e((string) $roomPagination['total_items']); ?> kamar
                </span>
            </div>
            <div class="table-responsive table-wrapper">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No. Kamar</th>
                            <th>Cabang</th>
                            <th>Harga</th>
                            <th>Diskon Kamar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rooms === []): ?>
                            <tr>
                                <td colspan="6">Belum ada data kamar.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($rooms as $room): ?>
                            <?php
                            $badgeClass = $room['status'] === 'Terisi' ? 'warning' : 'success';
                            $buttonClass = $room['status'] === 'Terisi' ? 'btn btn-sm btn-delete' : 'btn btn-sm btn-edit';
                            $buttonIcon = $room['status'] === 'Terisi' ? 'fa-user-xmark' : 'fa-user-check';
                            $buttonLabel = $room['status'] === 'Terisi' ? 'Kosongkan' : 'Isi Kamar';
                            $confirmMessage = $room['status'] === 'Terisi' ? 'Yakin ingin mengosongkan kamar ini?' : 'Ubah status menjadi Terisi?';
                            ?>
                            <tr>
                                <td><?php echo e($room['nomor_kamar']); ?></td>
                                <td><?php echo e($room['nama_kost']); ?></td>
                                <td>Rp <?php echo number_format((float) $room['harga'], 0, ',', '.'); ?></td>
                                <td><?php echo (int) ($room['diskon_persen'] ?? 0) > 0 ? e($room['diskon_persen']) . '%' : '-'; ?></td>
                                <td><span class="badge <?php echo e($badgeClass); ?>"><?php echo e($room['status']); ?></span></td>
                                <td>
                                    <form method="POST" action="<?php echo e(url('/admin/rooms/toggle-status')); ?>" class="inline-action-form" onsubmit="return confirm(<?php echo e(json_encode($confirmMessage)); ?>)">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($room['id_kamar']); ?>">
                                        <button type="submit" class="<?php echo e($buttonClass); ?>"><i class="fa-solid <?php echo e($buttonIcon); ?>"></i> <?php echo e($buttonLabel); ?></button>
                                    </form>

                                    <a href="<?php echo e(url('/admin/rooms/edit?id=' . $room['id_kamar'])); ?>" class="btn btn-sm btn-edit"><i class="fa-solid fa-pen"></i></a>
                                    <form method="POST" action="<?php echo e(url('/admin/rooms/delete')); ?>" class="inline-action-form" onsubmit="return confirm('Hapus kamar?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($room['id_kamar']); ?>">
                                        <button type="submit" class="btn btn-sm btn-delete"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ((int) $roomPagination['total_pages'] > 1): ?>
                <?php
                $currentRoomPage = (int) $roomPagination['current_page'];
                $totalRoomPages = (int) $roomPagination['total_pages'];
                $startRoomPage = max(1, $currentRoomPage - 2);
                $endRoomPage = min($totalRoomPages, $currentRoomPage + 2);
                ?>
                <nav aria-label="Navigasi data kamar">
                    <ul class="pagination justify-content-center flex-wrap admin-room-pagination mb-0">
                    <?php if ($currentRoomPage > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?php echo e($adminRoomsPageUrl($currentRoomPage - 1)); ?>"><i class="fa-solid fa-chevron-left"></i></a></li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-left"></i></span></li>
                    <?php endif; ?>

                    <?php if ($startRoomPage > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?php echo e($adminRoomsPageUrl(1)); ?>">1</a></li>
                        <?php if ($startRoomPage > 2): ?><li class="page-item disabled"><span class="page-link page-dots">...</span></li><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($pageNumber = $startRoomPage; $pageNumber <= $endRoomPage; $pageNumber++): ?>
                        <?php if ($pageNumber === $currentRoomPage): ?>
                            <li class="page-item active" aria-current="page"><span class="page-link"><?php echo e((string) $pageNumber); ?></span></li>
                        <?php else: ?>
                            <li class="page-item"><a class="page-link" href="<?php echo e($adminRoomsPageUrl($pageNumber)); ?>"><?php echo e((string) $pageNumber); ?></a></li>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($endRoomPage < $totalRoomPages): ?>
                        <?php if ($endRoomPage < $totalRoomPages - 1): ?><li class="page-item disabled"><span class="page-link page-dots">...</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?php echo e($adminRoomsPageUrl($totalRoomPages)); ?>"><?php echo e((string) $totalRoomPages); ?></a></li>
                    <?php endif; ?>

                    <?php if ($currentRoomPage < $totalRoomPages): ?>
                        <li class="page-item"><a class="page-link" href="<?php echo e($adminRoomsPageUrl($currentRoomPage + 1)); ?>"><i class="fa-solid fa-chevron-right"></i></a></li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-right"></i></span></li>
                    <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>

        <section class="page" id="data-user">
            <h2 class="page-title">Data User</h2>
            <div class="table-header">
                <a href="<?php echo e(url('/admin/users/create')); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah User</a>
            </div>
            <div class="table-responsive table-wrapper">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>No HP</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo e($user['nama_lengkap']); ?></td>
                                <td><?php echo e($user['email']); ?></td>
                                <td><?php echo e($user['no_hp']); ?></td>
                                <td>
                                    <a href="<?php echo e(url('/admin/users/edit?id=' . $user['id_user'])); ?>" class="btn btn-sm btn-edit"><i class="fa-solid fa-pen"></i></a>
                                    <form method="POST" action="<?php echo e(url('/admin/users/delete')); ?>" class="inline-action-form" onsubmit="return confirm('Hapus user?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($user['id_user']); ?>">
                                        <button type="submit" class="btn btn-sm btn-delete"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="page" id="pembayaran">
            <h2 class="page-title">Pembayaran (<?php echo e($billingMonth); ?>)</h2>
            <p class="page-subtitle">Kelola status pembayaran penyewa bulan ini</p>
            <div class="table-responsive table-wrapper">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama Penghuni</th>
                            <th>Kamar</th>
                            <th>Invoice / Periode</th>
                            <th>Tagihan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($billings as $billing): ?>
                            <?php
                            $status = (string) ($billing['status_verifikasi'] ?? '');
                            $isPaid = $status === 'Lunas';
                            $isPendingBooking = ($billing['status_sewa'] ?? '') === 'Menunggu Pembayaran';
                            $statusLabel = $isPaid ? 'Lunas' : ($status === 'Menunggu' ? 'Menunggu Konfirmasi' : 'Belum Bayar');
                            $billAmount = (float) ($billing['total_bayar'] ?? 0);
                            if ($billAmount <= 0) {
                                $billAmount = (float) ($billing['harga'] ?? 0);
                            }
                            $invoiceLabel = $billing['invoice_no'] ?? $billingMonth;
                            $periodLabel = !empty($billing['periode_mulai']) && !empty($billing['periode_selesai'])
                                ? date('d M Y', strtotime((string) $billing['periode_mulai'])) . ' - ' . date('d M Y', strtotime((string) $billing['periode_selesai']))
                                : $billingMonth;
                            $waMessage = 'Halo ' . $billing['nama_lengkap'] . ', tagihan kost bulan ' . $billingMonth . ' belum dibayar ya.';
                            $phone = preg_replace('/\D+/', '', (string) $billing['no_hp']) ?? '';
                            $waPhone = str_starts_with($phone, '0') ? '62' . substr($phone, 1) : $phone;
                            $waLink = 'https://wa.me/' . $waPhone . '?text=' . rawurlencode($waMessage);
                            ?>
                            <tr>
                                <td><?php echo e($billing['nama_lengkap']); ?></td>
                                <td><?php echo e($billing['nama_kost'] . ' - ' . $billing['nomor_kamar']); ?></td>
                                <td>
                                    <strong><?php echo e($invoiceLabel); ?></strong><br>
                                    <small style="color:#64748b;"><?php echo e($periodLabel); ?></small>
                                </td>
                                <td>Rp <?php echo number_format($billAmount, 0, ',', '.'); ?></td>
                                <td><span class="badge <?php echo $isPaid ? 'success' : 'warning'; ?>"><?php echo e($statusLabel); ?></span></td>
                                <td>
                                    <form method="POST" action="<?php echo e(url('/admin/payments/update')); ?>" class="inline-action-form" onsubmit="return confirm('<?php echo $isPaid ? 'Batalkan status bayar?' : 'Konfirmasi lunas?'; ?>')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($billing['id_sewa']); ?>">
                                        <input type="hidden" name="aksi" value="<?php echo $isPaid ? 'batal' : 'lunas'; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $isPaid ? 'btn-delete' : 'btn-edit'; ?>">
                                            <i class="fa-solid <?php echo $isPaid ? 'fa-xmark' : 'fa-check'; ?>"></i> <?php echo $isPaid ? 'Batal' : 'Lunas'; ?>
                                        </button>
                                    </form>
                                    <?php if ($isPendingBooking && !$isPaid): ?>
                                        <form method="POST" action="<?php echo e(url('/admin/payments/update')); ?>" class="inline-action-form" onsubmit="return confirm('Batalkan booking pending ini?')">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo e($billing['id_sewa']); ?>">
                                            <input type="hidden" name="aksi" value="batal">
                                            <button type="submit" class="btn btn-sm btn-delete"><i class="fa-solid fa-ban"></i> Batalkan</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (!$isPaid): ?>
                                        <a href="<?php echo e($waLink); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-edit" style="background:#25D366; color:white;"><i class="fa-brands fa-whatsapp"></i> Tagih</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="page <?php echo $activeTab === 'chat-user' ? 'active' : ''; ?>" id="chat-user">
            <div class="admin-chat-layout"
                 data-chat-realtime
                 data-thread-id="<?php echo e($currentChatThread['id_thread'] ?? ''); ?>"
                 data-fetch-url="<?php echo e(url('/admin/chat/messages')); ?>"
                 data-typing-url="<?php echo e(url('/admin/chat/typing')); ?>"
                 data-csrf="<?php echo e(csrf_token()); ?>"
                 data-me-type="admin"
                 data-me-label="Admin"
                 data-peer-label="<?php echo e($currentChatThread['nama_lengkap'] ?? 'User'); ?>"
                 data-ws-url="ws://<?php echo e($wsHost); ?>:8098">
                <aside class="admin-chat-contacts">
                    <div class="admin-chat-sidebar-head">
                        <div>
                            <p>Pusat Chat</p>
                            <h3>Pesan User</h3>
                            <span>Pantau pertanyaan kamar, booking, dan pembayaran dari satu ruang.</span>
                        </div>
                    </div>
                    <button type="button" class="admin-chat-sidebar-rule">
                        <span>Kontak Aktif</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <?php if ($chatThreads !== []): ?>
                        <?php foreach ($chatThreads as $thread): ?>
                            <?php
                            $isActiveThread = $currentChatThread !== null && (int) $currentChatThread['id_thread'] === (int) $thread['id_thread'];
                            $context = $thread['email'] ?? 'User KosOnline';
                            ?>
                            <a href="<?php echo e(url('/admin/dashboard?tab=chat-user&thread=' . $thread['id_thread'])); ?>" class="<?php echo $isActiveThread ? 'active' : ''; ?>">
                                <i><?php echo e($chatInitials($thread['nama_lengkap'] ?? 'User')); ?></i>
                                <span class="thread-copy">
                                    <strong><?php echo e($thread['nama_lengkap']); ?></strong>
                                    <em><?php echo e($context); ?></em>
                                    <small><?php echo e($thread['pesan_terakhir'] ?? 'Belum ada pesan'); ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">Belum ada chat user.</div>
                    <?php endif; ?>
                    <div class="admin-chat-sidebar-foot">
                        <span><?php echo count($chatThreads); ?> kontak</span>
                        <strong><i></i> Admin Online</strong>
                    </div>
                </aside>

                <section class="admin-chat-panel">
                    <?php if ($currentChatThread !== null): ?>
                        <div class="admin-chat-head">
                            <div class="admin-chat-head-user">
                                <i><?php echo e($chatInitials($currentChatThread['nama_lengkap'] ?? 'User')); ?></i>
                                <div>
                                    <h3><?php echo e($currentChatThread['nama_lengkap']); ?></h3>
                                    <p>
                                        <?php echo e($currentChatThread['email']); ?> &bull; <?php echo e($currentChatThread['no_hp']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="admin-chat-head-actions">
                                <span><i></i> Online</span>
                                <button type="button" aria-label="Pin chat"><i class="fa-solid fa-thumbtack"></i></button>
                                <button type="button" aria-label="Favorit chat"><i class="fa-solid fa-star"></i></button>
                            </div>
                        </div>
                        <div class="admin-chat-messages" data-chat-scroll>
                            <div class="admin-chat-message-feed" data-chat-messages>
                                <?php foreach ($chatMessages as $message): ?>
                                    <?php
                                    $card = $roomCardFromMessage($message);
                                    $isRoomCardOnly = ($message['tipe_pesan'] ?? 'text') === 'room_card';
                                    ?>
                                    <?php if ($card !== null): ?>
                                        <div class="admin-chat-sent-room-card user<?php echo !$isRoomCardOnly ? ' grouped' : ''; ?>" data-initials="<?php echo e($chatInitials($currentChatThread['nama_lengkap'] ?? 'User')); ?>">
                                            <a href="<?php echo e($card['url']); ?>" class="admin-chat-room-card admin-chat-room-card-message" target="_blank">
                                                <img src="<?php echo e($card['image']); ?>" alt="Foto kamar">
                                                <div>
                                                    <strong><?php echo e($card['title']); ?></strong>
                                                    <span><?php echo e($card['subtitle']); ?></span>
                                                    <b>Rp <?php echo number_format((float) $card['harga'], 0, ',', '.'); ?></b>
                                                </div>
                                                <small><?php echo e($card['status']); ?></small>
                                            </a>
                                        </div>
                                        <?php if ($isRoomCardOnly): ?>
                                            <?php continue; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="admin-chat-bubble <?php echo $message['sender_type'] === 'admin' ? 'mine' : 'user'; ?><?php echo $card !== null ? ' grouped-with-card' : ''; ?>" data-initials="<?php echo e($chatInitials($message['sender_type'] === 'admin' ? 'Admin' : ($currentChatThread['nama_lengkap'] ?? 'User'))); ?>">
                                        <span><?php echo $message['sender_type'] === 'admin' ? 'Admin' : e($currentChatThread['nama_lengkap']); ?></span>
                                        <p><?php echo nl2br(e($message['isi_pesan'])); ?></p>
                                        <small><?php echo e(date('d M Y H:i', strtotime((string) $message['dibuat_pada']))); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="admin-chat-typing" data-chat-typing hidden></div>
                        <form method="POST" action="<?php echo e(url('/admin/chat/send')); ?>" class="admin-chat-compose" data-chat-compose>
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id_thread" value="<?php echo e($currentChatThread['id_thread']); ?>">
                            <div class="admin-chat-compose-box">
                                <textarea name="isi_pesan" rows="1" placeholder="Tulis balasan... Enter untuk kirim, Shift+Enter untuk baris baru" required></textarea>
                                <button type="submit" aria-label="Kirim balasan"><i class="fa-solid fa-paper-plane"></i></button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="empty-state">Pilih chat user untuk mulai membalas.</div>
                    <?php endif; ?>
                </section>
            </div>
        </section>

        <section class="page" id="pesan-masuk">
            <h2 class="page-title">Pesan Masuk</h2>
            <div class="table-responsive table-wrapper">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama</th>
                            <th>Pesan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td style="font-size: 13px; color: #666;"><?php echo e(date('d-m-Y', strtotime($message['tanggal']))); ?></td>
                                <td>
                                    <strong><?php echo e($message['nama_pengirim']); ?></strong><br>
                                    <small style="color: #1e3a8a;"><?php echo e($message['email_pengirim']); ?></small>
                                </td>
                                <td><?php echo e($message['isi_pesan']); ?></td>
                                <td>
                                    <a href="mailto:<?php echo e($message['email_pengirim']); ?>" class="btn btn-sm btn-edit" style="background: #3b82f6; margin-bottom: 5px;"><i class="fa-solid fa-paper-plane"></i> Balas</a>
                                    <form method="POST" action="<?php echo e(url('/admin/messages/delete')); ?>" class="inline-action-form" onsubmit="return confirm('Hapus pesan?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($message['id_pesan']); ?>">
                                        <button type="submit" class="btn btn-sm btn-delete"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="page" id="atur-lokasi">
            <h2 class="page-title">Atur Lokasi Kost</h2>
            <div class="table-responsive table-wrapper">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama Kost</th>
                            <th>Alamat</th>
                            <th>Koordinat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $location): ?>
                            <?php $latLong = !empty($location['latitude']) ? $location['latitude'] . ', ' . $location['longitude'] : "<span style='color:red'>Belum diset</span>"; ?>
                            <tr>
                                <td><?php echo e($location['nama_kost']); ?></td>
                                <td><?php echo e($location['alamat']); ?></td>
                                <td><?php echo $latLong; ?></td>
                                <td>
                                    <a href="<?php echo e(url('/admin/locations/edit?id=' . $location['id_kost'])); ?>" class="btn btn-sm btn-edit" style="background:#3b82f6;">
                                        <i class="fa-solid fa-map-pin"></i> Set Titik
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="info-box" style="margin-top:20px;">
                <h3><i class="fa-solid fa-circle-info"></i> Cara Mengambil Koordinat</h3>
                <p>1. Buka Google Maps.<br>
                   2. Klik kanan pada lokasi kost.<br>
                   3. Klik angka pertama yang muncul (itu adalah Latitude & Longitude).<br>
                   4. Paste angka tersebut di menu "Set Titik".</p>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.adminRevenueChart = {
            labels: <?php echo $revenueLabelsJson; ?>,
            values: <?php echo $revenueValuesJson; ?>
        };
    </script>
    <script src="<?php echo e(asset('js/notifications.js')); ?>"></script>
    <script src="<?php echo e(asset('js/chat-realtime.js')); ?>"></script>
    <script src="<?php echo e(asset('js/admin.js')); ?>"></script>
</body>
</html>
