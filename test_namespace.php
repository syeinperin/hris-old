<?php
// test_namespace.php

$file = __DIR__ . '/app/Http/Controllers/DataAnalyticsController.php';

$lines = file($file);
echo "---- First 5 lines of DataAnalyticsController.php ----\n";
for ($i = 0; $i < min(5, count($lines)); $i++) {
    echo ($i+1) . ": " . $lines[$i];
}

echo "\n\n---- Raw Bytes at Start ----\n";
$handle = fopen($file, "rb");
$bytes = fread($handle, 20);
fclose($handle);

for ($i = 0; $i < strlen($bytes); $i++) {
    printf("%02X ", ord($bytes[$i]));
}
echo "\n";
