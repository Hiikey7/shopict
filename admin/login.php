<?php
require __DIR__ . '/../includes/admin.php';

if (current_admin()) {
    redirect('index.php');
}

$error = null;
$email = '';
if (is_post()) {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (request_rate_limited('admin-login', 10, 900)) {
        http_response_code(429);
        header('Retry-After: 900');
        $error = 'Too many sign-in attempts. Please wait 15 minutes before trying again.';
    } else {
        $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? AND is_admin = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            unset($_SESSION['csrf']);
            $_SESSION['admin_id'] = (int) $user['id'];
            $_SESSION['admin_last_activity'] = time();
            redirect('index.php');
        }
        // Slow down password guessing a little.
        usleep(400000);
        $error = 'Incorrect email or password.';
    }
}

admin_header('Log in');
?>
<div class="auth-card">
  <div class="admin-brand"><img src="<?= e(admin_asset(store_logo())) ?>" alt="<?= e(setting('store_name', 'Shop ICT Gadgets')) ?>" height="44" style="width:auto"></div>
  <div class="card">
    <div class="card-body p-4">
      <h2 class="h5 mb-3">Sign in to the dashboard</h2>
      <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Log in</button>
      </form>
    </div>
  </div>
  <p class="text-center mt-3 small"><a href="../">← Back to the store</a></p>
</div>
<?php admin_footer(); ?>
