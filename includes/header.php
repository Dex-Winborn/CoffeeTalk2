<?php
// Top part of every page: HTML head, announcement bar and navigation.
// A page sets variables such as $pageTitle before including this file to change what is shown.
require_once __DIR__ . "/functions.php";
// Default values, used when the page did not set its own.
$pageTitle=$pageTitle??"Coffee Talk"; $bodyClass=$bodyClass??"app-body"; $navMode=$navMode??"app"; $activePage=$activePage??"";
$metaDescription=$metaDescription??"Coffee Talk café ordering and menu management system.";
$announcement=$announcement??"Coffee Talk Café System <span>·</span> Menu &amp; order management";
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="theme-color" content="#002830" /><meta name="description" content="<?= e($metaDescription) ?>" />
<title><?= e($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="<?= e(url('css/style.css')) ?>" /></head>
<body class="<?= e($bodyClass) ?>"><div class="announcement"><?= $announcement ?></div>
<?php /* The navigation menu changes depending on whether the visitor is logged in. */ ?>
<?php require __DIR__ . "/navigation.php"; ?>
