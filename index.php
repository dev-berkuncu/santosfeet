<?php
/**
 * Homepage – Legal notice + photo grid
 */
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

$pdo = get_pdo();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$countStmt = $pdo->query("SELECT COUNT(*) FROM photos WHERE is_published = 1");
$total = (int)$countStmt->fetchColumn();
$pg = paginate($total, ITEMS_PER_PAGE, $page);

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS character_name, c.slug AS character_slug
    FROM photos p
    JOIN characters c ON c.id = p.character_id
    WHERE p.is_published = 1
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $pg['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$photos = $stmt->fetchAll();

$pageTitle = SITE_TITLE . ' – Home';
include __DIR__ . '/partials/header.php';
?>

<!-- Legal Notice -->
<div class="legal-notice">
    <strong>⚠️ 18+ Content Warning</strong><br>
    This is an <strong>unofficial fan site</strong>. All characters are <strong>fictional</strong> and belong to <strong>Rockstar Games</strong>.
    No real persons are depicted. By continuing, you confirm you are 18 years or older.
</div>

<!-- Photo Grid -->
<?php if (empty($photos)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-image" style="font-size:3rem"></i>
        <p class="mt-2">No photos yet.</p>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($photos as $photo): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= SITE_URL ?>/photo.php?id=<?= $photo['id'] ?>" class="text-decoration-none">
                <div class="photo-card">
                    <img src="<?= e($photo['image_url']) ?>"
                         alt="<?= e($photo['caption'] ?? $photo['character_name']) ?>"
                         loading="lazy"
                         onerror="this.classList.add('broken');this.onerror=null;">
                    <div class="card-body">
                        <?php if ($photo['caption']): ?>
                            <small class="text-light d-block text-truncate"><?= e($photo['caption']) ?></small>
                        <?php endif; ?>
                        <small class="character-name">
                            <a href="<?= SITE_URL ?>/character.php?slug=<?= e($photo['character_slug']) ?>" class="text-decoration-none text-warning">
                                <?= e($photo['character_name']) ?>
                            </a>
                        </small>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <?= render_pagination($pg, SITE_URL . '/index.php') ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
