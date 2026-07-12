<?php
$locations = $locations ?? [];
$mappedLocations = array_values(array_filter($locations, static fn(array $loc): bool => !empty($loc['latitude']) && !empty($loc['longitude'])));
$totalAvailable = array_sum(array_map(static fn(array $loc): int => (int) ($loc['kamar_tersedia'] ?? 0), $locations));

ob_start();
?>

<main class="public-page map-page">
    <section class="public-page-hero map-hero">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="section-eyebrow">Peta Lokasi</span>
                    <h1>Pilih cabang kos dari peta, bukan cuma dari nama.</h1>
                    <p>
                        Lihat persebaran cabang, cek kamar tersedia, lalu buka daftar kamar per lokasi. Ini membantu calon penyewa
                        memilih kos yang dekat dengan rutinitasnya.
                    </p>
                    <div class="hero-actions">
                        <a href="#branch-map" class="btn btn-light btn-lg fw-bold">
                            <i class="fa-solid fa-map me-2"></i> Buka Peta
                        </a>
                        <a href="<?php echo e(url('/rooms')); ?>" class="btn btn-outline-light btn-lg fw-bold">
                            <i class="fa-solid fa-bed me-2"></i> Lihat Kamar
                        </a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="map-hero-visual">
                        <div class="map-visual-top">
                            <span><i class="fa-solid fa-location-crosshairs"></i> Live area</span>
                            <strong>Cirebon</strong>
                        </div>
                        <div class="map-route-line">
                            <span></span>
                            <i class="fa-solid fa-house-chimney"></i>
                            <span></span>
                            <i class="fa-solid fa-bed"></i>
                            <span></span>
                        </div>
                        <div class="map-visual-grid">
                            <div>
                                <small>Total cabang</small>
                                <strong><?php echo e((string) count($locations)); ?></strong>
                            </div>
                            <div>
                                <small>Kamar tersedia</small>
                                <strong><?php echo e((string) $totalAvailable); ?></strong>
                            </div>
                        </div>
                        <p>Mulai dari lokasi terdekat, lalu pilih kamar yang paling masuk akal untuk rutinitas harian.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="branch-map" class="public-section map-section">
        <div class="container">
            <div class="map-insight-grid">
                <article>
                    <i class="fa-solid fa-location-dot"></i>
                    <span><?php echo e((string) count($mappedLocations)); ?> cabang sudah punya pin peta.</span>
                </article>
                <article>
                    <i class="fa-solid fa-door-open"></i>
                    <span><?php echo e((string) $totalAvailable); ?> kamar tersedia bisa langsung difilter per cabang.</span>
                </article>
                <article>
                    <i class="fa-solid fa-route"></i>
                    <span>Tombol rute membuka Google Maps untuk navigasi cepat.</span>
                </article>
            </div>
            <div class="map-shell">
                <aside class="map-sidebar">
                    <div class="map-sidebar-head">
                        <span class="section-eyebrow">Cabang Kos</span>
                        <h2>Pilih lokasi</h2>
                        <p>Klik salah satu cabang untuk membuka pin dan melihat kamar tersedia.</p>
                    </div>

                    <div class="map-location-list">
                        <?php foreach ($locations as $i => $loc): ?>
                            <?php
                            $hasCoordinate = !empty($loc['latitude']) && !empty($loc['longitude']);
                            $mapUrl = $hasCoordinate
                                ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string) $loc['latitude'] . ',' . (string) $loc['longitude'])
                                : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string) ($loc['alamat'] ?? ''));
                            ?>
                            <article class="map-location-card <?php echo $hasCoordinate ? '' : 'is-muted'; ?>">
                                <button
                                    type="button"
                                    data-location-index="<?php echo e((string) $i); ?>"
                                    data-latitude="<?php echo e((string) ($loc['latitude'] ?? '')); ?>"
                                    data-longitude="<?php echo e((string) ($loc['longitude'] ?? '')); ?>"
                                    <?php echo $hasCoordinate ? '' : 'disabled'; ?>
                                >
                                    <span class="map-pin-icon"><i class="fa-solid fa-location-dot"></i></span>
                                    <span>
                                        <strong><?php echo e($loc['nama_kost']); ?></strong>
                                        <small><?php echo e(mb_strimwidth((string) ($loc['alamat'] ?? ''), 0, 70, '...')); ?></small>
                                    </span>
                                </button>
                                <div class="map-card-footer">
                                    <span><?php echo e((string) ($loc['kamar_tersedia'] ?? 0)); ?> kamar tersedia</span>
                                    <div>
                                        <a href="<?php echo e(url('/rooms?cabang=' . $loc['id_kost'])); ?>">Kamar</a>
                                        <a href="<?php echo e($mapUrl); ?>" target="_blank" rel="noopener">Rute</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </aside>

                <div class="map-canvas-card">
                    <div id="map" class="map-canvas"></div>
                    <div class="map-floating-tip">
                        <i class="fa-solid fa-circle-info"></i>
                        Klik pin untuk lihat foto, alamat, dan shortcut kamar.
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
$content = ob_get_clean();
$title = 'Peta Lokasi Kos - KosOnline';
$mapPageCss = e(asset('css/pages/map.css'));
$extraHead = <<<HTML
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="{$mapPageCss}">
HTML;

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$locationsJson = json_encode($locations, $jsonFlags);
$uploadBaseUrl = json_encode(url('/public/assets/images/uploads/'), $jsonFlags);
$roomSearchBaseUrl = json_encode(url('/rooms?cabang='), $jsonFlags);
$fallbackImage = json_encode(site_image('images.jpg'), $jsonFlags);

