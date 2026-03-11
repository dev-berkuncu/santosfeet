<?php
/**
 * Admin – Photos CRUD + Bulk Add (Step 1 / Step 2 / Step 3)
 */
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

$pdo = get_pdo();
$msg = '';
$msgType = 'success';
$tab = $_GET['tab'] ?? $_POST['tab'] ?? 'list';

// ─── Characters for dropdowns ──────────────────────────────────────
$characters = $pdo->query("SELECT * FROM characters ORDER BY name")->fetchAll();

// ═══════════════════════════════════════════════════════════════════
//  SINGLE PHOTO ACTIONS (list tab)
// ═══════════════════════════════════════════════════════════════════

// Delete single photo
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $token = $_GET['token'] ?? '';
    if (hash_equals(csrf_token(), $token)) {
        $pdo->prepare("DELETE FROM photos WHERE id = ?")->execute([$delId]);
        $msg = 'Photo deleted.';
    }
}

// Toggle publish
if (isset($_GET['toggle'])) {
    $togId = (int)$_GET['toggle'];
    $token = $_GET['token'] ?? '';
    if (hash_equals(csrf_token(), $token)) {
        $pdo->prepare("UPDATE photos SET is_published = NOT is_published WHERE id = ?")->execute([$togId]);
        $msg = 'Publish status toggled.';
    }
}

