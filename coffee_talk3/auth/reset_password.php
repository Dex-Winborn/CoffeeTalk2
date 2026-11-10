<?php
require_once __DIR__ . "/../includes/validation.php";
if (is_logged_in()) redirect(is_admin() ? "pages/admin_dashboard.php" : "pages/dashboard.php");

$token = isset($_GET["token"]) && is_string($_GET["token"]) ? $_GET["token"] : (isset($_POST["token"]) && is_string($_POST["token"]) ? $_POST["token"] : "");
$validToken = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$userId = 0;
if ($validToken) {
    $tokenHash = hash("sha256", $token);
    $stmt = db()->prepare("SELECT user_id FROM password_reset_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1");
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) $userId = (int) $row["user_id"];
}

$errors = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_post("auth/forgot_password.php");
    $password = is_string($_POST["password"] ?? null) ? $_POST["password"] : "";
    $confirm = is_string($_POST["confirm_password"] ?? null) ? $_POST["confirm_password"] : "";
    if (!$userId) $errors[] = "This password-reset link is invalid or has expired. Request a new one.";
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) $errors[] = "Password must be at least 8 characters and include a letter and a number.";
    if ($password !== $confirm) $errors[] = "Password and confirmation do not match.";
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $conn = db();
        $conn->begin_transaction();
        try {
            $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $update->bind_param("si", $hash, $userId);
            $update->execute();
            $update->close();
            $delete = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
            $delete->bind_param("i", $userId);
            $delete->execute();
            $delete->close();
            $conn->commit();
            flash_set("success", "Your password has been changed. Please log in with your new password.");
            redirect("auth/login.php");
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log("Password reset completion failed: " . $exception->getMessage());
            $errors[] = "The password could not be changed. Please request a new reset link.";
        }
    }
}
$pageTitle = "Set a new password — Coffee Talk";
$bodyClass = "app-body login-page";
$activePage = "reset-password";
$extraScripts = ["js/validation.js"];
require __DIR__ . "/../includes/header.php";
?>
<main class="app-main"><section class="auth-card">
  <div class="eyebrow">Account recovery</div>
  <h1 class="auth-title">Set a new password</h1>
  <p class="auth-lead">Choose a new password for your Coffee Talk account.</p>
  <?= flash_render() ?>
  <?php if ($errors): ?><div class="alert alert-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <?php if ($userId): ?>
    <form method="post" action="<?= e(url('auth/reset_password.php')) ?>?token=<?= e($token) ?>" data-validate novalidate>
      <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>" />
      <div class="field"><label for="password">New password</label><input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" data-strong-password /><small class="field-hint">At least 8 characters with a letter and a number.</small><span class="field-error" aria-live="polite"></span></div>
      <div class="field"><label for="confirm_password">Confirm new password</label><input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" data-match="password" /><span class="field-error" aria-live="polite"></span></div>
      <button class="button auth-submit" type="submit">Change password</button>
    </form>
  <?php else: ?>
    <p class="auth-lead">This password-reset link is invalid or expired. Request a new one to continue.</p>
    <p class="auth-alt"><a href="<?= e(url('auth/forgot_password.php')) ?>">Request another reset link</a></p>
  <?php endif; ?>
</section></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>
