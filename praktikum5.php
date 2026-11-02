<?php
// praktikum5.php - Praktikum 5: Konfigurasi SCM dan Pembelian Berbasis Kebutuhan Material (MRP dari database)
$pageTitle = 'Praktikum 5: SCM & MRP';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/mrp.php';
$pdo = getDbConnection();
$m = mrpCompute($pdo); $sc = $m['sc']; $A = $m['materials']['RAW-A']; $B = $m['materials']['RAW-B'];
$items = $pdo->query("SELECT * FROM items WHERE code IN ('P-01','RAW-A','RAW-B') ORDER BY code")->fetchAll();
$bom = $pdo->query("SELECT * FROM bom WHERE parent_code='P-01' ORDER BY id")->fetchAll();
$prs = $pdo->query("SELECT * FROM purchase_requisitions WHERE module='P5' ORDER BY id")->fetchAll();
$pos = $pdo->query("SELECT * FROM purchase_orders ORDER BY id")->fetchAll();
$stock = []; foreach ($items as $i) $stock[$i['code']] = (int)$i['stock_qty'];
$jr = $pdo->query("SELECT j.entry_no, j.description, d.account_code, d.debit, d.credit FROM journal_entries j JOIN journal_details d ON d.journal_id=j.id WHERE j.module='P5' ORDER BY j.id, d.id")->fetchAll();
$hasPR = !empty($prs);
$deadline = $m['deadline'];
require_once __DIR__ . '/includes/header.php';
function lateBadge($x, $dl) { return $x['net'] <= 0 ? '<span class="badge badge-success">Tidak perlu beli</span>' : ($x['lead'] <= $dl ? '<span class="badge badge-success">Tepat waktu (hari ke-' . $x['lead'] . ')</span>' : '<span class="badge badge-danger">Terlambat ' . ($x['lead'] - $dl) . ' hari (hari ke-' . $x['lead'] . ')</span>'); }
?>
<div class="page-hero">
    <div class="page-hero-meta"><span class="badge badge-primary">Praktikum 5</span><span class="badge">Indikator: Konfigurasi modul SCM dan pembelian</span></div>
    <h1 class="page-hero-title">SCM &amp; Pembelian Berbasis Kebutuhan Material</h1>
    <p class="page-hero-desc">Produksi <?= num($sc['target_qty']) ?> unit produk dalam <?= $deadline ?> hari kerja. Setiap unit butuh <?= $A['per'] ?> unit Bahan A dan <?= $B['per'] ?> unit Bahan B. Data master, perhitungan kebutuhan, PR/PO, penerimaan barang, dan jurnal tersimpan di database.</p>
