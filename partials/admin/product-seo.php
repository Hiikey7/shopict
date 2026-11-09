<?php
/**
 * Product editor: "Search engines (SEO)" card. Every field is optional: left empty, the shop writes it from the
 * product's saved details and keeps it up to date (shown in grey). "Use suggestion" copies that text in to edit it.
 *
 * @var array|null $product  saved product (null while adding)
 * @var array      $form     current form values
 * @var int        $pictureCount
 */
$saved = $product ?: null;
$auto = $saved ? [
    'title'    => seo_title_auto($saved),
    'meta'     => seo_meta_auto($saved, seo_offer_facts(price_product($saved))['low']),
    'metaKeep' => seo_meta_auto($saved),
    'focus'    => seo_focus_auto($saved),
    'keywords' => implode(', ', seo_keywords_auto($saved)),
    'faqs'     => seo_faqs_text(seo_faqs_auto($saved)),
    'alts'     => implode("\n", seo_image_alts(['image_alts' => ''] + $saved, max(1, $pictureCount))),
    'name'     => seo_name_suggestion($saved),
] : null;
$score = $saved ? seo_product_score($saved, seo_duplicate_maps(), $pictureCount) : null;
$scoreClass = $score ? ($score['score'] >= 80 ? 'badge-on' : ($score['score'] >= 55 ? 'badge-warn' : 'badge-off')) : '';
$value = fn (string $k) => (string) ($form[$k] ?? '');
$open = isset($errors['gtin']);
$slug = $saved ? product_url($saved) : 'product-name-p…';
$suggest = function (string $target, ?string $text, string $label = 'Use suggestion') {
    return $text !== null && $text !== '' ? '<button type="button" class="btn btn-link btn-sm p-0" data-fill="' . e($target) . '" data-value="' . e($text) . '">' . e($label) . '</button>' : '';
};
?>
<details class="card mb-4 card-collapse seo-card" id="seo"<?= $open ? ' open' : '' ?> data-seo-card>
  <summary class="card-header">Search engines (SEO)<?php if ($score): ?> <span class="badge <?= $scoreClass ?>" title="Our own checklist, not a Google ranking">Score <?= $score['score'] ?>/100</span><?php endif; ?> <span class="small text-muted fw-normal">(optional, filled in automatically)</span></summary>
  <div class="card-body">
    <?php if (!$saved): ?>
    <p class="section-note mb-3">Search titles, descriptions and questions are written automatically from the name, brand, categories and specifications once the product is saved. You can change them here afterwards.</p>
    <?php endif; ?>

    <div class="serp" aria-label="Google preview" data-serp data-auto-title="<?= e($auto['title'] ?? '') ?>" data-auto-desc="<?= e($auto['meta'] ?? '') ?>">
      <div class="serp-site"><span class="serp-icon" aria-hidden="true"><img src="../favicon.ico" alt="" width="16" height="16"></span><span><span class="serp-name"><?= e(seo_store()) ?></span><span class="serp-url"><?= e(preg_replace('#^https?://#', '', seo_site_url())) ?> › <?= e(short_title($slug, 48)) ?></span></span></div>
      <div class="serp-title" data-serp-title><?= e($value('seo_title') ?: ($auto['title'] ?? 'Product name Price in Kenya | ' . seo_store())) ?></div>
      <div class="serp-desc" data-serp-desc><?= e($value('meta_description') ?: ($auto['meta'] ?? 'Buy … in Kenya. Free pickup in Nairobi & countrywide delivery.')) ?></div>
    </div>
    <p class="small text-muted mt-2 mb-3">How it may look on Google. Google sometimes shortens or rewrites titles and descriptions.</p>

    <?php if ($score): ?>
    <ul class="seo-checks mb-4">
      <?php foreach ($score['checks'] as [$label, $ok, $weight, $tip]): ?>
      <li class="<?= $ok ? 'is-ok' : 'is-todo' ?>"><span class="seo-check-mark" aria-hidden="true"><?= $ok ? '✓' : '!' ?></span><span><?= e($label) ?><?php if (!$ok): ?> <span class="text-muted">– <?= e($tip) ?></span><?php endif; ?></span><span class="visually-hidden"><?= $ok ? 'done' : 'to do' ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php if ($auto && $auto['name'] !== $saved['title']): ?>
    <div class="seo-name-tip mb-3">
      <span class="small text-muted">Suggested product name:</span>
      <div class="small fw-medium"><?= e($auto['name']) ?></div>
      <?= $suggest('#title', $auto['name'], 'Use this name') ?>
    </div>
    <?php endif; ?>

    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-end gap-2"><label class="form-label mb-1" for="seo_title">Search title</label><?= $suggest('#seo_title', $auto['title'] ?? null) ?></div>
      <input class="form-control" id="seo_title" name="seo_title" maxlength="120" value="<?= e($value('seo_title')) ?>" placeholder="<?= e($auto['title'] ?? 'Automatic') ?>" data-count="60" data-serp-input="title">
      <div class="form-text">About 50–60 characters: brand, model, a key spec and “Price in Kenya”. Must be different from every other product.</div>
    </div>
    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-end gap-2"><label class="form-label mb-1" for="meta_description">Search description</label><?= $suggest('#meta_description', $auto['metaKeep'] ?? null) ?></div>
      <textarea class="form-control" id="meta_description" name="meta_description" rows="2" maxlength="320" placeholder="<?= e($auto['meta'] ?? 'Automatic') ?>" data-count="158" data-serp-input="desc"><?= e($value('meta_description')) ?></textarea>
      <div class="form-text">About 140–158 characters. The automatic text shows the current price; a saved text doesn’t mention a price, so it never goes out of date.</div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-5">
        <div class="d-flex justify-content-between align-items-end gap-2"><label class="form-label mb-1" for="focus_keyword">Main keyword</label><?= $suggest('#focus_keyword', $auto['focus'] ?? null, 'Use') ?></div>
        <input class="form-control" id="focus_keyword" name="focus_keyword" maxlength="120" value="<?= e($value('focus_keyword')) ?>" placeholder="<?= e($auto['focus'] ?? 'Brand + model') ?>">
      </div>
      <div class="col-md-7">
        <div class="d-flex justify-content-between align-items-end gap-2"><label class="form-label mb-1" for="secondary_keywords">Other keywords</label><?= $suggest('#secondary_keywords', $auto['keywords'] ?? null, 'Use') ?></div>
        <input class="form-control" id="secondary_keywords" name="secondary_keywords" maxlength="600" value="<?= e($value('secondary_keywords')) ?>" placeholder="<?= e($auto['keywords'] ?? 'Separated by commas') ?>">
      </div>
      <div class="col-12 form-text mt-1">Keywords are a checklist for you (use them naturally in the name and description). They are not shown on the page, and Google ignores keyword lists.</div>
    </div>
    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-end gap-2"><label class="form-label mb-1" for="faqs">Questions &amp; answers</label><?= $suggest('#faqs', $auto['faqs'] ?? null) ?></div>
      <textarea class="form-control" id="faqs" name="faqs" rows="6" placeholder="<?= e(($auto['faqs'] ?? '') !== '' ? $auto['faqs'] : "Q: Does it come with a charger?\nA: Yes, the original charger is in the box.") ?>"><?= e($value('faqs')) ?></textarea>
      <div class="form-text">Shown in a “Questions” tab on the product page. Write “Q:” and “A:” lines. Automatic questions only use the saved specifications; with fewer than two real facts, none are shown.</div>
    </div>
    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-end gap-2"><label class="form-label mb-1" for="image_alts">Picture descriptions</label><?= $suggest('#image_alts', $auto['alts'] ?? null) ?></div>
      <textarea class="form-control" id="image_alts" name="image_alts" rows="3" placeholder="<?= e($auto['alts'] ?? 'One line per picture, in order') ?>"><?= e($value('image_alts')) ?></textarea>
      <div class="form-text">One line per picture, in the order above, describing what it shows, e.g. “HP EliteBook 840 G8 keyboard and touchpad”. Read by Google Images and screen readers.</div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label mb-1" for="gtin">Barcode (GTIN / EAN / UPC)</label>
        <input class="form-control<?= isset($errors['gtin']) ? ' is-invalid' : '' ?>" id="gtin" name="gtin" inputmode="numeric" maxlength="20" value="<?= e($value('gtin')) ?>" placeholder="From the box, e.g. 0196068…">
        <?php if (isset($errors['gtin'])): ?><div class="invalid-feedback d-block"><?= e($errors['gtin']) ?></div><?php endif; ?>
      </div>
      <div class="col-md-6">
        <label class="form-label mb-1" for="mpn">Maker’s part number (MPN)</label>
        <input class="form-control" id="mpn" name="mpn" maxlength="64" value="<?= e($value('mpn')) ?>" placeholder="e.g. 358P6EA">
      </div>
      <div class="col-12 form-text mt-1">Optional. Helps Google Shopping match the exact product. Only enter codes printed on the box or the maker’s spec sheet.</div>
    </div>
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" role="switch" id="noindex" name="noindex" value="1" <?= !empty($form['noindex']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="noindex">Hide from Google</label>
      <div class="form-text">The product stays in the shop but isn’t shown in search results, the sitemap or the Shopping feed.</div>
    </div>
  </div>
</details>
