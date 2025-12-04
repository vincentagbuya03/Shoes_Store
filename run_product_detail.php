<?php
chdir(__DIR__);
$_GET['id'] = 1;
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include product page to surface runtime errors in CLI
include __DIR__ . '/product-detail.php';
