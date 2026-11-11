<?php
// Step 1 of password recovery: the user types their email and receives a reset link.
require_once __DIR__ . "/../includes/validation.php";
// A user who is logged in does not need this page.
if (is_logged_in()) redirect(is_admin() ? "pages/admin_dashboard.php" : "pages/dashboard.php");

// $sent switches the page to the confirmation message. $localResetUrl is only filled in on localhost
// when email is not set up.
$sent = false;
$localResetUrl = "";
// The form was submitted.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_post("auth/forgot_password.php");
    $email = post_string("email");
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
        $error = "Enter a valid email address.";
    } else {
        // Always show the same confirmation, whether or not an account exists.
        $stmt = db()->prepare("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        // Account found: create a random token.
        if ($account) {
            // The token goes in the emailed link. Only its SHA-256 hash is saved in MySQL, so a leaked
            // database cannot be used to reset passwords.
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash("sha256", $token);
            $userId = (int) $account["id"];
            $conn = db();
            // Delete the user's older tokens and save the new one (valid for 1 hour) in one
            // transaction.
            $conn->begin_transaction();
            try {
                $delete = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
                $delete->bind_param("i", $userId);
                $delete->execute();
                $delete->close();
                $insert = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
                $insert->bind_param("is", $userId, $tokenHash);
                $insert->execute();
                $insert->close();
                $conn->commit();

                // Build the link and try to email it. XAMPP usually has no mail server, so mail() may
                // fail.
                $resetUrl = app_base_url() . "/auth/reset_password.php?token=" . rawurlencode($token);
                $subject = "Coffee Talk password reset";
                $message = "Hello " . $account["full_name"] . ",\n\nUse this link within one hour to set a new Coffee Talk password:\n" . $resetUrl . "\n\nIf you did not request this, you can ignore this email.";
                $headers = "From: Coffee Talk <no-reply@localhost>\r\n";
                $mailSent = @mail($account["email"], $subject, $message, $headers);
                // If the email could not be sent and the request comes from the same computer, show
                // the link on the page so the feature can still be demonstrated.
                $baseHost = strtolower((string) parse_url(app_base_url(), PHP_URL_HOST));
                $remoteHost = strtolower((string) ($_SERVER["REMOTE_ADDR"] ?? ""));
                $isLocalRequest = in_array($baseHost, ["localhost", "127.0.0.1", "::1"], true)
                  && in_array($remoteHost, ["localhost", "127.0.0.1", "::1"], true);
                if (!$mailSent && $isLocalRequest) $localResetUrl = $resetUrl;
            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                error_log("Password reset request failed: " . $exception->getMessage());
            }
        }
        $sent = true;
    }
}
// Page settings, then the shared header.
$pageTitle = "Forgot password — Coffee Talk";
$bodyClass = "app-body login-page";
$activePage = "forgot-password";
$extraScripts = ["js/validation.js"];
require __DIR__ . "/../includes/header.php";
?>
<main class="app-main"><section class="auth-card">
  <div class="eyebrow">Account recovery</div>
  <h1 class="auth-title">Forgot password?</h1>
  <?php if ($sent): ?>
    <p class="auth-lead">If an account matches that email, a secure reset link is available for one hour. Check your inbox, or use the local link below when running XAMPP without email configured.</p>
    <?php if ($localResetUrl !== ""): ?>
      <div class="alert alert-success" role="status">Local XAMPP email is not configured. Use this local-development reset link:<br /><a class="reset-link" href="<?= e($localResetUrl) ?>">Set a new password</a></div>
    <?php endif; ?>
    <p class="auth-alt"><a href="<?= e(url('auth/login.php')) ?>">Return to log in</a></p>
  <?php else: ?>
    <p class="auth-lead">Enter the email address on your account. We’ll send you a secure link to choose a new password.</p>
    <?= flash_render() ?>
    <?php if (!empty($error)): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('auth/forgot_password.php')) ?>" data-validate novalidate>
      <?= csrf_field() ?>
      <div class="field"><label for="email">Account email</label><input type="email" id="email" name="email" required maxlength="120" autocomplete="email" /><span class="field-error" aria-live="polite"></span></div>
      <button class="button auth-submit" type="submit">Send reset link</button>
    </form>
    <p class="auth-alt"><a href="<?= e(url('auth/login.php')) ?>">Back to log in</a></p>
  <?php endif; ?>
</section></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>
