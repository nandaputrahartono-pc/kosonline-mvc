<?php
$rooms = $rooms ?? [];
$branches = $branches ?? [];
$keyword = (string) ($keyword ?? '');
$idKost = $idKost ?? null;
$promoOnly = (bool) ($promoOnly ?? false);
$sort = (string) ($sort ?? 'recommended');
$reviewSummaries = $reviewSummaries ?? [];
$savedRoomIds = $savedRoomIds ?? [];
$summary = $roomsSummary ?? [
    'total_available' => count($rooms),
    'result_count' => count($rooms),
    'promo_count' => 0,
    'lowest_price' => 0,
];
$pagination = array_merge([
    'current_page' => 1,
    'per_page' => 9,
    'total_pages' => 1,
    'total_items' => count($rooms),
    'from' => count($rooms) > 0 ? 1 : 0,
    'to' => count($rooms),
], (array) ($pagination ?? []));
$roomPageUrl = static function (int $page) use ($keyword, $idKost, $promoOnly, $sort): string {
    $query = [];
    if ($keyword !== '') {
        $query['cari'] = $keyword;
    }
    if ($idKost !== null) {
        $query['cabang'] = (int) $idKost;
    }
    if ($sort !== 'recommended') {
        $query['sort'] = $sort;
    }
    if ($promoOnly) {
        $query['promo'] = '1';
    }
    if ($page > 1) {
        $query['page'] = $page;
    }

    return url('/rooms' . ($query !== [] ? '?' . http_build_query($query) : ''));
};
$currentRoomQuery = [];
if ($keyword !== '') {
    $currentRoomQuery['cari'] = $keyword;
}
if ($idKost !== null) {
    $currentRoomQuery['cabang'] = (int) $idKost;
}
if ($sort !== 'recommended') {
    $currentRoomQuery['sort'] = $sort;
}
if ($promoOnly) {
    $currentRoomQuery['promo'] = '1';
}
if ((int) $pagination['current_page'] > 1) {
    $currentRoomQuery['page'] = (int) $pagination['current_page'];
}
$currentRoomsUrl = '/rooms' . ($currentRoomQuery !== [] ? '?' . http_build_query($currentRoomQuery) : '');

$sortLabels = [
    'recommended' => 'Rekomendasi',
    'termurah' => 'Termurah',
    'termahal' => 'Termahal',
    'promo' => 'Promo terbesar',
];

ob_start();
?>

