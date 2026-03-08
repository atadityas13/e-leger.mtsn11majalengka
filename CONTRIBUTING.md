# Contributing Guide

Terima kasih atas minat Anda untuk berkontribusi pada **TRACER MTsN 11 Majalengka**! 🎉

Sistem **Transkrip & Academic Ledger** ini dibangun untuk pendidikan, dan kami menyambut kontribusi yang meningkatkan kualitas, performa, dan keamanan aplikasi.

---

## 📋 Cara Berkontribusi

### 1. **Fork & Clone Repository**

```bash
# Fork repository via GitHub UI, kemudian clone
git clone https://github.com/YOUR_USERNAME/tracer-mtsn11majalengka.git
cd tracer-mtsn11majalengka

# Setup remote upstream
git remote add upstream https://github.com/mtsn11majalengka/tracer-mtsn11majalengka.git
```

### 2. **Buat Branch Baru**

Gunakan naming convention yang jelas:

```bash
# Untuk fitur baru
git checkout -b feature/nama-fitur

# Untuk bug fix
git checkout -b fix/nama-bug

# Untuk improvement
git checkout -b improve/deskripsi-improvement

# Untuk dokumentasi
git checkout -b docs/update-readme
```

**Contoh:**
- `feature/export-leger-per-kelas`
- `fix/nilai-import-validation`
- `improve/pdf-generation-performance`
- `docs/add-api-documentation`

### 3. **Setup Development Environment**

```bash
# Install dependencies
composer install

# Copy .env.example
copy .env.example .env

# Edit .env dengan database lokal Anda
# Kemudian import database/schema.sql
```

### 4. **Lakukan Perubahan**

- Ikuti standar kode yang ada
- Pastikan perubahan scope kecil dan fokus
- Jangan mengubah banyak fitur sekaligus dalam satu PR
- Tulis kode yang mudah dibaca dan maintainable

### 5. **Testing**

```bash
# Test manual di browser
# Pastikan tidak ada error di:
# - PHP error log
# - Browser console
# - Network tab (untuk AJAX)

# Checklist testing:
# ✅ Fitur baru berfungsi sesuai ekspektasi
# ✅ Tidak merusak fitur existing
# ✅ Validasi input berfungsi
# ✅ Keamanan (CSRF, SQL injection, XSS) terjaga
# ✅ Responsif di mobile
```

### 6. **Commit Changes**

Gunakan commit message yang jelas dan deskriptif:

```bash
# Format: <type>: <description>

git add .
git commit -m "feat: tambah export leger per kelas"
git commit -m "fix: perbaiki validasi nilai import di semester genap"
git commit -m "improve: optimasi query dashboard dengan eager loading"
git commit -m "docs: update README dengan panduan deployment"
```

**Commit Types:**
- `feat`: Fitur baru
- `fix`: Bug fix
- `improve`: Performance/UX improvement
- `refactor`: Code refactoring tanpa mengubah behavior
- `docs`: Update dokumentasi
- `style`: Perubahan formatting/styling
- `test`: Tambah atau update test
- `chore`: Maintenance task (update dependencies, dll)

### 7. **Push & Create Pull Request**

```bash
# Push ke fork Anda
git push origin feature/nama-fitur

# Buat Pull Request via GitHub UI
# Isi template PR dengan lengkap
```

---

## 📐 Standar Kode

### PHP Code Style

```php
// ✅ GOOD - Gunakan prepared statements
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE current_semester = ?");
$stmt->execute([$semester]);

// ✅ GOOD - Validasi input
if (!in_array($nilai, range(70, 100))) {
    $errors[] = "Nilai harus antara 70-100";
}

// ✅ GOOD - Use .env untuk konfigurasi
$dbHost = getenv('DB_HOST') ?: 'localhost';

// ❌ BAD - String concatenation di query
$query = "SELECT * FROM siswa WHERE nisn = '$nisn'";

// ❌ BAD - Hardcode credentials
$db = new PDO('mysql:host=localhost', 'root', 'password123');
```

### File Organization

```
app/views/pages/
├── dashboard.php          # ✅ One feature per file
├── siswa.php             # ✅ Related operations grouped
└── nilai_import.php      # ✅ Clear naming

app/helpers/
├── common.php            # ✅ Shared utility functions
└── validation.php        # ✅ (Jangan buat banyak helper file kecil)
```

### Database Queries

```php
// ✅ GOOD - Optimized dengan JOIN
$sql = "SELECT s.*, COUNT(n.id) as jumlah_nilai 
        FROM siswa s 
        LEFT JOIN nilai n ON s.id = n.siswa_id 
        GROUP BY s.id";

// ❌ BAD - N+1 Query Problem
foreach ($siswa as $s) {
    $nilai = $pdo->query("SELECT COUNT(*) FROM nilai WHERE siswa_id = {$s['id']}");
}
```

