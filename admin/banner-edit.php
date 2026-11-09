<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$placements = banner_placements();
$id = (int) ($_GET['id'] ?? 0);
$banner = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM banners WHERE id = ?');
    $stmt->execute([$id]);
    $banner = $stmt->fetch() ?: null;
    if (!$banner) {
        flash('That banner no longer exists.', 'error');
        redirect('banners.php');
    }
}
$placement = $banner['placement'] ?? (string) ($_GET['placement'] ?? '');
if (!isset($placements[$placement])) {
    flash('Unknown banner placement.', 'error');
    redirect('banners.php');
}
$pl = $placements[$placement];
$fields = $pl['fields'];

if (!$banner && $pl['max'] !== null) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM banners WHERE placement = ?');
    $stmt->execute([$placement]);
    if ((int) $stmt->fetchColumn() >= $pl['max']) {
        flash($pl['label'] . ' already has its banner. Edit it instead.', 'error');
        redirect('banners.php#' . $placement);
    }
}

$form = $banner ?? array_fill_keys(['product_id', 'eyebrow', 'title', 'title_highlight', 'subtitle', 'price_label', 'price', 'button_text', 'link_url', 'image', 'bg_image', 'tag_color', 'text_color', 'price_color', 'text_hex', 'bg_hex', 'title_color', 'title_highlight_color', 'subtitle_color', 'button_color', 'hero_price_color', 'meta'], null) + ['is_active' => 1, 'hero_price_visible' => 1];
$features = json_decode((string) ($form['meta'] ?? ''), true) ?: [];
$errors = [];

