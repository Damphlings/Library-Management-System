<?php

session_start();

require_once __DIR__ . '/config/database.php';


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$userName = $_SESSION['full_name']
    ?? $_SESSION['username']
    ?? 'Administrator';

$userRole = $_SESSION['role']
    ?? 'Librarian';


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';


/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| TOTAL BOOKS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(
        SUM(quantity),
        0
    )
    FROM books
");

$totalBooks = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| AVAILABLE BOOKS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(
        SUM(available_quantity),
        0
    )
    FROM books
");

$availableBooks = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| BORROWED BOOKS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM borrowings
    WHERE status IN (
        'borrowed',
        'overdue'
    )
");

$borrowedBooks = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| OVERDUE BOOKS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM borrowings
    WHERE status = 'overdue'
       OR (
            status = 'borrowed'
            AND due_date < CURDATE()
       )
");

$overdueBooks = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL BORROWERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM borrowers
    WHERE status = 'active'
");

$totalBorrowers = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| RECENT TRANSACTIONS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        borrowings.id,

        books.book_code,
        books.title,

        borrowers.student_id,
        borrowers.full_name,

        borrowings.borrow_date,
        borrowings.due_date,
        borrowings.return_date,
        borrowings.status

    FROM borrowings

    INNER JOIN books
        ON borrowings.book_id = books.id

    INNER JOIN borrowers
        ON borrowings.borrower_id = borrowers.id

    ORDER BY borrowings.created_at DESC

    LIMIT 8
");

$recentTransactions =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| RECENT ACTIVITY LOGS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        activity_logs.id,
        activity_logs.action,
        activity_logs.description,
        activity_logs.created_at,

        users.full_name

    FROM activity_logs

    LEFT JOIN users
        ON activity_logs.user_id = users.id

    ORDER BY activity_logs.created_at DESC

    LIMIT 8
");

$recentLogs =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| STATUS CLASS
|--------------------------------------------------------------------------
*/

