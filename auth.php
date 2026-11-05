<?php
// Central authentication guard for every protected page.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/config/db.php";

if (empty($_SESSION["user_id"]) || !filter_var($_SESSION["user_id"], FILTER_VALIDATE_INT)) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: index.php");
    exit();
}

// Confirm that the account still exists. Never substitute another user's account.
$auth_user_id = (int) $_SESSION["user_id"];
$auth_stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
if (!$auth_stmt) {
    http_response_code(500);
    exit("Authentication could not be completed. Please try again later.");
}
$auth_stmt->bind_param("i", $auth_user_id);
$auth_stmt->execute();
$auth_result = $auth_stmt->get_result();
$auth_user = $auth_result->fetch_assoc();
$auth_stmt->close();

if (!$auth_user) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: index.php");
    exit();
}

$_SESSION["user_id"] = (int) $auth_user["id"];
$_SESSION["user_name"] = $auth_user["name"];
$_SESSION["user_email"] = $auth_user["email"];
$user_id = (int) $auth_user["id"];
$user_name = $auth_user["name"];
?>
