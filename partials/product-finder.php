<?php
/**
 * "Product finder" pop-up on the shop page: item type, brand, specs and amount range.
 * Submits to the shop page; only the chosen item's brand/spec fields are sent.
 *
 * @var array    $sel      finder_selection() result
 * @var int|null $minPrice
 * @var int|null $maxPrice
 */
$config = array_filter(finder_config(), fn ($slug) => category_by_slug($slug) !== null, ARRAY_FILTER_USE_KEY);
if (!$config) {
    return;
}
$current = $sel['item'] && isset($config[$sel['item']]) ? $sel['item'] : array_key_first($config);
$icon = fn (string $paths, int $size = 20) => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
?>
<div class="modal fade finder-modal" id="productFinder" tabindex="-1" aria-labelledby="productFinderTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content finder" method="get" action="shop.php" data-finder>
      <div class="finder-head">
        <span class="finder-badge"><?= $icon('<rect x="5" y="8" width="14" height="11" rx="2.5"/><path d="M12 4v4M9 13h.01M15 13h.01M9.5 16.5h5M3 12v3M21 12v3"/>', 22) ?></span>
        <div>
          <h2 class="finder-title" id="productFinderTitle">Product finder</h2>
          <p class="finder-sub">Choose what you need and search.</p>
        </div>
        <button type="button" class="finder-close" data-bs-dismiss="modal" aria-label="Close"><?= $icon('<path d="M6 6l12 12M18 6 6 18"/>', 18) ?></button>
      </div>

      <fieldset class="finder-block">
        <legend class="finder-label">Item</legend>
        <div class="finder-items">
          <?php foreach ($config as $slug => $item): ?>
          <label class="finder-item">
            <input type="radio" name="cat" value="<?= e($slug) ?>" <?= $slug === $current ? 'checked' : '' ?> data-finder-item>
            <span><?= $icon($item['icon'], 20) ?><?= e($item['label']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <?php foreach ($config as $slug => $item): $on = $slug === $current; ?>
      <div class="finder-group" data-finder-group="<?= e($slug) ?>"<?= $on ? '' : ' hidden' ?>>
        <fieldset class="finder-block"<?= $on ? '' : ' disabled' ?>>
          <legend class="finder-spec-head finder-label-row"><span class="finder-label">Brand</span><button type="button" class="finder-any" data-finder-any="brand">Any</button></legend>
          <div class="finder-chips-row">
            <?php foreach ($item['brands'] as $brand): ?>
            <label class="finder-chip-opt"><input type="checkbox" name="brand[]" value="<?= e($brand) ?>" <?= $on && in_array($brand, $sel['brand'], true) ? 'checked' : '' ?>><span><?= e($brand) ?></span></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <div class="finder-block">
          <p class="finder-label">Specs</p>
          <?php foreach ($item['specs'] as $key => $group): ?>
          <fieldset class="finder-spec"<?= $on ? '' : ' disabled' ?>>
            <legend class="finder-spec-head"><span class="finder-sublabel"><?= e($group['label']) ?></span><button type="button" class="finder-any" data-finder-any="<?= e($key) ?>">Any</button></legend>
            <div class="finder-chips-row">
              <?php foreach ($group['options'] as $value => $label): ?>
              <label class="finder-chip-opt"><input type="checkbox" name="<?= e($key) ?>[]" value="<?= e($value) ?>" <?= $on && in_array((string) $value, $sel['specs'][$key] ?? [], true) ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
              <?php endforeach; ?>
            </div>
          </fieldset>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <fieldset class="finder-block">
        <legend class="finder-label">Amount range</legend>
        <div class="finder-range">
          <input type="number" name="min" min="0" step="1" inputmode="numeric" placeholder="Min" aria-label="Minimum amount in KES" value="<?= $minPrice !== null ? $minPrice : '' ?>">
          <input type="number" name="max" min="0" step="1" inputmode="numeric" placeholder="Max" aria-label="Maximum amount in KES" value="<?= $maxPrice !== null ? $maxPrice : '' ?>">
        </div>
      </fieldset>

      <button type="submit" class="finder-search"><?= $icon('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>', 20) ?>Search</button>
    </form>
  </div>
</div>
