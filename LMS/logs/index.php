<?php

session_start();

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$userName = $_SESSION['full_name']
    ?? $_SESSION['username']
    ?? 'User';

$userRole = $_SESSION['role']
    ?? 'librarian';


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle   = 'Activity Logs';
$currentPage = 'logs';


/*
|--------------------------------------------------------------------------
| DELETE SINGLE LOG
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['delete']) &&
    is_numeric($_GET['delete']) &&
    $userRole === 'admin'
) {

    $logId = (int) $_GET['delete'];

    try {

        $stmt = $pdo->prepare("
            DELETE FROM activity_logs
            WHERE id = ?
        ");

        $stmt->execute([$logId]);

    } catch (PDOException $e) {

        // Ignore deletion error

    }

    header("Location: index.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| CLEAR ALL LOGS
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['clear']) &&
    $_GET['clear'] === 'all' &&
    $userRole === 'admin'
) {

    try {

        $pdo->exec("
            DELETE FROM activity_logs
        ");

    } catch (PDOException $e) {

        // Ignore deletion error

    }

    header("Location: index.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| SEARCH AND FILTER
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET['search'] ?? ''
);

$action = trim(
    $_GET['action'] ?? ''
);


/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$limit = 15;

$page = isset($_GET['page'])
    ? max(
        1,
        (int) $_GET['page']
    )
    : 1;

$offset =
    ($page - 1) *
    $limit;


/*
|--------------------------------------------------------------------------
| BUILD WHERE CLAUSE
|--------------------------------------------------------------------------
*/

$where  = [];
$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $where[] = "
        (
            activity_logs.description LIKE ?
            OR activity_logs.action LIKE ?
            OR users.full_name LIKE ?
            OR users.username LIKE ?
        )
    ";

    $searchValue =
        '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

}


/*
|--------------------------------------------------------------------------
| ACTION FILTER
|--------------------------------------------------------------------------
*/

if ($action !== '') {

    $where[] = "
        activity_logs.action = ?
    ";

    $params[] = $action;

}


/*
|--------------------------------------------------------------------------
| WHERE SQL
|--------------------------------------------------------------------------
*/

$whereSQL = '';

if (!empty($where)) {

    $whereSQL =
        'WHERE ' .
        implode(
            ' AND ',
            $where
        );

}


/*
|--------------------------------------------------------------------------
| COUNT LOGS
|--------------------------------------------------------------------------
*/

$countSQL = "
    SELECT COUNT(*)

    FROM activity_logs

    LEFT JOIN users
        ON activity_logs.user_id = users.id

    $whereSQL
";


$stmt =
    $pdo->prepare(
        $countSQL
    );

$stmt->execute(
    $params
);

