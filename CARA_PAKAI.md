# Cara Pakai - NAV-X Robot Dashboard (v4.3.0)

> SPA + Custom RBAC + Obstacle Detection + Pest Detection + Demografi + Folder Structure

## Ringkasan Cepat

- Arsitektur: Single Page Application (SPA) dengan 7 tab
- Security: Role-Based Access Control (RBAC) - jumlah role tak terbatas
- Highlight v4.3.0:
  - Pest Detection History (foto via Wikimedia, koordinat XYZ + LU/LS)
  - Demografi tab (plot LU/LS posisi robot)
  - Folder structure: public/, src/, assets/
  - Login page redesign (video background)
  - .htaccess rewrite + deny-all untuk file sensitif
  - Filter + search + delete (per-row & batch) di Riwayat
- Highlight v4.2.0: Custom Roles Management
- Highlight v4.0.0: Obstacle Detection & Collision Avoidance

---

## Struktur Folder

```
robot-navigasi/
├── .htaccess                ← rewrite ke public/, deny file sensitif
├── .gitignore, .gitattributes
├── README.md, CARA_PAKAI.md, SUMMARY.md, LICENSE
├── db.sql                   ← schema lengkap (semua tabel + permission)
│
├── public/                  ← entry point dari browser
│   ├── .htaccess
│   ├── index.php            (dashboard SPA)
│   ├── login.php            (login + video background)
│   ├── logout.php
│   ├── api.php              (sesi: POST/DELETE)
│   ├── pest_api.php         (deteksi hama: GET/POST/DELETE)
│   ├── position_api.php     (posisi robot: GET/POST/DELETE)
│   ├── admin_api.php        (user/role/permission management)
│   └── assets/
│       ├── img/
│       └── video/           (background login.php)
│
└── src/                     ← protected (deny all .htaccess)
    ├── .htaccess
    ├── Config/db.php        (kredensial DB)
    └── Auth/auth.php        (auth functions)
```

> File di `src/` tidak bisa diakses lewat URL berkat `.htaccess` deny-all. Hanya di-include via `require __DIR__ . '/../src/...'` dari `public/*.php`.

---

## Cara Setup

### Step 1: Setup Database

1. Buka phpMyAdmin: `http://localhost/phpmyadmin`
2. Buat database baru: `robot_dashboard`
3. Import file `db.sql` (semua tabel, role default, permission, dan akun demo akan ter-create otomatis)

### Step 2: Konfigurasi Database

Edit `src/Config/db.php` jika kredensial MySQL kamu berbeda dari default XAMPP:

```php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "robot_dashboard";
```

### Step 3: Pastikan mod_rewrite aktif (XAMPP default sudah ON)

Cek `C:\xampp\apache\conf\httpd.conf`:
- `LoadModule rewrite_module modules/mod_rewrite.so` (uncomment)
- `<Directory "C:/xampp/htdocs">` -> `AllowOverride All`

### Step 4: Login

Buka:
```
http://localhost/robot_dashboard/robot-navigasi/
```
URL otomatis di-rewrite ke `public/index.php`. Kalau belum login akan diarahkan ke `public/login.php`.

Akun demo:

| Username | Password | Role | Tipe |
|----------|----------|------|------|
| `admin` | `admin123` | Super Admin | Sistem (locked) |
| `operator` | `operator123` | Operator | Built-in |
| `viewer` | `viewer123` | Viewer | Built-in |
| `technician` | `tech123` | Technician | Built-in |

---

## Navigasi Dashboard (7 Tab)

### Tab 1: Monitoring (Dashboard Utama)

**Fitur:**
- Battery monitoring real-time
- Live FPV camera (multi-camera support)
- GPS tracking & interactive map
- Jarak tempuh real-time
- Air keluar monitoring
- Sisa tangki
- Path tracking dengan canvas
- Control buttons (Up, Down, Left, Right)
- Spray water button (auto-detect hama random + kirim ke Riwayat)
- Save & Reset session
- Mode selection (Manual/Auto)
- Obstacle Detection & Collision Avoidance (start kosong, user tambah manual)

