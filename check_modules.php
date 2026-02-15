<?php
require 'vendor/autoload.php';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=nexo', 'root', 'Heaven');

// Re-enable WhatsApp module
$modules = ["2" => "MyNexoPOS", "4" => "Commission", "5" => "Skeleton", "6" => "WhatsApp"];
$json = json_encode($modules);

$stmt = $pdo->prepare("UPDATE nexopos_options SET value = ? WHERE `key` = 'enabled_modules'");
$stmt->execute([$json]);

echo "Updated. WhatsApp module enabled.\n";
