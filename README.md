
<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

# Blog API

Aplikasi **Blog API** ini dibangun menggunakan Laravel 12 dengan PHP versi 8.2 ke atas, MySQL sebagai database, dan dokumentasi API menggunakan Swagger.  
Aplikasi ini berfungsi sebagai backend API untuk sistem blog yang menyediakan endpoint untuk manajemen postingan, kategori, dan pengguna.

---

## Fitur Utama

- RESTful API untuk manajemen blog (post, kategori, user)
- Dokumentasi API interaktif menggunakan Swagger (OpenAPI)
- Otentikasi dan otorisasi berbasis token
- Penggunaan Laravel Eloquent ORM untuk manajemen data
- Mendukung Laravel 12 dan PHP 8.2+
- Migration dan seeding database

---

## Persyaratan Sistem

- PHP 8.2 atau lebih baru
- Composer
- MySQL 5.7+ atau MariaDB
- Web server (Apache/Nginx)

---

## Cara Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/valentviktor/laravel-blog-api
cd laravel-blog-api
```

### 2. Install Dependencies

Jalankan composer untuk menginstal paket yang dibutuhkan:

```bash
composer install
```

### 3. Setup File `.env`

Salin file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Edit file `.env` sesuai konfigurasi lokal atau server Anda:

```env
APP_NAME=BlogAPI
APP_ENV=local
APP_KEY= (akan di-generate)
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blog_api_db
DB_USERNAME=root
DB_PASSWORD=secret
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Migrasi dan Seed Database

Untuk membuat tabel dan data awal:

```bash
php artisan migrate --seed
```

Jika tidak ingin seed data, jalankan:

```bash
php artisan migrate
```

### 6. Jalankan Server Development

```bash
php artisan serve
```

Jangan lupa untuk membuat link storage
```bash
php artisan storage:link
```

Akses API di:  
`http://localhost:8000`

### 7. Dokumentasi API Swagger

Setelah server berjalan, akses dokumentasi API di:

```
http://localhost:8000/api/documentation
```

---

## Cara Penggunaan

- Gunakan API endpoint sesuai dokumentasi Swagger.
- Login dan dapatkan token untuk mengakses API yang dilindungi.
- Gunakan fitur CRUD untuk blog post, kategori, dan user.

---

## Kontribusi

Silakan fork dan buat pull request untuk kontribusi fitur atau perbaikan bug.

---

## Lisensi

MIT License. Silakan lihat file LICENSE untuk detail.

---

## Kontak

Nama: Viktorius Valentino  
Email: viktorvalents@gmail.com
