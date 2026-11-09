<?php
$siteName = setting('store_name', 'Shop ICT Gadgets');
$phone = preg_replace('/[^\d+]/', '', (string) setting('store_phone', ''));
$whatsapp = whatsapp_number();
$callNumber = $phone !== '' ? $phone : (string) $whatsapp;
$logo = store_logo();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#111827">
  <title>Under maintenance · <?= e($siteName) ?></title>
  <style>
    :root{color-scheme:light;font-family:Inter,"Segoe UI",Arial,sans-serif;background:#f3f4f6;color:#111827}
    *{box-sizing:border-box}
    body{min-height:100vh;margin:0;display:grid;place-items:center;padding:24px;background:radial-gradient(ellipse at 50% 0,#e5e7eb 0,transparent 58%),#f3f4f6}
    main{width:min(100%,620px);padding:clamp(28px,7vw,64px);border:1px solid #e5e7eb;border-radius:28px;background:#fff;box-shadow:0 24px 70px #11182712;text-align:center}
    .brand{display:inline-flex;align-items:center;justify-content:center;min-height:38px;margin-bottom:42px}
    .brand img{display:block;max-width:190px;max-height:48px;object-fit:contain}
    .mark{display:block;width:min(100%,360px);height:auto;margin:0 auto 26px}
    .mark img{display:block;width:100%;height:auto}
    .eyebrow{margin:0 0 12px;color:#e30613;font-size:12px;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
    h1{margin:0;font-size:clamp(34px,7vw,52px);line-height:1.08;letter-spacing:-.045em}
    .message{max-width:420px;margin:18px auto 30px;color:#667085;font-size:16px;line-height:1.7}
    .actions{display:flex;flex-wrap:wrap;justify-content:center;gap:12px}
    .button{display:inline-flex;min-height:50px;align-items:center;justify-content:center;gap:10px;padding:0 20px;border:1px solid #e30613;border-radius:12px;background:#e30613;color:#fff;text-decoration:none;font-size:14px;font-weight:700;transition:transform .15s,background .15s}
    .button:hover{transform:translateY(-2px);background:#c9000c;color:#fff}
    .button.secondary{border-color:#e5e7eb;background:#fff;color:#111827}
    .button.secondary:hover{background:#f9fafb}
    .button svg{width:19px;height:19px;flex:none}
    .foot{margin:34px 0 0;color:#98a2b3;font-size:12px}
    @media(max-width:420px){main{border-radius:20px}.actions{flex-direction:column}.button{width:100%}}
  </style>
</head>
<body>
  <main>
    <div class="brand">
      <img src="<?= e(asset($logo)) ?>" alt="<?= e($siteName) ?>">
    </div>
    <div class="mark"><img src="<?= e(asset('images/Under.png')) ?>" alt="Under maintenance"></div>
    <p class="eyebrow">We'll be right back</p>
    <h1>We're making things better.</h1>
    <p class="message"><?= e($siteName) ?> is temporarily under maintenance. We're working behind the scenes and will be back shortly.</p>
    <div class="actions">
      <?php if ($callNumber !== ''): ?>
      <a class="button" href="tel:<?= e($callNumber) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/></svg>
        Call us
      </a>
      <?php endif; ?>
      <?php if ($whatsapp): ?>
      <a class="button secondary" href="<?= e(whatsapp_url('Hi! I have a question for the team.', $whatsapp)) ?>" target="_blank" rel="noopener">
        <?= whatsapp_icon(19) ?>
        Chat on WhatsApp
      </a>
      <?php endif; ?>
    </div>
    <p class="foot">Thank you for your patience.</p>
  </main>
</body>
</html>
