```php
<?php

session_start();

require_once __DIR__ . '/config/database.php';

/*
|--------------------------------------------------------------------------
| RECORD LOGOUT ACTIVITY
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user_id'])) {

    $userId = $_SESSION['user_id'];
    $fullName = $_SESSION['full_name'] ?? 'User';

    try {

        $stmt = $pdo->prepare("
            INSERT INTO activity_logs
            (
                user_id,
                action,
                description
            )
            VALUES
            (
                ?,
                ?,
                ?
            )
        ");

        $stmt->execute([
            $userId,
            'LOGOUT',
            $fullName . ' logged out of the system.'
        ]);

    } catch (PDOException $e) {

        /*
        | Do not stop logout if the activity log fails.
        | The user should still be logged out.
        */

    }
}


/*
|--------------------------------------------------------------------------
| DESTROY SESSION
|--------------------------------------------------------------------------
*/

// Remove all session variables
$_SESSION = [];


/*
|--------------------------------------------------------------------------
| DELETE SESSION COOKIE
|--------------------------------------------------------------------------
*/

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


/*
|--------------------------------------------------------------------------
| DESTROY SESSION
|--------------------------------------------------------------------------
*/

session_destroy();


/*
|--------------------------------------------------------------------------
| REDIRECT TO LOGIN
|--------------------------------------------------------------------------
*/

header("Location: login.php?logout=success");
exit();

?>
```