if (is_post()) {
    verify_csrf();

    if (($_POST['action'] ?? '') === 'delete' && $banner) {
        db()->prepare('DELETE FROM banners WHERE id = ?')->execute([$id]);
        flash('Banner deleted.');
        redirect('banners.php#' . $placement);
    }

    foreach (['eyebrow', 'title', 'title_highlight', 'subtitle', 'price_label', 'price', 'button_text', 'link_url'] as $f) {
        if (in_array($f, $fields, true)) {
            $form[$f] = post_str($f);
        }
    }
    foreach (['tag_color', 'text_color', 'price_color'] as $f) {
        if (in_array($f, $fields, true)) {
            $v = (string) ($_POST[$f] ?? '');
            $form[$f] = array_key_exists($v, COLOR_CLASSES) && $v !== '' ? $v : null;
        }
    }
    $form['is_active'] = empty($_POST['is_active']) ? 0 : 1;
    if (in_array('custom_colours', $fields, true)) {
        foreach (['text_hex', 'bg_hex'] as $f) {
            $v = strtolower((string) ($_POST[$f] ?? ''));
            $form[$f] = empty($_POST[$f . '_default']) && preg_match('/^#[0-9a-f]{6}$/', $v) ? $v : null;
        }
    }
    if ($placement === 'hero_main') {
        foreach (['title_color', 'title_highlight_color', 'subtitle_color'] as $f) {
            $v = strtolower((string) ($_POST[$f] ?? ''));
            $form[$f] = in_array($v, ['#ffffff', '#000000', '#ff0000'], true) ? $v : '#ffffff';
        }
        $form['button_color'] = in_array($_POST['button_color'] ?? '', ['red', 'white'], true) ? $_POST['button_color'] : 'white';
        $v = strtolower((string) ($_POST['hero_price_color'] ?? ''));
        $form['hero_price_color'] = in_array($v, ['#ffffff', '#000000', '#ff0000'], true) ? $v : '#ffffff';
        $form['price_label'] = ($_POST['price_label'] ?? '') === 'Now' ? 'Now' : '';
        $form['hero_price_visible'] = empty($_POST['hero_price_visible']) ? 0 : 1;
    } elseif ($placement === 'hero_side_1') {
        $form['button_color'] = in_array($_POST['button_color'] ?? '', ['red', 'white'], true) ? $_POST['button_color'] : 'white';
    }
    if (in_array('bg_image', $fields, true) && !empty($_POST['remove_bg_image'])) {
        $form['bg_image'] = null;
    }
    if (in_array('product', $fields, true)) {
        $pid = (int) ($_POST['product_id'] ?? 0);
        $form['product_id'] = $pid && find_product($pid) ? $pid : null;
    }
    if (in_array('features', $fields, true)) {
        $features = [];
        foreach ((array) ($_POST['feature_label'] ?? []) as $i => $label) {
            $label = trim((string) $label);
            $value = trim((string) ($_POST['feature_value'][$i] ?? ''));
            $icon = (string) ($_POST['feature_icon'][$i] ?? '');
            if ($label !== '' || $value !== '') {
                $features[] = ['label' => $label, 'value' => $value] + (isset(FEATURE_ICONS[$icon]) ? ['icon' => $icon] : []);
            }
        }
        $form['meta'] = $features ? json_encode($features, JSON_UNESCAPED_UNICODE) : null;
    }
    if ($form['link_url'] !== null && preg_match('/^\s*javascript:/i', $form['link_url'])) {
        $errors['link_url'] = 'Enter a normal web address.';
    }

    try {
        foreach (['image', 'bg_image'] as $f) {
            if (in_array($f, $fields, true) && ($img = upload_image($f))) {
                $form[$f] = $img['path'];
            }
        }
    } catch (RuntimeException $ex) {
        $errors['image'] = $ex->getMessage();
    }
    if (!$errors && !$form['image'] && !$form['bg_image'] && empty($form['product_id'])) {
        $errors['image'] = 'Upload an image for the banner.';
    }

    if (!$errors) {
        $cols = ['eyebrow', 'title', 'title_highlight', 'subtitle', 'price_label', 'price', 'button_text', 'link_url', 'image', 'bg_image', 'tag_color', 'text_color', 'price_color', 'text_hex', 'bg_hex', 'title_color', 'title_highlight_color', 'subtitle_color', 'button_color', 'hero_price_color', 'hero_price_visible', 'meta', 'product_id', 'is_active'];
        $values = array_map(fn ($c) => $form[$c] ?? null, $cols);
        if ($banner) {
            db()->prepare('UPDATE banners SET ' . implode(', ', array_map(fn ($c) => "$c = ?", $cols)) . ' WHERE id = ?')->execute([...$values, $id]);
        } else {
            $stmt = db()->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM banners WHERE placement = ?');
            $stmt->execute([$placement]);
            $position = (int) $stmt->fetchColumn();
            db()->prepare('INSERT INTO banners (placement, position, ' . implode(', ', $cols) . ') VALUES (?, ?, ' . implode(', ', array_fill(0, count($cols), '?')) . ')')
                ->execute([$placement, $position, ...$values]);
            $id = (int) db()->lastInsertId();
        }
        flash($banner ? 'Banner saved.' : 'Banner created.');
        redirect('banner-edit.php?id=' . $id);
    }
}

$has = fn (string $f) => in_array($f, $fields, true);
$fieldLabel = fn (string $f) => $pl['labels'][$f] ?? BANNER_FIELD_LABELS[$f];
$label = fn (string $f) => $fieldLabel($f)[0];
$help = fn (string $f) => $fieldLabel($f)[1] ? '<div class="form-text">' . e($fieldLabel($f)[1]) . '</div>' : '';
$productOptions = $has('product') ? db()->query('SELECT id, title, price FROM products WHERE is_active = 1 ORDER BY title')->fetchAll() : [];
$multiline = ['title', 'subtitle'];
if (!$features && $has('features')) {
    $features = [['label' => '', 'value' => '']];
}

