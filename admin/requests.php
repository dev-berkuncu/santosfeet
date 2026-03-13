<?php
/**
 * Admin – Contact/Takedown Requests
 */
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/csrf.php';

$pdo = get_pdo();
$msg = '';

// Close request
if (isset($_GET['close'])) {
    $closeId = (int)$_GET['close'];
    $token = $_GET['token'] ?? '';
    if (hash_equals(csrf_token(), $token)) {
        $pdo->prepare("UPDATE requests SET status = 'closed' WHERE id = ?")->execute([$closeId]);
        $msg = 'Request closed.';
    }
}

// Reopen
if (isset($_GET['reopen'])) {
    $reopenId = (int)$_GET['reopen'];
    $token = $_GET['token'] ?? '';
    if (hash_equals(csrf_token(), $token)) {
        $pdo->prepare("UPDATE requests SET status = 'open' WHERE id = ?")->execute([$reopenId]);
        $msg = 'Request reopened.';
    }
}

// Delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $token = $_GET['token'] ?? '';
    if (hash_equals(csrf_token(), $token)) {
        $pdo->prepare("DELETE FROM requests WHERE id = ?")->execute([$delId]);
        $msg = 'Request deleted.';
    }
}

$filter = $_GET['filter'] ?? 'open';
$where = $filter === 'all' ? '' : "WHERE status = ?";
$params = $filter === 'all' ? [] : [$filter];

$stmt = $pdo->prepare("SELECT * FROM requests $where ORDER BY created_at DESC");
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = 'Requests';
include __DIR__ . '/partials/admin_header.php';
?>

<h2 class="h4 mb-3">Contact Requests</h2>

<?php if ($msg): ?>
    <div class="alert alert-success py-2"><?= e($msg) ?></div>
<?php endif; ?>

<div class="mb-3">
    <a href="?filter=open" class="btn btn-sm <?= $filter === 'open' ? 'btn-warning' : 'btn-outline-warning' ?>">Open</a>
    <a href="?filter=closed" class="btn btn-sm <?= $filter === 'closed' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Closed</a>
    <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-light' : 'btn-outline-light' ?>">All</a>
</div>

<div class="table-responsive">
<table class="table table-dark table-hover table-sm">
    <thead>
        <tr><th>#</th><th>Email</th><th>Page URL</th><th>Message</th><th>Status</th><th>Date</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php foreach ($requests as $r): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= e($r['email']) ?></td>
            <td class="text-truncate" style="max-width:150px"><?= e($r['page_url'] ?? '') ?></td>
            <td class="text-truncate" style="max-width:250px"><?= e($r['message']) ?></td>
            <td>
                <?php if ($r['status'] === 'open'): ?>
                    <span class="badge bg-warning text-dark">Open</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Closed</span>
                <?php endif; ?>
            </td>
            <td><small><?= date('Y-m-d H:i', strtotime($r['created_at'])) ?></small></td>
            <td>
                <?php if ($r['status'] === 'open'): ?>
                    <a href="?filter=<?= $filter ?>&close=<?= $r['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-outline-success btn-sm" title="Close"><i class="bi bi-check-lg"></i></a>
                <?php else: ?>
                    <a href="?filter=<?= $filter ?>&reopen=<?= $r['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-outline-warning btn-sm" title="Reopen"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
                <a href="?filter=<?= $filter ?>&delete=<?= $r['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-outline-danger btn-sm" title="Delete" onclick="return confirm('Delete this request?')"><i class="bi bi-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($requests)): ?>
            <tr><td colspan="7" class="text-center text-muted">No requests.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<?php include __DIR__ . '/partials/admin_footer.php'; ?>
