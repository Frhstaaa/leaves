-- SGIN Database Ready to Import (Clean & Reordered)
-- Generated for phpMyAdmin / MySQL / MariaDB compatibility

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

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

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` VALUES 
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
('15', '2024_01_01_000012_add_performance_indexes', '7');

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` VALUES 
('1', 'view-dashboard', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('2', 'create-leave-request', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('3', 'view-leave-history', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('4', 'manage-approvals', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('5', 'manage-employees', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('6', 'view-hrd-rekap', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('7', 'export-hrd-reports', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('8', 'manage-roles', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('9', 'manage-system-settings', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('10', 'create-employee', 'web', '2026-08-21 02:48:22', '2026-08-21 02:48:22'),
('11', 'edit-employee', 'web', '2026-08-21 02:48:22', '2026-08-21 02:48:22'),
('12', 'delete-employee', 'web', '2026-08-21 02:48:22', '2026-08-21 02:48:22'),
('13', 'view-leave-quota-report', 'web', '2026-08-21 02:48:22', '2026-08-21 02:48:22'),
('14', 'view-department-report', 'web', '2026-08-21 02:48:22', '2026-08-21 02:48:22'),
('15', 'manage-leave-categories', 'web', '2026-08-21 02:48:22', '2026-08-21 02:48:22');

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

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` VALUES 
('1', 'superadmin', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('2', 'admin', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('3', 'manager', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('4', 'employee', 'web', '2026-08-20 02:13:14', '2026-08-20 02:13:14');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` VALUES 
('1', 'app_name', 'PT. Sugiyama', '2026-08-21 02:07:06', '2026-08-21 02:07:06'),
('2', 'app_subname', 'Cuti & Ketidakhadiran', '2026-08-21 02:07:06', '2026-08-21 02:07:06'),
('3', 'company_name', 'PT. SGIN Indonesia', '2026-08-21 02:07:06', '2026-08-21 02:07:06'),
('4', 'company_address', 'Jl. Industri Raya No. 123, Kawasan Industri', '2026-08-21 02:07:06', '2026-08-21 02:07:06'),
('5', 'company_phone', '+62 21 8901234', '2026-08-21 02:07:06', '2026-08-21 02:07:06'),
('6', 'company_email', 'hrd@sgin.co.id', '2026-08-21 02:07:06', '2026-08-21 02:07:06'),
('7', 'theme_color', '#059669', '2026-08-21 02:07:06', '2026-08-21 02:07:06'),
('8', 'app_description', 'Sistem Informasi Manajemen Cuti, Ketidakhadiran, Izin, Sakit, Lembur, dan Distribusi Slip Gaji Karyawan Real-time.', '2026-08-21 02:07:06', '2026-08-21 02:07:06');

DROP TABLE IF EXISTS `leave_categories`;
CREATE TABLE `leave_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `unit_type` varchar(255) NOT NULL DEFAULT 'hari',
  `requires_attachment` tinyint(1) NOT NULL DEFAULT 0,
  `default_quota` int(11) NOT NULL DEFAULT 12,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leave_categories` VALUES 
('1', 'Cuti Tahunan', 'hari', '0', '12', 'Cuti tahunan reguler karyawan (12 hari/tahun).', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('2', 'Cuti Haid', 'hari', '0', '2', 'Izin khusus haid hari pertama/kedua untuk karyawan perempuan.', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('3', 'Sakit (Dengan Surat Dokter)', 'hari', '1', '14', 'Ketidakhadiran karena sakit disertai bukti surat keterangan dokter.', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('4', 'Sakit (Tanpa Surat Dokter)', 'hari', '0', '3', 'Ketidakhadiran karena sakit ringan tanpa surat dokter.', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('5', 'Sakit Karena Kecelakaan Kerja', 'hari', '1', '14', 'Ketidakhadiran karena kecelakaan pada saat menjalankan tugas pekerjaan.', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('6', 'Ijin tidak masuk karena Suami/Istri/Anak/Orang tua/Mertua/Saudara Kandung Meninggal/ Istri Melahirkan', 'hari', '0', '3', 'Izin khusus musibah keluarga inti atau anggota keluarga melahirkan.', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('7', 'Mangkir', 'hari', '0', '0', 'Ketidakhadiran kerja tanpa pemberitahuan/izin sah.', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('8', 'Ijin Datang terlambat (Kurang dari 4 jam)', 'jam', '0', '24', 'Izin keterlambatan masuk kantor kurang dari 4 jam.', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('9', 'Ijin Datang terlambat (Lebih dari 4 jam)', 'jam', '0', '24', 'Izin keterlambatan masuk kantor lebih dari 4 jam.', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('10', 'Pulang lebih cepat tanpa ijin', 'jam', '0', '0', 'Meninggalkan pekerjaan sebelum jam pulang tanpa persetujuan.', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('11', 'Ijin Tidak Masuk Kerja Tanpa Menerima Upah', 'hari', '0', '0', 'Izin tidak masuk kerja di luar hak cuti yang memotong upah harian.', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('12', 'Izin Meninggalkan Pekerjaan (Jam)', 'jam', '0', '24', 'Izin keluar kantor untuk urusan mendesak hitungan jam.', '2026-08-20 02:13:14', '2026-08-20 02:13:14');

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` VALUES 
('1', 'MGR-101', 'Ahmad Dahlan, S.T. (Manager IT)', 'manager@sgin.com', NULL, '$2y$10$9dGE1IDvRGpWV286CGdyMuXGVpJJKSXAkr6.lsP2r9FN7r1H8VjAm', 'manager', '1', NULL, NULL, NULL, NULL, NULL, '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('2', 'HRD-001', 'Citra Lestari, S.Psi (HRD / PGA Admin)', 'hrd@sgin.com', NULL, '$2y$10$jILLtiZQSdvKEwZhypE9ruVN8CankVUK9XAWdD9H0Oj5kTR8FLhIa', 'admin', '2', NULL, NULL, NULL, NULL, NULL, '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('3', 'EMP-201', 'Budi Santoso', 'karyawan@sgin.com', NULL, '$2y$10$1ojzJ3TIg7qLhX7OGq6xz.fdBfB4DZTuywobKFv3obq3KPL1ir6CG', 'employee', '1', '1', NULL, NULL, NULL, NULL, '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('4', 'EMP-202', 'Siti Rahmawati', 'siti@sgin.com', NULL, '$2y$10$Vpo5a/UGbIr0R87kpVtxd.Kvwct0A7g6Rv7i6dcO6Tr5O9FPXpVEq', 'employee', '1', '1', NULL, NULL, NULL, NULL, '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('5', 'EMP-203', 'Doni Kusuma', 'doni@sgin.com', NULL, '$2y$10$VHf5M6cEJuHBYX0McLJDTOtIsbBOewR95Izx.knH.UT49W1KGAKv.', 'employee', '4', '2', NULL, NULL, NULL, NULL, '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('6', 'SA-001', 'Superadmin SGIN', 'superadmin@sgin.com', NULL, '$2y$10$sFIjmRX705jfZe5/XBQOZururrTS3bZYm4zsbjQ/7NcmiMf02ToaO', 'superadmin', '1', NULL, NULL, NULL, NULL, NULL, '2026-08-20 02:13:14', '2026-08-20 02:13:14');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` VALUES 
('1', 'Information Technology', 'DEPT-IT', '1', NULL, NULL, '3_tier', NULL, '2026-08-20 02:13:13', '2026-08-20 02:13:14'),
('2', 'Human Resources & PGA', 'DEPT-HRD', '2', NULL, NULL, '3_tier', NULL, '2026-08-20 02:13:13', '2026-08-20 02:13:14'),
('3', 'Finance & Accounting', 'DEPT-FIN', NULL, NULL, NULL, '3_tier', NULL, '2026-08-20 02:13:13', '2026-08-20 02:13:13'),
('4', 'Operations & Supply', 'DEPT-OPS', NULL, NULL, NULL, '3_tier', NULL, '2026-08-20 02:13:13', '2026-08-20 02:13:13');

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leave_quotas` VALUES 
('1', '1', '2026', '12', '0', '12', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('2', '2', '2026', '12', '0', '12', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('3', '3', '2026', '12', '0', '12', '2026-08-20 02:13:14', '2026-08-20 16:43:46'),
('4', '4', '2026', '12', '0', '12', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('5', '5', '2026', '12', '0', '12', '2026-08-20 02:13:14', '2026-08-20 02:13:14'),
('6', '6', '2026', '12', '0', '12', '2026-08-20 02:13:14', '2026-08-20 02:13:14');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role_has_permissions` VALUES 
('1', '1'),
('1', '2'),
('1', '3'),
('1', '4'),
('2', '1'),
('2', '2'),
('2', '3'),
('2', '4'),
('3', '1'),
('3', '2'),
('3', '3'),
('3', '4'),
('4', '1'),
('4', '2'),
('4', '3'),
('5', '1'),
('6', '1'),
('6', '2'),
('7', '1'),
('7', '2'),
('8', '1'),
('9', '1'),
('10', '1'),
('10', '2'),
('11', '1'),
('11', '2'),
('12', '1'),
('12', '2'),
('13', '1'),
('13', '2'),
('14', '1'),
('14', '2'),
('15', '1'),
('15', '2');

DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `model_has_roles` VALUES 
('1', 'App\\Models\\User', '6'),
('2', 'App\\Models\\User', '2'),
('3', 'App\\Models\\User', '1'),
('4', 'App\\Models\\User', '3'),
('4', 'App\\Models\\User', '4'),
('4', 'App\\Models\\User', '5');

DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
