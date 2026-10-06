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

$pageTitle   = 'Borrow Book';
$currentPage = 'borrowing';

$error = '';



/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/

$borrowerId = '';
$bookId     = '';

$borrowDate = date('Y-m-d');

$dueDate = date(
    'Y-m-d',
    strtotime('+7 days')
);



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

    $borrowerId = (int) (
        $_POST['borrower_id'] ?? 0
    );

    $bookId = (int) (
        $_POST['book_id'] ?? 0
    );

    $borrowDate = trim(
        $_POST['borrow_date'] ?? ''
    );

    $dueDate = trim(
        $_POST['due_date'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($borrowerId <= 0) {

        $error =
            'Please select a borrower.';

    } elseif ($bookId <= 0) {

        $error =
            'Please select a book.';

    } elseif ($borrowDate === '') {

        $error =
            'Borrow date is required.';

    } elseif ($dueDate === '') {

        $error =
            'Due date is required.';

    } elseif ($dueDate < $borrowDate) {

        $error =
            'Due date cannot be earlier than the borrowing date.';

    }


    /*
    |--------------------------------------------------------------------------
    | PROCESS BORROWING
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
            | GET BOOK
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | The books table uses:
            |
            | quantity
            | available_quantity
            |
            | NOT:
            |
            | available_copies
            | total_copies
            |
            */

            $stmt = $pdo->prepare("

                SELECT

                    id,
                    book_code,
                    title,
                    author,
                    quantity,
                    available_quantity,
                    status

                FROM books

                WHERE id = ?

                FOR UPDATE

            ");


            $stmt->execute([
                $bookId
            ]);


            $book = $stmt->fetch(
                PDO::FETCH_ASSOC
            );



            /*
            |--------------------------------------------------------------------------
            | CHECK BOOK
            |--------------------------------------------------------------------------
            */

            if (!$book) {

                throw new Exception(
                    'The selected book does not exist.'
                );

            }



            /*
            |--------------------------------------------------------------------------
            | CHECK BOOK AVAILABILITY
            |--------------------------------------------------------------------------
            */

            if (
                (int) $book['available_quantity']
                <= 0
            ) {

                throw new Exception(
                    'This book currently has no available copies.'
                );

            }



            /*
            |--------------------------------------------------------------------------
            | CHECK BOOK STATUS
            |--------------------------------------------------------------------------
            */

            if (
                isset($book['status']) &&
                strtolower(
                    trim($book['status'])
                ) === 'unavailable'
            ) {

                throw new Exception(
                    'This book is currently unavailable.'
                );

            }



            /*
            |--------------------------------------------------------------------------
            | GET BORROWER
            |--------------------------------------------------------------------------
            |
            | borrowers.id is used as the value stored in
            | borrowings.borrower_id.
            |
            | borrowers.student_id is only the student's
            | displayed identification number.
            |
            */

            $stmt = $pdo->prepare("

                SELECT

                    id,
                    student_id,
                    full_name,
                    email,
                    contact,
                    course,
                    year_level,
                    status

                FROM borrowers

                WHERE id = ?

                FOR UPDATE

            ");


            $stmt->execute([
                $borrowerId
            ]);


            $borrower = $stmt->fetch(
                PDO::FETCH_ASSOC
            );



            /*
            |--------------------------------------------------------------------------
            | CHECK BORROWER
            |--------------------------------------------------------------------------
            */

            if (!$borrower) {

                throw new Exception(
                    'The selected borrower does not exist.'
                );

            }



            /*
            |--------------------------------------------------------------------------
            | CHECK BORROWER STATUS
            |--------------------------------------------------------------------------
            */

            if (
                isset($borrower['status']) &&
                strtolower(
                    trim($borrower['status'])
                ) !== 'active'
            ) {

                throw new Exception(
                    'This borrower is not currently active.'
                );

            }



            /*
            |--------------------------------------------------------------------------
            | INSERT BORROWING RECORD
            |--------------------------------------------------------------------------
            |
            | Based on your borrowings table:
            |
            | book_id
            | borrower_id
            | borrow_date
            | created_at
            | due_date
            | id
            | processed_by
            | return_date
            | status
            |
            | Therefore processed_by stores the logged-in
            | user's ID.
            |
            */

            $stmt = $pdo->prepare("

                INSERT INTO borrowings

                (
                    book_id,
                    borrower_id,
                    borrow_date,
                    due_date,
                    processed_by,
                    status
                )

                VALUES

                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'Borrowed'
                )

            ");


            $stmt->execute([

                $bookId,

                $borrowerId,

                $borrowDate,

                $dueDate,

                $_SESSION['user_id']

            ]);



            /*
            |--------------------------------------------------------------------------
            | REDUCE AVAILABLE QUANTITY
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("

                UPDATE books

                SET available_quantity =
                    available_quantity - 1

                WHERE id = ?

                AND available_quantity > 0

            ");


            $stmt->execute([
                $bookId
            ]);



            /*
            |--------------------------------------------------------------------------
            | VERIFY BOOK UPDATE
            |--------------------------------------------------------------------------
            */

            if ($stmt->rowCount() !== 1) {

                throw new Exception(
                    'Unable to update the book availability.'
                );

            }



            /*
            |--------------------------------------------------------------------------
            | UPDATE BOOK STATUS IF NO COPIES REMAIN
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("

                UPDATE books

                SET status = 'Unavailable'

                WHERE id = ?

                AND available_quantity <= 0

            ");


            $stmt->execute([
                $bookId
            ]);



            /*
            |--------------------------------------------------------------------------
            | ACTIVITY LOG
            |--------------------------------------------------------------------------
            */

            $descriptionLog =

                'Borrowed book: '

                . $book['title']

                . ' by '

                . $borrower['full_name']

                . ' ('

                . $borrower['student_id']

                . '). Due date: '

                . $dueDate

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
                    'BORROW',
                    ?
                )

            ");


            $logStmt->execute([

                $_SESSION['user_id'],

                $descriptionLog

            ]);



            /*
            |--------------------------------------------------------------------------
            | COMMIT TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->commit();



            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            |
            | Redirecting prevents duplicate submission when
            | the user refreshes the page.
            |
            */

            header(
                "Location: index.php?borrowed=1"
            );

            exit();


        } catch (PDOException $e) {


            /*
            |--------------------------------------------------------------------------
            | ROLLBACK DATABASE TRANSACTION
            |--------------------------------------------------------------------------
            */

            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }


            $error =

                'Unable to process the borrowing transaction. '

                . $e->getMessage();


        } catch (Exception $e) {


            /*
            |--------------------------------------------------------------------------
            | ROLLBACK
            |--------------------------------------------------------------------------
            */

            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }


            $error =
                $e->getMessage();

        }

    }

}



