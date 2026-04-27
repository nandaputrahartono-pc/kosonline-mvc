<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/member.css')); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .badge.danger { background-color: #fee2e2; color: #991b1b; }
        .badge.warning { background-color: #ffedd5; color: #9a3412; }
        .badge.success { background-color: #dcfce7; color: #166534; }
        .card-detail {
            background: white; border-radius: 10px; padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; gap: 20px;
        }
        .card-detail img { width: 300px; height: 200px; object-fit: cover; border-radius: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn-save { background: #1e3a8a; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
    </style>
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
            <h2>USER PANEL</h2>
        </div>
        <ul class="menu">
            <li><a href="#" data-page="dashboard" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
            <li><a href="#" data-page="profil"><i class="fa-solid fa-user"></i> Profil</a></li>
            <li><a href="#" data-page="pesananku"><i class="fa-solid fa-bed"></i> Pesananku</a></li>
            <li><a href="#" data-page="pembayaran"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a></li>
        </ul>
        <div class="logout">
            <a href="<?php echo e(url('/logout')); ?>"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <main class="content">
        <div class="header-mobile">
            <button id="sidebar-toggle" class="btn-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h3>KosOnline</h3>
        </div>

        <section class="page active" id="dashboard">
            <h2 class="page-title">Halo, <?php echo e($user['nama_lengkap'] ?? 'User'); ?>!</h2>
            <p class="page-subtitle">Selamat datang kembali di kamar kostmu.</p>

            <div class="stats">
                <div class="stats-card">
                    <h3>Tagihan Bulan Ini</h3>
                    <span>Rp <?php echo number_format((float) $summary['harga'], 0, ',', '.'); ?></span>
                </div>
                <div class="stats-card">
                    <h3>Status Pembayaran</h3>
                    <span class="badge <?php echo e($summary['class_badge']); ?>" style="font-size: 1rem;"><?php echo e($summary['status_bayar']); ?></span>
                </div>
                <div class="stats-card">
                    <h3>Jatuh Tempo</h3>
                    <span><?php echo $summary['jatuh_tempo'] !== '-' ? e(date('d F Y', strtotime($summary['jatuh_tempo']))) : '-'; ?></span>
                </div>
                <div class="stats-card">
                    <h3>Kamar Saya</h3>
                    <span><?php echo e($summary['kamar_info']); ?></span>
                </div>
            </div>

            <div class="info-box" style="margin-top: 20px;">
                <h3><i class="fa-solid fa-bullhorn"></i> Informasi</h3>
                <p>Harap konfirmasi ke admin jika sudah melakukan pembayaran.</p>
                <?php if ($summary['status_bayar'] === 'Belum Bayar' && (float) $summary['harga'] > 0): ?>
                    <br>
                    <a href="https://wa.me/6287748703029?text=Halo%20Admin,%20saya%20mau%20bayar%20tagihan" target="_blank" style="background-color: #25D366; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block; font-weight: bold; margin-top: 5px; border: 1px solid #128C7E;">
                        <i class="fa-brands fa-whatsapp"></i> Bayar Sekarang
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <section class="page" id="profil">
            <h2 class="page-title">Profil Saya</h2>
            <p class="page-subtitle">Kelola informasi akun kamu</p>

            <div class="table-wrapper" style="max-width: 600px;">
                <form method="POST">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" value="<?php echo e($user['nama_lengkap'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo e($user['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>No. Handphone</label>
                        <input type="text" name="no_hp" value="<?php echo e($user['no_hp'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="text" name="password" value="<?php echo e($user['password'] ?? ''); ?>" required>
                    </div>
                    <button type="submit" name="update_profil" class="btn-save">Simpan Perubahan</button>
                </form>
            </div>
        </section>

        <section class="page" id="pesananku">
            <h2 class="page-title">Kamar Saat Ini</h2>
            <p class="page-subtitle">Detail kost yang sedang kamu tempati</p>

            <?php if ($rental !== null): ?>
                <div class="card-detail">
                    <div>
                        <?php
                        $imagePath = base_path('public/assets/images/uploads/' . $rental['foto_kost']);
                        $imageSource = (!empty($rental['foto_kost']) && file_exists($imagePath))
                            ? upload_asset($rental['foto_kost'])
                            : 'https://via.placeholder.com/300x200?text=No+Image';
                        ?>
                        <img src="<?php echo e($imageSource); ?>" alt="Kamar Kost">
                    </div>

                    <div style="flex: 1;">
                        <h2 style="margin-bottom: 10px; color: #1e3a8a;"><?php echo e($rental['nama_kost']); ?></h2>
                        <p style="color: #666; margin-bottom: 20px;"><i class="fa-solid fa-location-dot"></i> <?php echo e($rental['alamat']); ?></p>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                            <div><strong>Nomor Kamar:</strong><br> <?php echo e($rental['nomor_kamar']); ?> (Lt. <?php echo e($rental['lantai']); ?>)</div>
                            <div><strong>Harga Sewa:</strong><br> Rp <?php echo number_format((float) $rental['harga'], 0, ',', '.'); ?> / bulan</div>
                            <div><strong>Fasilitas:</strong><br> <?php echo e($rental['fasilitas']); ?></div>
                            <div><strong>Tanggal Masuk:</strong><br> <?php echo e($rental['tanggal_masuk']); ?></div>
                        </div>

                        <a href="https://wa.me/6287748703029?text=Halo%20Admin,%20saya%20ingin%20berhenti%20sewa" target="_blank" style="background:#ef4444; color:white; text-decoration:none; padding:10px 15px; border-radius:5px; display:inline-block; cursor:pointer;">
                            <i class="fa-brands fa-whatsapp"></i> Berhenti Sewa (Hubungi Admin)
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <p>Kamu belum menyewa kamar apapun. <a href="<?php echo e(url('/rooms')); ?>">Klik disini untuk melihat/memesan kamar.</a></p>
                </div>
            <?php endif; ?>
        </section>

        <section class="page" id="pembayaran">
            <h2 class="page-title">Riwayat Pembayaran</h2>
            <p class="page-subtitle">Catatan pembayaran tagihan kost kamu</p>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Bulan Tagihan</th>
                            <th>Tanggal Bayar</th>
                            <th>Nominal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rental !== null && $paymentHistory !== []): ?>
                            <?php foreach ($paymentHistory as $payment): ?>
                                <?php $statusLabel = $payment['status_verifikasi'] === 'Lunas' ? 'success' : 'warning'; ?>
                                <tr>
                                    <td><?php echo e($payment['bulan_tagihan']); ?></td>
                                    <td><?php echo e($payment['tanggal_bayar']); ?></td>
                                    <td>Rp <?php echo number_format((float) $payment['nominal'], 0, ',', '.'); ?></td>
                                    <td><span class="badge <?php echo e($statusLabel); ?>"><?php echo e($payment['status_verifikasi']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php elseif ($rental !== null): ?>
                            <tr><td colspan="4" style="text-align:center;">Belum ada riwayat pembayaran.</td></tr>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">Tidak ada data sewa.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="<?php echo e(asset('js/admin.js')); ?>"></script>
</body>
</html>
