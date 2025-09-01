<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/db.php';

function current_user(mysqli $con) {
	if (!isset($_SESSION["user_id"])) return null;
	$uid = intval($_SESSION["user_id"]);
	$res = $con->query("SELECT u.id, u.username, u.role_id, u.dept_id FROM users u WHERE u.id = $uid LIMIT 1");
	return $res ? $res->fetch_assoc() : null;
}

function ensure_logged_in() {
	if (!isset($_SESSION["user"]) && !isset($_SESSION['user_id'])) {
		header("Location: index.php");
		exit();
	}
}

if (!function_exists('ensure_permission')) {
function ensure_permission(mysqli $con, $permission) {
	if (!isset($_SESSION['user_id'])) return; // allow legacy sessions
	$uid = intval($_SESSION["user_id"]);
	
	// Admin (role_id = 1) and Director (role_id = 11) have all permissions
	$user_result = $con->query("SELECT role_id FROM users WHERE id = $uid LIMIT 1");
	if ($user_result && $user_result->num_rows > 0) {
		$user = $user_result->fetch_assoc();
		if (in_array($user['role_id'], [1, 11])) return; // Admin and Director - grant access
	}
	
	$q = $con->query("SELECT rp.permission FROM users u JOIN role_permissions rp ON rp.role_id = u.role_id WHERE u.id = $uid AND rp.permission = '".$con->real_escape_string($permission)."' LIMIT 1");
	if (!$q || $q->num_rows === 0) {
		// Check if headers have already been sent
		if (!headers_sent()) {
			header("HTTP/1.1 403 Forbidden");
			header("Location: access_denied.php");
		} else {
			// If headers already sent, use JavaScript redirect
			echo "<script>window.location.href='access_denied.php';</script>";
		}
		exit();
	}
}
}

if (!function_exists('user_has_permission')) {
function user_has_permission(mysqli $con, $permission) {
	if (!isset($_SESSION['user_id'])) return false;
	$uid = intval($_SESSION["user_id"]);
	
	// Admin (role_id = 1) and Director (role_id = 11) have all permissions
	$user_result = $con->query("SELECT role_id FROM users WHERE id = $uid LIMIT 1");
	if ($user_result && $user_result->num_rows > 0) {
		$user = $user_result->fetch_assoc();
		if (in_array($user['role_id'], [1, 11])) return true; // Admin and Director
	}
	
	// Check specific permission
	$q = $con->query("SELECT rp.permission FROM users u JOIN role_permissions rp ON rp.role_id = u.role_id WHERE u.id = $uid AND rp.permission = '".$con->real_escape_string($permission)."' LIMIT 1");
	return $q && $q->num_rows > 0;
}
}

?>