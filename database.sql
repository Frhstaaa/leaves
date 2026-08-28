-- ========================================================
-- SGIN Leaves Application Database Dump
-- Ready for Import via phpMyAdmin (cPanel)
-- Database: sginco_dbleav_fix
-- Generated: 2026-08-28 02:46:58
-- ========================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for `departments`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `approver_1_id` bigint(20) unsigned DEFAULT NULL,
  `approver_2_id` bigint(20) unsigned DEFAULT NULL,
  `approval_type` varchar(255) NOT NULL DEFAULT '3_tier',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`),
  KEY `departments_approver_1_id_foreign` (`approver_1_id`),
  KEY `departments_approver_2_id_foreign` (`approver_2_id`),
  CONSTRAINT `departments_approver_1_id_foreign` FOREIGN KEY (`approver_1_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_approver_2_id_foreign` FOREIGN KEY (`approver_2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `departments`
INSERT INTO `departments` (`id`, `name`, `code`, `manager_id`, `approver_1_id`, `approver_2_id`, `approval_type`, `description`, `created_at`, `updated_at`) VALUES
('1', 'INSPECTION', 'DEPT-INS', NULL, NULL, NULL, '3_tier', NULL, '2026-08-20 09:13:13', '2026-08-27 09:23:19'),
('2', 'HRD & PGA', 'DEPT-HRD', NULL, NULL, NULL, '1_tier', NULL, '2026-08-20 09:13:13', '2026-08-27 09:02:24'),
('3', 'ACCOUNTING, FINANCE & TAX', 'DEPT-FIN', NULL, NULL, NULL, '1_tier', NULL, '2026-08-20 09:13:13', '2026-08-27 08:37:20'),
('4', 'BOD', 'DEPT-BOD', NULL, NULL, NULL, '1_tier', NULL, '2026-08-20 09:13:13', '2026-08-27 08:51:02'),
('5', 'PPIC & WAREHOUSE', 'DEPT-WHS', NULL, NULL, NULL, '1_tier', NULL, '2026-08-27 08:53:10', '2026-08-27 08:53:10'),
('6', 'PRODUCTION - FORGING', 'DEPT-PFG', NULL, NULL, NULL, '2_tier', NULL, '2026-08-27 08:55:29', '2026-08-27 08:55:29'),
('7', 'PRODUCTION - MACHINING', 'DEPT-PMC', NULL, NULL, NULL, '3_tier', NULL, '2026-08-27 08:56:13', '2026-08-27 09:03:57'),
('8', 'QA/QC', 'DEPT-QAQC', NULL, NULL, NULL, '2_tier', NULL, '2026-08-27 09:23:05', '2026-08-27 09:23:05'),
('9', 'Information Technology', 'DEPT-IT', NULL, NULL, NULL, '3_tier', NULL, '2026-08-27 14:28:09', '2026-08-27 14:28:09'),
('10', 'Operations & Supply', 'DEPT-OPS', NULL, NULL, NULL, '3_tier', NULL, '2026-08-27 14:28:09', '2026-08-27 14:28:09');

-- --------------------------------------------------------
-- Table structure for `failed_jobs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `leave_categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `leave_categories`;
CREATE TABLE `leave_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `unit_type` varchar(255) NOT NULL DEFAULT 'hari',
  `requires_attachment` tinyint(1) NOT NULL DEFAULT 0,
  `deducts_quota` tinyint(1) NOT NULL DEFAULT 0,
  `default_quota` int(11) NOT NULL DEFAULT 12,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `leave_categories`
INSERT INTO `leave_categories` (`id`, `name`, `unit_type`, `requires_attachment`, `deducts_quota`, `default_quota`, `description`, `created_at`, `updated_at`) VALUES
('1', 'Cuti Tahunan', 'hari', '0', '1', '12', 'Cuti tahunan reguler karyawan (12 hari/tahun).', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('2', 'Cuti Haid', 'hari', '0', '1', '2', 'Izin khusus haid hari pertama/kedua untuk karyawan perempuan.', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('3', 'Sakit (Dengan Surat Dokter)', 'hari', '1', '0', '14', 'Ketidakhadiran karena sakit disertai bukti surat keterangan dokter.', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('4', 'Sakit (Tanpa Surat Dokter)', 'hari', '0', '0', '3', 'Ketidakhadiran karena sakit ringan tanpa surat dokter.', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('5', 'Sakit Karena Kecelakaan Kerja', 'hari', '1', '0', '14', 'Ketidakhadiran karena kecelakaan pada saat menjalankan tugas pekerjaan.', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('6', 'Ijin tidak masuk karena Suami/Istri/Anak/Orang tua/Mertua/Saudara Kandung Meninggal/ Istri Melahirkan', 'hari', '0', '0', '3', 'Izin khusus musibah keluarga inti atau anggota keluarga melahirkan.', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('7', 'Mangkir', 'hari', '0', '0', '0', 'Ketidakhadiran kerja tanpa pemberitahuan/izin sah.', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('8', 'Ijin Datang terlambat (Kurang dari 4 jam)', 'jam', '0', '0', '24', 'Izin keterlambatan masuk kantor kurang dari 4 jam.', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('9', 'Ijin Datang terlambat (Lebih dari 4 jam)', 'jam', '0', '0', '24', 'Izin keterlambatan masuk kantor lebih dari 4 jam.', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('10', 'Pulang lebih cepat tanpa ijin', 'jam', '0', '0', '0', 'Meninggalkan pekerjaan sebelum jam pulang tanpa persetujuan.', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('11', 'Ijin Tidak Masuk Kerja Tanpa Menerima Upah', 'hari', '0', '0', '0', 'Izin tidak masuk kerja di luar hak cuti yang memotong upah harian.', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('12', 'Izin Meninggalkan Pekerjaan (Jam)', 'jam', '0', '0', '24', 'Izin keluar kantor untuk urusan mendesak hitungan jam.', '2026-08-20 09:13:14', '2026-08-20 09:13:14');

-- --------------------------------------------------------
-- Table structure for `leave_quotas`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `leave_quotas`;
CREATE TABLE `leave_quotas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `year` int(11) NOT NULL,
  `total_quota` int(11) NOT NULL DEFAULT 12,
  `used_quota` int(11) NOT NULL DEFAULT 0,
  `remaining_quota` int(11) NOT NULL DEFAULT 12,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lq_user_year` (`user_id`,`year`),
  CONSTRAINT `leave_quotas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `leave_quotas`