function statusClass($status)
{

    switch ($status) {

        case 'returned':
            return 'status-returned';

        case 'borrowed':
            return 'status-borrowed';

        case 'overdue':
            return 'status-overdue';

        default:
            return 'status-default';

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

    <title>
        <?= htmlspecialchars($pageTitle) ?> | LMS
    </title>


    <!-- =====================================================
         GLOBAL CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


<div class="lms-container">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php

    require_once __DIR__ . '/includes/navbar.php';

    ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">
        
        <!-- =================================================
             PAGE CONTENT
        ================================================== -->

        <section class="content">


            <!-- =================================================
                 WELCOME
            ================================================== -->

            <div class="welcome">

                <h2>

                    Welcome back,
                    <?= htmlspecialchars($userName) ?>!

                </h2>


                <p>

                    Here's an overview of your library today.

                </p>

            </div>



            <!-- =================================================
                 STATISTICS
            ================================================== -->

            <div class="stats-grid">


                <!-- TOTAL BOOKS -->

                <div class="stat-card">

                    <div>

                        <h3>
                            Total Books
                        </h3>

                        <div class="number">

                            <?= number_format(
                                $totalBooks
                            ) ?>

                        </div>

                    </div>


                    <div class="stat-icon">
                        📚
                    </div>

                </div>



                <!-- AVAILABLE BOOKS -->

                <div class="stat-card">

                    <div>

                        <h3>
                            Available Books
                        </h3>

                        <div class="number">

                            <?= number_format(
                                $availableBooks
                            ) ?>

                        </div>

                    </div>


                    <div class="stat-icon">
                        ✓
                    </div>

                </div>



                <!-- BORROWED BOOKS -->

                <div class="stat-card">

                    <div>

                        <h3>
                            Borrowed Books
                        </h3>

                        <div class="number">

                            <?= number_format(
                                $borrowedBooks
                            ) ?>

                        </div>

                    </div>


                    <div class="stat-icon">
                        ↗
                    </div>

                </div>



                <!-- OVERDUE -->

                <div class="stat-card overdue">

                    <div>

                        <h3>
                            Overdue
                        </h3>

                        <div class="number">

                            <?= number_format(
                                $overdueBooks
                            ) ?>

                        </div>

                    </div>


                    <div class="stat-icon">
                        !
                    </div>

                </div>



                <!-- BORROWERS -->

                <div class="stat-card">

                    <div>

                        <h3>
                            Borrowers
                        </h3>

                        <div class="number">

                            <?= number_format(
                                $totalBorrowers
                            ) ?>

                        </div>

                    </div>


                    <div class="stat-icon">
                        👥
                    </div>

                </div>


            </div>



            <!-- =================================================
                 QUICK ACTIONS
            ================================================== -->

            <div class="quick-actions">


                <a
                    href="books/add.php"
                    class="quick-btn"
                >
                    + Add Book
                </a>


                <a
                    href="borrowers/add.php"
                    class="quick-btn"
                >
                    + Add Borrower
                </a>


                <a
                    href="borrowing/borrow.php"
                    class="quick-btn yellow"
                >
                    + Borrow Book
                </a>


                <a
                    href="borrowing/index.php"
                    class="quick-btn"
                >
                    View Transactions
                </a>


            </div>



            <!-- =================================================
                 DASHBOARD GRID
            ================================================== -->

            <div class="dashboard-grid">


                <!-- =================================================
                     RECENT TRANSACTIONS
                ================================================== -->

                <div class="card">


                    <div class="card-header">

                        <h3>
                            Recent Transactions
                        </h3>


                        <a
                            href="borrowing/index.php"
                            class="view-all"
                        >
                            View All
                        </a>

                    </div>



                    <?php if (
                        count($recentTransactions) > 0
                    ): ?>


                        <div class="table-wrapper">


                            <table>


                                <thead>

                                    <tr>

                                        <th>
                                            Book
                                        </th>

                                        <th>
                                            Borrower
                                        </th>

                                        <th>
                                            Borrow Date
                                        </th>

                                        <th>
                                            Due Date
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                    </tr>

                                </thead>



                                <tbody>


                                    <?php foreach (
                                        $recentTransactions
                                        as $transaction
                                    ): ?>


                                        <?php

                                        $displayStatus =
                                            $transaction['status'];


                                        /*
                                        |------------------------------------------------------
                                        | AUTOMATIC OVERDUE DISPLAY
                                        |------------------------------------------------------
                                        */

                                        if (
                                            $transaction['status']
                                                === 'borrowed'
                                            &&
                                            $transaction['due_date']
                                                < date('Y-m-d')
                                        ) {

                                            $displayStatus =
                                                'overdue';

                                        }

                                        ?>


                                        <tr>


                                            <!-- BOOK -->

                                            <td>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $transaction[
                                                            'book_code'
                                                        ]
                                                    ) ?>

                                                </strong>


                                                <br>


                                                <small>

                                                    <?= htmlspecialchars(
                                                        $transaction[
                                                            'title'
                                                        ]
                                                    ) ?>

                                                </small>

                                            </td>



                                            <!-- BORROWER -->

                                            <td>

                                                <?= htmlspecialchars(
                                                    $transaction[
                                                        'full_name'
                                                    ]
                                                ) ?>


                                                <br>


                                                <small>

                                                    <?= htmlspecialchars(
                                                        $transaction[
                                                            'student_id'
                                                        ]
                                                    ) ?>

                                                </small>

                                            </td>



                                            <!-- BORROW DATE -->

                                            <td>

                                                <?= date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $transaction[
                                                            'borrow_date'
                                                        ]
                                                    )
                                                ) ?>

                                            </td>



                                            <!-- DUE DATE -->

                                            <td>

                                                <?= date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $transaction[
                                                            'due_date'
                                                        ]
                                                    )
                                                ) ?>

                                            </td>



                                            <!-- STATUS -->

                                            <td>

                                                <span
                                                    class="status <?= statusClass(
                                                        $displayStatus
                                                    ) ?>"
                                                >

                                                    <?= ucfirst(
                                                        $displayStatus
                                                    ) ?>

                                                </span>

                                            </td>


                                        </tr>


                                    <?php endforeach; ?>


                                </tbody>


                            </table>


                        </div>


                    <?php else: ?>


                        <div class="empty">

                            No transactions found.

                        </div>


                    <?php endif; ?>


                </div>



                <!-- =================================================
                     RECENT ACTIVITY
                ================================================== -->

                <div class="card">


                    <div class="card-header">

                        <h3>
                            Recent Activity
                        </h3>


                        <a
                            href="logs/index.php"
                            class="view-all"
                        >
                            View All
                        </a>

                    </div>



                    <?php if (
                        count($recentLogs) > 0
                    ): ?>


                        <div class="activity-list">


                            <?php foreach (
                                $recentLogs
                                as $log
                            ): ?>


                                <div class="activity-item">


                                    <div class="activity-action">

                                        <?= htmlspecialchars(
                                            $log['action']
                                        ) ?>

                                    </div>


                                    <div class="activity-description">

                                        <?= htmlspecialchars(
                                            $log['description']
                                        ) ?>

                                    </div>


                                    <div class="activity-date">

                                        <?= htmlspecialchars(
                                            $log['full_name']
                                                ?? 'System'
                                        ) ?>


                                        •


                                        <?= date(
                                            'M d, Y h:i A',
                                            strtotime(
                                                $log['created_at']
                                            )
                                        ) ?>

                                    </div>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php else: ?>


                        <div class="empty">

                            No activity recorded yet.

                        </div>


                    <?php endif; ?>


                </div>


            </div>


        </section>


    </main>


</div>


<!-- =========================================================
     DASHBOARD JAVASCRIPT
========================================================= -->

<script src="assets/js/dashboard.js"></script>


</body>

</html>