<?php
/**
 * Admin – Change Password
 */
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

$pdo = get_pdo();
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $current = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Get current admin
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($current, $admin['password_hash'])) {
        $msg = 'Current password is incorrect.';
        $msgType = 'danger';
    } elseif (strlen($newPass) < 6) {
        $msg = 'New password must be at least 6 characters.';
        $msgType = 'danger';
    } elseif ($newPass !== $confirm) {
        $msg = 'Password confirmation does not match.';
        $msgType = 'danger';
    } else {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")->execute([$hash, $admin['id']]);
        $msg = 'Password changed successfully.';
    }
}

$pageTitle = 'Change Password';
include __DIR__ . '/partials/admin_header.php';
?>

<h2 class="h4 mb-4">Change Password</h2>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> py-2"><?= e($msg) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-5">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100"><i class="bi bi-key me-1"></i>Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/admin_footer.php'; ?>
