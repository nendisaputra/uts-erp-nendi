<?php
// includes/helpers.php - fungsi bantu tampilan
function rp($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function num($n) { return number_format((float)$n, 0, ',', '.'); }
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function backWith($page, $msg, $type = 'info', $hash = '') {
    header("Location: ../$page?msg=" . urlencode($msg) . "&type=$type" . ($hash ? "#$hash" : ''));
    exit;
}
// Bar chart SVG sederhana (dirender server-side dari data database). $series = [['name'=>, 'color'=>, 'values'=>[...]], ...]
function barChartSvg($title, $labels, $series, $unit = '') {
    $w = 640; $hgt = 260; $padL = 76; $padB = 46; $padT = 34; $padR = 12;
    $max = 1;
    foreach ($series as $s) foreach ($s['values'] as $v) $max = max($max, $v);
    $plotH = $hgt - $padT - $padB; $plotW = $w - $padL - $padR;
    $gw = $plotW / max(1, count($labels)); $bw = min(46, ($gw - 14) / max(1, count($series)));
    $o = "<svg viewBox=\"0 0 $w $hgt\" width=\"100%\" role=\"img\" aria-label=\"" . h($title) . "\" style=\"max-width:$w" . "px;background:#fff;border-radius:10px\">";
    $o .= "<text x=\"" . ($w/2) . "\" y=\"20\" text-anchor=\"middle\" font-size=\"13\" font-weight=\"700\" fill=\"#0f172a\">" . h($title) . "</text>";
    for ($i = 0; $i <= 4; $i++) {
        $y = $padT + $plotH - $plotH * $i / 4; $val = $max * $i / 4;
        $o .= "<line x1=\"$padL\" x2=\"" . ($w-$padR) . "\" y1=\"$y\" y2=\"$y\" stroke=\"#e2e8f0\"/><text x=\"" . ($padL-6) . "\" y=\"" . ($y+4) . "\" text-anchor=\"end\" font-size=\"10\" fill=\"#475569\">" . number_format($val, 0, ',', '.') . "</text>";
    }
    foreach ($labels as $i => $lab) {
        $gx = $padL + $gw * $i + ($gw - $bw * count($series)) / 2;
        foreach ($series as $k => $s) {
            $v = $s['values'][$i] ?? 0; $bh = $plotH * $v / $max; $x = $gx + $k * $bw; $y = $padT + $plotH - $bh;
            $o .= "<rect x=\"$x\" y=\"$y\" width=\"" . ($bw-3) . "\" height=\"$bh\" fill=\"{$s['color']}\" rx=\"3\"/>";
            $o .= "<text x=\"" . ($x + ($bw-3)/2) . "\" y=\"" . ($y-4) . "\" text-anchor=\"middle\" font-size=\"9.5\" fill=\"#0f172a\">" . number_format($v, 0, ',', '.') . "</text>";
        }
        $short = mb_strlen($lab) > 12 ? mb_substr($lab, 0, 11) . '…' : $lab;
        $o .= "<text x=\"" . ($padL + $gw*$i + $gw/2) . "\" y=\"" . ($hgt-$padB+16) . "\" text-anchor=\"middle\" font-size=\"10.5\" fill=\"#0f172a\">" . h($short) . "</text>";
    }
    $lx = $padL;
    foreach ($series as $s) { $o .= "<rect x=\"$lx\" y=\"" . ($hgt-14) . "\" width=\"10\" height=\"10\" fill=\"{$s['color']}\"/><text x=\"" . ($lx+14) . "\" y=\"" . ($hgt-5) . "\" font-size=\"10.5\" fill=\"#0f172a\">" . h($s['name']) . "</text>"; $lx += 22 + 7 * strlen($s['name']); }
    return $o . '</svg>';
}
function diagramImg($file, $caption, $alt) {
    return "<figure class=\"diagram-fig\"><a href=\"assets/img/$file\" target=\"_blank\"><img src=\"assets/img/$file\" alt=\"" . h($alt) . "\"></a><figcaption>" . h($caption) . "</figcaption></figure>";
}
