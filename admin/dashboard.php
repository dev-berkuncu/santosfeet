<?php
/**
 * Admin Dashboard – Stats overview
 */
require_once __DIR__ . '/../lib/db.php';

$pdo = get_pdo();

$stats = [
    'characters' => $pdo->query("SELECT COUNT(*) FROM characters")->fetchColumn(),
    'photos'     => $pdo->query("SELECT COUNT(*) FROM photos")->fetchColumn(),
    'published'  => $pdo->query("SELECT COUNT(*) FROM photos WHERE is_published = 1")->fetchColumn(),
    'requests'   => $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'open'")->fetchColumn(),
];

$pageTitle = 'Dashboard';
include __DIR__ . '/partials/admin_header.php';
?>

<h2 class="h4 mb-4">Dashboard</h2>

<div class="row g-3">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary text-center p-3">
            <div class="display-6 text-warning"><?= $stats['characters'] ?></div>
            <small class="text-muted">Characters</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary text-center p-3">
            <div class="display-6 text-info"><?= $stats['photos'] ?></div>
            <small class="text-muted">Total Photos</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary text-center p-3">
            <div class="display-6 text-success"><?= $stats['published'] ?></div>
            <small class="text-muted">Published</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary text-center p-3">
            <div class="display-6 text-danger"><?= $stats['requests'] ?></div>
            <small class="text-muted">Open Requests</small>
        </div>
    </div>
</div>

<div class="mt-4">
    <h5>Quick Links</h5>
    <a href="<?= SITE_URL ?>/admin/characters.php" class="btn btn-outline-warning btn-sm me-2"><i class="bi bi-plus-circle me-1"></i>Add Character</a>
    <a href="<?= SITE_URL ?>/admin/photos.php?tab=bulk" class="btn btn-outline-info btn-sm me-2"><i class="bi bi-cloud-upload me-1"></i>Bulk Add Photos</a>
    <a href="<?= SITE_URL ?>/admin/requests.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-envelope me-1"></i>View Requests</a>
</div>

<?php include __DIR__ . '/partials/admin_footer.php'; ?>
