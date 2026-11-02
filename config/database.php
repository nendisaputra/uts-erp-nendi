<?php
// config/database.php - Koneksi PDO (MySQL / SQLite), pembuatan tabel, data awal soal, dan reset.

function dbConfig() { static $c = null; if ($c === null) $c = require __DIR__ . '/config.php'; return $c; }
function dbDriver() { return dbConfig()['driver'] === 'mysql' ? 'mysql' : 'sqlite'; }

function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $cfg = dbConfig();
    try {
        if (dbDriver() === 'mysql') {
            $m = $cfg['mysql'];
            $pdo = new PDO("mysql:host={$m['host']};port={$m['port']};dbname={$m['name']};charset=utf8mb4", $m['user'], $m['pass']);
        } else {
            $dir = __DIR__ . '/../database';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $pdo = new PDO('sqlite:' . $dir . '/erp_praktikum.db');
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $exists = dbDriver() === 'mysql'
            ? $pdo->query("SHOW TABLES LIKE 'coa'")->fetchAll()
            : $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='coa'")->fetchAll();
        if (empty($exists)) { initDatabase($pdo); }
        return $pdo;
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . htmlspecialchars($e->getMessage()));
    }
}

function schemaStatements($forceMysql = false) {
    $my = $forceMysql || dbDriver() === 'mysql';
    $pk = $my ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $eng = $my ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
    return [
"CREATE TABLE coa (
    code VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    normal_balance CHAR(1) NOT NULL,
    initial_balance DECIMAL(15,2) DEFAULT 0
)$eng",
"CREATE TABLE journal_entries (
    id $pk,
    entry_no VARCHAR(40) NOT NULL UNIQUE,
    entry_date DATE NOT NULL,
    description TEXT NOT NULL,
    source_doc VARCHAR(80),
    module VARCHAR(10) NOT NULL DEFAULT 'P4',
    is_reversed TINYINT DEFAULT 0,
    reversed_by_id INT NULL,
    created_by VARCHAR(60) DEFAULT 'Sistem ERP',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)$eng",
"CREATE TABLE journal_details (
    id $pk,
    journal_id INT NOT NULL,
    account_code VARCHAR(20) NOT NULL,
    debit DECIMAL(15,2) DEFAULT 0,
    credit DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (journal_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_code) REFERENCES coa(code)
)$eng",
"CREATE TABLE items (
    code VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL,
    stock_qty INT NOT NULL DEFAULT 0,
    unit VARCHAR(20) NOT NULL DEFAULT 'Unit',
    lead_time_days INT DEFAULT 0,
    unit_price DECIMAL(15,2) DEFAULT 0,
    vendor VARCHAR(100) NULL
)$eng",
"CREATE TABLE bom (
    id $pk,
    parent_code VARCHAR(20) NOT NULL,
    component_code VARCHAR(20) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (parent_code) REFERENCES items(code),
    FOREIGN KEY (component_code) REFERENCES items(code)
)$eng",
"CREATE TABLE purchase_requisitions (
    id $pk,
    pr_no VARCHAR(30) NOT NULL UNIQUE,
    item_code VARCHAR(20) NOT NULL,
    qty INT NOT NULL,
    requester VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    need_date DATE,
    status VARCHAR(30) DEFAULT 'PENDING',
    module VARCHAR(10) NOT NULL DEFAULT 'P5',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)$eng",
"CREATE TABLE purchase_orders (
    id $pk,
    po_no VARCHAR(30) NOT NULL UNIQUE,
    item_code VARCHAR(20) NOT NULL,
    vendor_name VARCHAR(100) NOT NULL,
    qty_ordered INT NOT NULL,
    qty_received INT NOT NULL DEFAULT 0,
    unit_price DECIMAL(15,2) DEFAULT 0,
    lead_time_days INT DEFAULT 0,
    order_date DATE,
    expected_date DATE,
    status VARCHAR(30) DEFAULT 'OPEN',
    source VARCHAR(10) NOT NULL DEFAULT 'EXISTING',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)$eng",
