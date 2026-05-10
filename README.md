# 🤖 NAV-X Robot Dashboard

<div align="center">

![Version](https://img.shields.io/badge/version-3.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Status](https://img.shields.io/badge/status-production%20ready-success)

**Advanced Robot Navigation Monitoring System with RBAC**

[Features](#-features) • [Demo](#-demo) • [Installation](#-installation) • [Documentation](#-documentation) • [Screenshots](#-screenshots)

</div>

---

## 📋 Overview

NAV-X Robot Dashboard adalah sistem monitoring dan kontrol robot navigasi berbasis web dengan arsitektur **Single Page Application (SPA)** dan **Role-Based Access Control (RBAC)**. Dashboard ini menyediakan monitoring real-time untuk 11 sensor robot, kontrol pergerakan, dan manajemen user dengan 4 level akses berbeda.

### ✨ Highlights

- 🎯 **Single Page Application** - Seamless navigation tanpa page reload
- 🔐 **RBAC System** - 4 user roles dengan 15 granular permissions
- 📊 **Real-time Monitoring** - 11 sensor monitoring via Firebase
- 🗺️ **Interactive Map** - GPS tracking dengan path visualization
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

| Role | Description | Permissions |
|------|-------------|-------------|
| **Super Admin** | Full system access | All 15 permissions |
| **Operator** | Robot control & monitoring | 9 permissions (control + monitor) |
| **Viewer** | Read-only access | 5 permissions (view only) |
| **Technician** | Maintenance & logs | 7 permissions (logs + maintenance) |

**Security Features:**
- ✅ Password hashing (bcrypt)
- ✅ SQL injection prevention
- ✅ Session management (30 min timeout)
- ✅ Audit trail logging
- ✅ Permission-based UI rendering

---

## 🎨 Screenshots

### Login Page
> Beautiful gradient login page with demo accounts display

### Dashboard - Monitoring Tab
> Main dashboard with robot controls, live camera, GPS map, and real-time data

### Dashboard - Sensors Tab
> 6 sensor cards with real-time monitoring

### Dashboard - Logbook Tab
> Activity logs with filtering and export functionality

### Permission-based UI
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
   
   # Import main database
   mysql -u root -p robot_dashboard < db.sql
   
   # Import RBAC tables
   mysql -u root -p robot_dashboard < setup_rbac.sql
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

The dashboard consists of 5 main tabs:

1. **Monitoring** - Main dashboard with robot controls
2. **Sensors** - Additional sensor monitoring
3. **Logbook** - Activity logs and history
4. **Riwayat** - Session history data
5. **Laporan** - PDF report generation

### User Roles

#### Super Admin
- ✅ Full system access
- ✅ All control buttons enabled
- ✅ Can manage users
- ✅ Can reset sessions

#### Operator
- ✅ Robot control & monitoring
- ✅ Can save sessions
- ❌ Cannot reset sessions
- ❌ Cannot manage users

#### Viewer
- ✅ View all data
- ✅ Generate PDF reports
- ❌ All control buttons disabled
- ❌ Read-only access

#### Technician
- ✅ View & export logs
- ✅ Add maintenance logs
- ❌ Cannot control robot
- ❌ Cannot manage users

---

## 📚 Documentation

### Main Documentation
- [SUMMARY.md](SUMMARY.md) - Complete project summary
- [README_RBAC.md](README_RBAC.md) - RBAC system guide (Indonesian)
- [RBAC_SETUP_GUIDE.md](RBAC_SETUP_GUIDE.md) - Setup instructions
- [CARA_PAKAI.txt](CARA_PAKAI.txt) - User guide (Indonesian)
- [VISUAL_GUIDE.md](VISUAL_GUIDE.md) - Visual mockups

### API Documentation
- [api.php](api.php) - REST API endpoints

### Database Schema
- [db.sql](db.sql) - Main database schema
- [setup_rbac.sql](setup_rbac.sql) - RBAC tables

---

## 🔐 Demo Accounts

| Username | Password | Role | Access Level |
|----------|----------|------|--------------|
| `admin` | `admin123` | Super Admin | Full Access |
| `operator` | `operator123` | Operator | Control + Monitor |
| `viewer` | `viewer123` | Viewer | Read-only |
| `technician` | `tech123` | Technician | Logs + Maintenance |

> ⚠️ **Warning**: Change these passwords before production deployment!

---

## 📁 Project Structure

```
robot-navigasi/
├── 🔐 RBAC System
│   ├── setup_rbac.sql      # Database RBAC setup
│   ├── auth.php            # Authentication functions
│   ├── login.php           # Login page
│   └── logout.php          # Logout handler
│
├── 📱 Application
│   ├── index.php           # Main SPA
│   ├── api.php             # REST API
│   ├── db.php              # Database connection
│   └── db.sql              # Main database schema
│
├── 📚 Documentation
│   ├── README.md           # This file
│   ├── SUMMARY.md          # Project summary
│   ├── README_RBAC.md      # RBAC guide
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
1. Verify `setup_rbac.sql` was executed
2. Check users table: `SELECT * FROM users;`
3. Clear browser cache (Ctrl+F5)
4. Check password hash in database

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

- **Total Features**: 11 monitoring + RBAC system
- **Total Roles**: 4 user roles
- **Total Permissions**: 15 granular permissions
- **Total Database Tables**: 7 (2 main + 5 RBAC)
- **Total Files**: 20 files
- **Lines of Code**: ~2000+ lines
- **Architecture**: SPA + RBAC
- **Version**: 3.0.0

---

## 🗺️ Roadmap

### Version 3.1.0 (Planned)
- [ ] Admin panel for user management
- [ ] Password reset functionality
- [ ] User profile page
- [ ] Email notifications
- [ ] Advanced analytics dashboard

### Version 3.2.0 (Future)
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

**Antigravity AI**

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

Made with ❤️ by Antigravity AI

[⬆ Back to Top](#-nav-x-robot-dashboard)

</div>
