<?php
/**
 * Admin – Characters CRUD + Quick Add
 */
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/csrf.php';

$pdo = get_pdo();
$msg = '';
$msgType = 'success';

// ─── DELETE ─────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $token = $_GET['token'] ?? '';
    if (hash_equals(csrf_token(), $token)) {
        $pdo->prepare("DELETE FROM characters WHERE id = ?")->execute([$delId]);
        $msg = 'Character deleted.';
    } else {
        $msg = 'Invalid CSRF token.';
        $msgType = 'danger';
    }
}

// ─── EDIT ───────────────────────────────────────────────────────────
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM characters WHERE id = ?");
    $stmt->execute([$editId]);
    $editChar = $stmt->fetch();
}

// ─── SAVE (Quick Add or Edit) ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_character'])) {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $charId = (int)($_POST['character_id'] ?? 0);

    if (!$name) {
        $msg = 'Name is required.';
        $msgType = 'danger';
    } else {
        if (!$slug) {
            $slug = slugify($name);
        } else {
            $slug = slugify($slug);
        }
        $slug = unique_slug($pdo, $slug, $charId ?: null);

        if ($charId) {
            // Update
            $stmt = $pdo->prepare("UPDATE characters SET name = ?, slug = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $charId]);
            $msg = "Character updated (slug: $slug).";
            unset($editChar); // clear edit mode
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO characters (name, slug) VALUES (?, ?)");
            $stmt->execute([$name, $slug]);
            $msg = "Character added (slug: $slug).";
        }
    }
}

// ─── LIST ───────────────────────────────────────────────────────────
$characters = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM photos WHERE character_id = c.id) AS photo_count FROM characters c ORDER BY c.name")->fetchAll();

$pageTitle = 'Characters';
include __DIR__ . '/partials/admin_header.php';
?>

<h2 class="h4 mb-4">Characters</h2>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> py-2"><?= e($msg) ?></div>
<?php endif; ?>

<!-- Quick Add / Edit Form -->
<div class="card bg-dark border-secondary mb-4">
    <div class="card-header">
        <i class="bi bi-lightning-fill text-warning me-1"></i>
        <?= isset($editChar) ? 'Edit Character' : 'Quick Add Character' ?>
    </div>
    <div class="card-body">
        <form method="POST" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <?php if (isset($editChar)): ?>
                <input type="hidden" name="character_id" value="<?= $editChar['id'] ?>">
            <?php endif; ?>
            <div class="col-md-4">
                <label class="form-label small">Name *</label>
                <input type="text" class="form-control form-control-sm" name="name" required value="<?= e(isset($editChar) ? $editChar['name'] : '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Slug <span class="text-muted">(auto if empty)</span></label>
                <input type="text" class="form-control form-control-sm" name="slug" value="<?= e(isset($editChar) ? $editChar['slug'] : '') ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" name="save_character" class="btn btn-warning btn-sm w-100">
                    <i class="bi bi-check-lg me-1"></i><?= isset($editChar) ? 'Update' : 'Add' ?>
                </button>
                <?php if (isset($editChar)): ?>
                    <a href="<?= SITE_URL ?>/admin/characters.php" class="btn btn-outline-secondary btn-sm w-100 mt-1">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Characters Table -->
<div class="table-responsive">
    <table class="table table-dark table-hover table-sm">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Photos</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($characters as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= e($c['name']) ?></td>
                <td><code><?= e($c['slug']) ?></code></td>
                <td><?= $c['photo_count'] ?></td>
                <td><?= date('Y-m-d', strtotime($c['created_at'])) ?></td>
                <td>
                    <a href="<?= SITE_URL ?>/character.php?slug=<?= e($c['slug']) ?>" class="btn btn-outline-light btn-sm" target="_blank" title="View"><i class="bi bi-eye"></i></a>
                    <a href="?edit=<?= $c['id'] ?>" class="btn btn-outline-warning btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a href="?delete=<?= $c['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-outline-danger btn-sm" title="Delete" onclick="return confirm('Delete this character and all its photos?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($characters)): ?>
                <tr><td colspan="6" class="text-center text-muted">No characters yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/partials/admin_footer.php'; ?>
