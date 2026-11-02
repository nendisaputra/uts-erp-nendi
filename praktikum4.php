<?php
// praktikum4.php - Praktikum 4: Konfigurasi dan Simulasi Modul Keuangan (semua angka dihitung dari database)
$pageTitle = 'Praktikum 4: Modul Keuangan';
require_once __DIR__ . '/config/database.php';
$pdo = getDbConnection();

$coa = $pdo->query("SELECT * FROM coa ORDER BY code")->fetchAll();
$rows = $pdo->query("SELECT j.id jid, j.entry_no, j.entry_date, j.description, j.source_doc, j.is_reversed, j.created_by, d.account_code, d.debit, d.credit
                     FROM journal_entries j JOIN journal_details d ON d.journal_id=j.id WHERE j.module='P4' ORDER BY j.id, d.id")->fetchAll();
$journals = [];
foreach ($rows as $r) {
    $journals[$r['jid']]['h'] = $r;
    $journals[$r['jid']]['lines'][] = $r;
}
// Buku besar + saldo
$accName = []; $ledger = [];
foreach ($coa as $a) {
    $accName[$a['code']] = $a['name'];
    $ledger[$a['code']] = ['acc'=>$a, 'rows'=>[], 'dr'=>0, 'cr'=>0, 'bal'=>(float)$a['initial_balance']];
}
foreach ($rows as $r) {
    $c = $r['account_code']; $acc = $ledger[$c]['acc'];
    $ledger[$c]['dr'] += $r['debit']; $ledger[$c]['cr'] += $r['credit'];
    $ledger[$c]['bal'] += $acc['normal_balance'] === 'D' ? ($r['debit'] - $r['credit']) : ($r['credit'] - $r['debit']);
    $ledger[$c]['rows'][] = ['no'=>$r['entry_no'], 'desc'=>$r['description'], 'dr'=>$r['debit'], 'cr'=>$r['credit'], 'bal'=>$ledger[$c]['bal']];
}
$totDr = $totCr = 0;
foreach ($ledger as $c => $l) { if ($l['acc']['normal_balance'] === 'D') $totDr += $l['bal']; else $totCr += $l['bal']; }
$balanced = abs($totDr - $totCr) < 0.01;
$hasDup = isset(array_column(array_column($journals, 'h'), null, 'entry_no')['TRX-002-DUP']);
$hasRev = isset(array_column(array_column($journals, 'h'), null, 'entry_no')['REV-TRX-002-DUP']);

// Saldo yang diharapkan dari soal (tanpa kesalahan): saldo awal kas 30jt + 5 transaksi
$expected = ['1-1000'=>30500000, '1-1100'=>7000000, '1-1200'=>8000000, '2-1000'=>5000000, '3-1000'=>30000000, '4-1000'=>12000000, '6-1000'=>1500000];
// Deteksi duplikat otomatis: dua jurnal dengan susunan akun & nilai yang sama
$sigs = []; $dupes = [];
foreach ($journals as $jid => $j) {
    $l = array_map(fn($x) => $x['account_code'] . ':' . (float)$x['debit'] . ':' . (float)$x['credit'], $j['lines']); sort($l);
    $sigs[implode('|', $l)][] = $j['h']['entry_no'];
}
foreach ($sigs as $list) if (count($list) > 1) $dupes[] = $list;

require_once __DIR__ . '/includes/header.php';
$kas = $ledger['1-1000']['bal'];
?>
<div class="page-hero">
    <div class="page-hero-meta"><span class="badge badge-primary">Praktikum 4</span><span class="badge">Indikator: Konfigurasi dan simulasi modul keuangan</span></div>
    <h1 class="page-hero-title">Konfigurasi dan Simulasi Modul Keuangan</h1>
    <p class="page-hero-desc">Saldo awal kas Rp 30.000.000. Lima transaksi dicatat sebagai jurnal di database; jurnal, buku besar, dan neraca saldo dihitung langsung dari data tersebut (pajak, HPP, dan persediaan akibat penjualan diabaikan sesuai soal).</p>
</div>

<div class="card" style="background:var(--bg-card-subtle)">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div>
            <div style="font-size:.72rem;color:var(--text-dim);font-weight:600;text-transform:uppercase">Status neraca saldo</div>
            <div class="<?= $balanced ? 'ok-text' : 'bad-text' ?>" style="font-size:1.1rem"><?= $balanced ? 'SEIMBANG' : 'TIDAK SEIMBANG' ?>
                <span style="font-size:.82rem;color:var(--text-muted);font-weight:400">(Debit <?= rp($totDr) ?> &bull; Kredit <?= rp($totCr) ?> &bull; Saldo kas <?= rp($kas) ?>)</span></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="tab-pills">
        <button class="tab-pill-btn active" data-tab="tab-konfigurasi-coa">Tahap 1. Konfigurasi Akun</button>
        <button class="tab-pill-btn" data-tab="tab-input-transaksi">Tahap 2. Input Transaksi</button>
        <button class="tab-pill-btn" data-tab="tab-jurnal-umum">Tahap 3a. Jurnal Umum</button>
        <button class="tab-pill-btn" data-tab="tab-buku-besar">3b. Buku Besar</button>
        <button class="tab-pill-btn" data-tab="tab-neraca-saldo">3c. Neraca Saldo &amp; Verifikasi</button>
        <button class="tab-pill-btn" data-tab="tab-analisis-koreksi">Tahap 4. Pengujian Kesalahan</button>
    </div>

    <div id="tab-konfigurasi-coa" class="tab-content-panel">
        <div class="alert alert-info"><strong>Akun yang dikonfigurasi</strong> (Kas, Piutang usaha, Persediaan, Utang usaha, Penjualan, Beban operasional; Modal sebagai pasangan saldo awal kas).</div>
        <div class="table-responsive"><table class="table-custom">
            <thead><tr><th>Kode</th><th>Nama akun</th><th>Klasifikasi</th><th>Saldo normal</th><th class="text-right">Saldo awal</th></tr></thead><tbody>
            <?php foreach ($coa as $c): ?><tr><td><span class="badge font-mono"><?= h($c['code']) ?></span></td><td><strong><?= h($c['name']) ?></strong></td><td><?= h($c['type']) ?></td><td><?= $c['normal_balance'] === 'D' ? 'Debit' : 'Kredit' ?></td><td class="font-mono text-right"><?= rp($c['initial_balance']) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
    </div>

    <div id="tab-input-transaksi" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>Lima transaksi soal sudah dimasukkan</strong> dengan akun yang tepat (lihat Jurnal Umum). Anda dapat menambah transaksi lain di sini; akun debit dan kredit dipilih dari daftar akun.</div>
        <div class="table-responsive" style="margin-bottom:14px"><table class="table-custom">
            <thead><tr><th>No</th><th>Transaksi (soal)</th><th class="text-right">Nilai</th><th>Jurnal</th></tr></thead><tbody>
            <tr><td>1</td><td>Pembelian bahan baku secara kredit</td><td class="font-mono text-right">8.000.000</td><td>Dr Persediaan / Cr Utang Usaha</td></tr>
            <tr><td>2</td><td>Pembayaran utang pemasok</td><td class="font-mono text-right">3.000.000</td><td>Dr Utang Usaha / Cr Kas</td></tr>
            <tr><td>3</td><td>Penjualan produk secara kredit</td><td class="font-mono text-right">12.000.000</td><td>Dr Piutang Usaha / Cr Penjualan</td></tr>
            <tr><td>4</td><td>Penerimaan pembayaran pelanggan</td><td class="font-mono text-right">5.000.000</td><td>Dr Kas / Cr Piutang Usaha</td></tr>
            <tr><td>5</td><td>Pembayaran biaya operasional tunai</td><td class="font-mono text-right">1.500.000</td><td>Dr Beban Operasional / Cr Kas</td></tr>
            </tbody></table></div>
        <form action="api/finance_actions.php" method="POST" class="card" style="background:var(--bg-card-subtle)">
            <input type="hidden" name="action" value="create_custom_journal">
            <div class="form-inline">
                <div class="form-group"><label class="form-label">Keterangan</label><input name="description" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Akun debit</label><select name="debit_account" class="form-control"><?php foreach ($coa as $c) echo '<option value="' . h($c['code']) . '">' . h($c['code'] . ' ' . $c['name']) . '</option>'; ?></select></div>
                <div class="form-group"><label class="form-label">Akun kredit</label><select name="credit_account" class="form-control"><?php foreach ($coa as $c) echo '<option value="' . h($c['code']) . '"' . ($c['code'] === '1-1000' ? ' selected' : '') . '>' . h($c['code'] . ' ' . $c['name']) . '</option>'; ?></select></div>
                <div class="form-group"><label class="form-label">Jumlah (Rp)</label><input type="number" name="amount" class="form-control font-mono" min="1" required></div>
                <button class="btn btn-primary" type="submit">Simpan jurnal</button>
            </div>
        </form>
    </div>

    <div id="tab-jurnal-umum" class="tab-content-panel" style="display:none;">
        <div class="table-responsive"><table class="table-custom">
            <thead><tr><th>No. entri</th><th>Tanggal</th><th>Keterangan</th><th>Akun</th><th class="text-right">Debit</th><th class="text-right">Kredit</th></tr></thead><tbody>
            <?php foreach ($journals as $j): $n = count($j['lines']); foreach ($j['lines'] as $i => $l): ?>
            <tr>
                <?php if ($i === 0): ?>
                <td rowspan="<?= $n ?>"><span class="badge font-mono"><?= h($j['h']['entry_no']) ?></span><?= $j['h']['is_reversed'] ? '<br><small class="bad-text">[DIBALIK]</small>' : '' ?></td>
                <td rowspan="<?= $n ?>" class="font-mono" style="font-size:.75rem"><?= h($j['h']['entry_date']) ?></td>
                <td rowspan="<?= $n ?>"><strong><?= h($j['h']['description']) ?></strong><br><small style="color:var(--text-dim)"><?= h($j['h']['source_doc']) ?> &bull; <?= h($j['h']['created_by']) ?></small></td>
                <?php endif; ?>
                <td style="<?= $l['credit'] > 0 ? 'padding-left:24px' : '' ?>"><?= h($l['account_code'] . ' ' . $accName[$l['account_code']]) ?></td>
                <td class="font-mono text-right"><?= $l['debit'] > 0 ? num($l['debit']) : '-' ?></td>
                <td class="font-mono text-right"><?= $l['credit'] > 0 ? num($l['credit']) : '-' ?></td>
            </tr>
            <?php endforeach; endforeach; ?>
            </tbody></table></div>
    </div>

    <div id="tab-buku-besar" class="tab-content-panel" style="display:none;">
        <div class="grid-2">
        <?php foreach ($ledger as $code => $l): ?>
            <div class="card" style="margin-bottom:0;background:var(--bg-card-subtle)">
                <div class="card-header" style="padding-bottom:8px;margin-bottom:8px"><div style="font-weight:600"><?= h($code . ' ' . $l['acc']['name']) ?></div><span class="badge badge-success">Saldo <?= rp($l['bal']) ?> (<?= $l['acc']['normal_balance'] ?>)</span></div>
                <div class="table-responsive"><table class="table-custom" style="font-size:.76rem">
                    <thead><tr><th>Ref / keterangan</th><th class="text-right">Debit</th><th class="text-right">Kredit</th><th class="text-right">Saldo</th></tr></thead><tbody>
                    <tr><td>Saldo awal</td><td></td><td></td><td class="font-mono text-right"><?= num($l['acc']['initial_balance']) ?></td></tr>
                    <?php foreach ($l['rows'] as $r): ?><tr><td><?= h($r['no']) ?></td><td class="font-mono text-right"><?= $r['dr'] > 0 ? num($r['dr']) : '-' ?></td><td class="font-mono text-right"><?= $r['cr'] > 0 ? num($r['cr']) : '-' ?></td><td class="font-mono text-right"><?= num($r['bal']) ?></td></tr><?php endforeach; ?>
                    </tbody></table></div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>

    <div id="tab-neraca-saldo" class="tab-content-panel" style="display:none;">
        <div class="table-responsive" style="margin-bottom:14px"><table class="table-custom">
            <thead><tr><th>Akun</th><th class="text-right">Debit</th><th class="text-right">Kredit</th><th class="text-right">Saldo seharusnya (soal)</th><th>Verifikasi</th></tr></thead><tbody>
            <?php foreach ($ledger as $c => $l): $d = $l['acc']['normal_balance'] === 'D'; $ok = abs($l['bal'] - $expected[$c]) < 0.01; ?>
            <tr><td><?= h($c . ' ' . $l['acc']['name']) ?></td><td class="font-mono text-right"><?= $d ? num($l['bal']) : '-' ?></td><td class="font-mono text-right"><?= !$d ? num($l['bal']) : '-' ?></td><td class="font-mono text-right"><?= num($expected[$c]) ?></td><td><?= $ok ? '<span class="badge badge-success">Sesuai</span>' : '<span class="badge badge-danger">Selisih ' . num($l['bal'] - $expected[$c]) . '</span>' ?></td></tr>
            <?php endforeach; ?>
            </tbody><tfoot><tr style="font-weight:700"><td>Total</td><td class="font-mono text-right"><?= num($totDr) ?></td><td class="font-mono text-right"><?= num($totCr) ?></td><td></td><td><?= $balanced ? '<span class="badge badge-success">Seimbang</span>' : '<span class="badge badge-danger">Tidak seimbang</span>' ?></td></tr></tfoot></table></div>
        <div class="chart-box"><?= barChartSvg('Saldo akhir per akun (Rp)', array_map(fn($l) => $l['acc']['name'], array_values($ledger)), [['name'=>'Saldo akhir','color'=>'#6366f1','values'=>array_map(fn($l) => max(0,$l['bal']), array_values($ledger))]]) ?></div>
        <p style="font-size:.8rem;color:var(--text-muted)">Perhitungan saldo kas: 30.000.000 &minus; 3.000.000 + 5.000.000 &minus; 1.500.000 = <strong>30.500.000</strong>. Piutang: 12.000.000 &minus; 5.000.000 = 7.000.000. Utang usaha: 8.000.000 &minus; 3.000.000 = 5.000.000. Penjualan 12.000.000, beban operasional 1.500.000. Total debit = total kredit = 47.000.000.</p>
    </div>

    <div id="tab-analisis-koreksi" class="tab-content-panel" style="display:none;">
        <div class="alert alert-warning"><strong>Pengujian kesalahan:</strong> dosen meminta satu pembayaran yang sudah dimasukkan dicatat ulang (duplikat). Ikuti empat langkah berikut.</div>
        <div class="grid-2" style="margin-bottom:14px">
            <div class="card" style="margin-bottom:0;background:var(--bg-card-subtle)">
                <div style="font-weight:700;margin-bottom:6px">1. Menemukan transaksi duplikat</div>
                <?php if ($dupes): foreach ($dupes as $d): ?><p style="font-size:.82rem">Terdeteksi jurnal identik: <strong><?= h(implode(' & ', $d)) ?></strong> (akun dan nilai sama).</p><?php endforeach; else: ?><p style="font-size:.82rem;color:var(--text-muted)">Belum ada duplikat terdeteksi.</p><?php endif; ?>
                <?php if (!$hasDup): ?><form action="api/finance_actions.php" method="POST"><input type="hidden" name="action" value="duplicate_payment"><button class="btn btn-danger btn-sm" type="submit">Catat ulang pembayaran utang Rp 3.000.000 (simulasi kesalahan)</button></form><?php endif; ?>
            </div>
            <div class="card" style="margin-bottom:0;background:var(--bg-card-subtle)">
                <div style="font-weight:700;margin-bottom:6px">2. Dampak terhadap saldo akun</div>
                <table class="table-custom" style="font-size:.8rem"><thead><tr><th>Akun</th><th class="text-right">Seharusnya</th><th class="text-right">Saat ini</th></tr></thead><tbody>
                <?php foreach (['1-1000','2-1000'] as $c): ?><tr><td><?= h($ledger[$c]['acc']['name']) ?></td><td class="font-mono text-right"><?= num($expected[$c]) ?></td><td class="font-mono text-right <?= abs($ledger[$c]['bal'] - $expected[$c]) > 0.01 ? 'bad-text' : 'ok-text' ?>"><?= num($ledger[$c]['bal']) ?></td></tr><?php endforeach; ?>
                </tbody></table>
                <p style="font-size:.78rem;color:var(--text-muted);margin-top:6px">Kas dan Utang Usaha sama-sama turun Rp 3.000.000 secara keliru. Neraca saldo tetap seimbang karena debit dan kredit berubah sama besar, sehingga kesalahan ini tidak terlihat dari keseimbangan saja; ia terdeteksi lewat rekonsiliasi bank dan subledger pemasok.</p>
            </div>
        </div>
        <div class="card" style="background:var(--bg-card-subtle)">
            <div style="font-weight:700;margin-bottom:6px">3. Memperbaiki dengan prosedur koreksi yang sesuai</div>
            <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:8px">Data lama tidak dihapus. Dibuat jurnal pembalik: Dr Kas 3.000.000 / Cr Utang Usaha 3.000.000.</p>
            <?php if ($hasDup && !$hasRev): ?><form action="api/finance_actions.php" method="POST"><input type="hidden" name="action" value="execute_reversal"><button class="btn btn-success btn-sm" type="submit">Eksekusi jurnal pembalik</button></form>
            <?php elseif ($hasRev): ?><span class="badge badge-success">Sudah dikoreksi</span><?php else: ?><span class="badge">Menunggu simulasi kesalahan</span><?php endif; ?>
        </div>
        <div class="card" style="background:var(--bg-card-subtle);margin-top:14px">
            <div style="font-weight:700;margin-bottom:6px">4. Bukti bahwa koreksi dapat ditelusuri (audit trail)</div>
            <table class="table-custom" style="font-size:.8rem"><thead><tr><th>Urutan</th><th>No. entri</th><th>Keterangan</th><th>Pengguna</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($journals as $j) { $e = $j['h']; if (!in_array($e['entry_no'], ['TRX-002','TRX-002-DUP','REV-TRX-002-DUP'])) continue; ?>
            <tr><td><?= $e['entry_no'] === 'TRX-002' ? 'Asli' : ($e['entry_no'] === 'TRX-002-DUP' ? 'Duplikat' : 'Pembalik') ?></td><td class="font-mono"><?= h($e['entry_no']) ?></td><td><?= h($e['description']) ?></td><td><?= h($e['created_by']) ?></td><td><?= $e['is_reversed'] ? 'Dibalik' : 'Aktif' ?></td></tr>
            <?php } ?></tbody></table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
