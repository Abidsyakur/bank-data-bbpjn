# Pengujian Otomatis Bank Data Terpusat BBPJN

Melengkapi lembar Black-Box manual (`Pengujian_Black-Box_Bank_Data_BBPJN.xlsx`) dengan tes yang dijalankan mesin.

## Isi

| Jenis | Lokasi | Isi |
|---|---|---|
| Tes unit | `tests/Unit` | Format ukuran, label aksi log (dan pemindai aksi tanpa label), pesan validasi, filter jalur ZIP berbahaya, model, aturan akses `DokumenPolicy`, `BerkasService`, periode laporan |
| Tes fitur | `tests/Feature` | Login dan pembatasan percobaan, hak akses per peran dan layanan, akun, dokumen dan unggah berkas, tindak lanjut, pencarian, dashboard dan persentase, laporan PDF/Excel, riwayat aktivitas, backup dan restore |
| Performa | `tests/Feature/PerformaTest.php`, `tests/Performance/k6-beban.js` | Deteksi N+1, batas waktu pada data besar, uji beban |
| Analisis statis | `phpstan.neon`, Pint | Kualitas dan gaya kode |
| Keamanan dependensi | `composer audit`, `npm audit` | Kerentanan paket |

Pemetaan ke lembar Black-Box: LGN, HAK, AKN, DOK, TLJ, CRI, DSH, LAP, LOG, BKP masing-masing memiliki tes otomatis pada kelas yang bernama sejalan (AuthTest, HakAksesTest, AkunTest, DokumenTest, TindakLanjutTest, PencarianTest, DashboardTest, LaporanTest, RiwayatTest, BackupTest).

## Menjalankan

```
php artisan test                         # semua tes (SQLite di memori, tanpa MySQL)
php artisan test --testsuite=Unit        # hanya tes unit
php artisan test --filter=DokumenTest    # satu kelas
```

Backup dan restore memakai `SET FOREIGN_KEY_CHECKS`, jadi tesnya dilewati pada SQLite. Jalankan pada MySQL/MariaDB dengan database khusus uji (nama harus berakhiran `_test`, kalau tidak tes menolak berjalan agar database asli tidak terhapus):

```
:: buat database bank_data_bbpjn_test di phpMyAdmin lebih dulu
set DB_CONNECTION=mysql
set DB_DATABASE=bank_data_bbpjn_test
php artisan test
```

## Coverage

Coverage memerlukan ekstensi PCOV atau Xdebug pada PHP.

- PCOV (lebih ringan): unduh php_pcov.dll yang sesuai versi PHP, thread safety, dan arsitektur dari PECL, taruh di `C:\xampp\php\ext`, lalu tambahkan di php.ini `extension=pcov` dan `pcov.enabled=1`.
- Xdebug: ikuti wizard di xdebug.org, lalu `zend_extension=xdebug` dan `xdebug.mode=coverage`.
- Periksa dengan `php -m` (harus memuat pcov atau xdebug).

```
php artisan test --coverage --min=70
php artisan test --coverage-html storage/coverage     # buka storage/coverage/index.html
```

Target 70% sudah realistis; mengejar 100% tidak perlu. Kelas yang bergantung pada MySQL (BackupService) baru tercakup saat tes dijalankan pada MySQL.

## Performa

1. Deteksi N+1 dan batas waktu pada 3.000 sampai 5.000 baris:
```
set RUN_PERFORMANCE_TESTS=1
php artisan test --filter=PerformaTest
```
2. Uji beban dengan k6 pada data besar:
```
php artisan db:seed --class=PerformaSeeder
k6 run tests/Performance/k6-beban.js
```
Jalankan terhadap Apache (XAMPP), bukan `php artisan serve`, karena server bawaan PHP hanya melayani satu permintaan pada satu waktu. Pembersihan data contoh ada di komentar PerformaSeeder.
3. Kinerja sisi peramban: Chrome DevTools > Lighthouse (Performance, Accessibility, Best Practices) pada /login dan /dashboard.

## Kualitas dan keamanan

```
vendor\bin\pint --test                         # periksa gaya kode (tanpa --test = rapikan otomatis)
composer require --dev larastan/larastan       # sekali saja
vendor\bin\phpstan analyse --memory-limit=1G   # analisis statis level 5
composer audit
npm audit --omit=dev
```

## Batasan

- Tes ini ditulis tanpa dapat dijalankan pada Laravel di lingkungan penulisnya. Sintaks seluruh berkas telah diperiksa, dan tes unit murni telah dijalankan dengan pelari tiruan PHPUnit. Tes fitur perlu dijalankan pertama kali oleh pengembang dan mungkin memerlukan penyesuaian kecil.
- Tes tidak menggantikan uji penetrasi, uji keterpakaian oleh pengguna sebenarnya, ataupun pembaruan PHP dan dependensi secara berkala.
