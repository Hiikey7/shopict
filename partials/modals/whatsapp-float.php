<?php
/**
 * Floating WhatsApp button (bottom right). Uses the WhatsApp number from Settings,
 * or the shop phone when no WhatsApp number is set. Hidden when neither is set.
 */
$waNumber = whatsapp_number();
if (!$waNumber) {
    return;
}
?>
<a class="wa-float" href="<?= e(whatsapp_url('Hi! I have a question about a product.', $waNumber)) ?>" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
  <?= whatsapp_icon(30) ?>
</a>
