# Suplemen Tambahan SRS & SDS: Aplikasi RuteSales

Dokumen ini berfungsi sebagai adendum (tambahan resmi) untuk melengkapi spesifikasi pada dokumen **SRS** (Software Requirements Specification) dan **SDS** (Software Design Specification) yang sudah ada.

---

## I. Tambahan SRS (Kebutuhan Fungsional & Aturan Bisnis)

### 1. Sistem Manajemen Peran & Otorisasi (RBAC)
- **Peran Pengguna (*Roles*):**
  - **Admin:** Memiliki akses penuh untuk mengelola master data (Sales, Toko, Rute), memantau seluruh aktivitas kunjungan, dan melihat *dashboard* analitik.
  - **Sales:** Memiliki akses terbatas untuk melihat daftar rute kunjungan harian yang ditugaskan kepadanya dan mengisi formulir *check-in* kunjungan.
- **Aturan Akses Rute/Tampilan:**
  - Sales **tidak dapat** mengakses halaman pengelolaan pengguna, pembuatan toko baru, atau *dashboard* analitik Admin.
  - Sales **hanya dapat** melihat dan mengubah data kunjungan yang ditugaskan khusus pada ID mereka (*Data-level Authorization*).

### 2. Aturan Transisi Status & Imutabilitas Kunjungan (*Workflow Rules*)
- **Status Awal:** Kunjungan baru secara otomatis berstatus `Direncanakan`.
- **Perubahan Status:** Diubah melalui form *check-in* menjadi salah satu dari: `Berhasil`, `Tutup`, atau `Ditolak`.
- **Aturan Imutabilitas (Kunci Data):**
  - Setelah status berubah menjadi salah satu kategori *Selesai* (`Berhasil`/`Tutup`/`Ditolak`), formulir kunjungan **dikunci (*read-only*)** dan tidak dapat diubah kembali oleh Sales untuk menjaga integritas data pengawasan.
  - Perubahan data yang sudah dikunci hanya dapat dilakukan oleh **Admin** dengan riwayat *log* perubahan.

### 3. Detail Aturan Validasi Server-Side
Formulir *check-in* kunjungan wajib menerapkan aturan validasi berikut di tingkat server:
- **`status`:** Wajib diisi (*required*), bernilai salah satu dari: `Berhasil`, `Tutup`, `Ditolak`.
- **`nilai_pesanan`:** 
  - Wajib diisi (*required*) dan harus numerik positif ($\ge 0$) **jika** `status` = `Berhasil`.
  - Wajib bernilai `null` atau `0` **jika** `status` = `Tutup` atau `Ditolak`.
- **`catatan`:** Wajib diisi (*required*) jika status `Tutup` atau `Ditolak` (sebagai alasan kegagalan), opsional jika status `Berhasil`.
- **`waktu_kunjungan`:** Diisi secara otomatis oleh sistem (*timestamp server*) saat formulir berhasil dikirim.

---

## II. Tambahan SDS (Desain Arsitektur & Antarmuka)

### 1. Arsitektur Otorisasi & Middleware (Laravel)
- **Authentication Middleware (`auth`):** Memastikan seluruh URI rute aplikasi (selain rute `login`) hanya dapat diakses oleh pengguna yang sudah terautentikasi.
- **Role-Based Middleware (`role:admin` / `role:sales`):**
  - Custom Middleware `EnsureUserHasRole` dibuat untuk memfilter rute HTTP berdasarkan kolom `role` pada tabel `users`.
  - *Skenario Otorisasi:*
    - GET `/admin/*` $\rightarrow$ Dijaga oleh middleware `auth` & `role:admin`.
    - GET/POST `/sales/*` $\rightarrow$ Dijaga oleh middleware `auth` & `role:sales`.
- **Laravel Policy / Gate:**
  - Digunakan untuk memeriksa apakah data `visit` yang diakses via URI dinamis (misal: `/rute/detail/{id}`) benar-benar milik `user_id` Sales yang sedang *login*.

### 2. Desain Skema Database (Pembaruan Tabel `users`)
Menambahkan atribut penentu peran pada tabel `users`:
- **Modifikasi Tabel `users`:**
  - `role`: `ENUM('admin', 'sales')`, default: `'sales'`.
  - `is_active`: `BOOLEAN`, default: `true` (memungkinkan admin menonaktifkan akun sales).

### 3. Pedoman Desain UI/UX Mobile-First (Khusus Antarmuka Sales)
Mengingat pengguna Sales beroperasi di lapangan menggunakan perangkat genggam (*smartphone*):
- **Desain Tombol & Input (*Touch Targets*):** Elemen interaktif (tombol *Check-in*, pilihan *dropdown*, tombol *Submit*) memiliki tinggi minimal `48px` untuk memudahkan ketukan jari.
- **Navigasi Simpel:** Menggunakan tata letak *bottom navigation bar* atau kartu (*cards*) yang ringkas untuk menampilkan daftar toko yang harus dikunjungi hari ini.
- **Umpan Balik Visual (*Flash Message*):** Menampilkan notifikasi sukses/gagal yang jelas di bagian atas layar setelah pengiriman formulir *check-in*.