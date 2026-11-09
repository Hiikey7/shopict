<?php
/**
 * Contact bar above the header: phone and address on the left, opening hours on the right (from Admin → Settings).
 * On small screens the items scroll slowly across in a loop; the copy used for the loop
 * is hidden from screen readers, and the movement stops for people who prefer reduced motion.
 */
$phone = trim((string) setting('store_phone', ''));
$address = trim((string) setting('store_address', ''));
$hours = trim((string) setting('store_hours', ''));
if ($phone === '' && $address === '' && $hours === '') {
    return;
}
$items = function (bool $copy) use ($phone, $address, $hours): string {
    $tab = $copy ? ' tabindex="-1"' : '';
    $out = '';
    if ($phone !== '') {
        $out .= '<li class="topbar-phone"><a href="tel:' . e(preg_replace('/[^\d+]/', '', $phone)) . '"' . $tab . '>' . e($phone) . '</a></li>';
    }
    if ($address !== '') {
        $out .= '<li class="topbar-address"><span>' . e($address) . '</span></li>';
    }
    if ($hours !== '') {
        $out .= '<li class="topbar-hours"><span>' . e($hours) . '</span></li>';
    }
    return $out;
};
?>
<div class="store-topbar" role="region" aria-label="Store contact details">
  <div class="container store-topbar-viewport">
    <div class="store-topbar-track">
      <ul class="store-topbar-list"><?= $items(false) ?></ul>
      <ul class="store-topbar-list store-topbar-copy" aria-hidden="true"><?= $items(true) ?></ul>
    </div>
  </div>
</div>