// Add single photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_single'])) {
    csrf_check();
    $charId   = (int)($_POST['character_id'] ?? 0);
    $imageUrl = normalize_url(trim($_POST['image_url'] ?? ''));
    $sourceUrl = normalize_url(trim($_POST['source_url'] ?? ''));
    $caption  = trim($_POST['caption'] ?? '');

    if (!$charId || !$imageUrl) {
        $msg = 'Character and Image URL are required.';
        $msgType = 'danger';
    } elseif (!validate_url($imageUrl)) {
        $msg = 'Invalid image URL.';
        $msgType = 'danger';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO photos (character_id, image_url, source_url, caption) VALUES (?, ?, ?, ?)");
            $stmt->execute([$charId, $imageUrl, $sourceUrl ?: null, $caption ?: null]);
            $msg = 'Photo added.';
        } catch (PDOException $ex) {
            if ($ex->getCode() == 23000) {
                $msg = 'This photo URL already exists for this character.';
            } else {
                $msg = 'Error: ' . $ex->getMessage();
            }
            $msgType = 'danger';
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
//  BULK ADD – Step 2 (Preview / Re-Preview) and Step 3 (Import)
// ═══════════════════════════════════════════════════════════════════
$bulkStep = 1;
$bulkRows = [];
$bulkStats = [];
$bulkCharId = 0;
$bulkResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'bulk') {
    csrf_check();
    $bulkCharId = (int)($_POST['bulk_character_id'] ?? 0);

    // ── Step 1 → Step 2: Parse raw text ────────────────────────────
    if (isset($_POST['bulk_parse'])) {
        $rawText = $_POST['bulk_text'] ?? '';
        $bulkRows = parse_bulk_text($rawText);
        // validate each
        foreach ($bulkRows as &$r) { $r = validate_row($r); }
        unset($r);
        // dedupe in paste
        $bulkRows = dedupe_rows_in_paste($bulkRows);
        // mark existing in DB
        if ($bulkCharId) {
            $bulkRows = mark_existing_in_db($pdo, $bulkCharId, $bulkRows);
        }
        $bulkStep = 2;
    }

    // ── Step 2 → Step 2: Re-Preview (after edits) ──────────────────
    if (isset($_POST['bulk_repreview'])) {
        $bulkRows = rebuild_rows_from_post();
        foreach ($bulkRows as &$r) { $r = validate_row($r); }
        unset($r);
        $bulkRows = dedupe_rows_in_paste($bulkRows);
        if ($bulkCharId) {
            $bulkRows = mark_existing_in_db($pdo, $bulkCharId, $bulkRows);
        }
        $bulkStep = 2;
    }

    // ── Step 2 action: Auto-fix all ─────────────────────────────────
    if (isset($_POST['bulk_autofix_all'])) {
        $bulkRows = rebuild_rows_from_post();
        foreach ($bulkRows as &$r) { $r = apply_autofix_row($r); }
        unset($r);
        foreach ($bulkRows as &$r) { $r = validate_row($r); }
        unset($r);
        $bulkRows = dedupe_rows_in_paste($bulkRows);
        if ($bulkCharId) { $bulkRows = mark_existing_in_db($pdo, $bulkCharId, $bulkRows); }
        $bulkStep = 2;
    }

    // ── Step 2 action: Auto-fix single row ──────────────────────────
    if (isset($_POST['bulk_autofix_single'])) {
        $fixIdx = (int)$_POST['fix_index'];
        $bulkRows = rebuild_rows_from_post();
        if (isset($bulkRows[$fixIdx])) {
            $bulkRows[$fixIdx] = apply_autofix_row($bulkRows[$fixIdx]);
        }
        foreach ($bulkRows as &$r) { $r = validate_row($r); }
        unset($r);
        $bulkRows = dedupe_rows_in_paste($bulkRows);
        if ($bulkCharId) { $bulkRows = mark_existing_in_db($pdo, $bulkCharId, $bulkRows); }
        $bulkStep = 2;
    }

    // ── Step 2 action: Remove single row ────────────────────────────
    if (isset($_POST['bulk_remove_single'])) {
        $rmIdx = (int)$_POST['remove_index'];
        $bulkRows = rebuild_rows_from_post();
        unset($bulkRows[$rmIdx]);
        $bulkRows = array_values($bulkRows);
        foreach ($bulkRows as &$r) { $r = validate_row($r); }
        unset($r);
        $bulkRows = dedupe_rows_in_paste($bulkRows);
        if ($bulkCharId) { $bulkRows = mark_existing_in_db($pdo, $bulkCharId, $bulkRows); }
        $bulkStep = 2;
    }

    // ── Step 2 action: Remove invalid ───────────────────────────────
    if (isset($_POST['bulk_remove_invalid'])) {
        $bulkRows = rebuild_rows_from_post();
        $bulkRows = array_values(array_filter($bulkRows, fn($r) => ($r['status'] ?? validate_row($r)['status']) !== 'INVALID'));
        foreach ($bulkRows as &$r) { $r = validate_row($r); }
        unset($r);
        $bulkRows = dedupe_rows_in_paste($bulkRows);
        if ($bulkCharId) { $bulkRows = mark_existing_in_db($pdo, $bulkCharId, $bulkRows); }
        $bulkStep = 2;
    }

    // ── Step 2 action: Keep first of duplicates ─────────────────────
    if (isset($_POST['bulk_keep_first_dup'])) {
        $bulkRows = rebuild_rows_from_post();
        foreach ($bulkRows as &$r) { $r = validate_row($r); }
        unset($r);
        $bulkRows = dedupe_rows_in_paste($bulkRows);
        // remove DUP_PASTE rows
        $bulkRows = array_values(array_filter($bulkRows, fn($r) => $r['status'] !== 'DUP_PASTE'));
        // re-validate
        $bulkRows = dedupe_rows_in_paste($bulkRows);
        if ($bulkCharId) { $bulkRows = mark_existing_in_db($pdo, $bulkCharId, $bulkRows); }
        $bulkStep = 2;
    }

    // ── Step 2 action: Remove exists (DB) ───────────────────────────
    if (isset($_POST['bulk_remove_exists'])) {
        $bulkRows = rebuild_rows_from_post();
        foreach ($bulkRows as &$r) { $r = validate_row($r); }
        unset($r);
        $bulkRows = dedupe_rows_in_paste($bulkRows);
        if ($bulkCharId) { $bulkRows = mark_existing_in_db($pdo, $bulkCharId, $bulkRows); }
        $bulkRows = array_values(array_filter($bulkRows, fn($r) => $r['status'] !== 'EXISTS'));
        $bulkStep = 2;
    }

    // ── Step 3: Import ──────────────────────────────────────────────
    if (isset($_POST['bulk_import'])) {
        $bulkRows = rebuild_rows_from_post();
        // re-validate before import
        foreach ($bulkRows as &$r) { $r = validate_row($r); }
        unset($r);
        $bulkRows = dedupe_rows_in_paste($bulkRows);
        if ($bulkCharId) { $bulkRows = mark_existing_in_db($pdo, $bulkCharId, $bulkRows); }

        $result = ['inserted' => 0, 'skipped_exists' => 0, 'skipped_dup_paste' => 0, 'invalid' => 0, 'removed' => 0];
        try {
            $pdo->beginTransaction();
            $insStmt = $pdo->prepare("INSERT INTO photos (character_id, image_url, source_url, caption) VALUES (?, ?, ?, ?)");

            foreach ($bulkRows as $r) {
                switch ($r['status']) {
                    case 'OK':
                        $insStmt->execute([
                            $bulkCharId,
                            $r['image_url'],
                            $r['source_url'] ?: null,
                            $r['caption'] ?: null,
                        ]);
                        $result['inserted']++;
                        break;
                    case 'EXISTS':
                        $result['skipped_exists']++;
                        break;
                    case 'DUP_PASTE':
                        $result['skipped_dup_paste']++;
                        break;
                    case 'INVALID':
                        $result['invalid']++;
                        break;
                    default:
                        $result['removed']++;
                }
            }
            $pdo->commit();
            $bulkResult = $result;
            $bulkStep = 3;
        } catch (PDOException $ex) {
            $pdo->rollBack();
            $msg = 'Import failed: ' . $ex->getMessage();
            $msgType = 'danger';
            $bulkStep = 2;
        }
    }
}

