<?php
// praktikum3.php - Praktikum 3: Pemetaan Proses Bisnis dan Perancangan Alur Persetujuan
$pageTitle = 'Praktikum 3: Proses Bisnis & Persetujuan';
require_once __DIR__ . '/config/database.php';
$pdo = getDbConnection();
$labRequests = $pdo->query("SELECT * FROM lab_purchase_requests ORDER BY id ASC")->fetchAll();
$stLabel = ['APPROVED'=>['Disetujui','badge-success'],'WAITING_APPROVAL'=>['Menunggu persetujuan','badge-warning'],'REAPPROVAL'=>['Perlu persetujuan ulang','badge-warning'],'ESCALATED'=>['Eskalasi ke Pimpinan','badge-danger']];
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-hero">
    <div class="page-hero-meta"><span class="badge badge-primary">Praktikum 3</span><span class="badge">Indikator: Pemetaan dan perancangan proses bisnis</span></div>
    <h1 class="page-hero-title">Pemetaan Proses Bisnis &amp; Alur Persetujuan</h1>
    <p class="page-hero-desc">Proses pembelian bahan praktik laboratorium Politeknik META Industri: diagram as-is dan to-be, tabel aktor, aturan bisnis, simulasi kondisi khusus (kenaikan harga pemasok) dengan data di database, dan alasan rancangan baru lebih baik.</p>
