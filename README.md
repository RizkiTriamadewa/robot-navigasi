# 🤖 NAV-X Robot Dashboard

<div align="center">

![Version](https://img.shields.io/badge/version-4.2.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Status](https://img.shields.io/badge/status-production%20ready-success)

**Advanced Robot Navigation Monitoring System with Custom RBAC & Admin Management**

[Features](#-features) • [Demo](#-demo) • [Installation](#-installation) • [Documentation](#-documentation) • [Screenshots](#-screenshots)

</div>

---

## 📋 Overview

NAV-X Robot Dashboard adalah sistem monitoring dan kontrol robot navigasi berbasis web dengan arsitektur **Single Page Application (SPA)**, **Role-Based Access Control (RBAC) dengan custom roles**, dan **Admin Management System**. Dashboard ini menyediakan monitoring real-time untuk 11 sensor robot, kontrol pergerakan, obstacle detection, dan manajemen user dengan jumlah role tak terbatas yang bisa kamu konfigurasi sendiri.

### ✨ Highlights

- 🎯 **Single Page Application** - Seamless navigation tanpa page reload (6 tabs)
- 🔐 **RBAC System** - Custom roles unlimited dengan kotak akses per page/aksi 🟢
- 🆕 **Admin Management** - User, role, dan permission management interface
- 📊 **Real-time Monitoring** - 11 sensor monitoring via Firebase
- 🗺️ **Interactive Map** - GPS tracking dengan path visualization
- 🔴 **Obstacle Detection** - Collision avoidance system
- 📱 **Responsive Design** - Mobile-friendly interface
- 🎨 **Modern UI** - Glassmorphism design dengan dark mode
- 🔒 **Secure** - Password hashing, SQL injection prevention, session management

---

## 🚀 Features

### 🤖 Robot Monitoring (11 Features)

| Feature | Description | Status |
|---------|-------------|--------|
| **Battery Monitoring** | Real-time battery level & voltage | ✅ |
| **Live Camera Feed** | Multi-camera FPV with recording | ✅ |
| **GPS Tracking** | Interactive map with path tracking | ✅ |
| **Liquid Level Sensor** | Tank level monitoring (ml & %) | ✅ |
| **Motion Detection** | Moving/Idle status detection | ✅ |
| **Position Tracking** | 3D coordinates (X, Y, Z) | ✅ |
| **Speed Monitor** | Current & average speed | ✅ |
| **Operation Mode** | Manual/Auto GPS mode | ✅ |
| **Internet Connection** | Connection status, ping, quality | ✅ |
| **Activity Logbook** | Real-time activity logs | ✅ |
| **Data Visualization** | Charts, graphs, statistics | ✅ |

### 🔐 RBAC System

| Role | Type | Description | Permissions |
|------|------|-------------|-------------|
| **Super Admin** | 🔒 System | Top-tier role, full access by design | All permissions (locked) |
| **Operator** | Built-in | Robot control & monitoring | 9 permissions (control + monitor) |
| **Viewer** | Built-in | Read-only access | 5 permissions (view only) |
| **Technician** | Built-in | Maintenance & logs | 7 permissions (logs + maintenance) |
| **Custom Roles** | 🟢 Custom | Buat role sendiri sebanyak yang dibutuhkan | Atur sendiri lewat checkbox matrix |

**Custom Roles Highlights (NEW v4.2.0):**
- ✅ Buat role baru dari tab Admin → Role Management
- ✅ Atur kotak akses (page/aksi) per role lewat permission matrix
- ✅ Rename atau hapus role custom kapan saja
- ✅ Super Admin tetap top-tier role yang tidak bisa diubah/dihapus
- ✅ Hapus role yang masih dipakai user otomatis ditolak (data integrity)

**Security Features:**
- ✅ Password hashing (bcrypt)
- ✅ SQL injection prevention
- ✅ Session management (30 min timeout)
- ✅ Audit trail logging
- ✅ Permission-based UI rendering

---

## 🎨 Screenshots

### Login Page
<img width="1919" height="908" alt="image" src="https://github.com/user-attachments/assets/0ab1c5c9-3077-4bc7-b800-3403b47dd834" />

> Beautiful gradient login page with demo accounts display

### Dashboard - Monitoring Tab
<img width="1917" height="915" alt="image" src="https://github.com/user-attachments/assets/22a912af-87f4-459f-997a-7bf2949daa47" />

> Main dashboard with robot controls, live camera, GPS map, and real-time data

### Dashboard - Sensors Tab
<img width="1919" height="909" alt="image" src="https://github.com/user-attachments/assets/da2dd568-64be-4206-9aea-100daa372c77" />

> 6 sensor cards with real-time monitoring

### Dashboard - Logbook Tab
<img width="1919" height="908" alt="image" src="https://github.com/user-attachments/assets/bb2dff38-5678-4bf8-bfca-0d955c0c5d72" />

> Activity logs with filtering and export functionality

### Permission-based UI
<img width="342" height="60" alt="image" src="https://github.com/user-attachments/assets/c3026848-c7e1-4081-9190-e7b21e92aa0c" />

> Buttons disabled based on user role (Viewer vs Admin)

---

## 🛠️ Tech Stack

### Backend
- **PHP** 7.4+ - Server-side logic
- **MySQL** 8.0+ - Database (mysqli)
- **Firebase** - Realtime Database

### Frontend
- **HTML5** - Structure
- **CSS3** - Styling with Tailwind CSS
- **JavaScript** ES6+ - Interactivity
- **Font Awesome** 6.4.0 - Icons
- **SweetAlert2** - Beautiful alerts
- **html2pdf.js** - PDF generation

### Architecture
- **SPA** - Single Page Application
- **RBAC** - Role-Based Access Control
- **REST API** - Backend communication

---

## 📦 Installation

### Prerequisites

- XAMPP (Apache + MySQL) or similar
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Modern web browser

### Quick Start

1. **Clone Repository**
   ```bash
   git clone https://github.com/yourusername/robot-navigasi.git
   cd robot-navigasi
   ```

2. **Setup Database**
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE robot_dashboard"
   
   # Import schema (tables, roles, permissions, demo users)
   mysql -u root -p robot_dashboard < db.sql
   ```

3. **Configure Database Connection**
   ```php
   // Edit db.php
   $host = 'localhost';
   $user = 'root';
   $pass = '';
   $db = 'robot_dashboard';
   ```

4. **Access Application**
   ```
   http://localhost/robot_dashboard/robot-navigasi/login.php
   ```

5. **Login with Demo Account**
   ```
   Username: admin
   Password: admin123
   ```

---

## 🎯 Usage

### Dashboard Navigation

The dashboard consists of 6 main tabs:

1. **Monitoring** - Main dashboard with robot controls & obstacle detection
2. **Sensors** - Additional sensor monitoring
3. **Logbook** - Activity logs and history
4. **Riwayat** - Session history data
5. **Laporan** - PDF report generation
6. **Admin** - User, role & permission management (Super Admin only) 🆕

### User Roles

#### Super Admin (System Role 🔒)
- ✅ Full system access by design (top-tier)
- ✅ All control buttons enabled
- ✅ Manage users, roles & permissions
- ✅ Create / rename / delete custom roles
- ✅ Cannot be renamed or deleted (built-in safeguard)

#### Operator (Built-in Role)
- ✅ Robot control & monitoring
- ✅ Save sessions
- ❌ Cannot reset sessions
- ❌ Cannot manage users
- 📝 Can be renamed or deleted via Admin tab if not in use

#### Viewer (Built-in Role)
- ✅ View all data
- ✅ Generate PDF reports
- ❌ All control buttons disabled
- 📝 Can be renamed or deleted via Admin tab if not in use

#### Technician (Built-in Role)
- ✅ View & export logs
- ✅ Add maintenance logs
- ❌ Cannot control robot
- 📝 Can be renamed or deleted via Admin tab if not in use

#### Custom Roles 🟢 (NEW v4.2.0)
- ✅ Create unlimited custom roles via Admin → Role Management
- ✅ Pick exactly which page/action permissions are allowed via the checkbox matrix
- ✅ Rename, delete, or update permissions anytime
- ✅ Custom roles automatically appear in the "Create User" role dropdown

---

## 📚 Documentation

### Main Documentation
- [SUMMARY.md](SUMMARY.md) - Complete project summary
- [CARA_PAKAI.txt](CARA_PAKAI.txt) - User guide (Indonesian)

### API Documentation
- [api.php](api.php) - REST API endpoints
- [admin_api.php](admin_api.php) - Admin / role / permission management API

### Database Schema
- [db.sql](db.sql) - Complete database schema (tables, roles, permissions, demo users)

---

## 🔐 Demo Accounts

| Username | Password | Role | Type | Access Level |
|----------|----------|------|------|--------------|
| `admin` | `admin123` | Super Admin | 🔒 System | Full Access |
| `operator` | `operator123` | Operator | Built-in | Control + Monitor |
| `viewer` | `viewer123` | Viewer | Built-in | Read-only |
| `technician` | `tech123` | Technician | Built-in | Logs + Maintenance |

> ⚠️ **Warning**: Change these passwords before production deployment!
> 💡 Built-in roles can be renamed or deleted via the Admin tab. Only Super Admin is locked as a system role.

---

## 📁 Project Structure

```
robot-navigasi/
├── 🔐 RBAC System
│   ├── auth.php            # Authentication functions
│   ├── login.php           # Login page
│   ├── logout.php          # Logout handler
│   └── admin_api.php       # Admin / role / permission API 🆕
│
├── 📱 Application
│   ├── index.php           # Main SPA (6 tabs)
│   ├── api.php             # REST API
│   ├── db.php              # Database connection
│   └── db.sql              # Complete database schema
│
├── 📚 Documentation
│   ├── README.md           # This file
│   ├── SUMMARY.md          # Project summary
│   └── CARA_PAKAI.txt      # User guide
│
└── ⚙️ Config
    ├── .gitignore
    └── package.json
```

---

## 🔧 Configuration

### Firebase Setup

1. Create Firebase project at [Firebase Console](https://console.firebase.google.com/)
2. Enable Realtime Database
3. Update Firebase config in `index.php`:

```javascript
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_PROJECT.firebaseapp.com",
    databaseURL: "https://YOUR_PROJECT.firebaseio.com",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_PROJECT.appspot.com",
    messagingSenderId: "YOUR_SENDER_ID",
    appId: "YOUR_APP_ID"
};
```

### Database Configuration

Edit `db.php`:
```php
$host = 'localhost';
$user = 'root';
$pass = 'your_password';
$db = 'robot_dashboard';
```

---

## 🧪 Testing

### Manual Testing Checklist

- [ ] Login with all 4 demo accounts
- [ ] Verify permissions for each role
- [ ] Test robot controls (Admin/Operator)
- [ ] Verify buttons disabled for Viewer
- [ ] Test session timeout (30 min)
- [ ] Test logout functionality
- [ ] Verify audit logs recording
- [ ] Test all 5 tabs navigation
- [ ] Test dark mode toggle
- [ ] Test PDF generation

---

## 🚨 Security

### Before Production

1. **Change Default Passwords**
   ```sql
   UPDATE users SET password = PASSWORD_HASH('new_strong_password', PASSWORD_DEFAULT) 
   WHERE username = 'admin';
   ```

2. **Create Real Users**
   - Create production user accounts
   - Disable or delete demo accounts

3. **Enable HTTPS**
   - Use SSL certificate
   - Force HTTPS redirect

4. **Database Backup**
   - Setup regular backups
   - Store backups securely

5. **Review Permissions**
   - Audit role permissions
   - Test with all roles

---

## 🐛 Troubleshooting

### Cannot Login

**Problem**: Login fails with correct credentials

**Solution**:
1. Verify `db.sql` was imported into the `robot_dashboard` database
2. Check users table: `SELECT * FROM users;`
3. Clear browser cache (Ctrl+F5)
4. Check password hash in database

### Cannot Delete a Custom Role

**Problem**: "Role masih digunakan oleh N user" message when deleting

**Solution**:
1. Reassign affected users to a different role first
2. Or deactivate / delete those users
3. Then retry the delete action

### Super Admin Permissions Look Locked

**Problem**: Cannot uncheck Super Admin permissions

**Solution**: This is intentional. Super Admin is the top-tier system role and always has full access. If you need a restricted admin, create a custom role and assign only the permissions you want.

### Control Buttons Disabled

**Problem**: Buttons disabled for Admin user

**Solution**:
1. Logout and login again
2. Clear browser cache
3. Check `role_permissions` table
4. Verify session has correct permissions

### Session Timeout Too Fast

**Problem**: Session expires too quickly

**Solution**:
1. Edit `auth.php`
2. Function `getSessionTimeout()`
3. Increase timeout value (default: 1800 seconds)

---

## 📊 Statistics

- **Total Features**: 11 monitoring + Custom RBAC + Admin Management + Obstacle Detection
- **Total Roles**: Unlimited (1 system + 3 built-in + N custom)
- **Total Permissions**: 15 granular permissions (extendable via DB)
- **Total Database Tables**: 7 (2 main + 5 RBAC)
- **Total Files**: 21+ files (including admin_api.php)
- **Lines of Code**: ~2700+ lines
- **Architecture**: SPA + Custom RBAC + Admin Management
- **Version**: 4.2.0
- **Tabs**: 6 (Monitoring, Sensors, Logbook, Riwayat, Laporan, Admin)

---

## 🗺️ Roadmap

### Version 4.2.0 (Completed) ✅
- ✅ Custom roles management (create / rename / delete)
- ✅ Per-role permission matrix with checkbox toggles
- ✅ Super Admin locked as system role (top-tier)
- ✅ Dynamic role dropdown in user creation form
- ✅ Safeguards: cannot delete role still in use, cannot edit system role permissions

### Version 4.1.0 (Completed) ✅
- ✅ Admin panel for user management
- ✅ RBAC permission management interface
- ✅ User creation & status toggle
- ✅ Real-time permission updates
- ✅ Audit logging for admin actions

### Version 4.3.0 (Planned)
- [ ] Password reset functionality
- [ ] User profile page
- [ ] Reassign users when deleting a role
- [ ] Email notifications
- [ ] Advanced analytics dashboard
- [ ] Export audit logs

### Version 4.4.0 (Future)
- [ ] Two-factor authentication (2FA)
- [ ] API authentication (JWT)
- [ ] Mobile app (React Native)
- [ ] Multi-language support
- [ ] Advanced reporting

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author

**Rizki Triamadewa**

- GitHub: [@yourusername](https://github.com/yourusername)
- Email: your.email@example.com

---

## 🙏 Acknowledgments

- Font Awesome for icons
- Tailwind CSS for styling
- Firebase for real-time database
- SweetAlert2 for beautiful alerts
- html2pdf.js for PDF generation

---

## 📞 Support

For support, email your.email@example.com or open an issue on GitHub.

---

<div align="center">

**⭐ Star this repo if you find it helpful!**

Made with ❤️ by Rizki Triamadewa

[⬆ Back to Top](#-nav-x-robot-dashboard)

</div>