</div>
<div class="card">
    <div class="tab-pills">
        <button class="tab-pill-btn active" data-tab="tab-data-master-bom">Tahap 1. Data Master &amp; BOM</button>
        <button class="tab-pill-btn" data-tab="tab-mrp-kalkulasi">Tahap 2. Kebutuhan Bruto &amp; Bersih</button>
        <button class="tab-pill-btn" data-tab="tab-simulasi-pr-po">Tahap 3. Simulasi Pembelian</button>
        <button class="tab-pill-btn" data-tab="tab-penerimaan-parsial">Tahap 4. Penerimaan Barang</button>
        <button class="tab-pill-btn" data-tab="tab-krisis-keterlambatan">Tahap 5. Perubahan Kondisi</button>
    </div>

    <div id="tab-data-master-bom" class="tab-content-panel">
        <div class="alert alert-info"><strong>Data master:</strong> produk jadi, bahan baku, BOM, gudang, pemasok, waktu pengadaan, satuan, dan aturan pemesanan.</div>
        <div class="table-responsive" style="margin-bottom:14px"><table class="table-custom">
            <thead><tr><th>Kode</th><th>Nama</th><th>Jenis</th><th>Gudang</th><th class="text-right">Stok fisik</th><th>Satuan</th><th>Waktu pengadaan</th><th>Pemasok</th></tr></thead><tbody>
            <?php foreach ($items as $i): ?><tr><td><span class="badge font-mono"><?= h($i['code']) ?></span></td><td><?= h($i['name']) ?></td><td><?= $i['type'] === 'FG' ? 'Produk jadi' : 'Bahan baku' ?></td><td>Gudang Utama</td><td class="font-mono text-right"><?= num($i['stock_qty']) ?></td><td><?= h($i['unit']) ?></td><td><?= $i['type'] === 'FG' ? '-' : (int)$i['lead_time_days'] . ' hari kerja' ?></td><td><?= h($i['vendor'] ?: '-') ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <div style="font-weight:700;margin-bottom:6px">Bill of Materials (BOM) P-01</div>
        <div class="table-responsive" style="margin-bottom:14px"><table class="table-custom"><thead><tr><th>Produk</th><th>Komponen</th><th class="text-right">Jumlah per unit</th></tr></thead><tbody>
            <?php foreach ($bom as $b): ?><tr><td>P-01</td><td><?= h($b['component_code']) ?></td><td class="font-mono text-right"><?= (int)$b['quantity'] ?></td></tr><?php endforeach; ?></tbody></table></div>
        <div class="alert alert-info"><strong>Aturan pemesanan:</strong> PR diterbitkan hanya untuk kebutuhan bersih &gt; 0; PO mengikuti pemasok utama bahan; target tiba = hari ini + waktu pengadaan.</div>
    </div>

    <div id="tab-mrp-kalkulasi" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>Kebutuhan bersih = (target &times; BOM) &minus; stok tersedia &minus; pesanan belum diterima.</strong> Ubah parameter untuk menguji kondisi lain; hasil dihitung ulang dari database.</div>
        <div class="table-responsive" style="margin-bottom:14px"><table class="table-custom">
            <thead><tr><th>Bahan</th><th class="text-right">BOM/unit</th><th class="text-right">Kebutuhan bruto</th><th class="text-right">Stok</th><th class="text-right">Pesanan belum diterima</th><th class="text-right">Kebutuhan bersih</th><th>Waktu pengadaan</th><th>Status vs <?= $deadline ?> hari</th></tr></thead><tbody>
            <?php foreach ([$A, $B] as $x): ?>
            <tr><td><strong><?= h($x['code']) ?></strong></td><td class="font-mono text-right"><?= $x['per'] ?></td><td class="font-mono text-right"><?= num($sc['target_qty']) ?> &times; <?= $x['per'] ?> = <strong><?= num($x['gross']) ?></strong></td><td class="font-mono text-right"><?= num($x['stock']) ?></td><td class="font-mono text-right"><?= num($x['onorder']) ?></td><td class="font-mono text-right" style="font-weight:700"><?= num($x['net']) ?> unit</td><td class="font-mono"><?= $x['lead'] ?> hari</td><td><?= lateBadge($x, $deadline) ?></td></tr>
            <?php endforeach; ?></tbody></table></div>
        <div class="chart-box"><?= barChartSvg('Kebutuhan bruto vs bersih (unit)', [$A['code'], $B['code']], [['name'=>'Kebutuhan bruto','color'=>'#6366f1','values'=>[$A['gross'],$B['gross']]],['name'=>'Stok','color'=>'#10b981','values'=>[$A['stock'],$B['stock']]],['name'=>'Kebutuhan bersih','color'=>'#ef4444','values'=>[$A['net'],$B['net']]]]) ?></div>
        <div class="chart-box"><?= barChartSvg('Waktu pengadaan vs tenggat (hari kerja)', ['Bahan A','Bahan B','Tenggat produksi'], [['name'=>'Hari','color'=>'#f59e0b','values'=>[$A['lead'],$B['lead'],$deadline]]]) ?></div>
        <div class="card" style="background:var(--bg-card-subtle)">
            <div style="font-weight:700;margin-bottom:6px">Asumsi yang dipakai</div>
            <ul style="font-size:.82rem;color:var(--text-muted);line-height:1.7;padding-left:16px">
                <li>Pesanan Bahan A yang belum diterima (<?= num($A['onorder']) ?> unit) <strong>dipakai</strong> sebagai pengurang kebutuhan karena dianggap tiba dalam waktu pengadaan <?= $A['lead'] ?> hari, sebelum tenggat <?= $deadline ?> hari. Bila pesanan itu terlambat, kebutuhan bersih Bahan A menjadi <?= num($A['net_if_po_late']) ?> unit.</li>
                <li>Hari kerja dianggap berurutan dan tidak ada waktu tambahan untuk proses perakitan setelah bahan tiba.</li>
                <li>Kapasitas awal tanpa pembelian: Bahan B <?= num($B['stock']) ?> &divide; <?= $B['per'] ?> = <strong><?= num($B['max_units']) ?> unit</strong>; Bahan A <?= num($A['stock']) ?> &divide; <?= $A['per'] ?> = <?= num($A['max_units']) ?> unit. Bahan B menjadi pembatas.</li>
            </ul>
        </div>
        <form action="api/scm_actions.php" method="POST" class="card" style="background:var(--bg-card-subtle);margin-top:14px">
            <input type="hidden" name="action" value="update_scenario">
            <div style="font-weight:700;margin-bottom:8px">Ubah parameter skenario (disimpan di database)</div>
            <div class="form-inline">
            <?php foreach (['target_qty'=>'Target produksi','deadline_days'=>'Tenggat (hari kerja)','stock_a'=>'Stok A','onorder_a'=>'Pesanan A belum tiba','lead_a'=>'Lead time A','stock_b'=>'Stok B','onorder_b'=>'Pesanan B belum tiba','lead_b'=>'Lead time B'] as $k => $lab): ?>
                <div class="form-group"><label class="form-label"><?= $lab ?></label><input type="number" min="0" name="<?= $k ?>" value="<?= (int)$sc[$k] ?>" class="form-control font-mono" style="width:120px"></div>
            <?php endforeach; ?>
                <button class="btn btn-primary" type="submit">Hitung ulang</button>
            </div>
            <p style="font-size:.75rem;color:var(--text-dim);margin-top:6px">Nilai soal: 300 unit, 6 hari, A (stok 400, pesanan 100, 4 hari), B (stok 250, pesanan 0, 7 hari). Tombol &quot;Reset Default&quot; di atas mengembalikannya.</p>
        </form>
    </div>

    <div id="tab-simulasi-pr-po" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>Permintaan pembelian dari kebutuhan produksi.</strong> Sistem mengubah kebutuhan bersih menjadi PR dan PO rekomendasi pengadaan.</div>
        <?php if (!$hasPR): ?><form action="api/scm_actions.php" method="POST" style="margin-bottom:12px"><input type="hidden" name="action" value="generate_mrp_pr"><button class="btn btn-primary" type="submit">Jalankan MRP &amp; terbitkan PR/PO</button></form><?php endif; ?>
        <div class="table-responsive" style="margin-bottom:14px"><table class="table-custom"><thead><tr><th>PR</th><th>Bahan</th><th class="text-right">Jumlah</th><th>Dibutuhkan paling lambat</th><th>Status</th></tr></thead><tbody>
            <?php if (!$prs) echo '<tr><td colspan="5" class="text-center">Belum ada PR.</td></tr>'; foreach ($prs as $p): ?>
            <tr><td><span class="badge badge-primary font-mono"><?= h($p['pr_no']) ?></span></td><td><?= h($p['item_code']) ?></td><td class="font-mono text-right"><?= num($p['qty']) ?></td><td class="font-mono"><?= h($p['need_date']) ?></td><td><?= h($p['status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <div class="table-responsive"><table class="table-custom"><thead><tr><th>PO</th><th>Bahan</th><th>Pemasok</th><th class="text-right">Dipesan</th><th class="text-right">Diterima</th><th>Perkiraan tiba</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($pos as $p): ?><tr><td><span class="badge font-mono"><?= h($p['po_no']) ?></span></td><td><?= h($p['item_code']) ?></td><td><?= h($p['vendor_name']) ?></td><td class="font-mono text-right"><?= num($p['qty_ordered']) ?></td><td class="font-mono text-right"><?= num($p['qty_received']) ?></td><td class="font-mono"><?= h($p['expected_date']) ?></td><td><span class="badge"><?= h($p['status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
    </div>

    <div id="tab-penerimaan-parsial" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>Penerimaan sebagian barang dari pemasok.</strong> Pilih PO dan jumlah yang diterima: stok bertambah, status PO menjadi PARTIAL/CLOSED, dan jurnal Dr Persediaan / Cr Utang Usaha terbentuk.</div>
        <?php $open = array_filter($pos, fn($p) => $p['qty_received'] < $p['qty_ordered']); if ($open): ?>
        <form action="api/scm_actions.php" method="POST" class="card" style="background:var(--bg-card-subtle)"><input type="hidden" name="action" value="receive_goods">
            <div class="form-inline">
                <div class="form-group"><label class="form-label">Nomor PO</label><select name="po_no" class="form-control"><?php foreach ($open as $p) echo '<option value="' . h($p['po_no']) . '">' . h($p['po_no'] . ' - ' . $p['item_code'] . ' (sisa ' . ($p['qty_ordered'] - $p['qty_received']) . ')') . '</option>'; ?></select></div>
                <div class="form-group"><label class="form-label">Jumlah diterima</label><input type="number" name="qty" class="form-control font-mono" value="200" min="1" style="width:120px"></div>
                <button class="btn btn-success" type="submit">Terima barang</button>
            </div></form>
        <?php else: ?><div class="alert alert-warning">Tidak ada PO terbuka. Terbitkan PR/PO di Tahap 3 lebih dulu.</div><?php endif; ?>
        <div class="table-responsive" style="margin:14px 0"><table class="table-custom"><thead><tr><th>Bahan</th><th class="text-right">Stok sekarang</th><th>PO terbuka</th><th>Kapasitas perakitan dari stok</th></tr></thead><tbody>
            <?php foreach ([$A, $B] as $x): $rem = 0; foreach ($pos as $p) if ($p['item_code'] === $x['code']) $rem += $p['qty_ordered'] - $p['qty_received']; ?>
            <tr><td><?= h($x['code']) ?></td><td class="font-mono text-right"><?= num($stock[$x['code']]) ?></td><td class="font-mono"><?= num($rem) ?> unit</td><td><?= $x['per'] ? num(intdiv($stock[$x['code']], $x['per'])) : 0 ?> unit produk</td></tr><?php endforeach; ?></tbody></table></div>
        <?php if ($jr): ?><div style="font-weight:700;margin-bottom:4px">Jurnal penerimaan (database)</div><?php foreach ($jr as $j) echo '<div class="font-mono" style="font-size:.78rem">' . h($j['entry_no']) . ' &mdash; ' . ($j['debit'] > 0 ? 'Dr ' : '&nbsp;&nbsp;Cr ') . h($j['account_code']) . ' ' . rp($j['debit'] > 0 ? $j['debit'] : $j['credit']) . '</div>'; endif; ?>
    </div>

    <div id="tab-krisis-keterlambatan" class="tab-content-panel" style="display:none;">
        <?php $late = max(8, $B['lead']); ?>
        <div class="alert alert-danger"><strong>Kondisi baru:</strong> &quot;Pemasok bahan B menginformasikan bahwa barang baru dapat dikirim dalam delapan hari kerja, sedangkan produksi harus selesai dalam <?= $deadline ?> hari kerja.&quot; Untuk memeriksanya di sistem, ubah <em>Lead time B</em> menjadi 8 di Tahap 2 dan lihat statusnya berubah menjadi terlambat.</div>
        <div class="table-responsive" style="margin-bottom:14px"><table class="table-custom">
            <thead><tr><th>Pilihan tindakan</th><th>Waktu</th><th>Biaya</th><th>Risiko</th><th>Penilaian</th></tr></thead><tbody>
            <tr><td><strong>1. Tetap menunggu pemasok B (8 hari)</strong></td><td>Hari ke-8, telat <?= 8 - $deadline ?> hari</td><td>Terendah</td><td><span class="badge badge-danger">Tinggi</span></td><td>Ditolak: tenggat gagal.</td></tr>
            <tr><td><strong>2. Pemasok alternatif (kirim cepat)</strong></td><td>&le; <?= max(1, $deadline - 3) ?> hari (perlu konfirmasi)</td><td>Harga/ongkos lebih tinggi</td><td><span class="badge badge-info">Rendah</span></td><td>Dipilih untuk menutup kekurangan <?= num($B['net']) ?> unit (atau sebagian).</td></tr>
            <tr><td><strong>3. Pakai persediaan lokasi lain</strong></td><td>1&ndash;2 hari</td><td>Ongkos angkut</td><td><span class="badge badge-success">Rendah</span></td><td>Cek dulu; murah &amp; cepat jika tersedia.</td></tr>
            <tr><td><strong>4. Susun ulang jadwal / produksi bertahap</strong></td><td>Mulai hari ke-1</td><td>Minim</td><td><span class="badge badge-warning">Sedang</span></td><td>Rakit <?= num($B['max_units']) ?> unit pertama dari stok B yang ada; sisanya menyusul.</td></tr>
            </tbody></table></div>
        <div class="card" style="background:var(--bg-card-subtle)"><div style="font-weight:700;margin-bottom:6px">Keputusan yang paling masuk akal</div>
            <p style="font-size:.84rem;color:var(--text-muted);line-height:1.6">Kombinasi: (a) mulai perakitan <?= num($B['max_units']) ?> unit pertama memakai stok Bahan B yang ada (tidak ada mesin menganggur), (b) cek persediaan lokasi lain, (c) pesan sisa kebutuhan ke pemasok alternatif yang bisa tiba sebelum hari ke-<?= $deadline ?>, dan (d) sisa PO ke pemasok B dibatalkan/ditunda agar tidak terjadi stok berlebih. Pertimbangan: waktu (tenggat <?= $deadline ?> hari), biaya (harga alternatif lebih tinggi), ketersediaan (cek stok lokasi lain), dan risiko (denda keterlambatan pelanggan).</p></div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