</div>
<div class="card">
    <div class="tab-pills">
        <button class="tab-pill-btn active" data-tab="tab-asis-workflow">A. Diagram As-Is</button>
        <button class="tab-pill-btn" data-tab="tab-tobe-workflow">B. Diagram To-Be &amp; Aktor</button>
        <button class="tab-pill-btn" data-tab="tab-aturan-bisnis">C. Aturan Bisnis</button>
        <button class="tab-pill-btn" data-tab="tab-kalkulator-toleransi">D. Kondisi Khusus</button>
        <button class="tab-pill-btn" data-tab="tab-data-permintaan">Data Permintaan (DB)</button>
        <button class="tab-pill-btn" data-tab="tab-pembelian-darurat">Pengadaan Darurat</button>
        <button class="tab-pill-btn" data-tab="tab-alasan">Mengapa Lebih Baik</button>
    </div>

    <div id="tab-asis-workflow" class="tab-content-panel">
        <div class="alert alert-danger"><strong>A. Diagram as-is:</strong> proses manual yang berlangsung saat ini; kotak merah menandai aktivitas berisiko salah atau lambat.</div>
        <?= diagramImg('p3_asis.png', 'Gambar 6. Proses pembelian bahan lab saat ini (as-is) beserta titik risiko', 'Diagram as-is pembelian bahan lab') ?>
        <div class="table-responsive"><table class="table-custom">
            <thead><tr><th>No</th><th>Aktor</th><th>Aktivitas manual</th><th>Media</th><th>Risiko / keterlambatan</th></tr></thead><tbody>
            <tr><td>1</td><td>Dosen</td><td>Mengajukan permintaan bahan</td><td>WhatsApp / lisan</td><td><span class="badge badge-danger">Tanpa format &amp; identitas, mudah tenggelam</span></td></tr>
            <tr><td>2</td><td>Kepala Lab</td><td>Memverifikasi kebutuhan</td><td>Perkiraan pribadi</td><td><span class="badge badge-danger">Keputusan subjektif</span></td></tr>
            <tr><td>3</td><td>Gudang</td><td>Memeriksa stok</td><td>Hitung manual</td><td><span class="badge badge-danger">Stok tidak real-time, beli ganda</span></td></tr>
            <tr><td>4</td><td>Pembelian</td><td>Memilih pemasok</td><td>Telepon / chat</td><td><span class="badge badge-warning">Tanpa pembanding harga</span></td></tr>
            <tr><td>5</td><td>Pimpinan</td><td>Memberi persetujuan</td><td>Tanda tangan / pesan</td><td><span class="badge badge-danger">Lambat, tanpa jejak, nilai berubah tak terpantau</span></td></tr>
            <tr><td>6</td><td>Gudang</td><td>Menerima barang</td><td>Surat jalan kertas</td><td><span class="badge badge-warning">Diterima tanpa nomor PO</span></td></tr>
            <tr><td>7</td><td>Keuangan</td><td>Memproses pembayaran</td><td>Foto surat jalan</td><td><span class="badge badge-danger">Bayar ganda / tanpa dokumen</span></td></tr>
            </tbody></table></div>
    </div>

    <div id="tab-tobe-workflow" class="tab-content-panel" style="display:none;">
        <div class="alert alert-success"><strong>B. Diagram to-be setelah ERP</strong> termasuk alur persetujuan, pemeriksaan stok, penerimaan barang, dan pembayaran.</div>
        <?= diagramImg('p3_tobe.png', 'Gambar 7. Rancangan proses pembelian bahan lab setelah ERP (to-be)', 'Diagram to-be pembelian bahan lab') ?>
        <div style="font-weight:700;margin:10px 0">Tabel aktor dan tanggung jawab</div>
        <div class="table-responsive"><table class="table-custom">
            <thead><tr><th>Aktor</th><th>Tanggung jawab di ERP</th><th>Dokumen / output</th></tr></thead><tbody>
            <tr><td>Dosen</td><td>Mengajukan Material Request dengan identitas, tujuan, tanggal pakai.</td><td>Material Request</td></tr>
            <tr><td>Kepala Lab</td><td>Memverifikasi kebutuhan; menyetujui pembelian &le; Rp 5.000.000 dan persetujuan ulang bila harga naik &gt; 5%.</td><td>Persetujuan digital</td></tr>
            <tr><td>Gudang</td><td>Cek stok (sistem), reservasi stok, menerima barang terhadap nomor PO.</td><td>Goods Receipt</td></tr>
            <tr><td>Bagian Pembelian</td><td>Memilih pemasok terdaftar, menerbitkan PO, memantau penawaran.</td><td>PR / PO</td></tr>
            <tr><td>Pimpinan</td><td>Menyetujui pembelian &gt; Rp 5.000.000 atau yang melewati plafon Kepala Lab.</td><td>Persetujuan PO</td></tr>
            <tr><td>Keuangan</td><td>Three-way matching PO&ndash;GR&ndash;Faktur, membayar, posting jurnal.</td><td>Voucher pembayaran, jurnal</td></tr>
            </tbody></table></div>
    </div>

    <div id="tab-aturan-bisnis" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>C. Aturan bisnis (6 aturan):</strong></div>
        <div class="grid-2">
            <div class="card" style="margin-bottom:0; background: var(--bg-card-subtle);">
                <div style="font-size: 0.88rem; font-weight: 600; color: #818cf8; margin-bottom: 6px;">1. Autentikasi Identitas Pengaju</div>
                <p style="font-size: 0.8rem; color: var(--text-muted);">
                    Permintaan wajib diajukan melalui akun resmi dosen pengampu dengan mencantumkan tujuan praktikum dan tanggal pemakaian. Pengajuan tanpa akun valid ditolak sistem.
                </p>
            </div>
            <div class="card" style="margin-bottom:0; background: var(--bg-card-subtle);">
                <div style="font-size: 0.88rem; font-weight: 600; color: #34d399; margin-bottom: 6px;">2. Pemeriksaan Stok Otomatis (Anti-Overstock)</div>
                <p style="font-size: 0.8rem; color: var(--text-muted);">
                    PR hanya diterbitkan untuk jumlah kekurangan riil: Kekurangan = Kebutuhan - (Stok Gudang + PO Terbuka - Reservasi). Jika selisih &le; 0, pengajuan diblokir.
                </p>
            </div>
            <div class="card" style="margin-bottom:0; background: var(--bg-card-subtle);">
                <div style="font-size: 0.88rem; font-weight: 600; color: #fbbf24; margin-bottom: 6px;">3. Batas Wewenang Persetujuan Bertingkat</div>
                <p style="font-size: 0.8rem; color: var(--text-muted);">
                    Plafon persetujuan: Belanja &le; Rp 5.000.000 cukup Kepala Lab. Belanja di atas Rp 5.000.000 wajib melibatkan persetujuan Pimpinan / Direktur.
                </p>
            </div>
            <div class="card" style="margin-bottom:0; background: var(--bg-card-subtle);">
                <div style="font-size: 0.88rem; font-weight: 600; color: #38bdf8; margin-bottom: 6px;">4. Pencocokan Tiga Dokumen (Three-Way Matching)</div>
                <p style="font-size: 0.8rem; color: var(--text-muted);">
                    Bagian keuangan hanya dapat memproses pembayaran jika sistem menemukan kecocokan mutlak antara dokumen PO, bukti penerimaan barang (GR), dan faktur tagihan vendor.
                </p>
            </div>
            <div class="card" style="margin-bottom:0; background: var(--bg-card-subtle);">
                <div style="font-size: 0.88rem; font-weight: 600; color: var(--text-main); margin-bottom: 6px;">5. Prinsip Tanpa PO Tidak Ada Penerimaan</div>
                <p style="font-size: 0.8rem; color: var(--text-muted);">
                    Petugas dilarang menerima barang yang tidak memiliki nomor PO aktif di sistem. Barang tanpa nomor PO akan ditolak di area penerimaan lab.
                </p>
            </div>
            <div class="card" style="margin-bottom:0; background: var(--bg-card-subtle);">
                <div style="font-size: 0.88rem; font-weight: 600; color: #f87171; margin-bottom: 6px;">6. Pemisahan Tugas (Separation of Duties)</div>
                <p style="font-size: 0.8rem; color: var(--text-muted);">
                    Pemohon barang (Dosen), penyetuju (Kepala Lab/Pimpinan), penerima fisik (Gudang), dan pembayar (Keuangan) dilarang dirangkap oleh staf yang sama.
                </p>
            </div>
        </div>
        </div>
    </div>

    <div id="tab-kalkulator-toleransi" class="tab-content-panel" style="display:none;">
        <div class="alert alert-warning"><strong>D. Simulasi kondisi khusus:</strong> nilai pembelian naik setelah pemasok mengirim penawaran terbaru. Toleransi 5% dan plafon Rp 5.000.000 adalah aturan rancangan kami (asumsi), bukan angka dari soal.</div>
        <div class="grid-2">
            <!-- Interaktif Calculator -->
            <div class="card dummy-editor-card" style="margin-bottom:0;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; flex-wrap:wrap; gap:8px;">
                    <div style="font-size: 0.95rem; font-weight: 700; color: var(--text-main);">
                        Kalkulator Uji Toleransi Kenaikan Harga
                    </div>
                    <span class="dummy-editor-badge">⚡ Real-time Evaluator</span>
                </div>

                <div style="margin-bottom: 14px;">
                    <label class="form-label">Contoh skenario penawaran baru (nilai contoh, bukan dari soal):</label>
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="setPresetTolerance(4000000, 4150000)">
                            Rp 4.150.000 (+3.75%)
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="setPresetTolerance(4000000, 4300000)">
                            Rp 4.300.000 (+7.50%)
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="setPresetTolerance(4000000, 5500000)">
                            Rp 5.500.000 (+37.5%)
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nilai PO Awal yang Disetujui (Rp):</label>
                    <input type="number" id="calc-base" class="form-control font-mono" value="4000000" oninput="calculateTolerance()">
                </div>

                <div class="form-group">
                    <label class="form-label">Nilai Penawaran Baru Vendor (Rp):</label>
                    <input type="number" id="calc-revised" class="form-control font-mono" value="4300000" oninput="calculateTolerance()">
                </div>

                <div class="form-group">
                    <label class="form-label">Ketentuan Kebijakan Toleransi:</label>
                    <input type="text" class="form-control" value="Toleransi &le; 5.0% | Batas Maksimal Kepala Lab: Rp 5.000.000" disabled style="opacity: 0.85;">
                </div>

                <button type="button" onclick="calculateTolerance(true)" class="btn btn-primary" style="width: 100%; margin-bottom: 14px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    <span>Hitung & Evaluasi Toleransi Sekarang</span>
                </button>

                <!-- Result Box -->
                <div id="calc-result-box" style="padding: 14px; border-radius: var(--radius-sm); background: #0c1220; border: 1.5px solid rgba(245, 158, 11, 0.5); transition: all 0.3s ease;">
                    <div style="font-size: 0.78rem; color: var(--text-dim); font-weight: 600;">Persentase Perubahan:</div>
                    <div id="calc-pct" class="font-mono" style="font-size: 1.25rem; font-weight: 800; color: #fbbf24; margin: 4px 0;">+7.50%</div>
                    <div id="calc-status" style="margin-top: 4px; font-weight: 700; font-size: 0.88rem; color: #fbbf24;">Status: Membutuhkan Persetujuan Ulang</div>
                    <div id="calc-desc" style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px; line-height: 1.45;">
                        Kenaikan &gt; 5%. PO dikembalikan ke status revisi dan wajib disetujui ulang oleh Kepala Lab.
                    </div>
                </div>
            </div>

            <!-- Tabel Ketentuan Soal -->
            <div class="card" style="margin-bottom:0; background: var(--bg-card-subtle);">
                <div style="font-size: 0.9rem; font-weight: 600; margin-bottom: 12px;">Ketentuan rancangan (contoh):</div>
                
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Penawaran Baru</th>
                                <th>Kenaikan</th>
                                <th>Keputusan Sistem ERP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-mono">Rp 4.150.000</td>
                                <td class="font-mono" style="color: #34d399;">+3.75% (&le; 5%)</td>
                                <td><span class="badge badge-success">Tetap Berlaku</span><br><small style="color:var(--text-dim)">Notifikasi ke Kepala Lab</small></td>
                            </tr>
                            <tr>
                                <td class="font-mono">Rp 4.300.000</td>
                                <td class="font-mono" style="color: #fbbf24;">+7.50% (&gt; 5%)</td>
                                <td><span class="badge badge-warning">Persetujuan Ulang Kepala Lab</span><br><small style="color:var(--text-dim)">Di bawah batas Rp 5 Juta</small></td>
                            </tr>
                            <tr>
                                <td class="font-mono">Rp 5.500.000</td>
                                <td class="font-mono" style="color: #f87171;">+37.5% (&gt; Rp 5 Jt)</td>
                                <td><span class="badge badge-danger">Persetujuan Batal</span><br><small style="color:var(--text-dim)">Eskalasi ke Direktur/Pimpinan</small></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-data-permintaan" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>Permintaan bahan lab (tersimpan di database).</strong> Jalur persetujuan ditentukan otomatis dari nilai; masukkan penawaran baru untuk menguji apakah persetujuan lama masih berlaku.</div>
        <div class="table-responsive" style="margin-bottom:16px"><table class="table-custom">
            <thead><tr><th>No</th><th>Barang</th><th>Pengaju</th><th class="text-right">Nilai awal</th><th class="text-right">Penawaran baru</th><th>Penyetuju</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($labRequests as $r): $sl = $stLabel[$r['status']] ?? [$r['status'],'']; ?>
            <tr><td><span class="badge font-mono"><?= h($r['request_no']) ?></span><?= $r['urgency']==='EMERGENCY_24H' ? '<br><span class="badge badge-danger">Darurat 24 jam</span>' : '' ?></td>
            <td><?= h($r['item_name']) ?> <small>(<?= (int)$r['qty'] ?> unit)</small><br><small style="color:var(--text-dim)"><?= h($r['notes']) ?></small></td>
            <td><?= h($r['requester_lecturer']) ?></td>
            <td class="font-mono text-right"><?= rp($r['total_estimated']) ?></td>
            <td class="font-mono text-right"><?= $r['actual_quotation'] ? rp($r['actual_quotation']) : '-' ?></td>
            <td><?= $r['approval_level'] === 'PIMPINAN' ? 'Pimpinan' : 'Kepala Lab' ?></td>
            <td><span class="badge <?= $sl[1] ?>"><?= h($sl[0]) ?></span></td>
            <td style="min-width:210px">
                <?php if ($r['status'] !== 'APPROVED'): ?>
                <form action="api/lab_actions.php" method="POST" style="margin-bottom:6px"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-success btn-sm" type="submit">Setujui</button></form>
                <?php endif; ?>
                <form action="api/lab_actions.php" method="POST" class="form-inline"><input type="hidden" name="action" value="revise_quote"><input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="number" name="new_total" class="form-control font-mono" placeholder="Nilai penawaran baru" style="width:140px" min="1" required><button class="btn btn-outline btn-sm" type="submit">Uji</button></form>
            </td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <form action="api/lab_actions.php" method="POST" class="card" style="background:var(--bg-card-subtle)">
            <input type="hidden" name="action" value="create_request">
            <div style="font-weight:700;margin-bottom:8px">Ajukan permintaan bahan baru</div>
            <div class="form-inline">
                <div class="form-group"><label class="form-label">Nama pengaju (dosen)</label><input name="requester" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Barang</label><input name="item_name" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Jumlah</label><input type="number" name="qty" class="form-control font-mono" value="1" min="1" style="width:90px"></div>
                <div class="form-group"><label class="form-label">Harga satuan (Rp)</label><input type="number" name="price" class="form-control font-mono" min="1" required></div>
                <div class="form-group"><label class="form-label">Urgensi</label><select name="urgency" class="form-control"><option value="NORMAL">Normal</option><option value="EMERGENCY_24H">Darurat 24 jam</option></select></div>
                <button class="btn btn-primary" type="submit">Ajukan</button>
            </div>
        </form>
    </div>

    <div id="tab-pembelian-darurat" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info">
            <strong>Prosedur Pengadaan Darurat 24 Jam:</strong> Mempercepat proses pemenuhan bahan ujian mahasiswa tanpa menghapus kontrol internal.
        </div>

        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Waktu (Timeline)</th>
                        <th>Tindakan Operasional</th>
                        <th>Kontrol Internal yang Tetap Berjalan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-mono">T+0 Jam</td>
                        <td>Dosen menginput formulir pengadaan darurat dengan alasan ujian mahasiswa.</td>
                        <td>Alasan kedaruratan dan identitas pengaju tercatat resmi di basis data.</td>
                    </tr>
                    <tr>
                        <td class="font-mono">T+1 Jam</td>
                        <td>Sistem memeriksa stok di laboratorium lain untuk opsi transfer internal.</td>
                        <td>Pemeriksaan ketersediaan stok fisik tetap menjadi syarat awal wajib.</td>
                    </tr>
                    <tr>
                        <td class="font-mono">T+2 s/d T+3 Jam</td>
                        <td>Persetujuan paralel cepat (SLA target 2 jam) oleh Kepala Lab & Pimpinan.</td>
                        <td>Persetujuan tetap dilakukan pejabat berwenang, bukan bypass kontrol.</td>
                    </tr>
                    <tr>
                        <td class="font-mono">T+4 Jam</td>
                        <td>PO Darurat diterbitkan ke vendor rekanan terdaftar dengan perjanjian harga payung.</td>
                        <td>Pemesanan hanya kepada vendor resmi terikat kontrak harga.</td>
                    </tr>
                    <tr>
                        <td class="font-mono">Hari yang Sama</td>
                        <td>Barang tiba di lab, petugas melakukan Goods Receipt (GR) terhadap nomor PO.</td>
                        <td>Barang tetap diverifikasi spesifikasinya dan dicatat penambahan stok fisiknya.</td>
                    </tr>
                    <tr>
                        <td class="font-mono">Pasca-Event (&le; 3 Hari)</td>
                        <td>Tinjauan pasca-darurat (Post-Audit Review) oleh Bagian Keuangan dan Pimpinan.</td>
                        <td>Pembayaran tagihan vendor tetap melalui pencocokan dokumen (three-way match).</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="tab-alasan" class="tab-content-panel" style="display:none;">
        <div class="alert alert-success"><strong>Alasan rancangan baru lebih baik daripada proses sebelumnya</strong></div>
        <div class="table-responsive"><table class="table-custom">
            <thead><tr><th>Aspek</th><th>Proses lama (as-is)</th><th>Rancangan baru (to-be)</th></tr></thead><tbody>
            <tr><td>Identitas &amp; jejak</td><td>Chat tanpa format, tenggelam.</td><td>Material Request digital dengan identitas dan audit trail.</td></tr>
            <tr><td>Stok</td><td>Hitung manual, rawan beli ganda.</td><td>Cek stok otomatis; PR hanya untuk kekurangan nyata.</td></tr>
            <tr><td>Persetujuan</td><td>Tidak jelas siapa berwenang.</td><td>Persetujuan bertingkat sesuai nilai, dengan bukti.</td></tr>
            <tr><td>Perubahan harga</td><td>Tidak terpantau.</td><td>Aturan toleransi 5% &amp; plafon memicu persetujuan ulang.</td></tr>
            <tr><td>Penerimaan &amp; pembayaran</td><td>Surat jalan kertas, bayar tanpa dokumen.</td><td>GR terhadap PO dan three-way matching sebelum bayar.</td></tr>
            <tr><td>Kecepatan</td><td>Berhari-hari lewat telepon/chat.</td><td>Alur digital paralel, jalur darurat 24 jam tetap terkontrol.</td></tr>
            </tbody></table></div>
    </div>
