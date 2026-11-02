<?php
// index.php - Dashboard Utama Simulasi Praktikum ERP TRPL
$pageTitle = 'Dashboard Simulasi Praktikum ERP';
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();

// Fetch summary stats
$totalAkun = $pdo->query("SELECT COUNT(*) FROM coa")->fetchColumn();
$totalAudit = $pdo->query("SELECT COUNT(*) FROM security_audit_logs")->fetchColumn();
$stokA = $pdo->query("SELECT stock_qty FROM items WHERE code = 'RAW-A'")->fetchColumn() ?: 0;
$stokB = $pdo->query("SELECT stock_qty FROM items WHERE code = 'RAW-B'")->fetchColumn() ?: 0;

// Saldo kas (Praktikum 4) dari jurnal modul P4
$kasRow = $pdo->query("SELECT COALESCE(SUM(d.debit),0) dr, COALESCE(SUM(d.credit),0) cr FROM journal_details d JOIN journal_entries j ON j.id=d.journal_id WHERE d.account_code='1-1000' AND j.module='P4'")->fetch();
$saldoKas = (float)$pdo->query("SELECT initial_balance FROM coa WHERE code='1-1000'")->fetchColumn() + $kasRow['dr'] - $kasRow['cr'];
$totalTrx = $pdo->query("SELECT COUNT(*) FROM journal_entries WHERE module='P4'")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-hero">
    <div class="page-hero-meta">
        <span class="badge">UTS Semester Ganjil 2026/2027</span>
        <span class="badge">ERP / PL / 4008</span>
        <span class="badge">Dosen: Adie Kusna Wibowo</span>
    </div>
    <h1 class="page-hero-title">Platform Simulasi Praktikum ERP</h1>
    <p class="page-hero-desc">
        Sistem simulasi proses bisnis untuk membuktikan hasil konfigurasi modul keuangan, perencanaan kebutuhan material (MRP), kontrol akses peran (RBAC), dan alur persetujuan berbasis basis data relasional.
    </p>
</div>

<!-- Stats Bar -->
<div class="grid-4" style="margin-bottom: 24px;">
    <div class="stat-box">
        <span class="stat-label">Saldo Kas (1-1000)</span>
        <span class="stat-value">Rp <?= number_format($saldoKas, 0, ',', '.') ?></span>
        <span class="stat-desc">Kas / Bank dari buku besar</span>
    </div>
    <div class="stat-box">
        <span class="stat-label">Total Entri Jurnal</span>
        <span class="stat-value"><?= $totalTrx ?> Entri</span>
        <span class="stat-desc">Termasuk jejak audit trail transaksi</span>
    </div>
    <div class="stat-box">
        <span class="stat-label">Persediaan Bahan Baku</span>
        <span class="stat-value"><?= $stokA ?> A / <?= $stokB ?> B</span>
        <span class="stat-desc">Stok fisik gudang PT Maju Presisi</span>
    </div>
    <div class="stat-box">
        <span class="stat-label">Log Keamanan RBAC</span>
        <span class="stat-value"><?= $totalAudit ?> Log</span>
        <span class="stat-desc">Riwayat verifikasi hak akses</span>
    </div>
</div>