**Cara Pakai:**
1. Gunakan control buttons untuk menggerakkan robot
2. Klik **Semprot** -> sistem random pilih jenis hama, simpan koordinat XYZ + LU/LS, kirim ke `pest_api.php`
3. Klik **Simpan** untuk save session
4. Klik **Sesi Baru** -> kirim event `stop` + `respawn` ke `position_api.php` (untuk Demografi)
5. Pilih mode: Manual atau Auto (GPS)
6. Robot **berhenti otomatis** jika menabrak obstacle

### Tab 2: Sensors (Sensor Monitor)

6 sensor cards (level cairan, motion, posisi XYZ, speed, mode, koneksi). Update otomatis dari Firebase.

### Tab 3: Logbook (Activity Logs)

Real-time activity logs dengan 4 tipe (info/success/warning/error), filter, export CSV, clear logs (admin only).

### Tab 4: Riwayat (History) - DIPERBARUI v4.3.0

Tab Riwayat sekarang punya 2 panel:

**Panel 1: Riwayat Sesi**
- Tabel: Waktu, Baterai, Jarak, Air Keluar, Sisa Air, Aksi
- Filter by hari/bulan/tahun
- Tombol Hapus per-row (butuh permission `delete_session`)
- Cascade delete: hapus sesi -> hapus juga deteksi hama & posisi terkait

**Panel 2: Riwayat Deteksi Hama** (NEW v4.3.0)
- Otomatis terisi tiap kali tombol Semprot ditekan
- Kolom: Foto (klik untuk preview), Waktu, Hama, Jenis, Severity, Koordinat XYZ, LU/LS, Aksi
- Filter:
  - Search by nama hama
  - Severity (high/medium/low)
  - Hari/Bulan/Tahun (auto-detect dari data)
- Tombol Hapus per-row + tombol "Hapus Semua" (yang sedang ter-filter)
- Foto diambil dari Wikimedia Commons (URL stabil, sesuai spesies):
  - Wereng Coklat, Ulat Grayak, Belalang, Tungau Laba-laba,
  - Kutu Daun, Penggerek Batang Padi, Walang Sangit,
  - Jamur Karat, Bercak Daun, Siput Telanjang

### Tab 5: Demografi (NEW v4.3.0)

> Butuh permission `view_demografi`.

**Fitur:**
- Canvas peta yang plot titik posisi robot berdasarkan **LU (latitude) / LS (longitude)**
- Auto-bound: peta otomatis fit ke range koordinat data
- Garis penghubung antar titik (urut waktu)
- Filter event: All / Start / Stop / Respawn / Pause / Manual
- Color-coded marker:
  - Start (hijau), Stop (merah), Respawn (oranye), Pause (biru), Manual (abu)
- Tabel daftar posisi di samping (Waktu, Event, XYZ, LU/LS)
- Refresh button untuk reload data

**Sumber data:** robot otomatis kirim posisi ke `position_api.php` saat:
- Tekan tombol "Sesi Baru" -> event `stop` + `respawn`
- (Bisa di-extend untuk event start/pause via JS hook)

### Tab 6: Laporan (PDF Report)

Summary data sesi + download PDF dengan map screenshot.

### Tab 7: Admin (Admin Management)

> Hanya Super Admin yang bisa membuka tab ini.

User Management, Role Management, RBAC Permission Matrix. Detail di bagian RBAC bawah.

---

## Daftar Permission (19, dari 15 sebelumnya)

