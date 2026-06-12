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
$wishlistRooms = $wishlistRooms ?? [];
$chatThreads = $chatThreads ?? [];
$currentThread = $currentThread ?? null;
$chatMessages = $chatMessages ?? [];
$activeTab = in_array((string) ($activeTab ?? 'dashboard'), ['dashboard', 'pesananku', 'pembayaran', 'wishlist', 'chat', 'profil'], true)
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
$currentThreadContext = null;
if ($currentThread !== null && !empty($currentThread['id_kamar'])) {
    $currentThreadContext = [
        'title' => ($currentThread['nama_kost'] ?? 'KosOnline') . ' - Kamar ' . ($currentThread['nomor_kamar'] ?? '-'),
        'subtitle' => $currentThread['alamat'] ?? '',
        'harga' => (float) ($currentThread['harga'] ?? 0),
        'status' => $currentThread['status_kamar'] ?? '-',
        'image' => upload_asset((string) ($currentThread['foto_kost'] ?? '')),
        'url' => url('/rooms/detail?id=' . (int) $currentThread['id_kamar']),
    ];
}
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
            <li><a href="#" data-page="dashboard" class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
            <li><a href="#" data-page="pesananku" class="<?php echo $activeTab === 'pesananku' ? 'active' : ''; ?>"><i class="fa-solid fa-bed"></i> Pesananku</a></li>
            <li><a href="#" data-page="pembayaran" class="<?php echo $activeTab === 'pembayaran' ? 'active' : ''; ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Invoice</a></li>
            <li><a href="#" data-page="wishlist" class="<?php echo $activeTab === 'wishlist' ? 'active' : ''; ?>"><i class="fa-regular fa-heart"></i> Wishlist</a></li>
            <li><a href="#" data-page="chat" class="<?php echo $activeTab === 'chat' ? 'active' : ''; ?>"><i class="fa-regular fa-comments"></i> Chat Admin</a></li>
            <li><a href="#" data-page="profil" class="<?php echo $activeTab === 'profil' ? 'active' : ''; ?>"><i class="fa-solid fa-user-gear"></i> Profil</a></li>
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

        <section class="page <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>" id="dashboard">
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

        <section class="page <?php echo $activeTab === 'pesananku' ? 'active' : ''; ?>" id="pesananku">
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

        <section class="page <?php echo $activeTab === 'pembayaran' ? 'active' : ''; ?>" id="pembayaran">
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

        <section class="page <?php echo $activeTab === 'wishlist' ? 'active' : ''; ?>" id="wishlist">
            <div class="topbar">
                <div>
                    <p class="eyebrow">Wishlist</p>
                    <h1>Kamar yang Disimpan</h1>
                    <p class="page-subtitle">Tempat aman untuk kamar yang masih kamu pikir-pikir dulu.</p>
                </div>
                <a href="<?php echo e(url('/rooms')); ?>" class="btn-primary"><i class="fa-solid fa-bed"></i> Cari Kamar Lagi</a>
            </div>

            <div class="order-list">
                <?php if ($wishlistRooms !== []): ?>
                    <?php foreach ($wishlistRooms as $item): ?>
                        <?php
                        $imageSource = !empty($item['foto_kost']) ? upload_asset((string) $item['foto_kost']) : site_image('images.jpg');
                        $discount = max(0, min(100, (int) ($item['diskon_cabang'] ?? 0)));
                        $finalPrice = (float) $item['harga'] * (1 - ($discount / 100));
                        ?>
                        <article class="order-card">
                            <img src="<?php echo e($imageSource); ?>" alt="Foto kos">
                            <div class="order-body">
                                <div class="order-title">
                                    <div>
                                        <h3><?php echo e($item['nama_kost']); ?></h3>
                                        <p>Kamar <?php echo e($item['nomor_kamar']); ?>, Lantai <?php echo e($item['lantai']); ?></p>
                                    </div>
                                    <span class="badge <?php echo $item['status'] === 'Tersedia' ? 'success' : 'warning'; ?>"><?php echo e($item['status']); ?></span>
                                </div>
                                <div class="mini-grid">
                                    <span><b>Harga</b><?php echo e($formatRupiah($finalPrice)); ?></span>
                                    <span><b>Diskon</b><?php echo $discount > 0 ? e((string) $discount) . '%' : '-'; ?></span>
                                    <span><b>Disimpan</b><?php echo e($formatDate((string) $item['dibuat_pada'])); ?></span>
                                </div>
                                <p class="muted"><i class="fa-solid fa-location-dot"></i> <?php echo e($item['alamat']); ?></p>
                                <div class="invoice-actions">
                                    <a href="<?php echo e(url('/rooms/detail?id=' . $item['id_kamar'])); ?>" class="btn-primary soft">Lihat Detail</a>
                                    <form method="POST" action="<?php echo e(url('/wishlist/toggle')); ?>">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id_kamar" value="<?php echo e($item['id_kamar']); ?>">
                                        <input type="hidden" name="redirect" value="/member/dashboard?tab=wishlist">
                                        <button type="submit" class="btn-whatsapp wishlist-remove"><i class="fa-solid fa-heart-crack"></i> Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-heart"></i>
                        <h3>Wishlist masih kosong</h3>
                        <p>Simpan kamar dari halaman detail atau daftar kamar biar gampang dibandingkan nanti.</p>
                        <a href="<?php echo e(url('/rooms')); ?>" class="btn-primary">Cari Kamar</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="page <?php echo $activeTab === 'chat' ? 'active' : ''; ?>" id="chat">
            <div class="topbar">
                <div>
                    <p class="eyebrow">Chat Admin</p>
                    <h1>Obrolan dengan Admin</h1>
                    <p class="page-subtitle">Riwayat chat tersimpan, termasuk pertanyaan spesifik kamar dari halaman detail.</p>
                </div>
            </div>

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
                    <?php if ($chatThreads !== []): ?>
                        <?php foreach ($chatThreads as $thread): ?>
                            <?php $context = !empty($thread['id_kamar']) ? (($thread['nama_kost'] ?? 'Kamar') . ' - Kamar ' . ($thread['nomor_kamar'] ?? '-')) : 'Chat umum'; ?>
                            <a href="<?php echo e(url('/member/dashboard?tab=chat&thread=' . $thread['id_thread'])); ?>" class="<?php echo $currentThread !== null && (int) $currentThread['id_thread'] === (int) $thread['id_thread'] ? 'active' : ''; ?>">
                                <strong><?php echo e($thread['subjek']); ?></strong>
                                <span><?php echo e($context); ?></span>
                                <small><?php echo e((string) ($thread['pesan_terakhir'] ?? 'Belum ada pesan')); ?></small>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="chat-empty-list">Belum ada chat. Mulai tanya admin dari form di bawah.</div>
                    <?php endif; ?>
                </aside>

                <section class="chat-panel">
                    <div class="chat-panel-head">
                        <div>
                            <strong><?php echo e($currentThread['subjek'] ?? 'Chat umum dengan Admin'); ?></strong>
                            <span>
                                <?php echo $currentThread !== null && !empty($currentThread['id_kamar'])
                                    ? e(($currentThread['nama_kost'] ?? 'Kamar') . ' - Kamar ' . ($currentThread['nomor_kamar'] ?? '-'))
                                    : 'Admin akan membalas dari dashboard admin.'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="chat-room-card-wrap" data-chat-context>
                        <?php if ($currentThreadContext !== null): ?>
                            <a href="<?php echo e($currentThreadContext['url']); ?>" class="chat-room-card">
                                <img src="<?php echo e($currentThreadContext['image']); ?>" alt="Foto kamar">
                                <div>
                                    <strong><?php echo e($currentThreadContext['title']); ?></strong>
                                    <span><?php echo e($currentThreadContext['subtitle']); ?></span>
                                    <b><?php echo e($formatRupiah((float) $currentThreadContext['harga'])); ?></b>
                                </div>
                                <small><?php echo e($currentThreadContext['status']); ?></small>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="chat-message-list" data-chat-messages>
                        <?php if ($chatMessages !== []): ?>
                            <?php foreach ($chatMessages as $message): ?>
                                <div class="chat-bubble <?php echo $message['sender_type'] === 'user' ? 'mine' : 'admin'; ?>">
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
                    <div class="chat-typing-indicator" data-chat-typing hidden></div>
                    <form method="POST" action="<?php echo e(url('/member/chat/send')); ?>" class="chat-compose" data-chat-compose>
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id_thread" value="<?php echo e($currentThread['id_thread'] ?? 0); ?>">
                        <textarea name="isi_pesan" rows="3" placeholder="Tulis pesan untuk admin..." required></textarea>
                        <button type="submit"><i class="fa-solid fa-paper-plane"></i> Kirim</button>
                    </form>
                </section>
            </div>
        </section>

        <section class="page <?php echo $activeTab === 'profil' ? 'active' : ''; ?>" id="profil">
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

    <script src="<?php echo e(asset('js/chat-realtime.js')); ?>"></script>
    <script src="<?php echo e(asset('js/admin.js')); ?>"></script>
</body>
</html>
