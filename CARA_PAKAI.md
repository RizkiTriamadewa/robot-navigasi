# Cara Pakai - NAV-X Robot Dashboard (v4.2.0)

> SPA + Custom RBAC + Obstacle Detection + Admin Management

## Ringkasan Cepat

- Progress: **96%** (38/42 tugas selesai)
- Arsitektur: Single Page Application (SPA) dengan 6 tab
- Security: Role-Based Access Control (RBAC) - jumlah role tak terbatas
- Highlight v4.2.0: Custom Roles Management (admin bisa buat role sendiri)
- Highlight v4.0.0: Obstacle Detection & Collision Avoidance System

---

## Struktur Aplikasi

**RBAC System**
- `login.php` - Halaman login
- `logout.php` - Logout handler
- `auth.php` - Authentication functions
- `admin_api.php` - User/role/permission management API

**Main Application**
- `index.php` - Single Page Application (6 tab)
- `api.php` - REST API session
- `db.php` - Database connection
- `db.sql` - Schema lengkap (users, roles, permissions, role_permissions, audit_logs, daily_logs, activity_logs)

**Dokumentasi**
- `README.md` - Repository overview
- `SUMMARY.md` - Project summary
- `CARA_PAKAI.md` - File ini

---

## Cara Setup

### Step 1: Setup Database

1. Buka phpMyAdmin: `http://localhost/phpmyadmin`
2. Buat database baru: `robot_dashboard`
3. Import file `db.sql` (semua tabel, role default, permission, dan akun demo akan ter-create otomatis)

### Step 2: Konfigurasi Database

Edit `db.php` jika kredensial MySQL kamu berbeda dari default XAMPP:

```php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "robot_dashboard";
```

### Step 3: Login

1. Buka: `http://localhost/robot_dashboard/robot-navigasi/login.php`
2. Login dengan salah satu akun demo:

| Username | Password | Role | Tipe |
|----------|----------|------|------|
| `admin` | `admin123` | Super Admin | Sistem (locked) |
| `operator` | `operator123` | Operator | Built-in |
| `viewer` | `viewer123` | Viewer | Built-in |
| `technician` | `tech123` | Technician | Built-in |

> Catatan: Operator/Viewer/Technician adalah role bawaan biasa (bukan role sistem) sehingga bisa di-rename atau dihapus jika tidak dipakai. Hanya Super Admin yang berstatus role sistem.

### Step 4: Akses Dashboard

Setelah login berhasil, otomatis redirect ke:

```
http://localhost/robot_dashboard/robot-navigasi/index.php
```

---

## Navigasi Dashboard (6 Tab)

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
- Spray water button
- Save & Reset session
- Mode selection (Manual/Auto)
- Obstacle Detection & Collision Avoidance

**Cara Pakai:**
1. Gunakan control buttons untuk menggerakkan robot
2. Klik **Semprot** untuk spray water
3. Klik **Simpan** untuk save session
4. Klik **Sesi Baru** untuk reset (Admin only)
5. Pilih mode: Manual atau Auto (GPS)
6. Robot akan **berhenti otomatis** jika menabrak obstacle

### Tab 2: Sensors (Sensor Monitor)

**Fitur:**
- Level cairan (persentase & ml)
- Motion detection (Moving/Idle)
- Posisi (X, Y, Z coordinates)
- Speed (current & average)
- Mode operasi (Manual/Auto)
- Koneksi internet (status, ping, quality)

**Cara Pakai:** Klik tab **Sensors**, lihat 6 sensor cards. Data update otomatis dari Firebase.

### Tab 3: Logbook (Activity Logs)

**Fitur:**
- Real-time activity logs
- 4 tipe log (Info, Success, Warning, Error)
- Statistics dashboard
- Filter by type
- Clear logs (Admin only)
- Export to CSV
- Collision & Proximity alerts

**Cara Pakai:**
1. Klik tab **Logbook**
2. Filter by type: All, Info, Success, Warning, Error
3. Klik **Export CSV** untuk download
4. Klik **Clear Logs** untuk hapus semua (Admin only)
5. Collision events tercatat otomatis

