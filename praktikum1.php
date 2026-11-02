<?php
// praktikum1.php - Praktikum 1: Menganalisis Evolusi dan Integrasi ERP
$pageTitle = 'Praktikum 1: Evolusi & Integrasi ERP';
require_once __DIR__ . '/config/database.php';
$pdo = getDbConnection();
$so = $pdo->query("SELECT * FROM sales_orders ORDER BY id DESC LIMIT 1")->fetch();
$wo = $pdo->query("SELECT * FROM production_orders ORDER BY id DESC LIMIT 1")->fetch();
$prs = $pdo->query("SELECT * FROM purchase_requisitions WHERE module='P1' ORDER BY id")->fetchAll();
$jr = $pdo->query("SELECT j.entry_no, j.description, d.account_code, d.debit, d.credit FROM journal_entries j JOIN journal_details d ON d.journal_id=j.id WHERE j.module='P1' ORDER BY j.id, d.id")->fetchAll();
$stk = []; foreach ($pdo->query("SELECT code, name, stock_qty FROM items WHERE code IN ('K-01','MAT-A','MAT-B') ORDER BY code") as $r) $stk[$r['code']] = $r;
$boms = $pdo->query("SELECT component_code, quantity FROM bom WHERE parent_code='K-01'")->fetchAll(PDO::FETCH_KEY_PAIR);
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-hero">
    <div class="page-hero-meta">
        <span class="badge badge-primary">Praktikum 1</span>
        <span class="badge">Indikator: Konsep dan evolusi sistem ERP</span>
    </div>
    <h1 class="page-hero-title">Menganalisis Evolusi dan Integrasi ERP</h1>
    <p class="page-hero-desc">Data bisnis perusahaan yang masih memakai aplikasi terpisah: identifikasi hubungan data, diagram sebelum &amp; sesudah ERP, data master bersama, dan simulasi transaksi pesanan pelanggan yang tersimpan di database.</p>
</div>