INSERT INTO `leave_quotas` (`id`, `user_id`, `year`, `total_quota`, `used_quota`, `remaining_quota`, `created_at`, `updated_at`) VALUES
('6', '6', '2026', '99', '0', '99', '2026-08-20 09:13:14', '2026-08-25 18:22:15'),
('9', '40', '2026', '12', '0', '12', '2026-08-27 10:38:29', '2026-08-27 10:49:49'),
('10', '45', '2026', '12', '0', '12', '2026-08-27 12:11:52', '2026-08-27 12:11:52'),
('11', '46', '2026', '12', '0', '12', '2026-08-27 12:11:52', '2026-08-27 12:11:52'),
('12', '47', '2026', '12', '0', '12', '2026-08-27 12:11:52', '2026-08-27 12:11:52'),
('13', '48', '2026', '12', '0', '12', '2026-08-27 12:11:52', '2026-08-27 12:11:52'),
('14', '49', '2026', '12', '0', '12', '2026-08-27 12:11:52', '2026-08-27 12:11:52'),
('15', '50', '2026', '12', '0', '12', '2026-08-27 12:11:52', '2026-08-27 12:11:52'),
('16', '51', '2026', '12', '0', '12', '2026-08-27 12:11:52', '2026-08-27 12:11:52'),
('17', '52', '2026', '12', '0', '12', '2026-08-27 12:11:52', '2026-08-27 12:11:52'),
('18', '53', '2026', '12', '0', '12', '2026-08-27 12:11:52', '2026-08-27 12:11:52'),
('19', '54', '2026', '12', '0', '12', '2026-08-27 12:11:52', '2026-08-27 12:11:52'),
('20', '55', '2026', '12', '0', '12', '2026-08-27 12:11:52', '2026-08-27 12:11:52'),
('21', '56', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('22', '57', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('23', '58', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('24', '59', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('25', '60', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('26', '61', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('27', '62', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('28', '63', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('29', '64', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('30', '65', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('31', '66', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('32', '67', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('33', '68', '2026', '12', '0', '12', '2026-08-27 12:11:53', '2026-08-27 12:11:53'),
('34', '69', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('35', '70', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('36', '71', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('37', '72', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('38', '73', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('39', '74', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('40', '75', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('41', '76', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('42', '77', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('43', '78', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('44', '79', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('45', '80', '2026', '12', '0', '12', '2026-08-27 12:11:54', '2026-08-27 12:11:54'),
('46', '81', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55'),
('47', '82', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55'),
('48', '83', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55'),
('49', '84', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55'),
('50', '85', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55'),
('51', '86', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55'),
('52', '87', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55'),
('53', '88', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55'),
('54', '89', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55'),
('55', '90', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55'),
('56', '91', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55'),
('57', '92', '2026', '12', '0', '12', '2026-08-27 12:11:55', '2026-08-27 12:11:55');
INSERT INTO `leave_quotas` (`id`, `user_id`, `year`, `total_quota`, `used_quota`, `remaining_quota`, `created_at`, `updated_at`) VALUES
('58', '93', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('59', '94', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('60', '95', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('61', '96', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('62', '97', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('63', '98', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('64', '99', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('65', '100', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('66', '101', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('67', '102', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('68', '103', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('69', '104', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('70', '105', '2026', '12', '0', '12', '2026-08-27 12:11:56', '2026-08-27 12:11:56'),
('71', '106', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('72', '107', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('73', '108', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('74', '109', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('75', '110', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('76', '111', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('77', '112', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('78', '113', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('79', '114', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('80', '115', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('81', '116', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('82', '117', '2026', '12', '0', '12', '2026-08-27 12:11:57', '2026-08-27 12:11:57'),
('83', '118', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('84', '119', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('85', '120', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('86', '121', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('87', '122', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('88', '123', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('89', '124', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('90', '125', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('91', '126', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('92', '127', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('93', '128', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('94', '129', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('95', '130', '2026', '12', '0', '12', '2026-08-27 12:11:58', '2026-08-27 12:11:58'),
('96', '131', '2026', '12', '0', '12', '2026-08-27 12:11:59', '2026-08-27 12:11:59'),
('97', '132', '2026', '12', '0', '12', '2026-08-27 12:11:59', '2026-08-27 12:11:59'),
('98', '133', '2026', '12', '0', '12', '2026-08-27 12:11:59', '2026-08-27 12:11:59'),
('99', '134', '2026', '12', '0', '12', '2026-08-27 12:11:59', '2026-08-27 12:11:59'),
('100', '135', '2026', '12', '0', '12', '2026-08-27 12:11:59', '2026-08-27 12:11:59'),
('101', '136', '2026', '12', '0', '12', '2026-08-27 12:11:59', '2026-08-27 12:11:59'),
('102', '137', '2026', '12', '0', '12', '2026-08-27 12:11:59', '2026-08-27 12:11:59'),
('103', '138', '2026', '12', '0', '12', '2026-08-27 12:11:59', '2026-08-27 12:11:59'),
('104', '139', '2026', '12', '0', '12', '2026-08-27 12:11:59', '2026-08-27 12:11:59'),
('105', '140', '2026', '12', '0', '12', '2026-08-27 12:11:59', '2026-08-27 12:11:59'),
('106', '141', '2026', '12', '0', '12', '2026-08-27 12:11:59', '2026-08-27 12:11:59'),
('107', '144', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09');
INSERT INTO `leave_quotas` (`id`, `user_id`, `year`, `total_quota`, `used_quota`, `remaining_quota`, `created_at`, `updated_at`) VALUES
('108', '145', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09'),
('109', '146', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09'),
('110', '147', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09'),
('111', '148', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09'),
('112', '149', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09'),
('113', '150', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09'),
('114', '151', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09'),
('115', '152', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09'),
('116', '153', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09'),
('117', '154', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09'),
('118', '155', '2026', '12', '0', '12', '2026-08-27 12:15:09', '2026-08-27 12:15:09'),
('119', '156', '2026', '12', '0', '12', '2026-08-27 12:15:10', '2026-08-27 12:15:10'),
('120', '157', '2026', '12', '0', '12', '2026-08-27 12:15:10', '2026-08-27 12:15:10'),
('121', '158', '2026', '12', '0', '12', '2026-08-27 12:15:10', '2026-08-27 12:15:10'),
('122', '159', '2026', '12', '0', '12', '2026-08-27 12:15:10', '2026-08-27 12:15:10');

-- --------------------------------------------------------
-- Table structure for `leave_requests`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(255) NOT NULL,
  `submission_type` varchar(255) NOT NULL DEFAULT 'PERMOHONAN',
  `approval_agreed` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` bigint(20) unsigned NOT NULL,
  `leave_category_id` bigint(20) unsigned NOT NULL,
  `unit` varchar(255) NOT NULL DEFAULT 'hari',
  `amount` decimal(8,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `current_stage` varchar(255) NOT NULL DEFAULT 'hrd',
  `approved_by_1` bigint(20) unsigned DEFAULT NULL,
  `approval_1_note` text DEFAULT NULL,
  `approved_1_at` timestamp NULL DEFAULT NULL,
  `approved_by_2` bigint(20) unsigned DEFAULT NULL,
  `approval_2_note` text DEFAULT NULL,
  `approved_2_at` timestamp NULL DEFAULT NULL,
  `approved_by_hrd` bigint(20) unsigned DEFAULT NULL,
  `approval_hrd_note` text DEFAULT NULL,
  `approved_hrd_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approval_note` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_requests_request_number_unique` (`request_number`),
  KEY `leave_requests_leave_category_id_foreign` (`leave_category_id`),
  KEY `leave_requests_approved_by_foreign` (`approved_by`),
  KEY `leave_requests_approved_by_1_foreign` (`approved_by_1`),
  KEY `leave_requests_approved_by_2_foreign` (`approved_by_2`),
  KEY `leave_requests_approved_by_hrd_foreign` (`approved_by_hrd`),
  KEY `idx_lr_user_status` (`user_id`,`status`),
  KEY `idx_lr_stage_status` (`current_stage`,`status`),
  KEY `idx_lr_dates` (`start_date`,`end_date`),
  CONSTRAINT `leave_requests_approved_by_1_foreign` FOREIGN KEY (`approved_by_1`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_approved_by_2_foreign` FOREIGN KEY (`approved_by_2`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_approved_by_hrd_foreign` FOREIGN KEY (`approved_by_hrd`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_leave_category_id_foreign` FOREIGN KEY (`leave_category_id`) REFERENCES `leave_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `leave_requests`
INSERT INTO `leave_requests` (`id`, `request_number`, `submission_type`, `approval_agreed`, `user_id`, `leave_category_id`, `unit`, `amount`, `start_date`, `end_date`, `reason`, `attachment_path`, `attachment_name`, `status`, `current_stage`, `approved_by_1`, `approval_1_note`, `approved_1_at`, `approved_by_2`, `approval_2_note`, `approved_2_at`, `approved_by_hrd`, `approval_hrd_note`, `approved_hrd_at`, `approved_by`, `approval_note`, `approved_at`, `created_at`, `updated_at`) VALUES
('1', 'LV-20260821-0001', 'PERMOHONAN', '1', '6', '1', 'hari', '1.00', '2026-08-21', '2026-08-21', 'tess', NULL, NULL, 'rejected', 'completed', NULL, NULL, NULL, NULL, NULL, '2026-08-21 16:06:55', NULL, NULL, NULL, '6', 'batal', '2026-08-25 18:13:55', '2026-08-21 15:56:44', '2026-08-25 18:13:55'),
('5', 'LV-20260827-0001', 'PERMOHONAN', '1', '59', '1', 'hari', '1.00', '2026-08-28', '2026-08-28', 'Keperluan keluarga', NULL, NULL, 'pending', 'hrd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-27 13:45:50', '2026-08-27 13:45:50'),
('6', 'LV-20260827-0002', 'PERMOHONAN', '1', '6', '1', 'hari', '1.00', '2026-08-27', '2026-08-27', 'test', 'attachments/leave_requests/doc_6a9083197be0a_1787855641.pdf', 'TiyasF (1).pdf', 'pending', 'hrd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-27 18:34:02', '2026-08-27 18:34:02');

-- --------------------------------------------------------
-- Table structure for `migrations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `migrations`
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
('1', '2014_10_12_000000_create_users_table', '1'),
('2', '2014_10_12_100000_create_password_reset_tokens_table', '1'),
('3', '2019_08_19_000000_create_failed_jobs_table', '1'),
('4', '2019_12_14_000001_create_personal_access_tokens_table', '1'),
('5', '2024_01_01_000001_create_departments_table', '1'),
('6', '2024_01_01_000003_create_leave_categories_table', '1'),
('7', '2024_01_01_000004_create_leave_quotas_table', '1'),
('8', '2024_01_01_000005_create_leave_requests_table', '1'),
('9', '2024_01_01_000006_create_permission_tables', '1'),
('10', '2024_01_01_000007_add_multi_tier_approval_to_users_and_leave_requests', '2'),
('11', '2024_01_01_000008_add_approval_settings_to_departments_table', '3'),
('12', '2024_01_01_000009_create_payslips_table', '4'),
('13', '2024_01_01_000010_create_settings_table', '5'),
('14', '2024_01_01_000011_sync_all_leave_quotas', '6'),
('15', '2024_01_01_000012_add_performance_indexes', '7'),
('16', '2024_01_01_000013_add_deducts_quota_to_leave_categories_table', '8'),
('17', '2024_01_01_000014_add_biodata_fields_to_users_table', '9');

-- --------------------------------------------------------
-- Table structure for `model_has_permissions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `model_has_roles`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `model_has_roles`
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
('1', 'App\\Models\\User', '6'),
('2', 'App\\Models\\User', '40'),
('3', 'App\\Models\\User', '45'),
('3', 'App\\Models\\User', '46'),
('3', 'App\\Models\\User', '52'),
('3', 'App\\Models\\User', '57'),
('3', 'App\\Models\\User', '58'),
('3', 'App\\Models\\User', '60'),
('3', 'App\\Models\\User', '62'),
('4', 'App\\Models\\User', '47'),
('4', 'App\\Models\\User', '48'),
('4', 'App\\Models\\User', '49'),
('4', 'App\\Models\\User', '50'),
('4', 'App\\Models\\User', '51'),
('4', 'App\\Models\\User', '53'),
('4', 'App\\Models\\User', '54'),
('4', 'App\\Models\\User', '55'),
('4', 'App\\Models\\User', '56'),
('4', 'App\\Models\\User', '59'),
('4', 'App\\Models\\User', '61'),
('4', 'App\\Models\\User', '63'),
('4', 'App\\Models\\User', '64'),
('4', 'App\\Models\\User', '65'),
('4', 'App\\Models\\User', '66'),
('4', 'App\\Models\\User', '67'),
('4', 'App\\Models\\User', '68'),
('4', 'App\\Models\\User', '69'),
('4', 'App\\Models\\User', '70'),
('4', 'App\\Models\\User', '71'),
('4', 'App\\Models\\User', '72'),
('4', 'App\\Models\\User', '73'),
('4', 'App\\Models\\User', '74'),
('4', 'App\\Models\\User', '75'),
('4', 'App\\Models\\User', '76'),
('4', 'App\\Models\\User', '77'),
('4', 'App\\Models\\User', '78'),
('4', 'App\\Models\\User', '79'),
('4', 'App\\Models\\User', '80'),
('4', 'App\\Models\\User', '81'),
('4', 'App\\Models\\User', '82'),
('4', 'App\\Models\\User', '83'),
('4', 'App\\Models\\User', '84'),
('4', 'App\\Models\\User', '85'),
('4', 'App\\Models\\User', '86'),
('4', 'App\\Models\\User', '87'),
('4', 'App\\Models\\User', '88'),
('4', 'App\\Models\\User', '89'),
('4', 'App\\Models\\User', '90'),
('4', 'App\\Models\\User', '91'),
('4', 'App\\Models\\User', '92');
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
('4', 'App\\Models\\User', '93'),
('4', 'App\\Models\\User', '94'),
('4', 'App\\Models\\User', '95'),
('4', 'App\\Models\\User', '96'),
('4', 'App\\Models\\User', '97'),
('4', 'App\\Models\\User', '98'),
('4', 'App\\Models\\User', '99'),
('4', 'App\\Models\\User', '100'),
('4', 'App\\Models\\User', '101'),
('4', 'App\\Models\\User', '102'),
('4', 'App\\Models\\User', '103'),
('4', 'App\\Models\\User', '104'),
('4', 'App\\Models\\User', '105'),
('4', 'App\\Models\\User', '106'),
('4', 'App\\Models\\User', '107'),
('4', 'App\\Models\\User', '108'),
('4', 'App\\Models\\User', '109'),
('4', 'App\\Models\\User', '110'),
('4', 'App\\Models\\User', '111'),
('4', 'App\\Models\\User', '112'),
('4', 'App\\Models\\User', '113'),
('4', 'App\\Models\\User', '114'),
('4', 'App\\Models\\User', '115'),
('4', 'App\\Models\\User', '116'),
('4', 'App\\Models\\User', '117'),
('4', 'App\\Models\\User', '118'),
('4', 'App\\Models\\User', '119'),
('4', 'App\\Models\\User', '120'),
('4', 'App\\Models\\User', '121'),
('4', 'App\\Models\\User', '122'),
('4', 'App\\Models\\User', '123'),
('4', 'App\\Models\\User', '124'),
('4', 'App\\Models\\User', '125'),
('4', 'App\\Models\\User', '126'),
('4', 'App\\Models\\User', '127'),
('4', 'App\\Models\\User', '128'),
('4', 'App\\Models\\User', '129'),
('4', 'App\\Models\\User', '130'),
('4', 'App\\Models\\User', '131'),
('4', 'App\\Models\\User', '132'),
('4', 'App\\Models\\User', '133'),
('4', 'App\\Models\\User', '134'),
('4', 'App\\Models\\User', '135'),
('4', 'App\\Models\\User', '136'),
('4', 'App\\Models\\User', '137'),
('4', 'App\\Models\\User', '138'),
('4', 'App\\Models\\User', '139'),
('4', 'App\\Models\\User', '140'),
('4', 'App\\Models\\User', '141'),
('4', 'App\\Models\\User', '144');
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
('4', 'App\\Models\\User', '145'),
('4', 'App\\Models\\User', '146'),
('4', 'App\\Models\\User', '147'),
('4', 'App\\Models\\User', '148'),
('4', 'App\\Models\\User', '149'),
('4', 'App\\Models\\User', '150'),
('4', 'App\\Models\\User', '151'),
('4', 'App\\Models\\User', '152'),
('4', 'App\\Models\\User', '153'),
('4', 'App\\Models\\User', '154'),
('4', 'App\\Models\\User', '155'),
('4', 'App\\Models\\User', '156'),
('4', 'App\\Models\\User', '157'),
('4', 'App\\Models\\User', '158'),
('4', 'App\\Models\\User', '159');

-- --------------------------------------------------------
-- Table structure for `password_reset_tokens`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `payslips`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `payslips`;
CREATE TABLE `payslips` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `period_label` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `status` enum('published','draft') NOT NULL DEFAULT 'published',
  `viewed_at` timestamp NULL DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payslips_uploaded_by_foreign` (`uploaded_by`),
  KEY `payslips_user_id_year_month_index` (`user_id`,`year`,`month`),
  KEY `payslips_year_month_index` (`year`,`month`),
  CONSTRAINT `payslips_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payslips_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `payslips`
INSERT INTO `payslips` (`id`, `user_id`, `month`, `year`, `period_label`, `file_path`, `original_filename`, `file_size`, `notes`, `status`, `viewed_at`, `uploaded_by`, `created_at`, `updated_at`) VALUES
('6', '6', '8', '2026', 'Agustus 2026', 'payslips/2026/08/payslip_SA_001_2026_08_1787697474_rten.pdf', 'TiyasF (1).pdf', '239381', NULL, 'published', NULL, '6', '2026-08-25 22:25:37', '2026-08-25 22:37:56');

-- --------------------------------------------------------
-- Table structure for `permissions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `permissions`
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
('1', 'view-dashboard', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('2', 'create-leave-request', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('3', 'view-leave-history', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('4', 'manage-approvals', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('5', 'manage-employees', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('6', 'view-hrd-rekap', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('7', 'export-hrd-reports', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('8', 'manage-roles', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('9', 'manage-system-settings', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('10', 'create-employee', 'web', '2026-08-21 09:48:22', '2026-08-21 09:48:22'),
('11', 'edit-employee', 'web', '2026-08-21 09:48:22', '2026-08-21 09:48:22'),
('12', 'delete-employee', 'web', '2026-08-21 09:48:22', '2026-08-21 09:48:22'),
('13', 'view-leave-quota-report', 'web', '2026-08-21 09:48:22', '2026-08-21 09:48:22'),
('14', 'view-department-report', 'web', '2026-08-21 09:48:22', '2026-08-21 09:48:22'),
('15', 'manage-leave-categories', 'web', '2026-08-21 09:48:22', '2026-08-21 09:48:22'),
('16', 'delete-leave-request', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50'),
('17', 'view-payslips', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50'),
('18', 'download-payslips', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50'),
('19', 'view-monitoring-annual', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50'),
('20', 'view-monitoring-analytics', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50'),
('21', 'import-employees', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50'),
('22', 'manage-leave-quotas', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50'),
('23', 'manage-departments', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50'),
('24', 'manage-hrd-payslips', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50'),
('25', 'upload-hrd-payslips', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50'),
('26', 'delete-hrd-payslips', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50'),
('27', 'assign-user-roles', 'web', '2026-08-23 11:59:50', '2026-08-23 11:59:50');

-- --------------------------------------------------------
-- Table structure for `personal_access_tokens`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `role_has_permissions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `role_has_permissions`
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
('1', '1'),
('1', '2'),
('1', '3'),
('1', '4'),
('1', '5'),
('2', '1'),
('2', '2'),
('2', '3'),
('2', '4'),
('2', '5'),
('3', '1'),
('3', '2'),
('3', '3'),
('3', '4'),
('3', '5'),
('4', '1'),
('4', '2'),
('4', '3'),
('4', '5'),
('5', '1'),
('5', '2'),
('5', '5'),
('6', '1'),
('6', '2'),
('6', '5'),
('7', '1'),
('7', '2'),
('7', '5'),
('8', '1'),
('8', '2'),
('9', '1'),
('9', '2'),
('10', '1'),
('10', '2'),
('10', '5'),
('11', '1'),
('11', '2'),
('11', '5'),
('12', '1'),
('12', '2'),
('12', '5'),
('13', '1'),
('14', '1'),
('15', '1'),
('16', '1'),
('16', '2'),
('16', '3'),
('16', '4'),
('16', '5'),
('17', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
('17', '2'),
('17', '3'),
('17', '4'),
('17', '5'),
('18', '1'),
('18', '2'),
('18', '3'),
('18', '4'),
('18', '5'),
('19', '1'),
('19', '2'),
('19', '3'),
('19', '5'),
('20', '1'),
('20', '2'),
('20', '3'),
('20', '5'),
('21', '1'),
('21', '2'),
('21', '5'),
('22', '1'),
('22', '2'),
('22', '5'),
('23', '1'),
('23', '2'),
('23', '5'),
('24', '1'),
('24', '2'),
('25', '1'),
('25', '2'),
('26', '1'),
('26', '2'),
('27', '1'),
('27', '2');

-- --------------------------------------------------------
-- Table structure for `roles`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `roles`
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
('1', 'superadmin', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('2', 'admin', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('3', 'manager', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('4', 'employee', 'web', '2026-08-20 09:13:14', '2026-08-20 09:13:14'),
('5', 'supervisior', 'web', '2026-08-27 09:28:47', '2026-08-27 09:28:47');

-- --------------------------------------------------------
-- Table structure for `settings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `settings`
INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
('1', 'app_name', 'PT. Sugiyama', '2026-08-21 09:07:06', '2026-08-21 09:07:06'),
('2', 'app_subname', 'Cuti & Ketidakhadiran', '2026-08-21 09:07:06', '2026-08-21 09:07:06'),
('3', 'company_name', 'PT. SGIN Indonesia', '2026-08-21 09:07:06', '2026-08-21 09:07:06'),
('4', 'company_address', 'Jl. Industri Raya No. 123, Kawasan Industri', '2026-08-21 09:07:06', '2026-08-21 09:07:06'),
('5', 'company_phone', '+62 21 8901234', '2026-08-21 09:07:06', '2026-08-21 09:07:06'),
('6', 'company_email', 'hrd@sgin.co.id', '2026-08-21 09:07:06', '2026-08-21 09:07:06'),
('7', 'theme_color', '#059669', '2026-08-21 09:07:06', '2026-08-21 09:07:06'),
('8', 'app_description', 'Sistem Informasi Manajemen Cuti, Ketidakhadiran, Izin, Sakit, Lembur, dan Distribusi Slip Gaji Karyawan Real-time.', '2026-08-21 09:07:06', '2026-08-21 09:07:06'),
('9', 'app_logo', 'logos/opt_6a8ddb96e629d_1787681686.webp', '2026-08-21 15:30:57', '2026-08-25 18:14:46'),
('10', 'app_pwa_icon', 'logos/pwa_icon_master_1787681686.png', '2026-08-21 15:30:57', '2026-08-25 18:14:46');

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nik` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'employee',
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `employee_status` varchar(255) DEFAULT NULL,
  `education` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `ktp_number` varchar(30) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `birth_place` varchar(255) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `phone_number` varchar(30) DEFAULT NULL,
  `ktp_address` text DEFAULT NULL,
  `domicile_address` text DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `mother_maiden_name` varchar(255) DEFAULT NULL,
  `kk_number` varchar(30) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `npwp` varchar(50) DEFAULT NULL,
  `bpjs_kesehatan_number` varchar(50) DEFAULT NULL,
  `bpjs_health_facility` varchar(255) DEFAULT NULL,
  `bpjs_ketenagakerjaan_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `vehicle_plate_number` varchar(30) DEFAULT NULL,
  `sim_number` varchar(50) DEFAULT NULL,
  `sim_valid_until` date DEFAULT NULL,
  `shoe_size` varchar(10) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `emergency_contact_address` text DEFAULT NULL,
  `spouse_name` varchar(255) DEFAULT NULL,
  `spouse_ktp_number` varchar(30) DEFAULT NULL,
  `spouse_birth_place` varchar(255) DEFAULT NULL,
  `spouse_birth_date` date DEFAULT NULL,
  `child_1_name` varchar(255) DEFAULT NULL,
  `child_2_name` varchar(255) DEFAULT NULL,
  `child_3_name` varchar(255) DEFAULT NULL,
  `is_profile_completed` tinyint(1) NOT NULL DEFAULT 0,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `approver_1_id` bigint(20) unsigned DEFAULT NULL,
  `approver_2_id` bigint(20) unsigned DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_nik_unique` (`nik`),
  KEY `users_approver_1_id_foreign` (`approver_1_id`),
  KEY `users_approver_2_id_foreign` (`approver_2_id`),
  CONSTRAINT `users_approver_1_id_foreign` FOREIGN KEY (`approver_1_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_approver_2_id_foreign` FOREIGN KEY (`approver_2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `users`
INSERT INTO `users` (`id`, `nik`, `name`, `email`, `email_verified_at`, `password`, `role`, `department_id`, `join_date`, `employee_status`, `education`, `position`, `contract_end_date`, `ktp_number`, `gender`, `birth_place`, `birth_date`, `phone_number`, `ktp_address`, `domicile_address`, `marital_status`, `mother_maiden_name`, `kk_number`, `blood_type`, `npwp`, `bpjs_kesehatan_number`, `bpjs_health_facility`, `bpjs_ketenagakerjaan_number`, `bank_name`, `bank_account_number`, `vehicle_plate_number`, `sim_number`, `sim_valid_until`, `shoe_size`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `emergency_contact_address`, `spouse_name`, `spouse_ktp_number`, `spouse_birth_place`, `spouse_birth_date`, `child_1_name`, `child_2_name`, `child_3_name`, `is_profile_completed`, `manager_id`, `approver_1_id`, `approver_2_id`, `avatar`, `remember_token`, `created_at`, `updated_at`) VALUES
('6', 'SA-001', 'Superadmin SGIN', 'admin@sugiyama.co.id', NULL, '$2y$10$Xu5ZHH25tKhtChIqMlfeEeXBWN8gDWganFVXrdOAMAvW7mVXj/fZq', 'superadmin', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, 'avatars/opt_6a90823abf855_1787855418.webp', 'gFI3TxaLaBVviAPBBIDJruV4v22zAx2SAPC0r9p4BCT9vU60coZELEyXSp4y', '2026-08-20 09:13:14', '2026-08-27 18:30:19'),
('40', 'EMP-2026-783', 'Admin HRD', 'HRD@sgin.co.id', NULL, '$2y$10$kDRb4eCAZGQ6MWEESaoGZeQvfqq/vbdWvft1QyYGZkt0r4PWgxzwm', 'admin', '2', NULL, 'Tetap', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, 'lyF2F5pjFiQnvLiAQZgnADftuILVUomlcrheHmeEDGTqSkx0J1SzGTUVUqt3', '2026-08-27 10:38:29', '2026-08-27 10:49:49'),
('45', 'SGIN0076', 'SYUKRI HAMDI', 'hamdikeanu@gmail.com', NULL, '$2y$10$94tNNsNhXjgMb71PzJYSTuRbjudn3Hv1M2exhq97HtIXiG4EOfLQ.', 'manager', '3', NULL, 'Tetap', NULL, 'Manager', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, 'etxK4EVpGe5MXO0FA7s0jRRnrGggKN7aFm9yXNhiOz6li3su1wkVcRo0iEnN', '2026-08-27 12:11:52', '2026-08-27 13:54:47'),
('46', 'SGIN0006', 'VEKY FRANKY LENGKONG', 'veckylengkong1278@gmail.com', NULL, '$2y$10$CIxoJHiqmTp/bNdeTYEoWuGe7FwKbogtib0wqck.zZIWtPtnRBv7e', 'manager', '7', NULL, 'Tetap', NULL, 'Manager', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:52', '2026-08-27 13:54:47'),
('47', 'SGIN0010', 'HALLY APRIANTO', 'hallyaprianto@gmail.com', NULL, '$2y$10$aVxcM8cq4M6eMrUPGm2N/OLsPXXbjt5EgDJMShiUp0m7h870sx0i2', 'employee', '7', NULL, 'Tetap', NULL, 'Senior Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:52', '2026-08-27 13:54:47'),
('48', 'SGIN0014', 'DESTI WAHYU RIANA', 'destiriana.dr@gmail.com', NULL, '$2y$10$jqFyDEI1XxU5WEZfky328O4RDm3g3v/qDLBzMWsJ09A3WWg1ucEve', 'employee', '8', NULL, 'Tetap', NULL, 'Senior Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:52', '2026-08-27 13:54:47'),
('49', 'SGIN0017', 'NENENG LESYI JAYANTI', 'nenenglesyi84@gmail.com', NULL, '$2y$10$pbIBkXnqzDqHfwdAkMSQu.vqDvAEDoUu81E/JyQdp7QPIncOJqa7a', 'employee', '1', NULL, 'Tetap', NULL, 'Senior Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:52', '2026-08-27 13:54:47'),
('50', 'SGIN0018', 'FAHMI FAUZI', 'fahmifauzi0820@gmail.com', NULL, '$2y$10$YNrw40ArIhCox8pX.gKJBuZBfsHXR8ZAYhfWps20fMzNdd0UNVdT6', 'employee', '7', NULL, 'Tetap', NULL, 'Sub Leader', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:52', '2026-08-27 13:54:47'),
('51', 'SGIN0020', 'SUTOYO', 'tyosgin@gmail.com', NULL, '$2y$10$k/r8bw3lURUJCNG6m7qNI.uRX7jFLuck28KdXkeA9HCVhNTC/rPSO', 'employee', '7', NULL, 'Tetap', NULL, 'Sub Leader', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:52', '2026-08-27 13:54:47'),
('52', 'SGIN0021', 'JIRMAN FIRMANSYAH', 'production@sgin.co.id', NULL, '$2y$10$pTW5T1J0.h2q.5yRWiLUFOpIqn7OyiONvBFC2Pzkl1gp55L.ncJeS', 'manager', '7', NULL, 'Tetap', NULL, 'Supervisor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:52', '2026-08-27 13:54:47'),
('53', 'SGIN0030', 'MUSTAGHFIROTUL', 'mustaghfirotul05@yahoo.com', NULL, '$2y$10$z.jtxuj8VFz2WH/APruqJeKiTLTPHfSyaFym8lNi70pJe9urvXkOK', 'employee', '1', NULL, 'Tetap', NULL, 'Senior Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:52', '2026-08-27 13:54:48'),
('54', 'SGIN0053', 'TIA SETIAMUKTI', 'tiasetiamukti@gmail.com', NULL, '$2y$10$lSNxA2P7oqbF57meZcfGReAqT0S5mua.1hyzY27Mp5O/VUJOaVRSS', 'employee', '7', NULL, 'Tetap', NULL, 'Senior Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:52', '2026-08-27 13:54:48'),
('55', 'SGIN0062', 'CECEP KUSMULYADIN', 'cecepkusmulyadi@gmail.com', NULL, '$2y$10$Nm3HYhzidYslSRUK9Tx4VeO.joK49kLR/1/Qqd94O72AB9V0RuLqW', 'employee', '7', NULL, 'Tetap', NULL, 'Senior Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:52', '2026-08-27 13:54:48'),
('56', 'SGIN0063', 'M. CHOLIQUL HAMZAH', 'HAMZ.ONGISNADE@GMAIL.COM', NULL, '$2y$10$v0qytPPEPvzn80E9Gn6SYe6UjOAMeHaWXsOCJggPnCZSV1HITgKRy', 'employee', '6', NULL, 'Tetap', NULL, 'Senior Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:53', '2026-08-27 13:54:48'),
('57', 'SGIN0065', 'PURJIYO', 'Purjiyo17@gmail.com', NULL, '$2y$10$pyF74E/6lWzAm2Q5wBfeROTchIBZWJMMofI57Pey7W4Sp8bE2rzfq', 'manager', '6', NULL, 'Tetap', NULL, 'Leader', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:53', '2026-08-27 13:54:48'),
('58', 'SGIN0067', 'INDRA DERMAWAN', 'reynara.dermawan@gmail.com', NULL, '$2y$10$1UJC8pdAOf/n.YbkbX8qWu5ffW84N1N/i.7CV04kC1Efe.JKe688i', 'manager', '5', NULL, 'Tetap', NULL, 'Supervisor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:53', '2026-08-27 13:54:48'),
('59', 'SGIN0073', 'HERPIANTA', 'herpianta1991@gmail.com', NULL, '$2y$10$b8CqbGM2iSx.7bZS2Su9WeiiMvKrNj8i0sY39EpgzP9ZFtK2.Q4m6', 'employee', '3', NULL, 'Tetap', NULL, 'Manager', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, 'w1QuVZLoRXgIzF59w5dXfpdm7bO6dsv7ziidnNbXQ0iTJSlTtHWGn361Yhqv', '2026-08-27 12:11:53', '2026-08-27 13:54:48'),
('60', 'SGIN0074', 'ADE SUPRIYADI', 'ade25supriyadi@gmail.com', NULL, '$2y$10$vjnIrTX80cXt3CcXK5krWOF0MGQzGsQK5bwGqkkpsZQxZe7iAzWA2', 'manager', '1', NULL, 'Tetap', NULL, 'Sub Leader', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:53', '2026-08-27 13:54:48'),
('61', 'SGIN0118', 'IRFAN SAEPULLAH', 'irfan.saepullah8@gmail.com', NULL, '$2y$10$5e7y4LyHpl0PW4/NMqDVI.grYEsCtYhaMq6F9w4sxb0a.XFbSajli', 'employee', '2', NULL, 'Tetap', NULL, 'Senior Officer*', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:53', '2026-08-27 13:54:48'),
('62', 'SGIN0120', 'ABDULLAH CHOIR', 'choir.qa@gmail.com', NULL, '$2y$10$p1SHSvZf9O0aKxYj8t99qOhByIkmEucfDRsRGwGXzVc593p65kCHW', 'manager', '8', NULL, 'Tetap', NULL, 'Supervisor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:53', '2026-08-27 13:54:48'),
('63', 'SGIN0151', 'DANI SUGIANTO', 'danisugianto30@gmail.com', NULL, '$2y$10$T1P4RDS4.s.0GrqbHLmNZ.ZZa1BIduG7WFGK66l0Y7lJTgpzIdiRu', 'employee', '6', NULL, 'Tetap', NULL, 'Sub Leader', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:53', '2026-08-27 13:54:48'),
('64', 'SGIN0190', 'BUDI NURROHMAN', 'budinurohman30@gmail.com', NULL, '$2y$10$NEtVdir4LZ6zWemV2pS6mOk/6NVFZ/Efmi8WAxZo1QN4nTila4oXa', 'employee', '8', NULL, 'Tetap', NULL, 'Senior Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:53', '2026-08-27 13:54:48'),
('65', 'SGIN0201', 'YOPI ADITIYA', 'ayopi304@gmail.com', NULL, '$2y$10$arGUE2lunPWIjD7N7ftnGe8dyXTdLt06obhZw0xqIUwt3CptTbIWS', 'employee', '3', NULL, 'Tetap', NULL, 'Manager', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, '79vt1C4tU9F3JPWaatHZrsUBoqb8hnZMbSPttnyUzSiobZQehd4r6yNIDw61', '2026-08-27 12:11:53', '2026-08-27 13:54:48'),
('66', 'SGIN0265', 'NARWAN GUNAWAN', 'narwangunawan1997@gmail.com', NULL, '$2y$10$2dn0hGIDb62dQZaVW0mDdO.OTBtirWNBh8JCU8ubza.la0TactOvK', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:53', '2026-08-27 13:54:49'),
('67', 'SGIN0267', 'BAYU SHEVA ANGGARA', 'bayusheva617@gmail.com', NULL, '$2y$10$k29XyN1SwmXSH/nH8saBHuP8XJe.jr3bhVvXlmQKJJPoK5D6qfen2', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:53', '2026-08-27 13:54:49'),
('68', 'SGIN0268', 'ADI ARMAN SAPUTRA', 'adiarmansaputra@gmail.com', NULL, '$2y$10$LRccSU.dbPPpmOdgUNniae7vH3dj08QZE3Qmq8Nh3Ud2nNusnttg6', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:53', '2026-08-27 13:54:49'),
('69', 'SGIN0269', 'ADE SABANI', 'msglowcibubur9@gmail.com', NULL, '$2y$10$rETpotCNhZZ5NjwOY4jx6utJUZ75BORNKwIbcm13bS39LD8isOeWS', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:49'),
('70', 'SGIN0273', 'AURELIA HAPPY BINTARA', 'happybintaraaurelia@gmail.com', NULL, '$2y$10$uqrJtaZbFNGaeh9mo6KrQe6BH5gYJtPlFXRxzGw1vY1hoy10PIUVi', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:49'),
('71', 'SGIN0272', 'ADE LUKMANA', 'adelukmana10@gmail.com', NULL, '$2y$10$1DuMWm2U7bOvqfFRx7ImtenpfG70UE3TtQQGShaIf6iMQVTWuHXQm', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:49'),
('72', 'SGIN0278', 'M. RIO ANDREAS', 'RIOANDREAS71@GMAIL.COM', NULL, '$2y$10$NUG4cd9d0rL/3sACPpROX.6p/Un2DWmwavitNGqM5xSe59jEe4Z.e', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:49'),
('73', 'SGIN0280', 'ASRI ASTUTI', 'astutiasri05@gmail.com', NULL, '$2y$10$NFjhU0d0NGqzqHh/86kGo.mz.Kmknv9cDh3WCVhtm6/nbyNhjs8f2', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:49'),
('74', 'SGIN0281', 'NENI KURNIATI', 'nenikurniati5@gmail.com', NULL, '$2y$10$crRsCtGZ.i5X5tMk7yKDMOCH6NqOj1aqTDcqt5XDpv6tYqqCKMRTu', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:49'),
('75', 'SGIN0284', 'ISMI FATMIYATI', 'ismifatmiyati06@gmail.com', NULL, '$2y$10$p5dDNPILNvohWPGsen8TneuU2/VihDdnhomSESOe0H1b/srWEip.G', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:49'),
('76', 'SGIN0286', 'HOPIPAH ROBIYANTI', 'HROBYANTI@GMAIL.COM', NULL, '$2y$10$PQJMO7Wm/xP0uVgOT6NMaOvrgSWakn8fKhM5QQMPpMIPFmNVI0/e6', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:49'),
('77', 'SGIN0287', 'SITI SOZA ASYIAH NAZWA', 'sozanazwa@gmail.com', NULL, '$2y$10$TrvIdvXPBtBo1K/dSbJnTOmfAoeulMYGxJdmY0NSjPX2B7M.zO9T2', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:49'),
('78', 'SGIN0288', 'FILIPO JEREMIA KARINDA', 'filjeremia01@gmail.com', NULL, '$2y$10$WCmbH7w5CWb1O.8lBESobOPIolHpcC5XFtKc2qdpT3GvywEvYD6oK', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:50'),
('79', 'SGIN0289', 'SIROJUDIN', 'sirojhudin22@gmail.com', NULL, '$2y$10$Sn3z4EGczLz.1h.JCVahIebiCFYWNjCpLC/iYjOzFtRYgfx57t26C', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:50'),
('80', 'SGIN0290', 'HAIDAR AL DIEN', 'ALDINGANTENG20@GMAIL.COM', NULL, '$2y$10$XkNUDedLNDiSmL.k3tWtdutOK5P.IIk2REm82ZL81r/5ZPXHjBvFq', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:54', '2026-08-27 13:54:50'),
('81', 'SGIN0291', 'FEBRIANSYAH', 'FEBRIANSYAHKUY19@GMAIL.COM', NULL, '$2y$10$xHOPBrjYfF3Dfzw2CJrP3uQODz6jLWD9r34K6FofeLBMzrop4v966', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:50'),
('82', 'SGIN0293', 'NOVIANTI SUGIANTO', 'noviantisugianto54@gmail.com', NULL, '$2y$10$p8kJD3lHwQ3lHpeHkwv7bOQNpBRc44MSL8f5le7JIHv7gjCzAaT/.', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:50'),
('83', 'SGIN0294', 'YOSI ARSITA DEWI', 'yosiars17@gmail.com', NULL, '$2y$10$Ez7oEcnLpd0FnD.A78y1VO4fNzOEP5470ujujlBOJPGs5vRz0TcOq', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:50'),
('84', 'SGIN0295', 'PRANISA SALSABILLA WICAKSANA', 'salsabillawicaksana@gmail.com', NULL, '$2y$10$Du9EyByVsaot36tn4stH4ePoClNurzM5Fq8v/NWBCbea/losLfEAm', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:50'),
('85', 'SGIN0298', 'AYU LUTFIUL AEN', 'AYULUTFIULAEN@GMAIL.COM', NULL, '$2y$10$2ZuReF2uT3YJOpA9kpTtBu6G83VOKoeX5uI0tQhQkwBjKwPefpspy', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:50'),
('86', 'SGIN0299', 'SELYAWATI', 'syawati468@gmail.com', NULL, '$2y$10$JtFYVidA/u9tSGFv3Z6F/.81ThIiyGIfNJZtB05efUCBT.SrrS7ye', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:50'),
('87', 'SGIN0300', 'MUHAMAD AKMAL RIZKI', 'CIASEMSUBANG97@GMAIL.COM', NULL, '$2y$10$7/Vb19mMSbKOFrhEFUd.YO1kLMad2VmrbAweBWIXQn1X.UmdZoe.e', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:50'),
('88', 'SGIN0301', 'MOHAMAD YUSUF SAEPUL BAHRI', 'YUSUFSAEPUL23@GMAIL.COM', NULL, '$2y$10$hrBBsFux1y8ntRnwacuDj.mfPYNBLaC8Z.ZJxKmchAcTX1s0lIRgq', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:50'),
('89', 'SGIN0302', 'MUHAMAD SAFRUDIN', 'muhamadsafrudin196@gmail.com', NULL, '$2y$10$MgGu8KaCgFRitMbeThold.aeLipsE.Id74A6gg6xGvu3vTzaA0LRy', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:50'),
('90', 'SGIN0303', 'YONATHAN FRANKLIN GERAL LENGKONG', 'YONATHANFRANGCLIN@GMAIL.COM', NULL, '$2y$10$1qtvI1TaEYPfLwhuiKZtke/q/J8B2/H7d467N9y/xnKJoTr3V7QWW', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:50'),
('91', 'SGIN0304', 'MUHAMAD FAJAR MAULANA', 'ledongmaulana@gmail.com', NULL, '$2y$10$vg/psi3CWVnJGOdZHDZ4w.eJvQcuZtQ.T8bB/5SH2iavYgNZTPyui', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:51'),
('92', 'SGIN0305', 'DIADANI GUSBIO LAKSMA', 'dgusbio@gmail.com', NULL, '$2y$10$NJV9/sXScjPpZC13IDp6OesqlFWP5CNZr7O/bxyhb4bShM5q/S5wy', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:51');
INSERT INTO `users` (`id`, `nik`, `name`, `email`, `email_verified_at`, `password`, `role`, `department_id`, `join_date`, `employee_status`, `education`, `position`, `contract_end_date`, `ktp_number`, `gender`, `birth_place`, `birth_date`, `phone_number`, `ktp_address`, `domicile_address`, `marital_status`, `mother_maiden_name`, `kk_number`, `blood_type`, `npwp`, `bpjs_kesehatan_number`, `bpjs_health_facility`, `bpjs_ketenagakerjaan_number`, `bank_name`, `bank_account_number`, `vehicle_plate_number`, `sim_number`, `sim_valid_until`, `shoe_size`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `emergency_contact_address`, `spouse_name`, `spouse_ktp_number`, `spouse_birth_place`, `spouse_birth_date`, `child_1_name`, `child_2_name`, `child_3_name`, `is_profile_completed`, `manager_id`, `approver_1_id`, `approver_2_id`, `avatar`, `remember_token`, `created_at`, `updated_at`) VALUES
('93', 'SGIN0309', 'SHERLY JUWITA', 'juwitasherly0@gmail.com', NULL, '$2y$10$Y1HmcU.pjtOeP5Mama3kmOBGTmxf6IULnjX0nT0SjtIj5YYbnKB.e', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:55', '2026-08-27 13:54:51'),
('94', 'SGIN0308', 'RAIDAH AINUN ZAHRAH', 'raidahzahrah95@gmail.com', NULL, '$2y$10$q.IhnMOKzJExGXyvrBoD6uKvll.LuTFYIpGz9ghZK9OMDm4JhuOoS', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:51'),
('95', 'SGIN0310', 'TASYA FARA AULIA', 'tasyafara29@gmail.com', NULL, '$2y$10$5ITKZVgpvwee1ZEKc8dSxuF/uQFi4tWyUa906HMyVAqziB3OBcvZC', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:51'),
('96', 'SGIN0311', 'DAHLIA JULIANTI', 'dahliajuliantijulianti@gmail.com', NULL, '$2y$10$1OHLoETyDjPNjxh2NGvfE..TSaizMtO488SJLZ6zz3CGTQLGJIVB.', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:51'),
('97', 'SGIN0312', 'MUHAMAD ANWAR FAUZY', 'anwarfauzy012@gmail.com', NULL, '$2y$10$iGDm1P6cnrB4U4pS/98uXuOEZcWaKFqGnx/sELvacYWsnk7ALa0O2', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:51'),
('98', 'SGIN0313', 'SHINTA AYU PRADINA', 'pradinashintaayu@gmail.com', NULL, '$2y$10$Y7f7IiHN5w/OO0agaIMm5eKfaCNvrGjqPHBC4otNj.Xlcst9STzZa', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:51'),
('99', 'SGIN0317', 'RATU BUNGA PADILAH', 'ratuubunga06@gmail.com', NULL, '$2y$10$Spqf3B3vRtu7ChGg9Tn4Z.zINkyxy8q.J6f6lMg96MI930OKNU4TC', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:51'),
('100', 'SGIN0315', 'WINDI ROHMAWATI', 'windirohmawati22@gmail.com', NULL, '$2y$10$O.abjs71.1eHGhgFJdGcTOa7Z4RbRs0ydfsjMof9FHJkB3b52yvru', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:51'),
('101', 'SGIN0318', 'MUHAMMAD RULHAMDANI RAMADHAN', 'rulhadaniramadhan@gmail.com', NULL, '$2y$10$GRk./w0UWWa.t4gX4HT6tevxA8F6NSYXQYsu74WrbDL/jpoUTCRhq', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:51'),
('102', 'SGIN0319', 'JECONIA MAHARDIKA PUTRA', 'Jecomp37@gmail.com', NULL, '$2y$10$YwUdIxceuc68XU6ivdlWlOpiqwJx3.4WhRjO.LOGmHKGl8cVuf.5S', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:51'),
('103', 'SGIN0320', 'FENDI RAFLESIA FERDIANTO', 'cakep302@gmail.com', NULL, '$2y$10$dhMM4ATrzk/4SyCFt7gEhew3MS0cD6dlT.f.9cAuij9w8LEN9.F4O', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:51'),
('104', 'SGIN0321', 'YUSRIL IZZATI', 'yusrilizzati98@gmail.com', NULL, '$2y$10$b2enjnIHaX8XQeltrNuFVu./kmRcKScbU9/si1IQZFl6LkHy8X0Xy', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:52'),
('105', 'SGIN0322', 'SITI KOMARIYAH', 'ksiti4176@gmail.com', NULL, '$2y$10$80XCKtOcaofpnfR1pqKrI.5jbTmxZD/LsKHeLnJspPxq46AYksbnO', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:56', '2026-08-27 13:54:52'),
('106', 'SGIN0323', 'JESELINA LENGKEY', 'lengkeyjeselina@gmail.com', NULL, '$2y$10$zwf7RbadOSDUtOo14RvoLubA03zCXNJgQ1KtbVhnX1ApqVi8PgMga', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:52'),
('107', 'SGIN0324', 'LUTFIANI FAUZIAH', 'lutfianifauziah5@gmail.com', NULL, '$2y$10$NaBxrzmitlDdfa9Gk041UuTe2RpyQRWkni2rsdUnL01ucofQf2v5O', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:52'),
('108', 'SGIN0325', 'MAULIDA HIDAYANTI', 'maulida22062000@gmail.com', NULL, '$2y$10$QANzbuM9g04NlLthe4nMl./9IYRgcwGkWYouhTlAbac9yGzZVbP4G', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:52'),
('109', 'SGIN0326', 'MILA ANJANI PUTRI', 'milaanjaniputri635@gmail.com', NULL, '$2y$10$RGPX0RegaZr.H9cU3deQZ.IjVDNc7r7/WJ.TE.0MEl13kQ4X1eKhq', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:52'),
('110', 'SGIN0327', 'NABILA PUTRI AZZAHRA', 'nabilaputria141@gmail.com', NULL, '$2y$10$FDAPq/nF4vlXjev5Pp4IHOl3KaiC44Wk3w9IZPyCsKNqhoi89srCS', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:52'),
('111', 'SGIN0328', 'RIFKY INDRA PRASETYA', 'rifkyindraprasetya@gmail.com', NULL, '$2y$10$Mf4R4P1KJUskmKqtgWvZlOrCCpspE5CW0lkFFlCfJdiYxyosqz.E6', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:52'),
('112', 'SGIN0329', 'PUTRI TIARA', 'putri.tiara3008@gmail.com', NULL, '$2y$10$YlP4tRVHhbWKWis5CD04suekZl8Z5uYIbnXByaDfBELVNG8CeFmjW', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:52'),
('113', 'SGIN0330', 'IDA ROYANI', 'ida24royani@gmail.com', NULL, '$2y$10$P0PQ0G5Oo2LX8/DhPCcz9ey3de2yO.7TcS7BTL3mCkptvIB2mgxpa', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:52'),
('114', 'SGIN0331', 'NURI NUR OKTAFIANI', 'nurioktafiani07@gmail.com', NULL, '$2y$10$I3X4fzmaRWqTpa3YKR3rjOW9SDMdL5WZvincnj5z6aYqi8HIJM9DO', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:52'),
('115', 'SGIN0332', 'MELI AMELIA', 'melyamelia577@gmail.com', NULL, '$2y$10$YWq57hx/9MEnjZ6D0FUcCuV2v0yUWK4O5E.5oYHvbz4db5xgfGNCW', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:52'),
('116', 'SGIN0333', 'ATIKA RAHMA', 'tikarhmaa@gmail.com', NULL, '$2y$10$T3a89svj5e2lWvZqKzpiQOt3GDWRVpt4zUkYQDSambHMEYSU6K3Z.', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:53'),
('117', 'SGIN0334', 'SHELY SHERINA', 'shelysherinaaa23@gmail.com', NULL, '$2y$10$x31t2tcXguy.sJYHNqqVSew0i8EdaGVs5FqJ5uwaxyla.MStGAGKi', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:57', '2026-08-27 13:54:53'),
('118', 'SGIN0335', 'FITRIYANI', 'fy4550639@gmail.com', NULL, '$2y$10$qmo9rU6bzMLtj19wYeU5E.XrsVW7Y1S4CaNFsaPhqrIhC89RWLV.q', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:53'),
('119', 'SGIN0336', 'SINTA BELA', 'sintabella140899@gmail.com', NULL, '$2y$10$7fEMwn14zfVnybX1lTE3jOLn.wDepy42I.HbEAI/uT4Lbs1UAxUdi', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:53'),
('120', 'SGIN0337', 'AZRIE IKHWANSYAH', 'azriebmi82@gmail.com', NULL, '$2y$10$/.6JzLPpt1Vgrvm3bFLxV.11xrc4GO.u9Re9gWbQrVuAbgw5Dj2im', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:53'),
('121', 'SGIN0338', 'IMAM FAUZI', 'imamfauzi03042006@gmail.com', NULL, '$2y$10$m4GCXs93g1mTQBL2/mrFAeo.WSIidnYgo.3BlS3Mc0yRYCnzCL0Mq', 'employee', '6', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:53'),
('122', 'SGIN0339', 'ROBY ALBARKAH', 'robyalbarkah08@gmail.com', NULL, '$2y$10$1FNtG.bu9aUQ5WXeFQn1W.URYuSAhJnCUDYyyKUJQnY36UXy3VnNS', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:53'),
('123', 'SGIN0340', 'KOHAR NARTIANA', 'koharnartiana11@yahoo.com', NULL, '$2y$10$gQAle/Aoe8wo0o1AdDOPoeNn.7CoLDQ9cPbCmR7ZyywJPQReDZ69.', 'employee', '6', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:53'),
('124', 'SGIN0341', 'SITI NUR AULIYATUS SAADAH', 'sitinuraulia730@gmail.com', NULL, '$2y$10$h0C1hhQF.e2ippngiLI9o.RuObHIEXkXHFPtE2cGiHeXLYPGSVe9G', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:53'),
('125', 'SGIN0342', 'MUHAMAD GALIH J', 'galihjuliansyah042@gmail.com', NULL, '$2y$10$GAyzFjdNuTFUeyi8FmMBheUo9yt5aCCR3iImrLw.9D0MrIDqqikxS', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:53'),
('126', 'SGIN0343', 'SITI WULANDARI', 'sitiwulandari429@gmail.com', NULL, '$2y$10$G2isHqIoS1XupAjj./nCE.icmNxPCBvtit/L/0OHf0D/rybDDAPqO', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:53'),
('127', 'SGIN0344', 'SILVIA ZAHRO', 'silviazahro27@gmail.com', NULL, '$2y$10$RNLGRkD3TkoNrbZkz87PJ.QpnjxRwqvGNlW8aufKhBEDSaJFhoDyG', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:53'),
('128', 'SGIN0345', 'NUR LAELA', 'noncihningtiya@gmail.com', NULL, '$2y$10$ex/IpnqejAhQQHUS/duXcOTY1SeGhtPd2sRMIqf1Oqb/gcCN6kbS.', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:53'),
('129', 'SGIN0346', 'NONCIH AYU NINGTIYA', 'lnur76390@gmail.com', NULL, '$2y$10$gC9Uud8JL/lIqXO.swDfZOKk6PgwIoMekSb2WA7UdUjbHHsV4uGya', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:54'),
('130', 'SGIN-M-001', 'GERALDI ALEXAPRIYATNA', 'geraldialexpriatna@gmail.com', NULL, '$2y$10$cJDPi8.IWlEjKzVW3R55L.oDjSG.VzTnDwz6RVzCFAHN7m2GIp7Dm', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:58', '2026-08-27 13:54:54'),
('131', 'SGIN-M-002', 'DITO SEPTIANTO', 'ditoseptianto89@gmail.com', NULL, '$2y$10$.yOOt7F7M27IBVKbh3Bek.IJiG0EnkFu8h3r8fcy9rHCV5nQ8gyQW', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:59', '2026-08-27 13:54:54'),
('132', 'SGIN-M-003', 'FIRYAL MUTHIAH', 'firyalmuthiah28@gmail.com', NULL, '$2y$10$0xOwwB601UY7leCS1NrCX.fgwvIvx0FerXEHWzBRbYrcpJOgd5w0u', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:59', '2026-08-27 13:54:54'),
('133', 'SGIN-M-004', 'ELSA JULIAWATI', 'elsajuliawati25@gmail.com', NULL, '$2y$10$ElRT3e/LtRGryBxfiHBgmO7TInpAfgkjcc5zVZqKZfyMs35FNydQq', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:59', '2026-08-27 13:54:54'),
('134', 'SGIN-M-005', 'MAHFUD SETYA BUDHI', 'mahfudsetyabudhi08@gmail.com', NULL, '$2y$10$hcLs9gHwdHXB9UzoUkLD3.pZwiVyr.AgjL9l4RxOyKUkQcCNE6G4W', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:59', '2026-08-27 13:54:54'),
('135', 'SGIN-M-006', 'SRI MELYANI', 'melyaniputrimput@gmail.com', NULL, '$2y$10$ctWUcQh9FkjEQe34RLFHd.b5VOcwbzA.zdFkUUb6YPv23SRC8pK6O', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:59', '2026-08-27 13:54:54'),
('136', 'SGIN-M-007', 'DANANG SURYA KHAIRIZAL', 'danang02728@gmail.com', NULL, '$2y$10$PFC/BqjehgP8Ad4UD7p0fOgf/kmIHCmU2soAIBv.5zI3mnP3txz9C', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:59', '2026-08-27 13:54:54'),
('137', 'SGIN-M-008', 'KHOPIPAH', 'khopipahtuhfatul@gmail.com', NULL, '$2y$10$RMb2UaDraEdKnVo4Lp1PnOheOgq9RqytmisxkR6M9WgyYo83TMwca', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:59', '2026-08-27 13:54:54'),
('138', 'SGIN-OS-001', 'EROL MARCEL KOWURENG', 'erolkowureng88568@gmail.com', NULL, '$2y$10$5mTPj40CMfRAejvHasRM0u5qiT6dZRh8KrN6kMSHz/Fog82qybbMq', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:59', '2026-08-27 13:54:54'),
('139', 'SGIN-OS-002', 'LODRI GUNAWAN', 'lodrigunawan33@gmail.com', NULL, '$2y$10$fPhj.zXzBCKlmCY5F1lNWOmzuA0Kd7GTREDxyWOrBJD7AJL3zIWlS', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:59', '2026-08-27 13:54:54'),
('140', 'SGIN-OS-004', 'ELIN ROHAYATIN', 'linlinrohaya@gmail.com', NULL, '$2y$10$kT/6JvKGmqLhFA1R5tf6Leoau7HE7ceck3gcTQYPSJHcCKSX3vPPm', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:59', '2026-08-27 13:54:54'),
('141', 'SGIN-OS-005', 'ANDIKA', 'septembernine.rbesar@gmail.com', NULL, '$2y$10$r93oRk/Gg8a.WXE4DYt49.Hgy43ZbpF/i0wx0HYfVC0IG5VJbbfkO', 'employee', '8', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:11:59', '2026-08-27 13:54:54'),
('144', 'SGIN-OS-005-87', 'DERI ISMATULLAH', 'diery101091@gmai.com', NULL, '$2y$10$hPZ68A9nIiX0zo72GYRpF.3mwJUXwR0wGv3nAG5WDKEMSzFLiQVBa', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55');
INSERT INTO `users` (`id`, `nik`, `name`, `email`, `email_verified_at`, `password`, `role`, `department_id`, `join_date`, `employee_status`, `education`, `position`, `contract_end_date`, `ktp_number`, `gender`, `birth_place`, `birth_date`, `phone_number`, `ktp_address`, `domicile_address`, `marital_status`, `mother_maiden_name`, `kk_number`, `blood_type`, `npwp`, `bpjs_kesehatan_number`, `bpjs_health_facility`, `bpjs_ketenagakerjaan_number`, `bank_name`, `bank_account_number`, `vehicle_plate_number`, `sim_number`, `sim_valid_until`, `shoe_size`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `emergency_contact_address`, `spouse_name`, `spouse_ktp_number`, `spouse_birth_place`, `spouse_birth_date`, `child_1_name`, `child_2_name`, `child_3_name`, `is_profile_completed`, `manager_id`, `approver_1_id`, `approver_2_id`, `avatar`, `remember_token`, `created_at`, `updated_at`) VALUES
('145', 'SGIN-OS-007', 'KRISTIAN FARENDY A. LUMINTANG', 'farendyrendy@gmail.com', NULL, '$2y$10$Hrjiy3Vpdg3.K/DKnV9/J..dIIvmC/7K824fFD2kHwaMd.44fQJDa', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55'),
('146', 'SGIN-OS-008', 'RAHMATULLAH', 'rahmatchollay3@gmail.com', NULL, '$2y$10$KD0NWpEXHhFWpys01nORhO9N59NfUIY9RoLfYyXbH1HnVgIhO9Uw2', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55'),
('147', 'SGIN-OS-009', 'DEDE YUSUF', 'dedesugiyama@gmail.com', NULL, '$2y$10$JrAPR0Xk.G3tsuX02G6cqeaMvWjAap24PMf5BceeCEfhDX7x0sITa', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55'),
('148', 'SGIN-OS-010', 'RISWANTO', 'wantoris1607@gmail.com', NULL, '$2y$10$qbzKWU6BV2XhjTCZ50QxhO5n4NQBxYqQFi2PHwrfZwwKjn4vCvgji', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55'),
('149', 'SGIN-OS-011', 'ANANG MAULANA', 'anangmaulana18.86@gmail.com', NULL, '$2y$10$px3D2j0kGPPCUSSN2Z2ccu7wzZUWRWNcwNdl.P543uJanR.jUnnF.', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55'),
('150', 'SGIN-OS-012', 'ARIO YULIANO SEMET', 'semetario16@gmail.com', NULL, '$2y$10$.rXMMIpy1E8a9Rhc/YQKFuba0ZnnB2kO/3sLno0kvrwqdCgz5HJd6', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55'),
('151', 'SGIN-OS-013', 'RIRI SAEFUL BAHRI BAEHAKI', 'bahribaehaqi5@gmail.com', NULL, '$2y$10$7tkO240NCb3Dx6k1aHqTx.UWikNKKZ44A2LPs9iAtzu6/Y9dXAbeS', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55'),
('152', 'SGIN-OS-014', 'BUDI SETIADI', 'setiadibudi992@gmail.com', NULL, '$2y$10$p5dCjO7IPTX0qKGqJxr5N.nJMNW51YFQ5HQ4lbtN/6/WDP8l69y2K', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55'),
('153', 'SGIN-OS-015', 'HERI KUSUMA', 'herikusuma0196@gmail.com', NULL, '$2y$10$1F5t2OMCIfXe3ue1C2EGj.Yf2bAX57qI1TYhVptce.TZ8EmU./yp2', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55'),
('154', 'SGIN-OS-016', 'BAYU SETIAWAN', 'sapa.bayu@gmail.com', NULL, '$2y$10$fBFTZYbEPu7YBYyX4nc4oeu45f8tnqZ9qxZNPGZe/lN.DPkb4GmWy', 'employee', '8', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55'),
('155', 'SGIN-OS-017', 'M. SYAHRUL FAQIH SALIM', 'msyahrulfaqihsalim@gmail.com', NULL, '$2y$10$vEwsUBh80S0g0qZ9fU3l3usxl6UtcGEZI5C1txiJpuNM/SffsOKpq', 'employee', '1', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:09', '2026-08-27 13:54:55'),
('156', 'SGIN-OS-018', 'MARYANTO', 'maryantomio@gmail.com', NULL, '$2y$10$GlpdOhZHoRaFe9WlfQCrCeJckv7jsDHot9omPC76dBWO4cojW8bXG', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:10', '2026-08-27 13:54:56'),
('157', 'SGIN-OS-019', 'FARHAN FAHRUL ULUM', 'fahrulfarhan26@gmail.com', NULL, '$2y$10$7CUx2Wqct1Y.m2olElh0Ye55tqmIE8cbGbcLtx/yHN5x6sJNnd8Ve', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:10', '2026-08-27 13:54:56'),
('158', 'SGIN-OS-020', 'RIKI OCTAPYANA', 'rikioctapyana17@gmail.com', NULL, '$2y$10$lU0snJAAMBls3btfF90u7.CxMJ21OoaBRXMio6iAtdq.Xm7.ZNY/m', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:10', '2026-08-27 13:54:56'),
('159', 'SGIN-OS-021', 'RAMMADHA DARMANSYA', 'rammadha05@gmail.com', NULL, '$2y$10$d4pZzDydNZ0zgVNxDgqAJOZm.swH52GHJvaQdPK/EJwWFLKFlRkJi', 'employee', '7', NULL, 'Tetap', NULL, 'Operator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '2026-08-27 12:15:10', '2026-08-27 13:54:56');

SET FOREIGN_KEY_CHECKS=1;
