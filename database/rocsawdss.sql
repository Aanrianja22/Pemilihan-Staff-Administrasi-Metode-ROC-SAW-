-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 13 Jun 2025 pada 15.48
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rocsawdss`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `alternatives`
--

CREATE TABLE `alternatives` (
  `id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `alternatives`
--

INSERT INTO `alternatives` (`id`, `project_name`, `name`, `created_at`, `updated_at`) VALUES
(4, 'penerimaan karyawan.test', 'A', '2025-06-11 14:31:22', '2025-06-11 14:31:22'),
(5, 'penerimaan karyawan.test', 'B', '2025-06-11 14:31:40', '2025-06-11 14:31:40'),
(6, 'penerimaan karyawan.test', 'C', '2025-06-11 14:31:56', '2025-06-11 14:31:56'),
(7, 'bimaicikiwir', 'Bima', '2025-06-11 14:35:57', '2025-06-11 14:35:57'),
(8, 'bimaicikiwir', 'Galuh', '2025-06-11 14:36:12', '2025-06-11 14:36:12'),
(9, 'bimaicikiwir', 'Tsabit', '2025-06-11 14:36:27', '2025-06-11 14:36:27'),
(10, 'Penerimaan Staff Administrasi', 'A', '2025-06-11 15:55:48', '2025-06-11 15:55:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `alternative_values`
--

CREATE TABLE `alternative_values` (
  `id` int(11) NOT NULL,
  `alternative_id` int(11) NOT NULL,
  `criteria_id` int(11) NOT NULL,
  `value` decimal(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `alternative_values`
--

INSERT INTO `alternative_values` (`id`, `alternative_id`, `criteria_id`, `value`) VALUES
(13, 5, 6, 15.0000),
(14, 5, 5, 3.0000),
(15, 5, 4, 25.0000),
(16, 6, 6, 10.0000),
(17, 6, 5, 4.0000),
(18, 6, 4, 20.0000),
(19, 4, 6, 10.0000),
(20, 4, 5, 5.0000),
(21, 4, 4, 19.0000),
(23, 8, 7, 10.0000),
(24, 9, 7, 2.0000),
(26, 7, 7, 3.0000),
(27, 10, 8, 6.0000),
(28, 10, 9, 5.0000),
(29, 10, 12, 5.0000),
(30, 10, 10, 6.0000),
(31, 10, 13, 0.0000),
(32, 10, 11, 0.0000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `criteria`
--

CREATE TABLE `criteria` (
  `id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('benefit','cost') NOT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `sort_order` int(11) NOT NULL,
  `input_type` enum('numeric','ranges') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `criteria`
--

INSERT INTO `criteria` (`id`, `project_name`, `name`, `type`, `unit`, `sort_order`, `input_type`, `created_at`, `updated_at`) VALUES
(4, 'penerimaan karyawan.test', 'Umur', 'cost', 'Tahun', 3, 'numeric', '2025-06-11 14:29:42', '2025-06-11 14:30:40'),
(5, 'penerimaan karyawan.test', 'Pengalaman kerja', 'benefit', 'Tahun', 2, 'numeric', '2025-06-11 14:29:48', '2025-06-11 14:30:45'),
(6, 'penerimaan karyawan.test', 'Pendidikan', 'benefit', 'Pendidikan Terakhir', 1, 'ranges', '2025-06-11 14:30:21', '2025-06-11 14:30:45'),
(7, 'bimaicikiwir', 'Pengalaman kerja', 'benefit', 'Tahun', 1, 'numeric', '2025-06-11 14:35:48', '2025-06-11 14:35:48'),
(8, 'Penerimaan Staff Administrasi', 'Pengalaman Kerja', 'benefit', 'Tahun', 1, 'numeric', '2025-06-11 14:48:59', '2025-06-11 14:48:59'),
(9, 'Penerimaan Staff Administrasi', 'Pendidikan', 'benefit', 'Pendidikan Terakhir', 2, 'ranges', '2025-06-11 14:51:44', '2025-06-11 14:51:44'),
(10, 'Penerimaan Staff Administrasi', 'Award', 'benefit', 'Jumlah', 4, 'numeric', '2025-06-11 14:51:59', '2025-06-11 15:48:49'),
(11, 'Penerimaan Staff Administrasi', 'Sertifikat', 'benefit', 'Jumlah', 6, 'ranges', '2025-06-11 14:56:31', '2025-06-11 15:26:30'),
(12, 'Penerimaan Staff Administrasi', 'Pengalaman Magang', 'benefit', 'Jumlah', 3, 'ranges', '2025-06-11 15:05:04', '2025-06-11 15:12:54'),
(13, 'Penerimaan Staff Administrasi', 'Pengalaman Organisasi', 'benefit', 'Jumlah', 5, 'ranges', '2025-06-11 15:08:21', '2025-06-11 15:48:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `criteria_value_ranges`
--

CREATE TABLE `criteria_value_ranges` (
  `id` int(11) NOT NULL,
  `criteria_id` int(11) NOT NULL,
  `range_name` varchar(255) NOT NULL,
  `range_value` decimal(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `criteria_value_ranges`
--

INSERT INTO `criteria_value_ranges` (`id`, `criteria_id`, `range_name`, `range_value`) VALUES
(4, 6, 'S1', 10.0000),
(5, 6, 'S2', 15.0000),
(6, 6, 'S3', 20.0000),
(7, 9, 'SMA/SMK', 5.0000),
(8, 9, 'D3', 10.0000),
(9, 9, 'S1/D4', 15.0000),
(10, 9, 'S2', 20.0000),
(11, 9, 'S3', 25.0000),
(12, 11, '0 Sertifikat', 0.0000),
(13, 11, '1 Sertifikat', 5.0000),
(14, 11, '2-3 Sertifikat', 10.0000),
(15, 11, '4-5 Sertifikat', 15.0000),
(16, 11, '>5 Sertifikat', 20.0000),
(17, 12, 'Tidak Pernah', 0.0000),
(18, 12, '1 kali magang', 5.0000),
(19, 12, '2 kali magang', 10.0000),
(20, 12, '3 kali magang', 15.0000),
(21, 12, '≥4 kali magang', 20.0000),
(22, 13, 'Tidak Pernah', 0.0000),
(23, 13, '1 Organisasi', 5.0000),
(24, 13, '2-3 Organisasi', 10.0000),
(25, 13, '4-5 Organisasi', 15.0000),
(26, 13, '≥6 organisasi', 20.0000);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `alternatives`
--
ALTER TABLE `alternatives`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_name` (`project_name`,`name`);

--
-- Indeks untuk tabel `alternative_values`
--
ALTER TABLE `alternative_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `alternative_id` (`alternative_id`,`criteria_id`),
  ADD KEY `criteria_id` (`criteria_id`);

--
-- Indeks untuk tabel `criteria`
--
ALTER TABLE `criteria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_name` (`project_name`,`name`);

--
-- Indeks untuk tabel `criteria_value_ranges`
--
ALTER TABLE `criteria_value_ranges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criteria_id` (`criteria_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `alternatives`
--
ALTER TABLE `alternatives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `alternative_values`
--
ALTER TABLE `alternative_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `criteria`
--
ALTER TABLE `criteria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `criteria_value_ranges`
--
ALTER TABLE `criteria_value_ranges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `alternative_values`
--
ALTER TABLE `alternative_values`
  ADD CONSTRAINT `alternative_values_ibfk_1` FOREIGN KEY (`alternative_id`) REFERENCES `alternatives` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alternative_values_ibfk_2` FOREIGN KEY (`criteria_id`) REFERENCES `criteria` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `criteria_value_ranges`
--
ALTER TABLE `criteria_value_ranges`
  ADD CONSTRAINT `criteria_value_ranges_ibfk_1` FOREIGN KEY (`criteria_id`) REFERENCES `criteria` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
