# Hierarchical Back Navigation SMART-ABK

Dokumen ini mencatat parent struktural untuk setiap halaman terlindungi yang memiliki antarmuka. Route unduhan/ekspor dan endpoint mutasi tidak dianggap halaman navigasi.

## Matriks hierarki

| Role | Halaman | Named route | Parent | Kondisi khusus |
|---|---|---|---|---|
| Admin | Dashboard | `dev.preview` / `staff.admin.dashboard` | Root | Back meminta konfirmasi logout |
| Admin | Tahun Ajaran | `dev.admin.academic-years.index` | Dashboard | Filter GET tidak dianggap form kotor |
| Admin | Tambah/Ubah Tahun Ajaran | `dev.admin.academic-years.create/edit` | Tahun Ajaran | Konfirmasi jika form berubah |
| Admin | Pengguna | `dev.admin.users.index` | Dashboard | Konteks filter dipertahankan bila berasal dari daftar |
| Admin | Data Guru | `dev.admin.staff.index` | Pengguna | — |
| Admin | Data Orang Tua/Wali | `dev.admin.parents.index` | Pengguna | — |
| Admin | Tambah/Ubah Pengguna, Guru, Orang Tua | `dev.admin.users.create/edit`, `dev.admin.staff.create/edit`, `dev.admin.users.parents.create` | Pengguna | Konfirmasi jika form berubah |
| Admin | Siswa ABK | `dev.admin.students.index` | Dashboard | — |
| Admin | Tambah/Ubah Siswa | `dev.admin.students.create/edit` | Siswa ABK | Konfirmasi jika form berubah |
| Admin | Kelas | `dev.admin.classes.index` | Dashboard | Grup “Kelas & Mata Pelajaran” bukan halaman tersendiri |
| Admin | Tambah/Ubah Kelas | `dev.admin.classes.create/edit` | Kelas | Konfirmasi jika form berubah |
| Admin | Mata Pelajaran | `dev.admin.subjects.index` | Dashboard | Grup “Kelas & Mata Pelajaran” bukan halaman tersendiri |
| Admin | Tambah/Ubah Mata Pelajaran | `dev.admin.subjects.create/edit` | Mata Pelajaran | Konfirmasi jika form berubah |
| Admin | Penugasan Guru | `dev.admin.mappings.guru-mapel-kelas.index` | Dashboard | “Hubungan Data” bukan halaman tersendiri |
| Admin | Pendamping Siswa | `dev.admin.mappings.pendamping-siswa.index` | Dashboard | — |
| Admin | Orang Tua Siswa | `dev.admin.mappings.orang-tua-siswa.index` | Dashboard | — |
| Admin | Hak Akses | `dev.admin.mappings.access-rights.index` | Dashboard | — |
| GPK | Dashboard | `dev.gpk.dashboard` / `staff.gpk.dashboard` | Root | Back meminta konfirmasi logout |
| GPK | Data Siswa | `dev.gpk.students.index` | Dashboard | — |
| GPK | Detail Siswa | `dev.gpk.students.show` | Data Siswa | Filter/pagination daftar dipertahankan bila tersedia |
| GPK | Verifikasi Catatan | `dev.gpk.notes.index` | Dashboard | — |
| GPK | Detail/Verifikasi Catatan | `dev.gpk.notes.show` | Verifikasi Catatan | Form keputusan yang berubah dilindungi |
| GPK | Riwayat Verifikasi | `dev.gpk.history.index` | Dashboard | — |
| GPK | Rekap | `dev.gpk.recaps.index` | Dashboard | — |
| GPK | Detail Rekap | `dev.gpk.recaps.show` | Rekap | Semester/tahun ajaran dan konteks daftar aman dipertahankan |
| GPK | Pratinjau PDF Rekap | `dev.gpk.recaps.pdf.preview` | Detail Rekap | Tombol Back internal memakai parent yang sama |
| GPK | Laporan | `dev.gpk.reports.index` | Dashboard | — |
| GPK | Detail Laporan | `dev.gpk.reports.show` | Laporan | — |
| GPK | Pratinjau PDF Laporan | `dev.gpk.reports.pdf.preview` | Detail Laporan | — |
| GPK | Data Orang Tua/Wali | `dev.gpk.parents.index` | Dashboard | — |
| Tim Inklusi | Dashboard | `dev.tim-inklusi.dashboard` / `staff.tim-inklusi.dashboard` | Root | Back meminta konfirmasi logout |
| Tim Inklusi | Data Siswa | `dev.tim-inklusi.students.index` | Dashboard | — |
| Tim Inklusi | Detail Siswa | `dev.tim-inklusi.students.show` | Data Siswa | — |
| Tim Inklusi | Rekomendasi Pendampingan | `dev.tim-inklusi.students.recommendation` | Detail Siswa | — |
| Tim Inklusi | Detail Catatan Perkembangan | `dev.tim-inklusi.development-notes.show` | Detail Siswa | — |
| Tim Inklusi | Tambah Catatan | `dev.tim-inklusi.notes.create` | Detail Siswa | Konfirmasi jika form berubah |
| Tim Inklusi | Riwayat Catatan | `dev.tim-inklusi.notes.index` | Dashboard | — |
| Tim Inklusi | Detail Catatan Pendampingan | `dev.tim-inklusi.notes.show` | Riwayat Catatan | — |
| Tim Inklusi | Edit/Perbaiki Catatan | `dev.tim-inklusi.notes.edit` | Detail Catatan Pendampingan | Konfirmasi jika form berubah |
| Guru Mapel | Dashboard | `dev.guru-mapel.dashboard` / `guru-mapel.dashboard` | Root | Back meminta konfirmasi logout |
| Guru Mapel | Data Siswa | `*.students.index` | Dashboard | Prefix mengikuti mode development/production |
| Guru Mapel | Detail Siswa | `*.students.show` | Data Siswa | — |
| Guru Mapel | Rekomendasi | `*.recommendations.show` | Detail Siswa | — |
| Guru Mapel | Detail Catatan Referensi | `*.references.show` | Detail Siswa | ID siswa diselesaikan dari catatan yang sudah lolos otorisasi controller |
| Guru Mapel | Tambah Catatan | `*.notes.create` | Detail Siswa | Konfirmasi jika form berubah |
| Guru Mapel | Riwayat Catatan | `*.notes.status` | Dashboard | — |
| Guru Mapel | Detail Catatan | `*.notes.show` | Riwayat Catatan | — |
| Guru Mapel | Edit/Perbaiki Catatan | `*.notes.revise` | Detail Catatan | Konfirmasi jika form berubah |
| Kepala Sekolah | Dashboard | `dev.principal.dashboard` / `staff.principal.dashboard` | Root | Back meminta konfirmasi logout |
| Kepala Sekolah | Data Siswa | `dev.principal.students` | Dashboard | — |
| Kepala Sekolah | Detail Siswa | `dev.principal.student` | Data Siswa | — |
| Kepala Sekolah | Rekap | `dev.principal.recaps` | Dashboard | — |
| Kepala Sekolah | Detail Rekap Terverifikasi | `dev.principal.verified-recap` | Rekap | Semester/tahun ajaran dipertahankan |
| Kepala Sekolah | Pratinjau PDF Rekap Terverifikasi | `dev.principal.verified-recap.preview` | Detail Rekap Terverifikasi | — |
| Kepala Sekolah | Detail Rekap Resmi | `dev.principal.recap` | Rekap | — |
| Kepala Sekolah | Detail Catatan Sumber | `dev.principal.note` | Detail Rekap Resmi | — |
| Kepala Sekolah | Rekap Semester | `dev.principal.semester` | Detail Rekap Resmi | — |
| Kepala Sekolah | Pratinjau Rekap | `dev.principal.recap.preview` | Detail Rekap Resmi | — |
| Kepala Sekolah | Arsip Laporan | `dev.principal.archives` | Dashboard | — |
| Kepala Sekolah | Detail Laporan Periode | `dev.principal.period` | Arsip Laporan | — |
| Kepala Sekolah | Pratinjau Laporan Periode | `dev.principal.period.preview` | Detail Laporan Periode | — |
| Orang Tua | Dashboard | `dev.parent.dashboard` | Root | Back meminta konfirmasi logout |
| Orang Tua | Wajib Ganti Password | `dev.parent.password.edit` | Root autentikasi | Back meminta konfirmasi logout karena Dashboard belum boleh diakses |
| Orang Tua | Catatan Perkembangan | `dev.parent.notes.index` | Dashboard | — |
| Orang Tua | Detail Catatan | `dev.parent.notes.show` | Catatan Perkembangan | Hanya query filter yang diizinkan yang dipertahankan |
| Orang Tua | Laporan Perkembangan | `dev.parent.reports.index` | Dashboard | — |