"CREATE TABLE mrp_scenarios (
    id INT PRIMARY KEY,
    product_code VARCHAR(20) NOT NULL,
    target_qty INT NOT NULL,
    deadline_days INT NOT NULL,
    stock_a INT NOT NULL, onorder_a INT NOT NULL, lead_a INT NOT NULL,
    stock_b INT NOT NULL, onorder_b INT NOT NULL, lead_b INT NOT NULL
)$eng",
"CREATE TABLE security_audit_logs (
    id $pk,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    username VARCHAR(50) NOT NULL,
    role VARCHAR(60) NOT NULL,
    action_attempted VARCHAR(100) NOT NULL,
    resource VARCHAR(150) NOT NULL,
    status VARCHAR(20) NOT NULL,
    notes TEXT
)$eng",
"CREATE TABLE lab_purchase_requests (
    id $pk,
    request_no VARCHAR(30) NOT NULL UNIQUE,
    item_name VARCHAR(150) NOT NULL,
    qty INT NOT NULL,
    estimated_price DECIMAL(15,2) NOT NULL,
    total_estimated DECIMAL(15,2) NOT NULL,
    actual_quotation DECIMAL(15,2) NULL,
    requester_lecturer VARCHAR(100) NOT NULL,
    urgency VARCHAR(20) DEFAULT 'NORMAL',
    status VARCHAR(30) DEFAULT 'SUBMITTED',
    approval_level VARCHAR(30) DEFAULT 'KEPALA_LAB',
    approved_by VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)$eng",
"CREATE TABLE sales_orders (
    id $pk,
    so_no VARCHAR(30) NOT NULL UNIQUE,
    customer VARCHAR(100) NOT NULL,
    item_code VARCHAR(20) NOT NULL,
    qty INT NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    total DECIMAL(15,2) NOT NULL,
    status VARCHAR(30) DEFAULT 'DELIVERED',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)$eng",
"CREATE TABLE production_orders (
    id $pk,
    wo_no VARCHAR(30) NOT NULL UNIQUE,
    so_no VARCHAR(30) NOT NULL,
    item_code VARCHAR(20) NOT NULL,
    qty INT NOT NULL,
    status VARCHAR(30) DEFAULT 'COMPLETED',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)$eng",
    ];
}

function initDatabase($pdo) {
    foreach (schemaStatements() as $sql) $pdo->exec($sql);
    seedInitialData($pdo);
}

function insertRow($pdo, $table, $row) {
    $cols = array_keys($row);
    $st = $pdo->prepare("INSERT INTO $table (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")");
    $st->execute(array_values($row));
    return $pdo->lastInsertId();
}

function postJournal($pdo, $no, $date, $desc, $doc, $module, $by, $lines) {
    $id = insertRow($pdo, 'journal_entries', ['entry_no'=>$no,'entry_date'=>$date,'description'=>$desc,'source_doc'=>$doc,'module'=>$module,'created_by'=>$by]);
    foreach ($lines as $l) insertRow($pdo, 'journal_details', ['journal_id'=>$id,'account_code'=>$l[0],'debit'=>$l[1],'credit'=>$l[2]]);
    return $id;
}

