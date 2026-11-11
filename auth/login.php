<?php
// Login page. The username/email and password are checked against MySQL in PHP, never only in
// JavaScript.
require_once __DIR__ . "/../includes/validation.php";
// A user who is already logged in is sent to their dashboard. $error and $identifier hold the message
// and the typed username for redisplay.
guest_only(); $error=""; $identifier="";
// The login form was submitted.
if($_SERVER["REQUEST_METHOD"]==="POST"){
    // Reject the request if the CSRF token is wrong, then read the username/email and the password.
    require_post("auth/login.php"); $identifier=post_string("identifier"); $password=is_string($_POST["password"]??null)?$_POST["password"]:"";
    // Both fields are required.
    if($identifier===""||$password==="") $error="Please enter your username (or email) and password.";
    // Look up the account by username or email with a prepared statement.
    else{$stmt=db()->prepare("SELECT id,full_name,username,password_hash,role FROM users WHERE username=? OR email=? LIMIT 1");$stmt->bind_param("ss",$identifier,$identifier);$stmt->execute();$user=$stmt->get_result()->fetch_assoc();$stmt->close();
        // password_verify compares the typed password with the hash saved in MySQL.
        // On success: a new session id is created (stops session fixation), the user is saved in the
        // session and sent to the right dashboard.
        if($user&&password_verify($password,$user["password_hash"])){session_regenerate_id(true);$_SESSION["user_id"]=(int)$user["id"];$_SESSION["full_name"]=$user["full_name"];$_SESSION["username"]=$user["username"];$_SESSION["role"]=$user["role"];flash_set("success","Welcome back, ".$user["full_name"] ."!");redirect($user["role"]==="admin"?"pages/admin_dashboard.php":"pages/dashboard.php");}
        // The same message is used for a wrong username and a wrong password, so nobody can tell which
        // accounts exist.
        $error="Invalid username/email or password.";
    }
}
// Page settings, then the shared header. Everything below is the login form.
$pageTitle="Log in — Coffee Talk";$bodyClass="app-body login-page";$activePage="login";$extraScripts=["js/validation.js"];require __DIR__."/../includes/header.php";
?>
<main class="app-main"><section class="auth-card"><div class="eyebrow">Welcome back</div><h1 class="auth-title">Log in</h1><p class="auth-lead">Sign in to manage the menu and orders.</p>
<?= flash_render() ?><?php if($error!==""): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php /*
   Login form. data-validate turns on the checks in js/validation.js; novalidate stops the browser's
   own pop-ups.
*/ ?>
<form method="post" action="<?= e(url('auth/login.php')) ?>" data-validate novalidate><?= csrf_field() ?>
<div class="field"><label for="identifier">Username or email</label><input type="text" id="identifier" name="identifier" required maxlength="120" autocomplete="username" value="<?= e($identifier) ?>" /><span class="field-error" aria-live="polite"></span></div>
<div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required autocomplete="current-password" /><span class="field-error" aria-live="polite"></span></div>
<button class="button auth-submit" type="submit">Log in</button></form><p class="auth-alt"><a href="<?= e(url('auth/forgot_password.php')) ?>">Forgot your password?</a></p><p class="auth-alt">New here? <a href="<?= e(url('auth/register.php')) ?>">Create a staff account</a></p></section></main>
<?php require __DIR__."/../includes/footer.php"; ?>