$totalLogs =
    (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL PAGES
|--------------------------------------------------------------------------
*/

$totalPages =
    max(
        1,
        (int) ceil(
            $totalLogs /
            $limit
        )
    );


/*
|--------------------------------------------------------------------------
| PREVENT INVALID PAGE
|--------------------------------------------------------------------------
*/

if ($page > $totalPages) {

    $page =
        $totalPages;

    $offset =
        ($page - 1) *
        $limit;

}


/*
|--------------------------------------------------------------------------
| GET LOGS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        activity_logs.id,
        activity_logs.action,
        activity_logs.description,
        activity_logs.created_at,

        users.full_name,
        users.username

    FROM activity_logs

    LEFT JOIN users
        ON activity_logs.user_id = users.id

    $whereSQL

    ORDER BY activity_logs.created_at DESC

    LIMIT $limit
    OFFSET $offset
";


$stmt =
    $pdo->prepare(
        $sql
    );

$stmt->execute(
    $params
);

$logs =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| GET ACTION TYPES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT DISTINCT action

    FROM activity_logs

    WHERE action IS NOT NULL
      AND action != ''

    ORDER BY action ASC
");

$actions =
    $stmt->fetchAll(
        PDO::FETCH_COLUMN
    );


/*
|--------------------------------------------------------------------------
| ACTION CLASS
|--------------------------------------------------------------------------
*/

function actionClass($action)
{

    switch (
        strtoupper($action)
    ) {

        case 'LOGIN':
            return 'action-login';

        case 'LOGOUT':
            return 'action-logout';

        case 'ADD_BOOK':
            return 'action-add';

        case 'EDIT_BOOK':
            return 'action-edit';

        case 'DELETE_BOOK':
            return 'action-delete';

        case 'BORROW':
            return 'action-borrow';

        case 'RETURN':
            return 'action-return';

        default:
            return 'action-default';

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


    <!-- GLOBAL LMS CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- LOGS CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/logs.css"
    >

</head>


<body>


<div class="lms-container">


    <!-- =====================================================
         SHARED LMS NAVBAR
    ====================================================== -->

    <?php

    require_once __DIR__ . '/../includes/navbar.php';

    ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             ACTIVITY LOG CONTENT
        ================================================== -->

        <section class="logs-page">


            <!-- =================================================
                 PAGE HEADING
            ================================================== -->

            <div class="logs-page-heading">

                <div>

                    <h1>
                        Activity Logs
                    </h1>

                    <p>
                        Monitor user activity and system transactions.
                    </p>

                </div>

            </div>



            <!-- =================================================
                 FILTER TOOLBAR
            ================================================== -->

            <div class="logs-toolbar">


                <form
                    method="GET"
                    action="index.php"
                    class="logs-filter-form"
                >


                    <!-- SEARCH -->

                    <div class="logs-search">

                        <span class="logs-search-icon">
                            ⌕
                        </span>

                        <input
                            type="text"
                            name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="Search logs, users, actions..."
                        >

                    </div>



                    <!-- ACTION FILTER -->

                    <select
                        name="action"
                        class="logs-filter-select"
                    >

                        <option value="">
                            All Actions
                        </option>


                        <?php foreach (
                            $actions
                            as $actionOption
                        ): ?>

                            <option
                                value="<?= htmlspecialchars($actionOption) ?>"
                                <?= $action === $actionOption
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $actionOption
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>



                    <!-- SEARCH BUTTON -->

                    <button
                        type="submit"
                        class="logs-button logs-button-primary"
                    >

                        Search

                    </button>



                    <!-- RESET -->

                    <?php if (
                        $search !== ''
                        ||
                        $action !== ''
                    ): ?>

                        <a
                            href="index.php"
                            class="logs-button logs-button-reset"
                        >

                            Reset

                        </a>

                    <?php endif; ?>



                    <!-- CLEAR LOGS -->

                    <?php if (
                        $userRole === 'admin'
                    ): ?>

                        <a
                            href="index.php?clear=all"
                            class="logs-button logs-button-danger"
                            onclick="
                                return confirm(
                                    'Are you sure you want to clear ALL activity logs? This cannot be undone.'
                                );
                            "
                        >

                            Clear Logs

                        </a>

                    <?php endif; ?>


                </form>


            </div>



            <!-- =================================================
                 LOG CARD
            ================================================== -->

            <div class="logs-card">


                <!-- HEADER -->

                <div class="logs-card-header">

                    <div>

                        <h2>
                            Activity Records
                        </h2>

                        <p>
                            System activity and user transactions
                        </p>

                    </div>


                    <span class="logs-count">

                        <?= number_format(
                            $totalLogs
                        ) ?>

                        record(s)

                    </span>

                </div>



                <!-- =================================================
                     TABLE
                ================================================== -->

                <?php if (!empty($logs)): ?>


                    <div class="logs-table-wrapper">

                        <table class="logs-table">


                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        User
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                    <th>
                                        Description
                                    </th>

                                    <th>
                                        Date & Time
                                    </th>

                                    <?php if (
                                        $userRole === 'admin'
                                    ): ?>

                                        <th>
                                            Action
                                        </th>

                                    <?php endif; ?>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $logs
                                    as $index => $log
                                ): ?>


                                    <tr>


                                        <!-- NUMBER -->

                                        <td>

                                            <span class="log-number">

                                                <?= $offset + $index + 1 ?>

                                            </span>

                                        </td>



                                        <!-- USER -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $log['full_name']
                                                )
                                            ): ?>

                                                <div class="log-user">

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $log['full_name']
                                                        ) ?>

                                                    </strong>

                                                    <?php if (
                                                        !empty(
                                                            $log['username']
                                                        )
                                                    ): ?>

                                                        <small>

                                                            @<?= htmlspecialchars(
                                                                $log['username']
                                                            ) ?>

                                                        </small>

                                                    <?php endif; ?>

                                                </div>

                                            <?php else: ?>

                                                <div class="log-user">

                                                    <strong>
                                                        System
                                                    </strong>

                                                </div>

                                            <?php endif; ?>

                                        </td>



                                        <!-- ACTION -->

                                        <td>

                                            <span
                                                class="log-action <?= actionClass(
                                                    $log['action']
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $log['action']
                                                ) ?>

                                            </span>

                                        </td>



                                        <!-- DESCRIPTION -->

                                        <td>

                                            <div class="log-description">

                                                <?= htmlspecialchars(
                                                    $log['description']
                                                ) ?>

                                            </div>

                                        </td>



                                        <!-- DATE -->

                                        <td>

                                            <div class="log-date">

                                                <strong>

                                                    <?= date(
                                                        'M d, Y',
                                                        strtotime(
                                                            $log['created_at']
                                                        )
                                                    ) ?>

                                                </strong>

                                                <small>

                                                    <?= date(
                                                        'h:i A',
                                                        strtotime(
                                                            $log['created_at']
                                                        )
                                                    ) ?>

                                                </small>

                                            </div>

                                        </td>



                                        <!-- DELETE -->

                                        <?php if (
                                            $userRole === 'admin'
                                        ): ?>

                                            <td>

                                                <a
                                                    href="index.php?delete=<?= (int) $log['id'] ?>"
                                                    class="log-delete"
                                                    onclick="
                                                        return confirm(
                                                            'Delete this activity log?'
                                                        );
                                                    "
                                                >

                                                    Delete

                                                </a>

                                            </td>

                                        <?php endif; ?>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>

                    </div>


                <?php else: ?>


                    <!-- =================================================
                         EMPTY STATE
                    ================================================== -->

                    <div class="logs-empty">

                        <div class="logs-empty-icon">
                            📋
                        </div>

                        <h3>
                            No Activity Logs Found
                        </h3>

                        <p>
                            There are no activity records matching your search.
                        </p>

                    </div>


                <?php endif; ?>



                <!-- =================================================
                     PAGINATION
                ================================================== -->

                <?php if (
                    $totalPages > 1
                ): ?>


                    <div class="logs-pagination">


                        <div class="logs-pagination-info">

                            Page

                            <strong>
                                <?= $page ?>
                            </strong>

                            of

                            <strong>
                                <?= $totalPages ?>
                            </strong>

                        </div>


                        <div class="logs-pagination-links">


                            <!-- PREVIOUS -->

                            <?php if (
                                $page > 1
                            ): ?>

                                <a
                                    href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>"
                                >

                                    ←

                                </a>

                            <?php endif; ?>



                            <!-- PAGE NUMBERS -->

                            <?php

                            $startPage =
                                max(
                                    1,
                                    $page - 2
                                );

                            $endPage =
                                min(
                                    $totalPages,
                                    $page + 2
                                );

                            ?>


                            <?php for (
                                $i = $startPage;
                                $i <= $endPage;
                                $i++
                            ): ?>


                                <a
                                    href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>"
                                    class="<?= $i === $page
                                        ? 'active'
                                        : '' ?>"
                                >

                                    <?= $i ?>

                                </a>


                            <?php endfor; ?>



                            <!-- NEXT -->

                            <?php if (
                                $page < $totalPages
                            ): ?>

                                <a
                                    href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>"
                                >

                                    →

                                </a>

                            <?php endif; ?>


                        </div>

                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>


</body>

</html>