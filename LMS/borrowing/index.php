<?php

session_start();

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle   = 'Borrowing';
$currentPage = 'borrowing';


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
| SEARCH AND FILTER
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET['search'] ?? ''
);

$status = trim(
    $_GET['status'] ?? ''
);


/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$limit = 10;

$page = isset($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;

$offset = ($page - 1) * $limit;


/*
|--------------------------------------------------------------------------
| BUILD WHERE CLAUSE
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $where[] = "
        (
            borrowers.full_name LIKE ?
            OR borrowers.student_id LIKE ?
            OR books.title LIKE ?
            OR books.author LIKE ?
            OR books.isbn LIKE ?
        )
    ";

    $searchValue =
        '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $where[] = "
        borrowings.status = ?
    ";

    $params[] = $status;

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
| COUNT BORROWING RECORDS
|--------------------------------------------------------------------------
*/

$countSQL = "

    SELECT COUNT(*)

    FROM borrowings

    LEFT JOIN borrowers
        ON borrowings.borrower_id = borrowers.id

    LEFT JOIN books
        ON borrowings.book_id = books.id

    $whereSQL

";


$stmt = $pdo->prepare(
    $countSQL
);

$stmt->execute(
    $params
);

$totalRecords =
    (int) $stmt->fetchColumn();


$totalPages = max(
    1,
    (int) ceil(
        $totalRecords / $limit
    )
);


/*
|--------------------------------------------------------------------------
| PREVENT INVALID PAGE
|--------------------------------------------------------------------------
*/

if ($page > $totalPages) {

    $page = $totalPages;

    $offset =
        ($page - 1) * $limit;

}


/*
|--------------------------------------------------------------------------
| GET BORROWING RECORDS
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| borrowings.borrower_id
|       ↓
| borrowers.id
|
| The borrower display ID is:
|
| borrowers.student_id
|
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        borrowings.id,

        borrowings.book_id,

        borrowings.borrower_id,

        borrowings.borrow_date,

        borrowings.created_at,

        borrowings.due_date,

        borrowings.processed_by,

        borrowings.return_date,

        borrowings.status,


        /* Borrower information */

        borrowers.id
            AS borrower_db_id,

        borrowers.student_id
            AS borrower_student_id,

        borrowers.full_name
            AS borrower_name,

        borrowers.email
            AS borrower_email,

        borrowers.contact
            AS borrower_contact,

        borrowers.course
            AS borrower_course,

        borrowers.year_level
            AS borrower_year_level,


        /* Book information */

        books.id
            AS book_db_id,

        books.title
            AS book_title,

        books.author
            AS book_author,

        books.isbn
            AS book_isbn

    FROM borrowings


    /* Correct borrower relationship */

    LEFT JOIN borrowers

        ON borrowings.borrower_id
        = borrowers.id


    /* Correct book relationship */

    LEFT JOIN books

        ON borrowings.book_id
        = books.id


    $whereSQL


    ORDER BY

        borrowings.borrow_date DESC,

        borrowings.id DESC


    LIMIT $limit

    OFFSET $offset

";


$stmt = $pdo->prepare(
    $sql
);

$stmt->execute(
    $params
);

$borrowings =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| GET BORROWING STATUS TYPES
|--------------------------------------------------------------------------
*/

$statusStmt = $pdo->query("

    SELECT DISTINCT status

    FROM borrowings

    WHERE status IS NOT NULL

    AND status != ''

    ORDER BY status ASC

");

$statuses =
    $statusStmt->fetchAll(
        PDO::FETCH_COLUMN
    );


/*
|--------------------------------------------------------------------------
| STATUS CLASS
|--------------------------------------------------------------------------
*/

function borrowingStatusClass(
    $status
) {

    switch (
        strtolower(
            trim($status)
        )
    ) {

        case 'borrowed':

            return 'status-borrowed';


        case 'returned':

            return 'status-returned';


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
        <?= htmlspecialchars($pageTitle) ?>
    </title>


    <!-- GLOBAL CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =====================================================
           BORROWING PAGE
        ===================================================== */

        .borrowing-page {

            width: 100%;

        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-heading {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;

        }


        .page-heading h2 {

            color: var(--text);

            font-size: 24px;

            margin-bottom: 5px;

        }


        .page-heading p {

            color: var(--text-muted);

            font-size: 13px;

        }


        .add-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            min-height: 42px;

            padding: 0 18px;

            background: var(--accent);

            color: var(--accent-ink);

            border-radius: 6px;

            font-size: 12px;

            font-weight: bold;

        }


        .add-button:hover {

            background: var(--accent-hover);

        }


        /* =====================================================
           TOOLBAR
        ===================================================== */

        .filter-card {

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: 18px;

            margin-bottom: 20px;

        }


        .filter-form {

            display: flex;

            align-items: center;

            gap: 10px;

            flex-wrap: wrap;

        }


        .search-box {

            flex: 1;

            min-width: 240px;

        }


        .search-box input,
        .filter-form select {

            width: 100%;

            height: 42px;

            border: 1px solid var(--border-strong);

            border-radius: 6px;

            padding: 0 12px;

            outline: none;

            font-family: inherit;

            font-size: 12px;

            background: var(--surface-2);

        }


        .search-box input:focus,
        .filter-form select:focus {

            border-color: var(--accent);

        }


        .filter-form select {

            width: 180px;

        }


        .filter-button {

            height: 42px;

            padding: 0 18px;

            border: none;

            border-radius: 6px;

            background: var(--accent);

            color: var(--accent-ink);

            font-size: 12px;

            font-weight: bold;

            cursor: pointer;

        }


        .filter-button:hover {

            background: var(--accent-hover);

        }


        .reset-button {

            height: 42px;

            padding: 0 18px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 6px;

            background: var(--btn-grey);

            color: var(--text);

            font-size: 12px;

            font-weight: bold;

        }


        .reset-button:hover {

            background: var(--btn-grey-hover);

        }


        /* =====================================================
           TABLE CARD
        ===================================================== */

        .table-card {

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: 10px;

            overflow: hidden;

        }


        .table-card-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 18px 20px;

            border-bottom: 1px solid var(--border);

        }


        .table-card-header h3 {

            color: var(--text);

            font-size: 16px;

        }


        .record-count {

            color: var(--text-muted);

            font-size: 12px;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .data-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 950px;

        }


        .data-table th {

            background: var(--surface-2);

            color: var(--text-muted);

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: .3px;

            font-weight: bold;

            padding: 13px 15px;

            text-align: left;

            white-space: nowrap;

        }


        .data-table td {

            padding: 14px 15px;

            border-top: 1px solid var(--border-soft);

            color: var(--text-soft);

            font-size: 12px;

            vertical-align: middle;

        }


        .data-table tbody tr:hover {

            background: var(--row-hover);

        }


        .borrower-name {

            color: var(--text);

            font-weight: bold;

        }


        .student-id {

            color: var(--text-muted);

            font-size: 11px;

            margin-top: 3px;

        }


        .book-title {

            color: var(--text);

            font-weight: bold;

        }


        .book-author {

            color: var(--text-muted);

            font-size: 11px;

            margin-top: 3px;

        }


        .date-text {

            color: var(--text-soft);

            white-space: nowrap;

        }


        .date-subtext {

            color: var(--text-muted);

            font-size: 10px;

            margin-top: 3px;

        }


        /* =====================================================
           STATUS
        ===================================================== */

        .status-badge {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: bold;

            white-space: nowrap;

        }


        .status-borrowed {

            background: var(--warn-soft);

            color: var(--warn);

        }


        .status-returned {

            background: var(--ok-soft);

            color: var(--ok);

        }


        .status-overdue {

            background: var(--bad-soft);

            color: var(--bad);

        }


        .status-default {

            background: var(--surface-3);

            color: var(--text-muted);

        }


        /* =====================================================
           ACTIONS
        ===================================================== */

        .action-buttons {

            display: flex;

            align-items: center;

            gap: 6px;

            white-space: nowrap;

        }


        .action-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 34px;

            padding: 0 10px;

            border-radius: 5px;

            font-size: 11px;

            font-weight: bold;

        }


        .view-button {

            background: var(--accent);

            color: var(--accent-ink);

        }


        .view-button:hover {

            background: var(--accent-hover);

        }


        .return-button {

            background: var(--ok-solid);

            color: #ffffff;

        }


        .return-button:hover {

            background: #16803f;

        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {

            padding: 60px 20px;

            text-align: center;

            color: var(--text-muted);

        }


        .empty-icon {

            font-size: 40px;

            margin-bottom: 10px;

        }


        .empty-state h3 {

            color: var(--text);

            font-size: 16px;

            margin-bottom: 5px;

        }


        .empty-state p {

            font-size: 12px;

        }


        /* =====================================================
           PAGINATION
        ===================================================== */

        .pagination {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding: 18px 20px;

            border-top: 1px solid var(--border);

            flex-wrap: wrap;

        }


        .pagination-info {

            color: var(--text-muted);

            font-size: 12px;

        }


        .pagination-links {

            display: flex;

            gap: 5px;

        }


        .pagination-links a {

            min-width: 34px;

            height: 34px;

            padding: 0 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            border: 1px solid var(--border);

            border-radius: 5px;

            color: var(--accent);

            font-size: 12px;

        }


        .pagination-links a:hover {

            background: var(--accent);

            color: var(--accent-ink);

        }


        .pagination-links a.active {

            background: var(--accent);

            color: var(--accent-ink);

            border-color: var(--accent);

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 700px) {

            .page-heading {

                flex-direction: column;

                align-items: flex-start;

            }


            .add-button {

                width: 100%;

            }


            .filter-form {

                flex-direction: column;

                align-items: stretch;

            }


            .search-box {

                width: 100%;

            }


            .filter-form select {

                width: 100%;

            }


            .filter-button,
            .reset-button {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<div class="lms-container">


    <!-- =====================================================
         NAVBAR
    ====================================================== -->

    <?php

    require_once __DIR__ . '/../includes/navbar.php';

    ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <div class="borrowing-page">


            <!-- =================================================
                 PAGE HEADING
            ================================================== -->

            <div class="page-heading">

                <div>

                    <h2>
                        Borrowing
                    </h2>

                    <p>
                        Manage book borrowing transactions.
                    </p>

                </div>


                <a
                    href="borrow.php"
                    class="add-button"
                >

                    <span>+</span>

                    New Borrowing

                </a>

            </div>



            <!-- =================================================
                 FILTER
            ================================================== -->

            <div class="filter-card">

                <form
                    method="GET"
                    action="index.php"
                    class="filter-form"
                >


                    <div class="search-box">

                        <input
                            type="text"
                            name="search"
                            placeholder="Search borrower, student ID, book, author, or ISBN..."
                            value="<?= htmlspecialchars($search) ?>"
                        >

                    </div>


                    <select name="status">

                        <option value="">
                            All Status
                        </option>


                        <?php foreach (
                            $statuses
                            as $statusOption
                        ): ?>

                            <option
                                value="<?= htmlspecialchars($statusOption) ?>"
                                <?= $status === $statusOption
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $statusOption
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <button
                        type="submit"
                        class="filter-button"
                    >

                        Search

                    </button>


                    <a
                        href="index.php"
                        class="reset-button"
                    >

                        Reset

                    </a>


                </form>

            </div>



            <!-- =================================================
                 TABLE
            ================================================== -->

            <div class="table-card">


                <div class="table-card-header">

                    <h3>
                        Borrowing Records
                    </h3>


                    <span class="record-count">

                        <?= number_format(
                            $totalRecords
                        ) ?>

                        record(s)

                    </span>

                </div>



                <?php if (
                    !empty($borrowings)
                ): ?>


                    <div class="table-wrapper">


                        <table class="data-table">


                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        Borrower
                                    </th>

                                    <th>
                                        Book
                                    </th>

                                    <th>
                                        Borrow Date
                                    </th>

                                    <th>
                                        Due Date
                                    </th>

                                    <th>
                                        Return Date
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $borrowings
                                as $index => $record
                            ): ?>


                                <tr>


                                    <!-- NUMBER -->

                                    <td>

                                        <?= $offset
                                            + $index
                                            + 1 ?>

                                    </td>



                                    <!-- BORROWER -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $record[
                                                    'borrower_name'
                                                ]
                                            )
                                        ): ?>

                                            <div
                                                class="borrower-name"
                                            >

                                                <?= htmlspecialchars(
                                                    $record[
                                                        'borrower_name'
                                                    ]
                                                ) ?>

                                            </div>


                                            <div
                                                class="student-id"
                                            >

                                                Student ID:

                                                <?= htmlspecialchars(
                                                    $record[
                                                        'borrower_student_id'
                                                    ]
                                                ) ?>

                                            </div>

                                        <?php else: ?>

                                            <span>
                                                Unknown Borrower
                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- BOOK -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $record[
                                                    'book_title'
                                                ]
                                            )
                                        ): ?>

                                            <div
                                                class="book-title"
                                            >

                                                <?= htmlspecialchars(
                                                    $record[
                                                        'book_title'
                                                    ]
                                                ) ?>

                                            </div>


                                            <?php if (
                                                !empty(
                                                    $record[
                                                        'book_author'
                                                    ]
                                                )
                                            ): ?>

                                                <div
                                                    class="book-author"
                                                >

                                                    <?= htmlspecialchars(
                                                        $record[
                                                            'book_author'
                                                        ]
                                                    ) ?>

                                                </div>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span>
                                                Unknown Book
                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- BORROW DATE -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $record[
                                                    'borrow_date'
                                                ]
                                            )
                                        ): ?>

                                            <div
                                                class="date-text"
                                            >

                                                <?= date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $record[
                                                            'borrow_date'
                                                        ]
                                                    )
                                                ) ?>

                                            </div>

                                        <?php else: ?>

                                            —
                                            
                                        <?php endif; ?>

                                    </td>



                                    <!-- DUE DATE -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $record[
                                                    'due_date'
                                                ]
                                            )
                                        ): ?>

                                            <div
                                                class="date-text"
                                            >

                                                <?= date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $record[
                                                            'due_date'
                                                        ]
                                                    )
                                                ) ?>

                                            </div>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>



                                    <!-- RETURN DATE -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $record[
                                                    'return_date'
                                                ]
                                            )
                                        ): ?>

                                            <div
                                                class="date-text"
                                            >

                                                <?= date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $record[
                                                            'return_date'
                                                        ]
                                                    )
                                                ) ?>

                                            </div>

                                        <?php else: ?>

                                            <span
                                                class="date-subtext"
                                            >

                                                Not returned

                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                status-badge
                                                <?= borrowingStatusClass(
                                                    $record[
                                                        'status'
                                                    ]
                                                ) ?>
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $record[
                                                    'status'
                                                ]
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- ACTIONS -->

