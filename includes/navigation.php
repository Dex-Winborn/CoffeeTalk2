<?php
// Builds the menu at the top of every page. The links change with the visitor: public pages, logged-in
// staff or admin, or a guest.
// Admins and staff have different dashboards, so the dashboard link depends on the role.
$loggedIn=is_logged_in(); $user=current_user(); $home=$activePage==="home"?"":url("index.php");
$dashboardPath=$user['role']==='admin'?'pages/admin_dashboard.php':'pages/dashboard.php';
$dashboardLabel=$user['role']==='admin'?'Admin Dashboard':'Staff Dashboard';
// Small arrow icon used inside the buttons.
$arrow='<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 10h13m-5-5 5 5-5 5" /></svg>';
// Marks the link of the current page (highlight and aria-current for screen readers).
function nav_active(string $page,string $active): string { return $page===$active?' class="is-active" aria-current="page"':""; }
?>
<header class="site-header"><nav class="nav-wrap" aria-label="Main navigation">
<a class="brand" href="<?= e(url('index.php')) ?>" aria-label="Coffee Talk home"><img src="<?= e(url('images/logo.png')) ?>" alt="Coffee Talk" /></a>
<?php /* Menu button for phones. js/main.js shows or hides the links with it. */ ?>
<button class="menu-toggle" type="button" aria-label="Open navigation" aria-controls="primary-navigation" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16" /></svg></button>
<div class="nav-links" id="primary-navigation">
<?php /* Public pages (home, order menu): reviews, order menu, space, visit, then login or the dashboard. */ ?>
<?php if($navMode==="public"): ?>
<a href="<?= e($home) ?>#story">Reviews</a><a href="<?= e(url('pages/menu.php')) ?>"<?= nav_active('menu',$activePage) ?>>Order menu</a><a href="<?= e($home) ?>#space">The space</a><a href="<?= e($home) ?>#visit">Visit</a>
<?php if($loggedIn): ?><a class="nav-cta" href="<?= e(url($dashboardPath)) ?>"><?= e($dashboardLabel) ?> <?= $arrow ?></a><?php else: ?><a href="<?= e(url('auth/login.php')) ?>">Log in</a><a class="nav-cta" href="<?= e(url('auth/register.php')) ?>">Staff sign up <?= $arrow ?></a><?php endif; ?>
<?php /*
   Logged-in staff or admin: dashboard, menu items, orders, username and a logout button. Logout is a
   POST form with a CSRF token.
*/ ?>
<?php elseif($loggedIn): ?>
<a href="<?= e(url('index.php')) ?>">Home</a><a href="<?= e(url($dashboardPath)) ?>"<?= nav_active($user['role']==='admin'?'admin-dashboard':'dashboard',$activePage) ?>><?= e($dashboardLabel) ?></a><a href="<?= e(url('pages/records.php')) ?>"<?= nav_active('records',$activePage) ?>>Menu items</a><a href="<?= e(url('pages/transactions.php')) ?>"<?= nav_active('transactions',$activePage) ?>>Orders</a><span class="nav-user" title="Signed in as <?= e($user['full_name']) ?>">☕ <?= e($user['username']) ?></span>
<form class="nav-logout" method="post" action="<?= e(url('auth/logout.php')) ?>"><?= csrf_field() ?><button class="nav-cta" type="submit">Log out <?= $arrow ?></button></form>
<?php /* Guest on the login and register pages: Home, Log in and Sign up. */ ?>
<?php else: ?><a href="<?= e(url('index.php')) ?>">Home</a><a href="<?= e(url('auth/login.php')) ?>"<?= nav_active('login',$activePage) ?>>Log in</a><a class="nav-cta" href="<?= e(url('auth/register.php')) ?>">Sign up <?= $arrow ?></a><?php endif; ?>
</div></nav></header>