| ID | Nama Permission | Modul | Aksi | Catatan |
|----|----------------|-------|------|---------|
| 1 | `view_dashboard` | monitoring | view | |
| 2 | `control_robot` | monitoring | control | |
| 3 | `save_session` | monitoring | create | |
| 4 | `reset_session` | monitoring | delete | |
| 5 | `spray_water` | monitoring | control | |
| 6 | `view_sensors` | sensors | view | |
| 7 | `view_logbook` | logbook | view | |
| 8 | `export_logs` | logbook | export | |
| 9 | `clear_logs` | logbook | delete | |
| 10 | `add_maintenance_log` | logbook | create | |
| 11 | `view_history` | history | view | |
| 12 | `delete_history` | history | delete | |
| 13 | `generate_pdf` | reports | create | |
| 14 | `manage_users` | admin | manage | |
| 15 | `view_audit_logs` | admin | view | |
| 16 | `view_pest_detection` | history | view | NEW v4.3.0 |
| 17 | `view_demografi` | demografi | view | NEW v4.3.0 |
| 18 | `delete_pest_detection` | history | delete | NEW v4.3.0 |
| 19 | `delete_session` | history | delete | NEW v4.3.0 |

**Default mapping role baru (v4.3.0):**

| Permission | Super Admin | Operator | Viewer | Technician |
|-----------|-------------|----------|--------|------------|
| view_pest_detection | ✓ | ✓ | ✓ | ✓ |
| view_demografi | ✓ | ✓ | ✓ | ✓ |
| delete_pest_detection | ✓ | ✓ | - | - |
| delete_session | ✓ | ✓ | - | - |

---

## Pest Detection - Cara Kerja

1. User tekan tombol **Semprot** di tab Monitoring
2. Frontend (JS):
   - Random pilih hama dari katalog (10 spesies)
   - Ambil koordinat XYZ dari posisi robot di minimap
   - Ambil LU/LS dari GPS (kalau aktif) atau fallback ke titik dummy
3. POST ke `public/pest_api.php`:
   ```json
   {
     "session_id": 123,
     "pest_name": "Wereng Coklat",
     "pest_type": "Serangga (Hemiptera)",
     "severity": "high",
     "image_url": "https://upload.wikimedia.org/.../330px-Nilaparvata_lugens.jpg",
     "map_x": 412.5, "map_y": 198.3, "map_z": 0,
     "latitude": -6.20012, "longitude": 106.81684
   }
   ```
4. Server `INSERT INTO pest_detections` dengan FK ke `daily_logs(id)`
5. UI auto-prepend baris baru ke tabel Riwayat Hama tanpa reload

**Foto online (Wikimedia Commons):** URL pakai pola thumbnail `/330px-...` (whitelist resmi). Aman, ringan, tidak menyentuh DB.

---

## Robot Position - Cara Kerja (Demografi)

Setiap event signifikan, robot kirim posisinya ke `position_api.php`:

| Trigger | event_type | Catatan |
|---------|-----------|---------|
| User tekan "Sesi Baru" | `stop` -> `respawn` | Dua record berurutan |
| (Future) Robot start | `start` | Bisa di-hook saat first move |
| (Future) Robot pause | `pause` | |
| Manual logging | `manual` | |

Tab Demografi GET `position_api.php?all=1` -> render canvas.

---

## Obstacle Detection System

Mulai v4.3.0, **map start kosong** (tidak ada obstacle default lagi). User tambah manual:

1. Klik tombol **Add Obstacle** di header minimap
2. Drag-and-drop di minimap untuk membuat kotak obstacle
3. Robot otomatis stop saat menabrak (algoritma AABB)
4. Proximity warning (ring kuning <80px, ring merah <50px)
5. Tombol **Clear** menghapus semua obstacle dengan konfirmasi

---

## RBAC System

### Role Bawaan + Custom (Unlimited)

#### 1. Super Admin (`admin` / `admin123`) - Role Sistem
- Top tier, full access by design
- Permission selalu full, tidak bisa di-uncheck
- Nama "Super Admin" tidak bisa di-rename
- Role tidak bisa dihapus

#### 2. Operator (`operator` / `operator123`)
- Robot control & monitoring + spray water + save session
- Punya akses delete pest detection & delete session

#### 3. Viewer (`viewer` / `viewer123`)
- Read-only (dashboard, sensors, logbook, history, **demografi**, **pest detection**)
- Bisa generate PDF
- Tidak bisa delete

#### 4. Technician (`technician` / `tech123`)
- View & export logs, tambah maintenance log
- Akses pest detection + demografi (read-only)

