<?php
/**
 * Search page – find characters + show photos
 */
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

$q = trim($_GET['q'] ?? '');
$pdo = get_pdo();

$pageTitle = 'Search – ' . SITE_TITLE;
include __DIR__ . '/partials/header.php';

if ($q === ''):
?>
    <h2 class="h4 mb-3">Search Characters</h2>
    <p class="text-muted">Enter a character name in the search bar above.</p>
<?php
else:
    // 1) Exact match on name or slug
    $stmt = $pdo->prepare("SELECT * FROM characters WHERE name = ? OR slug = ? LIMIT 1");
    $stmt->execute([$q, $q]);
    $exact = $stmt->fetch();

    if ($exact) {
        // Redirect to character page
        header('Location: ' . SITE_URL . '/character.php?slug=' . urlencode($exact['slug']));
        exit;
    }

    // 2) LIKE search
    $stmt = $pdo->prepare("SELECT * FROM characters WHERE name LIKE ? OR slug LIKE ? ORDER BY name");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
    $results = $stmt->fetchAll();

    if (count($results) === 1) {
        // Single result → redirect
        header('Location: ' . SITE_URL . '/character.php?slug=' . urlencode($results[0]['slug']));
        exit;
    }
?>
    <h2 class="h4 mb-3">Search results for "<?= e($q) ?>"</h2>

    <?php if (empty($results)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-search" style="font-size:3rem"></i>
            <p class="mt-2">No characters found matching "<?= e($q) ?>".</p>
        </div>
    <?php else: ?>
        <p class="text-muted"><?= count($results) ?> character(s) found:</p>
        <?php foreach ($results as $char): ?>
            <div class="search-result-item">
                <a href="<?= SITE_URL ?>/character.php?slug=<?= e($char['slug']) ?>" class="text-warning text-decoration-none fw-bold">
                    <?= e($char['name']) ?>
                </a>
                <?php
                    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM photos WHERE character_id = ? AND is_published = 1");
                    $cntStmt->execute([$char['id']]);
                    $cnt = $cntStmt->fetchColumn();
                ?>
                <small class="text-muted ms-2">(<?= $cnt ?> photos)</small>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php
endif;
include __DIR__ . '/partials/footer.php';
?>