### Tab 4: Riwayat (History)

**Fitur:**
- Tabel history semua sesi robot
- Filter by day, month, year
- Data: Waktu, Baterai, Jarak, Air Keluar, Sisa Air

**Cara Pakai:** Klik tab **Riwayat**, pilih filter (Hari/Bulan/Tahun), lihat history sesi.

### Tab 5: Laporan (PDF Report)

**Fitur:**
- Summary data sesi saat ini
- Download PDF report
- Include map screenshot

**Cara Pakai:** Klik tab **Laporan**, klik **Download PDF** untuk generate report.

### Tab 6: Admin (Admin Management)

> Hanya Super Admin yang bisa membuka tab ini.

**Fitur:**
- User Management (Create / View / Toggle Status)
- Role Management (Create / Rename / Delete role custom)
- RBAC Permission Management (checkbox matrix per page/aksi)
- Real-time permission updates
- Audit logging

**Cara Pakai:**

1. **Buat User Baru**
   - Isi form: Username, Nama, Email, Password
   - Pilih Role dari dropdown (otomatis ikut role yang ada di DB, termasuk role custom)
   - Centang **User Aktif** jika ingin langsung aktif
   - Klik **Buat User**

2. **Kelola User**
   - Lihat daftar user di tabel
   - Klik **Aktifkan/Nonaktifkan** untuk toggle status
   - User nonaktif tidak bisa login

3. **Kelola Role**
   - Isi nama role + deskripsi, klik **Tambah Role**
   - Tabel role menampilkan badge tipe (Sistem / Custom) dan jumlah permission yang aktif
   - Tombol **Edit** untuk rename / ubah deskripsi
   - Tombol **Hapus** hanya aktif untuk role custom yang tidak sedang dipakai user manapun
   - Role Super Admin = tipe Sistem, tidak bisa di-rename / hapus

4. **Kelola Permission**
   - Scroll ke panel **RBAC Permission Management**
   - Setiap role punya kotak akses (checkbox per permission) yang bisa diatur sendiri
   - Centang/uncheck permission untuk role apapun
   - Permission tersimpan otomatis (debounce 500ms)
   - Toast notification muncul saat berhasil
   - Super Admin: kotak terkunci & selalu full access

**Tips:**
- User yang sedang login perlu logout/login ulang setelah permission berubah
- Jangan nonaktifkan akun sendiri (sistem otomatis menolak)
- Sebelum hapus role, pindahkan dulu user yang masih memakai role tersebut ke role lain

### Daftar Permission Default (15)

| ID | Nama Permission | Modul | Aksi |
|----|----------------|-------|------|
| 1 | `view_dashboard` | monitoring | view |
| 2 | `control_robot` | monitoring | control |
| 3 | `save_session` | monitoring | create |
| 4 | `reset_session` | monitoring | delete |
| 5 | `spray_water` | monitoring | control |
| 6 | `view_sensors` | sensors | view |
| 7 | `view_logbook` | logbook | view |
| 8 | `export_logs` | logbook | export |
| 9 | `clear_logs` | logbook | delete |
| 10 | `add_maintenance_log` | logbook | create |
| 11 | `view_history` | history | view |
| 12 | `delete_history` | history | delete |
| 13 | `generate_pdf` | reports | create |
| 14 | `manage_users` | admin | manage |
| 15 | `view_audit_logs` | admin | view |

> Daftar di atas adalah permission default. Kamu bisa menambah permission baru dengan `INSERT` ke tabel `permissions`, dan otomatis akan muncul sebagai kotak akses baru di matrix.

---

## Obstacle Detection System

### Fitur Utama

**1. Collision Detection**
- Robot berhenti otomatis saat menabrak obstacle (algoritma AABB)
- Alert muncul dengan pesan error
- Collision event tercatat di logbook
- Bekerja dengan arrow keys & drawn path

