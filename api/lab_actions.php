<?php
// api/lab_actions.php - Praktikum 3: permintaan bahan lab, jalur persetujuan, dan evaluasi penawaran baru
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
$pdo = getDbConnection();
$action = $_POST['action'] ?? '';
const LIMIT_KEPALA_LAB = 5000000; const TOLERANSI_PCT = 5.0;
try {
    if ($action === 'create_request') {
        $item = trim($_POST['item_name'] ?? ''); $qty = (int)($_POST['qty'] ?? 0); $price = (float)($_POST['price'] ?? 0); $who = trim($_POST['requester'] ?? '');
        if ($item === '' || $who === '' || $qty < 1 || $price <= 0) backWith('praktikum3.php', 'Aturan 1: identitas pengaju, barang, jumlah, dan harga wajib diisi.', 'danger', 'tab-data-permintaan');
        $total = $qty * $price; $level = $total > LIMIT_KEPALA_LAB ? 'PIMPINAN' : 'KEPALA_LAB';
        $no = 'PR-LAB-' . str_pad((string)($pdo->query("SELECT COUNT(*) FROM lab_purchase_requests")->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
        insertRow($pdo, 'lab_purchase_requests', ['request_no'=>$no,'item_name'=>$item,'qty'=>$qty,'estimated_price'=>$price,'total_estimated'=>$total,'requester_lecturer'=>$who,'urgency'=>$_POST['urgency'] === 'EMERGENCY_24H' ? 'EMERGENCY_24H' : 'NORMAL','status'=>'WAITING_APPROVAL','approval_level'=>$level,'notes'=>'Otomatis diarahkan ke ' . ($level === 'PIMPINAN' ? 'Pimpinan (nilai > Rp 5.000.000)' : 'Kepala Lab (nilai <= Rp 5.000.000)')]);
        backWith('praktikum3.php', "Permintaan $no (" . rp($total) . ") dibuat dan diarahkan ke " . ($level === 'PIMPINAN' ? 'Pimpinan' : 'Kepala Lab') . '.', 'success', 'tab-data-permintaan');
    }
    if ($action === 'approve') {
        $id = (int)$_POST['id'];
        $r = $pdo->prepare("SELECT * FROM lab_purchase_requests WHERE id=?"); $r->execute([$id]); $row = $r->fetch();
        if (!$row) backWith('praktikum3.php', 'Data tidak ditemukan.', 'danger', 'tab-data-permintaan');
        $by = $row['approval_level'] === 'PIMPINAN' ? 'Pimpinan' : 'Kepala Lab';
        $pdo->prepare("UPDATE lab_purchase_requests SET status='APPROVED', approved_by=?, notes=? WHERE id=?")->execute([$by, "Disetujui $by", $id]);
        backWith('praktikum3.php', "{$row['request_no']} disetujui oleh $by.", 'success', 'tab-data-permintaan');
    }
    if ($action === 'revise_quote') {
        $id = (int)$_POST['id']; $new = (float)($_POST['new_total'] ?? 0);
        $r = $pdo->prepare("SELECT * FROM lab_purchase_requests WHERE id=?"); $r->execute([$id]); $row = $r->fetch();
        if (!$row || $new <= 0) backWith('praktikum3.php', 'Data / nilai penawaran tidak valid.', 'danger', 'tab-data-permintaan');
        $base = (float)$row['total_estimated']; $pct = ($new - $base) / $base * 100;
        if ($new > LIMIT_KEPALA_LAB) { $status = 'ESCALATED'; $level = 'PIMPINAN'; $note = sprintf('Penawaran %s (%+.2f%%) melewati plafon Kepala Lab: persetujuan lama batal, wajib disetujui Pimpinan.', rp($new), $pct); }
        elseif ($pct > TOLERANSI_PCT) { $status = 'REAPPROVAL'; $level = 'KEPALA_LAB'; $note = sprintf('Kenaikan %+.2f%% > 5%%: persetujuan ulang Kepala Lab.', $pct); }
        else { $status = $row['status']; $level = $row['approval_level']; $note = sprintf('Kenaikan %+.2f%% <= 5%%: persetujuan awal tetap berlaku, Kepala Lab diberi notifikasi.', $pct); }
        $pdo->prepare("UPDATE lab_purchase_requests SET actual_quotation=?, status=?, approval_level=?, notes=? WHERE id=?")->execute([$new, $status, $level, $note, $id]);
        backWith('praktikum3.php', $note, $status === 'APPROVED' ? 'success' : ($status === 'ESCALATED' ? 'danger' : 'warning'), 'tab-data-permintaan');
    }
    backWith('praktikum3.php', 'Aksi tidak dikenal.', 'danger');
} catch (Exception $e) { die('Kesalahan: ' . h($e->getMessage())); }
