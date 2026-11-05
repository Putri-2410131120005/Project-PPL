# Inisialisasi database production tanpa SSH

Database production harus dibuat sebagai database baru di VistaPanel. Jangan
mengekspor atau mengimpor database development secara keseluruhan.

## Skema

1. Di komputer lokal, buat salinan kerja database kosong yang memakai versi
   migration yang sama dengan repository.
2. Jalankan semua migration pada salinan kerja tersebut.
3. Di phpMyAdmin, pilih database salinan kerja dan gunakan Export → Custom.
4. Pilih seluruh tabel, format SQL, Structure only, tanpa data, tanpa `DROP
   DATABASE`, dan tanpa kredensial.
5. Simpan hasil sebagai `smart-abk-production-schema.sql`.
6. Pilih database production yang baru di phpMyAdmin lalu Import file SQL itu.

Jangan gunakan `migrate:fresh`, `db:seed` dengan data operasional, atau export
yang menyertakan tabel pengguna/siswa/catatan dari database development.

## Data referensi wajib

Setelah schema berhasil diimpor, jalankan SQL referensi berikut di phpMyAdmin:

```sql
INSERT IGNORE INTO `role` (`nama_role`) VALUES
('Guru Mapel'),
('Tim Inklusi'),
('GPK'),
('Kepala Sekolah'),
('Orang Tua'),
('Admin');
```

`INSERT IGNORE` mempertahankan role yang sudah ada dan hanya menambahkan role
yang belum tersedia.

## Super Admin pertama

Jangan membuat route publik untuk Artisan. Karena InfinityFree tidak menyediakan
SSH, siapkan akun awal secara offline dan terkontrol:

1. Buat hash password baru menggunakan `php artisan tinker` atau helper PHP
   lokal yang memakai `Hash::make`; jangan memasukkan password asli ke Git,
   SQL, screenshot, atau laporan.
2. Di phpMyAdmin, pastikan role `Admin` sudah ada.
3. Buat satu baris `pengguna` dengan password hash, status `Aktif`, dan
   `is_super_admin=1` menggunakan SQL yang diisi manual saat instalasi.
4. Verifikasi email akun tersebut di Google OAuth dan lakukan login Google.
5. Setelah login pertama, hapus file SQL sementara dan catat pelaksanaan secara
   offline.

Kolom serta nilai akun harus mengikuti schema production yang diimpor. Jangan
memakai username, email, atau password default dari source code.
