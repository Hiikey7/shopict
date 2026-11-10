<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$promo = null;
$selected = [];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM promotions WHERE id = ?');
    $stmt->execute([$id]);
    $promo = $stmt->fetch() ?: null;
    if (!$promo) {
        flash('That promotion no longer exists.', 'error');
        redirect('promotions.php');
    }
    $stmt = db()->prepare('SELECT product_id FROM promotion_products WHERE promotion_id = ?');
    $stmt->execute([$id]);
    $selected = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$form = $promo ?? ['name' => '', 'badge' => '', 'code' => null, 'min_spend' => null, 'max_uses' => null, 'discount_type' => 'percent', 'discount_value' => '', 'applies_to' => 'all',
                   'category' => '', 'starts_at' => null, 'ends_at' => null, 'is_active' => 1];
$errors = [];

if (is_post()) {
    verify_csrf();

    if (($_POST['action'] ?? '') === 'delete' && $promo) {
        try {
            db()->prepare('DELETE FROM promotions WHERE id = ?')->execute([$id]);
            flash('Promotion "' . $promo['name'] . '" deleted.');
            redirect('promotions.php');
        } catch (Throwable $ex) {
            error_log('admin promotion delete failed: ' . $ex->getMessage());
            flash('The promotion could not be deleted. Check the server error log for details.', 'error');
            redirect('promotion-edit.php?id=' . $id);
        }
    }

    $form = [
        'name'           => post_str('name'),
        'badge'          => post_str('badge'),
        'code'           => ($code = normalize_promo_code((string) ($_POST['code'] ?? ''))) === '' ? null : $code,
        'min_spend'      => trim((string) ($_POST['min_spend'] ?? '')) === '' ? null : post_num('min_spend'),
        'max_uses'       => ($mu = preg_replace('/\D+/', '', (string) ($_POST['max_uses'] ?? ''))) === '' ? null : (int) $mu,
        'discount_type'  => ($_POST['discount_type'] ?? '') === 'fixed' ? 'fixed' : 'percent',
        'discount_value' => post_num('discount_value'),
        'applies_to'     => in_array($_POST['applies_to'] ?? '', ['all', 'category', 'products'], true) ? $_POST['applies_to'] : 'all',
        'category'       => post_str('category'),
        'starts_at'      => post_datetime('starts_at'),
        'ends_at'        => post_datetime('ends_at'),
        'is_active'      => empty($_POST['is_active']) ? 0 : 1,
    ];
    $selected = array_values(array_unique(array_map('intval', (array) ($_POST['product_ids'] ?? []))));

    if ($form['name'] === null) {
        $errors['name'] = 'Give the promotion a name.';
    }
    if (!valid_amount($form['discount_value']) || $form['discount_value'] <= 0) {
        $errors['discount_value'] = 'Enter a discount greater than 0.';
    } elseif ($form['discount_type'] === 'percent' && $form['discount_value'] > 100) {
        $errors['discount_value'] = 'A percentage discount can be at most 100.';
    }
    if ($form['code'] !== null) {
        if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $form['code'])) {
            $errors['code'] = 'Use 3 to 30 letters, numbers, - or _ (no spaces).';
        } else {
            $taken = db()->prepare('SELECT id FROM promotions WHERE code = ? AND id <> ?');
            $taken->execute([$form['code'], $id]);
            if ($taken->fetchColumn()) {
                $errors['code'] = 'Another promotion already uses this code.';
            }
        }
    }
    if ($form['max_uses'] !== null && $form['max_uses'] < 1) {
        $errors['max_uses'] = 'Enter how many orders can use it (1 or more), or leave it empty for no limit.';
    }
    if ($form['min_spend'] !== null && (!valid_amount($form['min_spend']) || $form['min_spend'] < 0)) {
        $errors['min_spend'] = 'Enter an amount in KSh, or leave it empty.';
    }
    if ($form['applies_to'] === 'category' && $form['category'] === null) {
        $errors['category'] = 'Choose a category.';
    }
    if ($form['applies_to'] === 'products' && !$selected) {
        $errors['products'] = 'Tick at least one product.';
    }
    if ($form['starts_at'] === false) {
        $errors['starts_at'] = 'The start date is not valid.';
    }
    if ($form['ends_at'] === false) {
        $errors['ends_at'] = 'The end date is not valid.';
    }
    if (!isset($errors['starts_at']) && !isset($errors['ends_at']) && $form['starts_at'] && $form['ends_at'] && $form['ends_at'] <= $form['starts_at']) {
        $errors['ends_at'] = 'The end must be after the start.';
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $cols = ['name', 'badge', 'code', 'min_spend', 'max_uses', 'discount_type', 'discount_value', 'applies_to', 'category', 'starts_at', 'ends_at', 'is_active'];
            $data = $form;
            if ($data['applies_to'] !== 'category') {
                $data['category'] = null;
            }
            $values = array_map(fn ($c) => $data[$c], $cols);
            if ($promo) {
                $pdo->prepare('UPDATE promotions SET ' . implode(', ', array_map(fn ($c) => "$c = ?", $cols)) . ' WHERE id = ?')->execute([...$values, $id]);
            } else {
                $pdo->prepare('INSERT INTO promotions (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')->execute($values);
                $id = (int) $pdo->lastInsertId();
            }
            $pdo->prepare('DELETE FROM promotion_products WHERE promotion_id = ?')->execute([$id]);
            if ($data['applies_to'] === 'products') {
                $add = $pdo->prepare('INSERT IGNORE INTO promotion_products (promotion_id, product_id) SELECT ?, id FROM products WHERE id = ?');
                foreach ($selected as $pid) {
                    $add->execute([$id, $pid]);
                }
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('admin promotion save failed: ' . $ex->getMessage());
            $errors['_save'] = 'The promotion could not be saved. Apply the admin database repair migration, then try again.';
        }
        if (!$errors) {
            flash($promo ? 'Promotion saved.' : 'Promotion created.');
            redirect('promotion-edit.php?id=' . $id);
        }
    }
}

