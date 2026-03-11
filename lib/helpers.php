<?php
/**
 * Helper functions: escape, slugify, URL validation, bulk-add parsing, pagination
 */

/* ── Output ──────────────────────────────────────────────────────── */

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ── Slug ────────────────────────────────────────────────────────── */

function slugify(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

/**
 * Generate unique slug; appends -2, -3 … on collision
 */
function unique_slug(PDO $pdo, string $base_slug, ?int $exclude_id = null): string {
    $slug = $base_slug;
    $i = 2;
    while (true) {
        $sql = 'SELECT id FROM characters WHERE slug = ?';
        $params = [$slug];
        if ($exclude_id) {
            $sql .= ' AND id != ?';
            $params[] = $exclude_id;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base_slug . '-' . $i;
        $i++;
    }
}

/* ── URL helpers ─────────────────────────────────────────────────── */

function validate_url(string $url): bool {
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
    $parts = parse_url($url);
    if (!$parts || !isset($parts['scheme'])) return false;
    return in_array($parts['scheme'], ['http', 'https'], true);
}

function normalize_url(string $url): string {
    $url = trim($url);
    $url = preg_replace('/\s+/', '', $url);
    $url = rtrim($url, '/');
    return $url;
}

function url_has_image_ext(string $url): bool {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg','jpeg','png','webp','gif'], true);
}

/* ── Bulk-add: parse ─────────────────────────────────────────────── */

/**
 * Parse raw textarea into rows.
 * Each row: ['lineNo'=>int, 'raw'=>string, 'image_url'=>'', 'source_url'=>'', 'caption'=>'']
 */
function parse_bulk_text(string $text): array {
    $lines = preg_split('/\r?\n/', $text);
    $rows = [];
    foreach ($lines as $idx => $line) {
        $lineNo = $idx + 1;
        $trimmed = trim($line);
        // skip empty / comment
        if ($trimmed === '' || $trimmed[0] === '#') continue;

        // clean wrappers: quotes, angle brackets
        $cleaned = clean_line($trimmed);

        // split by pipe
        $parts = array_map('trim', explode('|', $cleaned));

        $row = [
            'lineNo'     => $lineNo,
            'raw'        => $trimmed,
            'image_url'  => normalize_url($parts[0] ?? ''),
            'source_url' => normalize_url($parts[1] ?? ''),
            'caption'    => $parts[2] ?? '',
        ];
        $rows[] = $row;
    }
    return $rows;
}

function clean_line(string $s): string {
    // remove surrounding quotes
    $s = trim($s, "\"'`");
    // remove <...> wrappers
    if (preg_match('/^<(.+)>$/', $s, $m)) {
        $s = $m[1];
    }
    return trim($s);
}

/* ── Bulk-add: auto-fix ──────────────────────────────────────────── */

function apply_autofix_row(array $row): array {
    foreach (['image_url', 'source_url', 'caption'] as $field) {
        $val = $row[$field] ?? '';
        $val = trim($val);
        $val = trim($val, "\"'`");
        // remove angle brackets
        if (preg_match('/^<(.+)>$/', $val, $m)) {
            $val = $m[1];
        }
        if ($field !== 'caption') {
            $val = normalize_url($val);
            // add https:// if missing scheme but looks like url
            if ($val && !preg_match('#^https?://#i', $val) && preg_match('/\.[a-z]{2,}/', $val)) {
                $val = 'https://' . $val;
            }
        }
        $row[$field] = $val;
    }
    return $row;
}

/* ── Bulk-add: validate row ──────────────────────────────────────── */

function validate_row(array $row): array {
    $errors = [];
    $status = 'OK';

    $url = $row['image_url'] ?? '';
    if ($url === '') {
        $status = 'INVALID';
        $errors[] = 'Empty image URL';
    } elseif (!validate_url($url)) {
        $status = 'INVALID';
        $errors[] = 'Invalid URL format';
    }

    // warn if no image extension
    if ($status === 'OK' && !url_has_image_ext($url)) {
        $errors[] = 'Warning: no image extension';
    }

    $row['status'] = $status;
    $row['errors'] = $errors;
    return $row;
}

/* ── Bulk-add: dedupe in paste ───────────────────────────────────── */

function dedupe_rows_in_paste(array $rows): array {
    $seen = [];
    foreach ($rows as &$row) {
        $key = $row['image_url'];
        if (isset($seen[$key])) {
            if (($row['status'] ?? '') !== 'INVALID') {
                $row['status'] = 'DUP_PASTE';
                $row['errors'][] = 'Duplicate of line ' . $seen[$key];
            }
        } else {
            $seen[$key] = $row['lineNo'];
        }
    }
    unset($row);
    return $rows;
}

/* ── Bulk-add: mark existing in DB ───────────────────────────────── */

function mark_existing_in_db(PDO $pdo, int $characterId, array $rows): array {
    if (empty($rows)) return $rows;

    // collect valid URLs
    $urls = [];
    foreach ($rows as $row) {
        if (($row['status'] ?? '') === 'OK' && !empty($row['image_url'])) {
            $urls[] = $row['image_url'];
        }
    }
    if (empty($urls)) return $rows;

    $placeholders = implode(',', array_fill(0, count($urls), '?'));
    $stmt = $pdo->prepare(
        "SELECT image_url FROM photos WHERE character_id = ? AND image_url IN ($placeholders)"
    );
    $stmt->execute(array_merge([$characterId], $urls));
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $existingMap = array_flip($existing);

    foreach ($rows as &$row) {
        if (($row['status'] ?? '') === 'OK' && isset($existingMap[$row['image_url']])) {
            $row['status'] = 'EXISTS';
            $row['errors'][] = 'Already in database';
        }
    }
    unset($row);
    return $rows;
}

/* ── Pagination ──────────────────────────────────────────────────── */

function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $currentPage,
        'total_pages' => $totalPages,
        'offset'      => $offset,
    ];
}

function render_pagination(array $pg, string $baseUrl): string {
    if ($pg['total_pages'] <= 1) return '';
    $html = '<nav><ul class="pagination justify-content-center">';
    // prev
    $prevDisabled = $pg['current'] <= 1 ? ' disabled' : '';
    $prevPage = max(1, $pg['current'] - 1);
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . e($baseUrl . $sep . 'page=' . $prevPage) . '">&laquo;</a></li>';

    for ($i = 1; $i <= $pg['total_pages']; $i++) {
        $active = $i === $pg['current'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($baseUrl . $sep . 'page=' . $i) . '">' . $i . '</a></li>';
    }

    // next
    $nextDisabled = $pg['current'] >= $pg['total_pages'] ? ' disabled' : '';
    $nextPage = min($pg['total_pages'], $pg['current'] + 1);
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . e($baseUrl . $sep . 'page=' . $nextPage) . '">&raquo;</a></li>';
    $html .= '</ul></nav>';
    return $html;
}
