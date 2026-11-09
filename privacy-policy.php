<?php
// Customer policy: Privacy Policy (linked from the footer and the checkout form).
require __DIR__ . '/includes/bootstrap.php';

visitor_id();
track_page_view();

$phone = trim((string) setting('store_phone', ''));
$email = trim((string) setting('store_email', ''));
$address = trim((string) setting('store_address', ''));
$tel = fn (string $n) => 'tel:' . preg_replace('/[^\d+]/', '', $n);

partial('page-start', ['title' => 'Privacy Policy · Shop ICT Gadgets']);
?>
<section class="tf-sp-2 co-page">
  <div class="container policy-page">
    <nav class="co-crumbs body-text-3" aria-label="Breadcrumb"><a class="link" href="./">Home</a><span>/</span><span aria-current="page">Privacy Policy</span></nav>

    <article class="co-card policy-card">
      <p class="policy-eyebrow">Customer policy</p>
      <h1 class="policy-title">Privacy Policy</h1>
      <p class="policy-updated">Last updated: 6 November 2026</p>
      <p class="policy-lead">Shop ICT Gadgets (“we”, “us” or “our”) runs the website <strong>www.shopict.co.ke</strong> and our shop at <?= e($address ?: 'Nairobi, Laxmi Plaza, 3rd Flr, Room No 5') ?>. We are committed to handling your personal information safely and responsibly. This policy explains what we collect, how we use and protect it, and the choices you have when you visit our website, place an order or contact us.</p>

      <section class="policy-block" aria-labelledby="pp-collect">
        <h2 id="pp-collect">1. Information we collect</h2>
        <h3>1.1 Information you give us</h3>
        <ul>
          <li><strong>Contact details:</strong> your name, phone number and email address when you place an order or contact us.</li>
          <li><strong>Delivery details:</strong> your town or city, country and any delivery notes, or that you chose to pick up at our shop.</li>
          <li><strong>Order details:</strong> the products, options and quantities you order and any promo code you use.</li>
          <li><strong>Warranty and after-sales details:</strong> details about your device, such as model and serial number, when you ask for help with a product you bought from us.</li>
        </ul>
        <p>We do not collect card or mobile-money details on this website. You pay in cash on delivery or when you pick up your order.</p>
        <h3>1.2 Information collected automatically</h3>
        <ul>
          <li><strong>Usage data:</strong> the pages and products you view, items added to your cart, and the website that referred you.</li>
          <li><strong>Device data:</strong> the general type of device you use (phone, tablet or computer).</li>
        </ul>
        <p>This is linked to a random visitor ID stored in a cookie, not to your name. We do not record your IP address in our statistics.</p>
        <h3>1.3 Information from others</h3>
        <p>If you message us on WhatsApp or interact with us on social media, we receive the information you share there, such as your name and phone number.</p>
      </section>

      <section class="policy-block" aria-labelledby="pp-how">
        <h2 id="pp-how">2. How we collect information</h2>
        <ul>
          <li><strong>Directly from you:</strong> when you fill in the checkout form, email us<?= $email !== '' ? ' at <a class="link text-secondary" href="mailto:' . e($email) . '">' . e($email) . '</a>' : '' ?>, call us<?= $phone !== '' ? ' on <a class="link text-secondary" href="' . e($tel($phone)) . '">' . e($phone) . '</a>' : '' ?>, message us on WhatsApp or visit our shop.</li>
          <li><strong>Automatically:</strong> through cookies when you use the website (see section 5).</li>
          <li><strong>From others:</strong> as described in section 1.3.</li>
        </ul>
      </section>

      <section class="policy-block" aria-labelledby="pp-use">
        <h2 id="pp-use">3. How we use your information</h2>
        <ul>
          <li><strong>Processing orders:</strong> to confirm your order, arrange pickup or delivery, and send your order confirmation by email.</li>
          <li><strong>Customer service:</strong> to answer your questions and handle returns, exchanges and warranty requests.</li>
          <li><strong>Improving the website:</strong> to understand which pages and products are popular and make the shop easier to use.</li>
          <li><strong>Offers:</strong> to tell you about products or offers only if you have asked us to. You can ask us to stop at any time.</li>
          <li><strong>Legal compliance:</strong> to meet our obligations under Kenyan law, including tax and consumer protection requirements.</li>
          <li><strong>Preventing fraud:</strong> to detect and prevent fake or fraudulent orders.</li>
        </ul>
      </section>

      <section class="policy-block" aria-labelledby="pp-share">
        <h2 id="pp-share">4. How we share your information</h2>
        <p>We do not sell your personal information. We share it only when needed:</p>
        <ul>
          <li><strong>Service providers:</strong> with trusted partners who help us run the shop, such as couriers who deliver your order, and our website hosting and email providers.</li>
          <li><strong>Legal obligations:</strong> when required by law, a court order or a government authority in Kenya.</li>
          <li><strong>Business transfers:</strong> if our business is merged, sold or transferred, your information may pass to the new owner, under this policy.</li>
          <li><strong>With your consent:</strong> in any other case where you have clearly agreed.</li>
        </ul>
      </section>

      <section class="policy-block" aria-labelledby="pp-cookies">
        <h2 id="pp-cookies">5. Cookies</h2>
        <p>Cookies are small text files stored on your device. We use them to:</p>
        <ul>
          <li>keep your cart and wishlist while you shop;</li>
          <li>keep the checkout secure;</li>
          <li>count visits and product views with a random visitor ID.</li>
        </ul>
        <p>Our “Visit Our Shop” map is provided by Google Maps, which may set its own cookies. You can block or delete cookies in your browser settings, but the cart and checkout may not work without them.</p>
      </section>

      <section class="policy-block" aria-labelledby="pp-security">
        <h2 id="pp-security">6. Data security</h2>
        <p>We take reasonable technical and organisational measures to protect your information from unauthorised access, loss or misuse, including encrypted connections (SSL), keeping our systems up to date, and limiting access to staff who need it.</p>
        <p>No website can be completely secure. If you believe your information has been misused, please contact us straight away.</p>
      </section>

      <section class="policy-block" aria-labelledby="pp-rights">
        <h2 id="pp-rights">7. Your rights</h2>
        <p>Under the Kenya Data Protection Act, 2019, you have the right to:</p>
        <ul>
          <li><strong>Access:</strong> ask for a copy of the personal information we hold about you.</li>
          <li><strong>Correction:</strong> ask us to correct information that is wrong or incomplete.</li>
          <li><strong>Deletion:</strong> ask us to delete your information, unless we must keep it by law.</li>
          <li><strong>Objection:</strong> ask us to stop sending you offers or using your information for a particular purpose.</li>
          <li><strong>Portability:</strong> ask for your information in a common, structured format.</li>
        </ul>
        <p>To use these rights, contact us using the details in section 12. We will respond within the time required by Kenyan data protection law.</p>
      </section>

      <section class="policy-block" aria-labelledby="pp-retention">
        <h2 id="pp-retention">8. How long we keep information</h2>
        <p>We keep personal information only as long as we need it to complete your order, provide after-sales support, resolve disputes and meet legal requirements such as tax and accounting records (usually 7 years). After that, we securely delete or anonymise it.</p>
      </section>

      <section class="policy-block" aria-labelledby="pp-transfers">
        <h2 id="pp-transfers">9. International transfers</h2>
        <p>Some of our service providers, such as email and hosting providers, may process information outside Kenya. When this happens, we make sure appropriate safeguards are in place as required by Kenyan data protection law.</p>
      </section>

      <section class="policy-block" aria-labelledby="pp-links">
        <h2 id="pp-links">10. Other websites</h2>
        <p>Our website links to other services such as WhatsApp, Google Maps and social media. We are not responsible for their privacy practices, so please read their privacy policies before sharing information with them.</p>
      </section>

      <section class="policy-block" aria-labelledby="pp-children">
        <h2 id="pp-children">11. Children</h2>
        <p>Our website is not intended for anyone under 18. We do not knowingly collect information from children, and we will delete it promptly if we learn that we have.</p>
      </section>

      <section class="policy-block" aria-labelledby="pp-changes">
        <h2 id="pp-changes">12. Changes and contact</h2>
        <p>We may update this policy from time to time. The latest version is always on this page, with the date it was last updated at the top.</p>
        <p>If you have any questions or requests about your personal information, contact us:</p>
        <ul class="policy-contact-list">
          <?php if ($address !== ''): ?><li><strong>Visit:</strong> <?= e($address) ?><?= setting('store_hours') ? ' · ' . e(setting('store_hours')) : '' ?></li><?php endif; ?>
          <?php if ($email !== ''): ?><li><strong>Email:</strong> <a class="link text-secondary" href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li><?php endif; ?>
          <?php if ($phone !== ''): ?><li><strong>Phone:</strong> <a class="link text-secondary" href="<?= e($tel($phone)) ?>"><?= e($phone) ?></a></li><?php endif; ?>
          <?php if (whatsapp_number()): ?><li><strong>WhatsApp:</strong> <a class="link text-secondary" href="<?= e(whatsapp_url('Hi! I have a question about my personal information.')) ?>" target="_blank" rel="noopener">Message us</a></li><?php endif; ?>
        </ul>
      </section>
    </article>
  </div>
</section>
<?php partial('page-end'); ?>
