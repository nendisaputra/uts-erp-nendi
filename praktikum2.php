<?php
// praktikum2.php - Praktikum 2: Merancang dan Menguji Arsitektur Teknis ERP
$pageTitle = 'Praktikum 2: Arsitektur Teknis & Keamanan';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/data_p2.php';
$pdo = getDbConnection();
$auditLogs = $pdo->query("SELECT * FROM security_audit_logs ORDER BY id DESC LIMIT 12")->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-hero">
    <div class="page-hero-meta"><span class="badge badge-primary">Praktikum 2</span><span class="badge">Indikator: Arsitektur teknis ERP</span></div>
    <h1 class="page-hero-title">Merancang dan Menguji Arsitektur Teknis ERP</h1>
    <p class="page-hero-desc">Arsitektur ERP untuk 100 pengguna (penjualan, gudang, produksi, pembelian, keuangan): hubungan komponen, keamanan akses, pencadangan, aliran data pembelian, potensi kegagalan, dan simulasi gangguan yang dicatat di database.</p>
</div>
<div class="card">
    <div class="tab-pills">
        <button class="tab-pill-btn active" data-tab="tab-diagram-3tier">1. Arsitektur &amp; Alasan</button>
        <button class="tab-pill-btn" data-tab="tab-matriks-rbac">2. Keamanan &amp; Hak Akses</button>
        <button class="tab-pill-btn" data-tab="tab-backup-restore">3. Backup &amp; Pemulihan</button>
        <button class="tab-pill-btn" data-tab="tab-aliran-po">4. Aliran Data Pembelian</button>
        <button class="tab-pill-btn" data-tab="tab-kegagalan">5. Potensi Kegagalan</button>
        <button class="tab-pill-btn" data-tab="tab-simulasi-gangguan">6. Simulasi Gangguan &amp; Audit Log</button>
    </div>

    <div id="tab-diagram-3tier" class="tab-content-panel">
        <div class="alert alert-info"><strong>1. Hubungan pengguna, antarmuka aplikasi, server aplikasi, database, dan sistem pencadangan.</strong></div>
        <?= diagramImg('p2_arsitektur.png', 'Gambar 4. Arsitektur teknis ERP tiga lapis untuk 100 pengguna', 'Diagram arsitektur teknis ERP') ?>
        <div class="grid-2">
            <div class="card" style="margin-bottom:0;background:var(--bg-card-subtle)">
                <div style="font-weight:700;margin-bottom:6px">Alasan pemilihan rancangan</div>
                <ul style="font-size:.8rem;color:var(--text-muted);line-height:1.7;padding-left:16px">
                    <li><strong>Tiga lapis:</strong> pengguna tidak pernah langsung ke database; semua lewat server aplikasi yang memeriksa login dan hak akses.</li>
                    <li><strong>Dua server aplikasi + load balancer:</strong> 100 pengguna bersamaan butuh kapasitas dan tidak boleh bergantung pada satu server.</li>
                    <li><strong>Database + replika (RAID 10):</strong> data keuangan tidak boleh hilang; replika siap mengambil alih.</li>
                    <li><strong>Backup 3-2-1:</strong> melindungi dari kerusakan disk, kesalahan pengguna, ransomware, dan bencana lokasi.</li>
                    <li><strong>VPN + MFA, HTTPS:</strong> pegawai di luar kantor tetap aman.</li>
                </ul>
            </div>
            <div class="card" style="margin-bottom:0;background:var(--bg-card-subtle)">
                <div style="font-weight:700;margin-bottom:6px">Mengapa pengguna gagal akses saat server aplikasi gangguan?</div>
                <p style="font-size:.8rem;color:var(--text-muted);line-height:1.6">Semua permintaan harus melewati server aplikasi untuk login, otorisasi peran, dan logika bisnis. Bila server aplikasi mati dan tidak ada cadangan, tidak ada yang menerjemahkan permintaan ke database (<em>single point of failure</em>). Karena itu dipasang server standby dan load balancer.</p>
            </div>
        </div>
    </div>

    <div id="tab-matriks-rbac" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>2. Kebutuhan keamanan akses setiap departemen &amp; matriks hak akses pengguna.</strong></div>
        <div class="table-responsive" style="margin-bottom:16px"><table class="table-custom">
            <thead><tr><th>Departemen</th><th>Data yang diakses</th><th>Kebutuhan keamanan</th></tr></thead><tbody>
            <tr><td>Penjualan</td><td>Pesanan, pelanggan, stok siap jual</td><td>Hanya buat/ubah pesanan sendiri; tidak boleh mengubah harga pokok atau jurnal.</td></tr>
            <tr><td>Gudang</td><td>Stok, penerimaan, pengiriman</td><td>Hanya input penerimaan/pengiriman terhadap dokumen resmi (PO/DO); tidak boleh melihat laporan keuangan.</td></tr>
            <tr><td>Produksi</td><td>BOM, Work Order, pemakaian bahan</td><td>Ubah jadwal produksi; hanya lihat stok; membuat PR bahan, tidak membuat PO.</td></tr>
            <tr><td>Pembelian</td><td>PR, PO, pemasok</td><td>Buat/ubah PO; PO di atas Rp 5 juta menunggu persetujuan Pimpinan; tidak boleh memposting pembayaran.</td></tr>
            <tr><td>Keuangan</td><td>Jurnal, buku besar, piutang/utang</td><td>Akses terbatas &amp; paling sensitif; hanya Keuangan yang posting jurnal; wajib audit trail.</td></tr>
            </tbody></table></div>
    <div id="tab-matriks-rbac" class="tab-content-panel" style="display: none;">
        <div class="alert alert-info">
            <strong>Prinsip Least Privilege & Separation of Duties (SoD):</strong> Setiap peran dibatasi hanya pada wewenang tugasnya. Staf non-keuangan dilarang keras memposting jurnal akuntansi.
        </div>

        <div style="margin-bottom: 10px; font-size: 0.78rem; color: var(--text-dim);">
            <strong>Keterangan Wewenang:</strong> L = Lihat &bull; B = Buat &bull; E = Edit &bull; S = Setujui &bull; P = Posting Jurnal &bull; &mdash; = Tanpa Akses
        </div>

        <div class="table-responsive">
            <table class="table-custom text-center">
                <thead>
                    <tr>
                        <th class="text-left">Peran / Jabatan (Role)</th>
                        <th>Penjualan</th>
                        <th>Gudang</th>
                        <th>Produksi</th>
                        <th>Pembelian</th>
                        <th>Keuangan</th>
                        <th>Pengaturan Sistem</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-left"><strong>Staf Penjualan</strong></td>
                        <td><span class="badge badge-success">L, B, E</span></td>
                        <td><span class="badge">L (Cek Stok)</span></td>
                        <td><span class="badge">L (Status)</span></td>
                        <td>&mdash;</td>
                        <td>&mdash;</td>
                        <td>&mdash;</td>
                    </tr>
                    <tr>
                        <td class="text-left"><strong>Staf Gudang</strong></td>
                        <td><span class="badge">L (Kirim DO)</span></td>
                        <td><span class="badge badge-success">L, B, E (GR)</span></td>
                        <td><span class="badge">L</span></td>
                        <td><span class="badge">L (PO Masuk)</span></td>
                        <td>&mdash;</td>
                        <td>&mdash;</td>
                    </tr>
                    <tr>
                        <td class="text-left"><strong>Staf Produksi</strong></td>
                        <td>&mdash;</td>
                        <td><span class="badge">L</span></td>
                        <td><span class="badge badge-success">L, B, E</span></td>
                        <td><span class="badge badge-success">L, B (PR Bahan)</span></td>
                        <td>&mdash;</td>
                        <td>&mdash;</td>
                    </tr>
                    <tr>
                        <td class="text-left"><strong>Staf Pembelian</strong></td>
                        <td>&mdash;</td>
                        <td><span class="badge">L</span></td>
                        <td><span class="badge">L</span></td>
                        <td><span class="badge badge-success">L, B, E (PO)</span></td>
                        <td><span class="badge">L (Status Bayar)</span></td>
                        <td>&mdash;</td>
                    </tr>
                    <tr>
                        <td class="text-left"><strong>Staf Keuangan</strong></td>
                        <td><span class="badge">L (Faktur Jual)</span></td>
                        <td><span class="badge">L</span></td>
                        <td>&mdash;</td>
                        <td><span class="badge">L (Faktur Beli)</span></td>
                        <td><span class="badge badge-danger">L, B, E, P</span></td>
                        <td>&mdash;</td>
                    </tr>
                    <tr>
                        <td class="text-left"><strong>Pimpinan / Direktur</strong></td>
                        <td><span class="badge">L</span></td>
                        <td><span class="badge">L</span></td>
                        <td><span class="badge">L</span></td>
                        <td><span class="badge badge-primary">L, S (PO &gt; 5Jt)</span></td>
                        <td><span class="badge badge-primary">L, S (Lap. Keu)</span></td>
                        <td>&mdash;</td>
                    </tr>
                    <tr>
                        <td class="text-left"><strong>Administrator IT</strong></td>
                        <td>&mdash;</td>
                        <td>&mdash;</td>
                        <td>&mdash;</td>
                        <td>&mdash;</td>
                        <td>&mdash;</td>
                        <td><span class="badge badge-warning">L, B, E (User/DB)</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <div id="tab-backup-restore" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>3. Pencadangan database dan prosedur pemulihan data.</strong></div>
    <div id="tab-backup-restore" class="tab-content-panel" style="display: none;">
        <div class="grid-2">
            <div class="card" style="margin-bottom:0; background: var(--bg-card-subtle);">
                <div style="font-size: 0.9rem; font-weight: 600; margin-bottom: 10px;">Strategi Pencadangan 3-2-1</div>
                <ul style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.7; padding-left: 16px;">
                    <li><strong>3 Salinan Data:</strong> 1 database produksi aktif, 1 salinan lokal di storage NAS, 1 salinan offsite di cloud.</li>
                    <li><strong>2 Media Berbeda:</strong> SSD Server Array RAID-10 dan Cloud Storage Terenkripsi.</li>
                    <li><strong>1 Salinan di Luar Lokasi:</strong> Pusat data terpisah untuk mengantisipasi bencana fisik gedung.</li>
                    <li><strong>Jadwal:</strong> Full Backup tiap Minggu malam, Differential Backup harian, dan log transaksi tiap 15 menit.</li>
                    <li><strong>Target Pemulihan:</strong> RPO = 15 menit; RTO = 4 jam.</li>
                </ul>
            </div>

            <div class="card" style="margin-bottom:0; background: var(--bg-card-subtle);">
                <div style="font-size: 0.9rem; font-weight: 600; margin-bottom: 10px;">Prosedur Pemulihan (Disaster Recovery)</div>
                <ol style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.7; padding-left: 16px;">
                    <li>Hentikan transaksi dan bekukan sesi pengguna agar integritas data terjaga.</li>
                    <li>Identifikasi sumber kerusakan (kerusakan disk, korupsi data, atau malware).</li>
                    <li>Pulihkan full backup terakhir ke server pengganti.</li>
                    <li>Terapkan differential backup dan roll-forward log transaksi hingga titik waktu sebelum insiden.</li>
                    <li>Verifikasi integritas saldo buku besar, neraca saldo, dan saldo fisik gudang.</li>
                    <li>Buka kembali akses bertahap dimulai dari staf kunci.</li>
                </ol>
            </div>
        </div>
    </div>
    </div>

    <div id="tab-aliran-po" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>4. Aliran data saat pengguna melakukan transaksi pembelian.</strong></div>
        <?= diagramImg('p2_aliran_pembelian.png', 'Gambar 5. Aliran data transaksi pembelian dari pengguna sampai database', 'Sequence diagram transaksi pembelian') ?>
    </div>

    <div id="tab-kegagalan" class="tab-content-panel" style="display:none;">
        <div class="alert alert-warning"><strong>5. Potensi kegagalan teknis dan dampaknya terhadap operasional</strong> (minimal tiga; di sini lima).</div>
        <div class="table-responsive"><table class="table-custom">
            <thead><tr><th>No</th><th>Potensi kegagalan</th><th>Dampak operasional</th><th>Pencegahan</th></tr></thead><tbody>
            <tr><td>1</td><td>Server aplikasi mati (SPOF)</td><td>100 pengguna tidak bisa transaksi; pengiriman &amp; produksi tertunda.</td><td>Server standby + load balancer, monitoring.</td></tr>
            <tr><td>2</td><td>Database rusak / disk gagal</td><td>Seluruh data transaksi tidak tersedia; laporan keuangan terhenti.</td><td>RAID 10, replika, backup 3-2-1.</td></tr>
            <tr><td>3</td><td>Jaringan / internet terputus</td><td>Pegawai jarak jauh tidak bisa akses; transaksi gagal terkirim.</td><td>LAN internal, ISP cadangan, retry transaksi.</td></tr>
            <tr><td>4</td><td>Backup gagal / tidak teruji</td><td>Data hilang permanen saat bencana.</td><td>Alarm kegagalan backup, uji restore berkala.</td></tr>
            <tr><td>5</td><td>Akses tidak sah / hak akses terlalu luas</td><td>Kebocoran &amp; manipulasi data keuangan.</td><td>RBAC, MFA, audit log, least privilege.</td></tr>
            </tbody></table></div>
    </div>

    <div id="tab-simulasi-gangguan" class="tab-content-panel" style="display:none;">
        <div class="alert alert-info"><strong>Simulasi gangguan</strong> &mdash; dosen memilih satu kondisi; sistem mencatat tindakan penanganannya ke audit log. Simulasi ini aman: tidak mematikan server dan tidak menghapus database.</div>
        <div class="table-responsive" style="margin-bottom:16px"><table class="table-custom">
            <thead><tr><th>Kondisi</th><th>Tindakan penanganan</th><th></th></tr></thead><tbody>
            <?php foreach ($P2_INCIDENTS as $id => $inc): ?>
            <tr><td><strong><?= $id ?>. <?= h($inc[0]) ?></strong></td><td style="font-size:.8rem"><?= h($inc[1]) ?></td>
            <td><form action="api/p2_actions.php" method="POST"><input type="hidden" name="incident" value="<?= $id ?>"><button class="btn btn-outline btn-sm" type="submit">Simulasikan</button></form></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <div class="grid-2">
            <!-- Form Uji Akses -->
            <div class="card" style="margin-bottom:0; background: var(--bg-card-subtle);">
                <div style="font-size: 0.9rem; font-weight: 600; margin-bottom: 6px;">
                    Pengujian Kontrol Hak Akses (RBAC Enforcement)
                </div>
                <p style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 14px;">
                    Simulasikan apakah sistem menolak akses pengguna yang tidak memiliki wewenang pada modul tertentu.
                </p>

                <form action="api/scm_actions.php" method="POST">
                    <input type="hidden" name="action" value="test_access_control">

                    <div style="margin-bottom: 16px;">
                        <label class="form-label">Pilih Skenario Uji Cepat:</label>
                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="setRbacTest('staf_gudang', 'financial_report')" title="Uji penolakan akses">
                                🚫 Gudang &rarr; Keuangan (Harus Ditolak)
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="setRbacTest('staf_keuangan', 'financial_report')" title="Uji akses sah">
                                ✅ Keuangan &rarr; Keuangan (Diizinkan)
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="setRbacTest('staf_penjualan', 'approve_po')" title="Uji pelanggaran wewenang">
                                🚫 Penjualan &rarr; Approve PO (Ditolak)
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Peran Pengguna (Role):</label>
                        <select name="role" id="rbac-role" class="form-control">
                            <option value="staf_gudang">Staf Gudang (Warehouse)</option>
                            <option value="staf_penjualan">Staf Penjualan (Marketing)</option>
                            <option value="staf_produksi">Staf Produksi (Manufacturing)</option>
                            <option value="staf_keuangan">Staf Keuangan (Finance)</option>
                            <option value="pimpinan">Pimpinan / Direktur</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Menu yang Ingin Diakses:</label>
                        <select name="target" id="rbac-target" class="form-control">
                            <option value="financial_report">Laporan Keuangan & Neraca Saldo (Sensitif)</option>
                            <option value="approve_po">Persetujuan Pesanan Pembelian / Approval PO</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 6px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>Jalankan Pengujian Akses & Catat Audit Log</span>
                    </button>
                </form>

                <script>
                function setRbacTest(role, target) {
                    const roleSelect = document.getElementById('rbac-role');
                    const targetSelect = document.getElementById('rbac-target');
                    if (roleSelect && targetSelect) {
                        roleSelect.value = role;
                        targetSelect.value = target;
                    }
                }
                </script>
            </div>

            <!-- Tabel Log Audit -->
            <div class="card" style="margin-bottom:0; background: var(--bg-card-subtle);">
                <div class="card-header" style="margin-bottom:8px; padding-bottom:8px;">
                    <div style="font-size: 0.9rem; font-weight: 600;">Log Keamanan & Audit Trail</div>
                    <span class="badge">Tersimpan di DB</span>
                </div>

                <div class="table-responsive" style="max-height: 250px; overflow-y:auto;">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Role</th>
                                <th>Target</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($auditLogs)): ?>
                                <tr><td colspan="4" class="text-center">Belum ada catatan audit.</td></tr>
                            <?php else: ?>
                                <?php foreach ($auditLogs as $log): ?>
                                    <tr>
                                        <td style="font-size: 0.72rem; color: var(--text-dim);"><?= date('H:i:s', strtotime($log['timestamp'])) ?></td>
                                        <td><strong><?= htmlspecialchars($log['role']) ?></strong></td>
                                        <td><span class="font-mono" style="font-size:0.75rem;"><?= htmlspecialchars($log['resource']) ?></span></td>
                                        <td>
                                            <?php if ($log['status'] === 'ALLOWED'): ?>
                                                <span class="badge badge-success">DIIZINKAN</span>
                                            <?php elseif ($log['status'] === 'BLOCKED'): ?>
                                                <span class="badge badge-danger">DITOLAK</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">DITANGANI</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
