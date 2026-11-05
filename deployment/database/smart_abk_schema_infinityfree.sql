-- SMART-ABK schema export
-- MariaDB dump 10.19, Distrib 10.4.32-MariaDB, for Win64 (AMD64)
-- Generated from the complete Laravel migration set.
-- Contains schema, migrations tracking rows, and six role reference rows only.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=1;
SET UNIQUE_CHECKS=1;

--
-- ------------------------------------------------------


--
-- Table structure for table `aktivitas_gpk`
--

CREATE TABLE `aktivitas_gpk` (
  `id_aktivitas_gpk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_gpk` int(10) unsigned NOT NULL,
  `id_catatan_perkembangan` int(10) unsigned DEFAULT NULL,
  `id_catatan_pendampingan` int(10) unsigned DEFAULT NULL,
  `id_laporan_bulanan` int(10) unsigned DEFAULT NULL,
  `versi_pengajuan` int(10) unsigned DEFAULT NULL,
  `jenis_aktivitas` enum('Mulai Meninjau','Terverifikasi','Dikembalikan','Publikasi Laporan') NOT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal_aktivitas` datetime NOT NULL,
  PRIMARY KEY (`id_aktivitas_gpk`),
  KEY `aktivitas_gpk_id_gpk_foreign` (`id_gpk`),
  KEY `aktivitas_gpk_id_laporan_bulanan_foreign` (`id_laporan_bulanan`),
  KEY `idx_aktivitas_perkembangan` (`id_catatan_perkembangan`,`versi_pengajuan`),
  KEY `idx_aktivitas_pendampingan` (`id_catatan_pendampingan`,`versi_pengajuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `catatan_pendampingan`
--

CREATE TABLE `catatan_pendampingan` (
  `id_catatan_pendampingan` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_siswa` int(10) unsigned NOT NULL,
  `id_pembuat` int(10) unsigned NOT NULL,
  `tanggal_catatan` date NOT NULL,
  `kegiatan_pendampingan` text NOT NULL,
  `kendala` text DEFAULT NULL,
  `tindak_lanjut` text DEFAULT NULL,
  `status_pengajuan` enum('Draft','Dikirim','Perlu Revisi','Terverifikasi') NOT NULL DEFAULT 'Draft',
  `versi_pengajuan` int(10) unsigned NOT NULL DEFAULT 1,
  `tanggal_kirim` datetime DEFAULT NULL,
  `id_peninjau_gpk` int(10) unsigned DEFAULT NULL,
  `versi_ditinjau` int(10) unsigned DEFAULT NULL,
  `tanggal_mulai_tinjau` datetime DEFAULT NULL,
  `idempotency_key` char(36) DEFAULT NULL,
  PRIMARY KEY (`id_catatan_pendampingan`),
  UNIQUE KEY `catatan_pendampingan_idempotency_key_unique` (`idempotency_key`),
  KEY `catatan_pendampingan_id_siswa_foreign` (`id_siswa`),
  KEY `catatan_pendampingan_id_pembuat_foreign` (`id_pembuat`),
  KEY `catatan_pendampingan_id_peninjau_gpk_foreign` (`id_peninjau_gpk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `catatan_perkembangan`
--

CREATE TABLE `catatan_perkembangan` (
  `id_catatan_perkembangan` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_siswa` int(10) unsigned NOT NULL,
  `id_pembuat` int(10) unsigned NOT NULL,
  `id_mapel` int(10) unsigned NOT NULL,
  `tanggal_catatan` date NOT NULL,
  `materi_kegiatan` text NOT NULL,
  `kendala_peristiwa` text DEFAULT NULL,
  `tindakan` text DEFAULT NULL,
  `hasil_tindakan` text DEFAULT NULL,
  `catatan_tambahan` text DEFAULT NULL,
  `status_pengajuan` enum('Draft','Dikirim','Perlu Revisi','Terverifikasi') NOT NULL DEFAULT 'Draft',
  `versi_pengajuan` int(10) unsigned NOT NULL DEFAULT 1,
  `tanggal_kirim` datetime DEFAULT NULL,
  `id_peninjau_gpk` int(10) unsigned DEFAULT NULL,
  `versi_ditinjau` int(10) unsigned DEFAULT NULL,
  `tanggal_mulai_tinjau` datetime DEFAULT NULL,
  PRIMARY KEY (`id_catatan_perkembangan`),
  KEY `catatan_perkembangan_id_siswa_foreign` (`id_siswa`),
  KEY `catatan_perkembangan_id_pembuat_foreign` (`id_pembuat`),
  KEY `catatan_perkembangan_id_mapel_foreign` (`id_mapel`),
  KEY `catatan_perkembangan_id_peninjau_gpk_foreign` (`id_peninjau_gpk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `failed_jobs`
--

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

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_tahun_ajaran` int(10) unsigned DEFAULT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `tingkat` varchar(10) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `status_kelas` varchar(20) NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (`id_kelas`),
  KEY `kelas_id_tahun_ajaran_foreign` (`id_tahun_ajaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `laporan_bulanan`
--

CREATE TABLE `laporan_bulanan` (
  `id_laporan_bulanan` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `periode_bulan` date NOT NULL,
  `jumlah_siswa` int(10) unsigned NOT NULL DEFAULT 0,
  `jumlah_catatan` int(10) unsigned NOT NULL DEFAULT 0,
  `data_laporan` longtext NOT NULL,
  `status_laporan` enum('Belum Tersedia','Tersedia') NOT NULL DEFAULT 'Belum Tersedia',
  `tanggal_dibuat` datetime NOT NULL,
  `id_publikator` int(10) unsigned DEFAULT NULL,
  `tanggal_publikasi` datetime DEFAULT NULL,
  PRIMARY KEY (`id_laporan_bulanan`),
  UNIQUE KEY `laporan_bulanan_periode_bulan_unique` (`periode_bulan`),
  KEY `laporan_bulanan_id_publikator_foreign` (`id_publikator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `laporan_perkembangan`
--

CREATE TABLE `laporan_perkembangan` (
  `id_laporan` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_rekap` int(10) unsigned NOT NULL,
  `id_pembuat` int(10) unsigned NOT NULL,
  `periode` varchar(30) NOT NULL,
  `isi_laporan` longtext NOT NULL,
  `status_laporan` enum('Draft','Tersedia') NOT NULL DEFAULT 'Draft',
  `tanggal_laporan` datetime NOT NULL,
  `id_publikator` int(10) unsigned DEFAULT NULL,
  `tanggal_publikasi` datetime DEFAULT NULL,
  PRIMARY KEY (`id_laporan`),
  KEY `laporan_perkembangan_id_rekap_foreign` (`id_rekap`),
  KEY `laporan_perkembangan_id_pembuat_foreign` (`id_pembuat`),
  KEY `laporan_perkembangan_id_publikator_foreign` (`id_publikator`),
  CONSTRAINT `chk_laporan_publikasi` CHECK (`status_laporan` = 'Draft' and `id_publikator` is null and `tanggal_publikasi` is null or `status_laporan` = 'Tersedia' and `id_publikator` is not null and `tanggal_publikasi` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `mata_pelajaran`
--

CREATE TABLE `mata_pelajaran` (
  `id_mapel` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nama_mapel` varchar(100) NOT NULL,
  `status_mapel` varchar(20) NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (`id_mapel`),
  UNIQUE KEY `mata_pelajaran_nama_mapel_unique` (`nama_mapel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `orang_tua`
--

CREATE TABLE `orang_tua` (
  `id_orang_tua` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_pengguna` int(10) unsigned NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_orang_tua`),
  UNIQUE KEY `orang_tua_id_pengguna_unique` (`id_pengguna`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_role` int(10) unsigned NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `jabatan_gpk` varchar(20) DEFAULT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `password_diubah_pada` timestamp NULL DEFAULT NULL,
  `wajib_ganti_password` tinyint(1) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `status_akun` enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (`id_pengguna`),
  UNIQUE KEY `pengguna_email_unique` (`email`),
  UNIQUE KEY `pengguna_username_unique` (`username`),
  UNIQUE KEY `pengguna_nip_unique` (`nip`),
  UNIQUE KEY `uq_pengguna_jabatan_gpk` (`jabatan_gpk`),
  UNIQUE KEY `pengguna_google_id_unique` (`google_id`),
  KEY `pengguna_id_role_foreign` (`id_role`),
  KEY `pengguna_is_super_admin_index` (`is_super_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_pengguna` int(10) unsigned NOT NULL,
  `endpoint` text NOT NULL,
  `endpoint_hash` char(64) NOT NULL,
  `public_key` varchar(255) NOT NULL,
  `auth_token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_hash_unique` (`endpoint_hash`),
  KEY `push_subscriptions_id_pengguna_foreign` (`id_pengguna`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `rekap_perkembangan`
--

CREATE TABLE `rekap_perkembangan` (
  `id_rekap` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_siswa` int(10) unsigned NOT NULL,
  `id_pembuat` int(10) unsigned NOT NULL,
  `periode` varchar(30) NOT NULL,
  `ringkasan_perkembangan` text NOT NULL,
  `tanggal_rekap` datetime NOT NULL,
  PRIMARY KEY (`id_rekap`),
  KEY `rekap_perkembangan_id_siswa_foreign` (`id_siswa`),
  KEY `rekap_perkembangan_id_pembuat_foreign` (`id_pembuat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `relasi_guru_mapel_kelas`
--

CREATE TABLE `relasi_guru_mapel_kelas` (
  `id_relasi_guru_mapel` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_pengguna` int(10) unsigned NOT NULL,
  `id_mapel` int(10) unsigned NOT NULL,
  `id_kelas` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_relasi_guru_mapel`),
  UNIQUE KEY `uq_relasi_guru_mapel__1` (`id_pengguna`,`id_mapel`,`id_kelas`),
  KEY `relasi_guru_mapel_kelas_id_mapel_foreign` (`id_mapel`),
  KEY `relasi_guru_mapel_kelas_id_kelas_foreign` (`id_kelas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `relasi_kelas_mapel`
--

CREATE TABLE `relasi_kelas_mapel` (
  `id_relasi_kelas_mapel` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_kelas` int(10) unsigned NOT NULL,
  `id_mapel` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_relasi_kelas_mapel`),
  UNIQUE KEY `uq_relasi_kelas_mapel` (`id_kelas`,`id_mapel`),
  KEY `relasi_kelas_mapel_id_mapel_foreign` (`id_mapel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `relasi_orang_tua_siswa`
--

CREATE TABLE `relasi_orang_tua_siswa` (
  `id_relasi_ortu` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_orang_tua` int(10) unsigned NOT NULL,
  `id_siswa` int(10) unsigned NOT NULL,
  `hubungan` varchar(30) NOT NULL,
  PRIMARY KEY (`id_relasi_ortu`),
  UNIQUE KEY `uq_relasi_orang_tua_s_1` (`id_orang_tua`,`id_siswa`),
  UNIQUE KEY `uq_relasi_orang_tua_siswa_siswa` (`id_siswa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `relasi_pendamping_siswa`
--

CREATE TABLE `relasi_pendamping_siswa` (
  `id_relasi_pendamping` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_pengguna` int(10) unsigned NOT NULL,
  `id_siswa` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_relasi_pendamping`),
  UNIQUE KEY `uq_relasi_pendamping__1` (`id_pengguna`,`id_siswa`),
  KEY `relasi_pendamping_siswa_id_siswa_foreign` (`id_siswa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `riwayat_kelas_siswa`
--

CREATE TABLE `riwayat_kelas_siswa` (
  `id_riwayat_kelas_siswa` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_siswa` int(10) unsigned NOT NULL,
  `id_kelas` int(10) unsigned NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `status` enum('Aktif','Selesai') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_riwayat_kelas_siswa`),
  UNIQUE KEY `riwayat_siswa_kelas_mulai_unique` (`id_siswa`,`id_kelas`,`tanggal_mulai`),
  KEY `riwayat_kelas_siswa_id_kelas_foreign` (`id_kelas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `id_role` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nama_role` varchar(50) NOT NULL,
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `role_nama_role_unique` (`nama_role`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_kelas` int(10) unsigned NOT NULL,
  `nis` varchar(30) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `kebutuhan_khusus` varchar(150) DEFAULT NULL,
  `status_siswa` enum('Aktif','Lulus','Pindah','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (`id_siswa`),
  UNIQUE KEY `siswa_nis_unique` (`nis`),
  KEY `siswa_id_kelas_foreign` (`id_kelas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `sumber_rekap_catatan`
--

CREATE TABLE `sumber_rekap_catatan` (
  `id_sumber_rekap` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_rekap` int(10) unsigned NOT NULL,
  `id_catatan_perkembangan` int(10) unsigned DEFAULT NULL,
  `id_catatan_pendampingan` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_sumber_rekap`),
  UNIQUE KEY `uq_sumber_rekap_catat_1` (`id_rekap`,`id_catatan_perkembangan`),
  UNIQUE KEY `uq_sumber_rekap_catat_2` (`id_rekap`,`id_catatan_pendampingan`),
  KEY `sumber_rekap_catatan_id_catatan_perkembangan_foreign` (`id_catatan_perkembangan`),
  KEY `sumber_rekap_catatan_id_catatan_pendampingan_foreign` (`id_catatan_pendampingan`),
  CONSTRAINT `chk_sumber_rekap_xor` CHECK (`id_catatan_perkembangan` is not null and `id_catatan_pendampingan` is null or `id_catatan_perkembangan` is null and `id_catatan_pendampingan` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `tahun_ajaran`
--

CREATE TABLE `tahun_ajaran` (
  `id_tahun_ajaran` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(9) NOT NULL,
  `mulai_ganjil` date NOT NULL,
  `selesai_ganjil` date NOT NULL,
  `mulai_genap` date NOT NULL,
  `selesai_genap` date NOT NULL,
  `status` enum('Akan Datang','Aktif','Selesai') NOT NULL DEFAULT 'Akan Datang',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_tahun_ajaran`),
  UNIQUE KEY `tahun_ajaran_nama_unique` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `verifikasi_catatan`
--

CREATE TABLE `verifikasi_catatan` (
  `id_verifikasi` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_catatan_perkembangan` int(10) unsigned DEFAULT NULL,
  `id_catatan_pendampingan` int(10) unsigned DEFAULT NULL,
  `id_verifikator` int(10) unsigned NOT NULL,
  `versi_pengajuan` int(10) unsigned NOT NULL DEFAULT 1,
  `status_verifikasi` enum('Terverifikasi','Perlu Revisi') NOT NULL,
  `catatan_verifikasi` text DEFAULT NULL,
  `tanggal_verifikasi` datetime NOT NULL,
  `id_publikator` int(10) unsigned DEFAULT NULL,
  `tanggal_publikasi` datetime DEFAULT NULL,
  PRIMARY KEY (`id_verifikasi`),
  KEY `verifikasi_catatan_id_catatan_perkembangan_foreign` (`id_catatan_perkembangan`),
  KEY `verifikasi_catatan_id_catatan_pendampingan_foreign` (`id_catatan_pendampingan`),
  KEY `verifikasi_catatan_id_verifikator_foreign` (`id_verifikator`),
  KEY `verifikasi_catatan_id_publikator_foreign` (`id_publikator`),
  CONSTRAINT `chk_verifikasi_sumber_xor` CHECK (`id_catatan_perkembangan` is not null and `id_catatan_pendampingan` is null or `id_catatan_perkembangan` is null and `id_catatan_pendampingan` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DELIMITER ;;
CREATE TRIGGER `trg_verifikasi_publikasi_insert`
BEFORE INSERT ON `verifikasi_catatan`
FOR EACH ROW
BEGIN
    IF (
        (NEW.id_publikator IS NULL AND NEW.tanggal_publikasi IS NOT NULL)
        OR (NEW.id_publikator IS NOT NULL AND NEW.tanggal_publikasi IS NULL)
        OR (NEW.id_publikator IS NOT NULL AND NEW.status_verifikasi <> 'Terverifikasi')
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Publikasi memerlukan GPK, tanggal, dan status Terverifikasi';
    END IF;
END;;
DELIMITER ;
DELIMITER ;;
CREATE TRIGGER `trg_verifikasi_publikasi_update`
BEFORE UPDATE ON `verifikasi_catatan`
FOR EACH ROW
BEGIN
    IF (
        (NEW.id_publikator IS NULL AND NEW.tanggal_publikasi IS NOT NULL)
        OR (NEW.id_publikator IS NOT NULL AND NEW.tanggal_publikasi IS NULL)
        OR (NEW.id_publikator IS NOT NULL AND NEW.status_verifikasi <> 'Terverifikasi')
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Publikasi memerlukan GPK, tanggal, dan status Terverifikasi';
    END IF;
END;;
DELIMITER ;


--
-- ------------------------------------------------------


--
-- Foreign keys are added after all referenced tables exist.
SET FOREIGN_KEY_CHECKS=1;
ALTER TABLE `aktivitas_gpk` ADD CONSTRAINT `aktivitas_gpk_id_catatan_pendampingan_foreign` FOREIGN KEY (`id_catatan_pendampingan`) REFERENCES `catatan_pendampingan` (`id_catatan_pendampingan`);
ALTER TABLE `aktivitas_gpk` ADD CONSTRAINT `aktivitas_gpk_id_catatan_perkembangan_foreign` FOREIGN KEY (`id_catatan_perkembangan`) REFERENCES `catatan_perkembangan` (`id_catatan_perkembangan`);
ALTER TABLE `aktivitas_gpk` ADD CONSTRAINT `aktivitas_gpk_id_gpk_foreign` FOREIGN KEY (`id_gpk`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `aktivitas_gpk` ADD CONSTRAINT `aktivitas_gpk_id_laporan_bulanan_foreign` FOREIGN KEY (`id_laporan_bulanan`) REFERENCES `laporan_bulanan` (`id_laporan_bulanan`);
ALTER TABLE `catatan_pendampingan` ADD CONSTRAINT `catatan_pendampingan_id_pembuat_foreign` FOREIGN KEY (`id_pembuat`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `catatan_pendampingan` ADD CONSTRAINT `catatan_pendampingan_id_peninjau_gpk_foreign` FOREIGN KEY (`id_peninjau_gpk`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `catatan_pendampingan` ADD CONSTRAINT `catatan_pendampingan_id_siswa_foreign` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);
ALTER TABLE `catatan_perkembangan` ADD CONSTRAINT `catatan_perkembangan_id_mapel_foreign` FOREIGN KEY (`id_mapel`) REFERENCES `mata_pelajaran` (`id_mapel`);
ALTER TABLE `catatan_perkembangan` ADD CONSTRAINT `catatan_perkembangan_id_pembuat_foreign` FOREIGN KEY (`id_pembuat`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `catatan_perkembangan` ADD CONSTRAINT `catatan_perkembangan_id_peninjau_gpk_foreign` FOREIGN KEY (`id_peninjau_gpk`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `catatan_perkembangan` ADD CONSTRAINT `catatan_perkembangan_id_siswa_foreign` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);
ALTER TABLE `kelas` ADD CONSTRAINT `kelas_id_tahun_ajaran_foreign` FOREIGN KEY (`id_tahun_ajaran`) REFERENCES `tahun_ajaran` (`id_tahun_ajaran`);
ALTER TABLE `laporan_bulanan` ADD CONSTRAINT `laporan_bulanan_id_publikator_foreign` FOREIGN KEY (`id_publikator`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `laporan_perkembangan` ADD CONSTRAINT `laporan_perkembangan_id_pembuat_foreign` FOREIGN KEY (`id_pembuat`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `laporan_perkembangan` ADD CONSTRAINT `laporan_perkembangan_id_publikator_foreign` FOREIGN KEY (`id_publikator`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `laporan_perkembangan` ADD CONSTRAINT `laporan_perkembangan_id_rekap_foreign` FOREIGN KEY (`id_rekap`) REFERENCES `rekap_perkembangan` (`id_rekap`);
ALTER TABLE `orang_tua` ADD CONSTRAINT `orang_tua_id_pengguna_foreign` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `pengguna` ADD CONSTRAINT `pengguna_id_role_foreign` FOREIGN KEY (`id_role`) REFERENCES `role` (`id_role`);
ALTER TABLE `push_subscriptions` ADD CONSTRAINT `push_subscriptions_id_pengguna_foreign` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE;
ALTER TABLE `rekap_perkembangan` ADD CONSTRAINT `rekap_perkembangan_id_pembuat_foreign` FOREIGN KEY (`id_pembuat`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `rekap_perkembangan` ADD CONSTRAINT `rekap_perkembangan_id_siswa_foreign` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);
ALTER TABLE `relasi_guru_mapel_kelas` ADD CONSTRAINT `relasi_guru_mapel_kelas_id_kelas_foreign` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`);
ALTER TABLE `relasi_guru_mapel_kelas` ADD CONSTRAINT `relasi_guru_mapel_kelas_id_mapel_foreign` FOREIGN KEY (`id_mapel`) REFERENCES `mata_pelajaran` (`id_mapel`);
ALTER TABLE `relasi_guru_mapel_kelas` ADD CONSTRAINT `relasi_guru_mapel_kelas_id_pengguna_foreign` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `relasi_kelas_mapel` ADD CONSTRAINT `relasi_kelas_mapel_id_kelas_foreign` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`);
ALTER TABLE `relasi_kelas_mapel` ADD CONSTRAINT `relasi_kelas_mapel_id_mapel_foreign` FOREIGN KEY (`id_mapel`) REFERENCES `mata_pelajaran` (`id_mapel`);
ALTER TABLE `relasi_orang_tua_siswa` ADD CONSTRAINT `relasi_orang_tua_siswa_id_orang_tua_foreign` FOREIGN KEY (`id_orang_tua`) REFERENCES `orang_tua` (`id_orang_tua`);
ALTER TABLE `relasi_orang_tua_siswa` ADD CONSTRAINT `relasi_orang_tua_siswa_id_siswa_foreign` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);
ALTER TABLE `relasi_pendamping_siswa` ADD CONSTRAINT `relasi_pendamping_siswa_id_pengguna_foreign` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `relasi_pendamping_siswa` ADD CONSTRAINT `relasi_pendamping_siswa_id_siswa_foreign` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);
ALTER TABLE `riwayat_kelas_siswa` ADD CONSTRAINT `riwayat_kelas_siswa_id_kelas_foreign` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`);
ALTER TABLE `riwayat_kelas_siswa` ADD CONSTRAINT `riwayat_kelas_siswa_id_siswa_foreign` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);
ALTER TABLE `siswa` ADD CONSTRAINT `siswa_id_kelas_foreign` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`);
ALTER TABLE `sumber_rekap_catatan` ADD CONSTRAINT `sumber_rekap_catatan_id_catatan_pendampingan_foreign` FOREIGN KEY (`id_catatan_pendampingan`) REFERENCES `catatan_pendampingan` (`id_catatan_pendampingan`);
ALTER TABLE `sumber_rekap_catatan` ADD CONSTRAINT `sumber_rekap_catatan_id_catatan_perkembangan_foreign` FOREIGN KEY (`id_catatan_perkembangan`) REFERENCES `catatan_perkembangan` (`id_catatan_perkembangan`);
ALTER TABLE `sumber_rekap_catatan` ADD CONSTRAINT `sumber_rekap_catatan_id_rekap_foreign` FOREIGN KEY (`id_rekap`) REFERENCES `rekap_perkembangan` (`id_rekap`);
ALTER TABLE `verifikasi_catatan` ADD CONSTRAINT `verifikasi_catatan_id_catatan_pendampingan_foreign` FOREIGN KEY (`id_catatan_pendampingan`) REFERENCES `catatan_pendampingan` (`id_catatan_pendampingan`);
ALTER TABLE `verifikasi_catatan` ADD CONSTRAINT `verifikasi_catatan_id_catatan_perkembangan_foreign` FOREIGN KEY (`id_catatan_perkembangan`) REFERENCES `catatan_perkembangan` (`id_catatan_perkembangan`);
ALTER TABLE `verifikasi_catatan` ADD CONSTRAINT `verifikasi_catatan_id_publikator_foreign` FOREIGN KEY (`id_publikator`) REFERENCES `pengguna` (`id_pengguna`);
ALTER TABLE `verifikasi_catatan` ADD CONSTRAINT `verifikasi_catatan_id_verifikator_foreign` FOREIGN KEY (`id_verifikator`) REFERENCES `pengguna` (`id_pengguna`);

-- Dumping data for table `role`
--

INSERT INTO `role` VALUES (6,'Admin'),(3,'GPK'),(1,'Guru Mapel'),(4,'Kepala Sekolah'),(5,'Orang Tua'),(2,'Tim Inklusi');

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_16_150000_create_role_table',1),(5,'2026_10_16_150001_create_pengguna_table',1),(6,'2026_10_16_150002_create_kelas_table',1),(7,'2026_10_16_150003_create_siswa_table',1),(8,'2026_10_16_150004_create_orang_tua_table',1),(9,'2026_10_16_150005_create_mata_pelajaran_table',1),(10,'2026_10_16_150006_create_relasi_orang_tua_siswa_table',1),(11,'2026_10_16_150007_create_relasi_guru_mapel_kelas_table',1),(12,'2026_10_16_150008_create_relasi_pendamping_siswa_table',1),(13,'2026_10_16_150009_create_catatan_perkembangan_table',1),(14,'2026_10_16_150010_create_catatan_pendampingan_table',1),(15,'2026_10_16_150011_create_verifikasi_catatan_table',1),(16,'2026_10_16_150012_create_rekap_perkembangan_table',1),(17,'2026_10_16_150013_create_sumber_rekap_catatan_table',1),(18,'2026_10_16_150014_create_laporan_perkembangan_table',1),(19,'2026_10_16_161000_add_publication_guards_to_verifikasi_catatan_table',1),(20,'2026_10_17_090000_add_nip_to_pengguna_table',1),(21,'2026_10_19_100000_add_status_to_kelas_table',1),(22,'2026_10_19_100100_add_status_to_mata_pelajaran_table',1),(23,'2026_10_19_110000_create_relasi_kelas_mapel_table',1),(24,'2026_10_19_120000_add_password_reset_tracking_to_pengguna_table',1),(25,'2026_10_19_130000_add_foto_to_siswa_table',1),(26,'2026_10_19_140000_add_parent_temporary_password_status',1),(27,'2026_10_19_150000_make_pengguna_email_nullable',1),(28,'2026_10_20_190000_add_idempotency_key_to_catatan_pendampingan_table',1),(29,'2026_10_23_120000_create_laporan_bulanan_table',1),(30,'2026_10_24_100000_create_tahun_ajaran_and_riwayat_kelas',1),(31,'2026_10_29_000001_add_gpk_workflow_audit',1),(32,'2026_10_29_000002_backfill_gpk_activity_history',1),(33,'2026_10_29_000003_add_super_admin_protection',1),(34,'2026_10_30_000002_add_google_id_to_pengguna_table',1),(35,'2026_11_01_000001_create_push_subscriptions_table',1);




SET UNIQUE_CHECKS=1;