function seedInitialData($pdo) {
    // CoA (Praktikum 4, Tahap 1) — 6 akun soal + Modal Awal sebagai pasangan saldo awal kas
    foreach ([
        ['1-1000','Kas','Aset Lancar','D',30000000],
        ['1-1100','Piutang Usaha','Aset Lancar','D',0],
        ['1-1200','Persediaan Bahan Baku','Aset Lancar','D',0],
        ['2-1000','Utang Usaha','Kewajiban','K',0],
        ['3-1000','Modal / Saldo Awal','Ekuitas','K',30000000],
        ['4-1000','Penjualan','Pendapatan','K',0],
        ['6-1000','Beban Operasional','Beban','D',0],
    ] as $a) insertRow($pdo,'coa',['code'=>$a[0],'name'=>$a[1],'type'=>$a[2],'normal_balance'=>$a[3],'initial_balance'=>$a[4]]);

    // 5 transaksi soal Praktikum 4
    $y = date('Y-m');
    $trx = [
        ['TRX-001',"$y-01",'Pembelian bahan baku secara kredit','PO-001/INV-SUP-101',[['1-1200',8000000,0],['2-1000',0,8000000]]],
        ['TRX-002',"$y-03",'Pembayaran utang pemasok','BK-001/VOUCHER-PAY',[['2-1000',3000000,0],['1-1000',0,3000000]]],
        ['TRX-003',"$y-05",'Penjualan produk secara kredit','SO-001/INV-CUST-201',[['1-1100',12000000,0],['4-1000',0,12000000]]],
        ['TRX-004',"$y-08",'Penerimaan pembayaran pelanggan','BM-001/RECEIPT-88',[['1-1000',5000000,0],['1-1100',0,5000000]]],
        ['TRX-005',"$y-10",'Pembayaran biaya operasional secara tunai','BKK-002/OPR-EXP',[['6-1000',1500000,0],['1-1000',0,1500000]]],
    ];
    foreach ($trx as $t) postJournal($pdo,$t[0],$t[1].'','' . $t[2],$t[3],'P4','Staf Akuntansi',$t[4]);

    // Master barang: Praktikum 1 (K-01, MAT-A, MAT-B) dan Praktikum 5 (P-01, RAW-A, RAW-B)
    foreach ([
        ['P-01','Produk Jadi P-01','FG',0,'Unit',2,150000,null],
        ['RAW-A','Bahan A','RAW',400,'Unit',4,30000,'PT Sumber Logam Perkasa'],
        ['RAW-B','Bahan B','RAW',250,'Unit',7,40000,'PT Reksa Kimia Industri'],
        ['K-01','Produk Jadi K-01 (contoh Praktikum 1)','FG',40,'Unit',3,150000,null],
        ['MAT-A','Bahan Baku A (Praktikum 1)','RAW',150,'Unit',5,25000,'PT Sumber Logam Perkasa'],
        ['MAT-B','Bahan Baku B (Praktikum 1)','RAW',50,'Unit',10,35000,'PT Reksa Kimia Industri'],
    ] as $i) insertRow($pdo,'items',['code'=>$i[0],'name'=>$i[1],'type'=>$i[2],'stock_qty'=>$i[3],'unit'=>$i[4],'lead_time_days'=>$i[5],'unit_price'=>$i[6],'vendor'=>$i[7]]);
    foreach ([['P-01','RAW-A',3],['P-01','RAW-B',2],['K-01','MAT-A',2],['K-01','MAT-B',1]] as $b)
        insertRow($pdo,'bom',['parent_code'=>$b[0],'component_code'=>$b[1],'quantity'=>$b[2]]);

    // Pesanan pembelian Bahan A yang belum diterima (soal Praktikum 5)
    insertRow($pdo,'purchase_orders',['po_no'=>'PO-2026-A100','item_code'=>'RAW-A','vendor_name'=>'PT Sumber Logam Perkasa','qty_ordered'=>100,'qty_received'=>0,'unit_price'=>30000,'lead_time_days'=>4,'order_date'=>date('Y-m-d'),'expected_date'=>date('Y-m-d',strtotime('+4 days')),'status'=>'OPEN','source'=>'EXISTING']);
    // Skenario MRP dari soal
    insertRow($pdo,'mrp_scenarios',['id'=>1,'product_code'=>'P-01','target_qty'=>300,'deadline_days'=>6,'stock_a'=>400,'onorder_a'=>100,'lead_a'=>4,'stock_b'=>250,'onorder_b'=>0,'lead_b'=>7]);

    // Praktikum 3: contoh permintaan bahan lab
    insertRow($pdo,'lab_purchase_requests',['request_no'=>'PR-LAB-001','item_name'=>'Insert bubut carbide (praktik manufaktur)','qty'=>20,'estimated_price'=>200000,'total_estimated'=>4000000,'actual_quotation'=>null,'requester_lecturer'=>'Dosen Pengampu CNC','urgency'=>'NORMAL','status'=>'APPROVED','approval_level'=>'KEPALA_LAB','approved_by'=>'Kepala Laboratorium','notes'=>'Disetujui Kepala Lab (nilai <= Rp 5.000.000)']);
    insertRow($pdo,'lab_purchase_requests',['request_no'=>'PR-LAB-002','item_name'=>'Sensor proximity & pelumas hidrolik mesin milling','qty'=>10,'estimated_price'=>650000,'total_estimated'=>6500000,'actual_quotation'=>null,'requester_lecturer'=>'Dosen Penguji Praktik','urgency'=>'EMERGENCY_24H','status'=>'APPROVED','approval_level'=>'PIMPINAN','approved_by'=>'Pimpinan','notes'=>'Nilai > Rp 5.000.000 disetujui Pimpinan (jalur darurat 24 jam)']);

    // Log keamanan awal
    insertRow($pdo,'security_audit_logs',['username'=>'staf_pembelian','role'=>'Staf Pembelian','action_attempted'=>'CREATE_PO','resource'=>'modul_pembelian/po','status'=>'ALLOWED','notes'=>'Pembuatan PO berhasil divalidasi']);
    insertRow($pdo,'security_audit_logs',['username'=>'staf_gudang','role'=>'Staf Gudang','action_attempted'=>'VIEW_FINANCIAL_REPORT','resource'=>'modul_keuangan/neraca_saldo','status'=>'BLOCKED','notes'=>'Akses ditolak: Staf Gudang tidak memiliki hak akses laporan keuangan']);
}

function resetDatabaseToUTSDefault() {
    $pdo = getDbConnection();
    if (dbDriver() === 'mysql') $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['journal_details','journal_entries','coa','bom','purchase_orders','purchase_requisitions','items','mrp_scenarios','security_audit_logs','lab_purchase_requests','sales_orders','production_orders'] as $t)
        $pdo->exec("DROP TABLE IF EXISTS $t");
    if (dbDriver() === 'mysql') $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    initDatabase($pdo);
    return true;
}
