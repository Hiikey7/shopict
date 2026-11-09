<footer class="tf-footer">
  <div class="ft-body-wrap">
    <?php
    $ftPhone = trim((string) setting('store_phone', ''));
    $ftEmail = trim((string) setting('store_email', ''));
    $ftCity = trim((string) setting('store_city', ''));
    $ftAbout = trim((string) setting('footer_about', ''));
    // Shop links: All Products, then the main product categories (Admin → Product categories).
    $ftShop = ['All Products' => 'shop.php', 'Articles' => 'buying-guides'];
    foreach (category_tree() as $ftCat) {
        $ftShop[$ftCat['name']] = category_url($ftCat);
    }
    // Shop by brand: the active brands from Admin → Brands, each opening a shop search for that brand.
    $ftBrands = db()->query("SELECT name, link_url FROM brands WHERE is_active = 1 ORDER BY position, name")->fetchAll();
    $ftBrandName = function (string $n): string {
        $n = trim($n);
        if ($n === mb_strtolower($n) || $n === mb_strtoupper($n)) {
            return mb_strlen($n) <= 3 ? mb_strtoupper($n) : ucfirst(mb_strtolower($n)); // "hp" → HP, "DELL" → Dell
        }
        return $n;
    };
    $ftIcon = fn (string $path) => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
    ?>
    <div class="ft-body-inner">
      <div class="container">
        <div class="ft-columns">
          <div class="ft-col ft-col-about">
            <a class="logo-site" href="./"><img alt="<?= e(setting('store_name', 'Shop ICT Gadgets')) ?>" loading="lazy" width="185" height="41" decoding="async" src="<?= e(asset(store_logo())) ?>" /></a>
            <?php if ($ftAbout !== ''): ?><p class="ft-about"><?= e($ftAbout) ?></p><?php endif; ?>
            <ul class="social-list ft-social" aria-label="Follow us">
              <li>
                <a href="https://www.facebook.com" aria-label="Facebook" target="_blank" rel="noopener"><i class="icon-facebook"></i></a>
              </li>
              <li>
                <a href="https://x.com/" aria-label="X" target="_blank" rel="noopener"><i class="icon-x"></i></a>
              </li>
              <li>
                <a href="https://www.instagram.com/" aria-label="Instagram" target="_blank" rel="noopener"><i class="icon-instagram"></i></a>
              </li>
              <li>
                <a href="https://www.linkedin.com/" aria-label="LinkedIn" target="_blank" rel="noopener"><i class="icon-linkin"></i></a>
              </li>
              <li>
                <a href="https://web.whatsapp.com/" aria-label="WhatsApp" target="_blank" rel="noopener"><i class="icon-whatapp"></i></a>
              </li>
            </ul>
          </div>

          <nav class="ft-col" aria-labelledby="ftShopTitle">
            <h6 class="ft-title" id="ftShopTitle">Shop</h6>
            <ul class="ft-links">
              <?php foreach ($ftShop as $label => $href): ?>
              <li><a class="link" href="<?= e($href) ?>"><?= e($label) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </nav>

          <?php if ($ftBrands): ?>
          <nav class="ft-col" aria-labelledby="ftBrandsTitle">
            <h6 class="ft-title" id="ftBrandsTitle">Shop by Brand</h6>
            <ul class="ft-links ft-brand-links">
              <?php foreach ($ftBrands as $ftBrand): $ftHref = $ftBrand["link_url"] && $ftBrand["link_url"] !== "#" ? $ftBrand["link_url"] : brand_url($ftBrand["name"]); ?>
              <li><a class="link" href="<?= e($ftHref) ?>"><?= e($ftBrandName($ftBrand["name"])) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </nav>
          <?php endif; ?>

          <div class="ft-col">
            <h6 class="ft-title">Reach Us</h6>
            <ul class="ft-contact">
              <?php if ($ftPhone !== ''): ?>
              <li><?= $ftIcon('<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>') ?><a class="link" href="tel:<?= e(preg_replace('/[^\d+]/', '', $ftPhone)) ?>"><?= e($ftPhone) ?></a></li>
              <?php endif; ?>
              <?php if ($ftEmail !== ''): ?>
              <li><?= $ftIcon('<rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="m3 6.5 9 6.5 9-6.5"/>') ?><a class="link" href="mailto:<?= e($ftEmail) ?>"><?= e($ftEmail) ?></a></li>
              <?php endif; ?>
              <?php if ($ftCity !== ''): ?>
              <li><?= $ftIcon('<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>') ?><span><?= e($ftCity) ?></span></li>
              <?php endif; ?>
            </ul>
          </div>

        </div>
      </div>
    </div>
    <div class="ft-body-bottom">
      <div class="container">
        <div class="ft-bottom">
          <p class="nocopy caption ft-copy">© <?= date('Y') ?> Shop ICT Gadgets. All rights reserved.<span class="ft-sep" aria-hidden="true">•</span><a class="link" href="about">About Us</a><span class="ft-sep" aria-hidden="true">•</span><a class="link" href="terms.php">Terms &amp; Conditions</a><span class="ft-sep" aria-hidden="true">•</span><a class="link" href="terms.php#returns">Returns &amp; Exchanges</a><span class="ft-sep" aria-hidden="true">•</span><a class="link" href="privacy-policy.php">Privacy Policy</a></p>
        </div>
      </div>
    </div>
  </div>
</footer>
