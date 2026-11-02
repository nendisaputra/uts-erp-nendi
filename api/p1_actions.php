<?php
// api/p1_actions.php - Praktikum 1: simulasi transaksi pesanan pelanggan end-to-end (tersimpan di database)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
$pdo = getDbConnection();
$action = $_POST['action'] ?? '';

function p1Reset($pdo) {
    $pdo->exec("DELETE FROM sales_orders");
    $pdo->exec("DELETE FROM production_orders");
    $pdo->exec("DELETE FROM purchase_requisitions WHERE module='P1'");
    $ids = $pdo->query("SELECT id FROM journal_entries WHERE module='P1'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) { $pdo->exec("DELETE FROM journal_details WHERE journal_id=" . (int)$id); $pdo->exec("DELETE FROM journal_entries WHERE id=" . (int)$id); }
    $st = $pdo->prepare("UPDATE items SET stock_qty=? WHERE code=?");
    foreach ([['K-01',40],['MAT-A',150],['MAT-B',50]] as $r) $st->execute([$r[1], $r[0]]);
}

try {
    if ($action === 'reset_p1') { p1Reset($pdo); backWith('praktikum1.php', 'Simulasi Praktikum 1 dikembalikan ke kondisi awal.', 'info', 'tab-simulasi-pesanan'); }
    if ($action === 'run_order') {
        if ($pdo->query("SELECT COUNT(*) FROM sales_orders")->fetchColumn() > 0) backWith('praktikum1.php', 'Pesanan sudah diproses. Klik "Reset simulasi" untuk mencoba nilai lain.', 'warning', 'tab-simulasi-pesanan');
        $qty = max(1, (int)($_POST['qty'] ?? 100)); $price = max(1, (float)($_POST['price'] ?? 150000));
        $stock = []; foreach ($pdo->query("SELECT code, stock_qty FROM items WHERE code IN ('K-01','MAT-A','MAT-B')") as $r) $stock[$r['code']] = (int)$r['stock_qty'];
        $bom = []; foreach ($pdo->query("SELECT component_code, quantity FROM bom WHERE parent_code='K-01'") as $r) $bom[$r['component_code']] = (int)$r['quantity'];
        $produce = max(0, $qty - $stock['K-01']);
        $pdo->beginTransaction();
        $so = 'SO-' . date('ymd') . '-001'; $total = $qty * $price;
        insertRow($pdo, 'sales_orders', ['so_no'=>$so,'customer'=>'PT Sinar Otomotif','item_code'=>'K-01','qty'=>$qty,'unit_price'=>$price,'total'=>$total,'status'=>'DELIVERED']);
        if ($produce > 0) insertRow($pdo, 'production_orders', ['wo_no'=>'WO-' . date('ymd') . '-001','so_no'=>$so,'item_code'=>'K-01','qty'=>$produce,'status'=>'COMPLETED']);
        $upd = $pdo->prepare("UPDATE items SET stock_qty=? WHERE code=?");
        $n = 1;
        foreach ($bom as $comp => $per) {
            $need = $produce * $per; $short = max(0, $need - $stock[$comp]);
            if ($short > 0) insertRow($pdo, 'purchase_requisitions', ['pr_no'=>'PR-P1-' . $n++,'item_code'=>$comp,'qty'=>$short,'requester'=>'Sistem MRP','department'=>'Produksi','need_date'=>date('Y-m-d', strtotime('+5 days')),'status'=>'APPROVED','module'=>'P1']);
            $upd->execute([$stock[$comp] + $short - $need, $comp]);   // bahan kurang dibeli (PR) lalu dipakai produksi
        }
        $upd->execute([$stock['K-01'] + $produce - $qty, 'K-01']);   // stok jadi + hasil produksi - pengiriman
        postJournal($pdo, 'JRN-P1-' . date('ymd'), date('Y-m-d'), "Penjualan kredit $so (PT Sinar Otomotif)", "DO/$so", 'P1', 'Sistem ERP', [['1-1100',$total,0],['4-1000',0,$total]]);
        $pdo->commit();
        backWith('praktikum1.php', "Pesanan $so diproses: stok, produksi, pembelian, dan jurnal tersimpan di database.", 'success', 'tab-simulasi-pesanan');
    }
    backWith('praktikum1.php', 'Aksi tidak dikenal.', 'danger');
} catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); die('Kesalahan: ' . h($e->getMessage())); }
