-- Database Praktikum ERP (PL/4008) - Politeknik META Industri - Prodi TRPL
-- Format: MySQL / MariaDB (import lewat phpMyAdmin pada database kosong)
-- Dibuat: 2026-11-02 03:06:40

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `production_orders`;
DROP TABLE IF EXISTS `sales_orders`;
DROP TABLE IF EXISTS `lab_purchase_requests`;
DROP TABLE IF EXISTS `security_audit_logs`;
DROP TABLE IF EXISTS `mrp_scenarios`;
DROP TABLE IF EXISTS `purchase_orders`;
DROP TABLE IF EXISTS `purchase_requisitions`;
DROP TABLE IF EXISTS `bom`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `journal_details`;
DROP TABLE IF EXISTS `journal_entries`;
DROP TABLE IF EXISTS `coa`;

CREATE TABLE coa (
    code VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    normal_balance CHAR(1) NOT NULL,
    initial_balance DECIMAL(15,2) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_no VARCHAR(40) NOT NULL UNIQUE,
    entry_date DATE NOT NULL,
    description TEXT NOT NULL,
    source_doc VARCHAR(80),
    module VARCHAR(10) NOT NULL DEFAULT 'P4',
    is_reversed TINYINT DEFAULT 0,
    reversed_by_id INT NULL,
    created_by VARCHAR(60) DEFAULT 'Sistem ERP',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE journal_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    journal_id INT NOT NULL,
    account_code VARCHAR(20) NOT NULL,
    debit DECIMAL(15,2) DEFAULT 0,
    credit DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (journal_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_code) REFERENCES coa(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE items (
    code VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL,
    stock_qty INT NOT NULL DEFAULT 0,
    unit VARCHAR(20) NOT NULL DEFAULT 'Unit',
    lead_time_days INT DEFAULT 0,
    unit_price DECIMAL(15,2) DEFAULT 0,
    vendor VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_code VARCHAR(20) NOT NULL,
    component_code VARCHAR(20) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (parent_code) REFERENCES items(code),
    FOREIGN KEY (component_code) REFERENCES items(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_requisitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pr_no VARCHAR(30) NOT NULL UNIQUE,
    item_code VARCHAR(20) NOT NULL,
    qty INT NOT NULL,
    requester VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    need_date DATE,
    status VARCHAR(30) DEFAULT 'PENDING',
    module VARCHAR(10) NOT NULL DEFAULT 'P5',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mrp_scenarios (
    id INT PRIMARY KEY,
    product_code VARCHAR(20) NOT NULL,
    target_qty INT NOT NULL,
    deadline_days INT NOT NULL,
    stock_a INT NOT NULL, onorder_a INT NOT NULL, lead_a INT NOT NULL,
    stock_b INT NOT NULL, onorder_b INT NOT NULL, lead_b INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE security_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    username VARCHAR(50) NOT NULL,
    role VARCHAR(60) NOT NULL,
    action_attempted VARCHAR(100) NOT NULL,
    resource VARCHAR(150) NOT NULL,
    status VARCHAR(20) NOT NULL,
    notes TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lab_purchase_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sales_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_no VARCHAR(30) NOT NULL UNIQUE,
    customer VARCHAR(100) NOT NULL,
    item_code VARCHAR(20) NOT NULL,
    qty INT NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    total DECIMAL(15,2) NOT NULL,
    status VARCHAR(30) DEFAULT 'DELIVERED',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE production_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wo_no VARCHAR(30) NOT NULL UNIQUE,
    so_no VARCHAR(30) NOT NULL,
    item_code VARCHAR(20) NOT NULL,
    qty INT NOT NULL,
    status VARCHAR(30) DEFAULT 'COMPLETED',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data tabel `coa` (7 baris)
INSERT INTO `coa` (`code`, `name`, `type`, `normal_balance`, `initial_balance`) VALUES ('1-1000', 'Kas', 'Aset Lancar', 'D', '30000000');
INSERT INTO `coa` (`code`, `name`, `type`, `normal_balance`, `initial_balance`) VALUES ('1-1100', 'Piutang Usaha', 'Aset Lancar', 'D', '0');
INSERT INTO `coa` (`code`, `name`, `type`, `normal_balance`, `initial_balance`) VALUES ('1-1200', 'Persediaan Bahan Baku', 'Aset Lancar', 'D', '0');
INSERT INTO `coa` (`code`, `name`, `type`, `normal_balance`, `initial_balance`) VALUES ('2-1000', 'Utang Usaha', 'Kewajiban', 'K', '0');
INSERT INTO `coa` (`code`, `name`, `type`, `normal_balance`, `initial_balance`) VALUES ('3-1000', 'Modal / Saldo Awal', 'Ekuitas', 'K', '30000000');
INSERT INTO `coa` (`code`, `name`, `type`, `normal_balance`, `initial_balance`) VALUES ('4-1000', 'Penjualan', 'Pendapatan', 'K', '0');
INSERT INTO `coa` (`code`, `name`, `type`, `normal_balance`, `initial_balance`) VALUES ('6-1000', 'Beban Operasional', 'Beban', 'D', '0');

-- Data tabel `journal_entries` (5 baris)
INSERT INTO `journal_entries` (`id`, `entry_no`, `entry_date`, `description`, `source_doc`, `module`, `is_reversed`, `reversed_by_id`, `created_by`, `created_at`) VALUES ('1', 'TRX-001', '2026-11-01', 'Pembelian bahan baku secara kredit', 'PO-001/INV-SUP-101', 'P4', '0', NULL, 'Staf Akuntansi', '2026-11-02 03:06:40');
INSERT INTO `journal_entries` (`id`, `entry_no`, `entry_date`, `description`, `source_doc`, `module`, `is_reversed`, `reversed_by_id`, `created_by`, `created_at`) VALUES ('2', 'TRX-002', '2026-11-03', 'Pembayaran utang pemasok', 'BK-001/VOUCHER-PAY', 'P4', '0', NULL, 'Staf Akuntansi', '2026-11-02 03:06:40');
INSERT INTO `journal_entries` (`id`, `entry_no`, `entry_date`, `description`, `source_doc`, `module`, `is_reversed`, `reversed_by_id`, `created_by`, `created_at`) VALUES ('3', 'TRX-003', '2026-11-05', 'Penjualan produk secara kredit', 'SO-001/INV-CUST-201', 'P4', '0', NULL, 'Staf Akuntansi', '2026-11-02 03:06:40');
INSERT INTO `journal_entries` (`id`, `entry_no`, `entry_date`, `description`, `source_doc`, `module`, `is_reversed`, `reversed_by_id`, `created_by`, `created_at`) VALUES ('4', 'TRX-004', '2026-11-08', 'Penerimaan pembayaran pelanggan', 'BM-001/RECEIPT-88', 'P4', '0', NULL, 'Staf Akuntansi', '2026-11-02 03:06:40');
INSERT INTO `journal_entries` (`id`, `entry_no`, `entry_date`, `description`, `source_doc`, `module`, `is_reversed`, `reversed_by_id`, `created_by`, `created_at`) VALUES ('5', 'TRX-005', '2026-11-10', 'Pembayaran biaya operasional secara tunai', 'BKK-002/OPR-EXP', 'P4', '0', NULL, 'Staf Akuntansi', '2026-11-02 03:06:40');

-- Data tabel `journal_details` (10 baris)
INSERT INTO `journal_details` (`id`, `journal_id`, `account_code`, `debit`, `credit`) VALUES ('1', '1', '1-1200', '8000000', '0');
INSERT INTO `journal_details` (`id`, `journal_id`, `account_code`, `debit`, `credit`) VALUES ('2', '1', '2-1000', '0', '8000000');
INSERT INTO `journal_details` (`id`, `journal_id`, `account_code`, `debit`, `credit`) VALUES ('3', '2', '2-1000', '3000000', '0');
INSERT INTO `journal_details` (`id`, `journal_id`, `account_code`, `debit`, `credit`) VALUES ('4', '2', '1-1000', '0', '3000000');
INSERT INTO `journal_details` (`id`, `journal_id`, `account_code`, `debit`, `credit`) VALUES ('5', '3', '1-1100', '12000000', '0');
INSERT INTO `journal_details` (`id`, `journal_id`, `account_code`, `debit`, `credit`) VALUES ('6', '3', '4-1000', '0', '12000000');
INSERT INTO `journal_details` (`id`, `journal_id`, `account_code`, `debit`, `credit`) VALUES ('7', '4', '1-1000', '5000000', '0');
INSERT INTO `journal_details` (`id`, `journal_id`, `account_code`, `debit`, `credit`) VALUES ('8', '4', '1-1100', '0', '5000000');
INSERT INTO `journal_details` (`id`, `journal_id`, `account_code`, `debit`, `credit`) VALUES ('9', '5', '6-1000', '1500000', '0');
INSERT INTO `journal_details` (`id`, `journal_id`, `account_code`, `debit`, `credit`) VALUES ('10', '5', '1-1000', '0', '1500000');

-- Data tabel `items` (6 baris)
INSERT INTO `items` (`code`, `name`, `type`, `stock_qty`, `unit`, `lead_time_days`, `unit_price`, `vendor`) VALUES ('K-01', 'Produk Jadi K-01 (contoh Praktikum 1)', 'FG', '40', 'Unit', '3', '150000', NULL);
INSERT INTO `items` (`code`, `name`, `type`, `stock_qty`, `unit`, `lead_time_days`, `unit_price`, `vendor`) VALUES ('MAT-A', 'Bahan Baku A (Praktikum 1)', 'RAW', '150', 'Unit', '5', '25000', 'PT Sumber Logam Perkasa');
INSERT INTO `items` (`code`, `name`, `type`, `stock_qty`, `unit`, `lead_time_days`, `unit_price`, `vendor`) VALUES ('MAT-B', 'Bahan Baku B (Praktikum 1)', 'RAW', '50', 'Unit', '10', '35000', 'PT Reksa Kimia Industri');
INSERT INTO `items` (`code`, `name`, `type`, `stock_qty`, `unit`, `lead_time_days`, `unit_price`, `vendor`) VALUES ('P-01', 'Produk Jadi P-01', 'FG', '0', 'Unit', '2', '150000', NULL);
INSERT INTO `items` (`code`, `name`, `type`, `stock_qty`, `unit`, `lead_time_days`, `unit_price`, `vendor`) VALUES ('RAW-A', 'Bahan A', 'RAW', '400', 'Unit', '4', '30000', 'PT Sumber Logam Perkasa');
INSERT INTO `items` (`code`, `name`, `type`, `stock_qty`, `unit`, `lead_time_days`, `unit_price`, `vendor`) VALUES ('RAW-B', 'Bahan B', 'RAW', '250', 'Unit', '7', '40000', 'PT Reksa Kimia Industri');

-- Data tabel `bom` (4 baris)
INSERT INTO `bom` (`id`, `parent_code`, `component_code`, `quantity`) VALUES ('1', 'P-01', 'RAW-A', '3');
INSERT INTO `bom` (`id`, `parent_code`, `component_code`, `quantity`) VALUES ('2', 'P-01', 'RAW-B', '2');
INSERT INTO `bom` (`id`, `parent_code`, `component_code`, `quantity`) VALUES ('3', 'K-01', 'MAT-A', '2');
INSERT INTO `bom` (`id`, `parent_code`, `component_code`, `quantity`) VALUES ('4', 'K-01', 'MAT-B', '1');

-- Data tabel `purchase_requisitions` (0 baris)

-- Data tabel `purchase_orders` (1 baris)
INSERT INTO `purchase_orders` (`id`, `po_no`, `item_code`, `vendor_name`, `qty_ordered`, `qty_received`, `unit_price`, `lead_time_days`, `order_date`, `expected_date`, `status`, `source`, `created_at`) VALUES ('1', 'PO-2026-A100', 'RAW-A', 'PT Sumber Logam Perkasa', '100', '0', '30000', '4', '2026-11-02', '2026-11-06', 'OPEN', 'EXISTING', '2026-11-02 03:06:40');

-- Data tabel `mrp_scenarios` (1 baris)
INSERT INTO `mrp_scenarios` (`id`, `product_code`, `target_qty`, `deadline_days`, `stock_a`, `onorder_a`, `lead_a`, `stock_b`, `onorder_b`, `lead_b`) VALUES ('1', 'P-01', '300', '6', '400', '100', '4', '250', '0', '7');

-- Data tabel `security_audit_logs` (2 baris)
INSERT INTO `security_audit_logs` (`id`, `timestamp`, `username`, `role`, `action_attempted`, `resource`, `status`, `notes`) VALUES ('1', '2026-11-02 03:06:40', 'staf_pembelian', 'Staf Pembelian', 'CREATE_PO', 'modul_pembelian/po', 'ALLOWED', 'Pembuatan PO berhasil divalidasi');
INSERT INTO `security_audit_logs` (`id`, `timestamp`, `username`, `role`, `action_attempted`, `resource`, `status`, `notes`) VALUES ('2', '2026-11-02 03:06:40', 'staf_gudang', 'Staf Gudang', 'VIEW_FINANCIAL_REPORT', 'modul_keuangan/neraca_saldo', 'BLOCKED', 'Akses ditolak: Staf Gudang tidak memiliki hak akses laporan keuangan');

-- Data tabel `lab_purchase_requests` (2 baris)
INSERT INTO `lab_purchase_requests` (`id`, `request_no`, `item_name`, `qty`, `estimated_price`, `total_estimated`, `actual_quotation`, `requester_lecturer`, `urgency`, `status`, `approval_level`, `approved_by`, `notes`, `created_at`) VALUES ('1', 'PR-LAB-001', 'Insert bubut carbide (praktik manufaktur)', '20', '200000', '4000000', NULL, 'Dosen Pengampu CNC', 'NORMAL', 'APPROVED', 'KEPALA_LAB', 'Kepala Laboratorium', 'Disetujui Kepala Lab (nilai <= Rp 5.000.000)', '2026-11-02 03:06:40');
INSERT INTO `lab_purchase_requests` (`id`, `request_no`, `item_name`, `qty`, `estimated_price`, `total_estimated`, `actual_quotation`, `requester_lecturer`, `urgency`, `status`, `approval_level`, `approved_by`, `notes`, `created_at`) VALUES ('2', 'PR-LAB-002', 'Sensor proximity & pelumas hidrolik mesin milling', '10', '650000', '6500000', NULL, 'Dosen Penguji Praktik', 'EMERGENCY_24H', 'APPROVED', 'PIMPINAN', 'Pimpinan', 'Nilai > Rp 5.000.000 disetujui Pimpinan (jalur darurat 24 jam)', '2026-11-02 03:06:40');

-- Data tabel `sales_orders` (0 baris)

-- Data tabel `production_orders` (0 baris)

SET FOREIGN_KEY_CHECKS=1;