### Security Checklist

- [ ] Gunakan PDO prepared statements untuk semua query
- [ ] Validasi dan sanitasi input user
- [ ] Escape output dengan `htmlspecialchars()`
- [ ] Gunakan CSRF token di semua form
- [ ] Jangan expose error detail di production
- [ ] Hash password dengan `password_hash()`

---

## 🔄 Pull Request Checklist

Sebelum submit PR, pastikan:

### Code Quality
- [ ] Kode mengikuti style guide yang ada
- [ ] Tidak ada hardcoded credentials atau sensitive data
- [ ] Tidak ada commented code yang tidak perlu
- [ ] Variable naming jelas dan deskriptif
- [ ] Function/method tidak terlalu panjang (< 50 baris ideal)

### Functionality
- [ ] Perubahan sudah ditest manual
- [ ] Tidak merusak fitur existing
- [ ] Edge cases sudah dipertimbangkan
- [ ] Error handling ditambahkan

### Security
- [ ] Input validation diterapkan
- [ ] SQL injection prevention (prepared statements)
- [ ] XSS prevention (output escaping)
- [ ] CSRF token digunakan (jika form POST)

### Documentation
- [ ] Komentar kode untuk logika kompleks
- [ ] README.md diupdate (jika perlu)
- [ ] CHANGELOG.md diupdate (untuk fitur besar)

### PR Description
- [ ] Judul PR jelas dan deskriptif
- [ ] Deskripsi menjelaskan "apa" dan "mengapa"
- [ ] Screenshot/video untuk perubahan UI
- [ ] Mention issue terkait (jika ada)

---

## 🚫 Hal yang Harus Dihindari

### Perubahan Breaking

Hindari perubahan yang merusak kompatibilitas:

❌ Mengubah struktur database tanpa migration  
❌ Menghapus field yang masih digunakan  
❌ Mengubah flow semester control tanpa update helper  
❌ Mengubah format data ijazah JSON yang sudah ada  

### Scope Creep

Satu PR = Satu Fokus:

❌ "feat: tambah export leger + fix bug nilai + redesign login page"  
✅ "feat: tambah export leger per kelas"

### Large Files

❌ Upload folder `vendor/` ke git  
❌ Upload database backup (.sql besar) ke git  
❌ Upload file siswa/uploads ke git  
✅ Gunakan `.gitignore` untuk exclude file-file tersebut

---

## 💬 Code Review Process

Setelah submit PR:

1. **Automated Checks** (jika ada CI/CD)
   - Code syntax validation
   - Security scan

2. **Manual Review** oleh maintainer
   - Code quality
   - Security assessment
   - Functionality verification

3. **Feedback & Iteration**
   - Maintainer akan memberikan feedback
   - Lakukan perubahan jika diminta
   - Push update ke branch yang sama

4. **Merge**
   - Setelah approved, PR akan di-merge
   - Branch Anda akan otomatis deleted

---

## 🐛 Melaporkan Bug

Gunakan [Bug Report Template](.github/ISSUE_TEMPLATE/bug_report.md) di GitHub Issues.

**Informasi minimum:**
- Ringkasan bug
- Langkah reproduksi
- Expected vs actual behavior
- Screenshot/error log
- Environment (PHP version, browser, OS)

---

## ✨ Mengusulkan Fitur Baru

Gunakan [Feature Request Template](.github/ISSUE_TEMPLATE/feature_request.md) di GitHub Issues.

**Informasi minimum:**
- Deskripsi fitur
- Use case / masalah yang ingin diselesaikan
- Mockup/wireframe (jika ada)
- Dampak ke fitur existing

---

## 📞 Butuh Bantuan?

- **Email**: mtsn11majalengka@gmail.com
- **GitHub Issues**: Untuk pertanyaan teknis
- **Pull Request Comments**: Untuk diskusi perubahan kode

---

## 🎖️ Recognition

Kontributor yang berkontribusi signifikan akan:
- Dicantumkan di CHANGELOG.md
- Mendapat credit di release notes
- Dipertimbangkan untuk maintainer (kontribusi konsisten)

---

## 📜 Code of Conduct

Dengan berkontribusi, Anda setuju untuk mengikuti [Code of Conduct](CODE_OF_CONDUCT.md) kami.

---

**TRACER** — *Built with contributions from passionate developers*

Terima kasih telah membantu meningkatkan pendidikan di MTsN 11 Majalengka! 🙏