<td>

    <div class="action-buttons">

        <?php if (
            strtolower(
                trim(
                    $record['status']
                )
            ) !== 'returned'
        ): ?>

            <a
                href="return.php?id=<?= (int) $record['id'] ?>"
                class="action-button return-button"
            >

                Return

            </a>

        <?php else: ?>

            <span class="returned-label">
                Returned
            </span>

        <?php endif; ?>

    </div>

</td>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <!-- =================================================
                         EMPTY STATE
                    ================================================== -->

                    <div
                        class="empty-state"
                    >

                        <div
                            class="empty-icon"
                        >
                            📚
                        </div>


                        <h3>
                            No Borrowing Records Found
                        </h3>


                        <p>
                            There are no borrowing records matching your search.
                        </p>

                    </div>


                <?php endif; ?>



                <!-- =================================================
                     PAGINATION
                ================================================== -->

                <?php if (
                    $totalPages > 1
                ): ?>


                    <div
                        class="pagination"
                    >


                        <div
                            class="pagination-info"
                        >

                            Page

                            <?= $page ?>

                            of

                            <?= $totalPages ?>

                        </div>


                        <div
                            class="pagination-links"
                        >


                            <?php if (
                                $page > 1
                            ): ?>

                                <a
                                    href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"
                                >

                                    ‹

                                </a>

                            <?php endif; ?>



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
                                    href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"
                                    class="<?= $i === $page
                                        ? 'active'
                                        : '' ?>"
                                >

                                    <?= $i ?>

                                </a>


                            <?php endfor; ?>



                            <?php if (
                                $page < $totalPages
                            ): ?>

                                <a
                                    href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"
                                >

                                    ›

                                </a>

                            <?php endif; ?>


                        </div>


                    </div>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


</body>

</html>