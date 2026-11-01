<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Setup SMART-ABK dan data demo

Isi database MySQL tidak disimpan langsung di Git. Data demo SMART-ABK disimpan dalam
`database/seeders/RealisticDemoSeeder.php` sehingga setiap anggota tim dapat membuat
database yang sama setelah mengambil kode terbaru.

Untuk instalasi baru, siapkan koneksi database di `.env`, lalu jalankan:

```bash
composer run setup
```

Perintah tersebut menjalankan migrasi sekaligus memasukkan data demo. Jika proyek dan
database sudah pernah dibuat sebelumnya, ambil kode terbaru lalu jalankan:

```bash
composer run seed-demo
```

Seeder aman dijalankan ulang karena data utama dibuat dengan `updateOrCreate` atau
`firstOrCreate`. Jangan memakai `php artisan migrate:fresh --seed` pada database yang
berisi data penting karena perintah itu menghapus seluruh tabel terlebih dahulu.

## Google Login staf sekolah

Google Login hanya mengizinkan akun internal yang sudah dibuat Super Admin pada tabel
`pengguna`. Sistem tidak membuat akun atau menentukan role dari profil Google. Siapkan
OAuth Client bertipe **Web application** di Google Cloud Console, aktifkan consent
screen, lalu tambahkan redirect URI berikut secara persis:

```text
http://127.0.0.1:8000/auth/google/callback
```

Untuk production, gunakan domain HTTPS aplikasi dengan path yang sama. Isi konfigurasi
lokal tanpa memasukkan rahasia ke Git:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

Setelah menarik perubahan, jalankan `composer install` dan `php artisan migrate`.
Halaman `/masuk` adalah satu-satunya halaman login. Orang Tua/Wali menggunakan
username dan password yang dibuat Admin, sedangkan staf sekolah menggunakan tombol
`Masuk dengan Google`. Callback bersama `/auth/google/callback` mencocokkan email
Google terverifikasi dengan akun aktif pada tabel `pengguna`; aplikasi tidak membuat
akun atau menerima role dari URL maupun profil Google. Dashboard tujuan selalu
ditentukan oleh role yang tersimpan di database.

Route lama `/_dev`, dropdown role, dan session login development sudah dihapus.
Logout seluruh role menggunakan `POST /keluar`, mengakhiri semua guard terkait,
menginvalidasi session, meregenerasi token CSRF, lalu kembali ke `/masuk`.

Super Admin tetap dibuat atau dipulihkan dari terminal server menggunakan command
`php artisan app:super-admin` dengan password minimal 12 karakter. Jika Google ID
Super Admin perlu dilepas karena pemulihan akun, lakukan perubahan database secara
terkendali oleh pengelola server setelah identitas pemohon diverifikasi dan simpan
catatan audit operasional. Aplikasi sengaja tidak menyediakan tombol reset Google ID
melalui web.

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
