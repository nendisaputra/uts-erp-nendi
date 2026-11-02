# Simulator Praktikum ERP — PL/4008 (Politeknik META Industri, TRPL)

Web PHP + database untuk 5 praktikum UTS ERP. Semua angka (jurnal, buku besar, neraca saldo, MRP, PR/PO, stok,
permintaan lab, audit log) dibaca/ditulis ke **database**, bukan hanya di browser.

## Struktur
- `praktikum1.php`–`praktikum5.php`, `index.php` : halaman web
- `assets/img/*.png` : diagram (sumber Mermaid di `assets/img/src/*.mmd`)
- `api/` : aksi simulasi (simpan ke database), `api/export_sql.php` : unduh database `.sql`
- `database/erp_praktikum.sql` : database MySQL/MariaDB lengkap (struktur + data soal) — untuk phpMyAdmin
- `database/erp_praktikum.db` : database SQLite (cadangan, otomatis dibuat bila belum ada)

## Deploy ke hosting (cPanel) dengan MySQL — disarankan
1. cPanel → *MySQL Databases*: buat database + user, beri **All Privileges**.
2. phpMyAdmin → pilih database → *Import* → pilih `database/erp_praktikum.sql`.
3. Upload seluruh isi folder ini ke `public_html` (atau subfolder).
4. Salin `config/config.php` menjadi `config/config.local.php` (isi hanya yang berubah), contoh:
   ```php
   <?php return ['driver'=>'mysql','mysql'=>['host'=>'localhost','name'=>'NAMA_DB','user'=>'USER_DB','pass'=>'PASSWORD']];
   ```
5. Buka `index.php`. Tombol **Reset Default** mengembalikan data ke kondisi soal; **Ekspor SQL** mengunduh dump terbaru.

## Tanpa MySQL (SQLite)
Biarkan `driver` = `sqlite`. Folder `database/` harus bisa ditulis (chmod 775/777).

## Lokal (XAMPP/Laragon)
`php -S localhost:8000 -t .` lalu buka http://localhost:8000 (SQLite), atau pakai MySQL seperti langkah di atas.

## Kesesuaian dengan soal
| Praktikum | Bukti yang diminta | Di web |
|---|---|---|
| 1 | Diagram sebelum & sesudah integrasi; daftar data master; hasil simulasi transaksi; penjelasan satu masalah + solusi | Tab 2 (gambar), 3, 4 (tersimpan di DB), 5 |
| 2 | Diagram arsitektur; matriks hak akses; rencana backup/pemulihan; simulasi gangguan; alasan rancangan; ≥3 potensi kegagalan | Tab 1–6 (gambar + simulasi 5 kondisi ke audit log) |
| 3 | Diagram as-is & to-be; daftar aturan bisnis; tabel aktor; simulasi kondisi khusus; alasan lebih baik | Tab A–D, Data Permintaan (DB), Mengapa Lebih Baik |
| 4 | Pengaturan akun; input transaksi; jurnal & buku besar; hitung saldo; koreksi kesalahan dapat ditelusuri | Semua tab, dihitung dari DB |
| 5 | Data master & BOM; kebutuhan bruto/bersih; bukti PR; penerimaan barang; laporan stok; analisis pemasok terlambat | Tab 1–5, dihitung dari DB |

Asumsi rancangan (bukan angka dari soal): toleransi kenaikan harga 5% dan plafon Kepala Lab Rp 5.000.000 (Praktikum 3);
pesanan Bahan A 100 unit dianggap tiba sebelum hari ke-4 (Praktikum 5); data contoh PT Sinar Otomotif / K-01 (Praktikum 1).