<!-- 5 Modul Praktikum Cards -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span>Daftar Modul Praktikum</span>
        </div>
        <span class="badge">Bobot Praktikum 50%</span>
    </div>

    <div class="grid-3">
        <!-- P1 -->
        <div class="card card-interactive" style="margin-bottom: 0; background: var(--bg-card-subtle);">
            <div style="display:flex; justify-content:space-between; margin-bottom: 12px;">
                <span class="badge badge-primary">Praktikum 1</span>
                <span class="badge badge-success">● Tersedia</span>
            </div>
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-main);">Evolusi & Integrasi ERP</h3>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px; min-height: 48px; line-height: 1.5;">
                Analisis data silo, arsitektur sebelum vs sesudah ERP, konsolidasi data master, dan simulasi pesanan PT Sinar Otomotif.
            </p>
            <a href="praktikum1.php" class="btn btn-module">
                <span>Buka Praktikum 1</span>
                <span class="btn-arrow">&rarr;</span>
            </a>
        </div>

        <!-- P2 -->
        <div class="card card-interactive" style="margin-bottom: 0; background: var(--bg-card-subtle);">
            <div style="display:flex; justify-content:space-between; margin-bottom: 12px;">
                <span class="badge badge-primary">Praktikum 2</span>
                <span class="badge badge-success">● Tersedia</span>
            </div>
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-main);">Arsitektur Teknis & RBAC</h3>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px; min-height: 48px; line-height: 1.5;">
                Arsitektur 3-tier 100 pengguna, matriks hak akses 5 departemen, strategi backup 3-2-1, dan simulator uji coba pelanggaran izin akses.
            </p>
            <a href="praktikum2.php" class="btn btn-module">
                <span>Buka Praktikum 2</span>
                <span class="btn-arrow">&rarr;</span>
            </a>
        </div>

        <!-- P3 -->
        <div class="card card-interactive" style="margin-bottom: 0; background: var(--bg-card-subtle);">
            <div style="display:flex; justify-content:space-between; margin-bottom: 12px;">
                <span class="badge badge-primary">Praktikum 3</span>
                <span class="badge badge-success">● Tersedia</span>
            </div>
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-main);">Proses Bisnis Pengadaan Lab</h3>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px; min-height: 48px; line-height: 1.5;">
                Pengadaan lab manufaktur, diagram alur kerja, 6 aturan bisnis, protokol darurat 24 jam, dan kalkulator toleransi kenaikan harga vendor.
            </p>
            <a href="praktikum3.php" class="btn btn-module">
                <span>Buka Praktikum 3</span>
                <span class="btn-arrow">&rarr;</span>
            </a>
        </div>

        <!-- P4 -->
        <div class="card card-interactive" style="margin-bottom: 0; background: var(--bg-card-subtle);">
            <div style="display:flex; justify-content:space-between; margin-bottom: 12px;">
                <span class="badge badge-primary">Praktikum 4</span>
                <span class="badge badge-success">● Tersedia</span>
            </div>
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-main);">Modul Keuangan & Audit Koreksi</h3>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px; min-height: 48px; line-height: 1.5;">
                Chart of Accounts, input 5 transaksi UTS, Buku Besar, Neraca Saldo Seimbang (Rp 47 Juta), serta pengujian kesalahan duplikat dan jurnal pembalik.
            </p>
            <a href="praktikum4.php" class="btn btn-module">
                <span>Buka Praktikum 4</span>
                <span class="btn-arrow">&rarr;</span>
            </a>
        </div>

        <!-- P5 -->
        <div class="card card-interactive" style="margin-bottom: 0; background: var(--bg-card-subtle);">
            <div style="display:flex; justify-content:space-between; margin-bottom: 12px;">
                <span class="badge badge-primary">Praktikum 5</span>
                <span class="badge badge-success">● Tersedia</span>
            </div>
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-main);">SCM & Kebutuhan Material (MRP)</h3>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px; min-height: 48px; line-height: 1.5;">
                PT Maju Presisi: BOM explosion 300 unit produk, kalkulasi kebutuhan bersih, penerimaan parsial 200 unit Bahan B, dan matriks risiko keterlambatan vendor.
            </p>
            <a href="praktikum5.php" class="btn btn-module">
                <span>Buka Praktikum 5</span>
                <span class="btn-arrow">&rarr;</span>
            </a>
        </div>

        <!-- Pedoman Penilaian -->
        <div class="card" style="margin-bottom: 0; background: var(--bg-card-subtle);">
            <div style="display:flex; justify-content:space-between; margin-bottom: 10px;">
                <span class="badge">Pedoman</span>
                <span class="badge">Rubrik Ujian</span>
            </div>
            <h3 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 6px;">Kriteria Penilaian Praktikum</h3>
            <ul style="font-size: 0.78rem; color: var(--text-muted); padding-left: 16px; line-height: 1.6;">
                <li>Ketepatan konfigurasi & rancangan (25%)</li>
                <li>Ketepatan perhitungan / transaksi (25%)</li>
                <li>Keberhasilan pengujian skenario (20%)</li>
                <li>Kemampuan analisis kesalahan (15%)</li>
                <li>Kemampuan pertanggungjawaban hasil (15%)</li>
            </ul>
        </div>
    </div>
</div>

<!-- Architecture Summary -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span>Arsitektur Sistem Terintegrasi</span>
        </div>
        <span class="badge">Single Source of Truth</span>
    </div>

    <div class="flow-container">
        <div class="flow-node">
            <span class="flow-step-num">LAPISAN PENGGUNA</span>
            <h4>Klien & Browser</h4>
            <p>100 pengguna via LAN kantor dan akses jarak jauh via VPN terenkripsi.</p>
        </div>
        <div class="flow-node highlight">
            <span class="flow-step-num">LAPISAN APLIKASI</span>
            <h4>Mesin Logika ERP</h4>
            <p>Modul Keuangan, SCM/MRP, Pengadaan, dan pengontrol hak akses RBAC.</p>
        </div>
        <div class="flow-node success">
            <span class="flow-step-num">LAPISAN DATA</span>
            <h4>Basis Data Terpusat</h4>
            <p>Single source of truth (CoA, Jurnal Umum, Master Data, Audit Trail).</p>
        </div>
        <div class="flow-node">
            <span class="flow-step-num">KETAHANAN SISTEM</span>
            <h4>Pencadangan 3-2-1</h4>
            <p>3 salinan data, 2 media berbeda, 1 salinan terenkripsi di luar lokasi.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
