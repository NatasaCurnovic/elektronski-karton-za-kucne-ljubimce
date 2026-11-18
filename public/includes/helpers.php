<?php
date_default_timezone_set('Europe/Belgrade');
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function datum(?string $s, bool $vreme = false): string
{
    if (!$s) {
        return '';
    }
    $t = strtotime($s);
    return date('d.m.Y.', $t) . ($vreme ? ' u ' . date('H:i', $t) : '');
}

function polNaziv(string $p): string
{
    return ['MALE' => 'Muški', 'FEMALE' => 'Ženski', 'UNKNOWN' => 'Nepoznato'][$p] ?? '';
}