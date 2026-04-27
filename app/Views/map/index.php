<?php ob_start(); ?>
    <section class="map-section">
        <h1 class="map-title">Peta Persebaran Kost</h1>
        <p>Lihat lokasi kost kami yang tersebar di wilayah Cirebon</p>
        <br>
        <div id="map"></div>
    </section>
<?php
$content = ob_get_clean();
$title = 'Peta Lokasi - KosOnline';
$extraHead = <<<HTML
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        #map { height: 500px; width: 100%; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 1; }
        .map-section { padding: 40px 10%; text-align: center; }
        .map-title { margin-bottom: 20px; color: #1e3a8a; }
    </style>
HTML;
$locationsJson = json_encode($locations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$uploadBaseUrl = json_encode(url('/public/assets/images/uploads/'));
$roomSearchBaseUrl = json_encode(url('/rooms?cari='));
$extraScripts = <<<HTML
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([-6.705359, 108.555437], 13);
        var uploadBaseUrl = {$uploadBaseUrl};
        var roomSearchBaseUrl = {$roomSearchBaseUrl};

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var locations = {$locationsJson};

        locations.forEach(function (item) {
            if (!item.latitude || !item.longitude) {
                return;
            }

            var marker = L.marker([item.latitude, item.longitude]).addTo(map);
            marker.bindPopup(
                '<div style="text-align:center;">' +
                '<img src="' + uploadBaseUrl + encodeURIComponent(item.foto_kost) + '" style="width:100px; height:60px; object-fit:cover; border-radius:4px;"><br>' +
                '<b>' + item.nama_kost + '</b><br>' +
                '<span style="font-size:11px;">' + item.alamat + '</span><br>' +
                '<a href="' + roomSearchBaseUrl + encodeURIComponent(item.nama_kost) + '" style="color:blue; font-size:12px;">Lihat Kamar</a>' +
                '</div>'
            );
        });
    </script>
HTML;
require base_path('app/Views/layouts/public.php');
