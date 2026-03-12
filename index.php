<?php
/**
 * Anasayfa – Uyarı + fotoğraf galeri
 */
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

$pdo = get_pdo();

// Sayfalama
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

$pageTitle = SITE_TITLE . ' – Anasayfa';
include __DIR__ . '/partials/header.php';
?>

<!-- Uyarı -->
<div class="legal-notice">
    <strong>⚠️ 18+ İçerik Uyarısı</strong><br>
    Bu site <strong>Santos RP sunucusu</strong> için oluşturulmuş bir <strong>IC (In-Character)</strong> sitedir.
    Tüm karakterler tamamen <strong>kurgusaldır</strong>. Devam ederek 18 yaşından büyük olduğunuzu onaylıyorsunuz.
</div>

<!-- Fotoğraf Galerisi -->
<?php if (empty($photos)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-image" style="font-size:3rem"></i>
        <p class="mt-2">Henüz fotoğraf eklenmemiş.</p>
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
