<?php

declare(strict_types=1);

/**
 * Data rekening admin untuk pembayaran transfer bank manual.
 *
 * GANTI nomor rekening di bawah dengan rekening asli kos kamu.
 * File logo (opsional) ditaruh di public/assets/images/banks/ (mis. bca.png).
 * Kalau file logo belum ada, tampilan tetap aman (logo tidak dipaksa tampil).
 */
return [
    'manual_bca' => [
        'bank' => 'BCA',
        'no_rekening' => '1234567890', // GANTI dengan nomor rekening BCA asli
        'atas_nama' => 'Nanda Putra Hartono',
        'logo' => 'bca.png',
    ],
    'manual_bri' => [
        'bank' => 'BRI',
        'no_rekening' => '0987654321', // GANTI dengan nomor rekening BRI asli
        'atas_nama' => 'Nanda Putra Hartono',
        'logo' => 'bri.png',
    ],
    'manual_mandiri' => [
        'bank' => 'Mandiri',
        'no_rekening' => '1122334455', // GANTI dengan nomor rekening Mandiri asli
        'atas_nama' => 'Nanda Putra Hartono',
        'logo' => 'mandiri.png',
    ],
    'manual_cash' => [
        'bank' => 'Cash / Survei Langsung',
        'no_rekening' => null,
        'atas_nama' => null,
        'logo' => null,
    ],
];
