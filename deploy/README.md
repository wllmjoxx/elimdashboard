# Deploy GGP Elim

Skrip di folder ini menyiapkan server Ubuntu 24.04 dan memperbarui aplikasi.
Dipakai untuk **staging** di Oracle Cloud Always Free sekarang, dan nanti untuk
**production** di VPS (IDCloudHost) dengan langkah yang sama.

| File                                      | Fungsi                                                                                |
| ----------------------------------------- | ------------------------------------------------------------------------------------- |
| `provision.sh`                            | Setup sekali: paket, firewall, PHP-FPM, Nginx, HTTPS, queue, SSR, cron, build pertama |
| `deploy.sh`                               | Ambil kode terbaru, build, migrate, restart proses latar                              |
| `nginx.conf`                              | Konfigurasi situs Nginx (template)                                                    |
| `supervisor.conf`                         | Queue worker dan server SSR (template)                                                |
| `env.example`                             | Contoh `.env` untuk server                                                            |
| `../.github/workflows/deploy-staging.yml` | Deploy otomatis setelah tes di `main` lulus                                           |

## 1. Buat server di Oracle Cloud (gratis)

1. Daftar di <https://www.oracle.com/cloud/free/>. Pilih **Home Region** terdekat
   (Singapore atau Indonesia/Batam bila tersedia); region ini tidak bisa diganti.
   Verifikasi kartu hanya untuk identitas, tidak ditagih selama memakai
   resource _Always Free_.
2. **Compute › Instances › Create instance**
    - Image: **Canonical Ubuntu 24.04**
    - Shape: **VM.Standard.A1.Flex** (Ampere, _Always Free_), misalnya 2 OCPU dan 12 GB RAM
    - Networking: buat VCN baru dengan _public subnet_ dan **public IPv4**
    - SSH key: unggah public key Anda (atau unduh key yang dibuatkan Oracle)
    - Bila muncul "Out of capacity", coba lagi nanti atau pilih _availability domain_ lain.
3. **Networking › Virtual cloud networks › (VCN Anda) › Security Lists › Default**:
   tambah _Ingress Rule_ TCP port **80** dan **443** dari `0.0.0.0/0`.
4. Catat IP publik server, misalnya `152.70.1.2`.

## 2. Ambil kode ke server

```bash
ssh ubuntu@152.70.1.2
```

Repo GitHub ini privat, jadi server butuh _deploy key_ (hanya baca):

```bash
ssh-keygen -t ed25519 -C elim-server -f ~/.ssh/github -N ""
cat ~/.ssh/github.pub
```

Salin hasilnya ke GitHub › repo › **Settings › Deploy keys › Add deploy key**
(jangan centang _write access_). Lalu:

```bash
printf 'Host github.com\n  IdentityFile ~/.ssh/github\n' >> ~/.ssh/config
sudo mkdir -p /var/www/elim && sudo chown ubuntu:ubuntu /var/www/elim
git clone git@github.com:wllmjoxx/elimdashboard.git /var/www/elim
```

## 3. Setup sekali

Tanpa domain, pakai alamat gratis **sslip.io** dari IP server (titik diganti
tanda hubung). HTTPS tetap jalan.

```bash
cd /var/www/elim
sudo DOMAIN=152-70-1-2.sslip.io EMAIL=email-anda@example.org bash deploy/provision.sh
```

Selesai sekitar 5–10 menit. Situs ada di `https://152-70-1-2.sslip.io`.

## 4. Isi email dan buat akun pertama

```bash
nano /var/www/elim/.env      # isi MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS
php artisan optimize && php artisan queue:restart
php artisan core:create-superadmin email-anda@example.org --name="Nama Anda"
```

Di server, seeder **tidak** membuat akun dummy (`admin@example.com` dan
lainnya, password `password`) karena siapa pun bisa memakainya. Akun lain
dibuat lewat pendaftaran biasa lalu disetujui Superadmin/Admin.

## 5. Deploy otomatis dari GitHub (opsional)

Setelah ini, setiap push ke `main` yang lulus tes langsung ter-deploy.

1. Di laptop, buat pasangan key khusus deploy:
   `ssh-keygen -t ed25519 -C github-actions -f elim-actions -N ""`
2. Tambahkan isi `elim-actions.pub` ke `~/.ssh/authorized_keys` di server.
3. GitHub › repo › **Settings › Secrets and variables › Actions**:
    - Secret `STAGING_SSH_KEY` = isi file `elim-actions` (private key)
    - Variables `STAGING_HOST` = IP server, `STAGING_USER` = `ubuntu`,
      `STAGING_PATH` = `/var/www/elim`
4. Hapus file key dari laptop setelah tersimpan di GitHub.

Deploy manual kapan saja: `bash /var/www/elim/deploy/deploy.sh main`.

## Catatan

- Staging (`APP_ENV=staging`) tidak diindeks Google: `robots.txt` menolak
  semua halaman di luar production.
- Data staging memakai SQLite di `database/database.sqlite`. Production nanti
  memakai MySQL/MariaDB dan backup harian.
- Sertifikat HTTPS diperpanjang otomatis oleh certbot.
- Log: `storage/logs/laravel-*.log`, `storage/logs/queue.log`, `storage/logs/ssr.log`.
