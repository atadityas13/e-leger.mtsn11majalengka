# Security Policy

## 🔒 Komitmen Keamanan

**TRACER MTsN 11 Majalengka** dibangun dengan standar keamanan tinggi untuk melindungi data akademik siswa dan integritas sistem.

---

## 🛡️ Fitur Keamanan Built-in

### 1. **Database Security**
- **PDO Prepared Statements**: Semua query menggunakan parameter binding untuk mencegah SQL Injection
- **Password Hashing**: Algoritma BCRYPT dengan `password_hash()` PHP
- **Unique Constraints**: Validasi NISN/NIS di level database
- **Transaction Support**: Operasi kritikal dibungkus dalam database transaction

### 2. **Application Security**
- **CSRF Protection**: Token validation di semua form POST
- **Input Validation**: Sanitasi dan validasi input user (nilai 70-100, format NISN, dll)
- **Role-based Access Control**: Hierarki akses admin vs kurikulum
- **Session Management**: Session timeout dan regeneration

### 3. **HTTP Security Headers** (Halaman Verifikasi QR)
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: no-referrer
Permissions-Policy: geolocation=(), microphone=(), camera=()
Content-Security-Policy: default-src 'self'; ...
```

### 4. **Document Verification**
- **Token-based QR Code**: Setiap transkrip memiliki token unik SHA256
- **Authenticity Statement**: Dokumen dianggap asli selama data fisik identik dengan sistem
- **Error Handling**: Professional error pages untuk token invalid/expired

---

## 📋 Supported Versions

Versi yang menerima security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.1.x   | ✅ Fully supported |
| 1.0.x   | ⚠️ Critical fixes only |
| < 1.0   | ❌ Not supported   |

**Recommendation**: Selalu gunakan versi terbaru untuk mendapatkan patch keamanan.

---

## 🚨 Reporting a Vulnerability

Jika Anda menemukan celah keamanan, **JANGAN buat issue publik di GitHub**.

### Cara Melaporkan

**Email**: mtsn11majalengka@gmail.com  
**Subject**: `[SECURITY] TRACER Vulnerability Report`

### Informasi yang Diperlukan

Mohon sertakan detail berikut:

1. **Ringkasan Kerentanan**  
   Deskripsi singkat masalah keamanan

2. **Lokasi Terdampak**  
   - File/path yang terpengaruh
   - Modul/fitur sistem

3. **Langkah Reproduksi**  
   Step-by-step cara mereproduksi vulnerability

4. **Dampak Potensial**  
   - Tingkat severity (Low/Medium/High/Critical)
   - Data yang terekspos
   - Kemungkinan eksploitasi

5. **Proof of Concept** (jika ada)  
   - Screenshot
   - Video demo
   - Code snippet (tanpa exploit aktif)

6. **Saran Mitigasi** (opsional)  
   Rekomendasi perbaikan jika ada

---

## ⏱️ Response Timeline

Kami berkomitmen untuk merespons laporan keamanan dengan timeline berikut:

| Tahap | Target Waktu |
|-------|--------------|
| **Konfirmasi Penerimaan** | Maksimal 3 hari kerja |
| **Validasi Awal** | Maksimal 7 hari kerja |
| **Patch Development** | Tergantung severity: <br> - Critical: 24-48 jam <br> - High: 3-7 hari <br> - Medium: 7-14 hari <br> - Low: 14-30 hari |
| **Release Patch** | Setelah testing & validasi |
| **Public Disclosure** | Koordinasi dengan reporter |

---

## 📢 Disclosure Policy

### Coordinated Disclosure

Kami mengikuti prinsip **Responsible Disclosure**:

1. **DO NOT** publish vulnerability sebelum patch dirilis
2. **DO** berikan waktu yang wajar untuk perbaikan (minimum 90 hari)
3. **DO** koordinasi dengan kami untuk timeline disclosure
4. **DO** accept credit recognition (jika Anda inginkan)

### After Patch Release

Setelah patch dirilis:
- Vulnerability akan dicantumkan di CHANGELOG.md
- Credit diberikan kepada reporter (dengan persetujuan)
- Advisory detail dipublikasikan jika diperlukan

---

## ✅ Security Best Practices untuk Developer

Jika Anda berkontribusi ke TRACER, ikuti panduan ini:

### 1. **Database Queries**
```php
// ✅ GOOD - Prepared Statement
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE nisn = ?");
$stmt->execute([$nisn]);

// ❌ BAD - String Concatenation
$query = "SELECT * FROM siswa WHERE nisn = '$nisn'";
```

### 2. **Password Handling**
```php
// ✅ GOOD
$hashed = password_hash($password, PASSWORD_BCRYPT);

// ❌ BAD
$hashed = md5($password);
```

### 3. **Output Escaping**
```php
// ✅ GOOD
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// ❌ BAD
echo $user_input;
```

### 4. **File Upload Validation**
```php
// ✅ GOOD - Validate type & size
$allowed = ['jpg', 'png', 'pdf'];
$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
if (!in_array(strtolower($ext), $allowed)) {
    throw new Exception('Invalid file type');
}

// ❌ BAD - Tidak ada validasi
move_uploaded_file($_FILES['file']['tmp_name'], $destination);
```

### 5. **Environment Variables**
```php
// ✅ GOOD - Gunakan .env
$dbHost = getenv('DB_HOST');

// ❌ BAD - Hardcode credentials
$dbHost = 'localhost';
$dbPass = 'rahasia123';
```

---

## 🔐 Security Checklist untuk Deployment

Sebelum deploy ke production:

- [ ] File `.env` tidak ikut ter-commit ke git
- [ ] Password default sudah diganti
- [ ] Folder `uploads/` dan `storage/` memiliki permission yang benar (755)
- [ ] PHP error reporting dimatikan (`display_errors = Off`)
- [ ] Database user menggunakan least privilege
- [ ] HTTPS diaktifkan dengan SSL certificate valid
- [ ] Backup otomatis database diaktifkan
- [ ] Security headers sudah terkonfigurasi di web server

---

## 📚 Security Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)

---

## 📞 Kontak

**MTsN 11 Majalengka**  
Email: mtsn11majalengka@gmail.com  
Website: https://mtsn11majalengka.sch.id

---

**TRACER** — *Secure, Reliable, Trustworthy.*