#### 5. Role Custom (mis. Mahasiswa, QA Tester)
- Buat lewat tab Admin -> Role Management
- Bebas tentukan kotak akses (page/aksi) di RBAC Permission Matrix

---

## Login Page (NEW v4.3.0)

Login page diredesign:
- Background video full-screen (`public/assets/video/14492092_1920_1080_30fps.mp4`)
- Glassmorphism card (opacity rendah, backdrop blur)
- Tema gelap (slate + sky/indigo accent)
- No animasi naik-turun (sesuai request)
- Toggle show/hide password (eye icon)
- Filter brightness video tetap ringan supaya video terlihat jelas

Untuk ganti video:
1. Taruh file MP4 baru di `public/assets/video/`
2. Edit `public/login.php` -> ubah `<source src="assets/video/...">`

---

## Security Features

- Password hashing (bcrypt via `password_hash()`)
- SQL injection prevention (prepared statements)
- Session management (30 menit timeout)
- Auto-logout on timeout
- Audit trail logging (`audit_logs`)
- **Folder structure protection**: `src/` deny-all, `db.sql` & docs `Require all denied` via root `.htaccess`
- Permission-based UI rendering (PHP guard + JS PERMS object)
- Permission-based API enforcement (HTTP 403 untuk request tanpa hak)
- Cascade delete via FK `ON DELETE CASCADE` (hapus sesi -> hapus pest + position)

---

## Technology Stack

**Backend**
- PHP 7.4+
- MySQL/MariaDB (mysqli, prepared statements)
- Firebase Realtime Database

**Frontend**
- Tailwind CSS (CDN)
- Font Awesome 6.4.0
- SweetAlert2
- html2pdf.js
- Canvas API (minimap, demografi map, obstacles)

---

## Troubleshooting

**Tidak bisa login**
1. Pastikan `db.sql` sudah di-import
2. Cek tabel users: `SELECT * FROM users;`
3. Hard refresh browser (Ctrl+Shift+R)

**URL `http://localhost/robot_dashboard/robot-navigasi/` malah tampil directory listing**
1. Cek `mod_rewrite` aktif di Apache config
2. Cek `AllowOverride All` di `<Directory "C:/xampp/htdocs">`
3. Restart Apache dari XAMPP control panel

**Video login.php hitam pekat**
1. Cek file `public/assets/video/...mp4` ada
2. Buka URL video langsung untuk konfirmasi load
3. Browser block autoplay -> klik di mana saja di halaman

**Foto hama "use thumbnail sizes listed"**
- URL Wikimedia harus pakai width whitelist (mis. `/330px-` atau `/250px-`). Width 320/400 ditolak.

**Permission baru tidak muncul di matrix**
- Pastikan migrasi sudah dijalankan (atau import ulang `db.sql` yang sudah include permission ID 16-19)
- Logout & login ulang

**File `src/Config/db.php` muncul di browser saat dikunjungi langsung**
- `.htaccess` di `src/` belum dibaca. Cek `AllowOverride All` aktif & restart Apache.

---

## Penting Sebelum Production

1. Ganti password default
2. Buat user real, disable demo accounts
3. Enable HTTPS
4. Backup database regular
5. Review permission per role
6. Test obstacle system + collision detection
7. Cek seluruh `.htaccess` benar-benar aktif (mod_rewrite + AllowOverride)

---

## Quick Links

```
Dashboard: http://localhost/robot_dashboard/robot-navigasi/
Login:     http://localhost/robot_dashboard/robot-navigasi/login.php
API sesi:  http://localhost/robot_dashboard/robot-navigasi/api.php
API hama:  http://localhost/robot_dashboard/robot-navigasi/pest_api.php
API pos:   http://localhost/robot_dashboard/robot-navigasi/position_api.php
Admin API: http://localhost/robot_dashboard/robot-navigasi/admin_api.php
```

---

**Dibuat:** 2026-05-07
**Updated:** 2026-05-21 (v4.3.0: Pest Detection + Demografi + Folder Structure + Login Redesign)
**Developer:** Rizki Triamadewa
**Version:** 4.3.0
