<?php

session_start();

/*
|--------------------------------------------------------------------------
| ENTRY POINT
|--------------------------------------------------------------------------
| If the user is already logged in:
|     → Go to dashboard
|
| If the user is not logged in:
|     → Go to login page
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user_id'])) {

    header("Location: dashboard.php");
    exit();

} else {

    header("Location: login.php");
    exit();

}
?>
