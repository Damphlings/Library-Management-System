<?php

session_start();

require_once __DIR__ . '/config/database.php';

/*
|--------------------------------------------------------------------------
| If Already Logged In
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$error = '';
$username = '';


/*
|--------------------------------------------------------------------------
| Login Process
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Validate Input
    |--------------------------------------------------------------------------
    */

    if ($username === '' || $password === '') {

        $error = "Please enter your username and password.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Find User From users Table
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    username,
                    password,
                    full_name,
                    role,
                    status
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([
                $username
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);


            /*
            |--------------------------------------------------------------------------
            | Check Username, Status and Password
            |--------------------------------------------------------------------------
            */

            if (
                $user &&
                strtolower($user['status']) === 'active' &&
                password_verify(
                    $password,
                    $user['password']
                )
            ) {

                /*
                |--------------------------------------------------------------------------
                | Regenerate Session ID
                |--------------------------------------------------------------------------
                */

                session_regenerate_id(true);


                /*
                |--------------------------------------------------------------------------
                | Store User Information
                |--------------------------------------------------------------------------
                */

                $_SESSION['user_id'] =
                    $user['id'];

                $_SESSION['username'] =
                    $user['username'];

                $_SESSION['full_name'] =
                    $user['full_name'];

                $_SESSION['role'] =
                    $user['role'];


                /*
                |--------------------------------------------------------------------------
                | Record Login Activity
                |--------------------------------------------------------------------------
                */

                try {

                    $log = $pdo->prepare("
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

                    $log->execute([
                        $user['id'],
                        'LOGIN',
                        $user['full_name']
                        . ' logged into the system.'
                    ]);

                } catch (PDOException $e) {

                    /*
                    |--------------------------------------------------------------------------
                    | Do not stop login if activity logging fails
                    |--------------------------------------------------------------------------
                    */

                }


                /*
                |--------------------------------------------------------------------------
                | Login Successful
                |--------------------------------------------------------------------------
                */

                header(
                    "Location: dashboard.php"
                );

                exit();


            } else {

                /*
                |--------------------------------------------------------------------------
                | Invalid Login
                |--------------------------------------------------------------------------
                */

                $error =
                    "Invalid username or password.";

            }


        } catch (PDOException $e) {

            $error =
                "Database error. Please check your database connection.";

        }

    }
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sign in</title>


    <style>

        :root {
            color-scheme: dark;

            --bg: #101218;
            --surface: #181b23;
            --surface-2: #1f232d;
            --border: #2a2f3b;
            --border-strong: #3a4151;

            --text: #e8eaf0;
            --text-soft: #c4c9d4;
            --text-muted: #9aa1b0;
            --text-faint: #6f7686;

            --accent: #2ec4b6;
            --accent-hover: #4fd9cb;
            --accent-ink: #04201d;
            --accent-ring: rgba(46, 196, 182, 0.22);

            --bad: #f58b8b;
            --bad-soft: rgba(245, 139, 139, 0.14);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-top: 3px solid var(--accent);
            border-radius: 12px;
            padding: 44px 40px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.5);
        }

        .login-title {
            color: var(--text);
            font-size: 26px;
            margin-bottom: 8px;
        }

        .login-subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 28px;
        }

        .error-message {
            background: var(--bad-soft);
            border-left: 4px solid var(--bad);
            color: var(--bad);
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-soft);
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .form-control {
            width: 100%;
            height: 48px;
            border: 1px solid var(--border-strong);
            border-radius: 7px;
            background: var(--surface-2);
            color: var(--text);
            padding: 0 62px 0 45px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        .form-control::placeholder {
            color: var(--text-faint);
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-ring);
        }

        .form-control:-webkit-autofill {
            -webkit-text-fill-color: var(--text);
            box-shadow: 0 0 0 1000px var(--surface-2) inset;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 13px;
        }

        .password-toggle:hover {
            color: var(--accent);
        }

        .login-button {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 7px;
            background: var(--accent);
            color: var(--accent-ink);
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 5px;
        }

        .login-button:hover {
            background: var(--accent-hover);
        }

        :focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 34px 22px;
            }
        }

    </style>

</head>


<body>


<div class="login-container">


    <!-- =====================================================
         RIGHT SIDE
    ====================================================== -->

    <div class="login-right">


        <h2 class="login-title">
            Welcome Back
        </h2>


        <p class="login-subtitle">
            Enter your username and password to continue.
        </p>


        <?php if ($error !== ''): ?>

            <div class="error-message">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             LOGIN FORM
        ================================================== -->

        <form
            method="POST"
            action=""
            autocomplete="off"
        >


            <!-- USERNAME -->

            <div class="form-group">

                <label for="username">
                    Username
                </label>


                <div class="input-wrapper">

                    <span class="input-icon">
                        👤
                    </span>


                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Enter your username"
                        value="<?= htmlspecialchars($username) ?>"
                        required
                        autofocus
                    >

                </div>

            </div>



            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>


                <div class="input-wrapper">

                    <span class="input-icon">
                        🔒
                    </span>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        id="togglePassword"
                    >
                        Show
                    </button>

                </div>

            </div>



            <!-- LOGIN -->

            <button
                type="submit"
                class="login-button"
            >
                Sign In
            </button>


        </form>



    </div>

</div>



<script>

    /*
    |--------------------------------------------------------------------------
    | SHOW / HIDE PASSWORD
    |--------------------------------------------------------------------------
    */

    const passwordInput =
        document.getElementById('password');

    const togglePassword =
        document.getElementById('togglePassword');


    togglePassword.addEventListener('click', function () {

        if (passwordInput.type === 'password') {

            passwordInput.type = 'text';

            togglePassword.textContent = 'Hide';

        } else {

            passwordInput.type = 'password';

            togglePassword.textContent = 'Show';

        }

    });

</script>


</body>

</html>