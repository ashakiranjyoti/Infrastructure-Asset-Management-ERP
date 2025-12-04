# Infrastructure Asset Management System

A comprehensive web-based asset tracking system for managing sites, tubewells, and LCS (Liquid Chlorination System) infrastructure with daily status updates, media documentation, and audit trail management.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

---

## 📋 Project Overview

This is a **portfolio project** demonstrating enterprise-level asset management capabilities. Built for infrastructure monitoring, the system handles complete asset lifecycle tracking, daily status updates, media management, and comprehensive reporting.

**Note:** This is a sanitized version for portfolio purposes. All company-specific information, real site data, and media files have been removed/anonymized.

---

## ✨ Key Features

### 🏗️ Multi-Level Asset Management

**Site Management:**
- Site master data (location, division, contractor, site incharge)
- GPS coordinates (latitude/longitude) for map visualization
- Active/Inactive status tracking
- Number of tubewells per site
- LCS availability flag

**Tubewell Management:**
- Tubewell master under each site
- Pump and motor specifications
- Installation dates
- GPS coordinates
- Individual status tracking

**LCS (Liquid Chlorination System) Management:**
- One LCS per site capability
- LCS item master
- Daily status history per item
- Master notes with contributors
- Media attachments per item and date

---

### 📊 Real-Time Status & Monitoring

**Daily Status Updates:**
- Item-wise status tracking (configurable items)
- Remarks and notes per entry
- Created by tracking (user accountability)
- Timestamp recording
- Status history maintenance

**Dashboard Features:**
- Search across multiple fields (site name, location, contractor, etc.)
- Card-based site display with quick links
- Active/Inactive visual indicators (green/red)
- Quick access to detailed views

**Map-Based Visualization:**
- OpenStreetMap integration
- Site plotting via lat/long coordinates
- Color-coded markers (active=green, inactive=red)
- Click for site information popup
- Direct navigation to detail pages

---

### 📸 Media & Documentation Management

**Media Upload System:**
- Image upload (JPEG, PNG)
- Video upload (MP4, AVI)
- File type validation
- Secure storage
- Linked to sites/tubewells/LCS

**Media Gallery:**
- Modal preview for images
- Video player integration
- Date-wise media organization
- Download capabilities
- Fallback for missing media

---

### 📈 Comprehensive Reporting

**Report Types:**

1. **Site Report:**
   - Complete site metadata
   - Tubewell list with specifications
   - Status snapshots
   - Media references
   - PDF/Excel export

2. **LCS Site Report:**
   - Date-wise status matrix
   - Item-wise status tracking
   - Highlights changed items
   - Shows media per item
   - Contributors list ("By/With")
   - Quick links to PDF/Excel

3. **User-wise Report:**
   - Activity tracking per user
   - Update history
   - Audit trail

4. **Date-wise Change Report:**
   - Audit trail across date ranges
   - What changed, when, and by whom
   - Compliance reporting

**Export Options:**
- PDF generation (TCPDF)
- Excel downloads
- One-click exports
- Formatted printable reports

---

### 🔐 Access Control & Security

**Role-Based Access:**
- **Admin:** Full CRUD, user management, settings access
- **User:** View and update assigned sites/data

**Authentication:**
- Secure session-based login
- Password protection
- Session timeout
- Role-gated navigation
- Protected routes with redirects

**Audit Features:**
- Created by tracking on all entries
- Updated by tracking
- Timestamp on all changes
- Date-wise change reports
- User activity logs

---

## 🛠️ Tech Stack

### Backend
- **PHP** (Procedural) - Core application logic
- **MySQL** (mysqli) - Database management
- **Sessions** - Authentication & state management

### Frontend
- **HTML5/CSS3** - Structure and styling
- **Bootstrap** - Responsive UI framework
- **JavaScript (Vanilla)** - Client-side interactivity
- **jQuery** - AJAX and DOM manipulation

### Libraries & Integrations
- **TCPDF** - PDF report generation
- **OpenStreetMap API** - Map visualization
- **Custom CSS** - Responsive cards, modals, badges

### Development Environment
- **XAMPP** - Local development (Apache + PHP + MySQL on Windows)
- **phpMyAdmin** - Database management

---

## 📁 Project Structure
```
infrastructure-asset-management/
├── admin/                          # Main application directory
│   ├── index.php                   # Login page
│   ├── header.php                  # Header with navigation
│   ├── dashboard.php               # Searchable dashboard
│   ├── view_site.php              # Site detail view
│   ├── includes/
│   │   ├── dbconnection.php       # Database connection (not in git)
│   │   ├── db_config.php          # Centralized config
│   │   └── sanitize.php           # Input sanitization
│   ├── setup-device-*.php         # Device management
│   ├── setup-account-*.php        # User management
│   ├── lcs-*.php                  # LCS module
│   ├── site_report.php            # Site reporting
│   ├── lcs_site_report.php        # LCS reporting
│   ├── user_wise_report.php       # User activity report
│   ├── date_change_report.php     # Audit trail report
│   ├── generate_*_report.php      # PDF generation scripts
│   ├── generate_*_excel.php       # Excel export scripts
│   └── uploads/                   # Media storage (gitignored)
├── database/
│   └── schema.sql                 # Database schema with sample data
├── .gitignore
├── .env.example                   # Environment variables template
├── LICENSE
└── README.md
```

