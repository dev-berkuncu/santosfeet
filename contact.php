<?php
/**
 * İletişim / İçerik kaldırma talebi formu
 */
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/csrf.php';

$pdo = get_pdo();
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email   = trim($_POST['email'] ?? '');
    $pageUrl = trim($_POST['page_url'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Lütfen geçerli bir e-posta adresi girin.';
    }
    if (!$message) {
        $errors[] = 'Lütfen mesajınızı yazın.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO requests (email, page_url, message) VALUES (?, ?, ?)");
        $stmt->execute([$email, $pageUrl ?: null, $message]);
        $success = true;
    }
}

$pageTitle = 'İletişim – ' . SITE_TITLE;
include __DIR__ . '/partials/header.php';
?>

<h1 class="h3 mb-4">İletişim / İçerik Kaldırma Talebi</h1>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle me-1"></i> Mesajınız gönderildi. En kısa sürede dönüş yapılacaktır.
    </div>
<?php else: ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <form method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-posta *</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="page_url" class="form-label">İlgili Sayfa URL'si</label>
                            <input type="url" class="form-control" id="page_url" name="page_url" placeholder="https://..." value="<?= e($_POST['page_url'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Mesaj *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?= e($_POST['message'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning w-100"><i class="bi bi-send me-1"></i>Gönder</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
