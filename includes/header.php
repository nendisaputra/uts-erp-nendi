<?php
// includes/header.php
$currentPage = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/helpers.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Simulasi Praktikum ERP' ?> | Politeknik META Industri</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-top">
                <a href="index.php" class="brand-wrapper">
                    <div class="brand-indicator"></div>
                    <div class="brand-info">
                        <h1>Sistem Simulasi Praktikum ERP</h1>
                        <p>
                            <span>Enterprise Resource Planning (PL/4008)</span>
                            <span class="badge-campus">Politeknik META Industri &bull; TRPL</span>
                        </p>
                    </div>
                </a>

                <div class="header-actions">
                    <a href="api/export_sql.php" class="btn btn-outline btn-sm" title="Unduh Skema & Data Database SQL">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Ekspor SQL</span>
                    </a>
                    <button onclick="confirmResetDatabase()" class="btn btn-danger btn-sm" title="Kembalikan database ke skenario awal UTS">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                        <span>Reset Default</span>
                    </button>
                    <button onclick="window.print()" class="btn btn-outline btn-sm" title="Cetak halaman untuk laporan praktikum">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        <span>Cetak Laporan</span>
                    </button>
                </div>
            </div>

            <!-- Nav Tabs -->
            <nav class="nav-tabs-wrapper">
                <a href="index.php" class="nav-tab-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    <span>Dashboard</span>
                </a>
                <a href="praktikum1.php" class="nav-tab-item <?= $currentPage === 'praktikum1.php' ? 'active' : '' ?>">
                    <span class="badge-num">1</span>
                    <span>Integrasi & Data Master</span>
                </a>
                <a href="praktikum2.php" class="nav-tab-item <?= $currentPage === 'praktikum2.php' ? 'active' : '' ?>">
                    <span class="badge-num">2</span>
                    <span>Arsitektur & RBAC</span>
                </a>
                <a href="praktikum3.php" class="nav-tab-item <?= $currentPage === 'praktikum3.php' ? 'active' : '' ?>">
                    <span class="badge-num">3</span>
                    <span>Proses Bisnis Pengadaan</span>
                </a>
                <a href="praktikum4.php" class="nav-tab-item <?= $currentPage === 'praktikum4.php' ? 'active' : '' ?>">
                    <span class="badge-num">4</span>
                    <span>Modul Keuangan & Audit</span>
                </a>
                <a href="praktikum5.php" class="nav-tab-item <?= $currentPage === 'praktikum5.php' ? 'active' : '' ?>">
                    <span class="badge-num">5</span>
                    <span>SCM & MRP</span>
                </a>
            </nav>
        </div>
    </header>

    <main style="flex: 1; padding: 20px 0;">
        <div class="container">