admin_header(($banner ? 'Edit banner' : 'Add banner') . ' · ' . $pl['label'], 'banners');
?>
<a href="banners.php#<?= e($placement) ?>" class="small text-decoration-none">← All banners</a>
<p class="text-muted mt-2"><?= e($pl['where']) ?></p>
<?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-xl-7">
      <div class="card mb-4">
        <div class="card-header">Text</div>
        <div class="card-body">
          <?php foreach (['eyebrow', 'title', 'title_highlight', 'subtitle'] as $f): if (!$has($f)) continue; ?>
          <div class="mb-3">
            <label class="form-label" for="<?= $f ?>"><?= e($label($f)) ?></label>
            <?php if (in_array($f, $multiline, true) && in_array($placement, ['featured_card', 'smart_home'], true)): ?>
            <textarea class="form-control" id="<?= $f ?>" name="<?= $f ?>" rows="2"><?= e($form[$f]) ?></textarea>
            <?php else: ?>
            <input class="form-control" id="<?= $f ?>" name="<?= $f ?>" value="<?= e($form[$f]) ?>">
            <?php endif; ?>
            <?= $help($f) ?>
          </div>
          <?php endforeach; ?>

          <?php if ($has('price')): ?>
          <div class="row g-3 mb-3">
            <?php if ($has('price_label')): ?>
            <div class="col-sm-5">
              <label class="form-label" for="price_label"><?= e($label('price_label')) ?></label>
              <?php if ($placement === 'hero_main'): ?>
              <select class="form-select" id="price_label" name="price_label">
                <option value="" <?= empty($form['price_label']) ? 'selected' : '' ?>>No prefix</option>
                <option value="Now" <?= in_array($form['price_label'] ?? '', ['Now', 'From'], true) ? 'selected' : '' ?>>Now</option>
              </select>
              <?php else: ?>
              <input class="form-control" id="price_label" name="price_label" value="<?= e($form['price_label']) ?>">
              <?php endif; ?>
              <?= $help('price_label') ?>
            </div>
            <?php endif; ?>
            <div class="col-sm-7">
              <label class="form-label" for="price"><?= e($label('price')) ?></label>
              <input class="form-control" id="price" name="price" value="<?= e($form['price']) ?>">
              <?php if (isset($pl['labels']['price'])): ?><?= $help('price') ?><?php else: ?><div class="form-text"><?= $placement === 'featured_card' ? 'KSh is added in front automatically, e.g. 304,569' : 'Shown exactly as typed, e.g. KSh 51,471' ?></div><?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($placement === 'hero_main'): ?>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="hero_price_visible" name="hero_price_visible" value="1" <?= !empty($form['hero_price_visible']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="hero_price_visible">Show price on this banner</label>
          </div>
          <div class="mb-3">
            <label class="form-label" for="hero_price_color">Price colour</label>
            <select class="form-select" id="hero_price_color" name="hero_price_color">
              <?php foreach (['#ffffff' => 'White', '#000000' => 'Black', '#ff0000' => 'Red'] as $value => $option): ?>
              <option value="<?= $value ?>" <?= strtolower($form['hero_price_color'] ?: '#ffffff') === $value ? 'selected' : '' ?>><?= $option ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="button_color">Shop now button colour</label>
            <select class="form-select" id="button_color" name="button_color">
              <option value="red" <?= ($form['button_color'] ?? 'white') === 'red' ? 'selected' : '' ?>>Red button with white text</option>
              <option value="white" <?= ($form['button_color'] ?? 'white') === 'white' ? 'selected' : '' ?>>White button with black text</option>
            </select>
          </div>
          <div class="border-top pt-3 mt-4">
            <div class="fw-medium mb-3">Text colours</div>
            <div class="row g-3">
              <?php foreach (['title_color' => 'Title', 'title_highlight_color' => 'Title highlight', 'subtitle_color' => 'Subtitle'] as $f => $name): $colour = $form[$f] ?: '#ffffff'; ?>
              <div class="col-md-4">
                <label class="form-label" for="<?= $f ?>"><?= $name ?></label>
                <select class="form-select" id="<?= $f ?>" name="<?= $f ?>">
                  <?php foreach (['#ffffff' => 'White', '#000000' => 'Black', '#ff0000' => 'Red'] as $value => $option): ?>
                  <option value="<?= $value ?>" <?= strtolower($colour) === $value ? 'selected' : '' ?>><?= $option ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($placement === 'hero_side_1'): ?>
          <div class="mb-3">
            <label class="form-label" for="button_color">Button colour</label>
            <select class="form-select" id="button_color" name="button_color">
              <option value="red" <?= ($form['button_color'] ?? 'white') === 'red' ? 'selected' : '' ?>>Red button with white text</option>
              <option value="white" <?= ($form['button_color'] ?? 'white') === 'white' ? 'selected' : '' ?>>White button with black text</option>
            </select>
          </div>
          <?php endif; ?>

          <?php if ($has('product')): ?>
          <div class="mb-3">
            <label class="form-label" for="product_search">Linked product</label>
            <?php
            $selectedProduct = null;
            foreach ($productOptions as $po) {
                if ((int) ($form['product_id'] ?? 0) === (int) $po['id']) {
                    $selectedProduct = $po;
                    break;
                }
            }
            ?>
            <div class="input-group">
              <input class="form-control" type="search" id="product_search" list="banner_product_options" autocomplete="off" placeholder="Type a product name to search…" value="<?= $selectedProduct ? e($selectedProduct['title'] . ' — ' . money($selectedProduct['price']) . ' · #' . $selectedProduct['id']) : '' ?>" data-banner-product-search>
              <button class="btn btn-outline-secondary" type="button" data-clear-banner-product>Clear</button>
            </div>
            <input type="hidden" id="product_id" name="product_id" value="<?= $selectedProduct ? (int) $selectedProduct['id'] : '' ?>" data-banner-product-id>
            <datalist id="banner_product_options">
              <?php foreach ($productOptions as $po): ?>
              <option value="<?= e($po['title'] . ' — ' . money($po['price']) . ' · #' . $po['id']) ?>" data-product-id="<?= (int) $po['id'] ?>"></option>
              <?php endforeach; ?>
            </datalist>
            <div class="form-text">Optional. Fills an empty product name or price, links the banner to the product page and uses its image when none is uploaded.</div>
          </div>
          <?php endif; ?>

          <?php if ($has('features')): ?>
          <label class="form-label"><?= e($label('features')) ?></label>
          <?= $help('features') ?>
          <div class="mt-2" data-features>
            <?php foreach ($features as $feat): ?>
            <div class="row g-2 mb-2" data-feature-row>
              <div class="col-md-3"><input class="form-control" name="feature_label[]" value="<?= e($feat['label'] ?? '') ?>" placeholder="Label (e.g. 100Hz)"></div>
              <div class="col-md"><input class="form-control" name="feature_value[]" value="<?= e($feat['value'] ?? '') ?>" placeholder="Value (e.g. Refresh Rate)"></div>
              <div class="col-md-3"><?= feature_icon_select($feat) ?></div>
              <div class="col-auto"><button type="button" class="btn btn-light text-danger" data-remove-feature title="Remove">✕</button></div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary mb-3" data-add-feature>+ Add feature</button>
          <?php endif; ?>

          <?php if ($has('button_text')): ?>
          <div class="mb-3">
            <label class="form-label" for="button_text"><?= e($label('button_text')) ?></label>
            <input class="form-control" id="button_text" name="button_text" value="<?= e($form['button_text']) ?>">
            <?= $help('button_text') ?>
          </div>
          <?php endif; ?>
          <div class="mb-0">
            <label class="form-label" for="link_url"><?= e($label('link_url')) ?></label>
            <input class="form-control<?= isset($errors['link_url']) ? ' is-invalid' : '' ?>" id="link_url" name="link_url" value="<?= e($form['link_url']) ?>" placeholder="https://… or ./">
            <?= $help('link_url') ?>
          </div>
        </div>
      </div>

      <?php if ($has('custom_colours')): ?>
      <div class="card mb-4">
        <div class="card-header">Colours</div>
        <div class="card-body row g-4">
          <?php foreach (['text_hex' => ['Text colour', 'All the banner text (not the button). Default: white.', '#ffffff'], 'bg_hex' => ['Background colour', 'Fills the banner when there is no background image (and shows behind transparent ones).', '#d4f0f4']] as $f => [$name, $hint, $fallback]): ?>
          <div class="col-md-6">
            <label class="form-label" for="<?= $f ?>"><?= $name ?></label>
            <div class="d-flex align-items-center gap-2">
              <input class="form-control form-control-color" type="color" id="<?= $f ?>" name="<?= $f ?>" value="<?= e($form[$f] ?: $fallback) ?>" data-colour-input="<?= $f ?>">
              <code class="small" data-colour-value="<?= $f ?>"><?= e($form[$f] ?: $fallback) ?></code>
            </div>
            <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="<?= $f ?>_default" name="<?= $f ?>_default" value="1" <?= $form[$f] ? '' : 'checked' ?>><label class="form-check-label" for="<?= $f ?>_default">Use the default</label></div>
            <div class="form-text"><?= $hint ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($has('tag_color')): ?>
      <div class="card mb-4">
        <div class="card-header">Colours</div>
        <div class="card-body row g-3">
          <?php foreach (['tag_color', 'text_color', 'price_color'] as $f): ?>
          <div class="col-md-4">
            <label class="form-label" for="<?= $f ?>"><?= e($label($f)) ?></label>
            <select class="form-select" id="<?= $f ?>" name="<?= $f ?>">
              <?php foreach (COLOR_CLASSES as $class => $name): ?><option value="<?= e($class) ?>" <?= ($form[$f] ?? '') === $class ? 'selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?>
            </select>
          </div>
          <?php endforeach; ?>
          <div class="form-text">Pick colours that stay readable on the image.</div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-xl-5">
      <div class="card mb-4">
        <div class="card-header">Images</div>
        <div class="card-body">
          <?php foreach (['image', 'bg_image'] as $f): if (!$has($f)) continue; ?>
          <div class="mb-4">
            <label class="form-label"><?= e($label($f)) ?></label>
            <img class="img-preview <?= $form[$f] ? '' : 'd-none' ?>" src="<?= e(admin_asset($form[$f])) ?>" alt="" data-preview="<?= $f ?>">
            <input class="form-control" type="file" name="<?= $f ?>" accept="image/jpeg,image/png,image/gif,image/webp" data-preview-input="<?= $f ?>">
            <?php if (!empty($pl['sizes'][$f])): ?><div class="form-text">Recommended: <?= e($pl['sizes'][$f]) ?></div><?php endif; ?>
            <?php if ($f === 'bg_image' && $form['bg_image'] && $has('custom_colours')): ?><div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="remove_bg_image" name="remove_bg_image" value="1"><label class="form-check-label" for="remove_bg_image">Remove background image (use the background colour)</label></div><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= $form['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">Show this banner on the home page</label>
          </div>
        </div>
      </div>
    </div>
  </div>

  <template data-feature-template>
    <div class="row g-2 mb-2" data-feature-row>
      <div class="col-md-3"><input class="form-control" name="feature_label[]" placeholder="Label (e.g. 100Hz)"></div>
      <div class="col-md"><input class="form-control" name="feature_value[]" placeholder="Value (e.g. Refresh Rate)"></div>
      <div class="col-md-3"><?= feature_icon_select([]) ?></div>
      <div class="col-auto"><button type="button" class="btn btn-light text-danger" data-remove-feature title="Remove">✕</button></div>
    </div>
  </template>

  <div class="sticky-actions d-flex gap-2">
    <button class="btn btn-primary px-4" type="submit" name="action" value="save"><?= $banner ? 'Save banner' : 'Create banner' ?></button>
    <a class="btn btn-outline-secondary" href="banners.php#<?= e($placement) ?>">Cancel</a>
    <?php if ($banner): ?>
    <button class="btn btn-outline-danger ms-auto" type="submit" name="action" value="delete" formnovalidate data-confirm="Delete this banner?">Delete</button>
    <?php endif; ?>
  </div>
</form>
<?php admin_footer(); ?>
