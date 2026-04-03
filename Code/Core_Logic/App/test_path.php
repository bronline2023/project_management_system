<?php
echo "__DIR__: " . __DIR__ . "\n";
echo "1 level: " . dirname(__DIR__) . "\n";
echo "2 levels: " . dirname(dirname(__DIR__)) . "\n";
echo "3 levels: " . dirname(dirname(dirname(__DIR__))) . "\n";
echo "Exists? " . (file_exists(dirname(dirname(dirname(__DIR__))) . '/config.php') ? "YES" : "NO") . "\n";