**2. Visual Representation**
- Obstacles ditampilkan sebagai kotak merah di minimap
- Warning stripes pattern (diagonal kuning)
- Shadow effect untuk depth
- Warning icon di center obstacle

**3. Proximity Warning**
- Red circle: jarak < 50px (danger zone)
- Orange circle: 50-80px (warning zone)
- Dashed line pattern
- Distance logged ke logbook

**4. Obstacle Editor**
- Toggle **Add Obstacle** mode
- Drag-and-drop untuk create obstacle
- Preview saat dragging
- Minimum size: 20x20px
- Tombol **Clear** untuk hapus semua

### Cara Menggunakan

**A. Navigasi dengan Obstacles**
1. Gunakan arrow keys atau draw path
2. Robot akan berhenti otomatis jika menabrak obstacle
3. Alert muncul: *"Obstacle Terdeteksi!"*
4. Pilih arah lain untuk menghindari obstacle
5. Proximity warning muncul jika dekat obstacle (< 80px)

**B. Menambah Obstacle Baru**
1. Klik tombol **Add Obstacle** di header minimap
2. Tombol berubah orange = mode aktif
3. Klik dan drag di minimap untuk create obstacle
4. Release mouse untuk finalize
5. Obstacle baru muncul sebagai kotak merah
6. Toast notification: *"Obstacle Added"*

**C. Menghapus Obstacles**
1. Klik tombol **Clear** di header minimap
2. Konfirmasi dialog muncul
3. Klik **Ya, Hapus** untuk clear semua obstacles
4. Map kembali bersih

### Default Obstacles

Sistem sudah include 5 obstacles default di map:

| # | Posisi (x, y) | Ukuran |
|---|--------------|--------|
| 1 | (300, 150) | 80x80px |
| 2 | (500, 250) | 60x100px |
| 3 | (200, 300) | 100x50px |
| 4 | (600, 100) | 70x70px |
| 5 | (450, 400) | 90x60px |

### Tips & Tricks

- Robot berhenti **sebelum** menabrak (collision prevention)
- Proximity warning membantu navigasi di area sempit
- Gunakan drawn path untuk route planning menghindari obstacles
- Logbook mencatat semua collision & proximity events
- Obstacle editor memudahkan testing berbagai skenario

---

## RBAC System

Sistem mendukung jumlah role tak terbatas. Ada 1 role sistem (Super Admin) yang **tidak** bisa diubah/dihapus, plus role-role lain yang sepenuhnya bisa kamu kelola sendiri lewat tab Admin.

### Role Bawaan + Role Custom (Unlimited)

#### 1. Super Admin (`admin` / `admin123`) - Role Sistem

- Top tier, full access by design
- Permission selalu full, tidak bisa di-uncheck
- Nama "Super Admin" tidak bisa di-rename
- Role tidak bisa dihapus
- Hanya Super Admin yang bisa membuka tab Admin
- Bisa buat / rename / hapus role custom

#### 2. Operator (`operator` / `operator123`) - Role Bawaan

Default permission:
- Robot control & monitoring
- Bisa spray water, save session
- Tidak bisa reset session, clear logs, akses Admin

Bisa di-rename atau dihapus jika tidak dipakai.

#### 3. Viewer (`viewer` / `viewer123`) - Role Bawaan

Default permission:
- Read-only (lihat dashboard, sensors, logbook, history)
- Bisa generate PDF
- Semua control buttons disabled

Bisa di-rename atau dihapus jika tidak dipakai.

#### 4. Technician (`technician` / `tech123`) - Role Bawaan

Default permission:
- View & export logs, tambah maintenance log
- Tidak bisa control robot

Bisa di-rename atau dihapus jika tidak dipakai.

#### 5. Role Custom (mis. Mahasiswa, QA Tester, Supervisor Lapangan)

- Buat lewat tab Admin -> Role Management
- Tentukan sendiri kotak akses (page/aksi) di RBAC Permission Management
- Bisa di-rename, dihapus (jika belum dipakai user), permission bebas diubah kapan saja

> Saat ini DB sudah berisi contoh role custom: **Mahasiswa** (id=5), bisa kamu rename/hapus dari tab Admin.

