# Changelog

Semua perubahan penting pada project **TRACER MTsN 11 Majalengka** didokumentasikan di file ini.

Format mengikuti prinsip [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [Unreleased]

### Planned
- Export leger per kelas
- Notifikasi deadline upload nilai
- Grafik progres akademik siswa
- Backup otomatis database

---

## [1.1.0] - 2026-03-15

### Added
- **QR Code Verification System**: Setiap transkrip dilengkapi QR code unik untuk verifikasi keaslian dokumen
- **HTTP Security Headers**: Tambah perlindungan berlapis (X-Frame-Options, CSP, dll) di halaman`verify.php`
- **Professional Error Pages**: Halaman error 400/404 dengan desain konsisten untuk token tidak valid
- **Logo TRACER**: Implementasi logo emerald gradient di login (160px) dan sidebar (140px)
- **Frosted Glass Sidebar**: Efek frosted glass pada brand wrap sidebar dengan hover animation
- **.gitattributes**: Export control untuk file markdown (tidak terdeploy ke hosting)

### Changed
- **Rebranding**: Nama aplikasi dari "e-Leger" menjadi "TRACER MTsN 11 Majalengka"
  - Tagline: "Tracing Progress, Graduating Success."
  - Full name: Transkrip & Academic Ledger
- **Login Page**: Logo-centric design dengan subtitle & tagline (hapus judul teks)
- **Sidebar Branding**: Logo-only design (hapus teks "TRACER" dan "MTsN 11 Majalengka")
- **Color Scheme**: Konsisten gunakan emerald (#064e3b → #10b981) di semua halaman
- **Version Display**: Update footer "e-Leger v1.0.0" → "TRACER v1.0.0"

### Improved
- **PDF Generation Performance**: 
  - Batch query processing untuk multiple students
  - QR code caching untuk mengurangi API calls
  - Transaction wrapping untuk konsistensi data
- **Dokumentasi**: Update semua file .md dengan branding baru dan fitur lengkap
- **Error Handling**: Pesan error lebih informatif dengan styling profesional

### Security
- Implementasi Content Security Policy (CSP) di verify.php
- Tambah Referrer-Policy: no-referrer
- Tambah Permissions-Policy untuk kontrol API browser

---

## [1.0.0] - 2026-03-06

### Added
- **Sistem Autentikasi**: Login role-based untuk `admin` (Super Admin) dan `kurikulum`
- **Dashboard**: Statistik siswa real-time + monitoring status upload nilai per mata pelajaran
- **Master Data Management**:
  - Kelola users dengan role hierarchy
  - CRUD mata pelajaran
  - CRUD siswa dengan field `status_melanjutkan`, `current_semester`
- **Import Nilai Excel**: 
  - Upload massal berbasis NISN menggunakan PhpSpreadsheet
  - Auto-detect semester aktif (GANJIL/GENAP)
  - Validasi nilai rentang 70-100
  - Import UAM untuk semester 5
- **Kontrol Semester**:
  - Set tahun ajaran aktif
  - Finalisasi semester (lock nilai + auto-increment `current_semester`)
  - Normalisasi semester untuk cegah duplikasi
- **Sistem Kelulusan**:
  - Migrasi siswa eligible ke tabel alumni
  - Perhitungan nilai ijazah (60% rapor + 40% UAM)
  - Konversi angka ke terbilang
  - Simpan data ijazah dalam format JSON
- **Laporan**:
  - Export leger kolektif ke Excel
  - Generate transkrip nilai PDF menggunakan Dompdf
  - Header resmi Kemenag di PDF
- **Database Tools**:
  - Truncate table untuk development
  - Backup database
  - Restore database
- **Security Features**:
  - PDO prepared statements untuk semua query
  - Password hashing dengan BCRYPT
  - CSRF token protection
  - Input validation
  - Unique constraints (NISN, NIS)

### Database Schema
- Tabel: `users`, `mapel`, `siswa`, `nilai`, `alumni`, `semester_control`
- Seed data: akun superadmin default
- Migrations: `001_add_alumni_fields.sql`

### Documentation
- README.md dengan panduan instalasi lengkap
- CONTRIBUTING.md untuk kontributor
- SECURITY.md untuk pelaporan vulnerability  
- CODE_OF_CONDUCT.md untuk kode etik
- LICENSE.md (Proprietary)
- GitHub issue templates (bug report, feature request)
- Pull request template

---

## Notes

### Version Numbering
Format: `MAJOR.MINOR.PATCH`
- **MAJOR**: Perubahan breaking/inkompatibel
- **MINOR**: Tambahan fitur backward-compatible
- **PATCH**: Bug fixes backward-compatible

### Categories
- **Added**: Fitur baru
- **Changed**: Perubahan pada fitur existing
- **Deprecated**: Fitur yang akan dihapus
- **Removed**: Fitur yang dihapus
- **Fixed**: Bug fixes
- **Security**: Perbaikan keamanan
- **Improved**: Peningkatan performa/UX

---

**TRACER** — *Tracing Progress, Graduating Success.*