<div class="card">
    <div class="tab-pills">
        <button class="tab-pill-btn active" data-tab="tab-hubungan-data">1. Hubungan Data</button>
        <button class="tab-pill-btn" data-tab="tab-diagram-arsitektur">2. Diagram Sebelum &amp; Sesudah ERP</button>
        <button class="tab-pill-btn" data-tab="tab-data-master">3. Data Master Bersama</button>
        <button class="tab-pill-btn" data-tab="tab-simulasi-pesanan">4. Simulasi Transaksi</button>
        <button class="tab-pill-btn" data-tab="tab-tantangan-individual">5. Tantangan &amp; Masalah-Solusi</button>
    </div>

    <div id="tab-hubungan-data" class="tab-content-panel">
        <div class="alert alert-info"><strong>Data kondisi perusahaan (sesuai soal):</strong></div>
        <div class="table-responsive" style="margin-bottom:16px"><table class="table-custom">
            <thead><tr><th>Departemen</th><th>Sistem saat ini</th><th>Masalah</th></tr></thead>
            <tbody>
                <tr><td>Penjualan</td><td>Spreadsheet</td><td>Data pesanan terlambat diperbarui</td></tr>
                <tr><td>Gudang</td><td>Aplikasi stok</td><td>Stok berbeda dengan catatan pembelian</td></tr>
                <tr><td>Produksi</td><td>Formulir manual</td><td>Jadwal produksi sering berubah</td></tr>
                <tr><td>Keuangan</td><td>Aplikasi akuntansi</td><td>Transaksi harus dimasukkan ulang</td></tr>
            </tbody></table></div>
        <div class="alert alert-warning"><strong>1. Identifikasi hubungan data yang belum terintegrasi:</strong></div>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Jenis Data</th>
                        <th>Dibuat Di</th>
                        <th>Dibutuhkan Oleh</th>
                        <th>Format Saat Ini</th>
                        <th>Dampak Kegagalan Integrasi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Pesanan Pelanggan</strong></td>
                        <td>Departemen Penjualan</td>
                        <td>Gudang (Cek Stok), Produksi (Jadwal), Keuangan (Faktur)</td>
                        <td><span class="badge">Spreadsheet</span></td>
                        <td>Data pesanan lambat diperbarui; staf keuangan harus mengetik ulang; pengiriman berisiko tertunda.</td>
                    </tr>
                    <tr>
                        <td><strong>Stok Barang & Bahan</strong></td>
                        <td>Departemen Gudang</td>
                        <td>Penjualan (Janji Kirim), Produksi (Kebutuhan Bahan), Pembelian</td>
                        <td><span class="badge">Aplikasi Stok Mandiri</span></td>
                        <td>Jumlah fisik sering berbeda dengan catatan produksi; marketing menjanjikan barang yang belum siap.</td>
                    </tr>
                    <tr>
                        <td><strong>Kebutuhan Bahan & Jadwal</strong></td>
                        <td>Departemen Produksi</td>
                        <td>Gudang, Pembelian (Pengadaan), Penjualan</td>
                        <td><span class="badge badge-danger">Catatan Manual</span></td>
                        <td>Jadwal produksi berubah tanpa terlihat bagian lain; bahan baku berisiko habis di tengah jalan.</td>
                    </tr>
                    <tr>
                        <td><strong>Transaksi Keuangan</strong></td>
                        <td>Departemen Keuangan</td>
                        <td>Penjualan, Gudang, Pembelian, Direksi</td>
                        <td><span class="badge">Aplikasi Akuntansi Lokal</span></td>
                        <td>Transaksi diketik ulang di akhir bulan; penutupan buku terlambat dan rawan salah input.</td>
                    </tr>
                    <tr>
                        <td><strong>Master Pelanggan & Barang</strong></td>
                        <td>Masing-masing Departemen</td>
                        <td>Seluruh Departemen Perusahaan</td>
                        <td><span class="badge badge-warning">Desentralisasi / Ganda</span></td>
                        <td>Kode dan penamaan item berbeda antardepartemen; duplikasi data master memicu kerancuan transaksi.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="tab-diagram-arsitektur" class="tab-content-panel" style="display:none;">
        <div class="alert alert-danger"><strong>2. Diagram arsitektur proses SEBELUM ERP</strong> &mdash; empat sistem berdiri sendiri, data berpindah lewat salinan manual.</div>
        <?= diagramImg('p1_sebelum.png', 'Gambar 1. Arsitektur proses sebelum penerapan ERP (silo data)', 'Diagram arsitektur sebelum ERP: Penjualan, Gudang, Produksi, Keuangan terpisah') ?>
        <div class="alert alert-success"><strong>3. Diagram rancangan proses SETELAH ERP</strong> &mdash; semua modul memakai satu database dan data master yang sama.</div>
        <?= diagramImg('p1_sesudah.png', 'Gambar 2. Rancangan proses setelah penerapan ERP (database terpusat)', 'Diagram setelah ERP: modul terhubung ke database terpusat') ?>
        <?= diagramImg('p1_alur_pesanan.png', 'Gambar 3. Alur transaksi pesanan pelanggan di ERP (dasar simulasi tab 4)', 'Diagram alur pesanan pelanggan') ?>
    </div>

    <div id="tab-data-master" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>4. Data master yang dipakai bersama beberapa departemen:</strong> dikelola sekali, dipakai semua modul.</div>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Master Data</th>
                        <th>Departemen Pemilik</th>
                        <th>Departemen Pengguna</th>
                        <th>Atribut Kunci</th>
                        <th>Fungsi Integrasi ERP</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Master Pelanggan</strong></td>
                        <td>Penjualan</td>
                        <td>Penjualan, Gudang, Keuangan</td>
                        <td>Kode Cust, Nama, Plafon Kredit, Syarat Bayar</td>
                        <td>Memvalidasi batas kredit saat sales order dan sinkronisasi piutang otomatis.</td>
                    </tr>
                    <tr>
                        <td><strong>Master Barang & Material</strong></td>
                        <td>Gudang / Engineering</td>
                        <td>Seluruh Departemen</td>
                        <td>Kode Barang, Tipe (FG/RAW), Satuan, Lead Time</td>
                        <td>Satu sumber kebenaran stok fisik di gudang, WIP, dan barang jadi.</td>
                    </tr>
                    <tr>
                        <td><strong>Bill of Materials (BOM)</strong></td>
                        <td>Produksi</td>
                        <td>Produksi, Pembelian, Gudang, Akuntansi</td>
                        <td>Parent Item, Komponen Bahan, Qty per unit</td>
                        <td>Pondasi perhitungan otomatis kebutuhan material (MRP Engine).</td>
                    </tr>
                    <tr>
                        <td><strong>Master Pemasok (Vendor)</strong></td>
                        <td>Pembelian</td>
                        <td>Pembelian, Gudang, Keuangan</td>
                        <td>Kode Vendor, NPWP, Rekening, Syarat Bayar</td>
                        <td>Mencegah vendor fiktif, acuan PO, dan dasar pencatatan utang usaha.</td>
                    </tr>
                    <tr>
                        <td><strong>Chart of Accounts (CoA)</strong></td>
                        <td>Keuangan</td>
                        <td>Semua Modul Terkait Transaksi</td>
                        <td>Kode Akun, Tipe Akun, Saldo Normal</td>
                        <td>Pemetaan jurnal otomatis dari event operasional (GR, DO, Invoice).</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="tab-simulasi-pesanan" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>5. Simulasi satu transaksi pesanan pelanggan</strong> (contoh data: pelanggan PT Sinar Otomotif, produk K-01; BOM: <?= h(implode(', ', array_map(fn($k,$v)=>"$k &times; $v", array_keys($boms), $boms))) ?> per unit). Hasil disimpan di database.</div>
        <?php if (!$so): ?>
        <form action="api/p1_actions.php" method="POST" class="card" style="background:var(--bg-card-subtle)">
            <input type="hidden" name="action" value="run_order">
            <div class="form-inline">
                <div class="form-group"><label class="form-label">Jumlah pesanan K-01 (unit)</label><input type="number" name="qty" class="form-control font-mono" value="100" min="1"></div>
                <div class="form-group"><label class="form-label">Harga satuan (Rp)</label><input type="number" name="price" class="form-control font-mono" value="150000" min="1"></div>
                <button class="btn btn-primary" type="submit">Proses Pesanan</button>
            </div>
            <p style="font-size:.78rem;color:var(--text-muted);margin-top:8px">Stok awal: K-01 <?= num($stk['K-01']['stock_qty']) ?>, MAT-A <?= num($stk['MAT-A']['stock_qty']) ?>, MAT-B <?= num($stk['MAT-B']['stock_qty']) ?>. Sistem akan memeriksa stok, membuat Production Order, memicu Purchase Requisition bila bahan kurang, dan mem-posting jurnal piutang.</p>
        </form>
        <?php else: ?>
        <div class="table-responsive"><table class="table-custom">
            <thead><tr><th>Tahap</th><th>Modul</th><th>Hasil tersimpan di database</th></tr></thead><tbody>
            <tr><td>1. Pesanan</td><td>Penjualan</td><td><strong><?= h($so['so_no']) ?></strong> &mdash; <?= h($so['customer']) ?>, <?= num($so['qty']) ?> unit &times; <?= rp($so['unit_price']) ?> = <strong><?= rp($so['total']) ?></strong></td></tr>
            <tr><td>2. Produksi</td><td>Produksi</td><td><?= $wo ? h($wo['wo_no']) . ': produksi ' . num($wo['qty']) . ' unit (kekurangan stok barang jadi)' : 'Tidak perlu produksi (stok cukup)' ?></td></tr>
            <tr><td>3. Pengadaan</td><td>Pembelian</td><td><?php if ($prs) foreach ($prs as $p) echo h($p['pr_no']) . ': ' . h($p['item_code']) . ' ' . num($p['qty']) . ' unit<br>'; else echo 'Bahan cukup, tidak ada PR.'; ?></td></tr>
            <tr><td>4. Gudang</td><td>Stok</td><td><?php foreach ($stk as $s) echo h($s['code']) . ' &rarr; ' . num($s['stock_qty']) . ' unit<br>'; ?></td></tr>
            <tr><td>5. Keuangan</td><td>Jurnal</td><td><?php foreach ($jr as $j) echo ($j['debit'] > 0 ? 'Dr ' : '&nbsp;&nbsp;Cr ') . h($j['account_code']) . ' ' . rp($j['debit'] > 0 ? $j['debit'] : $j['credit']) . '<br>'; ?></td></tr>
            </tbody></table></div>
        <form action="api/p1_actions.php" method="POST" style="margin-top:12px"><input type="hidden" name="action" value="reset_p1"><button class="btn btn-outline btn-sm" type="submit">Reset simulasi (coba nilai lain)</button></form>
        <?php endif; ?>
    </div>

    <div id="tab-tantangan-individual" class="tab-content-panel" style="display:none;">
        <div class="alert alert-warning"><strong>Tantangan individual:</strong> bagian proses yang terdampak dan perubahan yang harus dilakukan pada sistem.</div>
        <div class="table-responsive"><table class="table-custom">
            <thead><tr><th>Kondisi dari dosen</th><th>Bagian proses terdampak</th><th>Perubahan pada sistem</th></tr></thead><tbody>
            <tr><td>1. Stok bahan baku ternyata tidak mencukupi</td><td>Gudang, Produksi, Pembelian, Penjualan (janji kirim)</td><td>Koreksi stok di master; MRP menghitung ulang kekurangan; terbit PR/PO; status Production Order &quot;menunggu material&quot;; tanggal janji kirim disesuaikan lead time pemasok.</td></tr>
            <tr><td>2. Pelanggan mengubah jumlah pesanan</td><td>Penjualan, Produksi, Gudang, Keuangan</td><td>Sales Order direvisi (versi baru, riwayat tetap); reservasi stok, Production Order, kebutuhan bahan, dan nilai piutang dihitung ulang otomatis.</td></tr>
            <tr><td>3. Pembayaran pelanggan belum diterima</td><td>Keuangan, Penjualan</td><td>Piutang tetap terbuka di laporan umur piutang; pesanan baru diblokir jika melewati plafon kredit; pengingat tagihan dikirim.</td></tr>
            <tr><td>4. Produksi mengalami keterlambatan</td><td>Produksi, Penjualan, Gudang</td><td>Estimasi selesai diperbarui, notifikasi ke Penjualan, opsi pengiriman bertahap (partial shipment), jadwal bahan disesuaikan.</td></tr>
            </tbody></table></div>
        <div class="card" style="background:var(--bg-card-subtle);margin-top:14px">
            <div style="font-weight:700;margin-bottom:6px">Penjelasan singkat: satu masalah yang ditemukan dan cara mengatasinya</div>
            <p style="font-size:.84rem;color:var(--text-muted);line-height:1.6"><strong>Masalah:</strong> stok di aplikasi gudang berbeda dengan catatan pembelian, sehingga Penjualan bisa menjanjikan barang yang sebenarnya belum ada dan Produksi kehabisan bahan di tengah jalan. <strong>Penyebab:</strong> stok dan pembelian disimpan di dua aplikasi berbeda dan disalin manual. <strong>Solusi:</strong> satu database ERP dengan data master barang tunggal; penerimaan barang (Goods Receipt) terhadap PO otomatis menambah stok, dan pemakaian produksi otomatis menguranginya, sehingga semua departemen melihat angka yang sama secara real-time.</p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
