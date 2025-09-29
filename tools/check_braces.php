<?php
$path = __DIR__ . '/../app/Http/Controllers/SaleController.php';
if (!file_exists($path)) {
    echo "file not found: $path\n";
    exit(1);
}
$lines = file($path);
$balance = 0;
foreach ($lines as $i => $line) {
    $open = substr_count($line, '{');
    $close = substr_count($line, '}');
    $old = $balance;
    $balance += $open - $close;
    if ($balance > $old) {
        $start = max(0, $i - 2);
        $end = min(count($lines) - 1, $i + 2);
        echo "Context around line " . ($i + 1) . PHP_EOL;
        for ($j = $start; $j <= $end; $j++) {
            echo ($j + 1) . ": " . rtrim($lines[$j]) . PHP_EOL;
        }
        echo "---- balance increased $old -> $balance\n";
    }
}
echo "FINAL BALANCE: $balance" . PHP_EOL;
