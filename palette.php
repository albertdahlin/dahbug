<?php
$white = '0;37';
$black = '0;30';

echo "\n";
echo "Standard colors:\n";
echo "\n  ";

for ($i = 0; $i < 16; $i++) {
    $color = $i;
    if ($i === 0) {
        $textColor = $white;
    } else {
        $textColor = $black;
    }
    $label = str_pad($color, 3, '0', STR_PAD_LEFT);
    echo "\033[{$textColor};48;5;{$color}m   {$label}   \033[0m";
    if ($i % 8 === 7) {
        echo "\n  ";
    }
}
echo "\n";
echo "\n";
echo "Extended colors rgb 6x6x6 (216)\n";
echo "\n";
for ($r = 0; $r < 6; $r++) {
    for ($g = 0; $g < 6; $g++) {
        echo "  ";
        for ($b = 0; $b < 6; $b++) {
            $color = 16 + 36 * $r + 6 * $g + $b;
            $label = str_pad($color, 3, '0', STR_PAD_LEFT);
            if (($r + 2 * $g + $b / 2) > 5) {
                $textColor = $black;
            } else {
                $textColor = $white;
            }

            echo "\033[{$textColor};48;5;{$color}m   {$label}   \033[0m";
        }
        echo "\n";
    }
    echo "\n";
}


echo "\n";
echo "\n";
echo "Grayscale:\n";
echo "\n  ";
for ($i = 232; $i < 256; $i++) {
    $color = $i;
    if ($i < 242) {
        $textColor = $white;
    } else {
        $textColor = $black;
    }
    $label = str_pad($color, 3, '0', STR_PAD_LEFT);
    echo "\033[{$textColor};48;5;{$color}m   {$label}   \033[0m";
    if ($i % 4 === 3) {
        echo "\n  ";
    }
}

echo "\n";
