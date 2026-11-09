<?php
// Wishlist API.
//   GET                                    -> wishlist product ids
//   POST {action: "toggle", product_id}    -> add, or remove if already there
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = api_input();
    if (($input['action'] ?? '') !== 'toggle') {
        json_response(['ok' => false, 'error' => 'Unknown action'], 400);
    }
    try {
        wishlist_toggle((int) ($input['product_id'] ?? 0));
    } catch (InvalidArgumentException $e) {
        json_response(['ok' => false, 'error' => $e->getMessage()], 404);
    }
}

json_response(['ok' => true, 'ids' => wishlist_ids()]);
