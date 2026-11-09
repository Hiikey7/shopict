<?php
$popupEnabled = setting('promo_popup_enabled') === '1';
$popupStarts = setting('promo_popup_starts_at');
$popupEnds = setting('promo_popup_ends_at');
$popupStartTs = $popupStarts ? strtotime($popupStarts) : false;
$popupEndTs = $popupEnds ? strtotime($popupEnds) : false;
$popupInSchedule = (!$popupStarts || ($popupStartTs !== false && $popupStartTs <= time()))
    && (!$popupEnds || ($popupEndTs !== false && $popupEndTs >= time()));
$popupType = setting('promo_popup_action_type', 'product');
$popupProduct = null;
$popupProductId = (int) setting('promo_popup_product_id', '0');
if ($popupEnabled && $popupInSchedule && $popupProductId > 0) {
    $stmt = db()->prepare('SELECT id, title FROM products WHERE id = ? AND is_active = 1');
    $stmt->execute([$popupProductId]);
    $popupProduct = $stmt->fetch() ?: null;
}
$popupCode = trim((string) setting('promo_popup_code', ''));
$popupImage = setting('promo_popup_image');
$popupReady = $popupEnabled && $popupInSchedule
    && trim((string) setting('promo_popup_title', '')) !== ''
    && trim((string) setting('promo_popup_description', '')) !== ''
    && trim((string) setting('promo_popup_action_label', '')) !== ''
    && (($popupType === 'product' && $popupProduct !== null) || ($popupType === 'promo_code' && $popupCode !== ''));
?>
<?php if ($popupReady): ?>
<div class="modal fade promo-popup" id="promoPopup" tabindex="-1" aria-labelledby="promoPopupTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content<?= $popupImage ? ' promo-popup--with-image' : '' ?>">
      <button type="button" class="btn-close promo-popup__close" data-bs-dismiss="modal" aria-label="Close"></button>
      <?php if ($popupImage): ?>
      <div class="promo-popup__image"><img src="<?= e(asset($popupImage)) ?>" alt="<?= e(setting('promo_popup_title')) ?>"></div>
      <?php endif; ?>
      <div class="promo-popup__body">
        <h2 class="promo-popup__title" id="promoPopupTitle"><?= e(setting('promo_popup_title')) ?></h2>
        <p class="promo-popup__description"><?= nl2br(e(setting('promo_popup_description'))) ?></p>
        <?php if ($popupType === 'promo_code'): ?>
        <div class="promo-popup__code-row">
          <button type="button" class="btn promo-popup__action" data-promo-copy data-code="<?= e($popupCode) ?>"><?= e(setting('promo_popup_action_label')) ?></button>
          <?php if ($popupProduct): ?>
          <a class="promo-popup__product-link" href="<?= e(product_url($popupProduct)) ?>" aria-label="View <?= e($popupProduct['title']) ?>">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="M5 12h13M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </a>
          <?php endif; ?>
        </div>
        <p class="promo-popup__copy-status" data-promo-copy-status role="status" aria-live="polite" hidden></p>
        <?php else: ?>
        <a class="btn promo-popup__action" href="<?= e(product_url($popupProduct)) ?>" data-bs-dismiss="modal"><?= e(setting('promo_popup_action_label')) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