## Cara kerja

- `App\Support\NavigationHierarchy` adalah sumber parent server-side berdasarkan named route dan parameter route yang sudah cocok.
- URL parent tidak diterima sebagai redirect mentah. Hanya named route, parameter route, dan query allow-list yang digunakan.
- Layout menulis metadata parent/root ke `<body>`; handler terpusat di `resources/js/app.js` menangani tombol Back browser/perangkat.
- Satu pasangan entry halaman/trap dibuat per dokumen untuk menangkap Back. Semua link internal memakai `location.replace()`, lalu handler memakai `location.replace()` ke parent sehingga trap halaman lama tidak menumpuk dan halaman anak tidak hidup kembali melalui Forward.
- Konteks filter/pagination dapat dibawa lewat `sessionStorage`, tetapi hanya diterima jika origin dan pathname sama persis dengan parent server-side. Konteks tersimpan hanya dipakai ulang saat refresh halaman yang sama; pembukaan URL langsung baru memakai parent server-side agar konteks lama tidak bocor.
- Form non-GET dibandingkan dengan snapshot awal. Back, klik link, dan penutupan halaman meminta konfirmasi bila nilai berubah; submit normal tidak diganggu.
- Dari Dashboard pada tab browser biasa, Back membuka modal logout yang sudah ada. Logout hanya berjalan setelah konfirmasi dan endpoint POST/CSRF yang sudah digunakan aplikasi.
- Dari Dashboard pada PWA yang terinstal (`standalone`/`fullscreen`), Back tidak diintersepsi. Sistem operasi menangani Back seperti aplikasi biasa sehingga aplikasi ditutup atau diminimalkan tanpa logout.
- Sebelum Google OAuth dimulai dari PWA, posisi history dicatat. Setelah callback sukses mencapai Dashboard, entry OAuth dilewati dan entry aplikasi awal diganti dengan Dashboard agar Back tidak kembali ke pemilih akun Google.
- Halaman yang dipulihkan dari BFCache setelah logout dimuat ulang sehingga middleware autentikasi memeriksa sesi lagi.

## Batasan platform

Browser tidak menyediakan API andal untuk menutup tab atau jendela PWA secara paksa. Karena itu implementasi tidak memakai `window.close()`: PWA terinstal menyerahkan Back root kepada OS, sementara tab browser memakai logout terkonfirmasi. Perilaku akhir PWA (menutup atau meminimalkan) ditentukan platform. Jika JavaScript dinonaktifkan, breadcrumb dan tombol Back internal tetap menjadi fallback navigasi, sedangkan Back browser kembali ke perilaku native.