---

## 🚀 Installation Guide

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.3+)
- Apache/Nginx web server
- XAMPP/WAMP/LAMP (recommended for local development)

### Setup Steps

#### 1. Clone Repository
```bash
git clone https://github.com/yourusername/infrastructure-asset-management.git
cd infrastructure-asset-management
```

#### 2. Database Setup

**Create Database:**
```sql
CREATE DATABASE asset_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Import Schema:**
```bash
mysql -u root -p asset_management < database/schema.sql
```

Or via phpMyAdmin:
- Open phpMyAdmin
- Select `asset_management` database
- Go to "Import" tab
- Choose `database/schema.sql`
- Click "Go"

#### 3. Configure Database Connection

**Copy example config:**
```bash
cp .env.example .env
```

**Update `admin/includes/db_config.php`:**
```php
<?php
$servername = "localhost";
$username = "root";
$password = "your_password_here";
$dbname = "asset_management";
?>
```

**⚠️ Important:** Never commit `db_config.php` with real credentials!

#### 4. Set Permissions (Linux/Mac)
```bash
# Make admin directory accessible
chmod 755 -R admin/

# Make uploads writable
chmod 777 admin/uploads/

# Make TCPDF cache writable (if using TCPDF)
chmod 777 -R admin/TCPDF-main/cache/
```

#### 5. Configure Web Server

**Apache (.htaccess already included):**
```apache
# Enable mod_rewrite if needed
<Directory /path/to/infrastructure-asset-management>
    AllowOverride All
    Require all granted
</Directory>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/infrastructure-asset-management;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

#### 6. Access Application

**Local Development:**
```
http://localhost/infrastructure-asset-management/admin/
```

**Production:**
```
https://yourdomain.com/admin/
```

#### 7. Default Login Credentials
```
Username: admin
Password: admin123
```

**⚠️ IMPORTANT:** Change password immediately after first login!

---

## 💾 Database Schema

### Core Tables

**users:**
- User authentication
- Role management (admin/user)
- Full name, access type

**sites:**
- Site master data
- Location, GPS coordinates
- Contractor, site incharge details
- Number of tubewells
- LCS availability flag

**tubewells:**
- Tubewell master per site
- Pump/motor specifications
- Installation dates
- GPS coordinates

**status_history:**
- Daily status updates
- Item-wise tracking
- Remarks and timestamps
- Created by user tracking

**media / tubewell_media:**
- File path storage
- File type tracking
- Upload timestamps
- Uploaded by user

**item_master:**
- Configurable items for status tracking
- Active/inactive flags

**lcs:**
- LCS master per site

**lcs_status_history:**
- Date-wise LCS item status
- Make/model, size/capacity
- Remarks

**lcs_master_notes:**
- Overall LCS notes per date
- Contributors tracking

**lcs_media / lcs_master_media:**
- LCS-specific media storage
- Date and item linking

### Relationships
- Sites → Tubewells (One-to-Many)
- Sites → LCS (One-to-One)
- Sites → Status History (One-to-Many)
- Users → All records (Created By relationship)

---

## 🎯 Key Features Explained

### 1. Real-Time Status Monitoring

**Implementation:**
```php
// Daily status with audit trail
INSERT INTO status_history (
    site_id, tubewell_id, item_name, status, 
    remark, created_by, updated_at
) VALUES (?, ?, ?, ?, ?, ?, NOW());
```

**Auto-refresh capability via AJAX** (can be added)

---

### 2. Hierarchical Asset Structure
```
Site
├── Metadata (location, contractor, etc.)
├── Tubewells
│   ├── Specifications
│   ├── Status History
│   └── Media
└── LCS
    ├── LCS Items
    ├── Status History per Item
    ├── Master Notes
    └── Media per Item/Date
```

---

### 3. Advanced Search

**Multi-field OR filtering:**
```php
WHERE site_name LIKE ? 
   OR location LIKE ? 
   OR division_name LIKE ? 
   OR contractor_name LIKE ?
```

Implemented with prepared statements for security.

---

### 4. LCS Reporting Logic

**Complex data merging:**
- Status history (item-wise, date-wise matrix)
- Master notes per date
- Contributors per date
- Media per item and date

