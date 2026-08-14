<?php
// includes/header.php
if (!defined('DB_NAME')) {
    require_once __DIR__ . '/../config.php';
}
$pageTitle = $pageTitle ?? 'Online Boarding House Booking System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