$extraScripts = <<<HTML
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            var locations = {$locationsJson};
            var uploadBaseUrl = {$uploadBaseUrl};
            var roomSearchBaseUrl = {$roomSearchBaseUrl};
            var fallbackImage = {$fallbackImage};
            var defaultCenter = [-6.690, 108.549];
            var firstMapped = locations.find(function (item) { return item.latitude && item.longitude; });
            var mapCenter = firstMapped ? [Number(firstMapped.latitude), Number(firstMapped.longitude)] : defaultCenter;
            var map = L.map('map', { scrollWheelZoom: false }).setView(mapCenter, 14);
            var markers = [];

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var markerIcon = L.divIcon({
                className: 'kos-map-marker',
                html: '<span><i class="fa-solid fa-house-chimney"></i></span>',
                iconSize: [42, 42],
                iconAnchor: [21, 42],
                popupAnchor: [0, -38]
            });

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, function (match) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    }[match];
                });
            }

            locations.forEach(function (item, index) {
                if (!item.latitude || !item.longitude) return;

                var marker = L.marker([Number(item.latitude), Number(item.longitude)], { icon: markerIcon }).addTo(map);
                var imageUrl = item.foto_kost ? uploadBaseUrl + encodeURIComponent(item.foto_kost) : fallbackImage;
                var popupHtml = ''
                    + '<div class="map-popup">'
                    + '<img src="' + imageUrl + '" alt="Foto ' + escapeHtml(item.nama_kost) + '">'
                    + '<strong>' + escapeHtml(item.nama_kost) + '</strong>'
                    + '<p>' + escapeHtml(item.alamat) + '</p>'
                    + '<span>' + escapeHtml(item.kamar_tersedia || 0) + ' kamar tersedia</span>'
                    + '<a href="' + roomSearchBaseUrl + encodeURIComponent(item.id_kost) + '">Lihat Kamar</a>'
                    + '</div>';

                marker.bindPopup(popupHtml);
                markers[index] = marker;
            });

            function setActive(index) {
                document.querySelectorAll('.map-location-card').forEach(function (card) {
                    card.classList.remove('active');
                });

                var button = document.querySelector('[data-location-index="' + index + '"]');
                if (button) {
                    button.closest('.map-location-card').classList.add('active');
                }
            }

            function flyToLocation(button) {
                var index = Number(button.dataset.locationIndex);
                var lat = Number(button.dataset.latitude);
                var lng = Number(button.dataset.longitude);

                if (!lat || !lng) return;

                setActive(index);
                map.flyTo([lat, lng], 17, { duration: 1.2 });
                if (markers[index]) {
                    setTimeout(function () {
                        markers[index].openPopup();
                    }, 900);
                }
            }

            document.querySelectorAll('[data-location-index]').forEach(function (button) {
                button.addEventListener('click', function () {
                    flyToLocation(button);
                });
            });

            if (firstMapped && markers[locations.indexOf(firstMapped)]) {
                setActive(locations.indexOf(firstMapped));
            }
        })();
    </script>
HTML;

require base_path('app/Views/layouts/public.php');
?>
