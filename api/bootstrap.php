<?php
session_start();

// Basic bootstrap for DB and RBAC helpers

require_once __DIR__ . '/../admin/db.php'; // provides $con (mysqli)

function json_response($data, $status = 200) {;
	http_response_code($status);
	header('Content-Type: application/json');
	echo json_encode($data);
	exit();
}

function require_login() {
	if (!isset($_SESSION['user_id'])) {
		json_response([ 'error' => 'unauthorized' ], 401);
	}
}

function get_user(mysqli $con) {
	if (!isset($_SESSION['user_id'])) { return null; }
	$uid = intval($_SESSION['user_id']);
	$res = $con->query("SELECT u.id, u.username, u.role_id, u.dept_id, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = $uid LIMIT 1");
	return $res ? $res->fetch_assoc() : null;
}

function user_has_permission(mysqli $con, $permission) {
	$user = get_user($con);
	if (!$user) return false;
	$roleId = intval($user['role_id']);
	$perm = $con->real_escape_string($permission);
	$sql = "SELECT 1 FROM role_permissions WHERE role_id = $roleId AND permission = '$perm' LIMIT 1";
	$res = $con->query($sql);
	return $res && $res->num_rows > 0;
}

function require_permission(mysqli $con, $permission) {
	if (!user_has_permission($con, $permission)) {
		json_response([ 'error' => 'forbidden', 'permission' => $permission ], 403);
	}
}

?>


