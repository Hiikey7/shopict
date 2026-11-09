<?php
// Cart API.
//   GET                                                      -> current cart
//   POST {action: "add", product_id, variant_id?, quantity}  -> add (no-op if that item is already in the cart)
//   POST {action: "update", item_id, quantity}
//   POST {action: "remove", item_id}
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response(cart_state(!empty($_GET["page"])));
}

$input = api_input();
$quantity = (int) ($input['quantity'] ?? 1);

try {
    switch ($input['action'] ?? '') {
        case 'add':
            $variantId = (int) ($input['variant_id'] ?? 0);
            cart_add((int) ($input['product_id'] ?? 0), $quantity, $variantId ?: null);
            break;
        case 'update':
            cart_update((int) ($input['item_id'] ?? 0), $quantity);
            break;
        case 'remove':
            cart_remove((int) ($input['item_id'] ?? 0));
            break;
        default:
            json_response(['ok' => false, 'error' => 'Unknown action'], 400);
    }
} catch (InvalidArgumentException $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 404);
}

json_response(cart_state(!empty($input["page"])));
