<?php
/**
 * Arama sayfası – karakter ara + fotoğrafları göster
 */
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

$q = trim($_GET['q'] ?? '');
$pdo = get_pdo();

$pageTitle = 'Arama – ' . SITE_TITLE;
include __DIR__ . '/partials/header.php';

if ($q === ''):
?>
    <h2 class="h4 mb-3">Karakter Ara</h2>
    <p class="text-muted">Yukarıdaki arama çubuğuna bir karakter adı yazın.</p>
<?php
else:
    // 1) İsim veya slug ile tam eşleşme
    $stmt = $pdo->prepare("SELECT * FROM characters WHERE name = ? OR slug = ? LIMIT 1");
    $stmt->execute([$q, $q]);
    $exact = $stmt->fetch();

    if ($exact) {
        // Karakter sayfasına yönlendir
        header('Location: ' . SITE_URL . '/character.php?slug=' . urlencode($exact['slug']));
        exit;
    }

    // 2) LIKE araması
    $stmt = $pdo->prepare("SELECT * FROM characters WHERE name LIKE ? OR slug LIKE ? ORDER BY name");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
    $results = $stmt->fetchAll();

    if (count($results) === 1) {
        // Tek sonuç → yönlendir
        header('Location: ' . SITE_URL . '/character.php?slug=' . urlencode($results[0]['slug']));
        exit;
    }
?>
    <h2 class="h4 mb-3">"<?= e($q) ?>" için arama sonuçları</h2>

    <?php if (empty($results)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-search" style="font-size:3rem"></i>
            <p class="mt-2">"<?= e($q) ?>" ile eşleşen karakter bulunamadı.</p>
        </div>
    <?php else: ?>
        <p class="text-muted"><?= count($results) ?> karakter bulundu:</p>
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
                <small class="text-muted ms-2">(<?= $cnt ?> fotoğraf)</small>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php
endif;
include __DIR__ . '/partials/footer.php';
?>
