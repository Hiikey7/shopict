<?php
/** Closing of every storefront page: shop map, footer, shared modals and scripts. */
partial('sections/store-map'); // Visit Our Shop: map to the shop (Admin → Settings → map location)
partial('footer');
partial('modals/cart');
partial('modals/quickview');
partial('modals/mobile-menu');
partial('modals/whatsapp-float');
partial('modals/install-toast');
partial('modals/toolbar');
partial('modals/search');
$seasonalEffect = setting('seasonal_effect', 'off');
$seasonalStart = setting('seasonal_starts_at');
$seasonalEnd = setting('seasonal_ends_at');
$seasonalStartTs = $seasonalStart ? strtotime($seasonalStart) : false;
$seasonalEndTs = $seasonalEnd ? strtotime($seasonalEnd) : false;
if (!in_array($seasonalEffect, ['black_november', 'christmas'], true)
    || ($seasonalStart !== null && ($seasonalStartTs === false || $seasonalStartTs > time()))
    || ($seasonalEnd !== null && ($seasonalEndTs === false || $seasonalEndTs < time()))) {
    $seasonalEffect = 'off';
}
?>
    </div>
    <?php partial('modals/promo'); ?>
    <script>
      window.ONSUS = <?= json_encode([
          'csrf'       => csrf_token(),
          'products'   => register_product(),
          'cart'       => page_state()['cartIds'],
          'cartKeys'   => page_state()['cartKeys'],
          'whatsapp'   => whatsapp_number(),
          'wishlist'   => page_state()['wishlist'],
          'dealEndsAt' => ($t = strtotime((string) setting('deal_ends_at', ''))) ? $t * 1000 : null,
          'seasonalEffect' => $seasonalEffect,
          'isHomepage' => basename($_SERVER['SCRIPT_NAME'] ?? '') === 'index.php',
      ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    </script>
    <script src="<?= asset('js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset('js/swiper-bundle.min.js') ?>"></script>
    <script src="<?= asset('js/simpleParallax.min.js') ?>"></script>
    <script src="<?= asset('js/wow.js') ?>"></script>
    <script src="<?= asset_v('js/main.js') ?>"></script>
  </body>
</html>
