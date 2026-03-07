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
    
    // 5. Insert Alumni (temp with empty ijazah data)
    echo "[5/6] Creating alumni record...\n";
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
    
    // 6. Generate data_ijazah_json with rata-rata, nilai_uam, nilai_ijazah, terbilang
    echo "[6/6] Generating ijazah data...\n";
    
    $stmtAllMapel = db()->prepare("SELECT id, nama_mapel FROM mapel ORDER BY id");
    $stmtAllMapel->execute();
    $allMapel = $stmtAllMapel->fetchAll();
    
    $ijazahDetail = [];
    foreach ($allMapel as $m) {
        // Hitung rata-rata nilai rapor semester 1-5
        $stR = db()->prepare("
            SELECT AVG(nilai_angka) as rata_rapor 
            FROM nilai_rapor 
            WHERE nisn = ? AND mapel_id = ? AND semester BETWEEN 1 AND 5
        ");
        $stR->execute([$nisn, $m['id']]);
        $rataRapor = (float) ($stR->fetch()['rata_rapor'] ?? 0);
        
        // Ambil nilai UAM
        $stU = db()->prepare("
            SELECT nilai_angka 
            FROM nilai_uam 
            WHERE nisn = ? AND mapel_id = ? 
            LIMIT 1
        ");
        $stU->execute([$nisn, $m['id']]);
        $nilaiUam = (float) ($stU->fetch()['nilai_angka'] ?? 0);
        
        // Hitung nilai ijazah: 60% rapor + 40% UAM
        $nilaiIjazah = hitung_nilai_ijazah($rataRapor, $nilaiUam);
        
        // Convert to terbilang (Indonesian text representation)
        $terbilangText = terbilang_nilai($nilaiIjazah);
        
        $ijazahDetail[] = [
            'mapel_id' => (int)$m['id'],
            'mapel' => $m['nama_mapel'],
            'rata_rapor' => (int)round($rataRapor),
            'nilai_uam' => (int)round($nilaiUam),
            'nilai_ijazah' => (int)round($nilaiIjazah),
            'terbilang' => $terbilangText
        ];
    }
    
    // Update alumni dengan data ijazah JSON
    $stmtUpdate = db()->prepare("UPDATE alumni SET data_ijazah_json = ? WHERE nisn = ?");
    $stmtUpdate->execute([json_encode($ijazahDetail), $nisn]);
    echo "  ✓ Generated " . count($ijazahDetail) . " ijazah items\n";
    
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
