-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql113.infinityfree.com
-- Generation Time: Mar 16, 2026 at 09:01 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41092274_kosonline`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama_lengkap`) VALUES
(1, 'admin', 'admin123', 'Admin Utama');

-- --------------------------------------------------------

--
-- Table structure for table `kamar`
--

CREATE TABLE `kamar` (
  `id_kamar` int(11) NOT NULL,
  `id_kost` int(11) DEFAULT NULL,
  `nomor_kamar` varchar(10) NOT NULL,
  `lantai` int(11) DEFAULT NULL,
  `fasilitas` text DEFAULT NULL,
  `harga` decimal(10,2) DEFAULT NULL,
  `status` enum('Tersedia','Terisi','Perbaikan') DEFAULT 'Tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kamar`
--

INSERT INTO `kamar` (`id_kamar`, `id_kost`, `nomor_kamar`, `lantai`, `fasilitas`, `harga`, `status`) VALUES
(6, 6, 'Kamar 1', 1, 'Listrik dan Air', '800000.00', 'Terisi'),
(7, 6, 'Kamar 2', 1, 'Listrik dan Air', '800000.00', 'Tersedia'),
(8, 6, 'Kamar 3', 1, 'Listrik dan Air', '800000.00', 'Tersedia'),
(9, 6, 'Kamar 4', 1, 'Listrik dan Air', '800000.00', 'Tersedia'),
(10, 6, 'Kamar 5', 1, 'Listrik dan Air', '800000.00', 'Tersedia'),
(11, 7, 'Kamar 1', 1, 'Listrik dan Air', '800000.00', 'Terisi'),
(12, 7, 'Kamar 2', 1, 'Air dan Listrik', '800000.00', 'Tersedia'),
(13, 7, 'Kamar 3', 1, 'Air dan Listrik', '800000.00', 'Tersedia'),
(14, 8, 'Kamar 1', 1, 'Listrik dan Air', '800000.00', 'Terisi'),
(15, 8, 'Kamar 2', 1, 'Air dan Listrik', '800000.00', 'Tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `kost`
--

CREATE TABLE `kost` (
  `id_kost` int(11) NOT NULL,
  `nama_kost` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto_kost` varchar(255) DEFAULT NULL,
  `latitude` varchar(100) DEFAULT '-6.732022',
  `longitude` varchar(100) DEFAULT '108.552316'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kost`
--

INSERT INTO `kost` (`id_kost`, `nama_kost`, `alamat`, `deskripsi`, `foto_kost`, `latitude`, `longitude`) VALUES
(6, 'Hartono Kost Gg. Pandan', '78 Gg. Pandan, Jadimulya, Kec. Gunungjati, Kabupaten Cirebon, Jawa Barat 45151', 'Listrik\r\nAir', 'kossan pandan.jpg', '-6.688318788830963', '108.54816041698169'),
(7, 'Hartono Kost Gg Mushola', 'Jl. Jadimulya, Kec Gunung Jati, Kab, Cirebon, Jawa Barat', 'Listrik dan Air', 'kossan mushola.jpg', '-6.688269169337556', '108.55050844804742'),
(8, 'Hartono Kost Jl Al-Ihsan', 'Jl Al-ihsan, Desa Jadimulya, Kec. Gunung Jati, Kab. Cirebon, Jawa Barat', 'Air dan Listrik', 'kossan.jpg', '-6.691101642057537', ' 108.54850603327533');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_sewa` int(11) DEFAULT NULL,
  `bulan_tagihan` varchar(20) DEFAULT NULL,
  `tanggal_bayar` date DEFAULT NULL,
  `nominal` decimal(10,2) DEFAULT NULL,
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `status_verifikasi` enum('Menunggu','Lunas','Ditolak') DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`id_pembayaran`, `id_sewa`, `bulan_tagihan`, `tanggal_bayar`, `nominal`, `bukti_bayar`, `status_verifikasi`) VALUES
(10, 8, 'February 2026', '2026-02-04', '800000.00', NULL, 'Lunas'),
(11, 9, 'February 2026', '2026-02-05', '800000.00', NULL, 'Lunas'),
(12, 11, 'March 2026', '2026-03-11', '800000.00', NULL, 'Lunas');

-- --------------------------------------------------------

--
-- Table structure for table `pesan`
--

CREATE TABLE `pesan` (
  `id_pesan` int(11) NOT NULL,
  `nama_pengirim` varchar(100) NOT NULL,
  `email_pengirim` varchar(100) NOT NULL,
  `isi_pesan` text NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesan`
--

INSERT INTO `pesan` (`id_pesan`, `nama_pengirim`, `email_pengirim`, `isi_pesan`, `tanggal`) VALUES
(2, 'Kapitariyani Kimpo Ellen', 'danangputrahartono@gmail.com', 'Hallo ka, mau info harga kos dong?? ', '2026-02-09 08:20:37'),
(3, 'Nanda', 'nandaputrah235@gmail.com', 'Saya ingin memesan kos', '2026-02-20 06:50:45');

-- --------------------------------------------------------

--
-- Table structure for table `sewa`
--

CREATE TABLE `sewa` (
  `id_sewa` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_kamar` int(11) DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `jatuh_tempo` date NOT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `status_sewa` enum('Aktif','Berhenti') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sewa`
--

INSERT INTO `sewa` (`id_sewa`, `id_user`, `id_kamar`, `tanggal_masuk`, `jatuh_tempo`, `tanggal_keluar`, `status_sewa`) VALUES
(3, 4, 11, '2026-02-04', '2026-03-04', '2026-02-04', 'Berhenti'),
(4, 4, 11, '2026-02-04', '2026-03-04', '2026-02-04', 'Berhenti'),
(5, 4, 11, '2026-02-04', '2026-03-04', '2026-02-04', 'Berhenti'),
(6, 4, 11, '2026-02-04', '2026-03-04', '2026-02-04', 'Berhenti'),
(7, 4, 11, '2026-02-05', '2026-03-06', '2026-02-04', 'Berhenti'),
(8, 4, 11, '2026-02-04', '0000-00-00', '2026-02-05', 'Berhenti'),
(9, 4, 14, '2026-02-05', '0000-00-00', NULL, 'Aktif'),
(10, 5, 6, '2026-02-05', '0000-00-00', NULL, 'Aktif'),
(11, 7, 11, '2026-03-11', '0000-00-00', NULL, 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama_lengkap`, `email`, `password`, `no_hp`, `foto_profil`) VALUES
(4, 'nanda', 'nandaputrah235@gmail.com', 'nanda123', '085798842395', 'default.jpg'),
(5, 'danang', 'danangputrahartono@gmail.com', 'danang123', '085798842395', 'default.jpg'),
(6, 'Pramana', 'pramana123@gmail.com', '123', '9080808', 'default.jpg'),
(7, 'andra', 'andra@gmail.com', 'andra123', '090909', 'default.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`);

--
-- Indexes for table `kamar`
--
ALTER TABLE `kamar`
  ADD PRIMARY KEY (`id_kamar`),
  ADD KEY `id_kost` (`id_kost`);

--
-- Indexes for table `kost`
--
ALTER TABLE `kost`
  ADD PRIMARY KEY (`id_kost`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `id_sewa` (`id_sewa`);

--
-- Indexes for table `pesan`
--
ALTER TABLE `pesan`
  ADD PRIMARY KEY (`id_pesan`);

--
-- Indexes for table `sewa`
--
ALTER TABLE `sewa`
  ADD PRIMARY KEY (`id_sewa`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_kamar` (`id_kamar`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kamar`
--
ALTER TABLE `kamar`
  MODIFY `id_kamar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `kost`
--
ALTER TABLE `kost`
  MODIFY `id_kost` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `pesan`
--
ALTER TABLE `pesan`
  MODIFY `id_pesan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sewa`
--
ALTER TABLE `sewa`
  MODIFY `id_sewa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kamar`
--
ALTER TABLE `kamar`
  ADD CONSTRAINT `kamar_ibfk_1` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE;

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_sewa`) REFERENCES `sewa` (`id_sewa`);

--
-- Constraints for table `sewa`
--
ALTER TABLE `sewa`
  ADD CONSTRAINT `sewa_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `sewa_ibfk_2` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