$catTree = category_tree(true);
// Labels of products that are not in a category yet, so old promotions still show their choice.
$legacyCats = db()->query("SELECT DISTINCT category FROM products WHERE category_id IS NULL AND category IS NOT NULL AND category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$products = db()->query('SELECT id, title, category, price, img_src, is_active FROM products ORDER BY title')->fetchAll();
$value = $form['discount_value'];
$valueText = $value === null || $value === '' ? '' : (is_float($value) && is_nan($value) ? (string) ($_POST['discount_value'] ?? '') : rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.'));
$invalid = fn (string $f) => isset($errors[$f]) ? ' is-invalid' : '';
$error = fn (string $f) => isset($errors[$f]) ? '<div class="invalid-feedback d-block">' . e($errors[$f]) . '</div>' : '';

admin_header($promo ? 'Edit promotion' : 'New promotion', 'promotions');
?>
<a href="promotions.php" class="small text-decoration-none">← All promotions</a>
<?php if ($errors): ?><div class="alert alert-danger mt-3"><?= isset($errors['_save']) ? e($errors['_save']) : 'Please fix the highlighted fields.' ?></div><?php endif; ?>
<?php if ($promo && !$errors): [$status, $badge] = promotion_status($promo); ?>
<div class="mt-3"><span class="badge <?= $badge ?>"><?= $status ?></span> <span class="small text-muted ms-1"><?= e(promotion_summary($promo + ['product_ids' => $selected])) ?></span></div>
<?php endif; ?>

<form method="post" class="mt-3" data-promo-form>
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-xl-7">
      <div class="card mb-4">
        <div class="card-header">Promotion</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label" for="name">Name *</label>
              <input class="form-control<?= $invalid('name') ?>" id="name" name="name" value="<?= e($form['name']) ?>" maxlength="150" placeholder="e.g. Black Friday laptops" required>
              <?= $error('name') ?>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="badge">Badge</label>
              <input class="form-control" id="badge" name="badge" value="<?= e($form['badge']) ?>" maxlength="30" placeholder="e.g. -20%">
              <div class="form-text">Shown in Quick View. Uses the name if empty.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="code">Promo code</label>
              <input class="form-control text-uppercase<?= $invalid('code') ?>" id="code" name="code" value="<?= e((string) $form['code']) ?>" maxlength="30" placeholder="e.g. SAVE500" autocomplete="off">
              <?= $error('code') ?>
              <div class="form-text">With a code, the discount applies only when the shopper enters it at checkout, and the code is shown on the product page. Leave empty to apply automatically to prices.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="min_spend">Minimum spend (KSh)</label>
              <input class="form-control<?= $invalid('min_spend') ?>" id="min_spend" name="min_spend" value="<?= $form['min_spend'] === null || $form['min_spend'] === '' ? '' : e(is_float($form['min_spend']) && is_nan($form['min_spend']) ? (string) ($_POST['min_spend'] ?? '') : (string) (0 + $form['min_spend'])) ?>" inputmode="numeric" placeholder="None">
              <?= $error('min_spend') ?>
              <div class="form-text">Promo codes only: the cart total needed for the code to work.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="max_uses">Number of uses</label>
              <input class="form-control<?= $invalid('max_uses') ?>" id="max_uses" name="max_uses" value="<?= $form['max_uses'] === null || $form['max_uses'] === '' ? '' : (int) $form['max_uses'] ?>" inputmode="numeric" placeholder="No limit" aria-describedby="max_uses_help">
              <?= $error('max_uses') ?>
              <div class="form-text" id="max_uses_help">
                e.g. <strong>300</strong> for the first 300 orders. Each order that gets this promotion counts once; cancelled orders don't count.
                <?php if ($promo): $used = promotion_uses()[(int) $promo['id']] ?? 0; $left = promotion_left($promo); ?>
                <br><strong class="<?= $left === 0 ? 'text-danger' : 'text-body' ?>">Used <?= $used ?><?= $promo['max_uses'] !== null ? ' of ' . (int) $promo['max_uses'] : ' times' ?><?= $left === 0 ? ' — used up, no longer applied' : ($left !== null ? ' · ' . $left . ' left' : '') ?></strong>
                <?php endif; ?>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label" for="discount_type">Discount type</label>
              <select class="form-select" id="discount_type" name="discount_type">
                <option value="percent" <?= $form['discount_type'] === 'percent' ? 'selected' : '' ?>>Percentage off</option>
                <option value="fixed" <?= $form['discount_type'] === 'fixed' ? 'selected' : '' ?>>Amount off (KSh)</option>
              </select>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label" for="discount_value">Discount *</label>
              <div class="input-group">
                <input class="form-control<?= $invalid('discount_value') ?>" id="discount_value" name="discount_value" value="<?= e($valueText) ?>" inputmode="decimal" required>
                <span class="input-group-text" data-value-unit>%</span>
              </div>
              <?= $error('discount_value') ?>
            </div>
            <div class="col-md-4">
              <label class="form-label d-block">Status</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= $form['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">On</label>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="starts_at">Starts</label>
              <input class="form-control<?= $invalid('starts_at') ?>" type="datetime-local" id="starts_at" name="starts_at" value="<?= e($form['starts_at'] ? date('Y-m-d\TH:i', strtotime($form['starts_at'])) : '') ?>">
              <?= $error('starts_at') ?>
              <div class="form-text">Leave empty to start now.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="ends_at">Ends</label>
              <input class="form-control<?= $invalid('ends_at') ?>" type="datetime-local" id="ends_at" name="ends_at" value="<?= e($form['ends_at'] ? date('Y-m-d\TH:i', strtotime($form['ends_at'])) : '') ?>">
              <?= $error('ends_at') ?>
              <div class="form-text">Leave empty to run until you turn it off.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-5">
      <div class="card mb-4">
        <div class="card-header">Applies to</div>
        <div class="card-body">
          <?php foreach (['all' => 'All products', 'category' => 'One category', 'products' => 'Chosen products'] as $k => $label): ?>
          <div class="form-check mb-1">
            <input class="form-check-input" type="radio" name="applies_to" id="applies_<?= $k ?>" value="<?= $k ?>" <?= $form['applies_to'] === $k ? 'checked' : '' ?>>
            <label class="form-check-label" for="applies_<?= $k ?>"><?= $label ?></label>
          </div>
          <?php endforeach; ?>

          <div class="mt-3" data-applies="category">
            <label class="form-label" for="category">Category</label>
            <select class="form-select<?= $invalid('category') ?>" id="category" name="category">
              <option value="">— Choose —</option>
              <?php foreach ($catTree as $main): ?>
              <optgroup label="<?= e($main['name']) ?>">
                <option value="<?= e($main['name']) ?>" <?= $main['name'] === $form['category'] ? 'selected' : '' ?>>All <?= e($main['name']) ?></option>
                <?php foreach ($main['children'] as $sub): ?><option value="<?= e($sub['name']) ?>" <?= $sub['name'] === $form['category'] ? 'selected' : '' ?>><?= e($sub['name']) ?></option><?php endforeach; ?>
              </optgroup>
              <?php endforeach; ?>
              <?php if ($legacyCats): ?><optgroup label="Not in a category yet"><?php foreach ($legacyCats as $c): ?><option value="<?= e($c) ?>" <?= $c === $form['category'] ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></optgroup><?php endif; ?>
            </select>
            <?= $error('category') ?>
          </div>

          <div class="mt-3" data-applies="products">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0" for="productFilter">Products</label>
              <span class="small text-muted"><span data-picked-count>0</span> selected</span>
            </div>
            <input class="form-control form-control-sm mb-2" type="search" id="productFilter" placeholder="Filter by name or category" data-product-filter>
            <div class="promo-product-list<?= isset($errors['products']) ? ' border-danger' : '' ?>">
              <?php foreach ($products as $p): ?>
              <label class="promo-product" data-product-option data-search="<?= e(mb_strtolower($p['title'] . ' ' . $p['category'] . ' ' . $p['id'])) ?>">
                <input class="form-check-input mt-0" type="checkbox" name="product_ids[]" value="<?= (int) $p['id'] ?>" <?= in_array((int) $p['id'], $selected, true) ? 'checked' : '' ?>>
                <?= thumb($p['img_src'], 32) ?>
                <span class="flex-grow-1 small"><span class="d-block text-truncate-2"><?= e($p['title']) ?></span><span class="text-muted"><?= money($p['price']) ?><?= $p['category'] ? ' · ' . e($p['category']) : '' ?><?= $p['is_active'] ? '' : ' · hidden' ?></span></span>
              </label>
              <?php endforeach; ?>
            </div>
            <?= $error('products') ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="sticky-actions d-flex gap-2 align-items-center">
    <button class="btn btn-primary px-4" type="submit" name="action" value="save"><?= $promo ? 'Save changes' : 'Create promotion' ?></button>
    <a class="btn btn-outline-secondary" href="promotions.php">Cancel</a>
    <?php if ($promo): ?>
    <button class="btn btn-outline-danger ms-auto" type="submit" name="action" value="delete" formnovalidate data-confirm="Delete this promotion? Prices go back to normal straight away.">Delete</button>
    <?php endif; ?>
  </div>
</form>
<?php admin_footer(); ?>
