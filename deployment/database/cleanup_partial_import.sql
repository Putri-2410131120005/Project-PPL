-- SMART-ABK partial-import cleanup
-- RUN ONLY after read-only verification confirms every listed table is empty
-- and the active phpMyAdmin database is: if0_43332123_smart_abk
-- This script intentionally contains no CREATE DATABASE, USE, or DROP DATABASE.
-- It drops only the SMART-ABK tables listed below.

SET FOREIGN_KEY_CHECKS = 1;

DROP TABLE IF EXISTS `verifikasi_catatan`;
DROP TABLE IF EXISTS `sumber_rekap_catatan`;
DROP TABLE IF EXISTS `aktivitas_gpk`;
DROP TABLE IF EXISTS `laporan_perkembangan`;
DROP TABLE IF EXISTS `laporan_bulanan`;
DROP TABLE IF EXISTS `rekap_perkembangan`;
DROP TABLE IF EXISTS `catatan_pendampingan`;
DROP TABLE IF EXISTS `catatan_perkembangan`;
DROP TABLE IF EXISTS `relasi_pendamping_siswa`;
DROP TABLE IF EXISTS `relasi_guru_mapel_kelas`;
DROP TABLE IF EXISTS `relasi_kelas_mapel`;
DROP TABLE IF EXISTS `relasi_orang_tua_siswa`;
DROP TABLE IF EXISTS `riwayat_kelas_siswa`;
DROP TABLE IF EXISTS `push_subscriptions`;
DROP TABLE IF EXISTS `orang_tua`;
DROP TABLE IF EXISTS `siswa`;
DROP TABLE IF EXISTS `kelas`;
DROP TABLE IF EXISTS `tahun_ajaran`;
DROP TABLE IF EXISTS `mata_pelajaran`;
DROP TABLE IF EXISTS `pengguna`;
DROP TABLE IF EXISTS `role`;
DROP TABLE IF EXISTS `migrations`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;
