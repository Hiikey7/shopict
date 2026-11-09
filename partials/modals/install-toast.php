<?php
/** "Install the app" message at the top of the page, shown by main.js after 10 seconds on the site. */
?>
<div class="install-toast" role="region" aria-label="Install the app" data-install-toast hidden>
  <img class="install-toast-icon" src="<?= asset_v('images/pwa/icon-192.png') ?>" alt="" width="44" height="44">
  <div class="install-toast-text">
    <strong>Install <?= e(setting('store_name', 'Shop ICT Gadgets')) ?></strong>
    <span data-install-toast-line>Shop faster from your home screen, even with a weak connection.</span>
  </div>
  <button type="button" class="install-toast-btn" data-install-toast-go>Install</button>
  <button type="button" class="install-toast-close" aria-label="Not now" data-install-toast-close>
    <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  </button>
</div>
