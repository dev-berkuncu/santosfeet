<?php
/**
 * Header partial – Bootstrap 5 + Navbar
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/helpers.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? SITE_TITLE) ?></title>
    <meta name="description" content="<?= e($metaDesc ?? 'Santosfeet – Santos RP karakter fotoğraf galerisi.') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/styles.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>/">
            <i class="bi bi-camera-fill me-1"></i><?= e(SITE_TITLE) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/">Anasayfa</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/contact.php">İletişim</a></li>
            </ul>
            <form class="d-flex me-2" action="<?= SITE_URL ?>/search.php" method="GET">
                <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Karakter ara…" value="<?= e($_GET['q'] ?? '') ?>">
                <button class="btn btn-outline-light btn-sm" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <a href="<?= e(DONATE_URL) ?>" class="btn btn-warning btn-sm" target="_blank" rel="noopener">
                <i class="bi bi-heart-fill me-1"></i>Bağış Yap
            </a>
        </div>
    </div>
</nav>

<main class="container py-4">
