<?php
/**
 * Responsive order confirmation email, using table layout and inline styles for email clients.
 *
 * @var array  $order
 * @var array  $items
 * @var string $store
 * @var string $url
 * @var string $bannerImageUrl
 * @var string $bannerLink
 */
$money = fn ($v) => money($v);
$deliveryMethod = (string) ($order['delivery_method'] ?? 'delivery');
$phone = preg_replace('/[^0-9+]/', '', (string) ($order['phone'] ?? ''));
$whatsapp = whatsapp_number();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <title>Order <?= e($order['order_number']) ?> confirmed</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#20242a;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f4f6;padding:20px 10px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;background:#fff;border:1px solid #e6e7e9;">
        <tr><td style="height:3px;background:#e30613;font-size:0;line-height:0;">&nbsp;</td></tr>
        <tr><td align="center" style="padding:22px 24px 12px;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0">
            <tr><td align="center" style="width:60px;height:60px;border-radius:50%;background:#e30613;color:#fff;font-size:38px;line-height:60px;font-weight:bold;">&#10003;</td></tr>
          </table>
          <h1 style="margin:12px 0 2px;font-size:34px;line-height:1.1;letter-spacing:-1px;color:#111;">Order <span style="color:#e30613;">Confirmed!</span></h1>
          <p style="margin:0;font-size:16px;line-height:23px;font-weight:bold;color:#222;">Thank you for shopping with <?= e($store) ?>.</p>
          <p style="margin:4px 0 0;font-size:14px;line-height:20px;color:#40444a;">Your order has been successfully received and is now being processed.<br>We appreciate your trust and can’t wait to serve you.</p>
        </td></tr>

        <tr><td style="padding:0 12px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e1e2e4;border-radius:8px;overflow:hidden;">
            <tr>
              <td style="padding:10px 14px;background:#e30613;color:#fff;font-size:15px;font-weight:bold;">&#128722;&nbsp; Order Details</td>
              <td align="right" style="padding:10px 14px;background:#f4f4f5;color:#333;font-size:12px;white-space:nowrap;">Order No: <strong style="color:#e30613;"><?= e($order['order_number']) ?></strong></td>
            </tr>
            <?php foreach ($items as $it): ?>
            <?php
              $itemImage = trim((string) ($it['image'] ?? ''));
              $itemImageUrl = $itemImage !== '' ? seo_abs(asset($itemImage)) : '';
            ?>
            <tr>
              <?php if ($itemImageUrl !== ''): ?>
              <td valign="middle" style="width:92px;padding:10px 8px 10px 12px;border-bottom:1px solid #ececee;">
                <img src="<?= e($itemImageUrl) ?>" width="82" height="72" alt="<?= e($it['title']) ?>" style="display:block;width:82px;height:72px;object-fit:contain;">
              </td>
              <?php endif; ?>
              <td valign="middle" style="padding:10px 8px;border-bottom:1px solid #ececee;font-size:13px;line-height:18px;">
                <strong style="font-size:14px;color:#16181b;"><?= e($it['title']) ?></strong>
                <?php if (!empty($it['variant_label'])): ?><br><span style="color:#555b62;"><?= e($it['variant_label']) ?></span><?php endif; ?>
                <?php if (!empty($it['is_preorder'])): ?><br><span style="color:#8a5a00;font-weight:bold;">Pre-order</span><?php endif; ?>
                <br><strong>Qty: <?= (int) $it['quantity'] ?></strong>
              </td>
              <td align="right" valign="middle" style="width:116px;padding:10px 14px 10px 8px;border-bottom:1px solid #ececee;font-size:12px;line-height:19px;white-space:nowrap;">
                Item Total<br><strong style="font-size:15px;color:#17191c;"><?= $money($it['line_total']) ?></strong>
              </td>
            </tr>
            <?php endforeach; ?>
            <tr><td colspan="3" style="padding:10px 14px 4px;font-size:13px;color:#555b62;">Subtotal <span style="float:right;color:#20242a;"><?= $money($order['subtotal']) ?></span></td></tr>
            <?php if ((float) ($order['discount'] ?? 0) > 0): ?>
            <tr><td colspan="3" style="padding:4px 14px;font-size:13px;color:#555b62;">Discount<?php if (!empty($order['promo_code'])): ?> (<?= e($order['promo_code']) ?>)<?php endif; ?><span style="float:right;color:#16803c;">−<?= $money($order['discount']) ?></span></td></tr>
            <?php endif; ?>
            <tr><td colspan="3" style="padding:4px 14px;font-size:13px;color:#555b62;">Delivery <span style="float:right;color:#20242a;"><?= e(delivery_cost_text($deliveryMethod, (float) $order['delivery_fee'])) ?></span></td></tr>
            <tr><td colspan="3" style="padding:8px 14px 12px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr><td style="padding:8px 12px;border-radius:8px;background:#fde4e6;text-align:right;font-size:12px;color:#333;">Total to pay<br><strong style="font-size:19px;color:#e30613;"><?= $money($order['total']) ?></strong></td></tr>
              </table>
            </td></tr>
          </table>
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:10px;background:#f7f8fa;border:1px solid #e8e9eb;border-radius:8px;font-size:13px;line-height:19px;">
            <tr><td style="padding:12px 14px;">
              <strong><?= $deliveryMethod === 'pickup' ? 'Pickup details' : 'Delivery address' ?></strong><br>
              <?= e($order['customer_name']) ?><br>
              <?= e(order_address_line($order)) ?><br>
              <?= e($order['phone']) ?>
              <?php if (!empty($order['notes'])): ?><br><span style="color:#555b62;">Note: <?= e($order['notes']) ?></span><?php endif; ?>
              <br><span style="color:#555b62;">Payment: Pay on delivery</span>
            </td></tr>
          </table>
        </td></tr>

        <tr><td style="padding:14px 12px 0;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #ff4952;border-radius:8px;">
            <tr>
              <td valign="middle" style="padding:12px;font-size:13px;line-height:19px;">
                <strong>We’ll call you shortly</strong><br>
                We’ll confirm your <?= $deliveryMethod === 'pickup' ? 'pickup' : 'delivery' ?> details and help with any questions.
              </td>
              <td align="right" valign="middle" style="padding:10px 12px;white-space:nowrap;">
                <?php if ($phone !== ''): ?><a href="tel:<?= e($phone) ?>" style="display:inline-block;margin:2px;padding:9px 12px;border-radius:6px;background:#e30613;color:#fff;text-decoration:none;font-size:12px;font-weight:bold;">Call Us</a><?php endif; ?>
                <?php if ($whatsapp): ?><a href="<?= e(whatsapp_url('Hi, I have a question about order ' . $order['order_number'] . '.')) ?>" style="display:inline-block;margin:2px;padding:9px 12px;border-radius:6px;background:#13a85a;color:#fff;text-decoration:none;font-size:12px;font-weight:bold;">WhatsApp Us</a><?php endif; ?>
              </td>
            </tr>
          </table>
        </td></tr>

        <tr><td style="padding:18px 16px 16px;">
          <h2 style="margin:0 0 12px;font-size:18px;line-height:22px;color:#17191c;">What Happens Next?</h2>
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td width="33%" align="center" valign="top" style="padding:0 5px;">
                <span style="display:inline-block;width:22px;height:22px;border-radius:50%;background:#e30613;color:#fff;font-size:13px;line-height:22px;font-weight:bold;">1</span>
                <p style="margin:6px 0 2px;font-size:12px;font-weight:bold;">Processing Your Order</p>
                <p style="margin:0;font-size:11px;line-height:15px;color:#555b62;">We’re preparing your items.</p>
              </td>
              <td width="33%" align="center" valign="top" style="padding:0 5px;">
                <span style="display:inline-block;width:22px;height:22px;border-radius:50%;background:#e30613;color:#fff;font-size:13px;line-height:22px;font-weight:bold;">2</span>
                <p style="margin:6px 0 2px;font-size:12px;font-weight:bold;">Shipping Soon</p>
                <p style="margin:0;font-size:11px;line-height:15px;color:#555b62;">We’ll contact you with delivery details.</p>
              </td>
              <td width="33%" align="center" valign="top" style="padding:0 5px;">
                <span style="display:inline-block;width:22px;height:22px;border-radius:50%;background:#e30613;color:#fff;font-size:13px;line-height:22px;font-weight:bold;">3</span>
                <p style="margin:6px 0 2px;font-size:12px;font-weight:bold;">Delivery</p>
                <p style="margin:0;font-size:11px;line-height:15px;color:#555b62;">Enjoy your order when it arrives.</p>
              </td>
            </tr>
          </table>
        </td></tr>

        <?php if ($bannerImageUrl !== ''): ?>
        <tr><td style="padding:0 12px 16px;">
          <?php if ($bannerLink !== ''): ?><a href="<?= e($bannerLink) ?>" target="_blank" rel="noopener" style="display:block;text-decoration:none;"><?php endif; ?>
          <img src="<?= e($bannerImageUrl) ?>" alt="<?= e($store) ?> promotion" width="596" style="display:block;width:100%;max-width:596px;height:auto;border:0;">
          <?php if ($bannerLink !== ''): ?></a><?php endif; ?>
        </td></tr>
        <?php endif; ?>

        <tr><td align="center" style="padding:12px 16px 20px;border-top:1px solid #e4e5e7;font-size:12px;line-height:19px;color:#555b62;">
          <p style="margin:0 0 6px;"><a href="<?= e($url) ?>" style="color:#e30613;font-weight:bold;text-decoration:none;">View your order details</a></p>
          <strong style="color:#20242a;"><?= e($store) ?></strong>&nbsp; | &nbsp;Smart Tech. Better Life.<br>
          Thank you for choosing us!
          <?php if (setting('store_phone') || $whatsapp): ?><br>Need help?<?php if (setting('store_phone')): ?> Call <?= e(setting('store_phone')) ?><?php endif; ?><?php if ($whatsapp): ?> or WhatsApp us.<?php endif; ?><?php endif; ?>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
