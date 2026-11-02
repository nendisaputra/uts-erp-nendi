<?php
// includes/mrp.php - Perhitungan kebutuhan material (MRP) dari data di database
function mrpCompute($pdo) {
    $sc = $pdo->query("SELECT * FROM mrp_scenarios WHERE id=1")->fetch();
    $bom = []; $st = $pdo->prepare("SELECT component_code, quantity FROM bom WHERE parent_code=? ORDER BY id"); $st->execute([$sc['product_code']]);
    foreach ($st->fetchAll() as $r) $bom[$r['component_code']] = (int)$r['quantity'];
    $out = ['sc' => $sc, 'deadline' => (int)$sc['deadline_days'], 'materials' => []];
    foreach (['RAW-A' => 'a', 'RAW-B' => 'b'] as $code => $k) {
        $item = $pdo->prepare("SELECT * FROM items WHERE code=?"); $item->execute([$code]); $item = $item->fetch();
        $per = $bom[$code] ?? 0; $gross = $per * (int)$sc['target_qty'];
        $stock = (int)$sc["stock_$k"]; $on = (int)$sc["onorder_$k"]; $lead = (int)$sc["lead_$k"];
        $net = max(0, $gross - $stock - $on);
        $late = $net > 0 ? max(0, $lead - $out['deadline']) : 0;
        $out['materials'][$code] = ['code'=>$code,'name'=>$item['name'],'per'=>$per,'gross'=>$gross,'stock'=>$stock,'onorder'=>$on,'net'=>$net,'net_if_po_late'=>max(0, $gross - $stock),'lead'=>$lead,'late'=>$late,'vendor'=>$item['vendor'],'price'=>(float)$item['unit_price'],'max_units'=>$per ? intdiv($stock, $per) : 0];
    }
    return $out;
}