/**
 * Rebuild rows array from posted form inputs
 */
function rebuild_rows_from_post(): array {
    $posted = $_POST['rows'] ?? [];
    $rows = [];
    foreach ($posted as $idx => $data) {
        $rows[] = [
            'lineNo'     => (int)($data['lineNo'] ?? $idx + 1),
            'raw'        => '',
            'image_url'  => trim($data['image_url'] ?? ''),
            'source_url' => trim($data['source_url'] ?? ''),
            'caption'    => trim($data['caption'] ?? ''),
            'status'     => 'OK',
            'errors'     => [],
        ];
    }
    return $rows;
}

// ═══════════════════════════════════════════════════════════════════
//  Compute bulk stats
// ═══════════════════════════════════════════════════════════════════
if ($bulkStep === 2 && !empty($bulkRows)) {
    $bulkStats = [
        'total'     => count($bulkRows),
        'ok'        => count(array_filter($bulkRows, fn($r) => $r['status'] === 'OK')),
        'invalid'   => count(array_filter($bulkRows, fn($r) => $r['status'] === 'INVALID')),
        'dup_paste' => count(array_filter($bulkRows, fn($r) => $r['status'] === 'DUP_PASTE')),
        'exists'    => count(array_filter($bulkRows, fn($r) => $r['status'] === 'EXISTS')),
    ];
}

// ═══════════════════════════════════════════════════════════════════
//  LIST TAB: Paginated photos
// ═══════════════════════════════════════════════════════════════════
if ($tab === 'list') {
    $filterChar = (int)($_GET['character_id'] ?? 0);
    $page = max(1, (int)($_GET['page'] ?? 1));

    $where = '';
    $params = [];
    if ($filterChar) {
        $where = ' WHERE p.character_id = ?';
        $params[] = $filterChar;
    }

    $countSql = "SELECT COUNT(*) FROM photos p" . $where;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pg = paginate($total, 20, $page);

    $sql = "SELECT p.*, c.name AS character_name FROM photos p
            JOIN characters c ON c.id = p.character_id
            $where ORDER BY p.created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}";
    $listStmt = $pdo->prepare($sql);
    $listStmt->execute($params);
    $photoList = $listStmt->fetchAll();
}

$pageTitle = 'Photos';
include __DIR__ . '/partials/admin_header.php';
?>

