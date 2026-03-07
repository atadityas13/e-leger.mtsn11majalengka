<?php
/**
 * ========================================================
 * E-Leger Alumni Test Data Migration Runner
 * ========================================================
 * Direct PHP approach - manual data insertion
 */

require_once __DIR__ . '/app/bootstrap.php';

echo "\n";
echo "=================================================\n";
echo "E-LEGER ALUMNI TEST DATA MIGRATION\n";
echo "=================================================\n\n";

try {
    $nisn = '1234567890123';
    
    // 1. Delete existing data
    echo "[1/5] Cleaning up old data...\n";
    db()->prepare("DELETE FROM alumni WHERE nisn = ?")->execute([$nisn]);
    db()->prepare("DELETE FROM nilai_uam WHERE nisn = ?")->execute([$nisn]);
    db()->prepare("DELETE FROM nilai_rapor WHERE nisn = ?")->execute([$nisn]);
    db()->prepare("DELETE FROM siswa WHERE nisn = ?")->execute([$nisn]);
    echo "  ✓ Cleanup ok\n";
    
    // 2. Insert Siswa
    echo "[2/5] Creating student data...\n";
    db()->prepare("
        INSERT INTO siswa (
            nisn, nis, nama, tempat_lahir, tgl_lahir, 
            kelas, nomor_absen, current_semester, tahun_masuk, 
            status_siswa, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ")->execute([
        $nisn, '2000123', 'Muhammad Rizki Al-Azhari', 'Majalengka',
        '2009-06-15', 'IX', 1, 6, '2022/2023', 'Lulus'
    ]);
    echo "  ✓ Student created\n";
    
    // 3. Get mapel list
    $mapelStmt = db()->query("SELECT id FROM mapel ORDER BY id");
    $mapelIds = $mapelStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    if (empty($mapelIds)) {
        throw new Exception("Tidak ada mata pelajaran di database!");
    }
    
    echo "[3/5] Creating rapor values (" . count($mapelIds) . " subjects × 5 semesters)...\n";
    
    // 3. Insert Nilai Rapor
    $stmtRapor = db()->prepare("
        INSERT INTO nilai_rapor (nisn, mapel_id, semester, tahun_ajaran, nilai_angka, is_finalized)
        VALUES (?, ?, ?, '2025/2026', ?, 1)
    ");
    
    $rapidCount = 0;
    for ($sem = 1; $sem <= 5; $sem++) {
        foreach ($mapelIds as $mapelId) {
            $nilai = round(75 + (rand() % 20), 2);
            $stmtRapor->execute([$nisn, $mapelId, $sem, $nilai]);
            $rapidCount++;
        }
    }
    echo "  ✓ Created " . $rapidCount . " rapor values\n";
    
    // 4. Insert Nilai UAM
    echo "[4/5] Creating UAM values...\n";
    $stmtUam = db()->prepare("
        INSERT INTO nilai_uam (nisn, mapel_id, nilai_angka)
        VALUES (?, ?, ?)
    ");
    
    $uamCount = 0;
    foreach ($mapelIds as $mapelId) {
        $nilai = round(76 + (rand() % 18), 2);
        $stmtUam->execute([$nisn, $mapelId, $nilai]);
        $uamCount++;
    }
    echo "  ✓ Created " . $uamCount . " UAM values\n";
    
    // 5. Insert Alumni
    echo "[5/5] Creating alumni record...\n";
    db()->prepare("
        INSERT INTO alumni (
            nisn, nama, angkatan_lulus, tanggal_kelulusan, 
            nomor_surat, verification_token, data_ijazah_json, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ")->execute([
        $nisn,
        'Muhammad Rizki Al-Azhari',
        2026,
        date('Y-m-d'),
        '       /Mts.10.89/PP.00.5/' . date('m/Y'),
        substr(hash('sha256', $nisn . time()), 0, 32),
        '[]'
    ]);
    echo "  ✓ Alumni record created\n";
    
    // Verify
    echo "\n=================================================\n";
    echo "✓ SUCCESS!\n";
    echo "=================================================\n\n";
    
    $checks = [
        ['Siswa', "SELECT COUNT(*) FROM siswa WHERE nisn = ?"],
        ['Nilai Rapor', "SELECT COUNT(*) FROM nilai_rapor WHERE nisn = ?"],
        ['Nilai UAM', "SELECT COUNT(*) FROM nilai_uam WHERE nisn = ?"],
        ['Alumni', "SELECT COUNT(*) FROM alumni WHERE nisn = ?"]
    ];
    
    echo "📊 Data Status:\n";
    foreach ($checks as [$label, $sql]) {
        $stmt = db()->prepare($sql);
        $stmt->execute([$nisn]);
        $count = $stmt->fetchColumn();
        echo "  ✓ " . str_pad($label, 15) . ": " . $count . "\n";
    }
    
    echo "\n📋 Student Info:\n";
    echo "  NISN: " . $nisn . "\n";
    echo "  Nama: Muhammad Rizki Al-Azhari\n";
    echo "  Status: Alumni / Lulus\n";
    echo "  Angkatan: 2026\n";
    
    echo "\n🎯 Next: Login e-leger → Data Alumni → Search → Cetak Leger\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getTraceAsString')) {
        echo "\nTrace: " . $e->getTraceAsString() . "\n";
    }
    exit(1);
}

echo "\n=================================================\n\n";
?>
