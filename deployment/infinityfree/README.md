# SMART-ABK di InfinityFree

## Struktur upload

InfinityFree menggunakan `htdocs` sebagai web root. Karena document root tidak
dapat diarahkan ke folder Laravel `public`, upload seluruh source aplikasi ke
dalam `htdocs`, lalu pasang `htdocs.htaccess` sebagai `.htaccess` langsung di
`htdocs`.

Upload dari komputer lokal:

- source Laravel: `app`, `bootstrap`, `config`, `database`, `resources`, `routes`
- runtime: `vendor`, `storage`, `public`, `artisan`
- file environment production yang sudah diisi secara manual: `.env`
- hasil frontend: `public/build`

Jangan upload `node_modules`, `.git`, `.env` development, file test, atau folder
`deployment`.

InfinityFree tidak mendukung SSH, Composer, Node/NPM, dan symlink. Karena itu
Composer dan Vite harus dijalankan di komputer lokal. `public/storage` tidak
boleh diupload sebagai symlink. Folder `storage` harus dapat ditulis oleh PHP,
tetapi tidak boleh dapat diakses langsung melalui browser.

## Pemeriksaan wajib sebelum pilot

Setelah upload, lakukan probe URL pada `security-probes.txt`. Jika salah satu
file sensitif menghasilkan HTTP 200 atau menampilkan isi file, hentikan pilot.
Rewrite bukan pengganti verifikasi server; aplikasi tidak boleh dipakai sebelum
semua probe sensitif menghasilkan 403/404.

## Environment production

Salin `.env.production.example` menjadi `.env` secara lokal, isi database,
domain HTTPS, Google OAuth, dan VAPID. Upload `.env` hanya melalui FTP setelah
memeriksa bahwa nama file benar. Jangan commit atau mengirimkannya melalui
GitHub.

`PROTECTED_STUDENT_PHOTOS=true` membuat foto siswa hanya dikirim oleh Laravel
setelah pemeriksaan role/relasi. Direct `/storage/...` sengaja diblokir.

## Cache dan konfigurasi

Tanpa terminal server, jangan mengandalkan `php artisan config:cache`. File
`.env` dibaca pada setiap request. Pastikan folder berikut writable:

- `storage/framework/cache/data`
- `storage/framework/sessions`
- `storage/framework/views`
- `storage/logs`
- `bootstrap/cache`

## Queue dan Web Push

Production InfinityFree memakai `QUEUE_CONNECTION=sync` dan timeout Web Push
terbatas. Kegagalan provider dicatat ke log dan tidak menggagalkan transaksi
utama. Ini menghindari job database yang tidak pernah diproses karena tidak ada
worker permanen.
