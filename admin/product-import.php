<?php
require __DIR__ . '/../includes/admin.php';
require_admin();
require __DIR__ . '/../includes/product-import.php';

if (isset($_GET['template'])) {
    product_import_template();
}

$results = [];
$imported = 0;
$errors = [];
if (is_post()) {
    verify_csrf();
    $file = $_FILES['workbook'] ?? null;
    if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $errors[] = match ($uploadError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The workbook is too large. Maximum upload size is 10 MB.',
            UPLOAD_ERR_NO_FILE => 'Choose an .xlsx workbook to import.',
            default => 'The workbook upload failed. Try again.',
        };
    } elseif ((int) $file['size'] > 10 * 1024 * 1024) {
        $errors[] = 'The workbook is too large. Maximum upload size is 10 MB.';
    } elseif (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
        $errors[] = 'Upload an Excel .xlsx workbook. Download the template below if needed.';
    } else {
        try {
            $rows = product_import_parse_xlsx((string) $file['tmp_name']);
            if (count($rows) > 500) {
                throw new RuntimeException('Import is limited to 500 product rows per workbook.');
            }
            if (!$rows) {
                throw new RuntimeException('No product rows were found in the Products worksheet.');
            }
            foreach ($rows as $rowNumber => $row) {
                $pdo = db();
                try {
                    [$data, $categories, $gallery, $variants] = product_import_record($row);
                    if ($variants) {
                        $default = array_values(array_filter($variants, static fn ($variant) => $variant['is_default']))[0];
                        $data['price'] = $default['price'];
                        $data['old_price'] = $default['old_price'];
                    }
                    $pdo->beginTransaction();
                    $columns = array_keys($data);
                    $pdo->prepare(
                        'INSERT INTO products (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')'
                    )->execute(array_values($data));
                    $productId = (int) $pdo->lastInsertId();

                    if ($categories) {
                        $link = $pdo->prepare('INSERT IGNORE INTO product_category_links (product_id, category_id) VALUES (?, ?)');
                        foreach ($categories as $category) {
                            $link->execute([$productId, (int) $category['id']]);
                        }
                    }
                    if ($gallery) {
                        $addImage = $pdo->prepare('INSERT INTO product_images (product_id, image, position) VALUES (?, ?, ?)');
                        foreach ($gallery as $position => $image) {
                            $addImage->execute([$productId, $image, $position + 1]);
                        }
                    }
                    if ($variants) {
                        $addVariant = $pdo->prepare(
                            'INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        foreach ($variants as $variant) {
                            $addVariant->execute([
                                $productId,
                                $variant['options'],
                                $variant['price'],
                                $variant['old_price'],
                                $variant['sku'],
                                $variant['stock'],
                                $variant['is_default'],
                                $variant['position'],
                            ]);
                        }
                    }
                    $pdo->commit();
                    $imported++;
                    $results[] = [
                        'row' => $rowNumber,
                        'title' => $data['title'],
                        'ok' => true,
                        'message' => (int) $data['is_active'] === 1 ? 'Imported visible' : 'Imported hidden',
                    ];
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('product import row ' . $rowNumber . ': ' . $e->getMessage());
                    $results[] = [
                        'row' => $rowNumber,
                        'title' => mb_substr($row['title'] ?? '', 0, 100),
                        'ok' => false,
                        'message' => $e instanceof InvalidArgumentException
                            ? $e->getMessage()
                            : 'Import failed due to a database or server error. Check server logs for details.',
                    ];
                }
            }
        } catch (Throwable $e) {
            error_log('product import workbook: ' . $e->getMessage());
            $errors[] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'The workbook could not be read. Make sure it is a valid Excel .xlsx file.';
        }
    }
}

admin_header('Bulk product import', 'products');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <a class="small text-decoration-none" href="products.php">← Products</a>
  <a class="btn btn-outline-primary" href="product-import.php?template=1">Download Excel template</a>
</div>
<div class="card card-body mb-3">
  <h1 class="h5 mb-2">Import products from Excel</h1>
  <p class="text-muted mb-2">Complete the Products sheet and upload the .xlsx file. Each valid row creates a new product; existing products are not edited.</p>
  <ul class="small text-muted mb-3">
    <li>Store visibility follows <code>is_active</code>: use <code>1</code> to show a product in the store or <code>0</code> to keep it hidden.</li>
    <li>Required fields: <code>title</code>, <code>price</code>, and an image path in <code>img_src</code> or the first entry in <code>gallery_images</code>. Missing required data rejects that row instead of filling it with a placeholder.</li>
    <li>Optional blank cells remain empty. Image paths must already exist inside <code>assets/</code>, or be an HTTPS image URL.</li>
    <li>Use <code>category_slugs</code> for multiple categories, JSON columns for specifications/options/variants, and separate gallery paths with <code>|</code>. See the template's Instructions sheet.</li>
    <li>Up to 500 product rows per workbook. Valid rows import even if another row has an error.</li>
  </ul>
  <?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row g-2 align-items-end">
      <div class="col-md-8">
        <label class="form-label" for="workbook">Excel workbook (.xlsx, max 10 MB)</label>
        <input class="form-control" type="file" id="workbook" name="workbook" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
      </div>
      <div class="col-md-4"><button class="btn btn-primary w-100">Import products</button></div>
    </div>
  </form>
</div>
<?php if ($results): ?>
<div class="alert <?= count(array_filter($results, static fn ($result) => !$result['ok'])) ? 'alert-warning' : 'alert-success' ?>">
  <?= $imported ?> product<?= $imported === 1 ? '' : 's' ?> imported hidden; <?= count($results) - $imported ?> row<?= count($results) - $imported === 1 ? '' : 's' ?> failed.
</div>
<div class="table-card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Excel row</th><th>Product</th><th>Result</th><th>Details</th></tr></thead>
      <tbody>
      <?php foreach ($results as $result): ?>
        <tr>
          <td><?= (int) $result['row'] ?></td>
          <td><?= e($result['title']) ?></td>
          <td><span class="badge <?= $result['ok'] ? 'badge-on' : 'badge-off' ?>"><?= $result['ok'] ? 'Imported' : 'Failed' ?></span></td>
          <td class="small"><?= e($result['message']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php partial('admin/footer'); ?>
