# RawaRukun

Portal warga untuk melaporkan eceng gondok, memantau penanganan, mengikuti kerja bakti, dan mengumpulkan poin kepedulian. Dibangun dengan Laravel 12 dan MySQL.

## Fitur

- Akun warga dengan nama, RT/RW, email, dan kata sandi.
- Laporan lokasi, kepadatan eceng gondok, keterangan, serta foto opsional.
- Papan laporan dengan filter status dan kepadatan.
- Peta interaktif wilayah Sidoarjo dengan pin yang dipilih saat membuat laporan, warna kepadatan, dan tautan titik ke Google Maps.
- Dashboard admin untuk memperbarui status laporan.
- Jadwal kerja bakti yang dibuat admin dan dapat dilihat warga.
- Poin warga: 10 poin per laporan, setara Rp1.000, dengan pencatatan permintaan penukaran pulsa, token listrik, dan e-wallet (DANA, GoPay, OVO, ShopeePay).
- Admin dapat memfilter dan memproses penukaran poin; permintaan yang ditolak mengembalikan poin warga secara otomatis.
- Halaman edukasi dampak, penanganan, dan pemanfaatan eceng gondok.

## Menjalankan lokal dengan XAMPP

1. Jalankan Apache dan MySQL melalui XAMPP.
2. Buat database MySQL bernama `rawarukun` melalui phpMyAdmin, atau jalankan `CREATE DATABASE rawarukun CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;` pada klien MySQL.
3. Pastikan `.env` memakai `DB_CONNECTION=mysql`, host `127.0.0.1`, port `3306`, database `rawarukun`, dan kredensial MySQL lokal Anda.
4. Atur `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` di `.env`. Akun admin hanya dibuat oleh seeder; pendaftaran publik selalu membuat akun warga.
5. Jalankan perintah:

   ```sh
   php artisan key:generate
   php artisan migrate
   php artisan db:seed
   php artisan storage:link
   php artisan serve
   ```

6. Buka `http://127.0.0.1:8000`.

Foto laporan disimpan pada disk publik Laravel. Permintaan penukaran poin dicatat dengan status menunggu untuk ditindaklanjuti pengelola.

Peta menggunakan tile OpenStreetMap melalui Leaflet dan tidak memerlukan Google Maps API key. Titik laporan disimpan sebagai koordinat, dibatasi pada area peta Sidoarjo, dan dapat dibuka di Google Maps dari popup pin. Laporan lama tanpa koordinat tetap tampil pada daftar, tetapi belum memiliki pin peta.

## Tes

Jalankan `php artisan test`. Tes menggunakan SQLite in-memory dan mencakup pemberian poin, penukaran, serta pembatasan perubahan status laporan oleh warga.<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

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
