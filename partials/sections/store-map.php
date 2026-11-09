<?php
/**
 * "Visit Our Shop": a full-width Google map of the shop.
 * The map location comes from Settings (map_query); no API key is needed for the embed.
 */
if (!section_visible('store_map')) {
    return;
}
$query = trim((string) setting('map_query', 'Laxmi Plaza, Nairobi')) ?: 'Laxmi Plaza, Nairobi';
$embed = 'https://www.google.com/maps?q=' . rawurlencode($query) . '&z=17&output=embed';
?>
<section class="tf-sp-2 pt-0 store-map-section" aria-labelledby="storeMapTitle">
  <div class="container">
    <div class="flat-title">
      <h5 class="fw-semibold" id="storeMapTitle"><?= e(section_title('store_map')) ?></h5>
    </div>
    <div class="store-map store-map-only">
      <div class="store-map-frame">
        <iframe src="<?= e($embed) ?>" title="Map: <?= e($query) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
      </div>
    </div>
  </div>
</section>
