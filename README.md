# NAV-X Robot Dashboard

---
## Link: https://navigasi.belantaraya.xyz
---

<div align="center">

![Version](https://img.shields.io/badge/version-4.3.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Status](https://img.shields.io/badge/status-production%20ready-success)

**Robot Navigation Monitoring System with Custom RBAC, Pest Detection & Demografi**

[Features](#features) - [Screenshots](#screenshots--dokumentasi-visual) - [Installation](#installation) - [Documentation](#documentation) - [Tech Stack](#tech-stack)

</div>

---

## Overview

NAV-X Robot Dashboard adalah sistem monitoring dan kontrol robot navigasi berbasis web dengan arsitektur **Single Page Application (SPA)**, **Role-Based Access Control (RBAC) dengan custom roles**, **Pest Detection History** (foto + koordinat XYZ + LU/LS), dan **Demografi Map** untuk plot posisi robot. Project sekarang sudah pakai struktur folder rapih (`public/`, `src/`, `assets/`) dengan `.htaccess` untuk URL bersih dan proteksi file sensitif.

### Highlights v4.3.0 (2026-05-21)

- **Pest Detection** - Tabel riwayat hama otomatis terisi saat tombol Semprot ditekan, dengan foto Wikimedia Commons (sesuai spesies), koordinat XYZ minimap, dan LU/LS GPS
- **Demografi Tab** - Canvas map yang plot posisi robot berdasarkan LU/LS, dengan filter per event (start/stop/respawn/dst)
- **Folder Structure** - `public/`, `src/`, `assets/` rapi dengan `.htaccess` rewrite + deny-all
- **Login Redesign** - Video background full-screen, glassmorphism card, no animasi distracting
- **Filter & Delete Lengkap** - Search, filter, delete per-row, batch delete untuk pest & sesi
- **4 Permission Baru** - `view_pest_detection`, `view_demografi`, `delete_pest_detection`, `delete_session`

### Highlights Versi Sebelumnya

- v4.2.0 - Custom Roles Management (admin bisa buat role unlimited)
- v4.1.0 - Admin Panel + RBAC Permission Matrix
- v4.0.0 - Obstacle Detection & Collision Avoidance

---

## Features

### Robot Monitoring (11 Sensor)

| Feature | Description |
|---------|-------------|
| Battery Monitoring | Real-time battery level & voltage |
| Live Camera Feed | Multi-camera FPV |
| GPS Tracking | Interactive map dengan path tracking |
| Liquid Level | Tank level (ml & %) |
| Motion Detection | Moving/Idle status |
| Position Tracking | 3D coordinates (X, Y, Z) |
| Speed Monitor | Current & average |
| Operation Mode | Manual/Auto GPS mode |
| Internet Connection | Status, ping, quality |
| Activity Logbook | Real-time logs |
| Data Visualization | Charts, graphs, statistics |

### Pest Detection (NEW v4.3.0)

- 10 jenis hama tersedia di katalog (Wereng Coklat, Ulat Grayak, Belalang, dll)
- Foto stabil dari Wikimedia Commons (sesuai spesies, license CC BY-SA / Public Domain)
- Otomatis simpan saat tombol Semprot ditekan
- Data: nama hama, jenis, severity, foto URL, koordinat XYZ minimap, LU/LS GPS
- Filter UI: search nama, severity, hari/bulan/tahun
- Delete: per-row + batch (mengikuti filter aktif)
- Foreign key `ON DELETE CASCADE` ke `daily_logs(id)`

### Demografi Map (NEW v4.3.0)

- Canvas map plot titik robot berdasarkan LU/LS
- Auto-bound peta otomatis fit data
- Garis penghubung urut waktu (dashed)
- Color-coded marker per event_type (start/stop/respawn/pause/manual)
- Filter event di header
- Tabel daftar posisi disamping map

### RBAC System

| Role | Type | Description |
|------|------|-------------|
| **Super Admin** | System (locked) | Full access by design, tidak bisa di-rename / hapus |
| **Operator** | Built-in | Robot control + pest delete + session delete |
| **Viewer** | Built-in | Read-only (termasuk pest & demografi) |
| **Technician** | Built-in | Maintenance & logs |
| **Custom Roles** | Custom | Unlimited, atur sendiri kotak akses lewat matrix |

**Permission count: 19** (15 lama + 4 baru di v4.3.0). Lihat [CARA_PAKAI.md](CARA_PAKAI.md) untuk detail mapping.

**Security:**
- Password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- Session management (30 min timeout)
- Audit trail logging
- Permission-based UI **dan** API enforcement (HTTP 403 untuk request tanpa hak)
- `.htaccess` proteksi: `src/` deny-all, `db.sql` & docs blocked, URL rewrite ke `public/`

### Obstacle Detection

- AABB collision detection (auto-stop saat menabrak)
- Visual: kotak merah dengan stripes diagonal
- Proximity warning ring (kuning <80px, merah <50px)
- Drag-and-drop obstacle editor di minimap
- **Mulai v4.3.0: map start kosong**, user tambah manual

---

## Screenshots & Dokumentasi Visual

> Letakkan semua screenshot di folder `docs/screenshots/` lalu commit ke repository GitHub.
> Markdown image syntax: `![alt text](docs/screenshots/nama-file.png)`
> Rekomendasi resolusi: 1920x1080 (full page) atau 1280x720 (komponen). Format: PNG (UI) / JPG (foto/video frame). Maks ~500KB per file biar README ringan saat di-clone.

```
docs/
└── screenshots/
    ├── 01-login.png
    ├── 02-dashboard-monitoring.png
    ├── 03-sensor-indikator.png
    ├── ...
```

### 1. Halaman Login

<!-- ini bagian screenshot halaman login (video background + glassmorphism card) -->
<!-- Ambil dari: http://localhost/robot_dashboard/robot-navigasi/public/login.php -->
<!-- Tunjukin: video background full-screen, card login transparan, logo NAV-X -->

<img width="1919" height="1079" alt="image" src="https://github.com/user-attachments/assets/13ab0fd4-d7f3-422f-aaa3-568c21f7e6a8" />

---

### 2. Dashboard Monitoring (Tab Utama)

<!-- ini bagian screenshot dashboard utama tab Monitoring -->
<!-- Tunjukin: minimap robot + kamera FPV + indikator battery di header -->
<!-- Login sebagai admin/admin123 dulu biar semua widget muncul -->

<img width="1919" height="1079" alt="image" src="https://github.com/user-attachments/assets/e112a994-0a5c-4013-b44a-966231543bed" />

---

### 3. Indikator Sensor (11 Sensor Realtime)

<!-- ini bagian screenshot indikator sensor -->
<!-- Tab "Sensors" - tunjukin grid 11 sensor: battery, GPS, liquid level, motion, position XYZ, speed, mode, internet, dll -->
<!-- Bisa dipecah jadi beberapa screenshot kalau panjang (3a, 3b, 3c) -->

<img width="1919" height="158" alt="image" src="https://github.com/user-attachments/assets/1746e705-11ae-4b8a-9b3c-f4ba794cd7bd" />

#### 3a. Detail Battery & Voltage
<!-- Close-up widget battery saja -->

<img width="481" height="86" alt="image" src="https://github.com/user-attachments/assets/668f0e99-5253-4712-94e8-711840f3bcff" />

#### 3b. GPS Map Tracking
<!-- Close-up peta GPS dengan path tracking -->

<img width="963" height="919" alt="image" src="https://github.com/user-attachments/assets/a18e9254-5e7b-400e-9e67-50130708231c" />

#### 3c. Liquid Level & Motion
<!-- Close-up tank level + status moving/idle -->

<img width="483" height="179" alt="image" src="https://github.com/user-attachments/assets/7a1eed16-39af-4774-82bd-b0c0604b8ec4" />

---

### 4. Minimap & Obstacle Detection

<!-- ini bagian screenshot minimap dengan obstacle -->
<!-- Tunjukin: kotak merah obstacle + warning ring kuning/merah saat robot dekat -->
<!-- Drag-drop obstacle editor (kalau bisa, screenshot saat obstacle di-drag) -->

<img width="1919" height="1079" alt="image" src="https://github.com/user-attachments/assets/96f4fc9e-b0ac-4250-9619-20f758c78003" />

---

### 5. Pest Detection (Riwayat Hama)

<!-- ini bagian screenshot tabel deteksi hama -->
<!-- Tab "Riwayat" -> sub-tab Pest Detection -->
<!-- Tunjukin: tabel dengan foto hama Wikimedia, koordinat XYZ, LU/LS, severity badge -->

<img width="1919" height="452" alt="image" src="https://github.com/user-attachments/assets/69d76e0d-ecf3-4c47-b5ba-8066df904b00" />

#### 5a. Filter & Search Pest
<!-- Tunjukin filter aktif: search by nama, severity dropdown, date range -->

<img width="1919" height="187" alt="image" src="https://github.com/user-attachments/assets/c2a2fb3b-61f2-40b8-9e37-b284c31e485c" />

#### 5b. Tombol Semprot (Trigger Auto-Save)
<!-- Tunjukin tombol Semprot di tab Monitoring + toast confirmation setelah klik -->

<img width="961" height="464" alt="image" src="https://github.com/user-attachments/assets/7cff118e-4a39-4eab-844d-f188b14d3a73" />
<img width="1919" height="1079" alt="image" src="https://github.com/user-attachments/assets/5ea88357-d0d9-4625-aa39-c997401e086e" />

---

### 6. Demografi Map (Canvas Plotting)

<!-- ini bagian screenshot demografi map -->
<!-- Tab "Demografi" - canvas map dengan titik-titik LU/LS, dashed line urut waktu -->
<!-- Color-coded marker per event_type -->

<img width="1918" height="1079" alt="image" src="https://github.com/user-attachments/assets/a9c77d27-ccf3-4a5f-b265-40e05f9e402e" />

#### 6a. Filter Per Event Type
<!-- Dropdown filter: start / stop / respawn / pause / manual -->

<img width="1489" height="1000" alt="image" src="https://github.com/user-attachments/assets/10677256-dc4c-49a2-959b-71852c9e828f" />

---

### 7. Logbook & Activity Logs

<!-- ini bagian screenshot logbook -->
<!-- Tab "Logbook" - tabel realtime log aktivitas robot -->

<img width="1919" height="1079" alt="image" src="https://github.com/user-attachments/assets/22c0f4ee-0106-49cf-b57e-6eb12b1634f9" />

---

### 8. Laporan & Export

<!-- ini bagian screenshot laporan -->
<!-- Tab "Laporan" - chart + tombol export PDF/CSV -->
<!-- Bisa screenshot file PDF hasilnya juga sebagai bukti -->

<img width="1919" height="1079" alt="image" src="https://github.com/user-attachments/assets/3b1a301e-cd70-4377-8634-191437075d14" />

---

### 9. Admin Panel - User Management

<!-- ini bagian screenshot admin user management -->
<!-- Tab "Admin" -> sub-tab Users -->
<!-- Tunjukin: list user, role badge, tombol edit/delete -->

<img width="1919" height="1079" alt="image" src="https://github.com/user-attachments/assets/1f064ca6-dba5-4645-a893-e0d9200cae3a" />

---

### 10. Admin Panel - Roles & Permission Matrix

<!-- ini bagian screenshot RBAC permission matrix -->
<!-- Tab "Admin" -> sub-tab Roles atau Permissions -->
<!-- Tunjukin: tabel checkbox role x permission (19 permission) -->
<!-- Highlight: row "Super Admin" yang locked + custom role baru -->

<img width="1919" height="1079" alt="image" src="https://github.com/user-attachments/assets/1eee2436-dd3e-41dd-9a61-7a0828f25983" />


#### 10a. Create Custom Role
<!-- Modal/form bikin role baru -->

<img width="1919" height="519" alt="image" src="https://github.com/user-attachments/assets/b4bc739d-da8c-490d-bc41-c5f361007104" />


---

### 11. Audit Log Trail

<!-- ini bagian screenshot audit log -->
<!-- Tab "Admin" -> sub-tab Audit Logs -->
<!-- Tunjukin: history login, perubahan role, delete action, dengan timestamp + IP -->

<img width="1919" height="652" alt="image" src="https://github.com/user-attachments/assets/7e86f957-86ff-485e-8674-b13d28fd382e" />

---

## Project Structure

```
robot-navigasi/
├── .htaccess               ← rewrite ke public/, deny file sensitif
├── README.md               ← file ini
├── CARA_PAKAI.md           ← user guide (Bahasa Indonesia)
├── SUMMARY.md              ← project summary
├── db.sql                  ← schema lengkap
├── LICENSE
│
├── public/                 ← entry point web
│   ├── .htaccess
│   ├── index.php           (SPA, 7 tabs)
│   ├── login.php           (video background)
│   ├── logout.php
│   ├── api.php             (sesi: POST/DELETE)
│   ├── pest_api.php        (deteksi hama)
│   ├── position_api.php    (posisi robot)
│   ├── admin_api.php       (user/role/permission)
│   └── assets/
│       ├── img/
│       └── video/
│
└── src/                    ← protected (deny-all)
    ├── .htaccess
    ├── Config/db.php
    └── Auth/auth.php
```

---

## Tech Stack

**Backend:** PHP 7.4+, MySQL/MariaDB (mysqli), Firebase Realtime Database
**Frontend:** Tailwind CSS, Font Awesome, SweetAlert2, html2pdf.js, Canvas API
**Architecture:** SPA + RBAC + REST API
**Server:** Apache (mod_rewrite + AllowOverride All)

---

## Installation

### Prerequisites

- XAMPP (Apache + MySQL) atau setara
- PHP 7.4+
- MySQL 8.0+ / MariaDB 10.4+
- Modern browser

### Quick Start

1. **Clone repository**
   ```bash
   git clone https://github.com/yourusername/robot-navigasi.git
   cd robot-navigasi
   ```

2. **Setup database**
   ```bash
   mysql -u root -e "CREATE DATABASE robot_dashboard"
   mysql -u root robot_dashboard < db.sql
   ```

3. **Configure DB** (edit `src/Config/db.php`):
   ```php
   $host = 'localhost';
   $user = 'root';
   $pass = '';
   $db   = 'robot_dashboard';
   ```

4. **Verifikasi Apache config** (`C:\xampp\apache\conf\httpd.conf`):
   - `LoadModule rewrite_module modules/mod_rewrite.so` (uncomment)
   - `<Directory "C:/xampp/htdocs">` -> `AllowOverride All`

5. **Restart Apache** dari XAMPP

6. **Akses aplikasi**
   ```
   http://localhost/robot_dashboard/robot-navigasi/
   ```
   Auto-redirect ke login (kalau belum) atau dashboard.

7. **Login dengan demo account**
   ```
   admin / admin123     (Super Admin)
   operator / operator123
   viewer / viewer123
   technician / tech123
   ```

---

## Demo Accounts

| Username | Password | Role | Type |
|----------|----------|------|------|
| `admin` | `admin123` | Super Admin | System |
| `operator` | `operator123` | Operator | Built-in |
| `viewer` | `viewer123` | Viewer | Built-in |
| `technician` | `tech123` | Technician | Built-in |

> **PENTING**: Ganti password default sebelum deploy production.
> Built-in roles bisa di-rename atau dihapus via tab Admin. Hanya Super Admin yang locked.

---

## Documentation

- [CARA_PAKAI.md](CARA_PAKAI.md) - User guide lengkap (Bahasa Indonesia)
- [SUMMARY.md](SUMMARY.md) - Project summary & progress tracking
- [db.sql](db.sql) - Schema lengkap (semua tabel + permission)

### API Endpoints (di folder `public/`)

| File | Method | Description |
|------|--------|-------------|
| `api.php` | POST | Insert/update sesi |
| `api.php` | DELETE `?id=X` atau `?all=1` | Hapus sesi (cascade ke pest + position) |
| `pest_api.php` | POST | Simpan deteksi hama |
| `pest_api.php` | GET `?all=1` / `?session_id=X` | List deteksi |
| `pest_api.php` | DELETE `?id=X` | Hapus deteksi |
| `position_api.php` | POST | Simpan posisi robot |
| `position_api.php` | GET `?all=1` / `?event=stop` | List posisi |
| `admin_api.php` | GET / POST | User/role/permission management |

Semua endpoint enforce permission via `hasPermission()`. Request tanpa hak dapat HTTP 403.

---

## Configuration

### Database

Edit `src/Config/db.php`:
```php
$host = 'localhost';
$user = 'root';
$pass = 'your_password';
$db   = 'robot_dashboard';
```

### Login Background Video

Taruh video MP4 baru di `public/assets/video/`, lalu edit `public/login.php`:
```html
<video class="bg-video" autoplay muted loop playsinline>
    <source src="assets/video/nama-video-baru.mp4" type="video/mp4">
</video>
```

Tips: convert ke MP4 H.264 ~1-2 Mbps, resolusi 1280x720 cukup untuk background.

### Firebase

Update config di `public/index.php`:
```javascript
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_PROJECT.firebaseapp.com",
    databaseURL: "https://YOUR_PROJECT.firebaseio.com",
    // ...
};
```

---

## Security Best Practices

### Sebelum Production

1. **Ganti default passwords**
   ```sql
   UPDATE users SET password = '$2y$10$...' WHERE username = 'admin';
   ```

2. **Buat user real**, disable demo accounts

3. **Enable HTTPS** (SSL certificate + force redirect)

4. **Backup database** regular

5. **Review permissions** untuk tiap role custom

6. **Cek `.htaccess` aktif:**
   - `http://.../db.sql` -> 403
   - `http://.../src/Config/db.php` -> 403
   - `http://.../` -> redirect ke `public/index.php`

---

## Troubleshooting

### Directory listing muncul saat akses root
- Pastikan `mod_rewrite` aktif & `AllowOverride All` di httpd.conf
- Restart Apache

### Login video hitam pekat
- Cek file MP4 ada di `public/assets/video/`
- Browser block autoplay -> klik di mana saja di halaman

### Foto hama tidak muncul
- URL Wikimedia perlu width whitelist (`/330px-` atau `/250px-`)

### Permission baru tidak muncul di matrix
- Re-import `db.sql` yang sudah include permission 16-19
- Logout & login ulang

### Cannot delete custom role
- Reassign user yang masih pakai role itu ke role lain dulu

---

## Roadmap

### v4.3.0 (Current) ✓
- Pest Detection History
- Demografi Map
- Folder structure refactor
- Login redesign
- Filter + delete lengkap

### v4.4.0 (Planned)
- Password reset functionality
- User profile page
- Pest detection: real ML/vision integration
- Demografi: heatmap mode
- Export pest report ke PDF/CSV

### v5.0.0 (Future)
- Two-factor authentication (2FA)
- API authentication (JWT)
- Mobile app
- Multi-language support

---

## Statistics

- **Tabs:** 7 (Monitoring, Sensors, Logbook, Riwayat, Demografi, Laporan, Admin)
- **Roles:** Unlimited (1 system + 3 built-in + N custom)
- **Permissions:** 19
- **Database tables:** 9 (`daily_logs`, `activity_logs`, `users`, `roles`, `permissions`, `role_permissions`, `audit_logs`, `pest_detections`, `robot_positions`)
- **Endpoints:** 4 API files (`api`, `pest_api`, `position_api`, `admin_api`)
- **Architecture:** SPA + RBAC + REST API + Folder structure (public/src)
- **Version:** 4.3.0

---

## License

MIT License. See [LICENSE](LICENSE).

---

## Author

**Rizki Triamadewa**

---

<div align="center">

Made with care, kept simple.

</div>