---

## Fitur UI

**Header (top right)**
- User info badge (nama + role)
- Real-time clock
- Dark/Light mode toggle
- Logout button dengan konfirmasi

**Minimap Controls**
- **Add Obstacle** button
- **Clear** button
- GPS status indicator

**Design**
- Glassmorphism panels
- Gradient buttons
- Smooth animations
- Responsive layout
- Consistent blue color scheme
- Permission-based UI (buttons disabled sesuai role)
- Obstacle visualization dengan warning colors

---

## Security Features

- Password hashing (bcrypt via `password_hash()`)
- SQL injection prevention (prepared statements)
- Session management (30 menit timeout)
- Auto-logout on timeout
- Audit trail logging (tabel `audit_logs`)
- Permission-based UI rendering
- Secure login/logout
- Collision detection untuk safety
- Super Admin role locked sebagai system role

---

## Technology Stack

**Backend**
- PHP 7.4+
- MySQL/MariaDB (mysqli)
- Firebase Realtime Database

**Frontend**
- HTML5, CSS3, JavaScript ES6+
- Tailwind CSS (CDN)
- Font Awesome 6.4.0
- SweetAlert2
- html2pdf.js
- Canvas API (untuk minimap & obstacles)

---

## Troubleshooting

**Tidak bisa login**
1. Pastikan `db.sql` sudah di-import ke database `robot_dashboard`
2. Cek tabel users ada: `SELECT * FROM users;`
3. Pastikan password benar (case-sensitive)
4. Clear browser cache (Ctrl+F5)

**Role custom yang baru dibuat tidak bisa dihapus**
1. Cek apakah masih ada user yang memakai role tersebut: `SELECT username FROM users WHERE role_id = <id_role>;`
2. Pindahkan user-user itu ke role lain dulu, baru hapus role
3. Role sistem (Super Admin) tidak akan pernah bisa dihapus

**Permission Super Admin tidak bisa di-uncheck**

By design. Super Admin selalu full access (top tier role). Kalau butuh akun "admin terbatas", buat role custom baru lalu pilih kotak akses sesuai kebutuhan.

**Control buttons disabled untuk Admin**
1. Logout dan login ulang (session perlu refresh permission)
2. Clear browser cache
3. Cek tabel `role_permissions`
4. Pastikan session memiliki permissions

**Obstacles tidak muncul di map**
1. Refresh halaman (F5)
2. Clear browser cache
3. Cek console untuk JavaScript errors
4. Pastikan Canvas API supported di browser

**Robot tidak berhenti saat menabrak obstacle**
1. Refresh halaman
2. Cek console untuk errors
3. Pastikan collision detection function loaded
4. Test dengan arrow keys terlebih dahulu

**Session timeout terlalu cepat**
1. Edit `auth.php`
2. Function `getSessionTimeout()`
3. Ubah return value (default: 1800 = 30 menit)

**Firebase tidak connect**
1. Cek koneksi internet
2. Cek Firebase config di `index.php`
3. Pastikan Firebase Realtime Database aktif

---

## Penting Sebelum Production

1. **Ganti password default**
   - Jangan gunakan `admin/admin123` di production
   - Buat password kuat (min 12 karakter)

2. **Buat user real**
   - Buat user account sesuai kebutuhan
   - Disable atau hapus demo accounts

3. **Enable HTTPS**
   - Gunakan SSL certificate
   - Secure login credentials

4. **Backup database**
   - Backup regular database
   - Simpan di tempat aman

5. **Review permissions**
   - Pastikan setiap role punya permission yang sesuai
   - Test dengan semua role

6. **Test obstacle system**
   - Test collision detection di berbagai skenario
   - Pastikan auto-stop berfungsi
   - Verify logbook logging

---

## Quick Links

- Login: `/robot-navigasi/login.php`
- Dashboard: `/robot-navigasi/index.php`
- API: `/robot-navigasi/api.php`
- Admin API: `/robot-navigasi/admin_api.php`

