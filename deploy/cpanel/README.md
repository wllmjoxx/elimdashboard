# Go live di hosting cPanel (DomaiNesia)

Situs dipasang dulu di subdomain **baru.ggpelim.com**. Situs lama di
ggpelim.com tetap jalan sampai situs baru siap menggantikannya.

Hosting tidak perlu Composer atau Node: paket zip sudah berisi `vendor/` dan
aset hasil build. Yang dipakai di cPanel: **Terminal**, **File Manager**,
**Manage My Databases**, **Email Accounts**, **Cron Jobs**, **Let's Encrypt**.

| File                | Fungsi                                                        |
| ------------------- | ------------------------------------------------------------- |
| `build-release.ps1` | Di laptop: membuat `Downloads\elim-release-<commit>.zip`      |
| `install.sh`        | Di hosting: pasang pertama kali dan pembaruan (aman diulang)  |
| `env.example`       | Contoh `.env` hosting (MySQL, email cPanel, queue lewat cron) |

Susunan folder di hosting:

```
/home/ggpelimc/
├── public_html/     situs lama ggpelim.com (tidak disentuh)
└── elim/            aplikasi baru
    └── public/      document root baru.ggpelim.com
```

## A. Persiapan sekali di cPanel

1. **Subdomain.** _Domains › Create A New Domain_: isi `baru.ggpelim.com`,
   hilangkan centang _Share document root_, isi document root `elim/public`.
2. **Versi PHP.** _MultiPHP Version_ (atau _Select PHP Version_): pilih PHP
   **8.3 atau 8.4** untuk `baru.ggpelim.com` saja. Jangan ubah ggpelim.com.
3. **Database.** _Manage My Databases_: buat database (misalnya
   `ggpelimc_elim`) dan user baru, lalu tambahkan user ke database dengan
   **All Privileges**. Catat nama database, user, dan password-nya.
4. **Email pengirim.** _Email Accounts › Create_: `noreply@ggpelim.com`.
   Email ini mengirim kode masuk dan pemberitahuan pendaftaran. Cek juga
   _Email Deliverability_: SPF dan DKIM untuk ggpelim.com harus _Valid_.
5. **HTTPS.** _Let's Encrypt_: terbitkan sertifikat untuk
   `baru.ggpelim.com`, lalu nyalakan _Force to HTTPS_.
6. **Nginx Cache** untuk `baru.ggpelim.com` biarkan **mati**. Kalau cache
   menyimpan halaman yang sedang login, form dan sesi bisa rusak.

## B. Buat paket di laptop

Commit dulu semua perubahan. Lalu di folder proyek:

```powershell
powershell -ExecutionPolicy Bypass -File deploy\cpanel\build-release.ps1
```

Hasilnya `Downloads\elim-release-<commit>.zip` (sekitar 12 MB).

## C. Pasang pertama kali

1. _File Manager_: buka folder home (`/home/ggpelimc`), lalu **Upload** zip
   tadi.
2. _Terminal_: ekstrak ke folder `elim` (ganti `xxxxxxx` dengan kode di nama
   file):

    ```bash
    mkdir -p ~/elim && unzip -oq ~/elim-release-xxxxxxx.zip -d ~/elim && rm ~/elim-release-xxxxxxx.zip
    ```

3. Jalankan pemasang. Run pertama hanya membuat `.env`:

    ```bash
    cd ~/elim && bash deploy/cpanel/install.sh
    ```

4. _File Manager_ (aktifkan _Settings › Show Hidden Files_): edit
   `elim/.env` dan isi:
    - `APP_URL=https://baru.ggpelim.com`
    - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` dari langkah A.3
    - `MAIL_USERNAME=noreply@ggpelim.com`, `MAIL_PASSWORD` dari langkah A.4,
      `MAIL_FROM_ADDRESS=noreply@ggpelim.com`
5. Jalankan lagi. Kali ini tabel dibuat, role dan konten awal beranda diisi,
   cache disiapkan:

    ```bash
    cd ~/elim && bash deploy/cpanel/install.sh
    ```

6. Buat akun Superadmin (password diketik di Terminal, tidak tampil):

    ```bash
    cd ~/elim && php artisan core:create-superadmin email-anda@ggpelim.com --name="Nama Anda"
    ```

    Kalau `php` di Terminal versinya di bawah 8.3, pakai perintah PHP yang
    ditampilkan `install.sh` di akhir (misalnya `/opt/alt/php83/usr/bin/php`).

7. _Cron Jobs_: tambahkan satu cron **Once Per Minute** dengan perintah yang
   ditampilkan `install.sh` di akhir, bentuknya:

    ```
    cd /home/ggpelimc/elim && php artisan schedule:run >> /dev/null 2>&1
    ```

    Cron ini mengirim email antrean (pemberitahuan pendaftaran), memperbarui
    token Instagram galeri, dan menghapus kode masuk yang kedaluwarsa. Kode
    masuk sendiri dikirim langsung tanpa menunggu cron.

Server tidak membuat akun dummy dan data contoh. Kegiatan, pengumuman,
renungan, galeri, dan kesaksian diisi tim lewat dashboard.

## D. Cek setelah pasang

- `https://baru.ggpelim.com` terbuka, ikon gembok aktif, dan tampilan rapi
  (aset termuat).
- Masuk dengan kode email: kode sampai dalam beberapa detik.
- Daftar sebagai jemaat baru, lalu setujui dari dashboard. Email "Buat
  password" sampai (paling lama 1 menit, lewat cron) dan tautannya terbuka.
- Unggah satu foto di Galeri dan satu poster Kegiatan.
- Kirim Form Doa dari halaman publik.

Kalau ada error: lihat `elim/storage/logs/laravel-*.log` di File Manager.

## E. Memperbarui situs

Setiap ada perubahan di `main`:

1. Di laptop: `git checkout main`, `git pull`, lalu jalankan
   `build-release.ps1`.
2. Upload zip ke home, lalu di Terminal:

    ```bash
    unzip -oq ~/elim-release-xxxxxxx.zip -d ~/elim && rm ~/elim-release-xxxxxxx.zip
    cd ~/elim && bash deploy/cpanel/install.sh
    ```

Selama pembaruan, pengunjung melihat halaman pemeliharaan singkat. `.env`,
foto yang sudah diunggah, dan database tidak tertimpa.

## F. Pindah ke ggpelim.com (nanti)

Setelah situs baru dites di subdomain: cadangkan `public_html` (situs lama),
arahkan ggpelim.com ke `elim/public`, ubah `APP_URL=https://ggpelim.com` di
`.env`, lalu jalankan `install.sh` lagi. Langkah rincinya dibuat saat itu.