<h2 class="h4 mb-3">Photos</h2>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> py-2"><?= e($msg) ?></div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'list' ? 'active' : '' ?>" href="?tab=list">
            <i class="bi bi-list me-1"></i>All Photos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'add' ? 'active' : '' ?>" href="?tab=add">
            <i class="bi bi-plus-circle me-1"></i>Add Single
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'bulk' ? 'active' : '' ?>" href="?tab=bulk">
            <i class="bi bi-cloud-upload me-1"></i>Bulk Add
        </a>
    </li>
</ul>

<?php
// ═══════════════════════════════════════════════════════════════════
//  TAB: List
// ═══════════════════════════════════════════════════════════════════
if ($tab === 'list'):
?>
    <!-- Filter -->
    <form class="row g-2 mb-3" method="GET">
        <input type="hidden" name="tab" value="list">
        <div class="col-auto">
            <select name="character_id" class="form-select form-select-sm">
                <option value="0">All characters</option>
                <?php foreach ($characters as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterChar == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-warning btn-sm">Filter</button>
        </div>
    </form>

    <div class="table-responsive">
    <table class="table table-dark table-hover table-sm">
        <thead>
            <tr><th>#</th><th>Preview</th><th>Character</th><th>Caption</th><th>Published</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($photoList as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><img src="<?= e($p['image_url']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:4px" onerror="this.classList.add('broken');this.style.width='50px';this.style.height='50px';this.onerror=null;"></td>
                <td><?= e($p['character_name']) ?></td>
                <td class="text-truncate" style="max-width:200px"><?= e($p['caption'] ?? '') ?></td>
                <td>
                    <?php if ($p['is_published']): ?>
                        <span class="badge bg-success">Yes</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">No</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?tab=list&toggle=<?= $p['id'] ?>&token=<?= csrf_token() ?>&character_id=<?= $filterChar ?>" class="btn btn-outline-info btn-sm" title="Toggle publish"><i class="bi bi-toggle-on"></i></a>
                    <a href="?tab=list&delete=<?= $p['id'] ?>&token=<?= csrf_token() ?>&character_id=<?= $filterChar ?>" class="btn btn-outline-danger btn-sm" title="Delete" onclick="return confirm('Delete this photo?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($photoList)): ?>
                <tr><td colspan="6" class="text-center text-muted">No photos.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <?php
    $baseUrl = SITE_URL . '/admin/photos.php?tab=list' . ($filterChar ? '&character_id=' . $filterChar : '');
    echo render_pagination($pg, $baseUrl);
    ?>

<?php
// ═══════════════════════════════════════════════════════════════════
//  TAB: Add Single
// ═══════════════════════════════════════════════════════════════════
elseif ($tab === 'add'):
?>
    <div class="card bg-dark border-secondary">
        <div class="card-body">
            <form method="POST" action="?tab=list">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Character *</label>
                    <select name="character_id" class="form-select" required>
                        <option value="">Select…</option>
                        <?php foreach ($characters as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image URL *</label>
                    <input type="url" class="form-control" name="image_url" required placeholder="https://...">
                </div>
                <div class="mb-3">
                    <label class="form-label">Source URL</label>
                    <input type="url" class="form-control" name="source_url" placeholder="https://...">
                </div>
                <div class="mb-3">
                    <label class="form-label">Caption</label>
                    <input type="text" class="form-control" name="caption" maxlength="255">
                </div>
                <button type="submit" name="add_single" class="btn btn-warning"><i class="bi bi-plus-circle me-1"></i>Add Photo</button>
            </form>
        </div>
    </div>

<?php
// ═══════════════════════════════════════════════════════════════════
//  TAB: Bulk Add
// ═══════════════════════════════════════════════════════════════════
elseif ($tab === 'bulk'):
?>

    <?php if ($bulkStep === 3 && $bulkResult): ?>
        <!-- ── Step 3: Import Result ─────────────────────────────────── -->
        <div class="alert alert-success">
            <h5><i class="bi bi-check-circle me-1"></i>Import Complete!</h5>
            <table class="table table-sm table-dark mt-2 mb-0" style="max-width:400px">
                <tr><td>✅ Inserted</td><td class="fw-bold"><?= $bulkResult['inserted'] ?></td></tr>
                <tr><td>⏭ Skipped (exists)</td><td><?= $bulkResult['skipped_exists'] ?></td></tr>
                <tr><td>⏭ Skipped (duplicate)</td><td><?= $bulkResult['skipped_dup_paste'] ?></td></tr>
                <tr><td>❌ Invalid</td><td><?= $bulkResult['invalid'] ?></td></tr>
                <tr><td>🗑 Removed</td><td><?= $bulkResult['removed'] ?></td></tr>
            </table>
        </div>
        <a href="?tab=bulk" class="btn btn-outline-warning"><i class="bi bi-arrow-repeat me-1"></i>New Bulk Add</a>
        <a href="?tab=list" class="btn btn-outline-light ms-2">View Photos</a>

    <?php elseif ($bulkStep === 2): ?>
        <!-- ── Step 2: Preview (Editable) ────────────────────────────── -->
        <div class="card bg-dark border-secondary mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-eye me-1"></i>Step 2: Preview & Edit</span>
                <a href="?tab=bulk" class="btn btn-outline-secondary btn-sm">← Back to Step 1</a>
            </div>
            <div class="card-body">
                <!-- Stats -->
                <div class="row g-2 mb-3">
                    <div class="col-auto"><span class="badge bg-secondary">Total: <?= $bulkStats['total'] ?></span></div>
                    <div class="col-auto"><span class="badge badge-ok">OK: <?= $bulkStats['ok'] ?></span></div>
                    <div class="col-auto"><span class="badge badge-invalid">Invalid: <?= $bulkStats['invalid'] ?></span></div>
                    <div class="col-auto"><span class="badge badge-dup">Dup (paste): <?= $bulkStats['dup_paste'] ?></span></div>
                    <div class="col-auto"><span class="badge badge-exists">Exists (DB): <?= $bulkStats['exists'] ?></span></div>
                </div>

                <!-- Preview thumbnails (first 6 OK images) -->
                <?php
                $previewImages = array_filter($bulkRows, fn($r) => $r['status'] === 'OK');
                $previewImages = array_slice($previewImages, 0, 6);
                if ($previewImages):
                ?>
                <div class="row g-2 mb-3">
                    <?php foreach ($previewImages as $pi): ?>
                    <div class="col-2">
                        <img src="<?= e($pi['image_url']) ?>" class="img-fluid rounded" style="height:80px;width:100%;object-fit:cover" onerror="this.classList.add('broken');this.style.height='80px';this.onerror=null;">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="bulk">
                    <input type="hidden" name="bulk_character_id" value="<?= $bulkCharId ?>">

                    <!-- Bulk action buttons -->
                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <button type="submit" name="bulk_autofix_all" class="btn btn-outline-info btn-sm"><i class="bi bi-magic me-1"></i>Auto-fix All</button>
                        <button type="submit" name="bulk_remove_invalid" class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Remove Invalid</button>
                        <button type="submit" name="bulk_keep_first_dup" class="btn btn-outline-warning btn-sm"><i class="bi bi-funnel me-1"></i>Keep First of Duplicates</button>
                        <button type="submit" name="bulk_remove_exists" class="btn btn-outline-secondary btn-sm"><i class="bi bi-database-dash me-1"></i>Remove Exists (DB)</button>
                        <button type="submit" name="bulk_repreview" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Re-Preview</button>
                    </div>

                    <!-- Editable rows table -->
                    <div class="table-responsive">
                    <table class="table table-dark table-sm bulk-table">
                        <thead>
                            <tr>
                                <th style="width:40px">Line</th>
                                <th style="width:80px">Status</th>
                                <th>Image URL</th>
                                <th>Source URL</th>
                                <th>Caption</th>
                                <th style="width:130px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bulkRows as $idx => $row): ?>
                            <tr>
                                <td>
                                    <small class="text-muted"><?= $row['lineNo'] ?></small>
                                    <input type="hidden" name="rows[<?= $idx ?>][lineNo]" value="<?= $row['lineNo'] ?>">
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = match($row['status']) {
                                        'OK'        => 'badge-ok',
                                        'INVALID'   => 'badge-invalid',
                                        'DUP_PASTE' => 'badge-dup',
                                        'EXISTS'    => 'badge-exists',
                                        default     => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= e($row['status']) ?></span>
                                    <?php if (!empty($row['errors'])): ?>
                                        <br><small class="text-danger"><?= e(implode('; ', $row['errors'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="rows[<?= $idx ?>][image_url]" value="<?= e($row['image_url']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="rows[<?= $idx ?>][source_url]" value="<?= e($row['source_url']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="rows[<?= $idx ?>][caption]" value="<?= e($row['caption']) ?>">
                                </td>
                                <td>
                                    <button type="submit" name="bulk_autofix_single" class="btn btn-outline-info btn-sm" title="Auto-fix"><i class="bi bi-magic"></i></button>
                                    <input type="hidden" name="fix_index" value="<?= $idx ?>" disabled>

                                    <button type="submit" name="bulk_remove_single" class="btn btn-outline-danger btn-sm" title="Remove"><i class="bi bi-x-lg"></i></button>
                                    <input type="hidden" name="remove_index" value="<?= $idx ?>" disabled>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>

                    <!-- Import button -->
                    <div class="mt-3">
                        <button type="submit" name="bulk_import" class="btn btn-success btn-lg" <?= $bulkStats['ok'] < 1 ? 'disabled' : '' ?>>
                            <i class="bi bi-cloud-upload me-1"></i>Import <?= $bulkStats['ok'] ?> Photo(s)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Fix per-row buttons with JS to set the correct index -->
        <script>
        document.querySelectorAll('button[name="bulk_autofix_single"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tr = this.closest('tr');
                var idx = tr.querySelector('input[name*="[lineNo]"]').name.match(/\[(\d+)\]/)[1];
                // Enable and set fix_index
                document.querySelectorAll('input[name="fix_index"]').forEach(function(el) { el.disabled = true; });
                var fixInput = document.createElement('input');
                fixInput.type = 'hidden'; fixInput.name = 'fix_index'; fixInput.value = idx;
                this.closest('form').appendChild(fixInput);
            });
        });
        document.querySelectorAll('button[name="bulk_remove_single"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tr = this.closest('tr');
                var idx = tr.querySelector('input[name*="[lineNo]"]').name.match(/\[(\d+)\]/)[1];
                document.querySelectorAll('input[name="remove_index"]').forEach(function(el) { el.disabled = true; });
                var rmInput = document.createElement('input');
                rmInput.type = 'hidden'; rmInput.name = 'remove_index'; rmInput.value = idx;
                this.closest('form').appendChild(rmInput);
            });
        });
        </script>

    <?php else: ?>
        <!-- ── Step 1: Paste ─────────────────────────────────────────── -->
        <div class="card bg-dark border-secondary">
            <div class="card-header"><i class="bi bi-clipboard me-1"></i>Step 1: Paste Photo URLs</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="bulk">
                    <div class="mb-3">
                        <label class="form-label">Character *</label>
                        <select name="bulk_character_id" class="form-select" required>
                            <option value="">Select…</option>
                            <?php foreach ($characters as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Photo URLs <small class="text-muted">(one per line)</small></label>
                        <textarea name="bulk_text" class="form-control font-monospace" rows="12" placeholder="https://example.com/photo1.jpg
https://example.com/photo2.jpg | https://source.com | Caption text
# Lines starting with # are ignored"></textarea>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">
                            Format per line: <code>URL</code> or <code>image_url | source_url | caption</code>
                        </small>
                    </div>
                    <button type="submit" name="bulk_parse" class="btn btn-warning">
                        <i class="bi bi-eye me-1"></i>Parse & Preview
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/partials/admin_footer.php'; ?>
