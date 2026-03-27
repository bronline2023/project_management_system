<?php
$pdo = new PDO('mysql:host=localhost;dbname=digital_services', 'root', '');
$stmt = $pdo->query('SELECT * FROM settings LIMIT 1');
print_r($stmt->fetch(PDO::FETCH_ASSOC));
