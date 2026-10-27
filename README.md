# Sistem Inventaris Laboratorium (UTS Praktikum Pemrograman Web, Tipe B)

Aplikasi CRUD sederhana untuk mendata barang inventaris pada setiap ruang
laboratorium. Dibangun dengan Laravel, PHP, MySQL/MariaDB, PDO, dan Blade
tanpa CSS maupun JavaScript.

- Mata kuliah: Praktikum Pemrograman Web (INF11197)
- NIM: 2401020014
- Basis data: `inventaris_lab_2401020014`
- Route utama: `/inventaris`

## Rancangan Data

Relasi satu-ke-banyak: satu `ruang` memiliki banyak `inventaris`.

| Tabel | Kolom |
|---|---|
| ruang | id (PK), nama_ruang (UNIQUE, NOT NULL) |
| inventaris | id (PK), kode_barang (UNIQUE), nama_barang, nomor_aset (UNIQUE), tahun_perolehan (CHECK 2000-2026), ruang_id (FK), created_at, updated_at |

Foreign key memakai `ON UPDATE CASCADE` dan `ON DELETE RESTRICT`.

## Cara Menjalankan

1. `composer install`
2. Salin `.env.example` menjadi `.env`, lalu `php artisan key:generate`
3. Aktifkan MySQL/MariaDB dan sesuaikan `DB_USERNAME` / `DB_PASSWORD` di `.env`
4. Impor SQL (berurutan):

   ```sql
   SOURCE database/sql/inventaris_struktur.sql;
   SOURCE database/sql/inventaris_data.sql;
   ```
5. `php artisan config:clear`
6. `php artisan serve`, lalu buka `http://127.0.0.1:8000/inventaris`

`SESSION_DRIVER`, `CACHE_STORE`, dan `QUEUE_CONNECTION` diatur ke `file`/`sync`
sehingga tidak membutuhkan tabel tambahan di basis data.

## Daftar Route

| Method | URI | Nama | Fungsi |
|---|---|---|---|
| GET | /inventaris | inventaris.index | Read, pencarian, filter |
| POST | /inventaris | inventaris.store | Create |
| GET | /inventaris/{id}/edit | inventaris.edit | Form edit |
| PUT | /inventaris/{id} | inventaris.update | Update |
| DELETE | /inventaris/{id} | inventaris.destroy | Delete |

## Fitur Utama

- Pencarian pada `kode_barang`, `nama_barang`, dan `nomor_aset`; filter berdasarkan `ruang_id`.
- Sanitasi `trim` + `strip_tags`, validasi server-side, pesan error, `old()`, `@csrf`, dan method spoofing PUT/DELETE.
- Semua query yang menerima input memakai prepared statement PDO.
- Kesalahan koneksi/query ditangkap dengan `try-catch`, dicatat lewat `report()`, dan pengguna hanya menerima pesan singkat yang aman.
- Fungsi `statusUsiaAset()` menampilkan `Baru` (tahun >= 2023) atau `Lama`.

File `.env` tidak disertakan dalam repository.
