<?php
$path = __DIR__ . '/../app/Http/Controllers/SaleController.php';
$s = file_get_contents($path);
echo 'opens=' . substr_count($s, '{') . " closes=" . substr_count($s, '}') . PHP_EOL;
