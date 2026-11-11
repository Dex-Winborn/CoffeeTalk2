<?php
// Logs the user out. Only a POST request with a valid CSRF token is accepted, so a link on another
// site cannot log the user out.
require_once __DIR__ . "/../includes/functions.php"; require_post("index.php");
// Clear the session data and delete the session cookie in the browser.
$_SESSION=[]; if(ini_get("session.use_cookies")){ $p=session_get_cookie_params(); setcookie(session_name(),"",time()-42000,$p["path"],$p["domain"],$p["secure"],$p["httponly"]); }
// Destroy the old session, start a new one for the logout message, then go to the login page.
session_destroy(); session_start(); session_regenerate_id(true); flash_set("success","You have been logged out."); redirect("auth/login.php");
