-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 26 Nov 2025 pada 17.45
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_agenda_sekolah`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `kegiatan`
--

CREATE TABLE `kegiatan` (
  `id_kegiatan` varchar(20) NOT NULL,
  `nama_kegiatan` varchar(255) NOT NULL,
  `jadwal` datetime NOT NULL,
  `lokasi` varchar(255) NOT NULL,
  `tujuan` text NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `tipe_kegiatan` enum('wajib','opsional') NOT NULL DEFAULT 'opsional',
  `maks_peserta` int(11) DEFAULT NULL COMMENT 'NULL/0: Tidak ada batasan',
  `status_persetujuan` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `log_persetujuan` text DEFAULT NULL COMMENT 'Log persetujuan terakhir',
  `alasan_penolakan` text DEFAULT NULL,
  `diajukan_oleh` varchar(100) NOT NULL COMMENT 'Nama panitia yang mengajukan',
  `tanggal_pengajuan` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kegiatan`
--

INSERT INTO `kegiatan` (`id_kegiatan`, `nama_kegiatan`, `jadwal`, `lokasi`, `tujuan`, `gambar`, `tipe_kegiatan`, `maks_peserta`, `status_persetujuan`, `log_persetujuan`, `alasan_penolakan`, `diajukan_oleh`, `tanggal_pengajuan`) VALUES
('KEG-20251126164119-f', 'Upacara Bendera', '2025-11-28 22:40:00', 'Lapangan Sekolah', 'Menghormati jasa pahlawan pahlawan indonesia', 'kegiatan_1764171679_69271f9fc0719.jpg', 'wajib', NULL, 'pending', 'Diproses oleh eksgz pada 2025-11-26 16:46:36', '', 'eksgz2', '2025-11-26 22:41:19'),
('KEG-20251126164158-6', 'ClassMeet', '2025-12-04 22:41:00', 'SMKN 1 KOTA BEKASI', 'Lomba', 'kegiatan_1764171718_69271fc6cb471.jpg', 'opsional', 6, 'approved', 'Diproses oleh eksgz pada 2025-11-26 16:46:54', '', 'eksgz2', '2025-11-26 22:41:58'),
('KEG-20251126164245-5', 'Misi Element', '2025-12-13 22:42:00', 'SECRET TEMPLE', 'Dapetin EL MAJA', 'kegiatan_1764171765_69271ff58adbe.webp', 'wajib', NULL, 'approved', 'Diproses oleh eksgz pada 2025-11-26 16:48:29', '', 'eksgz2', '2025-11-26 22:42:45'),
('KEG-20251126170311-f', 'awd', '2025-11-26 23:03:00', 'wad', 'dwad', 'kegiatan_1764172991_692724bf97367.jpg', 'wajib', NULL, 'pending', NULL, NULL, 'eksgz2', '2025-11-26 23:03:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peserta_kegiatan`
--

CREATE TABLE `peserta_kegiatan` (
  `id_kegiatan` varchar(20) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `tanggal_daftar` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peserta_kegiatan`
--

INSERT INTO `peserta_kegiatan` (`id_kegiatan`, `id_siswa`, `tanggal_daftar`) VALUES
('KEG-20251126164158-6', 3, '2025-11-26 22:57:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nip` varchar(50) DEFAULT NULL COMMENT 'NIP untuk guru, NIS untuk siswa',
  `nama` varchar(100) NOT NULL,
  `role` enum('guru','panitia','siswa') NOT NULL,
  `kelas` varchar(50) DEFAULT NULL COMMENT 'Hanya untuk siswa',
  `foto_profil` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL DEFAULT '123'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nip`, `nama`, `role`, `kelas`, `foto_profil`, `password`) VALUES
(1, NULL, 'eksgz2', 'panitia', NULL, NULL, '123'),
(4, '', 'Raafi', 'panitia', '', NULL, '123'),
(5, '1234', 'Hasbi', 'guru', '', NULL, '123'),
(6, '1001', 'Andhika', 'siswa', 'XI RPL B', NULL, '123'),
(7, '1002', 'Rheindaru', 'siswa', 'XI RPL B', NULL, '123'),
(8, '1003', 'Revand', 'siswa', 'XI RPL B', NULL, '123'),
(9, '1004', 'Faiz', 'siswa', 'XI RPL B', NULL, '123'),
(10, '1005', 'Aji', 'siswa', 'XI RPL B', NULL, '123');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD PRIMARY KEY (`id_kegiatan`),
  ADD KEY `diajukan_oleh` (`diajukan_oleh`);

--
-- Indeks untuk tabel `peserta_kegiatan`
--
ALTER TABLE `peserta_kegiatan`
  ADD PRIMARY KEY (`id_kegiatan`,`id_siswa`),
  ADD KEY `id_siswa` (`id_siswa`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip_role` (`nip`,`role`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
