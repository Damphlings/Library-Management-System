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
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$currentUser = $_SESSION['full_name']
    ?? $_SESSION['username']
    ?? 'Administrator';

$currentRole = $_SESSION['role']
    ?? 'Librarian';


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle   = 'Book Inventory';
$currentPage = 'books';


/*
|--------------------------------------------------------------------------
| SEARCH / FILTER
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET['search'] ?? ''
);

$category = trim(
    $_GET['category'] ?? ''
);

$status = trim(
    $_GET['status'] ?? ''
);


/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$page = max(
    1,
    (int) ($_GET['page'] ?? 1)
);

$recordsPerPage = 10;


/*
|--------------------------------------------------------------------------
| DELETE BOOK
|--------------------------------------------------------------------------
|
| The delete request is handled here so the hidden delete form
| from the page can submit directly to index.php.
|
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['delete_book'])
) {

    $bookId = (int) (
        $_POST['book_id'] ?? 0
    );


    if ($bookId > 0) {

        try {

            /*
            |--------------------------------------------------------------------------
            | CHECK IF BOOK EXISTS
            |--------------------------------------------------------------------------
            */

            $checkStmt = $pdo->prepare("
                SELECT
                    id,
                    title,
                    quantity,
                    available_quantity
                FROM books
                WHERE id = ?
                LIMIT 1
            ");

            $checkStmt->execute([
                $bookId
            ]);

            $bookToDelete =
                $checkStmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$bookToDelete) {

                header(
                    "Location: index.php?delete_error=1"
                );

                exit();

            }


            /*
            |--------------------------------------------------------------------------
            | CHECK BORROWED COPIES
            |--------------------------------------------------------------------------
            */

            $borrowedCopies =
                (int) $bookToDelete['quantity']
                -
                (int) $bookToDelete['available_quantity'];


            if ($borrowedCopies > 0) {

                header(
                    "Location: index.php?delete_error=borrowed"
                );

                exit();

            }


            /*
            |--------------------------------------------------------------------------
            | DELETE BOOK
            |--------------------------------------------------------------------------
            */

            $deleteStmt = $pdo->prepare("
                DELETE FROM books
                WHERE id = ?
            ");

            $deleteStmt->execute([
                $bookId
            ]);


            /*
            |--------------------------------------------------------------------------
            | ACTIVITY LOG
            |--------------------------------------------------------------------------
            */

            $logDescription =
                'Deleted book: '
                . $bookToDelete['title']
                . ' (ID: '
                . $bookId
                . ').';


            $logStmt = $pdo->prepare("
                INSERT INTO activity_logs
                (
                    user_id,
                    action,
                    description
                )
                VALUES
                (
                    ?,
                    'DELETE',
                    ?
                )
            ");

            $logStmt->execute([

                $_SESSION['user_id'],

                $logDescription

            ]);


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            header(
                "Location: index.php?deleted=1"
            );

            exit();


        } catch (PDOException $e) {

            header(
                "Location: index.php?delete_error=1"
            );

            exit();

        }

    }

}


/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$categoryStmt = $pdo->query("
    SELECT DISTINCT category
    FROM books
    WHERE category IS NOT NULL
      AND category <> ''
    ORDER BY category ASC
");

$categories =
    $categoryStmt->fetchAll(
        PDO::FETCH_COLUMN
    );


/*
|--------------------------------------------------------------------------
| BUILD FILTER CONDITIONS
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
            title LIKE :search
            OR author LIKE :search
            OR isbn LIKE :search
            OR book_code LIKE :search
        )
    ";

    $params[':search'] =
        '%' . $search . '%';

}


/*
|--------------------------------------------------------------------------
| CATEGORY
|--------------------------------------------------------------------------
*/

if ($category !== '') {

    $where[] =
        "category = :category";

    $params[':category'] =
        $category;

}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $where[] =
        "status = :status";

    $params[':status'] =
        $status;

}


/*
|--------------------------------------------------------------------------
| WHERE CLAUSE
|--------------------------------------------------------------------------
*/

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE '
        . implode(
            ' AND ',
            $where
        );

}


/*
|--------------------------------------------------------------------------
| TOTAL RECORDS
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*)
    FROM books
    $whereSql
";


$countStmt =
    $pdo->prepare(
        $countSql
    );


$countStmt->execute(
    $params
);


$totalRecords =
    (int) $countStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL PAGES
|--------------------------------------------------------------------------
*/