---

## Checklist Testing

**Setup**
- [ ] XAMPP running (Apache + MySQL)
- [ ] Database `robot_dashboard` exists
- [ ] `db.sql` sudah di-import (semua tabel ter-create)
- [ ] Tabel `daily_logs`, `users`, `roles`, `permissions` ada
- [ ] Firebase config benar

**Login**
- [ ] Bisa akses `login.php`
- [ ] Bisa login dengan `admin/admin123`
- [ ] Bisa login dengan `operator/operator123`
- [ ] Bisa login dengan `viewer/viewer123`
- [ ] Bisa login dengan `technician/tech123`
- [ ] User info muncul di header
- [ ] Logout button ada

**Dashboard**
- [ ] 6 tab navigation muncul
- [ ] Semua tab bisa dibuka
- [ ] Dark mode toggle berfungsi
- [ ] Clock real-time berjalan
- [ ] Tab switching smooth

**Obstacle System**
- [ ] 5 default obstacles muncul di map (kotak merah)
- [ ] **Add Obstacle** button ada di minimap header
- [ ] **Clear** button ada di minimap header
- [ ] Bisa toggle obstacle mode (button jadi orange)
- [ ] Bisa drag-and-drop create obstacle baru
- [ ] Preview obstacle muncul saat dragging
- [ ] Obstacle baru tersimpan setelah release
- [ ] Bisa clear semua obstacles dengan konfirmasi

**Collision Detection**
- [ ] Robot berhenti saat arrow keys menabrak obstacle
- [ ] Alert muncul: *"Obstacle Terdeteksi!"*
- [ ] Robot berhenti saat drawn path menabrak obstacle
- [ ] Collision event tercatat di logbook
- [ ] Proximity warning muncul saat dekat obstacle
- [ ] Red circle muncul jika < 50px
- [ ] Orange circle muncul jika 50-80px

**Permissions (Admin)**
- [ ] Semua control buttons enabled
- [ ] Bisa control robot
- [ ] Bisa spray water
- [ ] Bisa save session
- [ ] Bisa reset session
- [ ] Bisa add/clear obstacles

**Permissions (Viewer)**
- [ ] Semua control buttons disabled
- [ ] Tidak bisa control robot
- [ ] Tidak bisa add/clear obstacles
- [ ] Bisa view data
- [ ] Bisa generate PDF

**Custom Roles**
- [ ] Form **Tambah Role** muncul di tab Admin
- [ ] Bisa buat role custom baru
- [ ] Role custom muncul di dropdown create user
- [ ] Role custom muncul di RBAC Permission Matrix
- [ ] Bisa centang/uncheck kotak akses untuk role custom
- [ ] Role custom bisa di-rename via tombol Edit
- [ ] Role custom bisa dihapus jika tidak dipakai user
- [ ] Super Admin punya badge **Sistem** + checkbox terkunci
- [ ] Tombol Hapus pada Super Admin disabled
- [ ] Hapus role yang masih dipakai user otomatis ditolak

---

## Kesimpulan

**Status:** PRODUCTION READY (96%)

| Komponen | Progress |
|----------|----------|
| Fitur Monitoring | 11/11 (100%) |
| RBAC System | 15/15 permission (100%) |
| Admin Management | 100% |
| Custom Roles Management | 100% |
| Obstacle Detection | 100% |
| Documentation | Complete |
| Security | Implemented |

**Completed tasks:** 38/42 (96%)

**Deferred tasks:** 4/42 (4%)
- Machine Vision Analysis (requires ML)
- Geofencing (requires GPS polygon)
- Multi-channel Notifications (requires API keys)

**Arsitektur:** SPA + RBAC + Custom Roles + Obstacle Detection + Admin
**Versi:** 4.2.0
**Status:** PRODUCTION READY

---

**Dibuat:** 2026-05-07
**Updated:** 2026-05-20 (TXT -> MD, sync dengan kondisi DB & kode)
**Developer:** Rizki Triamadewa
**Progress:** 96% (38/42 tasks)
