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
$chatThreads = $chatThreads ?? [];
$currentThread = $currentThread ?? null;
$chatMessages = $chatMessages ?? [];
$pendingRoomId = (int) ($pendingRoomId ?? 0);
$pendingRoomCard = is_array($pendingRoomCard ?? null) ? $pendingRoomCard : null;
$activeTab = in_array((string) ($activeTab ?? 'dashboard'), ['dashboard', 'pesananku', 'pembayaran', 'chat', 'profil'], true)
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
    <div class="member-dashboard-shell">
        <aside class="sidebar">
        <ul class="menu">
            <li><a href="<?php echo e(url('/member/dashboard?tab=dashboard')); ?>" data-page="dashboard" class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
            <li><a href="<?php echo e(url('/member/dashboard?tab=pesananku')); ?>" data-page="pesananku" class="<?php echo $activeTab === 'pesananku' ? 'active' : ''; ?>"><i class="fa-solid fa-bed"></i> Pesananku</a></li>
            <li><a href="<?php echo e(url('/member/dashboard?tab=pembayaran')); ?>" data-page="pembayaran" class="<?php echo $activeTab === 'pembayaran' ? 'active' : ''; ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Invoice</a></li>
            <li><a href="<?php echo e(url('/member/dashboard?tab=chat')); ?>" data-page="chat" class="<?php echo $activeTab === 'chat' ? 'active' : ''; ?>"><i class="fa-regular fa-comments"></i> Chat Admin</a></li>
            <li><a href="<?php echo e(url('/member/dashboard?tab=profil')); ?>" data-page="profil" class="<?php echo $activeTab === 'profil' ? 'active' : ''; ?>"><i class="fa-solid fa-user-gear"></i> Profil</a></li>
        </ul>
        <div class="logout">
            <form method="POST" action="<?php echo e(url('/logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
            </form>
        </div>
        </aside>

        <section class="content">
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
                                                <img src="<?php echo e($card['image']); ?>" alt="Foto kamar">
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
                                <img src="<?php echo e($pendingRoomCard['image_url'] ?? ''); ?>" alt="Foto kamar">
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
        </section>
    </div>
</main>
<?php
$content = ob_get_clean();
$title = 'Dashboard User - KosOnline';
$showFooter = false;
$showChatbot = false;
$extraHead = '<link rel="stylesheet" href="' . e(asset('css/member.css')) . '">';
$extraScripts = '<script src="' . e(asset('js/chat-realtime.js')) . '"></script>' . <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
  const dashboard = document.querySelector('.member-dashboard-shell');
  if (!dashboard) return;

  const links = dashboard.querySelectorAll('[data-page]');
  const pages = dashboard.querySelectorAll('.page');
  const sidebar = dashboard.querySelector('.sidebar');
  const sidebarToggle = dashboard.querySelector('#sidebar-toggle');

  function showPage(pageId, pushState) {
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
  }

  links.forEach(function (link) {
    link.addEventListener('click', function (event) {
      const pageId = this.getAttribute('data-page');
      if (!pageId) return;
      event.preventDefault();
      showPage(pageId, true);
      if (sidebar) sidebar.classList.remove('active');
    });
  });

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function () {
      sidebar.classList.toggle('active');
    });
  }
});
</script>
HTML;
require base_path('app/Views/layouts/public.php');
