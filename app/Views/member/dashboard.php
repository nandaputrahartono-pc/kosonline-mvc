<?php
$user = array_merge([
    'nama_lengkap' => 'User',
    'username' => 'user',
    'email' => '-',
    'no_hp' => '-',
    'foto_profil' => 'default.jpg',
], $user ?? []);
$rental = $rental ?? null;
$rentals = $rentals ?? [];
$paymentHistory = $paymentHistory ?? [];
$latestInvoice = $latestInvoice ?? null;
$chatThreads = $chatThreads ?? [];
$currentThread = $currentThread ?? null;
$chatMessages = $chatMessages ?? [];
$pendingRoomId = (int) ($pendingRoomId ?? 0);
$pendingRoomCard = is_array($pendingRoomCard ?? null) ? $pendingRoomCard : null;
$activeTab = in_array((string) ($activeTab ?? 'dashboard'), ['dashboard', 'pesananku', 'chat', 'profil', 'kos-saya', 'riwayat-kos'], true)
    ? (string) $activeTab
    : 'dashboard';
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
$roomLabel = static function (mixed $roomNumber): string {
    $label = trim((string) $roomNumber);
    if ($label === '') {
        return 'Kamar -';
    }

    return preg_match('/^kamar\b/i', $label) === 1 ? $label : 'Kamar ' . $label;
};
$chatSubject = static function (?array $thread) use ($roomLabel): string {
    if ($thread === null) {
        return 'Chat dengan Admin';
    }

    return (string) ($thread['subjek'] ?? 'Chat dengan Admin');
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
$wsHost = preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1'));
$wsHost = $wsHost !== '' ? $wsHost : '127.0.0.1';
ob_start();
?>
<main class="member-page">
    <div class="member-dashboard-shell<?php echo $activeTab === 'chat' ? ' is-chat-open' : ''; ?>">
        <section class="content w-100">

        <!-- Tab 1: Dashboard / Akun Saya Hub -->
        <section class="page <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>" id="dashboard">
            <div class="account-hub-header">
                <nav class="hub-breadcrumbs">
                    <a href="<?php echo e(url('/')); ?>">Beranda</a>
                    <span class="separator">/</span>
                    <span class="current">Akun Saya</span>
                </nav>
                <h1>Akun Saya</h1>
                <p class="hub-subtitle">Kelola kos, invoice, dan pengaturan akun kamu di sini.</p>
            </div>

            <div class="account-hub-grid">
                <!-- Left Column: Profile Card -->
                <div class="hub-profile-card">
                    <div class="hub-profile-avatar-wrap">
                        <img src="<?php echo e($avatarSource); ?>" alt="Foto profil" class="hub-profile-avatar" decoding="async">
                        <span class="hub-verification-badge verified">
                            <i class="fa-solid fa-shield-halved"></i> Terverifikasi
                        </span>
                    </div>
                    <h2 class="hub-profile-name"><?php echo e($user['nama_lengkap']); ?></h2>
                    <p class="hub-profile-username">@<?php echo e($user['username']); ?></p>
                    <p class="hub-profile-email"><?php echo e($user['email']); ?></p>

                    <!-- Profile Completion Progress -->
                    <?php
                    $filledFields = 0;
                    if (!empty($user['nama_lengkap']) && $user['nama_lengkap'] !== 'User') $filledFields++;
                    if (!empty($user['email']) && $user['email'] !== '-') $filledFields++;
                    if (!empty($user['no_hp']) && $user['no_hp'] !== '-') $filledFields++;
                    if (!empty($user['foto_profil']) && $user['foto_profil'] !== 'default.jpg') $filledFields++;
                    $completionPercent = (int)(($filledFields / 4) * 100);
                    if ($completionPercent === 0) $completionPercent = 25; // Minimum progress
                    ?>
                    <div class="profile-completion">
                        <div class="completion-text">
                            <span>Kelengkapan Profil</span>
                            <strong><?php echo e($completionPercent); ?>%</strong>
                        </div>
                        <div class="completion-bar-outer">
                            <div class="completion-bar-inner" style="width: <?php echo e($completionPercent); ?>%;"></div>
                        </div>
                    </div>

                    <a href="#" class="btn-lengkapi-profil" data-page="profil"><i class="fa-solid fa-user-pen"></i> Lengkapi Profil</a>
                </div>

                <!-- Right Column: Menu Options List -->
                <div class="hub-menu-list">
                    <!-- 1. Kos Saya -->
                    <button type="button" class="hub-menu-item" data-page="kos-saya">
                        <span class="hub-menu-icon icon-blue">
                            <i class="fa-solid fa-house-chimney"></i>
                        </span>
                        <span class="hub-menu-text">
                            <strong>Kos Saya</strong>
                            <span>Lihat kos yang sedang kamu tempati.</span>
                        </span>
                        <i class="fa-solid fa-chevron-right hub-menu-chevron"></i>
                    </button>

                    <!-- 2. Pengajuan Sewa -->
                    <button type="button" class="hub-menu-item" data-page="pesananku">
                        <span class="hub-menu-icon icon-green">
                            <i class="fa-solid fa-file-invoice"></i>
                        </span>
                        <span class="hub-menu-text">
                            <strong>Pesanan & Invoice</strong>
                            <span>Cek status booking sekaligus detail invoice-nya.</span>
                        </span>
                        <i class="fa-solid fa-chevron-right hub-menu-chevron"></i>
                    </button>

                    <!-- 3. Riwayat Kos -->
                    <button type="button" class="hub-menu-item" data-page="riwayat-kos">
                        <span class="hub-menu-icon icon-purple">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </span>
                        <span class="hub-menu-text">
                            <strong>Riwayat Kos</strong>
                            <span>Lihat riwayat kos yang kamu tempati.</span>
                        </span>
                        <i class="fa-solid fa-chevron-right hub-menu-chevron"></i>
                    </button>

                    <!-- 4. Pengaturan -->
                    <button type="button" class="hub-menu-item" data-page="profil">
                        <span class="hub-menu-icon icon-pink">
                            <i class="fa-solid fa-gears"></i>
                        </span>
                        <span class="hub-menu-text">
                            <strong>Pengaturan</strong>
                            <span>Atur profil, keamanan, dan preferensi akun.</span>
                        </span>
                        <i class="fa-solid fa-chevron-right hub-menu-chevron"></i>
                    </button>

                    <!-- 7. Logout -->
                    <form method="POST" action="<?php echo e(url('/logout')); ?>" class="hub-logout-form">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="hub-menu-item w-100 border-0 text-start bg-transparent">
                            <span class="hub-menu-icon icon-red">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </span>
                            <span class="hub-menu-text">
                                <strong>Logout</strong>
                                <span>Keluar dari akun kamu.</span>
                            </span>
                            <i class="fa-solid fa-chevron-right hub-menu-chevron"></i>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Tab: Kos Saya -->
        <section class="page <?php echo $activeTab === 'kos-saya' ? 'active' : ''; ?>" id="kos-saya">
            <div class="hub-back-nav">
                <a href="#" class="btn-back-hub" data-page="dashboard"><i class="fa-solid fa-arrow-left"></i> Kembali ke Akun Saya</a>
            </div>
            <div class="topbar">
                <div>
                    <p class="eyebrow">Informasi Hunian</p>
                    <h1>Kos Saya</h1>
                    <p class="page-subtitle">Detail kos yang sedang kamu tempati saat ini.</p>
                </div>
            </div>

            <?php if ($rental !== null): ?>
                <div class="my-kost-detail-card">
                    <div class="my-kost-header">
                        <div class="my-kost-title">
                            <h2><?php echo e($rental['nama_kost']); ?></h2>
                            <p><i class="fa-solid fa-location-dot"></i> <?php echo e($rental['alamat']); ?></p>
                        </div>
                        <span class="badge <?php echo e($statusClass((string) $rental['status_sewa'])); ?>"><?php echo e($rental['status_sewa']); ?></span>
                    </div>

                    <div class="my-kost-grid">
                        <div class="my-kost-info-item">
                            <small>Nomor Kamar</small>
                            <strong>Kamar <?php echo e($rental['nomor_kamar']); ?></strong>
                        </div>
                        <div class="my-kost-info-item">
                            <small>Lantai</small>
                            <strong>Lantai <?php echo e($rental['lantai']); ?></strong>
                        </div>
                        <div class="my-kost-info-item">
                            <small>Harga Sewa</small>
                            <strong><?php echo e($formatRupiah((float)$rental['harga'])); ?> <span class="muted-text">/ bulan</span></strong>
                        </div>
                        <div class="my-kost-info-item">
                            <small>Tanggal Masuk</small>
                            <strong><?php echo e($formatDate((string) $rental['tanggal_masuk'])); ?></strong>
                        </div>
                        <div class="my-kost-info-item">
                            <small>Jatuh Tempo Berikutnya</small>
                            <strong class="text-danger"><?php echo e($formatDate((string) $summary['jatuh_tempo'])); ?></strong>
                        </div>
                        <div class="my-kost-info-item">
                            <small>Fasilitas Kamar</small>
                            <strong><?php echo e($rental['fasilitas'] ?? 'Fasilitas Standard'); ?></strong>
                        </div>
                    </div>

                    <div class="my-kost-actions">
                        <a href="#" class="btn-primary soft" data-page="pembayaran"><i class="fa-solid fa-file-invoice-dollar"></i> Bayar Tagihan</a>
                        <a href="<?php echo e(url('/member/dashboard?tab=chat')); ?>" class="btn-primary" data-page="chat"><i class="fa-regular fa-comments"></i> Hubungi Admin</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-house-circle-exclamation"></i>
                    <h3>Kamu belum menyewa kos</h3>
                    <p>Mulai cari kos yang nyaman dan strategis untuk tempat tinggalmu.</p>
                    <a href="<?php echo e(url('/rooms')); ?>" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Cari Kamar Kos</a>
                </div>
            <?php endif; ?>
        </section>

        <!-- Tab: Riwayat Kos -->
        <section class="page <?php echo $activeTab === 'riwayat-kos' ? 'active' : ''; ?>" id="riwayat-kos">
            <div class="hub-back-nav">
                <a href="#" class="btn-back-hub" data-page="dashboard"><i class="fa-solid fa-arrow-left"></i> Kembali ke Akun Saya</a>
            </div>
            <div class="topbar">
                <div>
                    <p class="eyebrow">Riwayat Hunian</p>
                    <h1>Riwayat Kos</h1>
                    <p class="page-subtitle">Daftar kos yang pernah kamu tempati sebelumnya.</p>
                </div>
            </div>

            <div class="order-list">
                <?php
                $historyRentals = array_filter($rentals, fn($item) => $item['status_sewa'] === 'Berhenti');
                ?>
                <?php if ($historyRentals !== []): ?>
                    <?php foreach ($historyRentals as $item): ?>
                        <?php
                        $imageSource = !empty($item['foto_kost']) ? upload_asset((string) $item['foto_kost']) : site_image('images.jpg');
                        ?>
                        <article class="order-card">
                            <img src="<?php echo e($imageSource); ?>" alt="Foto kos" loading="lazy" decoding="async">
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
                                    <span><b>Tanggal keluar</b><?php echo e($formatDate((string) $item['tanggal_keluar'])); ?></span>
                                    <span><b>Harga</b><?php echo e($formatRupiah((float) ($item['total_bayar'] ?? $item['harga']))); ?></span>
                                </div>
                                <p class="muted"><i class="fa-solid fa-location-dot"></i> <?php echo e($item['alamat']); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <h3>Belum ada riwayat kos</h3>
                        <p>Kamu belum memiliki riwayat sewa kos yang sudah selesai.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Tab 2: Pesananku -->
        <section class="page <?php echo $activeTab === 'pesananku' ? 'active' : ''; ?>" id="pesananku">
            <div class="hub-back-nav">
                <a href="#" class="btn-back-hub" data-page="dashboard"><i class="fa-solid fa-arrow-left"></i> Kembali ke Akun Saya</a>
            </div>
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
                            <img src="<?php echo e($imageSource); ?>" alt="Foto kos" loading="lazy" decoding="async">
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

                                <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--border-soft);">
                                    <?php if (!empty($item['invoice_no'])): ?>
                                        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px;">
                                            <strong style="font-size:.9rem;"><i class="fa-solid fa-file-invoice-dollar"></i> <?php echo e($item['invoice_no']); ?></strong>
                                            <span class="badge <?php echo e($statusClass((string) ($item['status_verifikasi'] ?? 'Menunggu'))); ?>"><?php echo e($item['status_verifikasi'] ?? 'Menunggu'); ?></span>
                                        </div>
                                        <div class="mini-grid">
                                            <span><b>Periode</b><?php echo e($formatDate((string) ($item['periode_mulai'] ?? ''))); ?> - <?php echo e($formatDate((string) ($item['periode_selesai'] ?? ''))); ?></span>
                                            <span><b>Total invoice</b><?php echo e($formatRupiah((float) ($item['total_bayar'] ?? $item['harga']))); ?></span>
                                            <span><b>Metode</b><?php echo e(ucwords(str_replace(['manual_', '_'], ['', ' '], (string) ($item['metode_bayar'] ?? '-')))); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
                                        <?php if (!empty($item['id_pembayaran'])): ?>
                                            <a href="<?php echo e(url('/rooms/invoice?id=' . $item['id_pembayaran'])); ?>" class="btn-primary soft"><i class="fa-solid fa-file-lines"></i> Detail Invoice</a>
                                        <?php endif; ?>
                                        <?php if (($item['status_sewa'] ?? '') === 'Dibatalkan'): ?>
                                            <form method="POST" action="<?php echo e(url('/member/orders/delete')); ?>" data-confirm="Hapus pesanan yang dibatalkan ini? Tidak bisa dikembalikan." data-confirm-ok="Ya, Hapus">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="id_sewa" value="<?php echo e($item['id_sewa']); ?>">
                                                <button type="submit" style="background:#fee2e2; color:#b91c1c; border:0; border-radius:10px; padding:10px 16px; font-weight:700; cursor:pointer;"><i class="fa-solid fa-trash"></i> Hapus</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
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

        <!-- Tab 4: Chat -->
        <section class="page <?php echo $activeTab === 'chat' ? 'active' : ''; ?>" id="chat">
            <div class="chat-dashboard"
                 data-chat-realtime
                 data-thread-id="<?php echo e($currentThread['id_thread'] ?? ''); ?>"
                 data-fetch-url="<?php echo e(url('/member/chat/messages')); ?>"
                 data-typing-url="<?php echo e(url('/member/chat/typing')); ?>"
                 data-csrf="<?php echo e(csrf_token()); ?>"
                 data-me-type="user"
                 data-me-label="Kamu"
                 data-peer-label="Admin"
                 data-ws-url="ws://<?php echo e($wsHost); ?>:8098">
                <aside class="chat-thread-list">
                    <div class="chat-sidebar-head">
                        <a href="#" class="btn-back-chat mb-3" data-page="dashboard" title="Kembali ke Akun Saya">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div>
                            <p>Chat KosOnline</p>
                            <h2>Ruang Bantuan</h2>
                            <span>Tanya kamar, pembayaran, atau jadwal survei langsung ke admin.</span>
                        </div>
                    </div>
                    <button type="button" class="chat-sidebar-rule">
                        <span>Riwayat Chat</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <?php if ($chatThreads !== []): ?>
                        <?php foreach ($chatThreads as $thread): ?>
                            <?php $context = 'Admin KosOnline'; ?>
                            <a href="<?php echo e(url('/member/dashboard?tab=chat&thread=' . $thread['id_thread'])); ?>" class="<?php echo $currentThread !== null && (int) $currentThread['id_thread'] === (int) $thread['id_thread'] ? 'active' : ''; ?>">
                                <i class="fa-regular fa-comments"></i>
                                <span class="thread-copy">
                                     <strong><?php echo e($chatSubject($thread)); ?></strong>
                                     <em><?php echo e($context); ?></em>
                                     <small><?php echo e((string) ($thread['pesan_terakhir'] ?? 'Belum ada pesan')); ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="chat-empty-list">Belum ada chat. Mulai tanya admin dari form di bawah.</div>
                    <?php endif; ?>
                    <div class="chat-sidebar-foot">
                        <span><?php echo count($chatThreads); ?> ruang chat</span>
                        <strong><i></i> Admin Online</strong>
                    </div>
                </aside>

                <section class="chat-panel">
                    <div class="chat-panel-head">
                        <div class="chat-panel-identity">
                            <i class="fa-solid fa-headset"></i>
                            <div>
                                <strong><?php echo e($chatSubject($currentThread)); ?></strong>
                                <span>Chat langsung dengan admin KosOnline.</span>
                            </div>
                        </div>
                        <div class="chat-panel-actions">
                            <span><i></i> Online</span>
                            <button type="button" aria-label="Pin chat"><i class="fa-solid fa-thumbtack"></i></button>
                        </div>
                    </div>
                    <div class="chat-message-list" data-chat-scroll>
                        <div class="chat-message-feed" data-chat-messages>
                            <?php if ($chatMessages !== []): ?>
                                <?php foreach ($chatMessages as $message): ?>
                                    <?php
                                    $card = $roomCardFromMessage($message);
                                    $isRoomCardOnly = ($message['tipe_pesan'] ?? 'text') === 'room_card';
                                    ?>
                                    <?php if ($card !== null): ?>
                                        <div class="chat-sent-room-card mine<?php echo !$isRoomCardOnly ? ' grouped' : ''; ?>" data-initials="<?php echo e($chatInitials($user['nama_lengkap'] ?? 'Kamu')); ?>">
                                            <a href="<?php echo e($card['url']); ?>" class="chat-room-card chat-room-card-message">
                                                <img src="<?php echo e($card['image']); ?>" alt="Foto kamar" loading="lazy" decoding="async">
                                                <div>
                                                    <strong><?php echo e($card['title']); ?></strong>
                                                    <span><?php echo e($card['subtitle']); ?></span>
                                                    <b><?php echo e($formatRupiah((float) $card['harga'])); ?></b>
                                                </div>
                                                <small><?php echo e($card['status']); ?></small>
                                            </a>
                                        </div>
                                        <?php if ($isRoomCardOnly): ?>
                                            <?php continue; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="chat-bubble <?php echo $message['sender_type'] === 'user' ? 'mine' : 'admin'; ?><?php echo $card !== null ? ' grouped-with-card' : ''; ?>" data-initials="<?php echo e($chatInitials($message['sender_type'] === 'user' ? ($user['nama_lengkap'] ?? 'Kamu') : 'Admin')); ?>">
                                        <span><?php echo $message['sender_type'] === 'user' ? 'Kamu' : 'Admin'; ?></span>
                                        <p><?php echo nl2br(e($message['isi_pesan'])); ?></p>
                                        <small><?php echo e(date('d M Y H:i', strtotime((string) $message['dibuat_pada']))); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state compact">
                                    <i class="fa-regular fa-comments"></i>
                                    <p>Mulai chat baru dengan admin.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="chat-typing-indicator" data-chat-typing hidden></div>
                    <div class="chat-room-card-wrap" data-chat-context <?php echo $pendingRoomCard === null ? 'hidden' : 'data-pending-context="1"'; ?>>
                        <?php if ($pendingRoomCard !== null): ?>
                            <a href="<?php echo e($pendingRoomCard['detail_url'] ?? '#'); ?>" class="chat-room-card">
                                <img src="<?php echo e($pendingRoomCard['image_url'] ?? ''); ?>" alt="Foto kamar" loading="lazy" decoding="async">
                                <div>
                                    <strong><?php echo e($pendingRoomCard['title'] ?? 'Kamar Kos'); ?></strong>
                                    <span><?php echo e($pendingRoomCard['subtitle'] ?? ''); ?></span>
                                    <b><?php echo e($formatRupiah((float) ($pendingRoomCard['harga'] ?? 0))); ?></b>
                                </div>
                                <small><?php echo e($pendingRoomCard['status'] ?? '-'); ?></small>
                            </a>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="<?php echo e(url('/member/chat/send')); ?>" class="chat-compose" data-chat-compose>
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id_thread" value="<?php echo e($currentThread['id_thread'] ?? 0); ?>">
                        <input type="hidden" name="id_kamar" value="<?php echo e($pendingRoomId); ?>" data-pending-room-input>
                        <div class="chat-compose-box">
                            <textarea name="isi_pesan" rows="1" placeholder="Tulis pesan... Enter untuk kirim, Shift+Enter untuk baris baru" required></textarea>
                            <button type="submit" aria-label="Kirim pesan"><i class="fa-solid fa-paper-plane"></i></button>
                        </div>
                    </form>
                </section>
            </div>
        </section>

        <!-- Tab 5: Profil / Settings -->
        <section class="page <?php echo $activeTab === 'profil' ? 'active' : ''; ?>" id="profil">
            <div class="hub-back-nav">
                <a href="#" class="btn-back-hub" data-page="dashboard"><i class="fa-solid fa-arrow-left"></i> Kembali ke Akun Saya</a>
            </div>
            <div class="topbar">
                <div>
                    <p class="eyebrow">Profil</p>
                    <h1>Data Akun</h1>
                    <p class="page-subtitle">Data ini akan dipakai otomatis saat membuat invoice booking.</p>
                </div>
            </div>

            <div class="profile-layout">
                <div class="profile-card">
                    <img src="<?php echo e($avatarSource); ?>" alt="Foto profil" loading="lazy" decoding="async">
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

        </section>
    </div>
