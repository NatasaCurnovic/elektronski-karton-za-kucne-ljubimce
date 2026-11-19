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

// Link koji u Google Calendar-u otvara događaj sa popunjenim podacima
function gcal_link(string $naslov, string $pocetak, string $kraj, string $opis = ''): string
{
    $fmt = fn(string $s) => date('Ymd\THis', strtotime($s));
    return 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action'  => 'TEMPLATE',
            'text'    => $naslov,
            'dates'   => $fmt($pocetak) . '/' . $fmt($kraj),
            'details' => $opis,
            'ctz'     => 'Europe/Belgrade',
        ], '', '&', PHP_QUERY_RFC3986);
}