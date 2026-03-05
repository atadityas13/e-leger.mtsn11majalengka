<?php
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf('laporan');

    $action = $_POST['action'] ?? '';

    if ($action === 'leger') {
        if (!class_exists(Spreadsheet::class)) {
            set_flash('error', 'PhpSpreadsheet belum terpasang.');
            redirect('index.php?page=laporan');
        }

        $semester = (int) ($_POST['semester'] ?? 1);
        $tahunAjaran = trim($_POST['tahun_ajaran'] ?? '');

        $stmt = db()->prepare("SELECT s.nisn, s.nama, m.nama_mapel, nr.nilai_angka, nr.is_finalized
                               FROM nilai_rapor nr
                               JOIN siswa s ON s.nisn=nr.nisn
                               JOIN mapel m ON m.id=nr.mapel_id
                               WHERE nr.semester=:semester AND nr.tahun_ajaran=:ta
                               ORDER BY s.nama, m.nama_mapel");
        $stmt->execute(['semester' => $semester, 'ta' => $tahunAjaran]);
        $rows = $stmt->fetchAll();

        $sheet = new Spreadsheet();
        $active = $sheet->getActiveSheet();
        $active->fromArray(['NISN', 'Nama', 'Mapel', 'Nilai', 'Finalized'], null, 'A1');

        $line = 2;
        foreach ($rows as $r) {
            $active->fromArray([
                $r['nisn'],
                $r['nama'],
                $r['nama_mapel'],
                (float) $r['nilai_angka'],
                (int) $r['is_finalized'] === 1 ? 'Ya' : 'Belum',
            ], null, 'A' . $line);
            $line++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="leger_sem' . $semester . '.xlsx"');
        $writer = new Xlsx($sheet);
        $writer->save('php://output');
        exit;
    }

    if ($action === 'transkrip') {
        if (!class_exists(Dompdf::class)) {
            set_flash('error', 'Dompdf belum terpasang.');
            redirect('index.php?page=laporan');
        }

        $nisn = trim($_POST['nisn'] ?? '');
        $stmt = db()->prepare('SELECT a.nisn, a.angkatan_lulus, a.data_ijazah_json, s.nama FROM alumni a LEFT JOIN siswa s ON s.nisn=a.nisn WHERE a.nisn=:nisn LIMIT 1');
        $stmt->execute(['nisn' => $nisn]);
        $alumni = $stmt->fetch();

        if (!$alumni) {
            set_flash('error', 'Data alumni tidak ditemukan.');
            redirect('index.php?page=laporan');
        }

        $detail = json_decode($alumni['data_ijazah_json'], true) ?: [];
        $rows = '';
        foreach ($detail as $d) {
            $rows .= '<tr>'
                . '<td>' . e($d['mapel']) . '</td>'
                . '<td>' . e((string) $d['rata_rapor']) . '</td>'
                . '<td>' . e((string) $d['nilai_uam']) . '</td>'
                . '<td>' . e((string) $d['nilai_ijazah']) . '</td>'
                . '<td>' . e($d['terbilang']) . '</td>'
                . '</tr>';
        }

        $html = '<h2>Transkrip Nilai Ijazah</h2>'
            . '<p>NISN: ' . e($alumni['nisn']) . '</p>'
            . '<p>Angkatan: ' . e((string)$alumni['angkatan_lulus']) . '</p>'
            . '<table border="1" cellspacing="0" cellpadding="6" width="100%">'
            . '<thead><tr><th>Mapel</th><th>Rata Rapor</th><th>UAM</th><th>Nilai Ijazah</th><th>Terbilang</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('transkrip_' . $nisn . '.pdf', ['Attachment' => true]);
        exit;
    }
}

$setting = setting_akademik();
$alumniList = db()->query('SELECT nisn, angkatan_lulus FROM alumni ORDER BY angkatan_lulus DESC, nisn')->fetchAll();

require dirname(__DIR__) . '/partials/header.php';
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3">
        <h3 class="mb-0">Cetak Leger Kolektif (Excel)</h3>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3 align-items-end">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="leger">
            <div class="col-md-5">
                <label class="form-label">Tahun Ajaran</label>
                <input type="text" class="form-control" name="tahun_ajaran" value="<?= e($setting['tahun_ajaran']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Semester</label>
                <select name="semester" class="form-select">
                    <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100">Download Excel</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <h3 class="mb-0">Cetak Transkrip Ijazah (PDF)</h3>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3 align-items-end">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="transkrip">
            <div class="col-md-8">
                <label class="form-label">Pilih Alumni (NISN)</label>
                <select name="nisn" class="form-select" required>
                    <option value="">-- pilih alumni --</option>
                    <?php foreach ($alumniList as $a): ?>
                        <option value="<?= e($a['nisn']) ?>"><?= e($a['nisn']) ?> - Angkatan <?= e((string)$a['angkatan_lulus']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Download PDF</button>
            </div>
        </form>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php';
