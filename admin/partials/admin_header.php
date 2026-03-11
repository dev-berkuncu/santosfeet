<?php
/**
 * Admin header partial - includes sidebar
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Admin') ?> – <?= e(SITE_TITLE) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/styles.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="admin-sidebar d-flex flex-column p-3" style="width:220px">
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="text-warning fw-bold mb-4 text-decoration-none">
            <i class="bi bi-speedometer2 me-1"></i>Admin Panel
        </a>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'dashboard') ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/dashboard.php"><i class="bi bi-house me-1"></i>Dashboard</a></li>
            <li class="nav-item"><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'characters') ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/characters.php"><i class="bi bi-people me-1"></i>Characters</a></li>
            <li class="nav-item"><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'photos') ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/photos.php"><i class="bi bi-images me-1"></i>Photos</a></li>
            <li class="nav-item"><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'requests') ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/requests.php"><i class="bi bi-envelope me-1"></i>Requests</a></li>
            <li class="nav-item"><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'change_password') ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/change_password.php"><i class="bi bi-key me-1"></i>Password</a></li>
            <li class="nav-item mt-3"><a class="nav-link text-danger" href="<?= SITE_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
        </ul>
        <div class="mt-auto">
            <a href="<?= SITE_URL ?>/" class="nav-link text-muted small"><i class="bi bi-globe me-1"></i>View Site</a>
        </div>
    </div>

    <!-- Main content -->
    <div class="flex-grow-1 p-4" style="min-height:100vh">
