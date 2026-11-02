<?php
// includes/data_p2.php - Kondisi gangguan Praktikum 2 beserta penanganannya (dipakai halaman & API)
$P2_INCIDENTS = [
 1 => ['Database tidak dapat diakses', 'Failover ke database replika (standby); server aplikasi diarahkan ke replika; transaksi baru ditahan beberapa menit lalu dibuka kembali. Jika replika ikut rusak: restore full backup + diff + log transaksi (RPO 15 menit, RTO 4 jam).'],
 2 => ['Server aplikasi mengalami gangguan', 'Load balancer otomatis memindahkan pengguna ke server aplikasi cadangan (standby); server utama diperiksa log-nya dan diperbaiki; sesi pengguna login ulang.'],
 3 => ['Koneksi internet terputus', 'Pengguna kantor tetap berjalan lewat LAN internal (server on-premise). Pengguna jarak jauh beralih ke jalur ISP cadangan/hotspot lewat VPN. Transaksi yang gagal dikirim disimpan sementara lalu dikirim ulang (retry).'],
 4 => ['Pengguna tidak berwenang membuka menu keuangan', 'Sistem menolak (HTTP 403) berdasarkan matriks RBAC, menampilkan pesan "akses ditolak", dan mencatat percobaan di audit log. Percobaan berulang memicu notifikasi ke Administrator IT.'],
 5 => ['Data transaksi hilang akibat backup tidak berjalan', 'Hentikan transaksi, cari backup valid terakhir (NAS lalu cloud), pulihkan full backup + differential + log transaksi, verifikasi neraca saldo & stok fisik. Perbaiki jadwal backup dan pasang alarm kegagalan backup.'],
];
