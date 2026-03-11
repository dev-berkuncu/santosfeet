<?php
/**
 * Character page – shows all photos for a character by slug
 */
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . SITE_URL . '/'); exit; }

$pdo = get_pdo();

$stmt = $pdo->prepare("SELECT * FROM characters WHERE slug = ?");
$stmt->execute([$slug]);
$character = $stmt->fetch();
if (!$character) {
    http_response_code(404);
    $pageTitle = 'Character Not Found';
    include __DIR__ . '/partials/header.php';
    echo '<div class="text-center py-5"><h2>Character not found.</h2><a href="' . SITE_URL . '/" class="btn btn-outline-warning mt-3">Go Home</a></div>';
    include __DIR__ . '/partials/footer.php';
    exit;
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM photos WHERE character_id = ? AND is_published = 1");
$countStmt->execute([$character['id']]);
$total = (int)$countStmt->fetchColumn();
$pg = paginate($total, ITEMS_PER_PAGE, $page);

$stmt = $pdo->prepare("
    SELECT * FROM photos
    WHERE character_id = ? AND is_published = 1
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(1, $character['id'], PDO::PARAM_INT);
$stmt->bindValue(':limit', $pg['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$photos = $stmt->fetchAll();

$pageTitle = e($character['name']) . ' – ' . SITE_TITLE;
$metaDesc = 'Photos of ' . $character['name'] . ' from GTA V.';
include __DIR__ . '/partials/header.php';
?>

<h1 class="h3 mb-1"><?= e($character['name']) ?></h1>
<p class="text-muted mb-4"><?= $total ?> photo<?= $total !== 1 ? 's' : '' ?></p>

<?php if (empty($photos)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-image" style="font-size:3rem"></i>
        <p class="mt-2">No photos for this character yet.</p>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($photos as $photo): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= SITE_URL ?>/photo.php?id=<?= $photo['id'] ?>" class="text-decoration-none">
                <div class="photo-card">
                    <img src="<?= e($photo['image_url']) ?>"
                         alt="<?= e($photo['caption'] ?? $character['name']) ?>"
                         loading="lazy"
                         onerror="this.classList.add('broken');this.onerror=null;">
                    <div class="card-body">
                        <?php if ($photo['caption']): ?>
                            <small class="text-light d-block text-truncate"><?= e($photo['caption']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <?= render_pagination($pg, SITE_URL . '/character.php?slug=' . urlencode($character['slug'])) ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
