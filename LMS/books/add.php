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

$pageTitle   = 'Add Book';
$currentPage = 'books';

$error = '';


/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/

$isbn             = '';
$title            = '';
$author           = '';
$publisher        = '';
$publication_year = '';
$category         = '';
$description      = '';
$quantity         = 1;
$location         = '';
$status           = 'Available';


/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */

    $isbn = trim(
        $_POST['isbn'] ?? ''
    );

    $title = trim(
        $_POST['title'] ?? ''
    );

    $author = trim(
        $_POST['author'] ?? ''
    );

    $publisher = trim(
        $_POST['publisher'] ?? ''
    );

    $publication_year = trim(
        $_POST['publication_year'] ?? ''
    );

    $category = trim(
        $_POST['category'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    $quantity = (int) (
        $_POST['quantity'] ?? 1
    );

    $location = trim(
        $_POST['location'] ?? ''
    );

    $status = trim(
        $_POST['status'] ?? 'Available'
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($title === '') {

        $error = 'Book title is required.';

    } elseif ($author === '') {

        $error = 'Author is required.';

    } elseif ($category === '') {

        $error = 'Category is required.';

    } elseif ($quantity < 1) {

        $error = 'Quantity must be at least 1.';

    } elseif (
        $publication_year !== ''
        &&
        (
            !ctype_digit($publication_year)
            ||
            (int) $publication_year < 1000
            ||
            (int) $publication_year > date('Y')
        )
    ) {

        $error = 'Please enter a valid publication year.';

    } elseif (
        !in_array(
            $status,
            [
                'Available',
                'Unavailable'
            ],
            true
        )
    ) {

        $error = 'Invalid book status.';

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE ISBN
    |--------------------------------------------------------------------------
    */

    if (
        $error === ''
        &&
        $isbn !== ''
    ) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM books
            WHERE isbn = ?
            LIMIT 1
        ");

        $stmt->execute([
            $isbn
        ]);


        if ($stmt->fetch()) {

            $error =
                'A book with this ISBN already exists.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT BOOK
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {

            /*
            |--------------------------------------------------------------------------
            | START TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | GENERATE UNIQUE BOOK CODE
            |--------------------------------------------------------------------------
            */

            do {

                $bookCode =
                    'BOOK-'
                    . date('Ymd')
                    . '-'
                    . random_int(1000, 9999);


                $checkCode = $pdo->prepare("
                    SELECT id
                    FROM books
                    WHERE book_code = ?
                    LIMIT 1
                ");

                $checkCode->execute([
                    $bookCode
                ]);

            } while ($checkCode->fetch());


            /*
            |--------------------------------------------------------------------------
            | INSERT BOOK
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("

                INSERT INTO books

                (
                    book_code,
                    isbn,
                    title,
                    author,
                    publisher,
                    publication_year,
                    category,
                    description,
                    quantity,
                    available_quantity,
                    location,
                    status
                )

                VALUES

                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )

            ");


            $stmt->execute([

                $bookCode,

                $isbn !== ''
                    ? $isbn
                    : null,

                $title,

                $author,

                $publisher !== ''
                    ? $publisher
                    : null,

                $publication_year !== ''
                    ? (int) $publication_year
                    : null,

                $category,

                $description !== ''
                    ? $description
                    : null,

                $quantity,

                $quantity,

                $location !== ''
                    ? $location
                    : null,

                $status

            ]);


            /*
            |--------------------------------------------------------------------------
            | ACTIVITY LOG
            |--------------------------------------------------------------------------
            */

            $descriptionLog =
                'Added new book: '
                . $title
                . ' by '
                . $author
                . '. Book Code: '
                . $bookCode
                . '. Quantity: '
                . $quantity
                . '.';


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
                    'CREATE',
                    ?
                )

            ");


            $logStmt->execute([

                $_SESSION['user_id'],

                $descriptionLog

            ]);


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            header(
                "Location: index.php?added=1"
            );

            exit();


        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | ROLLBACK
            |--------------------------------------------------------------------------
            */

            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }


            $error =
                'Unable to add the book. Database error: '
                . $e->getMessage();

        }

    }

}


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [

    'Information Technology',
    'Computer Science',
    'Business',
    'Education',
    'Engineering',
    'Science',
    'Mathematics',
    'Literature',
    'History',
    'Reference',
    'Other'

];

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


    <!-- GLOBAL CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- BOOKS CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/books.css"
    >

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



        <!-- =================================================
             PAGE CONTENT
        ================================================== -->

        <div class="books-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="page-heading">


                <div>

                    <h2>
                        Add New Book
                    </h2>

                    <p>
                        Add a new book to the library inventory.
                    </p>

                </div>


                <a
                    href="index.php"
                    class="back-button"
                >

                    ← Back to Books

                </a>


            </div>



            <!-- =================================================
                 ERROR
            ================================================== -->

            <?php if ($error !== ''): ?>

                <div class="alert alert-error">


                    <span class="alert-icon">
                        !
                    </span>


                    <div>

                        <strong>
                            Unable to add book
                        </strong>


                        <p>

                            <?= htmlspecialchars(
                                $error
                            ) ?>

                        </p>

                    </div>


                </div>

            <?php endif; ?>



            <!-- =================================================
                 FORM CARD
            ================================================== -->

            <div class="form-card">


                <!-- FORM HEADER -->

                <div class="form-card-header">


                    <div class="header-icon">
                        +
                    </div>


                    <div>

                        <h3>
                            Book Information
                        </h3>

                        <p>
                            Enter the details of the book below.
                        </p>

                    </div>


                </div>



                <!-- FORM -->

                <form
                    method="POST"
                    action=""
                    id="bookForm"
                >


                    <!-- =========================================
                         BASIC INFORMATION
                    ========================================== -->

                    <div class="form-section">


                        <h4>
                            Basic Information
                        </h4>


                        <div class="form-grid">


                            <!-- ISBN -->

                            <div class="form-group">

                                <label for="isbn">
                                    ISBN
                                </label>


                                <input
                                    type="text"
                                    id="isbn"
                                    name="isbn"
                                    value="<?= htmlspecialchars($isbn) ?>"
                                    maxlength="30"
                                    placeholder="978-XXXXXXXXXX"
                                >


                                <small>
                                    International Standard Book Number.
                                </small>

                            </div>



                            <!-- TITLE -->

                            <div class="form-group">

                                <label for="title">

                                    Book Title

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    value="<?= htmlspecialchars($title) ?>"
                                    maxlength="255"
                                    placeholder="Enter book title"
                                    required
                                >

                            </div>



                            <!-- AUTHOR -->

                            <div class="form-group">

                                <label for="author">

                                    Author

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    id="author"
                                    name="author"
                                    value="<?= htmlspecialchars($author) ?>"
                                    maxlength="150"
                                    placeholder="Enter author name"
                                    required
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
                                    value="<?= htmlspecialchars($publisher) ?>"
                                    maxlength="150"
                                    placeholder="Enter publisher"
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
                                    value="<?= htmlspecialchars($publication_year) ?>"
                                    min="1000"
                                    max="<?= date('Y') ?>"
                                    placeholder="<?= date('Y') ?>"
                                >

                            </div>



                            <!-- CATEGORY -->

                            <div class="form-group">

                                <label for="category">

                                    Category

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <select
                                    id="category"
                                    name="category"
                                    required
                                >


                                    <option value="">
                                        Select Category
                                    </option>


                                    <?php foreach ($categories as $item): ?>

                                        <option
                                            value="<?= htmlspecialchars($item) ?>"
                                            <?= $category === $item
                                                ? 'selected'
                                                : '' ?>
                                        >

                                            <?= htmlspecialchars($item) ?>

                                        </option>

                                    <?php endforeach; ?>


                                </select>

                            </div>


                        </div>


                    </div>



                    <!-- =========================================
                         INVENTORY INFORMATION
                    ========================================== -->

                    <div class="form-section">


                        <h4>
                            Inventory Information
                        </h4>


                        <div class="form-grid">


                            <!-- QUANTITY -->

                            <div class="form-group">

                                <label for="quantity">

                                    Quantity

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    value="<?= htmlspecialchars($quantity) ?>"
                                    min="1"
                                    max="9999"
                                    required
                                >


                                <small>
                                    Number of copies available in the library.
                                </small>

                            </div>



                            <!-- LOCATION -->

                            <div class="form-group">

                                <label for="location">
                                    Shelf / Location
                                </label>


                                <input
                                    type="text"
                                    id="location"
                                    name="location"
                                    value="<?= htmlspecialchars($location) ?>"
                                    maxlength="100"
                                    placeholder="Example: Shelf A-01"
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

                            </div>


                        </div>


                    </div>



                    <!-- =========================================
                         DESCRIPTION
                    ========================================== -->

                    <div class="form-section">


                        <h4>
                            Additional Information
                        </h4>


                        <div class="form-group">


                            <label for="description">
                                Description / Notes
                            </label>


                            <textarea
                                id="description"
                                name="description"
                                rows="5"
                                maxlength="1000"
                                placeholder="Enter additional information about the book..."
                            ><?= htmlspecialchars($description) ?></textarea>


                            <small>
                                Optional notes or description about the book.
                            </small>


                        </div>


                    </div>



                    <!-- =========================================
                         REQUIRED NOTE
                    ========================================== -->

                    <div class="required-note">

                        <span class="required">
                            *
                        </span>

                        Required fields

                    </div>



                    <!-- =========================================
                         ACTIONS
                    ========================================== -->

                    <div class="form-actions">


                        <a
                            href="index.php"
                            class="btn btn-secondary"
                        >

                            Cancel

                        </a>


                        <button
                            type="submit"
                            class="btn btn-primary"
                            id="saveButton"
                        >

                            + Add Book

                        </button>


                    </div>


                </form>


            </div>


        </div>


    </main>


</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="../assets/js/books.js"></script>


</body>

</html>