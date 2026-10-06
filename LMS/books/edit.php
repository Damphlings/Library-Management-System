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

$pageTitle = 'Edit Book';
$currentPage = 'books';


/*
|--------------------------------------------------------------------------
| GET BOOK ID
|--------------------------------------------------------------------------
*/

$bookId = (int) ($_GET['id'] ?? 0);


if ($bookId <= 0) {

    header("Location: index.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| LOAD BOOK
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
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
        status
    FROM books
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$bookId]);

$book = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$book) {

    header("Location: index.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| UPDATE BOOK
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $bookCode =
        trim($_POST['book_code'] ?? '');

    $isbn =
        trim($_POST['isbn'] ?? '');

    $title =
        trim($_POST['title'] ?? '');

    $author =
        trim($_POST['author'] ?? '');

    $category =
        trim($_POST['category'] ?? '');

    $publisher =
        trim($_POST['publisher'] ?? '');

    $publicationYear =
        trim($_POST['publication_year'] ?? '');

    $description =
        trim($_POST['description'] ?? '');

    $yearPublished =
        trim($_POST['year_published'] ?? '');

    $quantity =
        (int) ($_POST['quantity'] ?? 0);

    $availableQuantity =
        (int) ($_POST['available_quantity'] ?? 0);

    $location =
        trim($_POST['location'] ?? '');

    $status =
        trim($_POST['status'] ?? 'Available');


    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($title === '' || $author === '') {

        $error =
            'Book title and author are required.';

    } elseif ($quantity < 0) {

        $error =
            'Quantity cannot be negative.';

    } elseif (
        $availableQuantity < 0 ||
        $availableQuantity > $quantity
    ) {

        $error =
            'Available quantity must be between 0 and the total quantity.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | UPDATE DATABASE
            |--------------------------------------------------------------------------
            */

            $updateStmt = $pdo->prepare("
                UPDATE books
                SET
                    book_code = ?,
                    isbn = ?,
                    title = ?,
                    author = ?,
                    category = ?,
                    publisher = ?,
                    publication_year = ?,
                    description = ?,
                    year_published = ?,
                    quantity = ?,
                    available_quantity = ?,
                    location = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $updateStmt->execute([

                $bookCode !== ''
                    ? $bookCode
                    : null,

                $isbn !== ''
                    ? $isbn
                    : null,

                $title,

                $author,

                $category !== ''
                    ? $category
                    : null,

                $publisher !== ''
                    ? $publisher
                    : null,

                $publicationYear !== ''
                    ? (int) $publicationYear
                    : null,

                $description !== ''
                    ? $description
                    : null,

                $yearPublished !== ''
                    ? (int) $yearPublished
                    : null,

                $quantity,

                $availableQuantity,

                $location !== ''
                    ? $location
                    : null,

                $status,

                $bookId

            ]);


            /*
            |--------------------------------------------------------------------------
            | ACTIVITY LOG
            |--------------------------------------------------------------------------
            */

            $logDescription =
                'Updated book: ' .
                $title .
                ' (ID: ' .
                $bookId .
                ').';

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
                        'UPDATE',
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
                "Location: index.php?updated=1"
            );

            exit();

        } catch (PDOException $e) {

            $error =
                'Unable to update the book. Please try again.';

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
             TOP BAR
        ================================================== -->

        <header class="topbar">

            <div>

                <h1>
                    <?= htmlspecialchars($pageTitle) ?>
                </h1>

                <p>
                    Library Management System
                </p>

            </div>


            <div class="topbar-user">

                <div class="topbar-avatar">

                    <?= strtoupper(
                        substr($currentUser, 0, 1)
                    ) ?>

                </div>


                <div>

                    <strong>
                        <?= htmlspecialchars($currentUser) ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(
                            ucfirst($currentRole)
                        ) ?>
                    </span>

                </div>

            </div>

        </header>


        <!-- =================================================
             EDIT BOOK
        ================================================== -->

        <div class="books-page">


            <div class="page-heading">

                <div>

                    <h2>
                        Edit Book
                    </h2>

                    <p>
                        Update the information for this book.
                    </p>

                </div>


                <a
                    href="index.php"
                    class="reset-button"
                >
                    ← Back to Inventory
                </a>

            </div>


            <!-- =================================================
                 ERROR MESSAGE
            ================================================== -->

            <?php if (!empty($error)): ?>

                <div class="alert alert-error">

                    <div class="message-icon">
                        !
                    </div>

                    <div>

                        <strong>
                            Update Failed
                        </strong>

                        <p>
                            <?= htmlspecialchars($error) ?>
                        </p>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 EDIT FORM
            ================================================== -->

            <div class="table-card">


                <form
                    method="POST"
                    action=""
                    class="book-form"
                >


                    <!-- BOOK CODE -->

                    <div class="form-group">

                        <label for="book_code">
                            Book Code
                        </label>

                        <input
                            type="text"
                            id="book_code"
                            name="book_code"
                            value="<?= htmlspecialchars($book['book_code'] ?? '') ?>"
                        >

                    </div>


                    <!-- ISBN -->

                    <div class="form-group">

                        <label for="isbn">
                            ISBN
                        </label>

                        <input
                            type="text"
                            id="isbn"
                            name="isbn"
                            value="<?= htmlspecialchars($book['isbn'] ?? '') ?>"
                        >

                    </div>


                    <!-- TITLE -->

                    <div class="form-group">

                        <label for="title">
                            Title
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="title"
                            name="title"
                            value="<?= htmlspecialchars($book['title'] ?? '') ?>"
                            required
                        >

                    </div>


                    <!-- AUTHOR -->

                    <div class="form-group">

                        <label for="author">
                            Author
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="author"
                            name="author"
                            value="<?= htmlspecialchars($book['author'] ?? '') ?>"
                            required
                        >

                    </div>


                    <!-- CATEGORY -->

                    <div class="form-group">

                        <label for="category">
                            Category
                        </label>

                        <input
                            type="text"
                            id="category"
                            name="category"
                            value="<?= htmlspecialchars($book['category'] ?? '') ?>"
                        >

                    </div>


                    <!-- PUBLISHER -->

                    <div class="form-group">

                        <label for="publisher">
                            Publisher
                        </label>

                        <input
                            type="text"
                            id="publisher"
                            name="publisher"
                            value="<?= htmlspecialchars($book['publisher'] ?? '') ?>"
                        >

                    </div>


                    <!-- PUBLICATION YEAR -->

                    <div class="form-group">

                        <label for="publication_year">
                            Publication Year
                        </label>

                        <input
                            type="number"
                            id="publication_year"
                            name="publication_year"
                            value="<?= htmlspecialchars($book['publication_year'] ?? '') ?>"
                        >

                    </div>


                    <!-- YEAR PUBLISHED -->

                    <div class="form-group">

                        <label for="year_published">
                            Year Published
                        </label>

                        <input
                            type="number"
                            id="year_published"
                            name="year_published"
                            value="<?= htmlspecialchars($book['year_published'] ?? '') ?>"
                        >

                    </div>


                    <!-- QUANTITY -->

                    <div class="form-group">

                        <label for="quantity">
                            Total Quantity
                        </label>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            min="0"
                            value="<?= (int) $book['quantity'] ?>"
                        >

                    </div>


                    <!-- AVAILABLE -->

                    <div class="form-group">

                        <label for="available_quantity">
                            Available Quantity
                        </label>

                        <input
                            type="number"
                            id="available_quantity"
                            name="available_quantity"
                            min="0"
                            value="<?= (int) $book['available_quantity'] ?>"
                        >

                    </div>


                    <!-- LOCATION -->

                    <div class="form-group">

                        <label for="location">
                            Location
                        </label>

                        <input
                            type="text"
                            id="location"
                            name="location"
                            value="<?= htmlspecialchars($book['location'] ?? '') ?>"
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label for="status">
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                        >

                            <option
                                value="Available"
                                <?= $book['status'] === 'Available'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Available
                            </option>

                            <option
                                value="Unavailable"
                                <?= $book['status'] === 'Unavailable'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Unavailable
                            </option>

                        </select>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group form-full">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                        ><?= htmlspecialchars($book['description'] ?? '') ?></textarea>

                    </div>


                    <!-- FORM ACTIONS -->

                    <div class="form-actions">

                        <a
                            href="index.php"
                            class="reset-button"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="add-book-button"
                        >
                            Save Changes
                        </button>

                    </div>


                </form>


            </div>


        </div>


    </main>


</div>


<!-- =========================================================
     MOBILE SIDEBAR
========================================================= -->

<script>

function toggleSidebar() {

    const sidebar =
        document.querySelector('.sidebar');

    if (sidebar) {

        sidebar.classList.toggle('show');

    }

}

</script>


</body>

</html>