<?php
// api/scm_actions.php - Praktikum 5 (SCM/MRP) + uji akses RBAC Praktikum 2
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/mrp.php';

$pdo = getDbConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'update_scenario') {
        $f = ['target_qty','deadline_days','stock_a','onorder_a','lead_a','stock_b','onorder_b','lead_b'];
        $set = []; $val = [];
        foreach ($f as $k) { $set[] = "$k=?"; $val[] = max(0, (int)($_POST[$k] ?? 0)); }
        $pdo->prepare("UPDATE mrp_scenarios SET " . implode(',', $set) . " WHERE id=1")->execute($val);
        backWith('praktikum5.php', 'Skenario MRP diperbarui dan dihitung ulang dari database.', 'success', 'tab-mrp-kalkulasi');
    }
    if ($action === 'generate_mrp_pr') {
        if ($pdo->query("SELECT COUNT(*) FROM purchase_requisitions WHERE module='P5'")->fetchColumn() > 0)
            backWith('praktikum5.php', 'PR hasil MRP sudah diterbitkan. Reset database untuk mengulang.', 'warning', 'tab-simulasi-pr-po');
        $m = mrpCompute($pdo);
        $n = 1;
        foreach ($m['materials'] as $mat) {
            if ($mat['net'] <= 0) continue;
            $pr = 'PR-' . str_pad((string)$n++, 3, '0', STR_PAD_LEFT);
            insertRow($pdo, 'purchase_requisitions', ['pr_no'=>$pr,'item_code'=>$mat['code'],'qty'=>$mat['net'],'requester'=>'Sistem MRP','department'=>'Perencanaan Produksi','need_date'=>date('Y-m-d', strtotime('+' . $m['deadline'] . ' days')),'status'=>'APPROVED','module'=>'P5']);
            insertRow($pdo, 'purchase_orders', ['po_no'=>'PO-MRP-' . substr($mat['code'], -1),'item_code'=>$mat['code'],'vendor_name'=>$mat['vendor'],'qty_ordered'=>$mat['net'],'qty_received'=>0,'unit_price'=>$mat['price'],'lead_time_days'=>$mat['lead'],'order_date'=>date('Y-m-d'),'expected_date'=>date('Y-m-d', strtotime('+' . $mat['lead'] . ' days')),'status'=>'OPEN','source'=>'MRP']);
        }
        backWith('praktikum5.php', 'MRP menerbitkan PR & PO sesuai kebutuhan bersih.', 'success', 'tab-simulasi-pr-po');
    }
    if ($action === 'receive_goods') {
        $po = $pdo->prepare("SELECT * FROM purchase_orders WHERE po_no=?"); $po->execute([$_POST['po_no'] ?? '']); $po = $po->fetch();
        if (!$po) backWith('praktikum5.php', 'PO tidak ditemukan. Terbitkan PR/PO lebih dulu.', 'warning', 'tab-penerimaan-parsial');
        $remain = $po['qty_ordered'] - $po['qty_received'];
        $qty = (int)($_POST['qty'] ?? 0);
        if ($qty < 1 || $qty > $remain) backWith('praktikum5.php', "Jumlah terima harus 1 s/d sisa PO ($remain).", 'danger', 'tab-penerimaan-parsial');
        $received = $po['qty_received'] + $qty;
        $status = $received >= $po['qty_ordered'] ? 'CLOSED' : 'PARTIAL';
        $pdo->prepare("UPDATE purchase_orders SET qty_received=?, status=? WHERE id=?")->execute([$received, $status, $po['id']]);
        $pdo->prepare("UPDATE items SET stock_qty = stock_qty + ? WHERE code=?")->execute([$qty, $po['item_code']]);
        $amt = $qty * $po['unit_price'];
        postJournal($pdo, 'GR-' . $po['po_no'] . '-' . $received, date('Y-m-d'), "Penerimaan $qty unit {$po['item_code']} terhadap {$po['po_no']}", 'GR/' . $po['po_no'], 'P5', 'Gudang & Sistem', [['1-1200',$amt,0],['2-1000',0,$amt]]);
        backWith('praktikum5.php', "Diterima $qty unit {$po['item_code']}. Status PO: $status, sisa " . ($po['qty_ordered'] - $received) . ' unit. Stok & jurnal diperbarui.', 'success', 'tab-penerimaan-parsial');
    }
    if ($action === 'test_access_control') {
        $role = $_POST['role'] ?? 'staf_gudang'; $target = $_POST['target'] ?? 'financial_report';
        $allowed = $target === 'financial_report' ? in_array($role, ['staf_keuangan','pimpinan']) : in_array($role, ['pimpinan','kepala_lab']);
        $notes = $allowed ? "Akses diberikan: peran $role berwenang." : "Akses DITOLAK: peran $role tidak berwenang (prinsip least privilege / separation of duties).";
        insertRow($pdo, 'security_audit_logs', ['username'=>$role,'role'=>ucwords(str_replace('_',' ',$role)),'action_attempted'=>'ACCESS_TEST','resource'=>$target,'status'=>$allowed ? 'ALLOWED' : 'BLOCKED','notes'=>$notes]);
        backWith('praktikum2.php', $notes, $allowed ? 'success' : 'danger', 'tab-simulasi-gangguan');
    }
    backWith('praktikum5.php', 'Aksi tidak dikenal.', 'danger');
} catch (Exception $e) { die('Kesalahan operasi SCM: ' . h($e->getMessage())); }
