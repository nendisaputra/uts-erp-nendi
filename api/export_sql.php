<?php
// api/export_sql.php - Unduh database lengkap (struktur + data) format MySQL, siap import ke phpMyAdmin.
require_once __DIR__ . '/../config/database.php';
$pdo = getDbConnection();
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="erp_praktikum_' . date('Ymd_His') . '.sql"');
echo dumpSqlMysql($pdo);

function dumpSqlMysql($pdo) {
    $o  = "-- Database Praktikum ERP (PL/4008) - Politeknik META Industri - Prodi TRPL\n";
    $o .= "-- Format: MySQL / MariaDB (import lewat phpMyAdmin pada database kosong)\n";
    $o .= "-- Dibuat: " . date('Y-m-d H:i:s') . "\n\n";
    $o .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    $tables = ['coa','journal_entries','journal_details','items','bom','purchase_requisitions','purchase_orders','mrp_scenarios','security_audit_logs','lab_purchase_requests','sales_orders','production_orders'];
    $ddl = schemaStatements(true);
    foreach (array_reverse($tables) as $t) $o .= "DROP TABLE IF EXISTS `$t`;\n";
    $o .= "\n";
    foreach ($ddl as $stmt) $o .= $stmt . ";\n\n";
    foreach ($tables as $t) {
        $rows = $pdo->query("SELECT * FROM $t ORDER BY 1")->fetchAll();
        $o .= "-- Data tabel `$t` (" . count($rows) . " baris)\n";
        foreach ($rows as $r) {
            $cols = '`' . implode('`, `', array_keys($r)) . '`';
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r));
            $o .= "INSERT INTO `$t` ($cols) VALUES (" . implode(', ', $vals) . ");\n";
        }
        $o .= "\n";
    }
    return $o . "SET FOREIGN_KEY_CHECKS=1;\n";
}