</main>
<?php
$content = ob_get_clean();
$title = 'Akun Saya - KosOnline';
$showFooter = true;
$showChatbot = false;
$extraHead = '<link rel="stylesheet" href="' . e(asset('css/member.css')) . '?v=' . time() . '">';
$extraScripts = '<script src="' . e(asset('js/chat-realtime.js')) . '"></script>' . <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
  const dashboard = document.querySelector('.member-dashboard-shell');
  if (!dashboard) return;

  const links = dashboard.querySelectorAll('[data-page]');
  const pages = dashboard.querySelectorAll('.page');

  function scrollChatToBottom() {
    const chatScroll = dashboard.querySelector('#chat [data-chat-scroll]');
    if (!chatScroll) return;
    chatScroll.scrollTop = chatScroll.scrollHeight;
  }

  const footer = document.querySelector('.footer-premium');

  function showPage(pageId, pushState) {
    dashboard.classList.toggle('is-chat-open', pageId === 'chat');
    if (footer) {
      footer.style.display = pageId === 'chat' ? 'none' : 'block';
    }

    pages.forEach(function (page) {
      page.classList.toggle('active', page.id === pageId);
    });

    links.forEach(function (link) {
      link.classList.toggle('active', link.getAttribute('data-page') === pageId);
    });

    if (pushState) {
      const url = new URL(window.location.href);
      url.searchParams.set('tab', pageId);
      window.history.replaceState({}, '', url);
    }

    if (pageId === 'chat') {
      window.requestAnimationFrame(scrollChatToBottom);
      window.setTimeout(scrollChatToBottom, 180);
    }
  }

  links.forEach(function (link) {
    link.addEventListener('click', function (event) {
      const pageId = this.getAttribute('data-page');
      if (!pageId) return;
      event.preventDefault();
      showPage(pageId, true);
    });
  });

  if (dashboard.querySelector('#chat.page.active')) {
    window.requestAnimationFrame(scrollChatToBottom);
    window.setTimeout(scrollChatToBottom, 220);
  }
});
</script>
HTML;
require base_path('app/Views/layouts/public.php');
