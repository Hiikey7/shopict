<?php
declare(strict_types=1);

// Checkout, orders, order emails and WhatsApp ordering.

const ORDER_STATUSES = [
    'new'        => 'New',
    'confirmed'  => 'Confirmed',
    'processing' => 'Processing',
    'shipped'    => 'Out for delivery',
    'delivered'  => 'Delivered',
    'cancelled'  => 'Cancelled',
];

// ---------------------------------------------------------------- WhatsApp

/**
 * The shop's WhatsApp number as digits in international format (e.g. 254712345678), or null.
 * Uses the WhatsApp number from Settings, or the shop phone when that is empty (07… / 01… become 2547… / 2541…).
 */
function whatsapp_number(): ?string
{
    foreach (['whatsapp_number', 'store_phone'] as $key) {
        $digits = preg_replace('/\D+/', '', (string) setting($key, ''));
        if (strlen($digits) === 10 && $digits[0] === '0') {
            $digits = '254' . substr($digits, 1); // Kenyan local format
        }
        if (strlen($digits) >= 8) {
            return $digits;
        }
    }
    return null;
}

/** wa.me link that opens a chat with the shop (or $number) with $text typed in. */
function whatsapp_url(string $text, ?string $number = null): string
{
    $number ??= whatsapp_number();
    return 'https://wa.me/' . preg_replace('/\D+/', '', (string) $number) . '?text=' . rawurlencode($text);
}

function whatsapp_icon(int $size = 20): string
{
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2Zm0 18.15c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.21 8.21 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24 4.54 0 8.24 3.7 8.24 8.24 0 4.55-3.7 8.24-8.24 8.24Zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.42.08-.17.04-.31-.02-.43-.06-.13-.56-1.35-.77-1.85-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.24 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.1-.23-.16-.48-.29Z"/></svg>';
}

/** Order summary text for WhatsApp, from cart rows or order items. */
function whatsapp_order_text(array $lines, float $total, ?array $order = null): string
{
    $store = setting('store_name', 'Shop ICT Gadgets');
    $out = [$order
        ? 'Hi ' . $store . '! I would like to confirm my order *' . $order['order_number'] . '*:'
        : 'Hi! I would like to order:'];
    $out[] = '';
    foreach ($lines as $l) {
        $label = $l['variant_label'] ?? '';
        $out[] = '• ' . $l['title'] . ($label !== '' ? ' (' . $label . ')' : '') . (!empty($l['is_preorder']) ? ' [PRE-ORDER]' : '') . ' × ' . (int) $l['quantity'] . ' — ' . money($l['price'] * $l['quantity']);
    }
    $out[] = '';
    if ($order) {
        $method = $order['delivery_method'] ?? 'delivery';
        $out[] = 'Subtotal: ' . money($order['subtotal']);
        if ((float) ($order['discount'] ?? 0) > 0) {
            $out[] = 'Promo code ' . $order['promo_code'] . ': -' . money($order['discount']);
        }
        $out[] = 'Delivery: ' . delivery_cost_text($method, (float) $order['delivery_fee']);
    }
    $out[] = '*Total: ' . money($total) . '*';
    if ($order) {
        $out[] = '';
        $out[] = 'Name: ' . $order['customer_name'];
        $out[] = 'Phone: ' . $order['phone'];
        $out[] = 'Email: ' . $order['email'];
        $out[] = ($method === 'pickup' ? 'Pickup: ' : 'Deliver to: ') . order_address_line($order);
        if (!empty($order['notes'])) {
            $out[] = 'Notes: ' . $order['notes'];
        }
        $out[] = '';
        $out[] = 'Order details: ' . order_url($order, true);
    }
    return implode("\n", $out);
}

// ---------------------------------------------------------------- delivery

/** Delivery charge for an order: pickup at the shop is free; delivery uses the fee in Settings (0 = confirmed by phone). */
function delivery_fee_for(float $subtotal, string $method = 'delivery'): float
{
    return $method === 'pickup' ? 0.0 : max(0.0, (float) setting('delivery_fee', '0'));
}

/** There is no free delivery, so no "spend X more" progress bar. Kept so older callers still work. */
function free_delivery_progress(float $subtotal): ?array
{
    return null;
}

/** The two ways to get an order: [key => [title, detail]]. */
function delivery_methods(): array
{
    $addr = (string) setting('store_address', '');
    return [
        'pickup'   => ['Free pickup at our shop', $addr],
        'delivery' => ['Countrywide delivery', 'Across Kenya'],
    ];
}

/** What the Delivery line of an order shows. */
function delivery_cost_text(string $method, float $fee): string
{
    if ($method === 'pickup') {
        return 'Free (pickup)';
    }
    return $fee > 0 ? money($fee) : 'Confirmed by phone';
}

// ---------------------------------------------------------------- orders

