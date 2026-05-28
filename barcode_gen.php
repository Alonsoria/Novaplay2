<?php
/**
 * NOVAPLAY — BARCODE_GEN.PHP
 * Genera un código de barras Code 128-B como SVG.
 * Uso: barcode_gen.php?ref=REFERENCIA
 */

$ref = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['ref'] ?? ''));
if ($ref === '' || strlen($ref) > 64) {
    http_response_code(400);
    exit;
}

/* ── Tabla de patrones Code 128 (11 bits, 1=barra, 0=espacio) ── */
$P = [
    '11011001100','11001101100','11001100110','10010011000','10010001100',
    '10001001100','10011001000','10011000100','10001100100','11001001000',
    '11001000100','11000100100','10110011100','10011011100','10011001110',
    '10111001100','10011101100','10011100110','11001110010','11001011100',
    '11001001110','11011100100','11001110100','11101101110','11101001100',
    '11100101100','11100100110','11101100100','11100110100','11100110010',
    '11011011000','11011000110','11000110110','10100011000','10001011000',
    '10001000110','10110001000','10001101000','10001100010','11010001000',
    '11000101000','11000100010','10110111000','10110001110','10001101110',
    '10111011000','10111000110','10001110110','11101110110','11010001110',
    '11000101110','11011101000','11011100010','11011101110','11101011000',
    '11101000110','11100010110','11101101000','11101100010','11100011010',
    '11101111010','11001000010','11110001010','10100110000','10100001100',
    '10010110000','10010000110','10000101100','10000100110','10110010000',
    '10110000100','10011010000','10011000010','10000110100','10000110010',
    '11000010010','11001010000','11110111010','11000010100','10001111010',
    '10100111100','10010111100','10010011110','10111100100','10011110100',
    '10011110010','11110100100','11110010100','11110010010','11011011110',
    '11011110110','11110110110','10101111000','10100011110','10001011110',
    '10111101000','10111100010','11110101000','11110100010','10111011110',
    '10111101110','11101011110','11110101110',
    '11010000100', /* 103 Start A */
    '11010010000', /* 104 Start B */
    '11010011100', /* 105 Start C */
    '11000111010', /* 106 Stop   */
];

/* ── Codificar en Code 128-B ── */
$bits     = $P[104]; /* Start B */
$checksum = 104;
$pos      = 1;

for ($i = 0; $i < strlen($ref); $i++) {
    $ascii = ord($ref[$i]);
    $val   = $ascii - 32;
    if ($val < 0 || $val > 95) continue;
    $bits     .= $P[$val];
    $checksum += $val * $pos;
    $pos++;
}

$checksum %= 103;
$bits .= $P[$checksum];
$bits .= $P[106]; /* Stop */
$bits .= '11';    /* Terminador final (2 módulos) */

/* ── Calcular dimensiones SVG ── */
$mw         = 2;   /* módulos → píxeles */
$barH       = 70;
$textH      = 16;
$quiet      = 16;
$totalW     = $quiet * 2 + strlen($bits) * $mw;
$totalH     = $barH + $textH + 4;

header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<svg xmlns="http://www.w3.org/2000/svg"
     width="<?= $totalW ?>"
     height="<?= $totalH ?>"
     viewBox="0 0 <?= $totalW ?> <?= $totalH ?>">
  <rect width="<?= $totalW ?>" height="<?= $totalH ?>" fill="white"/>
<?php
$x   = $quiet;
$len = strlen($bits);
$i   = 0;
while ($i < $len) {
    if ($bits[$i] === '1') {
        /* Agrupar módulos consecutivos del mismo color */
        $run = 1;
        while ($i + $run < $len && $bits[$i + $run] === '1') {
            $run++;
        }
        $bw = $run * $mw;
        echo "  <rect x=\"{$x}\" y=\"0\" width=\"{$bw}\" height=\"{$barH}\" fill=\"black\"/>\n";
        $x += $bw;
        $i += $run;
    } else {
        $x += $mw;
        $i++;
    }
}
?>
  <text x="<?= (int)($totalW / 2) ?>"
        y="<?= $barH + $textH ?>"
        text-anchor="middle"
        font-family="Courier New, monospace"
        font-size="11"
        fill="#222"
        letter-spacing="1"><?= htmlspecialchars($ref, ENT_XML1) ?></text>
</svg>