/*
|--------------------------------------------------------------------------
| GET ACTIVE BORROWERS
|--------------------------------------------------------------------------
|
| borrowers.id:
|   Used internally as the foreign key.
|
| borrowers.student_id:
|   Displayed to the user.
|
*/

$stmt = $pdo->query("

    SELECT

        id,
        student_id,
        full_name

    FROM borrowers

    WHERE status = 'Active'

    ORDER BY full_name ASC

");


$borrowers = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);



/*
|--------------------------------------------------------------------------
| GET AVAILABLE BOOKS
|--------------------------------------------------------------------------
|
| The books table uses available_quantity.
|
*/

$stmt = $pdo->query("

    SELECT

        id,
        book_code,
        title,
        author,
        isbn,
        available_quantity

    FROM books

    WHERE available_quantity > 0

    ORDER BY title ASC

");


$books = $stmt->fetchAll(
    PDO::FETCH_ASSOC
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
        <?= htmlspecialchars($pageTitle) ?>
    </title>


    <!-- GLOBAL CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- BOOKS / FORM CSS -->

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
                        Borrow Book
                    </h2>

                    <p>
                        Record a new book borrowing transaction.
                    </p>

                </div>


                <a
                    href="index.php"
                    class="back-button"
                >

                    ← Back to Borrowing

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
                            Unable to borrow book
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


                <!-- =================================================
                     FORM HEADER
                ================================================== -->

                <div class="form-card-header">


                    <div class="header-icon">
                        +
                    </div>


                    <div>

                        <h3>
                            Borrowing Information
                        </h3>

                        <p>
                            Enter the details of the borrowing transaction below.
                        </p>

                    </div>


                </div>



                <!-- =================================================
                     FORM
                ================================================== -->

                <form
                    method="POST"
                    action=""
                    id="borrowForm"
                >



                    <!-- =============================================
                         BORROWING INFORMATION
                    ============================================== -->

                    <div class="form-section">


                        <h4>
                            Borrowing Information
                        </h4>


                        <div class="form-grid">



                            <!-- =====================================
                                 BORROWER
                            ====================================== -->

                            <div class="form-group">


                                <label for="borrower_id">

                                    Borrower

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <select
                                    id="borrower_id"
                                    name="borrower_id"
                                    required
                                >


                                    <option value="">
                                        Select Borrower
                                    </option>


                                    <?php foreach (
                                        $borrowers
                                        as $borrower
                                    ): ?>


                                        <option
                                            value="<?= (int) $borrower['id'] ?>"
                                            <?= (
                                                (int) $borrowerId
                                                ===
                                                (int) $borrower['id']
                                            )
                                                ? 'selected'
                                                : '' ?>
                                        >


                                            <?= htmlspecialchars(
                                                $borrower['full_name']
                                            ) ?>


                                            —

                                            <?= htmlspecialchars(
                                                $borrower['student_id']
                                            ) ?>


                                        </option>


                                    <?php endforeach; ?>


                                </select>


                                <small>

                                    Select an active borrower.

                                </small>


                                <?php if (
                                    empty($borrowers)
                                ): ?>

                                    <small
                                        class="error-text"
                                    >

                                        No active borrowers found.


                                        <a
                                            href="../borrowers/add.php"
                                        >

                                            Add a borrower

                                        </a>

                                    </small>

                                <?php endif; ?>


                            </div>



                            <!-- =====================================
                                 BOOK
                            ====================================== -->

                            <div class="form-group">


                                <label for="book_id">

                                    Book

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <select
                                    id="book_id"
                                    name="book_id"
                                    required
                                >


                                    <option value="">
                                        Select Book
                                    </option>


                                    <?php foreach (
                                        $books
                                        as $book
                                    ): ?>


                                        <option
                                            value="<?= (int) $book['id'] ?>"
                                            data-copies="<?= (int) $book['available_quantity'] ?>"
                                            <?= (
                                                (int) $bookId
                                                ===
                                                (int) $book['id']
                                            )
                                                ? 'selected'
                                                : '' ?>
                                        >


                                            <?= htmlspecialchars(
                                                $book['title']
                                            ) ?>


                                            —

                                            <?= htmlspecialchars(
                                                $book['author']
                                            ) ?>


                                            | Available:

                                            <?= (int) $book[
                                                'available_quantity'
                                            ] ?>


                                        </option>


                                    <?php endforeach; ?>


                                </select>


                                <small
                                    id="bookAvailability"
                                >

                                    Select a book to see its available copies.

                                </small>


                                <?php if (
                                    empty($books)
                                ): ?>

                                    <small
                                        class="error-text"
                                    >

                                        No books are currently available.

                                        <a
                                            href="../books/add.php"
                                        >

                                            Add a book

                                        </a>

                                    </small>

                                <?php endif; ?>


                            </div>



                        </div>


                    </div>



                    <!-- =============================================
                         DATES
                    ============================================== -->

                    <div class="form-section">


                        <h4>
                            Borrowing Dates
                        </h4>


                        <div class="form-grid">



                            <!-- BORROW DATE -->

                            <div class="form-group">


                                <label for="borrow_date">

                                    Borrow Date

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="date"
                                    id="borrow_date"
                                    name="borrow_date"
                                    value="<?= htmlspecialchars(
                                        $borrowDate
                                    ) ?>"
                                    required
                                >


                                <small>

                                    Date when the book was borrowed.

                                </small>


                            </div>



                            <!-- DUE DATE -->

                            <div class="form-group">


                                <label for="due_date">

                                    Due Date

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="date"
                                    id="due_date"
                                    name="due_date"
                                    value="<?= htmlspecialchars(
                                        $dueDate
                                    ) ?>"
                                    required
                                >


                                <small>

                                    Date when the book should be returned.

                                </small>


                            </div>



                        </div>


                    </div>



                    <!-- =============================================
                         INFORMATION
                    ============================================== -->

                    <div class="form-section">


                        <h4>
                            Transaction Information
                        </h4>


                        <div class="borrow-info-box">


                            <div class="borrow-info-icon">
                                i
                            </div>


                            <div>


                                <strong>
                                    Important
                                </strong>


                                <p>

                                    Borrowing this book will automatically
                                    decrease its available quantity by one.
                                    When no copies remain, the book will
                                    automatically be marked as unavailable.

                                </p>


                            </div>


                        </div>


                    </div>



                    <!-- =============================================
                         REQUIRED NOTE
                    ============================================== -->

                    <div class="required-note">

                        <span class="required">
                            *
                        </span>

                        Required fields

                    </div>



                    <!-- =============================================
                         ACTIONS
                    ============================================== -->

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
                            id="borrowButton"
                        >

                            + Borrow Book

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

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | BOOK AVAILABILITY
        |--------------------------------------------------------------------------
        */

        const bookSelect =
            document.getElementById(
                'book_id'
            );


        const availability =
            document.getElementById(
                'bookAvailability'
            );


        function updateAvailability() {


            const selectedOption =
                bookSelect.options[
                    bookSelect.selectedIndex
                ];


            if (
                !selectedOption ||
                !selectedOption.value
            ) {

                availability.textContent =
                    'Select a book to see its available copies.';

                return;

            }


            const copies =
                selectedOption.dataset.copies;


            availability.textContent =
                'Available copies: '
                + copies;


        }


        bookSelect.addEventListener(
            'change',
            updateAvailability
        );


        updateAvailability();



        /*
        |--------------------------------------------------------------------------
        | BORROW DATE / DUE DATE
        |--------------------------------------------------------------------------
        */

        const borrowDate =
            document.getElementById(
                'borrow_date'
            );


        const dueDate =
            document.getElementById(
                'due_date'
            );


        dueDate.min =
            borrowDate.value;


        borrowDate.addEventListener(
            'change',
            function () {


                dueDate.min =
                    borrowDate.value;


                if (
                    dueDate.value !== ''
                    &&
                    dueDate.value <
                    borrowDate.value
                ) {

                    dueDate.value =
                        borrowDate.value;

                }


            }
        );



        /*
        |--------------------------------------------------------------------------
        | FORM SUBMISSION
        |--------------------------------------------------------------------------
        */

        const form =
            document.getElementById(
                'borrowForm'
            );


        const button =
            document.getElementById(
                'borrowButton'
            );


        form.addEventListener(
            'submit',
            function (event) {


                const borrower =
                    document.getElementById(
                        'borrower_id'
                    );


                const book =
                    document.getElementById(
                        'book_id'
                    );


                if (
                    !borrower.value
                    ||
                    !book.value
                ) {

                    return;

                }


                const selectedBook =
                    book.options[
                        book.selectedIndex
                    ];


                const bookName =
                    selectedBook.text;


                const confirmed =
                    confirm(

                        'Are you sure you want to borrow this book?'

                        + '\n\n'

                        + bookName

                    );


                if (!confirmed) {

                    event.preventDefault();

                    return;

                }


                button.disabled = true;


                button.textContent =
                    'Processing...';


            }
        );


    }
);

</script>


</body>

</html>