function order_address_line(array $o): string
{
    if (($o['delivery_method'] ?? 'delivery') === 'pickup') {
        return 'Pickup at our shop' . (($addr = setting('store_address')) ? ' · ' . $addr : '');
    }
    return implode(', ', array_filter([$o['address'] ?? null, $o['city'] ?? null, $o['region'] ?? null, $o['postal_code'] ?? null, $o['country'] ?? null]));
}

function find_order(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function order_items(int $orderId): array
{
    $stmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

/** Customer's link to their order page. */
function order_url(array $o, bool $absolute = false): string
{
    $path = 'order.php?n=' . rawurlencode($o['order_number']) . '&t=' . $o['access_token'];
    if (!$absolute) {
        return $path;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/' . $path;
}

/** Checks and normalises checkout form input. Returns [data, errors]. */
function validate_checkout(array $in): array
{
    $v = fn (string $k, int $max) => mb_substr(trim((string) ($in[$k] ?? '')), 0, $max);
    $data = [
        'customer_name' => $v('customer_name', 120),
        'email'         => mb_strtolower($v('email', 255)),
        'phone'         => $v('phone', 30),
        'city'            => $v('city', 100),
        'country'         => $v('country', 60) ?: 'Kenya',
        'notes'           => $v('notes', 1000),
        'delivery_method' => ($in['delivery_method'] ?? '') === 'pickup' ? 'pickup' : 'delivery',
    ];
    $errors = [];
    if ($data['customer_name'] === '') {
        $errors['customer_name'] = 'Enter your full name.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address — your confirmation is sent there.';
    }
    if (strlen(preg_replace('/\D+/', '', $data['phone'])) < 7) {
        $errors['phone'] = 'Enter a phone number we can call for delivery.';
    }
    if ($data['city'] === '' && $data['delivery_method'] === 'delivery') {
        $errors['city'] = 'Enter the town or city we should deliver to.';
    }
    return [$data, $errors];
}

/**
 * Turns the visitor's cart into an order: checks stock, saves the order and its lines,
 * updates stock and sold counts, and empties the cart. Returns the saved order.
 *
 * @throws RuntimeException when the cart is empty or an item is no longer available
 */
function place_order(array $customer, string $channel, ?string $promoCode = null): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $items = cart_items();
        if (!$items) {
            throw new RuntimeException('Your cart is empty.');
        }
        foreach ($items as $it) {
            if ($it['variant_id']) {
                $stmt = $pdo->prepare('SELECT stock FROM product_variants WHERE id = ? FOR UPDATE');
                $stmt->execute([$it['variant_id']]);
                $stock = $stmt->fetchColumn();
                if ($stock !== null && $stock !== false && (int) $stock < (int) $it['quantity']) {
                    throw new RuntimeException('Only ' . (int) $stock . ' left of "' . $it['title'] . ($it['variant_label'] ? ' (' . $it['variant_label'] . ')' : '') . '". Please change the quantity.');
                }
            }
        }
        $subtotal = round(cart_subtotal($items), 2);
        $fee = round(delivery_fee_for($subtotal, $customer['delivery_method'] ?? 'delivery'), 2);
        $discount = 0.0;
        if ($promoCode !== null && $promoCode !== '') {
            [$promo, $discount, $why] = promo_code_check($promoCode, $items);
            if (!$promo) {
                throw new RuntimeException($why . ' Remove it to continue.');
            }
            $promoCode = $promo['code'];
        } else {
            $promoCode = null;
        }
        $order = $customer + [
            'access_token'   => bin2hex(random_bytes(16)),
            'channel'        => $channel === 'whatsapp' ? 'whatsapp' : 'web',
            'payment_method' => 'pay_on_delivery',
            'subtotal'       => $subtotal,
            'delivery_fee'   => $fee,
            'promo_code'     => $promoCode,
            'promotion_id'   => $promoCode !== null ? (int) $promo['id'] : null,
            'discount'       => $discount,
            'total'          => max(0, $subtotal - $discount) + $fee,
            'visitor_id'     => visitor_id(),
            'created_at'     => date('Y-m-d H:i:s'),
        ];
        foreach (['city', 'notes'] as $k) {
            $order[$k] = ($order[$k] ?? '') === '' ? null : $order[$k];
        }
        $cols = array_keys($order);
        $pdo->prepare('INSERT INTO orders (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')
            ->execute(array_values($order));
        $order['id'] = (int) $pdo->lastInsertId();
        $order['order_number'] = 'SICT-' . (1000 + $order['id']);
        $order['status'] = 'new';
        $pdo->prepare('UPDATE orders SET order_number = ? WHERE id = ?')->execute([$order['order_number'], $order['id']]);

        $line = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, variant_id, title, variant_label, sku, image, price, quantity, line_total, is_preorder, promotion_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $lessStock = $pdo->prepare('UPDATE product_variants SET stock = GREATEST(stock - ?, 0) WHERE id = ? AND stock IS NOT NULL');
        $sold = $pdo->prepare('UPDATE products SET sold = COALESCE(sold, 0) + ?, available = CASE WHEN available IS NULL THEN NULL ELSE GREATEST(CAST(available AS SIGNED) - ?, 0) END WHERE id = ?');
        foreach ($items as $it) {
            $variant = $it['variant_id'] ? find_variant((int) $it['id'], (int) $it['variant_id']) : null;
            $qty = (int) $it['quantity'];
            $price = round((float) $it['price'], 2);
            $line->execute([
                $order['id'], $it['id'], $it['variant_id'] ?: null, $it['title'], $it['variant_label'] ?: null,
                ($variant['sku'] ?? null) ?: $it['sku'], $it['img_src'], $price, $qty, round($price * $qty, 2),
                empty($it['is_preorder']) ? 0 : 1,
                !empty($it['promotion']['id']) ? (int) $it['promotion']['id'] : null,
            ]);
            if ($it['variant_id']) {
                $lessStock->execute([$qty, $it['variant_id']]);
            }
            $sold->execute([$qty, $qty, $it['id']]);
        }
        $pdo->prepare('DELETE FROM cart_items WHERE visitor_id = ?')->execute([visitor_id()]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    track_event('order_placed');
    return $order;
}

// ---------------------------------------------------------------- emails

/** Sends the customer's confirmation (and a copy to the shop, if set). Records when it was sent. */
function send_order_emails(array $order): bool
{
    $items = order_items((int) $order['id']);
    $store = setting('store_name', 'Shop ICT Gadgets') ?: 'Shop ICT Gadgets';
    $banner = trim((string) setting('order_email_banner', ''));
    $bannerImageUrl = $banner !== '' ? seo_abs(asset($banner)) : '';
    $bannerLink = trim((string) setting('order_email_banner_url', ''));
    $bannerLinkParts = $bannerLink !== '' ? parse_url($bannerLink) : false;
    if ($bannerLink !== '' && (!filter_var($bannerLink, FILTER_VALIDATE_URL)
        || !in_array(strtolower((string) ($bannerLinkParts['scheme'] ?? '')), ['http', 'https'], true))) {
        $bannerLink = '';
    }
    $html = render('emails/order-confirmation', [
        'order' => $order,
        'items' => $items,
        'store' => $store,
        'url' => order_url($order, true),
        'bannerImageUrl' => $bannerImageUrl,
        'bannerLink' => $bannerLink,
    ]);
    $text = order_email_text($order, $items, $store, $bannerLink, order_url($order, true));
    $notify = trim((string) setting('order_notify_email', ''));

    $ok = send_mail($order['email'], "Order {$order['order_number']} confirmed — $store", $html, $text, $notify !== '' ? $notify : null);
    if ($ok) {
        db()->prepare('UPDATE orders SET email_sent_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $order['id']]);
    }
    if ($notify !== '' && filter_var($notify, FILTER_VALIDATE_EMAIL)) {
        send_mail($notify, "New order {$order['order_number']} — " . money($order['total']), $html, "New order received.\n\n" . $text, $order['email']);
    }
    return $ok;
}

function order_email_text(array $order, array $items, string $store, string $bannerLink = '', string $orderUrl = ''): string
{
    $lines = ["ORDER CONFIRMED — Thank you for shopping with $store, {$order['customer_name']}!", '', "Order: {$order['order_number']}", ''];
    foreach ($items as $it) {
        $lines[] = '- ' . $it['title'] . ($it['variant_label'] ? " ({$it['variant_label']})" : '') . (!empty($it['is_preorder']) ? ' [PRE-ORDER]' : '') . " x{$it['quantity']}  " . money($it['line_total']);
    }
    $lines[] = '';
    $lines[] = 'Subtotal: ' . money($order['subtotal']);
    if ((float) ($order['discount'] ?? 0) > 0) {
        $lines[] = 'Promo code ' . $order['promo_code'] . ': -' . money($order['discount']);
    }
    $lines[] = 'Delivery: ' . delivery_cost_text($order['delivery_method'] ?? 'delivery', (float) $order['delivery_fee']);
    $lines[] = 'Total: ' . money($order['total']) . ' (pay on delivery)';
    $lines[] = '';
    $lines[] = (($order['delivery_method'] ?? 'delivery') === 'pickup' ? 'Pickup: ' : 'Deliver to: ') . order_address_line($order);
    $lines[] = 'Phone: ' . $order['phone'];
    if ($orderUrl !== '') {
        $lines[] = 'View your order: ' . $orderUrl;
    }
    $lines[] = '';
    $lines[] = 'We will call you to arrange delivery. — ' . $store;
    if ($bannerLink !== '') {
        $lines[] = '';
        $lines[] = 'Discover more: ' . $bannerLink;
    }
    return implode("\n", $lines);
}
