<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KosOnline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin.css')); ?>">
</head>
<body>
    <?php if ($successMessage !== null): ?>
        <script>alert(<?php echo json_encode($successMessage); ?>);</script>
    <?php endif; ?>
    <?php if ($errorMessage !== null): ?>
        <script>alert(<?php echo json_encode($errorMessage); ?>);</script>
    <?php endif; ?>

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>ADMIN PANEL</h2>
        </div>
        <ul class="menu">
            <li><a href="#" data-page="dashboard" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
            <li><a href="#" data-page="data-kost"><i class="fa-solid fa-building"></i> Data Kost</a></li>
            <li><a href="#" data-page="data-kamar"><i class="fa-solid fa-bed"></i> Data Kamar</a></li>
            <li><a href="#" data-page="data-user"><i class="fa-solid fa-users"></i> Data User</a></li>
            <li><a href="#" data-page="pembayaran"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a></li>
            <li><a href="#" data-page="pesan-masuk"><i class="fa-solid fa-envelope"></i> Pesan Masuk</a></li>
            <li><a href="#" data-page="atur-lokasi"><i class="fa-solid fa-map-location-dot"></i> Atur Lokasi</a></li>
        </ul>
        <div class="logout">
            <a href="<?php echo e(url('/admin/logout')); ?>"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <main class="content">
        <div class="header-mobile">
            <button id="sidebar-toggle" class="btn-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h3>Admin Panel</h3>
        </div>

        <section class="page active" id="dashboard">
            <h2 class="page-title">Dashboard</h2>
            <p class="page-subtitle">Ringkasan statistik kost kamu</p>

            <div class="stats">
                <div class="stats-card">
                    <h3>Total Cabang Kost</h3>
                    <span><?php echo e($stats['total_kost']); ?></span>
                </div>
                <div class="stats-card">
                    <h3>Total Kamar</h3>
                    <span><?php echo e($stats['total_kamar']); ?></span>
                    <small style="color:#666; font-size:12px;">(Isi: <?php echo e($stats['terisi']); ?>, Kosong: <?php echo e($stats['kosong']); ?>)</small>
                </div>
                <div class="stats-card">
                    <h3>Penghuni Aktif</h3>
                    <span><?php echo e($stats['penghuni_aktif']); ?></span>
                </div>
                <div class="stats-card">
                    <h3>Belum Bayar (<?php echo e($billingMonth); ?>)</h3>
                    <span style="color: #ef4444;"><?php echo e($stats['belum_bayar']); ?></span>
                </div>
            </div>
        </section>

        <section class="page" id="data-kost">
            <h2 class="page-title">Data Kost</h2>
            <div class="table-header">
                <a href="<?php echo e(url('/admin/kost/create')); ?>" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah Kost</a>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Kost</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kosts as $kost): ?>
                            <tr>
                                <td><?php echo e($kost['nama_kost']); ?></td>
                                <td><?php echo e($kost['alamat']); ?></td>
                                <td>
                                    <a href="<?php echo e(url('/admin/kost/edit?id=' . $kost['id_kost'])); ?>" class="btn-edit"><i class="fa-solid fa-pen"></i></a>
                                    <a href="<?php echo e(url('/admin/kost/delete?id=' . $kost['id_kost'])); ?>" class="btn-delete" onclick="return confirm('Yakin hapus?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="page" id="data-kamar">
            <h2 class="page-title">Data Kamar</h2>
            <div class="table-header">
                <a href="<?php echo e(url('/admin/rooms/create')); ?>" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah Kamar</a>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>No. Kamar</th>
                            <th>Cabang</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <?php
                            $badgeClass = $room['status'] === 'Terisi' ? 'warning' : 'success';
                            $buttonClass = $room['status'] === 'Terisi' ? 'btn-delete' : 'btn-edit';
                            $buttonIcon = $room['status'] === 'Terisi' ? 'fa-user-xmark' : 'fa-user-check';
                            $buttonLabel = $room['status'] === 'Terisi' ? 'Kosongkan' : 'Isi Kamar';
                            $confirmMessage = $room['status'] === 'Terisi' ? 'Yakin ingin mengosongkan kamar ini?' : 'Ubah status menjadi Terisi?';
                            ?>
                            <tr>
                                <td><?php echo e($room['nomor_kamar']); ?></td>
                                <td><?php echo e($room['nama_kost']); ?></td>
                                <td>Rp <?php echo number_format((float) $room['harga'], 0, ',', '.'); ?></td>
                                <td><span class="badge <?php echo e($badgeClass); ?>"><?php echo e($room['status']); ?></span></td>
                                <td>
                                    <a href="<?php echo e(url('/admin/rooms/toggle-status?id=' . $room['id_kamar'] . '&status=' . urlencode($room['status']))); ?>" class="<?php echo e($buttonClass); ?>" style="margin-right: 5px;" onclick="return confirm('<?php echo e($confirmMessage); ?>')">
                                        <i class="fa-solid <?php echo e($buttonIcon); ?>"></i> <?php echo e($buttonLabel); ?>
                                    </a>

                                    <a href="<?php echo e(url('/admin/rooms/edit?id=' . $room['id_kamar'])); ?>" class="btn-edit"><i class="fa-solid fa-pen"></i></a>
                                    <a href="<?php echo e(url('/admin/rooms/delete?id=' . $room['id_kamar'])); ?>" class="btn-delete" onclick="return confirm('Hapus kamar?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="page" id="data-user">
            <h2 class="page-title">Data User</h2>
            <div class="table-header">
                <a href="<?php echo e(url('/admin/users/create')); ?>" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah User</a>
            </div>
            <div class="table-wrapper">
                <table>
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
                                    <a href="<?php echo e(url('/admin/users/edit?id=' . $user['id_user'])); ?>" class="btn-edit"><i class="fa-solid fa-pen"></i></a>
                                    <a href="<?php echo e(url('/admin/users/delete?id=' . $user['id_user'])); ?>" class="btn-delete" onclick="return confirm('Hapus user?')"><i class="fa-solid fa-trash"></i></a>
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
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Penghuni</th>
                            <th>Kamar</th>
                            <th>Bulan</th>
                            <th>Tagihan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($billings as $billing): ?>
                            <?php
                            $billingMonthUrl = urlencode($billingMonth);
                            if ($billing['status_verifikasi'] === 'Lunas') {
                                $badge = '<span class="badge success">Lunas</span>';
                                $actions = '<a href="' . url('/admin/payments/update?id=' . $billing['id_sewa'] . '&bulan=' . $billingMonthUrl . '&aksi=batal') . '" class="btn-delete" onclick="return confirm(\'Batalkan status bayar?\')"><i class="fa-solid fa-xmark"></i> Batal</a>';
                            } else {
                                if ($billing['status_verifikasi'] === 'Menunggu') {
                                    $badge = '<span class="badge warning" style="background:orange; color:white;">Menunggu Konfirmasi</span>';
                                } else {
                                    $badge = '<span class="badge warning">Belum Bayar</span>';
                                }
                                
                                $waMessage = 'Halo ' . $billing['nama_lengkap'] . ', tagihan kost bulan ' . $billingMonth . ' belum dibayar ya.';
                                $waLink = 'https://wa.me/' . str_replace('08', '628', (string) $billing['no_hp']) . '?text=' . rawurlencode($waMessage);
                                $actions = '<a href="' . url('/admin/payments/update?id=' . $billing['id_sewa'] . '&bulan=' . $billingMonthUrl . '&aksi=lunas&nominal=' . $billing['harga']) . '" class="btn-edit" style="background:#10b981; color:white; margin-right:5px;" onclick="return confirm(\'Konfirmasi lunas?\')"><i class="fa-solid fa-check"></i> Lunas</a>';
                                $actions .= '<a href="' . $waLink . '" target="_blank" class="btn-edit" style="background:#25D366; color:white;"><i class="fa-brands fa-whatsapp"></i> Tagih</a>';
                            }
                            ?>
                            <tr>
                                <td><?php echo e($billing['nama_lengkap']); ?></td>
                                <td><?php echo e($billing['nama_kost'] . ' - ' . $billing['nomor_kamar']); ?></td>
                                <td><?php echo e($billingMonth); ?></td>
                                <td>Rp <?php echo number_format((float) $billing['harga'], 0, ',', '.'); ?></td>
                                <td><?php echo $badge; ?></td>
                                <td><?php echo $actions; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="page" id="pesan-masuk">
            <h2 class="page-title">Pesan Masuk</h2>
            <div class="table-wrapper">
                <table>
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
                                    <a href="mailto:<?php echo e($message['email_pengirim']); ?>" class="btn-edit" style="background: #3b82f6; margin-bottom: 5px;"><i class="fa-solid fa-paper-plane"></i> Balas</a>
                                    <a href="<?php echo e(url('/admin/messages/delete?id=' . $message['id_pesan'])); ?>" class="btn-delete" onclick="return confirm('Hapus pesan?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="page" id="atur-lokasi">
            <h2 class="page-title">Atur Lokasi Kost</h2>
            <div class="table-wrapper">
                <table>
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
                                    <a href="<?php echo e(url('/admin/locations/edit?id=' . $location['id_kost'])); ?>" class="btn-edit" style="background:#3b82f6;">
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

    <script src="<?php echo e(asset('js/admin.js')); ?>"></script>
</body>
</html>