$totalPages = max(
    1,
    (int) ceil(
        $totalRecords /
        $recordsPerPage
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

}


/*
|--------------------------------------------------------------------------
| OFFSET
|--------------------------------------------------------------------------
*/

$offset =
    ($page - 1)
    *
    $recordsPerPage;


/*
|--------------------------------------------------------------------------
| GET BOOKS
|--------------------------------------------------------------------------
*/

$booksSql = "
    SELECT

        id,
        book_code,
        isbn,
        title,
        author,
        category,
        publisher,
        publication_year,
        description,
        year_published,
        quantity,
        available_quantity,
        location,
        status,
        created_at,
        updated_at

    FROM books

    $whereSql

    ORDER BY id DESC

    LIMIT :limit
    OFFSET :offset
";


$booksStmt =
    $pdo->prepare(
        $booksSql
    );


/*
|--------------------------------------------------------------------------
| BIND FILTER PARAMETERS
|--------------------------------------------------------------------------
*/

foreach (
    $params
    as $key => $value
) {

    $booksStmt->bindValue(
        $key,
        $value,
        PDO::PARAM_STR
    );

}


/*
|--------------------------------------------------------------------------
| BIND PAGINATION
|--------------------------------------------------------------------------
*/

$booksStmt->bindValue(
    ':limit',
    $recordsPerPage,
    PDO::PARAM_INT
);

$booksStmt->bindValue(
    ':offset',
    $offset,
    PDO::PARAM_INT
);


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

$booksStmt->execute();


$books =
    $booksStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| INVENTORY STATISTICS
|--------------------------------------------------------------------------
*/

$statsStmt = $pdo->query("
    SELECT

        COUNT(*) AS total_titles,

        COALESCE(
            SUM(quantity),
            0
        ) AS total_copies,

        COALESCE(
            SUM(available_quantity),
            0
        ) AS available_copies,

        COALESCE(
            SUM(
                quantity -
                available_quantity
            ),
            0
        ) AS borrowed_copies

    FROM books
");


$stats =
    $statsStmt->fetch(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| STATISTIC VALUES
|--------------------------------------------------------------------------
*/

$totalTitles =
    (int) (
        $stats['total_titles']
        ?? 0
    );


$totalCopies =
    (int) (
        $stats['total_copies']
        ?? 0
    );


$availableCopies =
    (int) (
        $stats['available_copies']
        ?? 0
    );


$borrowedCopies =
    (int) (
        $stats['borrowed_copies']
        ?? 0
    );

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


    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- BOOK CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/books.css"
    >

</head>


<body>


<div class="lms-container">


    <!-- =====================================================
         NAVBAR / SIDEBAR
    ====================================================== -->

    <?php

    require_once __DIR__ . '/../includes/navbar.php';

    ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             BOOK INVENTORY
        ================================================== -->

        <div class="books-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="page-heading">

                <div>

                    <h2>
                        Book Inventory
                    </h2>

                    <p>
                        Manage and monitor all books in the library.
                    </p>

                </div>


                <a
                    href="add.php"
                    class="add-book-button"
                >

                    <span>+</span>

                    Add Book

                </a>

            </div>


            <!-- =================================================
                 SUCCESS / ERROR MESSAGES
            ================================================== -->

            <?php if (
                isset($_GET['added'])
                &&
                $_GET['added'] === '1'
            ): ?>

                <div class="alert alert-success">

                    <div class="message-icon">
                        ✓
                    </div>

                    <div>

                        <strong>
                            Book Added Successfully
                        </strong>

                        <p>
                            The book has been added to the inventory.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="close-message"
                        onclick="this.parentElement.remove()"
                    >
                        ×
                    </button>

                </div>

            <?php endif; ?>


            <?php if (
                isset($_GET['updated'])
                &&
                $_GET['updated'] === '1'
            ): ?>

                <div class="alert alert-success">

                    <div class="message-icon">
                        ✓
                    </div>

                    <div>

                        <strong>
                            Book Updated Successfully
                        </strong>

                        <p>
                            The book information has been updated.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="close-message"
                        onclick="this.parentElement.remove()"
                    >
                        ×
                    </button>

                </div>

            <?php endif; ?>


            <?php if (
                isset($_GET['deleted'])
                &&
                $_GET['deleted'] === '1'
            ): ?>

                <div class="alert alert-success">

                    <div class="message-icon">
                        ✓
                    </div>

                    <div>

                        <strong>
                            Book Deleted Successfully
                        </strong>

                        <p>
                            The book has been removed from the inventory.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="close-message"
                        onclick="this.parentElement.remove()"
                    >
                        ×
                    </button>

                </div>

            <?php endif; ?>


            <?php if (
                isset($_GET['delete_error'])
                &&
                $_GET['delete_error'] === 'borrowed'
            ): ?>

                <div class="alert alert-warning">

                    <div class="message-icon">
                        !
                    </div>

                    <div>

                        <strong>
                            Cannot Delete Book
                        </strong>

                        <p>
                            This book is currently borrowed and cannot be deleted.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="close-message"
                        onclick="this.parentElement.remove()"
                    >
                        ×
                    </button>

                </div>

            <?php endif; ?>


            <?php if (
                isset($_GET['delete_error'])
                &&
                $_GET['delete_error'] === '1'
            ): ?>

                <div class="alert alert-error">

                    <div class="message-icon">
                        !
                    </div>

                    <div>

                        <strong>
                            Delete Failed
                        </strong>

                        <p>
                            The book could not be deleted.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="close-message"
                        onclick="this.parentElement.remove()"
                    >
                        ×
                    </button>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 STATISTICS
            ================================================== -->

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon">
                        📚
                    </div>

                    <div>

                        <span>
                            Book Titles
                        </span>

                        <strong>
                            <?= number_format($totalTitles) ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ▣
                    </div>

                    <div>

                        <span>
                            Total Copies
                        </span>

                        <strong>
                            <?= number_format($totalCopies) ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ✓
                    </div>

                    <div>

                        <span>
                            Available
                        </span>

                        <strong>
                            <?= number_format($availableCopies) ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ↗
                    </div>

                    <div>

                        <span>
                            Borrowed
                        </span>

                        <strong>
                            <?= number_format($borrowedCopies) ?>
                        </strong>

                    </div>

                </div>


            </div>


            <!-- =================================================
                 SEARCH / FILTER
            ================================================== -->

            <div class="filter-card">

                <form
                    method="GET"
                    action="index.php"
                    class="filter-form"
                >

                    <div class="search-box">

                        <span class="search-icon">
                            ⌕
                        </span>

                        <input
                            type="text"
                            name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="Search by title, author, or ISBN..."
                        >

                    </div>


                    <select
                        name="category"
                        class="filter-select"
                    >

                        <option value="">
                            All Categories
                        </option>


                        <?php foreach ($categories as $cat): ?>

                            <option
                                value="<?= htmlspecialchars($cat) ?>"
                                <?= $category === $cat
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars($cat) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <select
                        name="status"
                        class="filter-select"
                    >

                        <option value="">
                            All Status
                        </option>


                        <option
                            value="Available"
                            <?= $status === 'Available'
                                ? 'selected'
                                : '' ?>
                        >
                            Available
                        </option>


                        <option
                            value="Unavailable"
                            <?= $status === 'Unavailable'
                                ? 'selected'
                                : '' ?>
                        >
                            Unavailable
                        </option>

                    </select>


                    <button
                        type="submit"
                        class="filter-button"
                    >
                        Filter
                    </button>


                    <?php if (
                        $search !== ''
                        ||
                        $category !== ''
                        ||
                        $status !== ''
                    ): ?>

                        <a
                            href="index.php"
                            class="reset-button"
                        >
                            Reset
                        </a>

                    <?php endif; ?>

                </form>

            </div>


            <!-- =================================================
                 BOOK TABLE
            ================================================== -->

            <div class="table-card">


                <div class="table-header">

                    <div>

                        <h3>
                            Library Books
                        </h3>

                        <p>

                            Showing

                            <strong>
                                <?= number_format($totalRecords) ?>
                            </strong>

                            book(s)

                        </p>

                    </div>

                </div>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>Book</th>
                                <th>ISBN</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Copies</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($books)): ?>

                            <tr>

                                <td
                                    colspan="8"
                                    class="empty-state"
                                >

                                    <div class="empty-icon">
                                        📚
                                    </div>

                                    <h3>
                                        No Books Found
                                    </h3>

                                    <p>
                                        There are no books matching your search.
                                    </p>


                                    <?php if (
                                        $search === ''
                                        &&
                                        $category === ''
                                        &&
                                        $status === ''
                                    ): ?>

                                        <a
                                            href="add.php"
                                            class="empty-button"
                                        >
                                            + Add Your First Book
                                        </a>

                                    <?php endif; ?>

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($books as $book): ?>

                                <?php

                                $total =
                                    (int) $book['quantity'];

                                $available =
                                    (int) $book['available_quantity'];

                                $borrowed =
                                    max(
                                        0,
                                        $total - $available
                                    );

                                ?>


                                <tr>


                                    <!-- BOOK -->

                                    <td>

                                        <div class="book-info">

                                            <div class="book-icon">
                                                📖
                                            </div>

                                            <div>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $book['title']
                                                    ) ?>
                                                </strong>


                                                <?php if (
                                                    !empty(
                                                        $book['publisher']
                                                    )
                                                ): ?>

                                                    <small>

                                                        <?= htmlspecialchars(
                                                            $book['publisher']
                                                        ) ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- ISBN -->

                                    <td>

                                        <?php if (
                                            !empty($book['isbn'])
                                        ): ?>

                                            <span class="isbn">

                                                <?= htmlspecialchars(
                                                    $book['isbn']
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="not-available">
                                                N/A
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- AUTHOR -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $book['author']
                                        ) ?>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <span class="category-badge">

                                            <?= htmlspecialchars(
                                                $book['category']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- COPIES -->

                                    <td>

                                        <div class="copies">

                                            <div>

                                                <strong>
                                                    <?= $available ?>
                                                </strong>

                                                <span>
                                                    available
                                                </span>

                                            </div>


                                            <?php if (
                                                $borrowed > 0
                                            ): ?>

                                                <small>

                                                    <?= $borrowed ?>

                                                    borrowed

                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    </td>


                                    <!-- LOCATION -->

                                    <td>

                                        <?php if (
                                            !empty($book['location'])
                                        ): ?>

                                            <span class="location">

                                                <?= htmlspecialchars(
                                                    $book['location']
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="not-available">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php if (
                                            $book['status'] === 'Available'
                                        ): ?>

                                            <span
                                                class="status-badge status-available"
                                            >

                                                <span>
                                                    ●
                                                </span>

                                                Available

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="status-badge status-unavailable"
                                            >

                                                <span>
                                                    ●
                                                </span>

                                                Unavailable

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="actions">


                                            <a
                                                href="edit.php?id=<?= (int) $book['id'] ?>"
                                                class="action-button edit"
                                                title="Edit Book"
                                            >
                                                ✎
                                            </a>


                                            <button
                                                type="button"
                                                class="action-button delete"
                                                title="Delete Book"
                                                onclick="deleteBook(
                                                    <?= (int) $book['id'] ?>,
                                                    '<?= htmlspecialchars(
                                                        $book['title'],
                                                        ENT_QUOTES
                                                    ) ?>'
                                                )"
                                            >
                                                ×
                                            </button>


                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     PAGINATION
                ================================================== -->

                <?php if ($totalPages > 1): ?>

                    <div class="pagination">


                        <div class="pagination-info">

                            Page

                            <strong>
                                <?= $page ?>
                            </strong>

                            of

                            <strong>
                                <?= $totalPages ?>
                            </strong>

                        </div>


                        <div class="pagination-buttons">


                            <?php

                            $queryParams = [];


                            if ($search !== '') {

                                $queryParams['search'] =
                                    $search;

                            }


                            if ($category !== '') {

                                $queryParams['category'] =
                                    $category;

                            }


                            if ($status !== '') {

                                $queryParams['status'] =
                                    $status;

                            }

                            ?>


                            <!-- PREVIOUS -->

                            <?php if ($page > 1): ?>

                                <?php

                                $queryParams['page'] =
                                    $page - 1;

                                $previousUrl =
                                    '?'
                                    .
                                    http_build_query(
                                        $queryParams
                                    );

                                ?>

                                <a
                                    href="<?= htmlspecialchars($previousUrl) ?>"
                                    class="page-button"
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

                                <?php

                                $queryParams['page'] =
                                    $i;

                                $pageUrl =
                                    '?'
                                    .
                                    http_build_query(
                                        $queryParams
                                    );

                                ?>

                                <a
                                    href="<?= htmlspecialchars($pageUrl) ?>"
                                    class="page-button <?= $i === $page ? 'active' : '' ?>"
                                >

                                    <?= $i ?>

                                </a>

                            <?php endfor; ?>


                            <!-- NEXT -->

                            <?php if ($page < $totalPages): ?>

                                <?php

                                $queryParams['page'] =
                                    $page + 1;

                                $nextUrl =
                                    '?'
                                    .
                                    http_build_query(
                                        $queryParams
                                    );

                                ?>

                                <a
                                    href="<?= htmlspecialchars($nextUrl) ?>"
                                    class="page-button"
                                >
                                    →
                                </a>

                            <?php endif; ?>


                        </div>

                    </div>

                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


<!-- =========================================================
     DELETE FORM
========================================================= -->

<form
    method="POST"
    action="index.php"
    id="deleteForm"
    style="display:none;"
>

    <input
        type="hidden"
        name="delete_book"
        value="1"
    >

    <input
        type="hidden"
        name="book_id"
        id="deleteBookId"
        value=""
    >

</form>


<!-- =========================================================
     BOOK JAVASCRIPT
========================================================= -->

<script src="../assets/js/books.js"></script>


</body>

</html>