</div>
<script>
function setPresetTolerance(base, revised) {
    const baseEl = document.getElementById('calc-base');
    const revisedEl = document.getElementById('calc-revised');
    if (baseEl && revisedEl) {
        baseEl.value = base;
        revisedEl.value = revised;
        calculateTolerance(true);
    }
}

function calculateTolerance(animate = false) {
    const base = parseFloat(document.getElementById('calc-base').value) || 0;
    const revised = parseFloat(document.getElementById('calc-revised').value) || 0;
    
    if (base <= 0) return;
    
    const diff = revised - base;
    const pct = (diff / base) * 100;
    
    const pctEl = document.getElementById('calc-pct');
    const statusEl = document.getElementById('calc-status');
    const descEl = document.getElementById('calc-desc');
    const boxEl = document.getElementById('calc-result-box');
    
    pctEl.innerText = (pct >= 0 ? '+' : '') + pct.toFixed(2) + '% (Selisih: Rp ' + diff.toLocaleString('id-ID') + ')';
    
    if (pct <= 5.0 && revised <= 5000000) {
        pctEl.style.color = '#34d399';
        statusEl.innerText = 'Status: Persetujuan Awal Tetap Berlaku';
        statusEl.style.color = '#34d399';
        descEl.innerText = 'Kenaikan dalam batas toleransi 5% dan total nilai di bawah Rp 5 Juta. Notifikasi otomatis dikirimkan ke Kepala Lab.';
        boxEl.style.borderColor = 'rgba(16, 185, 129, 0.6)';
        boxEl.style.boxShadow = '0 0 16px rgba(16, 185, 129, 0.2)';
    } else if (pct > 5.0 && revised <= 5000000) {
        pctEl.style.color = '#fbbf24';
        statusEl.innerText = 'Status: Membutuhkan Persetujuan Ulang (Kepala Lab)';
        statusEl.style.color = '#fbbf24';
        descEl.innerText = 'Kenaikan melebihi batas 5%. PO dikembalikan ke status revisi dan wajib disetujui ulang oleh Kepala Lab.';
        boxEl.style.borderColor = 'rgba(245, 158, 11, 0.6)';
        boxEl.style.boxShadow = '0 0 16px rgba(245, 158, 11, 0.2)';
    } else {
        pctEl.style.color = '#f87171';
        statusEl.innerText = 'Status: Persetujuan Batal Mutlak (Eskalasi ke Pimpinan)';
        statusEl.style.color = '#f87171';
        descEl.innerText = 'Total nilai baru melampaui plafon wewenang Kepala Lab (Rp 5.000.000). PO wajib diajukan ke Direktur/Pimpinan!';
        boxEl.style.borderColor = 'rgba(239, 68, 68, 0.6)';
        boxEl.style.boxShadow = '0 0 16px rgba(239, 68, 68, 0.2)';
    }

    if (animate) {
        boxEl.style.transform = 'scale(1.02)';
        setTimeout(() => {
            boxEl.style.transform = 'scale(1)';
        }, 180);
    }
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
