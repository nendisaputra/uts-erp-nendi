<?php
// api/p2_actions.php - Praktikum 2: simulasi gangguan (hanya mencatat ke audit log; TIDAK mematikan server/menghapus database)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/data_p2.php';
$pdo = getDbConnection();
$id = (int)($_POST['incident'] ?? 0);
if (!isset($P2_INCIDENTS[$id])) backWith('praktikum2.php', 'Kondisi gangguan tidak dikenal.', 'danger', 'tab-simulasi-gangguan');
[$name, $handling] = $P2_INCIDENTS[$id];
insertRow($pdo, 'security_audit_logs', ['username'=>'penguji','role'=>'Simulasi Gangguan','action_attempted'=>'INCIDENT_SIM_' . $id,'resource'=>$name,'status'=>'MITIGATED','notes'=>$handling]);
backWith('praktikum2.php', "Simulasi \"$name\" dicatat. Penanganan: $handling", 'success', 'tab-simulasi-gangguan');
