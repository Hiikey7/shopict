<?php
/**
 * Minimal, mobile-friendly order confirmation email.
 * Uses table layout and inline styles for broad email-client support.
 *
 * @var array  $order
 * @var array  $items
 * @var string $store
 * @var string $url
 * @var string $bannerImageUrl
 * @var string $bannerLink
 */
$money = fn ($value) => money($value);
$deliveryMethod = (string) ($order['delivery_method'] ?? 'delivery');
$phone = preg_replace('/[^0-9+]/', '', (string) ($order['phone'] ?? ''));
$whatsapp = whatsapp_number();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <title>Order <?= e($order['order_number']) ?> confirmed</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;color:#202124;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f5f7;">
    <tr><td align="center" style="padding:20px 10px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background:#ffffff;border:1px solid #e6e7e9;">
        <tr><td style="height:4px;background:#e30613;font-size:0;line-height:0;">&nbsp;</td></tr>

        <tr><td style="padding:24px 22px 18px;">
          <p style="margin:0 0 8px;color:#e30613;font-size:12px;font-weight:bold;letter-spacing:1px;">ORDER CONFIRMATION</p>
          <h1 style="margin:0 0 8px;color:#17191c;font-size:25px;line-height:31px;font-weight:700;">Thank you, <?= e($order['customer_name']) ?>.</h1>
          <p style="margin:0;color:#50545a;font-size:15px;line-height:23px;">Your order has been received and is now being processed. We appreciate your trust and can’t wait to serve you.</p>
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;">
            <tr>
              <td style="padding:8px 10px;background:#f7f7f8;border:1px solid #e7e7e9;color:#555b62;font-size:12px;">Order number</td>
              <td style="padding:8px 10px;border:1px solid #e7e7e9;color:#17191c;font-size:13px;font-weight:bold;"><?= e($order['order_number']) ?></td>
            </tr>
          </table>
        </td></tr>

        <tr><td style="padding:0 22px;">
          <h2 style="margin:0;padding:0 0 10px;border-bottom:1px solid #e8e9eb;color:#17191c;font-size:16px;line-height:22px;">Order summary</h2>
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <?php foreach ($items as $it): ?>
            <?php
              $itemImage = trim((string) ($it['image'] ?? ''));
              $itemImageUrl = $itemImage !== '' ? seo_abs(asset($itemImage)) : '';
            ?>
            <tr>
              <td width="58" valign="middle" style="width:58px;padding:12px 8px 12px 0;border-bottom:1px solid #eeeeef;">
                <?php if ($itemImageUrl !== ''): ?><img src="<?= e($itemImageUrl) ?>" width="50" height="50" alt="<?= e($it['title']) ?>" style="display:block;width:50px;height:50px;border:0;object-fit:contain;"><?php endif; ?>
              </td>
              <td valign="middle" style="padding:12px 6px;border-bottom:1px solid #eeeeef;font-size:13px;line-height:19px;word-break:break-word;">
                <strong style="color:#202124;font-size:13px;"><?= e($it['title']) ?></strong>
                <?php if (!empty($it['variant_label'])): ?><br><span style="color:#656a70;"><?= e($it['variant_label']) ?></span><?php endif; ?>
                <?php if (!empty($it['is_preorder'])): ?><br><span style="color:#8a5a00;font-weight:bold;">Pre-order</span><?php endif; ?>
                <br><span style="color:#656a70;">Qty: <?= (int) $it['quantity'] ?></span>
              </td>
              <td align="right" valign="middle" style="width:104px;padding:12px 0 12px 6px;border-bottom:1px solid #eeeeef;white-space:nowrap;font-size:13px;font-weight:bold;color:#202124;">
                <?= $money($it['line_total']) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </table>

          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:12px;font-size:13px;line-height:20px;">
            <tr><td style="padding:3px 0;color:#555b62;">Subtotal</td><td align="right" style="padding:3px 0;color:#202124;"><?= $money($order['subtotal']) ?></td></tr>
            <?php if ((float) ($order['discount'] ?? 0) > 0): ?>
            <tr><td style="padding:3px 0;color:#555b62;">Discount<?php if (!empty($order['promo_code'])): ?> (<?= e($order['promo_code']) ?>)<?php endif; ?></td><td align="right" style="padding:3px 0;color:#16803c;">−<?= $money($order['discount']) ?></td></tr>
            <?php endif; ?>
            <tr><td style="padding:3px 0;color:#555b62;">Delivery</td><td align="right" style="padding:3px 0;color:#202124;"><?= e(delivery_cost_text($deliveryMethod, (float) $order['delivery_fee'])) ?></td></tr>
            <tr><td colspan="2" style="padding:8px 0 0;border-top:1px solid #e8e9eb;"></td></tr>
            <tr><td style="padding:3px 0;color:#17191c;font-size:15px;font-weight:bold;">Total to pay</td><td align="right" style="padding:3px 0;color:#e30613;font-size:17px;font-weight:bold;"><?= $money($order['total']) ?></td></tr>
          </table>
        </td></tr>

        <tr><td style="padding:22px 22px 0;">
          <h2 style="margin:0 0 10px;padding-bottom:9px;border-bottom:1px solid #e8e9eb;color:#17191c;font-size:16px;line-height:22px;"><?= $deliveryMethod === 'pickup' ? 'Pickup details' : 'Delivery details' ?></h2>
          <p style="margin:0;color:#34373b;font-size:13px;line-height:21px;">
            <strong><?= e($order['customer_name']) ?></strong><br>
            <?= e(order_address_line($order)) ?><br>
            <?= e($order['phone']) ?><br>
            <?php if (!empty($order['notes'])): ?><span style="color:#555b62;">Note: <?= e($order['notes']) ?></span><br><?php endif; ?>
            <span style="color:#555b62;">Payment: Pay on delivery</span>
          </p>
        </td></tr>

        <tr><td style="padding:18px 22px 0;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fafafa;border-left:3px solid #e30613;">
            <tr><td style="padding:12px 13px;color:#45494e;font-size:13px;line-height:20px;">
              <strong style="color:#202124;">We’ll call you shortly</strong><br>
              We’ll confirm your <?= $deliveryMethod === 'pickup' ? 'pickup' : 'delivery' ?> details and help with any questions.
              <br style="line-height:12px;">
              <?php if ($phone !== ''): ?><a href="tel:<?= e($phone) ?>" style="display:inline-block;margin:8px 6px 0 0;padding:8px 12px;background:#e30613;color:#ffffff;text-decoration:none;font-size:12px;font-weight:bold;">Call us</a><?php endif; ?>
              <?php if ($whatsapp): ?><a href="<?= e(whatsapp_url('Hi, I have a question about order ' . $order['order_number'] . '.')) ?>" style="display:inline-block;margin:8px 0 0;padding:8px 12px;background:#128c4a;color:#ffffff;text-decoration:none;font-size:12px;font-weight:bold;">WhatsApp us</a><?php endif; ?>
            </td></tr>
          </table>
        </td></tr>

        <tr><td style="padding:20px 22px 0;">
          <h2 style="margin:0 0 10px;color:#17191c;font-size:16px;line-height:22px;">What happens next</h2>
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:12px;line-height:18px;">
            <tr><td width="28" valign="top" style="width:28px;padding:5px 0;color:#e30613;font-weight:bold;">1.</td><td style="padding:5px 0;color:#4d5156;"><strong style="color:#202124;">Processing your order</strong><br>We’re preparing your items.</td></tr>
            <tr><td width="28" valign="top" style="width:28px;padding:5px 0;color:#e30613;font-weight:bold;">2.</td><td style="padding:5px 0;color:#4d5156;"><strong style="color:#202124;">Shipping soon</strong><br>We’ll contact you with delivery details.</td></tr>
            <tr><td width="28" valign="top" style="width:28px;padding:5px 0;color:#e30613;font-weight:bold;">3.</td><td style="padding:5px 0;color:#4d5156;"><strong style="color:#202124;">Delivery</strong><br>Enjoy your order when it arrives.</td></tr>
          </table>
        </td></tr>

        <?php if ($bannerImageUrl !== ''): ?>
        <tr><td style="padding:18px 22px 0;">
          <?php if ($bannerLink !== ''): ?><a href="<?= e($bannerLink) ?>" target="_blank" rel="noopener" style="display:block;text-decoration:none;"><?php endif; ?>
          <img src="<?= e($bannerImageUrl) ?>" alt="<?= e($store) ?> promotion" width="556" style="display:block;width:100%;max-width:556px;height:auto;border:0;">
          <?php if ($bannerLink !== ''): ?></a><?php endif; ?>
        </td></tr>
        <?php endif; ?>

        <tr><td align="center" style="padding:20px 22px;border-top:1px solid #e8e9eb;">
          <a href="<?= e($url) ?>" style="display:inline-block;padding:11px 18px;background:#e30613;color:#ffffff;text-decoration:none;font-size:13px;font-weight:bold;">View your order details</a>
          <p style="margin:16px 0 4px;color:#202124;font-size:13px;font-weight:bold;"><?= e($store) ?></p>
          <p style="margin:0;color:#656a70;font-size:12px;line-height:19px;">Smart Tech. Better Life.<br>Thank you for choosing us!
          <?php if (setting('store_phone') || $whatsapp): ?><br>Need help?<?php if (setting('store_phone')): ?> Call <?= e(setting('store_phone')) ?><?php endif; ?><?php if ($whatsapp): ?> or WhatsApp us.<?php endif; ?><?php endif; ?></p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
