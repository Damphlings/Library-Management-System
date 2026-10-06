<?php
/*
|--------------------------------------------------------------------------
| SIDEBAR NAVIGATION
|--------------------------------------------------------------------------
| Reusable navigation
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$currentUser = $_SESSION['full_name']
    ?? $_SESSION['username']
    ?? 'User';

$currentRole = $_SESSION['role']
    ?? 'librarian';


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
| Each page should define:
|
| $currentPage = 'dashboard';
| $currentPage = 'books';
| $currentPage = 'borrowers';
| $currentPage = 'borrowing';
| $currentPage = 'returns';
| $currentPage = 'logs';
|
*/

$currentPage = $currentPage ?? '';
?>



<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">


    <!-- =====================================================
         NAVIGATION
    ====================================================== -->

    <nav class="sidebar-menu">


        <!-- MAIN MENU -->

        <div class="menu-title">
            MAIN MENU
        </div>



        <!-- DASHBOARD -->

        <a
            href="/LMS/dashboard.php"
            class="<?= $currentPage === 'dashboard'
                ? 'active'
                : '' ?>"
        >

            <span class="menu-icon">
                ▣
            </span>

            <span>
                Dashboard
            </span>

        </a>



        <!-- BOOK INVENTORY -->

        <a
            href="/LMS/books/index.php"
            class="<?= $currentPage === 'books'
                ? 'active'
                : '' ?>"
        >

            <span class="menu-icon">
                📚
            </span>

            <span>
                Inventory Books
            </span>

        </a>



        <!-- BORROWERS -->

        <a
            href="/LMS/borrowers/index.php"
            class="<?= $currentPage === 'borrowers'
                ? 'active'
                : '' ?>"
        >

            <span class="menu-icon">
                👥
            </span>

            <span>
                Borrowers
            </span>

        </a>



        <!-- =================================================
             TRANSACTIONS
        ================================================== -->

        <div class="menu-title">
            TRANSACTIONS
        </div>



        <!-- BORROWING -->

        <a
            href="/LMS/borrowing/index.php"
            class="<?= $currentPage === 'borrowing'
                ? 'active'
                : '' ?>"
        >

            <span class="menu-icon">
                ↔
            </span>

            <span>
                Borrowing
            </span>

        </a>



        <!-- RETURN -->

        <a
            href="/LMS/borrowing/return.php"
            class="<?= $currentPage === 'returns'
                ? 'active'
                : '' ?>"
        >

            <span class="menu-icon">
                ↩
            </span>

            <span>
                Returns
            </span>

        </a>



        <!-- =================================================
             RECORDS
        ================================================== -->

        <div class="menu-title">
            RECORDS
        </div>



        <!-- ACTIVITY LOGS -->

        <a
            href="/LMS/logs/index.php"
            class="<?= $currentPage === 'logs'
                ? 'active'
                : '' ?>"
        >

            <span class="menu-icon">
                📋
            </span>

            <span>
                Activity Logs
            </span>

        </a>



        <!-- =================================================
             ADMINISTRATION
        ================================================== -->

        <?php if ($currentRole === 'admin'): ?>


            <div class="menu-title">
                ADMINISTRATION
            </div>



            <!-- USERS -->

            <a
                href="/LMS/users/index.php"
                class="<?= $currentPage === 'users'
                    ? 'active'
                    : '' ?>"
            >

                <span class="menu-icon">
                    👤
                </span>

                <span>
                    Users
                </span>

            </a>


        <?php endif; ?>


    </nav>



    <!-- =====================================================
         SIDEBAR BOTTOM
    ====================================================== -->

    <div class="sidebar-bottom">


        <!-- USER -->

        <div class="sidebar-user">


            <div class="user-avatar">

                <?= strtoupper(
                    substr($currentUser, 0, 1)
                ) ?>

            </div>


            <div class="user-info">

                <strong>

                    <?= htmlspecialchars(
                        $currentUser
                    ) ?>

                </strong>


                <span>

                    <?= htmlspecialchars(
                        ucfirst($currentRole)
                    ) ?>

                </span>

            </div>


        </div>



        <!-- LOGOUT -->

        <a
            href="/LMS/logout.php"
            class="logout-link"
            onclick="
                return confirm(
                    'Are you sure you want to logout?'
                );
            "
        >

            <span>
                ↪
            </span>

            Logout

        </a>


    </div>


</aside>
