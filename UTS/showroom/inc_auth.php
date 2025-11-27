<?php
// inc_auth.php
// Authentication & simple role helpers
// Pastikan file ini di-include sebelum output apa pun dan sebelum session digunakan.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * LOGIN dengan password MD5 (sesuaikan jika berubah)
 */
function login_user($pdo, $email, $password) {
    if (!$pdo) return false;

    $stmt = $pdo->prepare('SELECT id, email, password_hash, full_name, role_id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) return false;

    if (strtolower($u['password_hash']) !== md5($password)) return false;

    session_regenerate_id(true);
    $_SESSION['user_id']     = $u['id'];
    $_SESSION['user_email']  = $u['email'];
    $_SESSION['user_name']   = $u['full_name'];
    $_SESSION['role_id']     = $u['role_id'];

    return true;
}

/**
 * LOGOUT
 */
function logout_user() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * CEK LOGIN
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * WAJIB LOGIN
 */
function require_login($redirect = '/login.php') {
    if (!is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * AMBIL ROLE NAME (requires PDO)
 */
function get_role_name($pdo, $role_id) {
    if (!$pdo || !$role_id) return null;
    $stmt = $pdo->prepare('SELECT name FROM roles WHERE id = ? LIMIT 1');
    $stmt->execute([$role_id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ? $r['name'] : null;
}

/**
 * USER SAAT INI
 */
function current_user($pdo = null) {
    if (!is_logged_in()) return null;

    if ($pdo) {
        $stmt = $pdo->prepare('SELECT id, full_name, email, role_id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return [
        'id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role_id' => $_SESSION['role_id'] ?? null,
    ];
}

/**
 * FLEXIBLE require_role
 *
 * Bisa dipanggil sebagai:
 *   require_role($pdo, 'Admin');
 *   require_role('Admin', $pdo);
 *   require_role($pdo, 'Customer'); // dll
 *
 * Jika hanya diberikan roleName tanpa PDO, fungsi akan mencoba menggunakan session role_id
 * dan tidak akan melakukan lookup nama role di DB.
 */
function require_role($a, $b = null) {
    // determine which arg is PDO and which is roleName
    $pdo = null;
    $roleName = null;

    if ($a instanceof PDO) {
        $pdo = $a;
        $roleName = $b;
    } elseif ($b instanceof PDO) {
        $pdo = $b;
        $roleName = $a;
    } else {
        // neither arg is PDO: treat first arg as roleName (string) or role id (int)
        $roleName = $a;
    }

    // require login first
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }

    // get role id from session or refresh from DB if PDO available
    $rid = $_SESSION['role_id'] ?? null;
    if (!$rid && $pdo && isset($_SESSION['user_id'])) {
        $user = current_user($pdo);
        $rid = $user['role_id'] ?? null;
        $_SESSION['role_id'] = $rid;
    }

    if (!$rid) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Forbidden';
        exit;
    }

    // if caller passed a roleName (string), compare to DB lookup (requires PDO)
    if ($roleName && is_string($roleName)) {
        if ($pdo) {
            $name = get_role_name($pdo, $rid);
            if ($name !== $roleName) {
                header('HTTP/1.1 403 Forbidden');
                echo 'Forbidden';
                exit;
            }
            // matched role name -> allowed
            return;
        } else {
            // no PDO: cannot translate name to id reliably, deny for safety
            header('HTTP/1.1 403 Forbidden');
            echo 'Forbidden';
            exit;
        }
    }

    // if roleName is numeric (role id), compare with session role id
    if ($roleName && (is_int($roleName) || ctype_digit((string)$roleName))) {
        if (intval($roleName) !== intval($rid)) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Forbidden';
            exit;
        }
        return;
    }

    // if no roleName provided, we only enforced login above and refreshed role_id if possible
    return;
}
