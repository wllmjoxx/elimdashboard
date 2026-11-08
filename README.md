# Website GGP Elim

Rumah digital GGP Elim (Rumah Kasih Karunia): landing page publik, akun jemaat,
form doa mingguan, kesaksian anonim, renungan, galeri, dan panel admin.
Dibangun bertahap sesuai PRD (Tahap 1–5).

**Stack:** Laravel 13 · Inertia 3 + React 19 + TypeScript · Tailwind CSS 4 ·
Fortify (auth) · Wayfinder (route bertipe) · MySQL/MariaDB (produksi), SQLite (lokal & tes)

## Mulai

Butuh PHP 8.3+, Composer 2, dan Node 22+.

```bash
composer setup                     # install dependensi, .env, key, migrate, build
php artisan migrate:fresh --seed   # data dummy dari semua modul
composer dev                       # http://localhost:8000
```

Akun dummy: `test@example.com` / `password`.

## Struktur

Aplikasi berbentuk _modular monolith_. Setiap fitur adalah modul di
`app/Modules/<Nama>` yang mendaftarkan sendiri route, migrasi, permission,
menu sidebar, seeder, dan feature flag-nya. Halaman React-nya ada di
`resources/js/pages/<key>/` dan tesnya di `tests/Modules/<Nama>/`.

```bash
php artisan make:module Events --title="Kegiatan" --icon=CalendarDays
```

Modul Tahap 1: `Core`, `Members`, `Prayer`, `Testimonies`, `Devotionals`,
`Gallery`, `Landing`.

## Pengecekan

```bash
composer ci:check   # format & lint (Pint, vp check), tipe (tsc), tes (PHPUnit)
```

CI GitHub Actions menjalankan hal yang sama di setiap pull request.

## Bekerja berdua

Baca [CONTRIBUTING.md](CONTRIBUTING.md): pembagian modul, file bersama, alur
branch dan PR, serta cara menyelesaikan konflik (termasuk lockfile).
