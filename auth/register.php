<?php
// Registration page. It validates the form, rejects duplicate usernames and emails, and saves the new
// account with a hashed password.
require_once __DIR__ . "/../includes/validation.php"; guest_only(); $errors=[]; $values=["full_name"=>"","username"=>"","email"=>""];
if($_SERVER["REQUEST_METHOD"]==="POST"){
    // Form submitted: check the CSRF token, then validate the fields on the server
    // (validate_registration in includes/validation.php).
    require_post("auth/register.php"); [$errors,$clean]=validate_registration(); $values=["full_name"=>$clean["full_name"],"username"=>$clean["username"],"email"=>$clean["email"]]; $conn=db();
    // Check whether the username or email is already used (case-insensitive).
    if(!$errors){$stmt=$conn->prepare("SELECT username,email FROM users WHERE username=? OR email=?");$stmt->bind_param("ss",$clean["username"],$clean["email"]);$stmt->execute();$result=$stmt->get_result();while($row=$result->fetch_assoc()){if(strcasecmp($row["username"],$clean["username"])===0)$errors[]="That username is already taken.";if(strcasecmp($row["email"],$clean["email"])===0)$errors[]="That email address is already registered.";}$stmt->close();}
    // Everything is valid: hash the password with password_hash (plain text is never stored) and
    // insert the user.
    // Error 1062 means a duplicate key, which can still happen if two people register at the same
    // moment.
    if(!$errors){$hash=password_hash($clean["password"],PASSWORD_DEFAULT);try{$stmt=$conn->prepare("INSERT INTO users(full_name,username,email,password_hash) VALUES(?,?,?,?)");$stmt->bind_param("ssss",$clean["full_name"],$clean["username"],$clean["email"],$hash);$stmt->execute();$stmt->close();flash_set("success","Account created! You can now log in as ".$clean["username"].".");redirect("auth/login.php");}catch(mysqli_sql_exception $ex){$errors[]=$ex->getCode()===1062?"That username or email address is already registered.":"Registration failed. Please try again.";}}
}
// Page settings, then the shared header. Below is the registration form. The values typed before an
// error are put back in the fields (the password is not).
$pageTitle="Create an account — Coffee Talk";$activePage="register";$extraScripts=["js/validation.js"];require __DIR__."/../includes/header.php";
?>
<main class="app-main"><section class="auth-card"><div class="eyebrow">Join the team</div><h1 class="auth-title">Create an account</h1><p class="auth-lead">Register to manage the Coffee Talk menu and take orders.</p>
<?= flash_render() ?><?php if($errors): ?><div class="alert alert-error" role="alert"><strong>Registration failed:</strong><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php /* Registration form with browser-side checks (js/validation.js). The server repeats every check. */ ?>
<form method="post" action="<?= e(url('auth/register.php')) ?>" data-validate novalidate><?= csrf_field() ?>
<div class="field"><label for="full_name">Full name</label><input type="text" id="full_name" name="full_name" required minlength="2" maxlength="100" autocomplete="name" value="<?= e($values['full_name']) ?>" /><span class="field-error" aria-live="polite"></span></div>
<div class="field"><label for="username">Username</label><input type="text" id="username" name="username" required minlength="4" maxlength="30" pattern="[A-Za-z0-9_]{4,30}" autocomplete="username" data-pattern-message="Use 4–30 letters, numbers, or underscores." value="<?= e($values['username']) ?>" /><span class="field-error" aria-live="polite"></span></div>
<div class="field"><label for="email">Email</label><input type="email" id="email" name="email" required maxlength="120" autocomplete="email" value="<?= e($values['email']) ?>" /><span class="field-error" aria-live="polite"></span></div>
<div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" data-strong-password /><small class="field-hint">At least 8 characters with a letter and a number.</small><span class="field-error" aria-live="polite"></span></div>
<div class="field"><label for="confirm_password">Confirm password</label><input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" data-match="password" /><span class="field-error" aria-live="polite"></span></div>
<button class="button auth-submit" type="submit">Create account</button></form><p class="auth-alt">Already registered? <a href="<?= e(url('auth/login.php')) ?>">Log in</a></p></section></main>
<?php require __DIR__."/../includes/footer.php"; ?>