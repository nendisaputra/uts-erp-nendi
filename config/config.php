<?php
// config/config.php - Pengaturan database.
$config = [
    'driver' => 'mysql',           // Diubah dari 'sqlite' menjadi 'mysql'
    'mysql'  => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'nendi_erp', // Nama database Anda
        'user' => 'root',
        'pass' => '',
    ],
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}
return $config;