<?php
// Customer policy: Terms & Conditions, including Returns & Exchanges (linked from the footer and checkout).
require __DIR__ . '/includes/bootstrap.php';

visitor_id();
track_page_view();

$phone = trim((string) setting('store_phone', ''));
$email = trim((string) setting('store_email', ''));
$address = trim((string) setting('store_address', ''));
$tel = fn (string $n) => 'tel:' . preg_replace('/[^\d+]/', '', $n);

partial('page-start', ['title' => 'Terms & Conditions · Shop ICT Gadgets']);
?>
<section class="tf-sp-2 co-page">
  <div class="container policy-page">
    <nav class="co-crumbs body-text-3" aria-label="Breadcrumb"><a class="link" href="./">Home</a><span>/</span><span aria-current="page">Terms &amp; Conditions</span></nav>

    <article class="co-card policy-card">
      <p class="policy-eyebrow">Customer policy</p>
      <h1 class="policy-title">Terms &amp; Conditions</h1>
      <p class="policy-updated">Last updated: 6 November 2026</p>
      <p class="policy-lead">Welcome to Shop ICT Gadgets (“we”, “us” or “our”). We run the website <strong>www.shopict.co.ke</strong> and our shop at <?= e($address ?: 'Nairobi, Laxmi Plaza, 3rd Flr, Room No 5') ?>. By using our website, placing an order or buying from our shop, you agree to these Terms &amp; Conditions. If you do not agree, please do not use our website or services.</p>

      <nav class="policy-toc" aria-label="On this page">
        <a href="#t-general">General information</a>
        <a href="#t-pricing">Pricing</a>
        <a href="#t-orders">Orders &amp; payment</a>
        <a href="#t-delivery">Pickup &amp; delivery</a>
        <a href="#returns">Returns &amp; exchanges</a>
        <a href="#t-warranty">Warranty</a>
        <a href="#t-liability">Liability</a>
        <a href="#t-ip">Intellectual property</a>
        <a href="#t-law">Governing law</a>
        <a href="#t-changes">Changes</a>
      </nav>

      <section class="policy-block" id="t-general" aria-labelledby="t-general-h">
        <h2 id="t-general-h">1. General information</h2>
        <p>The information on our website is for general information only. We work to keep it accurate, including product descriptions, specifications and pictures, but we are not responsible for errors or omissions. Information is provided “as is”, without any guarantee that it is complete, accurate or up to date. Please confirm any detail that matters to you with our staff before you buy.</p>
      </section>

      <section class="policy-block" id="t-pricing" aria-labelledby="t-pricing-h">
        <h2 id="t-pricing-h">2. Pricing</h2>
        <p>All prices are in Kenya Shillings (KSh) and are <strong>exclusive of Value Added Tax (VAT)</strong> unless otherwise stated. Prices may change without notice because of market changes, supplier prices or other factors. The price that applies is the one confirmed when your order is accepted.</p>
        <p>If a product is listed at a wrong price by mistake, we will contact you before processing the order, and you may cancel it at no cost.</p>
      </section>

      <section class="policy-block" id="t-orders" aria-labelledby="t-orders-h">
        <h2 id="t-orders-h">3. Orders &amp; payment</h2>
        <p>When you order on our website, by phone, on WhatsApp or in our shop, you agree to give accurate and complete details. An order is accepted once we confirm it with you.</p>
        <p>We may refuse or cancel an order, for example if a product is out of stock, a price is wrong, or we suspect fraud. If you have already paid for a cancelled order, we will refund the amount paid.</p>
        <p>Payment is made in full, in cash, on delivery or when you pick up your order at our shop. We do not offer instalment or “pay later” plans.</p>
      </section>

      <section class="policy-block" id="t-delivery" aria-labelledby="t-delivery-h">
        <h2 id="t-delivery-h">4. Pickup &amp; delivery</h2>
        <ul>
          <li><strong>Free pickup:</strong> you can collect your order at our shop<?= $address !== '' ? ' (' . e($address) . ')' : '' ?><?= setting('store_hours') ? ' during opening hours: ' . e(setting('store_hours')) : '' ?>. We will let you know when it is ready.</li>
          <li><strong>Countrywide delivery:</strong> we deliver across Kenya. The delivery cost depends on your location and is confirmed with you by phone before dispatch.</li>
        </ul>
        <p>Delivery times are estimates, not guarantees. We are not liable for delays caused by couriers or by circumstances beyond our control. Please inspect your order when you receive it.</p>
      </section>

      <section class="policy-block" id="returns" aria-labelledby="t-returns-h">
        <h2 id="t-returns-h">5. Returns &amp; exchanges</h2>
        <p>All customers are advised to inspect and test their products immediately after purchase.</p>
        <p>You may request a return or exchange within <strong>3 days from the date of purchase</strong>, provided the product is in its original condition, with all accessories, packaging and documentation intact.</p>
        <p>After the 3-day period, we do not accept returns or exchanges because of a change of mind, preference, dissatisfaction or compatibility issues.</p>
        <p>An exchange or replacement given as a goodwill gesture does not extend or restart the 3-day return period, unless we expressly agree otherwise in writing.</p>
      </section>

      <section class="policy-block" id="t-warranty" aria-labelledby="t-warranty-h">
        <h2 id="t-warranty-h">6. Warranty</h2>
        <p>Our warranty is separate from the return and exchange policy. Where a warranty applies, the product is handled according to the warranty terms given at the time of purchase.</p>
        <ul>
          <li><strong>Manufacturer warranty:</strong> any warranty is provided by the manufacturer, not by Shop ICT Gadgets, unless we state otherwise. Defects are handled by the manufacturer’s authorised service centre or its official partners.</li>
          <li><strong>Check before you buy:</strong> please confirm with our staff whether a product comes with a local warranty. Not all products have one, and some are sold “as is”.</li>
          <li><strong>What is not covered:</strong> physical damage (such as a broken screen), liquid damage, misuse, and unauthorised repairs or modifications.</li>
          <li><strong>Repair or replacement, not refund:</strong> we do not give refunds for faults covered by warranty. Faulty products are repaired or replaced by the manufacturer.</li>
          <li><strong>Registration:</strong> where the manufacturer requires it, you are responsible for registering your product on its website or by the method it provides.</li>
          <li><strong>Accessories:</strong> accessories such as chargers, cables, earphones and cases do not come with a warranty unless stated otherwise.</li>
          <li><strong>Service centre help:</strong> if you cannot reach the manufacturer’s service centre, we can take the product there for you. This can take 7 to 30 days, depending on the manufacturer.</li>
        </ul>
      </section>

      <section class="policy-block" id="t-liability" aria-labelledby="t-liability-h">
        <h2 id="t-liability-h">7. Limitation of liability</h2>
        <p>To the fullest extent permitted by law, Shop ICT Gadgets is not liable for any indirect, incidental or consequential loss arising from the use of our website, products or services, including loss of profits or data. Please back up your data before handing in a device for warranty service. Our total liability for any claim will not exceed the amount you paid for the product or service concerned. Nothing in these terms limits your rights under Kenyan consumer protection law.</p>
      </section>

      <section class="policy-block" id="t-ip" aria-labelledby="t-ip-h">
        <h2 id="t-ip-h">8. Intellectual property</h2>
        <p>All content on our website, including text, images, logos and designs, belongs to Shop ICT Gadgets or its licensors and is protected by copyright and other intellectual property laws. Brand names and product images belong to their respective owners. You may not copy, distribute or use our content without our written permission.</p>
      </section>

      <section class="policy-block" id="t-law" aria-labelledby="t-law-h">
        <h2 id="t-law-h">9. Governing law</h2>
        <p>These Terms &amp; Conditions are governed by the laws of the Republic of Kenya. Any dispute arising from them is subject to the exclusive jurisdiction of the courts in Nairobi, Kenya.</p>
      </section>

      <section class="policy-block" id="t-changes" aria-labelledby="t-changes-h">
        <h2 id="t-changes-h">10. Changes to these terms</h2>
        <p>We may update these terms at any time. Changes take effect when they are posted on this page, and the “Last updated” date above will change. If you keep using our website or services after a change, you accept the updated terms. How we handle your personal information is explained in our <a class="link text-secondary" href="privacy-policy.php">Privacy Policy</a>.</p>
      </section>

      <p class="policy-agree">By purchasing from Shop ICT Gadgets, you acknowledge and agree to these terms, including the return, exchange and warranty terms above.</p>

      <?php if ($phone !== '' || $email !== '' || $address !== '' || whatsapp_number()): ?>
      <div class="policy-contact">
        <h2>Questions, returns or warranty help?</h2>
        <p class="mb-0">
          <?php if ($phone !== ''): ?>Call us on <a class="link text-secondary" href="<?= e($tel($phone)) ?>"><?= e($phone) ?></a><?php endif; ?><?php if (whatsapp_number()): ?><?= $phone !== '' ? ', ' : '' ?><a class="link text-secondary" href="<?= e(whatsapp_url('Hi! I have a question about my order.')) ?>" target="_blank" rel="noopener">message us on WhatsApp</a><?php endif; ?><?php if ($email !== ''): ?><?= $phone !== '' || whatsapp_number() ? ' or email ' : 'Email ' ?><a class="link text-secondary" href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>.
          <?php if ($address !== ''): ?><br>Visit us: <?= e($address) ?><?= setting('store_hours') ? ' · ' . e(setting('store_hours')) : '' ?><?php endif; ?>
        </p>
      </div>
      <?php endif; ?>
    </article>
  </div>
</section>
<?php partial('page-end'); ?>