<main class="public-page rooms-page">
    <section class="rooms-compact-hero">
        <div class="container">
            <div class="rooms-hero-banner">
                <img src="<?php echo e(site_image('kossan.jpg')); ?>" alt="Area kamar kos KosOnline">
                <div class="rooms-hero-content">
                    <h1>Cari kamar kos sesuai kebutuhanmu</h1>
                    <p>Pilih cabang, fasilitas, harga, dan promo dari satu tempat</p>
                </div>
            </div>
        </div>
    </section>

    <section class="public-section rooms-results-section pt-0">
        <div class="container">
            <div id="room-filter" class="public-filter-card rooms-filter-card">
                <form action="<?php echo e(url('/rooms')); ?>" method="GET" class="rooms-search-form">
                    <div class="rooms-search-field">
                        <label for="cabang" class="form-label">Cabang Kos</label>
                        <select name="cabang" id="cabang" class="form-select">
                            <option value="">Semua Cabang</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo e($branch['id_kost']); ?>" <?php echo $idKost === (int) $branch['id_kost'] ? 'selected' : ''; ?>>
                                    <?php echo e($branch['nama_kost']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rooms-search-field rooms-search-field-wide">
                        <label for="searchInput" class="form-label">Cari fasilitas / alamat</label>
                        <div class="input-group input-icon-group">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="searchInput" name="cari" class="form-control" placeholder="Contoh: AC, WiFi, dekat kampus" value="<?php echo e($keyword); ?>">
                        </div>
                    </div>
                    <div class="rooms-search-field">
                        <label for="sort" class="form-label">Urutkan</label>
                        <select name="sort" id="sort" class="form-select">
                            <?php foreach ($sortLabels as $sortValue => $sortLabel): ?>
                                <option value="<?php echo e($sortValue); ?>" <?php echo $sort === $sortValue ? 'selected' : ''; ?>>
                                    <?php echo e($sortLabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="room-filter-actions">
                        <label class="promo-toggle">
                            <input type="checkbox" name="promo" value="1" <?php echo !empty($promoOnly) ? 'checked' : ''; ?>>
                            <span><i class="fa-solid fa-tags"></i> Promo saja</span>
                        </label>
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Cari
                        </button>
                    </div>
                </form>
            </div>

            <?php if (!empty($rooms)): ?>
                <div class="room-modern-grid">
                    <?php $cardRedirectUrl = $currentRoomsUrl; ?>
                    <?php foreach ($rooms as $roomCard): ?>
                        <?php require __DIR__ . '/../partials/room-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?php if ((int) $pagination['total_pages'] > 1): ?>
                    <?php
                    $currentPage = (int) $pagination['current_page'];
                    $totalPages = (int) $pagination['total_pages'];
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    ?>
                    <nav class="rooms-pagination" aria-label="Navigasi halaman kamar">
                        <ul class="pagination justify-content-center flex-wrap mb-0">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a href="<?php echo e($roomPageUrl($currentPage - 1)); ?>" class="page-link rooms-page-link">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link rooms-page-link"><i class="fa-solid fa-chevron-left"></i></span>
                                </li>
                            <?php endif; ?>

                            <?php if ($startPage > 1): ?>
                                <li class="page-item"><a href="<?php echo e($roomPageUrl(1)); ?>" class="page-link rooms-page-link">1</a></li>
                                <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link rooms-page-dots">...</span></li><?php endif; ?>
                            <?php endif; ?>

                            <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                                <?php if ($pageNumber === $currentPage): ?>
                                    <li class="page-item active" aria-current="page"><span class="page-link rooms-page-link"><?php echo e((string) $pageNumber); ?></span></li>
                                <?php else: ?>
                                    <li class="page-item"><a href="<?php echo e($roomPageUrl($pageNumber)); ?>" class="page-link rooms-page-link"><?php echo e((string) $pageNumber); ?></a></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link rooms-page-dots">...</span></li><?php endif; ?>
                                <li class="page-item"><a href="<?php echo e($roomPageUrl($totalPages)); ?>" class="page-link rooms-page-link"><?php echo e((string) $totalPages); ?></a></li>
                            <?php endif; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a href="<?php echo e($roomPageUrl($currentPage + 1)); ?>" class="page-link rooms-page-link">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link rooms-page-link"><i class="fa-solid fa-chevron-right"></i></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="public-empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-bed"></i></div>
                    <h3>Kamar belum ketemu</h3>
                    <p>Coba longgarkan filter, atau chat admin supaya dibantu pilih kamar yang mendekati kebutuhanmu.</p>
                    <div class="empty-actions">
                        <a href="<?php echo e(url('/rooms')); ?>" class="btn btn-primary">Reset Filter</a>
                        <a href="https://wa.me/6287748703029?text=<?php echo e(rawurlencode('Halo Admin KosOnline, saya ingin dibantu cari kamar kos.')); ?>" class="btn btn-outline-success" target="_blank" rel="noopener">
                            Tanya Admin
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
$content = ob_get_clean();
$title = 'Daftar Kamar Kost - KosOnline';
$extraHead = '<link rel="stylesheet" href="' . e(asset('css/pages/rooms.css')) . '">';

// Filter (cabang, urutkan, promo) langsung diterapkan begitu diubah — tanpa ini
// pengguna harus menekan "Cari" dulu sehingga filternya terasa "mati".
$extraScripts = <<<'HTML'
<script>
    (function () {
        var form = document.querySelector('.rooms-search-form');
        if (!form) return;

        ['cabang', 'sort', 'promo'].forEach(function (name) {
            var field = form.querySelector('[name="' + name + '"]');
            if (!field) return;
            field.addEventListener('change', function () {
                form.submit();
            });
        });
    })();
</script>
HTML;

require base_path('app/Views/layouts/public.php');
?>
