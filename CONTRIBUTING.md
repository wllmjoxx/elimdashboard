# Panduan Kontribusi (2 developer, 1 repo, minim konflik)

Repo ini dikerjakan dua orang sekaligus. Aturan di bawah dibuat supaya kalian
hampir tidak pernah mengedit file yang sama di waktu yang sama. Kalau tetap
terjadi konflik, cara menyelesaikannya ada di bagian akhir.

## 1. Prinsip utama: satu fitur = satu folder modul

Setiap fitur hidup di foldernya sendiri dan **mendaftarkan dirinya sendiri**.
Menambah atau mengubah modul tidak perlu menyentuh file bersama.

| Bagian                                                 | Lokasi                                                            |
| ------------------------------------------------------ | ----------------------------------------------------------------- |
| Provider, flag fitur, permission, menu sidebar, seeder | `app/Modules/<Nama>/<Nama>ServiceProvider.php`                    |
| Konfigurasi & feature flag                             | `app/Modules/<Nama>/config.php` → `config('modules.<key>.*')`     |
| Route (nama route wajib berawalan `<key>.`)            | `app/Modules/<Nama>/routes/web.php`                               |
| Controller, Request, Policy, Action, Model             | `app/Modules/<Nama>/Http/...`, `Models/`, `Actions/`, `Policies/` |
| Migrasi                                                | `app/Modules/<Nama>/Database/Migrations/`                         |
| Seeder (data dummy)                                    | `app/Modules/<Nama>/Database/Seeders/`                            |
| Halaman React (Inertia)                                | `resources/js/pages/<key>/`                                       |
| Komponen & ikon khusus modul                           | `resources/js/modules/<key>/` (ikon sidebar di `icons.ts`)        |
| Tes                                                    | `tests/Modules/<Nama>/`                                           |

Modul Tahap 1 yang sudah dibuat:

| Modul       | Key           | Isi (PRD Tahap 1)                                                                                                         |
| ----------- | ------------- | ------------------------------------------------------------------------------------------------------------------------- |
| Core        | `core`        | Cabang (`churches`, `church_id` + global scope), role & permission (spatie), login OTP email, audit log, pengaturan situs |
| Members     | `members`     | Registrasi + persetujuan Admin, profil jemaat, data jemaat                                                                |
| Prayer      | `prayer`      | Form doa (tamu & login), doa mingguan, tandai didoakan/terjawab, ekspor PDF                                               |
| Testimonies | `testimonies` | Kesaksian anonim (tanpa `user_id`) + moderasi                                                                             |
| Devotionals | `devotionals` | Renungan harian: draf → tinjau → terbit, jadwal & arsip                                                                   |
| Gallery     | `gallery`     | Album & foto, kompres otomatis, urutan                                                                                    |
| Landing     | `landing`     | Landing page publik, section dinamis (urutan, tampil/sembunyi), jadwal ibadah, kontak                                     |

Membuat modul baru:

```bash
php artisan make:module Events --title="Kegiatan" --icon=CalendarDays
```

Perintah itu membuat provider, config, route, controller, seeder, folder
migrasi, halaman, ikon, dan tes. Modul langsung terdaftar otomatis.

Generator lain yang langsung masuk ke folder modul:

```bash
php artisan make:model 'App\Modules\Prayer\Models\PrayerRequest'
php artisan make:migration create_prayer_requests_table --path=app/Modules/Prayer/Database/Migrations
php artisan make:controller 'App\Modules\Prayer\Http\Controllers\PrayerRequestController'
php artisan make:request 'App\Modules\Prayer\Http\Requests\StorePrayerRequest'
php artisan make:policy 'App\Modules\Prayer\Policies\PrayerRequestPolicy'
```

## 2. Pembagian kerja

- **Satu modul dikerjakan satu orang dalam satu waktu.** Sebelum mulai, buat
  atau ambil issue di GitHub dan assign ke diri sendiri. Kalau partner sedang
  memegang modul itu, ambil modul lain atau sepakati dulu.
- Butuh sesuatu dari modul partner (misalnya model `Church` dari Core)? Pakai
  saja, tapi minta perubahan di modul itu lewat issue atau PR kecil terpisah.
- Antarmodul berkomunikasi lewat model, action/service class, atau event.
  Jangan menyalin kode dari modul lain.

## 3. File bersama (hati-hati)

File berikut dipakai semua modul. Ubah **hanya lewat PR kecil tersendiri**,
kabari partner sebelum mulai, dan merge secepatnya:

- `app/Support/**`, `app/Providers/**`, `bootstrap/**`, `config/**` (selain config modul)
- `app/Models/User.php`, `database/migrations/` bawaan, `database/seeders/DatabaseSeeder.php`
- `resources/js/components/**` (komponen UI bersama & komponen Glass), `resources/js/layouts/**`, `resources/css/app.css` (token desain)
- `composer.json`, `package.json`, `vite.config.ts`, `.github/**`

Contoh: komponen `Glass` dan token desain liquid glass (PRD 4.9) dibuat sekali
di PR terpisah, di-merge, baru dipakai oleh modul lain.

## 4. Alur Git

1. `main` selalu hijau dan bisa di-deploy. Tidak ada push langsung ke `main`.
2. Buat branch pendek dari `main` terbaru:
    - `feat/<modul>-<deskripsi>`, contoh `feat/prayer-form-doa`
    - `fix/<modul>-<deskripsi>`, `chore/<deskripsi>`, `docs/<deskripsi>`
3. Jaga PR **kecil** (idealnya < 400 baris) dan **berumur pendek** (≤ 2–3 hari).
   Fitur besar dipecah: migrasi + model dulu, lalu halaman admin, lalu halaman publik.
4. Sinkronkan dengan `main` minimal sekali sehari:
    ```bash
    git fetch origin
    git rebase origin/main
    ```
5. Sebelum push, jalankan pengecekan yang sama dengan CI:
    ```bash
    composer lint         # rapikan PHP (Pint)
    npm run check:fix     # rapikan & lint TypeScript
    composer ci:check     # format, lint, tipe, tes
    ```
6. Buka PR, isi template, tunggu CI hijau dan review partner, lalu
   **Squash and merge**. Hapus branch setelah merge.
7. Pesan commit memakai format Conventional Commits:
   `feat(prayer): tambah form doa untuk tamu`, `fix(core): ...`, `chore: ...`.

## 5. Aturan supaya tidak bentrok

- **Migrasi:** jangan pernah mengedit migrasi yang sudah ada di `main`. Buat
  migrasi baru. Nama file bertimestamp, jadi dua orang bisa menambah migrasi
  tanpa konflik.
- **Nama route** berawalan key modul (`prayer.index`, `prayer.weekly`).
- **Nama permission** berawalan key modul (`prayer.view`, `prayer.mark-prayed`)
  dan didaftarkan di `permissions()` milik modul.
- **Menu sidebar** didaftarkan di `navigation()` milik modul, bukan di
  `app-sidebar.tsx`.
- **Seeder** didaftarkan di `seeders()` milik modul, bukan di `DatabaseSeeder`.
- **Format kode** dipaksa oleh Pint (PHP) dan `vp check` (TS/CSS); line ending
  selalu LF (`.gitattributes`, `.editorconfig`). Jangan mematikan formatter.
- **File hasil generate** (`resources/js/actions`, `resources/js/routes`,
  `resources/js/wayfinder`, `public/build`) tidak di-commit.
- **Paket baru** (composer/npm) masuk lewat PR tersendiri bila memungkinkan,
  supaya perubahan lockfile tidak bertumpuk dengan pekerjaan fitur.

## 6. Kalau tetap konflik

### Konflik di kode

```bash
git fetch origin
git rebase origin/main
# perbaiki file yang konflik, lalu:
git add <file>
git rebase --continue
git push --force-with-lease
```

Gunakan `--force-with-lease` (bukan `--force`) agar tidak menimpa push orang lain.

### Konflik lockfile

Jangan pernah mengedit `composer.lock` atau `package-lock.json` dengan tangan.
Ambil versi `main`, lalu pasang ulang paket yang kamu tambahkan:

```bash
# package-lock.json (selesaikan dulu konflik di package.json bila ada)
git checkout origin/main -- package-lock.json
npm install                        # menyusun ulang lock dari package.json gabungan
git add package-lock.json

# composer.lock (selesaikan dulu konflik di composer.json bila ada)
git checkout origin/main -- composer.lock
composer update vendor/paket-yang-kamu-tambahkan
git add composer.lock
```

CI menjalankan `npm ci`, jadi lockfile yang tidak sinkron akan langsung gagal.

## 7. Menjalankan proyek

```bash
composer setup     # install, .env, key, migrate, npm install, build
composer dev       # server + queue + log + vite
php artisan migrate:fresh --seed   # isi ulang data dummy dari semua modul
```

Akun dummy setelah seeding: `test@example.com` / `password`.
