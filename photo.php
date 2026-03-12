<?php
/**
 * Tekli fotoğraf detay sayfası
 */
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/'); exit; }

$pdo = get_pdo();
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS character_name, c.slug AS character_slug
    FROM photos p
    JOIN characters c ON c.id = p.character_id
    WHERE p.id = ? AND p.is_published = 1
");
$stmt->execute([$id]);
$photo = $stmt->fetch();

if (!$photo) {
    http_response_code(404);
    $pageTitle = 'Fotoğraf Bulunamadı';
    include __DIR__ . '/partials/header.php';
    echo '<div class="text-center py-5"><h2>Fotoğraf bulunamadı.</h2><a href="' . SITE_URL . '/" class="btn btn-outline-warning mt-3">Anasayfaya Dön</a></div>';
    include __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = ($photo['caption'] ?: $photo['character_name']) . ' – ' . SITE_TITLE;
include __DIR__ . '/partials/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/" class="text-warning">Anasayfa</a></li>
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/character.php?slug=<?= e($photo['character_slug']) ?>" class="text-warning"><?= e($photo['character_name']) ?></a></li>
        <li class="breadcrumb-item active text-muted"><?= e($photo['caption'] ?: 'Fotoğraf #' . $photo['id']) ?></li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-8">
        <img src="<?= e($photo['image_url']) ?>"
             alt="<?= e($photo['caption'] ?? $photo['character_name']) ?>"
             class="img-fluid rounded shadow"
             style="max-height:80vh; width:100%; object-fit:contain; background:#1e1e1e;"
             onerror="this.classList.add('broken');this.onerror=null;">
    </div>
    <div class="col-lg-4 mt-3 mt-lg-0">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="card-title text-warning"><?= e($photo['character_name']) ?></h5>
                <?php if ($photo['caption']): ?>
                    <p class="card-text"><?= e($photo['caption']) ?></p>
                <?php endif; ?>
                <?php if ($photo['source_url']): ?>
                    <a href="<?= e($photo['source_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm mb-2">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Kaynak
                    </a>
                <?php endif; ?>
                <hr class="border-secondary">
                <small class="text-muted">Eklenme: <?= date('d.m.Y', strtotime($photo['created_at'])) ?></small>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
