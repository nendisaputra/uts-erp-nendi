<?php
// api/finance_actions.php - Praktikum 4: input transaksi, simulasi kesalahan duplikat, jurnal pembalik
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
$pdo = getDbConnection();
$action = $_POST['action'] ?? '';
$P = 'praktikum4.php';
try {
    if ($action === 'duplicate_payment') {
        if ($pdo->query("SELECT COUNT(*) FROM journal_entries WHERE entry_no='TRX-002-DUP'")->fetchColumn() > 0)
            backWith($P, 'Transaksi duplikat sudah ada. Lanjutkan dengan jurnal pembalik.', 'warning', 'tab-analisis-koreksi');
        postJournal($pdo, 'TRX-002-DUP', date('Y-m-04'), '[KESALAHAN] Pembayaran utang pemasok tercatat dua kali (duplikat TRX-002)', 'BK-001/VOUCHER-PAY (DUPLIKAT)', 'P4', 'Staf Keuangan', [['2-1000',3000000,0],['1-1000',0,3000000]]);
        backWith($P, 'Kesalahan disimulasikan: pembayaran Rp 3.000.000 tercatat dua kali. Lihat perubahan saldo Kas & Utang Usaha.', 'danger', 'tab-analisis-koreksi');
    }
    if ($action === 'execute_reversal') {
        $t = $pdo->prepare("SELECT id, is_reversed FROM journal_entries WHERE entry_no='TRX-002-DUP'"); $t->execute(); $t = $t->fetch();
        if (!$t) backWith($P, 'Jurnal duplikat tidak ditemukan.', 'danger', 'tab-analisis-koreksi');
        if ($t['is_reversed']) backWith($P, 'Jurnal ini sudah pernah dibalik.', 'warning', 'tab-analisis-koreksi');
        $rid = postJournal($pdo, 'REV-TRX-002-DUP', date('Y-m-d'), '[JURNAL PEMBALIK] Koreksi pembayaran ganda ref. TRX-002-DUP (data asli tidak dihapus)', 'MEMO-KOREKSI/01', 'P4', 'Akuntan Senior', [['1-1000',3000000,0],['2-1000',0,3000000]]);
        $pdo->prepare("UPDATE journal_entries SET is_reversed=1, reversed_by_id=? WHERE id=?")->execute([$rid, $t['id']]);
        backWith($P, 'Jurnal pembalik REV-TRX-002-DUP dibuat. Saldo kembali benar, jejak audit tetap utuh.', 'success', 'tab-analisis-koreksi');
    }
    if ($action === 'create_custom_journal') {
        $dr = $_POST['debit_account'] ?? ''; $cr = $_POST['credit_account'] ?? ''; $amt = (float)($_POST['amount'] ?? 0); $desc = trim($_POST['description'] ?? '');
        $valid = $pdo->query("SELECT code FROM coa")->fetchAll(PDO::FETCH_COLUMN);
        if ($desc === '' || $amt <= 0 || $dr === $cr || !in_array($dr, $valid) || !in_array($cr, $valid))
            backWith($P, 'Isi keterangan, jumlah > 0, dan pilih akun debit & kredit yang berbeda.', 'danger', 'tab-input-transaksi');
        $no = 'TRX-' . str_pad((string)($pdo->query("SELECT COUNT(*) FROM journal_entries WHERE module='P4'")->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
        $tries = 0; while ($pdo->query("SELECT COUNT(*) FROM journal_entries WHERE entry_no='$no'")->fetchColumn() > 0) $no = 'TRX-X' . (++$tries) . substr((string)time(), -4);
        postJournal($pdo, $no, date('Y-m-d'), $desc, trim($_POST['source_doc'] ?? '') ?: 'INPUT-MANUAL', 'P4', 'Mahasiswa (input manual)', [[$dr,$amt,0],[$cr,0,$amt]]);
        backWith($P, "Jurnal $no sebesar " . rp($amt) . ' tersimpan.', 'success', 'tab-jurnal-umum');
    }
    backWith($P, 'Aksi tidak dikenal.', 'danger');
} catch (Exception $e) { die('Kesalahan operasi keuangan: ' . h($e->getMessage())); }