**Optimization:**
- Preload all data in single queries
- Build PHP arrays indexed by keys
- Avoid N+1 query problem

---

### 5. Media Management

**Upload validation:**
```php
$allowed_image = ['jpg', 'jpeg', 'png', 'gif'];
$allowed_video = ['mp4', 'avi', 'mov', 'mkv'];

// File type check
// Size limit check
// Secure filename generation
```

**Display with fallback:**
```php
if (file_exists($file_path)) {
    echo '<img src="' . $file_path . '">';
} else {
    echo '<div class="no-media">Media not available</div>';
}
```

---

## 📊 Screenshots

[Add screenshots after anonymizing - showing:]
1. Login page
2. Dashboard with search
3. Map view with site markers
4. Site detail page
5. Status update form
6. Media gallery
7. Reports (Site report, LCS report)
8. User management (Admin only)

---

## 🔒 Security Features

### Implemented
✅ Session-based authentication  
✅ Role-based access control  
✅ Prepared statements (partially)  
✅ Input sanitization via `sanitizeInput()`  
✅ File upload validation  
✅ Session timeout  
✅ SQL injection prevention (mysqli_real_escape_string)

### Recommended Additions (For Production)
- [ ] HTTPS enforcement
- [ ] CSRF tokens on forms
- [ ] Password hashing with bcrypt (currently MD5 - upgrade needed)
- [ ] Rate limiting on login
- [ ] Content Security Policy headers
- [ ] XSS prevention filters
- [ ] Complete migration to prepared statements
- [ ] Input validation library

---

## 🚧 Known Limitations & Future Enhancements

### Current Limitations
- Procedural PHP (no MVC framework)
- Basic password hashing (MD5 - should upgrade to bcrypt)
- No API layer (direct page access only)
- Limited mobile responsiveness on some pages
- Manual media cleanup (orphaned files)

### Planned Enhancements
- [ ] Migrate to Laravel or CodeIgniter (MVC architecture)
- [ ] RESTful API for mobile app integration
- [ ] WebSocket for real-time updates (replace AJAX polling)
- [ ] Advanced analytics dashboard (charts, trends)
- [ ] Email notifications (status changes, alerts)
- [ ] Bulk upload via Excel
- [ ] Export to multiple formats (CSV, JSON, XML)
- [ ] Offline capability (Progressive Web App)
- [ ] Automated backup system
- [ ] QR code generation for assets
- [ ] Mobile app (React Native / Flutter)

---

## 🤝 Contributing

This is a portfolio project, but feedback and suggestions are welcome!

To suggest improvements:
1. Open an issue
2. Describe the enhancement or bug
3. I'll review and respond

---

## 📄 License

MIT License - See [LICENSE](LICENSE) file for details.

**This project is for portfolio and educational purposes.**

---

## ⚠️ Important Disclaimer

### Portfolio Project Notice

This is a **sanitized portfolio version** of a professional project developed during employment.

**Modifications for portfolio:**
- All company branding, logos, and identifying information removed
- Real site data, tubewell data, and user information replaced with sample/dummy data
- Actual media files (images/videos) excluded
- Database credentials are placeholder examples
- Proprietary business logic generalized

**Purpose:**
- Demonstrate technical skills and system architecture capabilities
- Showcase full-stack development, database design, and ERP module development
- Provide code samples for potential employers
- Educational reference

**Legal Compliance:**
- No confidential company information included
- No trade secrets or competitive data disclosed
- Follows standard industry practice for developer portfolios
- All sensitive data anonymized

For concerns or questions: [your email]

---

## 👤 Author

**[Your Name]**  
Backend Developer | PHP | MySQL | ERP Systems

- 📧 Email: your.email@example.com
- 💼 LinkedIn: [Your LinkedIn Profile]
- 🌐 Portfolio: [Your Website]
- 💻 GitHub: [@yourusername](https://github.com/yourusername)

---

## 🙏 Acknowledgments

- Built during professional work to demonstrate enterprise asset management capabilities
- Thanks to open-source communities for Bootstrap, TCPDF, OpenStreetMap
- Inspired by real-world infrastructure monitoring needs

---

## 📞 Contact

For technical questions, job opportunities, or collaboration:

**Email:** your.email@example.com  
**LinkedIn:** [Your Profile]

**Interview Availability:** Open to discussing the architecture, implementation details, and technical decisions behind this project.

---

**⭐ If you find this project interesting, please star the repository!**

---

### Additional Documentation

For detailed technical documentation:
- [Database Schema Details](docs/DATABASE.md) *(optional - create if needed)*
- [API Endpoints](docs/API.md) *(for future API layer)*
- [Deployment Guide](docs/DEPLOYMENT.md) *(production deployment steps)*

---

**Last Updated:** December 2024  
**Version:** 1.0.0 (Portfolio Release)
