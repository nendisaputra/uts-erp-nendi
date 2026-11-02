<?php
// api/reset_db.php - kembalikan seluruh data ke kondisi awal soal
require_once __DIR__ . '/../config/database.php';
try {
    resetDatabaseToUTSDefault();
    $back = basename($_GET['redirect'] ?? 'index.php');
    if (!preg_match('/^(index|praktikum[1-5])\.php$/', $back)) $back = 'index.php';
    header("Location: ../$back?msg=" . urlencode('Database dikembalikan ke kondisi awal soal.') . '&type=success');
    exit;
} catch (Exception $e) { die('Gagal reset database: ' . htmlspecialchars($e->getMessage())); }
